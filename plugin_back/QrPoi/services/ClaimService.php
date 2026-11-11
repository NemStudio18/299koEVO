<?php

namespace QrPoi\Services;

use PDO;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour vérifier si un POI est réclamé par un utilisateur
 */
class ClaimService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    private function getConnection(): ?PDO
    {
        try {
            return \Core\Database::getInstance()->getPdo();
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_WARNING, "QrPoi ClaimService: Erreur de connexion au core PDO: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si un utilisateur est propriétaire d'un POI
     */
    public function isOwner(int $userId, int $poiId): bool
    {
        try {
            $pdo = $this->getConnection();
            if (!$pdo) {
                return false;
            }

            // Vérifier dans la table poi_public (colonne claimed_by)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM poi_public WHERE id = ? AND claimed_by = ?");
            $stmt->execute([$poiId, $userId]);
            $count = (int)$stmt->fetchColumn();
            
            if ($count > 0) {
                return true;
            }

            // Vérifier dans poi_claims (claim approuvé)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM poi_claims WHERE poi_id = ? AND user_id = ? AND status = 'approved'");
            $stmt->execute([$poiId, $userId]);
            $count = (int)$stmt->fetchColumn();
            
            return $count > 0;
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_WARNING, "QrPoi ClaimService::isOwner - Erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les POI réclamés par un utilisateur
     */
    public function getUserPois(int $userId): array
    {
        try {
            $pdo = $this->getConnection();
            if (!$pdo) {
                return [];
            }

            // Récupérer depuis poi_public (claimed_by)
            $stmt = $pdo->prepare("SELECT id, name, addr_street, addr_city FROM poi_public WHERE claimed_by = ? ORDER BY name");
            $stmt->execute([$userId]);
            $pois = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupérer depuis poi_claims (claims approuvés)
            $stmt = $pdo->prepare("SELECT p.id, p.name, p.addr_street, p.addr_city 
                                   FROM poi_public p 
                                   INNER JOIN poi_claims c ON p.id = c.poi_id 
                                   WHERE c.user_id = ? AND c.status = 'approved' 
                                   ORDER BY p.name");
            $stmt->execute([$userId]);
            $claimedPois = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fusionner et dédupliquer
            $allPois = [];
            $seenIds = [];
            
            foreach ($pois as $poi) {
                if (!in_array($poi['id'], $seenIds)) {
                    $allPois[] = $poi;
                    $seenIds[] = $poi['id'];
                }
            }
            
            foreach ($claimedPois as $poi) {
                if (!in_array($poi['id'], $seenIds)) {
                    $allPois[] = $poi;
                    $seenIds[] = $poi['id'];
                }
            }
            
            return $allPois;
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_WARNING, "QrPoi ClaimService::getUserPois - Erreur: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les informations d'un POI
     */
    public function getPoiInfo(int $poiId): ?array
    {
        try {
            $pdo = $this->getConnection();
            if (!$pdo) {
                return null;
            }

            $stmt = $pdo->prepare("SELECT id, name, addr_street, addr_city FROM poi_public WHERE id = ? LIMIT 1");
            $stmt->execute([$poiId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_WARNING, "QrPoi ClaimService::getPoiInfo - Erreur: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère l'ID MySQL d'un utilisateur à partir de son email ou username
     */
    public function getMySQLUserId(string $email, string $username): ?int
    {
        // Nettoyer les valeurs (trim)
        $email = trim($email);
        $username = trim($username);
        
        // Si les deux sont vides, retourner null
        if ($email === '' && $username === '') {
            return null;
        }
        
        try {
            $pdo = $this->getConnection();
            if (!$pdo) {
                return null;
            }
            
            // Essayer d'abord par email (plus fiable)
            if ($email !== '') {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result && !empty($result['id'])) {
                    return (int)$result['id'];
                }
            }
            
            // Si pas trouvé par email, essayer par username
            if ($username !== '') {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result && !empty($result['id'])) {
                    return (int)$result['id'];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_WARNING, "QrPoi ClaimService::getMySQLUserId - Erreur: " . $e->getMessage());
            return null;
        }
    }
}



