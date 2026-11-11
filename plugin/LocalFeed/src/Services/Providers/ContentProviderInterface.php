<?php
namespace LocalFeed\Services\Providers;

use PDO;

/**
 * Interface des fournisseurs de contenu pour le registre universel de réactions.
 * Chaque fournisseur encapsule la logique d'accessibilité et les types de réactions autorisés.
 */
interface ContentProviderInterface {
    /**
     * Type de contenu unique géré par ce fournisseur ('post', 'event', 'offer', 'blog').
     */
    public function getContentType(): string;

    /**
     * Indique si un type de réaction donné ('like', 'interest') est supporté par ce contenu.
     */
    public function supportsReaction(string $reactionType): bool;

    /**
     * Vérifie l'existence et l'accessibilité publique du contenu dans le contexte transactionnel.
     * Doit lever une exception si le contenu est introuvable, dépublié ou inaccessible.
     *
     * @param PDO $pdo Connexion active sous transaction
     * @param int $contentId Identifiant du contenu
     * @param int|null $userId Identifiant de l'utilisateur demandeur
     * @param string $reactionType Type de réaction demandée
     * @throws \RuntimeException Si le contenu n'est pas accessible
     */
    public function assertAccessible(PDO $pdo, int $contentId, ?int $userId, string $reactionType): void;

    /**
     * Calcule le nombre exact de réactions pour ce contenu et ce type de réaction
     * directement depuis la source de vérité content_reactions.
     */
    public function getReactionCount(PDO $pdo, int $contentId, string $reactionType = 'like'): int;
}
