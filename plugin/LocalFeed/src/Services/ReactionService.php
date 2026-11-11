<?php
namespace LocalFeed\Services;

use Core\Database;
use PDO;
use InvalidArgumentException;

class ReactionService {

    private static ?self $instance = null;

    private function __construct() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Bascule ou met à jour une réaction (like, interest) de façon atomique.
     * 
     * Garanties :
     * 1. content_reactions est la source de vérité unique (aucun compteur désynchronisé).
     * 2. L'accessibilité du contenu est validée sous transaction active (LOCK IN SHARE MODE).
     * 3. La gestion des collisions concurrentes préserve toujours l'action utilisateur
     *    sans jamais supprimer arbitrairement une réaction en cas de double-clic.
     * 
     * @param int $userId Identifiant de l'utilisateur (BIGINT UNSIGNED)
     * @param string $contentType Type de contenu ('post', 'event', 'offer', 'blog')
     * @param int $contentId Identifiant du contenu
     * @param string $reactionType Type de réaction ('like', 'interest')
     * @return array [success => bool, reacted => bool, reaction_type => string, reaction_count => int]
     */
    public function toggle(int $userId, string $contentType, int $contentId, string $reactionType = 'like'): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException("Un identifiant utilisateur valide est requis pour réagir.");
        }

        $provider = ContentProviderRegistry::get($contentType);
        if (!$provider) {
            throw new InvalidArgumentException("Type de contenu non supporté : {$contentType}");
        }

        if (!$provider->supportsReaction($reactionType)) {
            throw new InvalidArgumentException("La réaction '{$reactionType}' n'est pas autorisée pour le contenu {$contentType}.");
        }

        $pdo = Database::getInstance()->getPdo();
        $pdo->beginTransaction();

        try {
            // A. Vérification de l'existence et des droits dans le contexte transactionnel
            $provider->assertAccessible($pdo, $contentId, $userId, $reactionType);

            // B. Lecture de l'état actuel sous verrou exclusif FOR UPDATE
            $stmt = $pdo->prepare("
                SELECT id, reaction_type 
                FROM content_reactions 
                WHERE user_id = :uid 
                  AND content_type = :type 
                  AND content_id = :cid 
                FOR UPDATE
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':type' => $contentType,
                ':cid' => $contentId
            ]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ($existing['reaction_type'] === $reactionType) {
                    // Clic identique : Toggle OFF (suppression intentionnelle)
                    $del = $pdo->prepare("DELETE FROM content_reactions WHERE id = :id");
                    $del->execute([':id' => $existing['id']]);
                    $reacted = false;
                } else {
                    // Clic différent : mise à jour du type de réaction (ex: like -> interest)
                    $upd = $pdo->prepare("
                        UPDATE content_reactions 
                        SET reaction_type = :rtype, created_at = NOW() 
                        WHERE id = :id
                    ");
                    $upd->execute([':rtype' => $reactionType, ':id' => $existing['id']]);
                    $reacted = true;
                }
            } else {
                // Toggle ON (nouvel ajout)
                try {
                    $ins = $pdo->prepare("
                        INSERT INTO content_reactions (user_id, content_type, content_id, reaction_type, created_at)
                        VALUES (:uid, :type, :cid, :rtype, NOW())
                    ");
                    $ins->execute([
                        ':uid' => $userId,
                        ':type' => $contentType,
                        ':cid' => $contentId,
                        ':rtype' => $reactionType
                    ]);
                    $reacted = true;
                } catch (\PDOException $e) {
                    // C. Gestion d'une collision concurrente (race condition lors d'un double-clic simultané)
                    // Une autre requête concurrente vient d'insérer la ligne :
                    // On ne supprime JAMAIS la réaction concurrente, on confirme sa présence !
                    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate') !== false) {
                        $recheck = $pdo->prepare("
                            SELECT id, reaction_type 
                            FROM content_reactions 
                            WHERE user_id = :uid AND content_type = :type AND content_id = :cid
                        ");
                        $recheck->execute([
                            ':uid' => $userId,
                            ':type' => $contentType,
                            ':cid' => $contentId
                        ]);
                        $row = $recheck->fetch(PDO::FETCH_ASSOC);

                        if ($row && $row['reaction_type'] !== $reactionType) {
                            $upd = $pdo->prepare("UPDATE content_reactions SET reaction_type = :rtype WHERE id = :id");
                            $upd->execute([':rtype' => $reactionType, ':id' => $row['id']]);
                        }
                        $reacted = true;
                    } else {
                        throw $e;
                    }
                }
            }

            // D. Compteur calculé directement depuis content_reactions (source de vérité unique)
            $reactionCount = $provider->getReactionCount($pdo, $contentId, $reactionType);

            // Récupération de l'ensemble des compteurs de ce contenu (pour rafraîchir les boutons multiples)
            $stmtCounts = $pdo->prepare("
                SELECT reaction_type, COUNT(*) as cnt
                FROM content_reactions
                WHERE content_type = :type AND content_id = :id
                GROUP BY reaction_type
            ");
            $stmtCounts->execute([':type' => $contentType, ':id' => $contentId]);
            $counts = [];
            while ($row = $stmtCounts->fetch(PDO::FETCH_ASSOC)) {
                $counts[$row['reaction_type']] = (int)$row['cnt'];
            }

            $pdo->commit();

            return [
                'success' => true,
                'reacted' => $reacted,
                'reaction_type' => $reactionType,
                'reaction_count' => $reactionCount,
                'counts' => $counts
            ];

        } catch (\Throwable $t) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $t;
        }
    }

    /**
     * Charge les réactions de l'utilisateur connecté STRICTEMENT limitées aux contenus
     * présents dans la page affichée ($items). Évite de charger l'historique complet.
     * 
     * @param int $userId Identifiant utilisateur
     * @param array $items Liste des cartes affichées (chacune contenant 'type' et 'id')
     * @return array Dictionnaire indexé [ "type_id" => "reaction_type" ]
     */
    public function getUserReactionsMap(int $userId, array $items): array {
        if ($userId <= 0 || empty($items)) {
            return [];
        }

        // Regrouper les identifiants par type de contenu présent dans le flux
        $grouped = [];
        foreach ($items as $item) {
            $type = $item['type'] ?? null;
            $id = isset($item['id']) ? (int)$item['id'] : 0;
            if ($type && $id > 0) {
                $grouped[$type][] = $id;
            }
        }

        if (empty($grouped)) {
            return [];
        }

        $pdo = Database::getInstance()->getPdo();
        $clauses = [];
        $params = [':uid' => $userId];
        $paramIndex = 0;

        foreach ($grouped as $type => $ids) {
            $uniqueIds = array_values(array_unique($ids));
            $placeholders = [];
            foreach ($uniqueIds as $id) {
                $ph = ":id_{$paramIndex}";
                $placeholders[] = $ph;
                $params[$ph] = $id;
                $paramIndex++;
            }
            $typePh = ":type_{$paramIndex}";
            $params[$typePh] = $type;
            $paramIndex++;

            $clauses[] = "(content_type = {$typePh} AND content_id IN (" . implode(',', $placeholders) . "))";
        }

        $sql = "
            SELECT content_type, content_id, reaction_type 
            FROM content_reactions 
            WHERE user_id = :uid AND (" . implode(" OR ", $clauses) . ")
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            foreach ($rows as $r) {
                $key = $r['content_type'] . '_' . $r['content_id'];
                $map[$key] = $r['reaction_type'];
            }
            return $map;
        } catch (\Throwable $t) {
            return [];
        }
    }

    /**
     * Récupère les compteurs de réactions pour une liste de contenus affichés.
     * Exécuté en une seule requête SQL globale pour optimiser les performances (zéro N+1).
     * 
     * @param array $items Liste des cartes affichées
     * @return array Dictionnaire indexé [ "type_id" => [ "like" => int, "interest" => int ] ]
     */
    public function getReactionCountsMap(array $items): array {
        if (empty($items)) {
            return [];
        }

        $grouped = [];
        foreach ($items as $item) {
            $type = $item['type'] ?? null;
            $id = isset($item['id']) ? (int)$item['id'] : 0;
            if ($type && $id > 0) {
                $grouped[$type][] = $id;
            }
        }

        if (empty($grouped)) {
            return [];
        }

        $pdo = Database::getInstance()->getPdo();
        $clauses = [];
        $params = [];
        $paramIndex = 0;

        foreach ($grouped as $type => $ids) {
            $uniqueIds = array_values(array_unique($ids));
            $placeholders = [];
            foreach ($uniqueIds as $id) {
                $ph = ":cid_{$paramIndex}";
                $placeholders[] = $ph;
                $params[$ph] = $id;
                $paramIndex++;
            }
            $typePh = ":ctype_{$paramIndex}";
            $params[$typePh] = $type;
            $paramIndex++;

            $clauses[] = "(content_type = {$typePh} AND content_id IN (" . implode(',', $placeholders) . "))";
        }

        $sql = "
            SELECT content_type, content_id, reaction_type, COUNT(*) as cnt 
            FROM content_reactions 
            WHERE (" . implode(" OR ", $clauses) . ")
            GROUP BY content_type, content_id, reaction_type
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            foreach ($rows as $r) {
                $key = $r['content_type'] . '_' . $r['content_id'];
                if (!isset($map[$key])) {
                    $map[$key] = [];
                }
                $map[$key][$r['reaction_type']] = (int)$r['cnt'];
            }
            return $map;
        } catch (\Throwable $t) {
            return [];
        }
    }
}

