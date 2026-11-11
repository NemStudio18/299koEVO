<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin Claim / Ownership skeleton.
 */

// Chargement des classes avant exécution
function claimBeforeRunPlugin(): void
{
    foreach (glob(PLUGINS . 'claim/controllers/' . '*.php') as $file) {
        require_once $file;
    }
    foreach (glob(PLUGINS . 'claim/services/' . '*.php') as $file) {
        require_once $file;
    }
    
    // Enregistrer les hooks après le chargement du Core
    // Note: navigation:build est enregistré via hooks.json, pas besoin de le réenregistrer ici
    if (class_exists('\Core\Core')) {
        $core = \Core\Core::getInstance();
        $core->addHook('user:collect-context-data', 'claimCollectUserContextData');
    }
}

/**
 * Collecte les données de revendication pour le singleton CurrentUser.
 * Injecté via le hook 'user:collect-context-data'
 */
function claimCollectUserContextData(array $data): array
{
    // Mode Test : si l'utilisateur impersonné est un test user "owner", on injecte des claims fictifs
    $testMode = \Core\Auth\AdminTestMode::getInstance();
    if ($testMode->isActive() && $testMode->isTestUserWithRole('owner')) {
        $data['claims'] = [999]; // ID fictif pour activer les flags isOwner/hasClaims
        $data['flags']['is_owner'] = true;
        return $data;
    }

    $currentUser = \Core\Auth\CurrentUser::get();
    $userId = $currentUser->id();
    
    if (!$userId) return $data;

    try {
        $service = claimGetService();
        $claims = $service->getUserClaims($userId, null, null, true);
        
        $poiIds = [];
        foreach ($claims as $c) {
            if ($c['claim_status'] === 'approved') {
                $poiIds[] = (int)$c['poi_id'];
            }
        }
        
        $data['claims'] = array_unique($poiIds);
    } catch (\Exception $e) {
        // Silencieux
    }
    return $data;
}

/**
 * Hook generic pour verifier la propriete d'une entite (POI)
 */
function claimVerifyOwnership($data): bool
{
    // Si la propriété a déjà été validée par un hook précédent dans la chaîne
    if ($data === true) {
        return true;
    }

    // Mode Test : si l'utilisateur impersonné est un test user "owner", bypass MySQL
    $testMode = \Core\Auth\AdminTestMode::getInstance();
    if ($testMode->isActive() && $testMode->isTestUserWithRole('owner')) {
        return true;
    }

    if (!is_array($data)) {
        return false;
    }

    $entityType = $data['entity_type'] ?? '';
    $entityId = (int)($data['entity_id'] ?? 0);
    $email = $data['email'] ?? null;
    $username = $data['username'] ?? null;
    
    if ($entityType !== 'poi') {
        return false;
    }
    
    // If entity_id is 0, we check if user owns ANY entity of this type
    $checkAny = ($entityId <= 0);

    try {
        $service = new \Claim\Services\ClaimService();
        
        // Resolve MySQL User ID
        $mysqlUserId = $service->getMySQLUserId($email ?? '', $username ?? '');
        
        if (!$mysqlUserId) {
            if (!empty($data['mysql_user_id'])) {
                $mysqlUserId = (int)$data['mysql_user_id'];
            } else {
                return false;
            }
        }
        
        if ($checkAny) {
            return $service->hasUserClaims($mysqlUserId);
        } else {
            return $service->isOwner($mysqlUserId, $entityId);
        }

    } catch (\Exception $e) {
        return false;
    }
}

// Installation : prépare le dossier data et un config basique
function claimInstall(): void
{
    // Les dossiers et fichiers JSON sont gérés par les entités via JsonActiveRecord.
}

// Hook pour enregistrer les items de navigation via le nouveau système
function claimNavigationBuild($tree): void
{
    try {
        $currentUser = \Core\Auth\CurrentUser::get();
        
        if (!$currentUser->isLogged() || !$currentUser->hasClaims()) {
            return;
        }
        
        $navigationService = \Core\Navigation\NavigationService::getInstance();
        $baseUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');
        
        // Item principal "Mes fiches"
        $navigationService->register('plugin.claim.main', [
            'label' => 'Mes fiches',
            'target' => $baseUrl . '/claim/my-claims',
            'targetAttr' => '_self',
            'cssClass' => 'claim-menu-dropdown',
            'position' => 55,
            'source' => 'plugin:claim',
            'visibility' => ['public' => true, 'logged' => true],
            'navContext' => 'auto',
            'enabled' => true
        ]);
        
        // Note: Le dropdown avec les fiches individuelles nécessitera une adaptation
        // du renderer ou un hook spécifique pour l'affichage dynamique
    } catch (\Exception $e) {
        $core = \Core\Core::getInstance();
        $core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "claimNavigationBuild - Erreur: " . $e->getMessage());
    }
}

/**
 * Hook: poi:get-claim-status
 */
function claimGetClaimStatus(array $data): array
{
    $poiId = (int)($data['poi_id'] ?? 0);
    $user = $data['user'] ?? null;
    
    if ($poiId <= 0) {
        return $data;
    }

    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('claim');
    if (!$plugin || !$plugin->getConfigVal('activate')) {
        return $data;
    }

    $service = new \Claim\Services\ClaimService();
    $status = $service->getClaimStatus($poiId);
    if ($status) {
        $data['status'] = $status;
        $data['claimed'] = true;
        $data['claimed_validated'] = ($status === 'approved');
    }

    if ($user !== null) {
        $userId = (int)($user->id ?? 0);
        if ($userId > 0) {
            $claim = $service->getClaimByPoiAndUser($poiId, $userId);
            if ($claim) {
                $data['user_has_pending'] = ($claim->status === 'pending');
                $data['user_is_owner'] = ($claim->status === 'approved');
            }
        }
    }

    return $data;
}

// claimEndMainNavigation supprimé car déprécié (remplacé par NavigationService)

// Hook pour ajouter le CSS du menu dans le head (hook de filtre)
function claimEndFrontHead($param = ''): string
{
    $baseUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');
    $output = (string)$param;
    $cssFile = PLUGINS . 'claim/template/claim.css';
    if (file_exists($cssFile)) {
        $output .= '<link rel="stylesheet" href="' . $baseUrl . '/plugin/claim/template/claim.css" media="all">' . "\n";
    }
    return $output;
}

/**
 * Hook pour injecter le bouton "C'est Chez moi!" dans les pages de recherche/détail POI
 * @param array|null $poi Données du POI (passées depuis le template)
 */
function claimSearchEVOPoiActions($poi = null): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('claim');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return '';
    }
    
    // Si $poi n'est pas fourni ou n'est pas un tableau, ne rien faire
    if (!is_array($poi)) {
        return '';
    }
    
    // Vérifier si le POI est déjà réclamé
    if (!empty($poi['claimed_validated']) || (isset($poi['status']) && $poi['status'] === 'pending')) {
        // Si en attente, afficher un bouton désactivé
        if (isset($poi['status']) && $poi['status'] === 'pending') {
            $userHasPending = (bool)($poi['user_has_pending'] ?? false);
            $title = $userHasPending ? 'Votre demande est en cours de validation' : 'Revendication en cours de validation';
            $text = $userHasPending ? 'En cours de validation' : 'En cours de validation';
            return '<span class="button-search button button-disabled" title="' . htmlspecialchars($title) . '">' .
                   '<i class="fa-solid fa-clock"></i> <span class="button-text">' . htmlspecialchars($text) . '</span></span>';
        }
        // Si déjà réclamé, ne rien afficher
        return '';
    }
    
    $poiId = (int)($poi['id'] ?? 0);
    if ($poiId <= 0) {
        return '';
    }
    
    $router = \Core\Router\Router::getInstance();
    $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
    $ctx = new \Core\Navigation\ConditionContext(\Core\Auth\UsersManager::getCurrentUser());
    $isLogged = $evaluator->canAccess(new \Core\Navigation\GenericResource('claim:access'), $ctx);
    
    try {
        $claimUrl = $router->generate('claim-start', ['id' => $poiId]);
    } catch (\Exception $e) {
        $claimUrl = '/claim/' . $poiId;
    }
    
    // Si l'utilisateur n'est pas connecté, rediriger vers la page de login avec message spécifique
    $loginUrl = '';
    if (!$isLogged) {
        try {
            $loginUrl = $router->generate('login') . '?msg=claim';
        } catch (\Exception $e) {
            $loginUrl = '/login?msg=claim';
        }
    }
    
    $href = $isLogged ? $claimUrl : $loginUrl;
    
    return '<a href="' . htmlspecialchars($href) . '" class="button-search button">' .
           '<i class="fa-solid fa-house-user"></i> <span class="button-text">C\'est Chez moi!</span></a>';
}

/**
 * Hook: user:avatar-menu
 * Ajoute l'entrée "Mes fiches" dans le menu utilisateur
 */
/**
 * Hook: user:avatar-menu
 * Ajoute l'entrée "Mes fiches" dans le menu utilisateur
 */
/**
 * Hook: user:avatar-menu
 * Ajoute l'entrée "Mes fiches" dans le menu utilisateur
 */
/**
 * Hook: user:avatar-menu
 * Ajoute l'entrée "Mes fiches" et le CTA de revendication dans le menu utilisateur
 */
function claimUserAvatarMenu(array $data): array
{
    $currentUser = \Core\Auth\CurrentUser::get();
    $router = \Core\Router\Router::getInstance();
    
    // 1. Bouton "Mes fiches" (si propriétaire d'au moins 1 fiche)
    if ($currentUser->hasClaims()) {
        try {
            $url = $router->generate('claim-my-claims');
        } catch (\Exception $e) {
            $url = '/claim/my-claims';
        }
        $data['items'][] = [
            'icon' => 'fa-solid fa-building',
            'label' => 'Mes fiches',
            'url' => $url,
            'order' => 60
        ];
    } else {
        // 2. CTA "Revendiquer" (si simple membre) -> Redirige vers tutoriel Page ID 19
        try {
            $url = $router->generate('page-read', ['name' => 'comment-revendiquer', 'id' => 19]);
        } catch (\Exception $e) {
            $url = '/page/comment-revendiquer-19';
        }
        $data['items'][] = [
            'icon' => 'fa-solid fa-house-user',
            'label' => 'Revendiquer',
            'url' => $url,
            'class' => 'avatar-cta-claim',
            'order' => 65
        ];
    }

    return $data;
}

/**
 * Hook poi:card-actions
 * Proxy vers claimSearchEVOPoiActions pour affichage direct
 */
function claimPoiCardActions($poi): void
{
    $html = claimSearchEVOPoiActions($poi);
    if ($html) {
        echo $html;
    }
}

/**
 * Hook poi:detail-actions
 * Affiche le bouton claim sur la page de détail
 */
function claimPoiDetailActions($poi): void
{
    $poiData = is_object($poi) ? (array)$poi : $poi;
    $core = \Core\Core::getInstance();
    $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
    $currentUser = \Core\Auth\UsersManager::getCurrentUser();
    $ctx = new \Core\Navigation\ConditionContext($currentUser);
    $isAdmin = $evaluator->isAdmin($ctx);
    $isLogged = $evaluator->canAccess(new \Core\Navigation\GenericResource('claim:access'), $ctx);
    $router = \Core\Router\Router::getInstance();
    
    // Les admins ne voient pas ce bouton — leur bloc de gestion est distinct dans detail.tpl
    if ($isAdmin) {
        return;
    }
    
    $claimedValidated = (bool)($poiData['claimed_validated'] ?? false);
    $status = $poiData['status'] ?? null;
    $userHasPending = (bool)($poiData['user_has_pending'] ?? false);
    $claimUrl = $poiData['claim_url'] ?? '#';
    
    // Si le POI est déjà revendiqué et validé, afficher le badge "Établissement vérifié"
    // pour les visiteurs (les propriétaires le voient dans leur bloc dédié)
    if ($claimedValidated) {
        echo '<span class="button-search button poi-badge-verified" title="Établissement vérifié">
                <i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i> <span class="button-text">Établissement vérifié</span>
              </span>';
        return;
    }
    
    try {
        $loginUrl = $router->generate('login');
    } catch (\Exception $e) {
        $loginUrl = '/login';
    }

    if ($status === 'pending') {
        $title = $userHasPending ? 'Votre demande est en cours de validation' : 'Revendication en cours de validation';
        $text = 'En cours de validation';
        echo '<span class="button-search button button-disabled" title="' . $title . '">
                <i class="fa-solid fa-clock"></i> <span class="button-text">' . $text . '</span>
              </span>';
        // Le bouton C'est chez moi (Claim) est désormais géré par ConversionOrchestrator
        // pour centraliser les appels d'action et éviter les doublons.
    }
}

/**
 * Hook: core:stats-collector
 */
/**
 * Centralized Service getter for Claim plugin
 */
function claimGetService(): \Claim\Services\ClaimService
{
    return new \Claim\Services\ClaimService();
}

function claimCollectStats(array $stats): array
{
    try {
        $service = claimGetService();
        $claimStats = $service->getStats();
        
        $stats['claim'] = [
            'claimed_pois' => $claimStats['claimed_pois']
        ];
        $stats['counts']['claimed_pois'] = $claimStats['claimed_pois'];
    } catch (\Exception $e) {
        // Ignorer
    }
    return $stats;
}

/**
 * Hook: user:profile-stats
 */
function claimProfileStats(array $stats): array
{
    try {
        $user = \Core\Auth\UsersManager::getCurrentUser();
        if (!$user) return $stats;

        $service = claimGetService();
        $claims = $service->getUserClaims((int)$user->id, null, null, true);
        
        $count = 0;
        foreach ($claims as $c) {
            if ($c['claim_status'] === 'approved') $count++;
        }

        if ($count > 0) {
            $stats[] = [
                'label' => 'POI Gérés',
                'value' => $count,
                'color' => '#2ecc71',
                'icon' => 'fa-solid fa-house-circle-check'
            ];
        }
    } catch (\Exception $e) {
        // Ignorer
    }
    return $stats;
}

/**
 * Hook: user:profile-activity
 */
function claimProfileActivity(array $activities): array
{
    try {
        $user = \Core\Auth\UsersManager::getCurrentUser();
        if (!$user) return $activities;

        $service = claimGetService();
        $claims = $service->getUserClaims((int)$user->id, null, null, false);

        if (!empty($claims)) {
            $items = [];
            foreach (array_slice($claims, 0, 5) as $claim) {
                $statusLabel = $claim['claim_status'] === 'approved' ? 'Validée' : ($claim['claim_status'] === 'rejected' ? 'Refusée' : 'En attente');
                $items[] = [
                    'text' => 'Demande de gestion : ' . ($claim['name'] ?: 'POI #' . $claim['poi_id']),
                    'subtext' => 'Statut : ' . $statusLabel,
                    'date' => date('d/m/Y', strtotime($claim['requested_at'])),
                    'url' => '#',
                    'icon' => 'fa-solid fa-file-signature'
                ];
            }
            $activities[] = [
                'title' => 'Établissements',
                'items' => $items
            ];
        }
    } catch (\Exception $e) {
        // Ignorer
    }
    return $activities;
}

// Les hooks seront enregistrés via claimBeforeRunPlugin qui est appelé après le chargement du Core



