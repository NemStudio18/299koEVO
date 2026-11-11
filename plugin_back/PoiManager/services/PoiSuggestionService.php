<?php

namespace PoiManager\Services;

use PoiManager\Entities\PoiSuggestionEntity;

defined('ROOT') or exit('Access denied!');

/**
 * PoiSuggestionService
 *
 * Gère les suggestions de corrections de POIs existants soumises par les membres.
 * Chaque suggestion = un champ modifié (field_name / old_value / new_value).
 * Workflow : pending → approved (applique le champ) ou rejected.
 */
class PoiSuggestionService
{
    private \PDO           $pdo;
    private PoiManagerService $poiService;

    /** Champs qu'un membre peut suggérer de modifier */
    private const ALLOWED_FIELDS = [
        'name', 'category', 'subcategory', 'desc_list',
        'addr_housenumber', 'addr_street', 'addr_postcode', 'addr_city',
        'contact_phone', 'contact_email', 'website',
        'opening_hours', 'wheelchair', 'internet_access', 'parking',
        'fee', 'indoor', 'vending', 'brand',
    ];

    public function __construct()
    {
        $this->pdo        = \Core\Database::getInstance()->getPdo();
        $this->poiService = new PoiManagerService();
    }

    // =========================================================
    // SOUMISSION (membre)
    // =========================================================

    /**
     * Soumet une ou plusieurs suggestions de correction pour un POI.
     * $fields = ['field_name' => 'new_value', ...]
     * Retourne le nombre de suggestions créées.
     */
    public function submit(int $poiId, array $fields, int $userId): int
    {
        $poi = $this->poiService->getById($poiId);
        if (!$poi) return 0;

        $count = 0;
        foreach ($fields as $fieldName => $newValue) {
            if (!in_array($fieldName, self::ALLOWED_FIELDS, true)) continue;
            $newValue = trim((string)$newValue);
            $oldValue = (string)($poi[$fieldName] ?? '');

            // Ne pas créer une suggestion si la valeur est identique
            if ($newValue === $oldValue) continue;

            $suggestion = new PoiSuggestionEntity([
                'poi_id'      => $poiId,
                'user_id'     => $userId,
                'field_name'  => $fieldName,
                'old_value'   => $oldValue,
                'new_value'   => $newValue,
                'status'      => 'pending',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
            $suggestion->save();
            $count++;
        }

        if ($count > 0) {
            $this->notifyAdminNewSuggestion($poiId, $userId, $count);

            // Hook pour gamification
            \Core\Core::getInstance()->callHook('poi:suggestion-submitted', [
                'user_id' => $userId,
                'poi_id' => $poiId,
                'count' => $count
            ]);
        }

        return $count;
    }

    // =========================================================
    // LECTURE (admin)
    // =========================================================

    /**
     * Retourne toutes les suggestions en attente.
     */
    public function getPending(): array
    {
        $sql = "
            SELECT s.*, p.name AS poi_name, u.username AS user_username, u.email AS user_email
            FROM poi_suggestions s
            LEFT JOIN poi_public p ON s.poi_id = p.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.status = 'pending'
            ORDER BY s.poi_id ASC, s.created_at ASC
        ";
        $stmt = $this->pdo->query($sql);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $entities = [];
        foreach ($results as $row) {
            $entity = new PoiSuggestionEntity($row);
            // On s'assure que les colonnes supplémentaires sont bien dans l'array d'attributs pour Twig
            $entity->attributes['poi_name'] = $row['poi_name'];
            $entity->attributes['user_username'] = $row['user_username'];
            $entity->attributes['user_email'] = $row['user_email'];
            $entities[] = $entity;
        }
        return $entities;
    }

    /**
     * Retourne tout l'historique des suggestions (tous statuts).
     */
    public function getHistory(int $limit = 100): array
    {
        $sql = "
            SELECT s.*, p.name AS poi_name, u.username AS user_username, u.email AS user_email
            FROM poi_suggestions s
            LEFT JOIN poi_public p ON s.poi_id = p.id
            LEFT JOIN users u ON s.user_id = u.id
            ORDER BY s.created_at DESC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        $entities = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $entity = new PoiSuggestionEntity($row);
            $entity->attributes['poi_name'] = $row['poi_name'];
            $entity->attributes['user_username'] = $row['user_username'];
            $entity->attributes['user_email'] = $row['user_email'];
            $entities[] = $entity;
        }
        return $entities;
    }

    /**
     * Retourne les suggestions en attente groupées par POI.
     */
    public function getPendingGroupedByPoi(): array
    {
        $suggestions = $this->getPending();
        $grouped = [];
        foreach ($suggestions as $s) {
            $grouped[$s->poi_id][] = $s;
        }
        return $grouped;
    }

    /**
     * Retourne les suggestions d'un POI spécifique.
     */
    public function getByPoi(int $poiId): array
    {
        return PoiSuggestionEntity::query()
            ->where('poi_id', '=', $poiId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Retourne une suggestion par son ID.
     */
    public function getById(int $id): ?PoiSuggestionEntity
    {
        return PoiSuggestionEntity::findPK($id);
    }

    // =========================================================
    // DÉCISION (admin)
    // =========================================================

    /**
     * Approuve une suggestion : applique le champ sur poi_public.
     */
    public function approve(int $suggestionId, int $adminId): bool
    {
        $suggestion = $this->getById($suggestionId);
        if (!$suggestion || $suggestion->status !== 'pending') return false;

        if (!in_array($suggestion->field_name, self::ALLOWED_FIELDS, true)) return false;

        // Appliquer le changement sur poi_public
        $success = $this->poiService->update(
            (int)$suggestion->poi_id,
            [$suggestion->field_name => $suggestion->new_value]
        );

        if ($success) {
            $suggestion->status      = 'approved';
            $suggestion->reviewed_by = $adminId;
            $suggestion->reviewed_at = date('Y-m-d H:i:s');
            $suggestion->save();

            // Hook pour gamification
            \Core\Core::getInstance()->callHook('poi-edit:contribution-accepted', [
                'user_id' => $suggestion->user_id,
                'poi_id' => $suggestion->poi_id,
                'type' => 'suggestion',
                'field' => $suggestion->field_name
            ]);

            $this->notifyMemberDecision($suggestion->attributes, 'approved');
        }

        return $success;
    }

    /**
     * Rejette une suggestion.
     */
    public function reject(int $suggestionId, int $adminId): bool
    {
        $suggestion = $this->getById($suggestionId);
        if (!$suggestion || $suggestion->status !== 'pending') return false;

        $suggestion->status      = 'rejected';
        $suggestion->reviewed_by = $adminId;
        $suggestion->reviewed_at = date('Y-m-d H:i:s');
        $suggestion->save();

        $this->notifyMemberDecision($suggestion->attributes, 'rejected');
        return true;
    }

    /**
     * Approuve toutes les suggestions en attente d'un POI d'un coup.
     */
    public function approveAllForPoi(int $poiId, int $adminId): int
    {
        $pending = PoiSuggestionEntity::query()
            ->where('poi_id', '=', $poiId)
            ->where('status', '=', 'pending')
            ->get();

        $count = 0;
        foreach ($pending as $s) {
            if ($this->approve((int)$s->id, $adminId)) $count++;
        }
        return $count;
    }

    // =========================================================
    // NOTIFICATIONS
    // =========================================================

    private function notifyAdminNewSuggestion(int $poiId, int $userId, int $count): void
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiManager');
        if (!$plugin || !($plugin->getConfigVal('notifications')['admin_email_on_suggestion'] ?? false)) return;

        $adminEmail = \Core\Core::getInstance()->getConfigVal('adminEmail');
        if (empty($adminEmail)) return;

        \Utils\Util::sendEmail(
            $adminEmail, $adminEmail, $adminEmail,
            "[PoiManager] $count nouvelle(s) suggestion(s) pour le POI #$poiId",
            "Un membre a soumis $count correction(s) pour le POI #$poiId.\nConnectez-vous à l'admin pour les valider."
        );
    }

    private function notifyMemberDecision(array $suggestion, string $decision): void
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiManager');
        if (!$plugin || !($plugin->getConfigVal('notifications')['member_email_on_decision'] ?? false)) return;

        $userId = (int)($suggestion['user_id'] ?? 0);
        if (!$userId) return;

        $pdo  = \Core\Database::getInstance()->getPdo();
        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $email = $stmt->fetchColumn();
        if (!$email) return;

        $adminEmail = \Core\Core::getInstance()->getConfigVal('adminEmail');
        $field      = $suggestion['field_name'] ?? '?';
        $poiId      = $suggestion['poi_id'] ?? '?';

        if ($decision === 'approved') {
            $subject = "[PoiManager] Votre correction a été acceptée (POI #$poiId)";
            $body    = "Votre suggestion de correction pour le champ \"$field\" du POI #$poiId a été acceptée. Merci pour votre contribution !";
        } else {
            $subject = "[PoiManager] Votre correction a été refusée (POI #$poiId)";
            $body    = "Votre suggestion de correction pour le champ \"$field\" du POI #$poiId a été refusée.";
        }

        \Utils\Util::sendEmail($adminEmail, $adminEmail, $email, $subject, $body);
    }
}
