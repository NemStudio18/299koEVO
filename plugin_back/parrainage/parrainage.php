<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin Parrainage
 * Système de codes d'affiliations et de parrainages.
 */

// 1. Autoloader pour le namespace Parrainage (mappe vers src/)
spl_autoload_register(function ($class) {
    if (strpos($class, 'Parrainage\\') === 0) {
        $path = str_replace('\\', '/', substr($class, 11));
        $file = __DIR__ . '/src/' . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

/**
 * Installation : création des tables
 */
function parrainageInstall(): void
{
    $pdo = \Core\Database::getInstance()->getPdo();
    $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $statements = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT,
                username TEXT,
                role TEXT DEFAULT 'member'
            )",
            "CREATE TABLE IF NOT EXISTS parrainage_codes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                email TEXT NULL,
                code TEXT NOT NULL UNIQUE,
                commission_percent NUMERIC DEFAULT 0.00,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS parrainage_referrals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code_id INTEGER NOT NULL,
                referred_user_id INTEGER NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (code_id) REFERENCES parrainage_codes(id) ON DELETE CASCADE,
                FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS parrainage_commissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                referral_id INTEGER NOT NULL,
                amount NUMERIC NOT NULL,
                status TEXT DEFAULT 'pending',
                notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (referral_id) REFERENCES parrainage_referrals(id) ON DELETE CASCADE
            )"
        ];
        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
        }
    } else {
        $sql = "
            CREATE TABLE IF NOT EXISTS parrainage_codes (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT NULL COMMENT 'Optionnel, si lié à un compte existant',
                email VARCHAR(255) NULL COMMENT 'Optionnel, si lié par email seulement',
                code VARCHAR(50) NOT NULL UNIQUE,
                commission_percent DECIMAL(5,2) DEFAULT 0.00,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY (user_id),
                KEY (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS parrainage_referrals (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                code_id BIGINT NOT NULL,
                referred_user_id BIGINT NOT NULL UNIQUE COMMENT 'Le filleul',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (code_id) REFERENCES parrainage_codes(id) ON DELETE CASCADE,
                FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS parrainage_commissions (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                referral_id BIGINT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'processed', 'cancelled') DEFAULT 'pending',
                notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (referral_id) REFERENCES parrainage_referrals(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $pdo->exec($sql);
    }
}

// Les hooks sont maintenant gérés par la classe Parrainage\Hooks\ParrainageHooks
// via le fichier param/hooks.json
