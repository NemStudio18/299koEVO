<?php
namespace LocalFeed\Services\Providers;

use PDO;
use RuntimeException;
use Utils\Util;

class BlogProvider implements ContentProviderInterface {

    public function getContentType(): string {
        return 'blog';
    }

    public function supportsReaction(string $reactionType): bool {
        return $reactionType === 'like';
    }

    /**
     * Vérifie l'existence et l'état publiable de l'article de blog.
     * Note architecturale : les articles de blog étant sérialisés dans blog.json,
     * cette validation n'est pas verrouillée par la transaction SQL, mais exécutée
     * de façon synchrone avant l'enregistrement de la réaction.
     */
    public function assertAccessible(PDO $pdo, int $contentId, ?int $userId, string $reactionType): void {
        // 1. Tenter d'utiliser le gestionnaire natif du plugin Blog s'il est chargé
        if (class_exists('\\newsManager')) {
            try {
                $manager = new \newsManager();
                $article = $manager->create($contentId);
                if ($article && method_exists($article, 'getDraft')) {
                    if ($article->getDraft() === "1" || $article->getDraft() === true) {
                        throw new RuntimeException("Cet article de blog est actuellement en brouillon.");
                    }
                    return;
                }
            } catch (RuntimeException $re) {
                throw $re;
            } catch (\Throwable $t) {
                // Si newsManager échoue (contexte CLI ou environnement partiel), fallback sur la lecture JSON
            }
        }

        // 2. Fallback robuste : lecture du fichier JSON canonique du blog
        $blogFile = (defined('DATA') ? DATA : (defined('ROOT') ? ROOT . 'data/' : __DIR__ . '/../../../../../../data/')) . 'plugin/blog/blog.json';
        if (!file_exists($blogFile)) {
            // Chemins alternatifs selon l'environnement
            $altPath = dirname(__DIR__, 5) . '/data/plugin/blog/blog.json';
            if (file_exists($altPath)) {
                $blogFile = $altPath;
            }
        }

        if (!file_exists($blogFile)) {
            throw new RuntimeException("Source d'articles du blog introuvable.");
        }

        $raw = file_get_contents($blogFile);
        $articles = json_decode($raw, true);

        if (!is_array($articles)) {
            throw new RuntimeException("Données du blog corrompues ou illisibles.");
        }

        $found = false;
        foreach ($articles as $a) {
            if ((int)($a['id'] ?? 0) === $contentId) {
                if (!empty($a['draft']) && ($a['draft'] === "1" || $a['draft'] === true)) {
                    throw new RuntimeException("Cet article de blog est un brouillon et n'est pas accessible au public.");
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new RuntimeException("Article de blog introuvable (ID: {$contentId}).");
        }
    }

    public function getReactionCount(PDO $pdo, int $contentId, string $reactionType = 'like'): int {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM content_reactions 
            WHERE content_type = 'blog' 
              AND content_id = :cid 
              AND reaction_type = :rtype
        ");
        $stmt->execute([':cid' => $contentId, ':rtype' => $reactionType]);
        return (int)$stmt->fetchColumn();
    }
}
