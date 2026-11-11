<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin Subscription
 * Gestion des abonnements via Mollie pour les propriétaires de POI
 */

// Chargement des classes avant exécution
function subscriptionBeforeRunPlugin(): void
{
    foreach (glob(PLUGINS . 'subscription/controllers/' . '*.php') as $file) {
        require_once $file;
    }
    foreach (glob(PLUGINS . 'subscription/entities/' . '*.php') as $file) {
        require_once $file;
    }
    foreach (glob(PLUGINS . 'subscription/services/' . '*.php') as $file) {
        require_once $file;
    }
    
    // Hooks are now declared in param/hooks.json
    if (class_exists('\Core\Core')) {
        $core = \Core\Core::getInstance();
        $core->addHook('user:collect-context-data', 'subscriptionCollectUserContextData');
    }
}

// Hook pour enregistrer les items de navigation via le nouveau système
function subscriptionNavigationBuild($tree): void
{
    try {
        $currentUser = \Core\Auth\CurrentUser::get();
        
        // Vérifier si l'utilisateur est propriétaire (via CurrentUser qui a centralisé l'info)
        if (!$currentUser->isOwner()) {
            return;
        }
        
        $navigationService = \Core\Navigation\NavigationService::getInstance();
        $router = \Core\Router\Router::getInstance();
        
        try {
            $subscriptionUrl = $router->generate('subscription-home');
        } catch (\Exception $e) {
            $subscriptionUrl = '/subscription';
        }
        
        $navigationService->register('plugin.subscription.main', [
            'label' => 'Abonnement',
            'target' => $subscriptionUrl,
            'targetAttr' => '_self',
            'cssClass' => 'nav-link',
            'position' => 60,
            'source' => 'plugin:subscription',
            'visibility' => ['public' => true, 'logged' => true],
            'enabled' => true
        ]);
    } catch (\Exception $e) {
        \Core\Core::getInstance()->getLogger()->error('subscriptionNavigationBuild error: ' . $e->getMessage());
    }
}

// Hook pour ajouter le CSS public du plugin
function subscriptionEndFrontHead($param = ''): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('subscription');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return (string)$param;
    }

    $baseUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');
    $output = (string)$param;
    $cssFile = PLUGINS . 'subscription/template/public.css';
    if (file_exists($cssFile)) {
        $output .= '<link rel="stylesheet" href="' . $baseUrl . '/plugin/subscription/template/public.css" media="all">' . "\n";
    }
    return $output;
}

// Vérifie si un utilisateur est propriétaire d'au moins une fiche POI
function subscriptionIsOwner($data): bool
{
    if ($data === true) return true;

    $userId = 0;
    $email = null;
    $username = null;

    if (is_array($data)) {
        $userId = (int)($data['user_id'] ?? 0);
        $email = $data['email'] ?? null;
        $username = $data['username'] ?? null;
    } elseif (is_object($data) && isset($data->attributes['id'])) {
        $userId = (int)$data->attributes['id'];
        $email = $data->attributes['email'] ?? null;
        $username = $data->attributes['username'] ?? null;
    }

    if (!$userId && !$email) {
        return false;
    }
    
    // Utiliser le hook entity:ownership-verify (centré dans le plugin claim)
    // C'est sûr maintenant que cette fonction n'est plus enregistrée comme handler de ce hook.
    return \Core\Core::getInstance()->callHook('entity:ownership-verify', [
        'entity_type' => 'poi',
        'entity_id' => 0,
        'user_id' => $userId,
        'email' => $email,
        'username' => $username
    ]) === true;
}

/**
 * Hook generic pour verifier l'abonnement d'un utilisateur
 */
function subscriptionVerifySubscription(array $data): bool
{
    $userId = (int)($data['user_id'] ?? 0);
    if (!$userId) return false;
    
    $service = new \Subscription\Services\SubscriptionService();
    $sub = $service->getUserSubscription($userId);
    
    return ($sub !== null && $sub->status === 'active');
}

/**
 * Hook API pour récupérer les détails de l'abonnement d'un utilisateur
 * Renvoie un tableau avec le statut, la date de début, etc.
 */
function subscriptionGetSubscriptionDetails(array $data): ?array
{
    $userId = (int)($data['user_id'] ?? 0);
    if (!$userId) return null;
    
    $service = new \Subscription\Services\SubscriptionService();
    $sub = $service->getUserSubscription($userId);
    
    if (!$sub) {
        return ['status' => 'inactive'];
    }
    
    $totalAddonsPrice = 0;
    $addons = $service->getUserActiveAddons($userId);
    foreach ($addons as $addon) {
        $totalAddonsPrice += (float)$addon['price'];
    }

    return [
        'status' => $sub->status,
        'plan_type' => $sub->plan_type,
        'monthly_price' => (float)$sub->monthly_price,
        'annual_price' => (float)$sub->annual_price,
        'addons_total' => $totalAddonsPrice,
        'total_revenue' => ($sub->plan_type === 'annual' ? (float)$sub->annual_price : (float)$sub->monthly_price) + $totalAddonsPrice,
        'started_at' => $sub->started_at,
        'expires_at' => $sub->expires_at,
    ];
}

// Installation : prépare le dossier data et les fichiers de configuration
function subscriptionInstall(): void
{
    $dataDir = DATA_PLUGIN . 'subscription' . DS;
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    
    // Créer prices.json avec les tarifs par défaut
    $pricesFile = $dataDir . 'prices.json';
    if (!file_exists($pricesFile)) {
        @file_put_contents($pricesFile, json_encode([
            'monthly_price' => 9.99,
            'annual_price' => 99.99,
            'annual_discount' => 0,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    // Créer mollie.json avec la config Mollie par défaut
    $mollieFile = $dataDir . 'mollie.json';
    if (!file_exists($mollieFile)) {
        @file_put_contents($mollieFile, json_encode([
            'mollie_api_key' => '',
            'mollie_sandbox' => true,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}


/**
 * Hook: user:avatar-menu
 */
/**
 * Hook: user:avatar-menu
 */
/**
 * Hook: user:avatar-menu
 */
function subscriptionUserAvatarMenu(array $data): array
{
    $currentUser = \Core\Auth\CurrentUser::get();
    
    // On n'affiche le menu abonnement qu'aux propriétaires
    if (!$currentUser->isOwner()) {
        return $data;
    }
    
    $router = \Core\Router\Router::getInstance();
    
    // 1. Bouton "Mon abonnement" (si Premium)
    if ($currentUser->isPremium()) {
        try {
            $url = $router->generate('subscription-manage');
        } catch (\Exception $e) {
            $url = '/subscription/manage';
        }
        
        $data['items'][] = [
            'label' => 'Mon abonnement',
            'url' => $url,
            'icon' => 'fa-solid fa-credit-card',
            'order' => 90
        ];
    } else {
        // 2. CTA "✨ S'abonner" (si Owner non-Premium)
        try {
            $url = $router->generate('subscription-home');
        } catch (\Exception $e) {
            $url = '/subscription';
        }
        
        $data['items'][] = [
            'label' => '✨ S\'abonner',
            'url' => $url,
            'icon' => 'fa-solid fa-crown',
            'class' => 'avatar-cta-premium',
            'order' => 80
        ];
    }
    
    return $data;
}

/**
 * Collecte les données d'abonnement et d'addons pour le singleton CurrentUser.
 */
/**
 * Collecte les données d'abonnement et d'addons pour le singleton CurrentUser.
 */
function subscriptionCollectUserContextData(array $data): array
{
    $currentUser = \Core\Auth\CurrentUser::get();
    // Utiliser l'id du singleton qui gère déjà la simulation via UsersManager
    $userId = $currentUser->id();
    
    if (!$userId) return $data;

    try {
        $service = new \Subscription\Services\SubscriptionService();
        $sub = $service->getUserSubscription($userId);
        
        if ($sub && $sub->status === 'active') {
            // Stocker l'objet d'abonnement pour que CurrentUser calcule is_subscriber
            $data['subscription'] = $sub;
            
            // Récupérer les addons actifs
            $addons = $service->getUserActiveAddons($userId);
            $addonFlags = $data['addons'] ?? [];
            foreach ($addons as $addon) {
                $slug = $addon['slug'] ?? null;
                if ($slug) {
                    $addonFlags[$slug] = true;
                }
            }
            $data['addons'] = $addonFlags;

            // Enrichir les capacités
            // Ex: si on a un abonnement, on a peut-être des capacités spécifiques
            // $data['capabilities']['advanced_stats'] = true;
        }
    } catch (\Exception $e) {
        // Silencieux
    }
    return $data;
}
