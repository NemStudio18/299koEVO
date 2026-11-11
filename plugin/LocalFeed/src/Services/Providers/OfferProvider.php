<?php
namespace LocalFeed\Services\Providers;

use PDO;
use RuntimeException;

class OfferProvider implements ContentProviderInterface {

    public function getContentType(): string {
        return 'offer';
    }

    public function supportsReaction(string $reactionType): bool {
        return in_array($reactionType, ['interest', 'like'], true);
    }

    public function assertAccessible(PDO $pdo, int $contentId, ?int $userId, string $reactionType): void {
        // Verrouillage partagé de l'offre
        $stmt = $pdo->prepare("
            SELECT id, status, valid_until 
            FROM offers 
            WHERE id = :id 
            LOCK IN SHARE MODE
        ");
        $stmt->execute([':id' => $contentId]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$offer) {
            throw new RuntimeException("Cette offre promotionnelle n'existe pas ou a été retirée.");
        }

        if ($offer['status'] !== 'ACTIVE') {
            throw new RuntimeException("Cette offre n'est plus active.");
        }

        // Vérification de la date limite de validité
        if (!empty($offer['valid_until'])) {
            $validUntilTimestamp = strtotime($offer['valid_until']);
            if ($validUntilTimestamp !== false && $validUntilTimestamp < time()) {
                throw new RuntimeException("Cette offre promotionnelle a expiré.");
            }
        }
    }

    public function getReactionCount(PDO $pdo, int $contentId, string $reactionType = 'like'): int {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM content_reactions 
            WHERE content_type = 'offer' 
              AND content_id = :cid 
              AND reaction_type = :rtype
        ");
        $stmt->execute([':cid' => $contentId, ':rtype' => $reactionType]);
        return (int)$stmt->fetchColumn();
    }
}
