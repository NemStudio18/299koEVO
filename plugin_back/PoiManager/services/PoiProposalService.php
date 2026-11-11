<?php

namespace PoiManager\Services;

use PoiManager\Entities\PoiProposalEntity;

defined('ROOT') or exit('Access denied!');

/**
 * PoiProposalService
 *
 * Gère les propositions de nouveaux POIs soumises par les membres.
 * Workflow : pending → approved (crée le POI) ou rejected.
 */
class PoiProposalService
{
    private \PDO           $pdo;
    private PoiManagerService $poiService;

    public function __construct()
    {
        $this->pdo        = \Core\Database::getInstance()->getPdo();
        $this->poiService = new PoiManagerService();
    }

    // =========================================================
    // SOUMISSION (membre)
    // =========================================================

    /**
     * Crée une nouvelle proposition de POI soumise par un membre.
     * Retourne l'ID de la proposition ou null en cas d'erreur.
     */
    public function submit(array $data, int $userId): ?int
    {
        $proposal = new PoiProposalEntity([
            'name'          => trim($data['name'] ?? ''),
            'category'      => $data['category'] ?? null,
            'street'        => $data['street'] ?? null,
            'housenumber'   => $data['housenumber'] ?? null,
            'postcode'      => $data['postcode'] ?? null,
            'city'          => $data['city'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'website'       => $data['website'] ?? null,
            'opening_hours' => $data['opening_hours'] ?? null,
            'wheelchair'    => $data['wheelchair'] ?? null,
            'lat'           => !empty($data['lat']) ? (float)$data['lat'] : null,
            'lon'           => !empty($data['lon']) ? (float)$data['lon'] : null,
            'is_owner'      => !empty($data['is_owner']) ? 1 : 0,
            'owner_proof'   => $data['owner_proof'] ?? null,
            'note'          => $data['note'] ?? null,
            'user_id'       => $userId,
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        if (!$proposal->save()) {
            return null;
        }

        // Notification email admin
        $this->notifyAdminNewProposal($proposal->attributes);

        // Hook pour gamification
        \Core\Core::getInstance()->callHook('poi:proposal-submitted', [
            'user_id' => $userId,
            'proposal_id' => $proposal->id
        ]);

        return (int)$proposal->id;
    }

    // =========================================================
    // LECTURE (admin)
    // =========================================================

    /**
     * Retourne toutes les propositions en attente.
     */
    public function getPending(): array
    {
        return PoiProposalEntity::query()
            ->where('status', '=', 'pending')
            ->orderBy('created_at', 'ASC')
            ->get();
    }

    /**
     * Retourne une proposition par son ID.
     */
    public function getById(int $id): ?PoiProposalEntity
    {
        return PoiProposalEntity::findPK($id);
    }

    /**
     * Retourne toutes les propositions (toutes statuts), avec pagination.
     */
    public function getAll(int $limit = 30, int $offset = 0): array
    {
        return PoiProposalEntity::query()
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get();
    }

    /**
     * Retourne tout l'historique des propositions (toutes statuts).
     */
    public function getHistory(int $limit = 100): array
    {
        $sql = "
            SELECT p.*, u.email as user_email, u.username as user_username
            FROM poi_proposals p
            LEFT JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        $entities = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $entity = new PoiProposalEntity($row);
            $entity->attributes['user_username'] = $row['user_username'];
            $entity->attributes['user_email'] = $row['user_email'];
            $entities[] = $entity;
        }
        return $entities;
    }
    // =========================================================
    // DÉCISION (admin)
    // =========================================================

    /**
     * Approuve une proposition : crée un POI dans poi_public, marque la proposition comme approuvée.
     * Retourne l'ID du POI créé ou null en cas d'erreur.
     */
    public function approve(int $proposalId, int $adminId): ?int
    {
        $proposal = $this->getById($proposalId);
        if (!$proposal || $proposal->status !== 'pending') {
            return null;
        }

        // Construire les données du POI à partir de la proposition
        $poiData = [
            'name'          => $proposal->name,
            'category'      => $proposal->category,
            'addr_street'   => $proposal->street,
            'addr_housenumber' => $proposal->housenumber,
            'addr_postcode' => $proposal->postcode,
            'addr_city'     => $proposal->city,
            'contact_phone' => $proposal->phone,
            'website'       => $proposal->website,
            'opening_hours' => $proposal->opening_hours,
            'wheelchair'    => $proposal->wheelchair,
            'lat'           => $proposal->lat ?? 0,
            'lon'           => $proposal->lon ?? 0,
            'source'        => 'user',
            'status'        => 'public',
        ];

        $poiId = $this->poiService->create($poiData, $adminId);
        if (!$poiId) return null;

        // Marquer la proposition comme approuvée
        $proposal->status      = 'approved';
        $proposal->reviewed_by = $adminId;
        $proposal->reviewed_at = date('Y-m-d H:i:s');
        $proposal->save();

        // Hook pour gamification
        \Core\Core::getInstance()->callHook('poi:proposal-approved', [
            'user_id' => $proposal->user_id,
            'poi_id' => $poiId,
            'proposal_id' => $proposalId
        ]);

        // Notification email au membre
        $this->notifyMemberDecision($proposal->attributes, 'approved', $poiId);

        return $poiId;
    }

    /**
     * Rejette une proposition.
     */
    public function reject(int $proposalId, int $adminId, string $reason = ''): bool
    {
        $proposal = $this->getById($proposalId);
        if (!$proposal || $proposal->status !== 'pending') {
            return false;
        }

        $proposal->status      = 'rejected';
        $proposal->reviewed_by = $adminId;
        $proposal->reviewed_at = date('Y-m-d H:i:s');
        $proposal->note        = ($proposal->note ? $proposal->note . "\n" : '') . 'Rejeté : ' . $reason;
        $proposal->save();

        // Notification email au membre
        $this->notifyMemberDecision($proposal->attributes, 'rejected', null, $reason);

        return true;
    }

    // =========================================================
    // NOTIFICATIONS
    // =========================================================

    private function notifyAdminNewProposal(array $proposal): void
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiManager');
        if (!$plugin || !$plugin->getConfigVal('notifications')['admin_email_on_proposal']) {
            return;
        }

        $adminEmail = \Core\Core::getInstance()->getConfigVal('adminEmail');
        if (empty($adminEmail)) return;

        $name = $proposal['name'] ?? 'Sans nom';
        \Utils\Util::sendEmail(
            $adminEmail, $adminEmail, $adminEmail,
            '[PoiManager] Nouvelle proposition de POI : ' . $name,
            "Un membre a proposé un nouveau POI.\n\nNom : $name\nVille : " . ($proposal['city'] ?? '') .
            "\n\nConnectez-vous à l'admin pour valider ou rejeter cette proposition."
        );
    }

    private function notifyMemberDecision(array $proposal, string $decision, ?int $poiId, string $reason = ''): void
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiManager');
        if (!$plugin || !$plugin->getConfigVal('notifications')['member_email_on_decision']) {
            return;
        }

        // Récupérer l'email du membre
        $userId = (int)($proposal['user_id'] ?? 0);
        if (!$userId) return;

        $pdo  = \Core\Database::getInstance()->getPdo();
        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $email = $stmt->fetchColumn();
        if (!$email) return;

        $adminEmail = \Core\Core::getInstance()->getConfigVal('adminEmail');
        $name       = $proposal['name'] ?? 'POI';

        if ($decision === 'approved') {
            $subject = '[PoiManager] Votre proposition a été acceptée : ' . $name;
            $body    = "Bonne nouvelle ! Votre proposition de POI \"$name\" a été acceptée et est maintenant visible sur le site.";
        } else {
            $subject = '[PoiManager] Votre proposition a été refusée : ' . $name;
            $body    = "Votre proposition de POI \"$name\" a été refusée." . ($reason ? "\nRaison : $reason" : '');
        }

        \Utils\Util::sendEmail($adminEmail, $adminEmail, $email, $subject, $body);
    }
}
