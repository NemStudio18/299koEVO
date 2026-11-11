<?php

namespace Subscription\Entities;

use PDO;

defined('ROOT') or exit('Access denied!');

class PromoCodeManager
{
    private PDO $pdo;
    private string $tablePrefix;

    public function __construct(PDO $pdo, string $tablePrefix = '')
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
        $this->initTable();
    }

    private function initTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tablePrefix}subscription_promo_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            discount_percentage INT NOT NULL DEFAULT 0,
            duration VARCHAR(20) NOT NULL DEFAULT 'infinite',
            max_uses INT DEFAULT NULL,
            current_uses INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        try {
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            \Core\Core::getInstance()->getLogger()->error('PromoCodeManager::initTable - ' . $e->getMessage());
        }
    }

    /**
     * Get a promo code by its code string
     */
    public function getByCode(string $code): ?array
    {
        $sql = "SELECT * FROM {$this->tablePrefix}subscription_promo_codes WHERE code = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Get all promo codes
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM {$this->tablePrefix}subscription_promo_codes ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new promo code
     */
    public function create(string $code, int $discountPercentage, string $duration, ?int $maxUses): bool
    {
        try {
            $sql = "INSERT INTO {$this->tablePrefix}subscription_promo_codes (code, discount_percentage, duration, max_uses, is_active) VALUES (?, ?, ?, ?, 1)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([strtoupper(trim($code)), $discountPercentage, $duration, $maxUses]);
        } catch (\PDOException $e) {
            \Core\Core::getInstance()->getLogger()->error('PromoCodeManager::create - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle the active status of a promo code
     */
    public function toggleActive(int $id, bool $isActive): bool
    {
        try {
            $sql = "UPDATE {$this->tablePrefix}subscription_promo_codes SET is_active = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$isActive ? 1 : 0, $id]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Delete a promo code
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->tablePrefix}subscription_promo_codes WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Validate a promo code for checkout
     * Returns an array with validation status and the promo code object if valid
     */
    public function validateCode(string $code): array
    {
        $promoCode = $this->getByCode(strtoupper(trim($code)));

        if (!$promoCode) {
            return ['valid' => false, 'message' => "Le code promotionnel saisi n'existe pas."];
        }

        if (!$promoCode['is_active']) {
            return ['valid' => false, 'message' => "Ce code promotionnel est inactif ou a été révoqué."];
        }

        if ($promoCode['max_uses'] !== null && $promoCode['current_uses'] >= $promoCode['max_uses']) {
            return ['valid' => false, 'message' => "Ce code promotionnel a atteint sa limite d'utilisation maximale."];
        }

        return ['valid' => true, 'promo' => $promoCode, 'message' => "Code appliqué avec succès."];
    }

    /**
     * Increment the usage count of a promo code
     */
    public function incrementUsage(int $id): bool
    {
        try {
            $sql = "UPDATE {$this->tablePrefix}subscription_promo_codes SET current_uses = current_uses + 1 WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            return false;
        }
    }
}
