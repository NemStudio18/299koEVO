<?php

namespace Offers {

use Core\Plugin\PluginsManager;
use Core\Core;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
        $dbDir = DATA_PLUGIN . 'offers';
        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0755, true);
        }
    }

    public static function endFrontHead(): void
    {
        $plugin = PluginsManager::getInstance()->getPlugin('offers');
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return;
        }
        echo '<link rel="stylesheet" href="' . \Utils\Util::urlBuild(PLUGINS . 'offers/assets/css/offers.css') . '" type="text/css"/>';
    }

    // endMainNavigation supprimé — géré par Page::addToNavigation() en legacy


    /**
     * Hook pour injecter les liens dans le menu avatar
     */
    public static function userAvatarMenu(array $data): array
    {
        $plugin = PluginsManager::getInstance()->getPlugin('offers');
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return $data;
        }

        $user = \Core\Auth\UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('offers:manage'), $ctx)) {
            return $data;
        }

        $core = Core::getInstance();
        $router = \Core\Router\Router::getInstance();

        // 1. Lien "Gérer mes offres" (pour les propriétaires)
        // Note: is_owner est déjà géré par la politique offers:manage si on voulait être pur, 
        // mais ici on veut savoir s'il est propriétaire d'AU MOINS UN POI pour afficher le lien.
        // La politique offers:manage avec is_owner sans ID de ressource pourrait être ambiguë.
        // On garde la vérification explicite pour le menu car c'est une vue agrégée.
        $isOwner = $core->callHook('entity:ownership-verify', [
            'entity_type' => 'poi',
            'entity_id' => 0,
            'user_id' => (int)$user->attributes['id'],
            'email' => $user->attributes['email'] ?? null,
            'username' => $user->attributes['username'] ?? null
        ]);

        if ($isOwner === true) {
            try {
                $offersUrl = $router->generate('offers-my-offers');
            } catch (\Exception $e) {
                $offersUrl = '/offers/my-offers';
            }
            $data['items'][] = [
                'label' => 'Offres',
                'url' => $offersUrl,
                'icon' => 'fa-solid fa-briefcase',
                'order' => 75
            ];
        }

        return $data;
    }
}
}

namespace {

    /**
     * Hook: notifications:collect
     */
    function offersNotificationsCollect(array $data): array
    {
        return $data;
    }

    function offersInstall(): void
    {
        \Offers\Hooks::install();
    }

    function offersBeforeRunPlugin(): void
    {
        // L'autoloader multi-root gère maintenant le chargement des classes
    }

    // offersEndMainNavigation supprimé — géré via legacy pages.json

    function offersEndFrontHead(): void
    {
        \Offers\Hooks::endFrontHead();
    }


    function offersClaimMyClaimsItemActions($claim = null): string
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('offers');
        if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
            return '';
        }
        if (!is_array($claim) || !isset($claim['claim_status']) || $claim['claim_status'] !== 'approved') {
            return '';
        }
        
        $router = \Core\Router\Router::getInstance();
        try {
            $offersAddUrl = $router->generate('offers-add');
        } catch (\Exception $e) {
            $offersAddUrl = '/offers/add';
        }
        
        $poiId = (int)($claim['poi_id'] ?? 0);
        if ($poiId <= 0) return '';
        
        return '<a class="button offers-create-btn" href="' . htmlspecialchars($offersAddUrl) . '?poi_id=' . $poiId . '">' .
               '<i class="fa-solid fa-tag"></i> Créer une offre</a>';
    }

    // Hooks are now registered in hooks.json

}