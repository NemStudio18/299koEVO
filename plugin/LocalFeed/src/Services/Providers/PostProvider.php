<?php
namespace LocalFeed\Services\Providers;

use PDO;
use RuntimeException;

class PostProvider implements ContentProviderInterface {

    public function getContentType(): string {
        return 'post';
    }

    public function supportsReaction(string $reactionType): bool {
        return $reactionType === 'like';
    }

    public function assertAccessible(PDO $pdo, int $contentId, ?int $userId, string $reactionType): void {
        // Verrouillage partagé pour garantir que le post ne change pas de statut pendant la transaction
        $stmt = $pdo->prepare("
            SELECT id, status 
            FROM poi_posts 
            WHERE id = :id 
            LOCK IN SHARE MODE
        ");
        $stmt->execute([':id' => $contentId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            throw new RuntimeException("Cette publication n'existe pas ou a été supprimée.");
        }

        if ($post['status'] !== 'published') {
            throw new RuntimeException("Cette publication n'est pas accessible au public.");
        }
    }

    public function getReactionCount(PDO $pdo, int $contentId, string $reactionType = 'like'): int {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM content_reactions 
            WHERE content_type = 'post' 
              AND content_id = :cid 
              AND reaction_type = :rtype
        ");
        $stmt->execute([':cid' => $contentId, ':rtype' => $reactionType]);
        return (int)$stmt->fetchColumn();
    }
}
