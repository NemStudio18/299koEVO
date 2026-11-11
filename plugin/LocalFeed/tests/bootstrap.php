<?php
/**
 * Bootstrap de test isolé pour le plugin LocalFeed.
 * 
 * GARANTIES DE SÉCURITÉ :
 * 1. Utilise exclusivement le serveur MySQL local (127.0.0.1:3307) sur une base éphémère localfeed_test_db.
 * 2. Aucun accès ni dépendance vers le serveur ou la base de production.
 * 3. Aucune altération des classes métier de production.
 */

define('ROOT', realpath(__DIR__ . '/../../../../') . '/');
define('DATA', ROOT . 'data/');
define('TEST_DB_NAME', 'localfeed_test_db');

// Autoloader Core & LocalFeed
require_once ROOT . 'install_vierge/common/Core/Plugin/Plugin.php';
require_once ROOT . 'install_vierge/plugin/LocalFeed/LocalFeed.php';

// Chargement des classes du Core nécessaires
require_once ROOT . 'install_vierge/common/Core/Interfaces/SqlDriverInterface.php';
require_once ROOT . 'install_vierge/common/Core/Database.php';

class TestHarness {
    public static PDO $pdo;
    public static int $testsRun = 0;
    public static int $testsPassed = 0;
    public static int $testsFailed = 0;
    public static array $failures = [];
    public static array $sqlErrors = [];

    public static function initDatabase(): void {
        $host = '127.0.0.1';
        $port = 3307;
        $user = 'root';
        $pass = 'root';

        // 1. Connexion initiale pour créer la base de test isolée
        try {
            $rootPdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS " . TEST_DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        } catch (\PDOException $e) {
            echo "❌ Impossible de se connecter au MySQL local de test (127.0.0.1:3307) : " . $e->getMessage() . "\n";
            exit(1);
        }

        // 2. Connexion dédiée à la base de test
        self::$pdo = new PDO("mysql:host={$host};port={$port};dbname=" . TEST_DB_NAME . ";charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // 3. Injecter la connexion de test dans le singleton Core\Database via réflexion
        $ref = new \ReflectionClass(\Core\Database::class);
        $dbInstance = $ref->newInstanceWithoutConstructor();

        $connProp = $ref->getProperty('connections');
        $connProp->setAccessible(true);
        $connProp->setValue($dbInstance, ['default' => self::$pdo]);

        $configsProp = $ref->getProperty('configs');
        $configsProp->setAccessible(true);
        $configsProp->setValue($dbInstance, [
            'default' => [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'dbname' => TEST_DB_NAME,
                'user' => $user,
                'password' => $pass
            ]
        ]);

        $instProp = $ref->getProperty('instance');
        $instProp->setAccessible(true);
        $instProp->setValue(null, $dbInstance);

        // 4. Initialiser la structure des tables
        self::resetSchema();
    }

    public static function resetSchema(): void {
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        self::$pdo->exec("DROP TABLE IF EXISTS content_reactions;");
        self::$pdo->exec("DROP TABLE IF EXISTS content_reactions_backup;");
        self::$pdo->exec("DROP TABLE IF EXISTS poi_posts;");
        self::$pdo->exec("DROP TABLE IF EXISTS agenda_events;");
        self::$pdo->exec("DROP TABLE IF EXISTS offers;");
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // Table des réactions (fichier de migration officiel)
        $migrationSql = file_get_contents(ROOT . 'install_vierge/plugin/LocalFeed/migrations/001_create_content_reactions.sql');
        self::$pdo->exec($migrationSql);

        // Table poi_posts (schéma réel)
        self::$pdo->exec("
            CREATE TABLE poi_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                poi_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
                author_user_id INT NOT NULL DEFAULT 1,
                post_type ENUM('news', 'special_offer', 'photo', 'event', 'alert') DEFAULT 'news',
                title VARCHAR(255) NULL,
                content TEXT NOT NULL,
                status ENUM('published', 'draft', 'archived') DEFAULT 'published',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table agenda_events (schéma réel)
        self::$pdo->exec("
            CREATE TABLE agenda_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'approved',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table offers (schéma réel)
        self::$pdo->exec("
            CREATE TABLE offers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
                valid_until DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Insertion des fixtures
        // 1. Posts
        self::$pdo->exec("INSERT INTO poi_posts (id, title, content, status) VALUES 
            (101, 'Post Public Valide', 'Contenu visible', 'published'),
            (102, 'Post Brouillon', 'En cours de rédaction', 'draft'),
            (103, 'Post Archivé', 'Ancien contenu', 'archived');
        ");

        // 2. Events
        self::$pdo->exec("INSERT INTO agenda_events (id, title, status) VALUES 
            (201, 'Concert de Printemps', 'approved'),
            (202, 'Atelier en attente', 'pending');
        ");

        // 3. Offers
        self::$pdo->exec("INSERT INTO offers (id, title, status, valid_until) VALUES 
            (301, 'Offre 20% Réduction', 'ACTIVE', DATE_ADD(NOW(), INTERVAL 30 DAY)),
            (302, 'Offre Expirée', 'ACTIVE', DATE_SUB(NOW(), INTERVAL 1 DAY)),
            (303, 'Offre Désactivée', 'INACTIVE', DATE_ADD(NOW(), INTERVAL 30 DAY));
        ");

        // 4. Blog (Fichier JSON de test dans data/plugin/blog/blog.json)
        $blogDir = DATA . 'plugin/blog';
        if (!is_dir($blogDir)) {
            mkdir($blogDir, 0777, true);
        }
        $testBlogArticles = [
            [
                'id' => 401,
                'name' => 'Article Blog Publié',
                'draft' => '0',
                'content' => 'Texte public'
            ],
            [
                'id' => 402,
                'name' => 'Article Blog Brouillon',
                'draft' => '1',
                'content' => 'Texte secret'
            ]
        ];
        file_put_contents($blogDir . '/blog.json', json_encode($testBlogArticles, JSON_PRETTY_PRINT));
    }

    public static function assert(string $description, bool $condition, string $detail = ''): void {
        self::$testsRun++;
        if ($condition) {
            self::$testsPassed++;
            echo "   ✅ PASS: {$description}\n";
        } else {
            self::$testsFailed++;
            $msg = "   ❌ FAIL: {$description}" . ($detail ? " ({$detail})" : "");
            self::$failures[] = $msg;
            echo "{$msg}\n";
        }
    }
}

TestHarness::initDatabase();
