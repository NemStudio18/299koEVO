<?php

namespace Parrainage\Services;

use Core\Database;
use Core\Core;
use Parrainage\Entities\ParrainageCode;
use Parrainage\Entities\ParrainageReferral;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class ParrainageService
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getPdo();
        if (function_exists('parrainageInstall')) {
            parrainageInstall();
        }
    }

    /**
     * Génère un nouveau code d'affiliation.
     */
    public function generateCode(string $code, float $commissionPercent, ?int $userId = null, ?string $email = null): bool
    {
        $sql = "INSERT INTO parrainage_codes (code, commission_percent, user_id, email) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([$code, $commissionPercent, $userId, $email]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère tous les codes (pour l'admin).
     */
    public function getAllCodes(): array
    {
        $sql = "
            SELECT c.*, u.username as owner_username, u.email as owner_account_email,
                   (SELECT COUNT(*) FROM parrainage_referrals r WHERE r.code_id = c.id) as usage_count
            FROM parrainage_codes c
            LEFT JOIN users u ON c.user_id = u.id
            ORDER BY c.created_at DESC
        ";
        $results = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        
        // On pourrait mapper en objets ParrainageCode ici, 
        // mais pour les dashboards on a souvent besoin des jointures (owner_username).
        return $results;
    }
    
    /**
     * Récupère tous les codes appartenant à un utilisateur spécifique (pour le tableau de bord parrain).
     */
    public function getCodesByUser(int $userId, string $email): array
    {
        $sql = "
            SELECT c.*,
                   (SELECT COUNT(*) FROM parrainage_referrals r WHERE r.code_id = c.id) as usage_count
            FROM parrainage_codes c
            WHERE c.user_id = ? OR c.email = ?
            ORDER BY c.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $email]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Enregistre une commission figée lors d'un paiement.
     */
    public function registerCommission(int $referralId, float $amount, string $notes = ''): bool
    {
        $sql = "INSERT INTO parrainage_commissions (referral_id, amount, status, notes) VALUES (?, ?, 'pending', ?)";
        return $this->pdo->prepare($sql)->execute([$referralId, $amount, $notes]);
    }

    /**
     * Récupère l'historique des commissions pour un code.
     */
    public function getCommissionsByCode(int $codeId): array
    {
        $sql = "
            SELECT c.*, r.referred_user_id, u.username as referred_username
            FROM parrainage_commissions c
            JOIN parrainage_referrals r ON c.referral_id = r.id
            JOIN users u ON r.referred_user_id = u.id
            WHERE r.code_id = ?
            ORDER BY c.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$codeId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les détails d'un code et la liste de ses filleuls.
     */
    public function getCodeDetails(int $codeId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM parrainage_codes WHERE id = ?");
        $stmt->execute([$codeId]);
        $code = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$code) return null;

        $sqlRef = "
            SELECT r.*, u.username as referred_username, u.email as referred_email
            FROM parrainage_referrals r
            JOIN users u ON r.referred_user_id = u.id
            WHERE r.code_id = ?
            ORDER BY r.created_at DESC
        ";
        $stmtRef = $this->pdo->prepare($sqlRef);
        $stmtRef->execute([$codeId]);
        $referrals = $stmtRef->fetchAll(\PDO::FETCH_ASSOC);

        // Enrichir avec l'API Subscription (Découplé)
        $core = Core::getInstance();
        $totalEstimatedMonthly = 0;
        
        foreach ($referrals as &$ref) {
            // Appel au Hook du plugin Subscription (API interne)
            $subDetails = $core->callHook('subscription:get-details', ['user_id' => $ref['referred_user_id']]);
            
            $ref['has_subscription'] = false;
            
            if (is_array($subDetails) && ($subDetails['status'] ?? '') === 'active') {
                $ref['has_subscription'] = true;
                $ref['plan_type'] = $subDetails['plan_type'] ?? 'inconnu';
                $ref['started_at'] = $subDetails['started_at'] ?? null;
                
                // Nouveau : calcul basé sur le TOTAL REVENUE (Abonnement + Addons)
                $price = (float)($subDetails['total_revenue'] ?? 0);
                
                // Si annuel, on affiche quand même l'estimation mensuelle pour le dashboard
                if ($ref['plan_type'] === 'annual') {
                    $price = $price / 12;
                }
                
                $gain = $price * ($code['commission_percent'] / 100);
                $ref['estimated_monthly_gain'] = round($gain, 2);
                $totalEstimatedMonthly += $gain;
            } else {
                $ref['estimated_monthly_gain'] = 0;
            }
        }

        $code['referrals'] = $referrals;
        $code['total_estimated_monthly'] = round($totalEstimatedMonthly, 2);
        
        // Ajouter l'historique des gains réellement perçus
        $code['commissions_history'] = $this->getCommissionsByCode($codeId);
        $code['total_earned'] = array_sum(array_column($code['commissions_history'], 'amount'));
        
        return $code;
    }

    /**
     * Vérifie si un utilisateur possède l'addon "Parrainage" actif.
     */
    public function isAddonActive(int $userId): bool
    {
        // 1. Récupérer l'ID de l'addon "Parrainage"
        $stmt = $this->pdo->prepare("SELECT id FROM subscription_addons WHERE name = ? AND active = 1");
        $stmt->execute(['Parrainage']);
        $addonId = $stmt->fetchColumn();
        
        if (!$addonId) return false;

        // 2. Vérifier si l'utilisateur y est abonné via subscription_user_addons
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM subscription_user_addons 
            WHERE user_id = ? AND addon_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId, $addonId]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Récupère le code d'un utilisateur ou le génère s'il a l'addon actif.
     */
    public function getOrCreateUserCode(int $userId, string $email): ?string
    {
        // 1. Vérifier si un code existe déjà (lié à l'ID ou à l'Email non lié)
        $stmt = $this->pdo->prepare("SELECT id, code, user_id FROM parrainage_codes WHERE user_id = ? OR (email = ? AND user_id IS NULL) LIMIT 1");
        $stmt->execute([$userId, $email]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existing) {
            // Si trouvé par email mais non lié, on lie maintenant par sécurité
            if ($existing['user_id'] === null) {
                $update = $this->pdo->prepare("UPDATE parrainage_codes SET user_id = ? WHERE id = ?");
                $update->execute([$userId, $existing['id']]);
            }
            return $existing['code'];
        }

        // 2. Si pas de code, vérifier si l'addon est actif
        if ($this->isAddonActive($userId)) {
            // Générer un code unique (ex: USERNAME-XXXX)
            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $username = $stmt->fetchColumn() ?: 'USER';
            $code = strtoupper($username) . '-' . strtoupper(bin2hex(random_bytes(2)));
            
            // On s'assure de l'unicité du code (simplifié ici, on pourrait boucler si collision)
            $this->generateCode($code, 10.0, $userId, $email);
            return $code;
        }

        return null;
    }
}
