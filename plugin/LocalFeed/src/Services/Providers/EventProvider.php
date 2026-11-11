<?php
namespace LocalFeed\Services\Providers;

use PDO;
use RuntimeException;

class EventProvider implements ContentProviderInterface {

    public function getContentType(): string {
        return 'event';
    }

    public function supportsReaction(string $reactionType): bool {
        return in_array($reactionType, ['interest', 'like'], true);
    }

    public function assertAccessible(PDO $pdo, int $contentId, ?int $userId, string $reactionType): void {
        // Verrouillage partagé de l'événement dans la transaction
        $stmt = $pdo->prepare("
            SELECT id, status 
            FROM agenda_events 
            WHERE id = :id 
            LOCK IN SHARE MODE
        ");
        $stmt->execute([':id' => $contentId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            throw new RuntimeException("Cet événement n'existe pas ou a été annulé.");
        }

        // Dans agenda_events, les événements publiés ont le statut 'approved'
        if ($event['status'] !== 'approved') {
            throw new RuntimeException("Cet événement n'est pas validé pour diffusion publique.");
        }
    }

    public function getReactionCount(PDO $pdo, int $contentId, string $reactionType = 'like'): int {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM content_reactions 
            WHERE content_type = 'event' 
              AND content_id = :cid 
              AND reaction_type = :rtype
        ");
        $stmt->execute([':cid' => $contentId, ':rtype' => $reactionType]);
        return (int)$stmt->fetchColumn();
    }
}
