<?php

namespace Core\Auth;

defined('ROOT') or exit('Access denied!');

/**
 * Service gérant le Mode Test Administrateur.
 * Permet de simuler des écritures en session sans impacter les fichiers réels.
 */
class AdminTestMode
{
    private static $instance = null;
    private bool $active = false;
    private $impersonatedUserId = null; // Mixed: int or string

    private function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->active = $_SESSION['admin_test_mode_active'] ?? false;
        $this->impersonatedUserId = $_SESSION['admin_test_mode_user_id'] ?? null;

        // Hook global pour simuler la propriété d'entité (utilisé par Offers, etc.)
        if (class_exists('\Core\Core')) {
            \Core\Core::getInstance()->addHook('entity:ownership-verify', [$this, 'handleOwnershipVerify']);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isActive(): bool
    {
        // On ne doit JAMAIS appeler getCurrentUser() ici car Database/JsonStorage 
        // appellent isActive(), et getCurrentUser() peut appeler Database/JsonStorage.
        // On se fie au flag en session qui n'a pu être mis que par un admin via le contrôleur dédié.
        return $this->active;
    }

    public function activate($userId = null): void
    {
        $_SESSION['admin_test_mode_active'] = true;
        $_SESSION['admin_test_mode_user_id'] = $userId;
        // Réinitialiser le stockage volatil pour ce nouvel utilisateur
        $_SESSION['volatile_sql_tables'] = [];
        $_SESSION['volatile_sql_logs'] = [];
        $_SESSION['volatile_json'] = [];
        $this->active = true;
        $this->impersonatedUserId = $userId;

        // Force AuthService to reload the user next time (to take impersonation into account immediately)
        \Core\Core::getInstance()->auth()->clearUserCache();
        
        // Vider le cache des politiques d'accès pour éviter les faux positifs du rôle précédent
        if (class_exists('\Core\Navigation\PolicyCache')) {
            \Core\Navigation\PolicyCache::getInstance()->flush();
        }

        // Auto-seed des fixtures si c'est un test user
        if ($userId) {
            $this->seedTestUserFixtures($userId);
        }
    }

    /**
     * Charge et injecte les fixtures volatiles pour un test user
     */
    private function seedTestUserFixtures($userId): void
    {
        $fixturesFile = DATA_PLUGIN . 'demo/test_fixtures.json';
        if (!is_file($fixturesFile)) return;

        $allFixtures = json_decode(file_get_contents($fixturesFile), true);
        if (!is_array($allFixtures)) return;

        $userFixtures = $allFixtures[(string)$userId] ?? null;
        if (!is_array($userFixtures)) return;

        // Mappings automatiques pour les clés communes vers leurs fichiers JSON respectifs
        // Si une clé est présente ici, les données seront traitées comme du JSON volatil.
        // Sinon, elles seront traitées comme des lignes SQL volatiles.
        $jsonMappings = [
            'notifications' => 'notifications/notifications.json',
            'offers' => 'offers/offers.json',
            'agenda_events' => 'agenda/events.json',
            'favorites' => 'PoiFavorites/favorites.json',
            'forum_posts' => 'forum/posts.json',
            'bugtracker_reports' => 'BugTracker/bugs.json',
            'galerie_items' => 'galerie/galerie.json',
            'newsletter_subscribers' => 'newsletter/subscribers.json',
            'parrainage_referrals' => 'parrainage/referrals.json',
            'poi_claims' => 'claim/claims.json',
            'poi_public' => 'poidata/poi.json',
            'subscriptions' => 'subscription/subscriptions.json',
            'poi_reviews' => 'PoiReviews/reviews.json',
            'users' => 'core/auth/users.json',
            'blog' => 'blog/blog.json',
            'gamification_points' => 'gamification/transactions.json'
        ];

        foreach ($userFixtures as $key => $data) {
            if ($key === '_files' && is_array($data)) {
                 foreach ($data as $relativePath => $fileData) {
                     // Support for both 'plugin/foo/bar.json' and just 'foo/bar.json' (assumed plugin data)
                     if (strpos($relativePath, 'core/') === 0) {
                         $fullPath = DATA . $relativePath;
                     } else {
                         // Default to plugin data
                         $fullPath = DATA_PLUGIN . $relativePath;
                     }
                     $this->setVolatileJson($fullPath, $fileData);
                 }
                 continue;
            }

            if (!is_array($data)) continue;

            // Redirection vers JSON volatil si mappé
            if (isset($jsonMappings[$key])) {
                $relPath = $jsonMappings[$key];
                if (str_starts_with($relPath, 'core/')) {
                    $fullPath = DATA . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
                } else {
                    $fullPath = DATA_PLUGIN . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
                }
                $this->setVolatileJson($fullPath, $data);
                continue;
            }

            // Par défaut : lignes SQL volatiles
            foreach ($data as $row) {
                $this->addVolatileRow($key, $row);
            }
        }
    }

    public function deactivate(): void
    {
        unset($_SESSION['admin_test_mode_active']);
        unset($_SESSION['admin_test_mode_user_id']);
        unset($_SESSION['volatile_json']);
        unset($_SESSION['volatile_sql']);
        $this->active = false;
        $this->impersonatedUserId = null;

        // Vider le cache des politiques d'accès lors du retour au mode normal
        if (class_exists('\Core\Navigation\PolicyCache')) {
            \Core\Navigation\PolicyCache::getInstance()->flush();
        }
        
        // Force reload original user
        \Core\Core::getInstance()->auth()->clearUserCache();
    }

    public function getImpersonatedUserId(): mixed
    {
        return $this->impersonatedUserId;
    }

    private function normalizePath(string $path): string
    {
        // On normalise tout en slash (/) pour avoir une clé cohérente en session quel que soit l'OS
        return str_replace(['\\', '//'], '/', $path);
    }

    /**
     * Gère le stockage volatil pour le JSON
     */
    public function getVolatileJson(string $filePath): ?array
    {
        return $_SESSION['volatile_json'][$this->normalizePath($filePath)] ?? null;
    }

    public function setVolatileJson(string $filePath, array $data): void
    {
        $_SESSION['volatile_json'][$this->normalizePath($filePath)] = $data;
    }

    /**
     * Gère le stockage volatil pour le SQL (Simulation d'écritures)
     */
    public function recordSqlWrite(string $sql, array $params): void
    {
        $sqlClean = trim(strtoupper($sql));
        
        // Tentative d'extraire le nom de la table pour les INSERT simples
        if (preg_match('/INSERT\s+INTO\s+([a-zA-Z0-9_]+)/i', $sql, $matches)) {
            $table = $matches[1];
            // Pour la simulation, on ne parse pas tout le SQL complexe, 
            // on stocke juste l'intention si besoin. 
            // Mais pour que l'UI suive, on va stocker les objets créés.
        }

        $_SESSION['volatile_sql_logs'][] = [
            'sql' => $sql,
            'params' => $params,
            'time' => time()
        ];
    }

    /**
     * Ajoute une ligne volatile à une table pour simulation de SELECT
     */
    public function addVolatileRow(string $table, array $row): void
    {
        if (!isset($_SESSION['volatile_sql_tables'][$table])) {
            $_SESSION['volatile_sql_tables'][$table] = [];
        }
        // Générer un ID fictif si absent
        if (!isset($row['id'])) {
            $row['id'] = 999000 + count($_SESSION['volatile_sql_tables'][$table]);
        }
        $_SESSION['volatile_sql_tables'][$table][] = $row;
    }

    public function getVolatileRows(string $table): array
    {
        return $_SESSION['volatile_sql_tables'][$table] ?? [];
    }

    /**
     * Retourne les données fixture d'un utilisateur de test, ou null si ce n'est pas un test user
     */
    public function getTestUserFixture(): ?array
    {
        $userId = $this->getImpersonatedUserId();
        if (!$userId) return null;

        $testUsersFile = DATA_PLUGIN . 'demo/test_users.json';
        if (!is_file($testUsersFile)) return null;

        $testUsers = json_decode(file_get_contents($testUsersFile), true);
        if (!is_array($testUsers)) return null;

        foreach ($testUsers as $tu) {
            if (($tu['id'] ?? '') == $userId) {
                return $tu;
            }
        }
        return null;
    }

    /**
     * Vérifie si l'utilisateur courant impersonné est un test user avec un rôle spécifique
     */
    public function isTestUserWithRole(string $role): bool
    {
        $fixture = $this->getTestUserFixture();
        if ($fixture === null) return false;
        
        $userRole = $fixture['_test_role'] ?? '';
        
        // Match exact ou préfixe (ex: 'owner' match avec 'owner_sub', 'owner_full')
        return ($userRole === $role || str_starts_with($userRole, $role . '_'));
    }
    /**
     * Tente de simuler une requête SELECT simple sur les données volatiles
     * Utile pour les wrappers Database des plugins
     */
    public function simulateSelect(string $table, array $filters = []): ?array
    {
        if (!$this->isActive()) return null;
        
        $rows = $this->getVolatileRows($table);
        if (empty($rows)) return null;

        $results = [];
        foreach ($rows as $row) {
            $match = true;
            foreach ($filters as $k => $condition) {
                if (is_array($condition) && isset($condition['column'], $condition['operator'], $condition['value'])) {
                    $col = $condition['column'];
                    $op = strtoupper((string) $condition['operator']);
                    $val = $condition['value'];
                    $actual = $row[$col] ?? null;

                    switch ($op) {
                        case '=':
                            if ($actual != $val) $match = false;
                            break;
                        case '!=':
                            if ($actual == $val) $match = false;
                            break;
                        case '>':
                            if (!($actual > $val)) $match = false;
                            break;
                        case '<':
                            if (!($actual < $val)) $match = false;
                            break;
                        case '>=':
                            if (!($actual >= $val)) $match = false;
                            break;
                        case '<=':
                            if (!($actual <= $val)) $match = false;
                            break;
                        case 'IN':
                            // Handle scalar value for IN operator if mapped incorrectly
                            $valArr = is_array($val) ? $val : [$val];
                            if (!in_array($actual, $valArr)) $match = false;
                            break;
                        case 'LIKE':
                            $pattern = str_replace('%', '.*', preg_quote($val, '/'));
                            if (!preg_match("/^$pattern$/i", (string) $actual)) $match = false;
                            break;
                        default:
                            $match = false;
                    }
                } else {
                    // Fallback (simple associatif)
                    if (!is_numeric($k) && !is_array($condition)) {
                        if (($row[$k] ?? null) != $condition) {
                            $match = false;
                        }
                    }
                }
                
                if (!$match) break;
            }
            if ($match) {
                $results[] = $row;
            }
        }
        
        return $results;
    }

    /**
     * Tente de simuler un COUNT(*) simple
     */
    public function simulateCount(string $table, array $filters = []): ?int
    {
        $results = $this->simulateSelect($table, $filters);
        return ($results === null) ? null : count($results);
    }

    /**
     * Handler pour le hook entity:ownership-verify
     */
    public function handleOwnershipVerify($params)
    {
        if ($params === true) return true;
        if (!$this->isActive()) return $params;

        // Si on simule un 'owner', on valide la propriété pour n'importe quelle entité (cas global)
        if ($this->isTestUserWithRole('owner')) {
            return true;
        }

        return $params;
    }
}
