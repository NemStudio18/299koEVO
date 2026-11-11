<?php

namespace Claim\Services;

use Claim\Entities\Claim;
use PoiData\Entities\Poi;
use Core\Auth\User;
use Core\Core;

defined('ROOT') or exit('Access denied!');

class ClaimService
{
    public function __construct()
    {
        // Plus besoin de PDO, les entités gèrent leur propre stockage
    }

    /**
     * Trouve un POI par ID
     */
    public function findPoi(int $poiId): ?object
    {
        if (class_exists('\\PoiData\\Entities\\Poi')) {
            return \PoiData\Entities\Poi::findPK($poiId);
        }
        return null;
    }

    /**
     * Récupère l'ID d'un utilisateur à partir de son email ou username
     */
    public function getMySQLUserId(string $email, string $username): ?int
    {
        // On utilise l'entité User du Core
        $user = User::query()->where('email', '=', $email)->first()
             ?? User::query()->where('username', '=', $username)->first();
             
        return $user ? (int)($user->attributes['id'] ?? 0) : null;
    }

    /**
     * S'assure qu'un utilisateur existe dans la base MySQL (synchronisation)
     * Pour la compatibilité avec PoiEditPublicController
     */
    public function ensureUserExists(int $cmsUserId, string $email, string $username): int
    {
        $user = User::query()->where('id', '=', $cmsUserId)->first();
        
        if (!$user) {
            // Créer un utilisateur temporaire pour la synchro si non trouvé (cas rare car déjà logué)
            $user = new User([
                'id' => $cmsUserId,
                'email' => $email,
                'username' => $username,
                'role' => 'member',
                'status' => 'active'
            ]);
            $user->save();
        } else {
            // S'assurer que les données sont synchros
            $user->save();
        }
        
        return $cmsUserId;
    }

    /**
     * Crée un claim (revendication)
     */
    public function createClaim(int $poiId, int $cmsUserId, string $note, ?string $proofPath, ?string $userEmail = null, ?string $username = null): int
    {
        Claim::beginTransaction();
        try {
            $poi = class_exists(Poi::class) ? Poi::findPK($poiId) : null;
            if (!$poi) {
                throw new \Exception("POI #{$poiId} not found");
            }

            // Vérification : déjà revendiqué ou en cours ?
            $existing = Claim::query()
                ->where('poi_id', '=', $poiId)
                ->where('status', '!=', 'rejected')
                ->first();

            if ($existing) {
                throw new \Exception("Une revendication est déjà en cours ou acceptée pour ce POI.");
            }

            $claim = new Claim([
                'poi_id' => $poiId,
                'user_id' => $cmsUserId,
                'status' => 'pending',
                'note' => $note,
                'proof_path' => $proofPath,
                'requested_at' => date('Y-m-d H:i:s')
            ]);

            if ($claim->save()) {
                Claim::commit();
                return (int)$claim->id;
            }

            throw new \Exception("Erreur lors de la sauvegarde du claim.");
        } catch (\Throwable $e) {
            Claim::rollBack();
            throw $e;
        }
    }

    /**
     * Récupère tous les claims
     */
    public function getAllClaims(): array
    {
        return Claim::all();
    }

    /**
     * Récupère un claim par ID
     */
    public function getClaimById($claimId): ?Claim
    {
        return Claim::findPK($claimId);
    }

    /**
     * Récupère un claim pour un POI et un utilisateur spécifiques
     */
    public function getClaimByPoiAndUser(int $poiId, int $userId): ?Claim
    {
        return Claim::query()
            ->where('poi_id', '=', $poiId)
            ->where('user_id', '=', $userId)
            ->first();
    }

    /**
     * Met à jour le statut d'un claim et met à jour le POI si approuvé
     */
    public function updateClaimStatus($claimId, string $status, ?int $approvedBy = null): bool
    {
        Claim::beginTransaction();
        try {
            $claim = Claim::findPK($claimId);
            if (!$claim) {
                Claim::rollBack();
                return false;
            }

            $claim->status = $status;
            $claim->approved_at = ($status === 'approved') ? date('Y-m-d H:i:s') : null;
            
            if (!$claim->save()) {
                Claim::rollBack();
                return false;
            }

            // Sync avec le POI
            if ($status === 'approved') {
                $poi = class_exists(Poi::class) ? Poi::findPK($claim->poi_id) : null;
                if ($poi) {
                    $poi->claimed_by = $claim->user_id;
                    $poi->claimed_at = date('Y-m-d H:i:s');
                    $poi->status_claim = 'claimed';
                    $poi->save();
                }
            } elseif ($status === 'rejected') {
                // Reset si c'était le dernier owner
                $poi = class_exists(Poi::class) ? Poi::findPK($claim->poi_id) : null;
                if ($poi && (int)$poi->claimed_by === (int)$claim->user_id) {
                    $poi->claimed_by = null;
                    $poi->claimed_at = null;
                    $poi->status_claim = 'unclaimed';
                    $poi->save();
                }
            }

            Claim::commit();
            return true;
        } catch (\Exception $e) {
            Claim::rollBack();
            throw $e;
        }
    }

    /**
     * Vérifie si un utilisateur est propriétaire d'un POI
     */
    public function isOwner(int $userId, int $poiId): bool
    {
        $poi = class_exists(Poi::class) ? Poi::findPK($poiId) : null;
        return $poi && (int)$poi->claimed_by === $userId;
    }

    /**
     * Récupère le statut de claim d'un POI
     */
    public function getClaimStatus(int $poiId): ?string
    {
        $claim = Claim::query()
            ->where('poi_id', '=', $poiId)
            ->where('status', '!=', 'rejected')
            ->orderBy('requested_at', 'DESC')
            ->first();
            
        return $claim ? $claim->status : null;
    }

    /**
     * Récupère tous les claims d'un utilisateur
     */
    public function getUserClaims(int $cmsUserId, ?string $userEmail = null, ?string $username = null, bool $excludeRejected = true): array
    {
        $query = Claim::query()->where('user_id', '=', $cmsUserId);
        if ($excludeRejected) {
            $query->where('status', '!=', 'rejected');
        }
        
        $claims = $query->orderBy('requested_at', 'DESC')->get();
        
        // On complète avec les infos POI pour la compatibilité avec l'ancien retour
        $results = [];
        foreach ($claims as $claim) {
            $poi = class_exists(Poi::class) ? Poi::findPK($claim->poi_id) : null;
            $results[] = array_merge($claim->attributes, [
                'claim_id' => $claim->id,
                'claim_status' => $claim->status,
                'name' => $poi ? $poi->name : 'POI #' . $claim->poi_id,
                'city' => $poi ? $poi->addr_city : '',
                'street' => $poi ? $poi->addr_street : '',
                // ... autres champs si nécessaires
            ]);
        }
        return $results;
    }

    public function hasUserClaims(int $userId): bool
    {
        return Claim::query()
            ->where('user_id', '=', $userId)
            ->where('status', '!=', 'rejected')
            ->count() > 0;
    }

    public function getStats(): array
    {
        return [
            'total_claims'    => Claim::query()->count(),
            'pending_claims'  => Claim::query()->where('status', '=', 'pending')->count(),
            'approved_claims' => Claim::query()->where('status', '=', 'approved')->count(),
            'rejected_claims' => Claim::query()->where('status', '=', 'rejected')->count(),
            'claimed_pois'    => Claim::query()->where('status', '=', 'approved')->count(),
            'total_pois'      => class_exists(Poi::class) ? Poi::query()->count() : 0,
        ];
    }
}
