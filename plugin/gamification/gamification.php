<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin Gamification — Système de points, paliers et badges
 */

function gamificationInstall(): void
{
    $dataDir = DATA_PLUGIN . 'gamification' . DS;
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    
    // Config par défaut
    $configFile = $dataDir . 'gamification_conf.json';
    if (!file_exists($configFile)) {
        @file_put_contents($configFile, json_encode([
            'activate' => true,
            'points_contribution' => 10,
            'points_contribution_accepted' => 50,
            'points_edit_poi' => 5,
            'points_edit_poi_accepted' => 25,
            'points_claim_poi' => 100,
            'points_claim_poi_validated' => 200,
            'points_login_daily' => 1,
            'points_first_contribution' => 50,
            'points_review_added' => 15,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    // Paliers par défaut
    $paliersFile = $dataDir . 'paliers.json';
    if (!file_exists($paliersFile)) {
        $defaultPaliers = [
            [
                'id' => 1,
                'name' => 'Débutant',
                'points_required' => 0,
                'badge_name' => 'Débutant',
                'badge_icon' => 'fa-seedling',
                'badge_color' => '#95a5a6'
            ],
            [
                'id' => 2,
                'name' => 'Contributeur',
                'points_required' => 100,
                'badge_name' => 'Contributeur',
                'badge_icon' => 'fa-hand-holding-heart',
                'badge_color' => '#3498db'
            ],
            [
                'id' => 3,
                'name' => 'Expert',
                'points_required' => 500,
                'badge_name' => 'Expert',
                'badge_icon' => 'fa-star',
                'badge_color' => '#f39c12'
            ],
            [
                'id' => 4,
                'name' => 'Maître',
                'points_required' => 1000,
                'badge_name' => 'Maître',
                'badge_icon' => 'fa-crown',
                'badge_color' => '#e74c3c'
            ],
            [
                'id' => 5,
                'name' => 'Légende',
                'points_required' => 5000,
                'badge_name' => 'Légende',
                'badge_icon' => 'fa-trophy',
                'badge_color' => '#9b59b6'
            ]
        ];
        @file_put_contents($paliersFile, json_encode($defaultPaliers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

function gamificationBeforeRunPlugin(): void
{
    foreach (glob(PLUGINS . 'gamification/controllers/' . '*.php') as $file) {
        require_once $file;
    }
    foreach (glob(PLUGINS . 'gamification/services/' . '*.php') as $file) {
        require_once $file;
    }
    
    // Hooks are now registered in hooks.json
}

/**
 * Hook pour les données de progression sur le profil
 */
function gamificationUserProfileProgression(array $params): array
{
    $userId = (int)($params['userId'] ?? 0);
    $data = $params['data'] ?? [];

    if ($userId <= 0) {
        return $data;
    }

    try {
        require_once PLUGINS . 'gamification/services/PointsService.php';
        $pointsService = new \Gamification\Services\PointsService();
        $pointsService->ensureUserHasPalier($userId);

        $currentPalier = $pointsService->getUserPalier($userId);
        if (!$currentPalier) {
            return $data;
        }

        $data['has_data'] = true;
        $data['badge_name'] = $currentPalier['badge_name'] ?? 'Débutant';
        $data['badge_icon'] = $currentPalier['badge_icon'] ?? 'fa-seedling';
        $data['badge_color'] = $currentPalier['badge_color'] ?? '#95a5a6';
    } catch (\Exception $e) {
        // Ignorer
    }

    return $data;
}

/**
 * Hook appelé quand une contribution est acceptée
 */
function gamificationOnContributionAccepted($data): void
{
    if (!isset($data['user_id']) || !isset($data['type'])) {
        return;
    }
    
    require_once PLUGINS . 'gamification/services/PointsService.php';
    $pointsService = new \Gamification\Services\PointsService();
    
    $userId = (int)$data['user_id'];
    $type = $data['type'] ?? 'contribution';
    
    // Points pour contribution acceptée
    // Distinguer entre proposition de nouveau POI (contribution) et suggestion de correction (edit_poi)
    $action = ($type === 'suggestion') ? 'edit_poi_accepted' : 'contribution_accepted';
    $points = $pointsService->getPointsForAction($action);

    if ($points > 0) {
        $pointsService->addPoints($userId, $points, $action, [
            'type' => $type,
            'poi_id' => $data['poi_id'] ?? null
        ]);
    }
}

/**
 * Hook appelé quand un claim est validé
 */
function gamificationOnClaimValidated($data): void
{
    if (!isset($data['user_id'])) {
        return;
    }
    
    require_once PLUGINS . 'gamification/services/PointsService.php';
    $pointsService = new \Gamification\Services\PointsService();
    
    $userId = (int)$data['user_id'];
    
    // Points pour claim validé
    $points = $pointsService->getPointsForAction('claim_poi_validated');
    if ($points > 0) {
        $pointsService->addPoints($userId, $points, 'claim_validated', [
            'poi_id' => $data['poi_id'] ?? null
        ]);
    }
}

/**
 * Hook appelé quand un utilisateur se connecte
 */
function gamificationOnUserLogin($data): void
{
    if (!isset($data['user_id'])) {
        return;
    }
    
    require_once PLUGINS . 'gamification/services/PointsService.php';
    $pointsService = new \Gamification\Services\PointsService();
    
    $userId = (int)$data['user_id'];
    
    // Vérifier si l'utilisateur a déjà reçu des points aujourd'hui
    $lastLoginFile = DATA_PLUGIN . 'gamification' . DS . 'daily_logins.json';
    $dailyLogins = file_exists($lastLoginFile) ? json_decode(file_get_contents($lastLoginFile), true) : [];
    if (!is_array($dailyLogins)) {
        $dailyLogins = [];
    }
    
    $today = date('Y-m-d');
    $lastLoginDate = $dailyLogins[$userId] ?? null;
    
    if ($lastLoginDate !== $today) {
        // Points pour connexion quotidienne
        $points = $pointsService->getPointsForAction('login_daily');
        if ($points > 0) {
            $pointsService->addPoints($userId, $points, 'login_daily', []);
        }
        
        $dailyLogins[$userId] = $today;
        \Utils\Util::writeJsonFile($lastLoginFile, $dailyLogins);
    }
}

/**
 * Hook appelé quand une proposition de POI est soumise
 */
function gamificationOnProposalSubmitted($data): void
{
    if (!isset($data['user_id'])) return;
    require_once PLUGINS . 'gamification/services/PointsService.php';
    $pointsService = new \Gamification\Services\PointsService();
    $points = $pointsService->getPointsForAction('contribution');
    if ($points > 0) {
        $pointsService->addPoints((int)$data['user_id'], $points, 'proposal_submitted', [
            'proposal_id' => $data['proposal_id'] ?? null
        ]);
    }
}

/**
 * Hook appelé quand une proposition de POI est approuvée
 */
function gamificationOnProposalApproved($data): void
{
    if (!isset($data['user_id'])) return;
    require_once PLUGINS . 'gamification/services/PointsService.php';
    $pointsService = new \Gamification\Services\PointsService();
    $points = $pointsService->getPointsForAction('contribution_accepted');
    if ($points > 0) {
        $pointsService->addPoints((int)$data['user_id'], $points, 'proposal_approved', [
            'poi_id' => $data['poi_id'] ?? null,
            'proposal_id' => $data['proposal_id'] ?? null
        ]);
    }
}

/**
 * Hook appelé quand une suggestion de correction est soumise
 */
function gamificationOnSuggestionSubmitted($data): void
{
    if (!isset($data['user_id']) || !isset($data['poi_id']) || !isset($data['field_name'])) return;
    
    // Attribuer des points selon le champ modifié, aligné sur le barème Datascore
    $field = $data['field_name'];
    $points = 0;
    
    // Barème aligné avec PoiManagerService::calculateDatascore
    $scoreMap = [
        'name' => 5, 'addr_street' => 10, 'category' => 5, 'subcategory' => 5,
        'contact_phone' => 5, 'contact_email' => 5, 'website' => 5,
        'desc_list' => 5, 'description' => 15,
        'logo' => 5, 'opening_hours' => 15,
        'wheelchair' => 10, 'internet_access' => 10, 'parking' => 10, 'fee' => 10, 'indoor' => 10, 'vending' => 10
    ];
    
    // Si le champ est dans le barème, on donne ces points, sinon 5 par défaut par édition
    $points = $scoreMap[$field] ?? 5;
    
    if ($points > 0) {
        require_once PLUGINS . 'gamification/services/PointsService.php';
        $pointsService = new \Gamification\Services\PointsService();
        $pointsService->addPoints((int)$data['user_id'], $points, 'suggestion_submitted', [
            'poi_id' => $data['poi_id'],
            'field' => $field
        ]);
    }
}

/**
 * Hook appelé quand un avis est ajouté
 */
function gamificationOnReviewAdded($data): void
{
    if (!isset($data['user_id'])) return;
    require_once PLUGINS . 'gamification/services/PointsService.php';
    $pointsService = new \Gamification\Services\PointsService();
    $points = $pointsService->getPointsForAction('review_added');
    if ($points > 0) {
        $pointsService->addPoints((int)$data['user_id'], $points, 'review_added', [
            'poi_id' => $data['poi_id'] ?? null,
            'rating' => $data['rating'] ?? null
        ]);
    }
}

// Le hook gamificationProfileContent n'est plus utilisé
// Les données sont maintenant préparées directement dans UsersLoginController::prepareGamificationData()

/**
 * Hook pour afficher la barre de progression de gamification sur toutes les pages
 */
function gamificationEndFrontBody(): void
{
    // Vérifier que le plugin est activé
    $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
    $plugin = $pluginsManager->getPlugin('gamification');
    
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return;
    }
    
    $user = \Core\Auth\UsersManager::getCurrentUser();
    
    // Vérifier les conditions de visibilité via les politiques
    $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
    $ctx = new \Core\Navigation\ConditionContext($user);
    if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('user:progression:view'), $ctx)) {
        return; 
    }
    
    $userId = (int)($user->attributes['id'] ?? 0);
    if ($userId <= 0) {
        return;
    }
    
    // Charger le service de points
    require_once PLUGINS . 'gamification/services/PointsService.php';
    $pointsService = new \Gamification\Services\PointsService();
    
    // S'assurer que l'utilisateur a au moins le palier "Débutant"
    $pointsService->ensureUserHasPalier($userId);
    
    // Récupérer les données
    $points = $pointsService->getUserPoints($userId);
    $currentPalier = $pointsService->getUserPalier($userId);
    $paliers = $pointsService->getPaliers();
    
    // Trouver le prochain palier
    $nextPalier = null;
    foreach ($paliers as $palier) {
        if (($palier['points_required'] ?? 0) > $points) {
            $nextPalier = $palier;
            break;
        }
    }
    
    // Calculer la progression
    $progressPercent = 0;
    $pointsRemaining = 0;
    $currentPalierPoints = 0;
    
    if ($nextPalier) {
        $nextPalierPoints = $nextPalier['points_required'] ?? 0;
        $currentPalierPoints = $currentPalier ? ($currentPalier['points_required'] ?? 0) : 0;
        $pointsInCurrentRange = $points - $currentPalierPoints;
        $pointsNeededForNext = $nextPalierPoints - $currentPalierPoints;
        
        if ($pointsNeededForNext > 0) {
            $progressPercent = ($pointsInCurrentRange / $pointsNeededForNext) * 100;
            if ($progressPercent > 100) {
                $progressPercent = 100;
            }
            $pointsRemaining = max(0, $nextPalierPoints - $points);
        }
    } else {
        // L'utilisateur a atteint le dernier palier
        $progressPercent = 100;
    }
    
    // Préparer les données pour le template
    $currentPalierName = $currentPalier ? ($currentPalier['name'] ?? 'Débutant') : 'Débutant';
    $currentPalierColor = $currentPalier ? ($currentPalier['badge_color'] ?? '#95a5a6') : '#95a5a6';
    $currentPalierIcon = $currentPalier ? ($currentPalier['badge_icon'] ?? 'fa-seedling') : 'fa-seedling';
    $nextPalierName = $nextPalier ? ($nextPalier['name'] ?? '') : '';
    $nextPalierPoints = $nextPalier ? ($nextPalier['points_required'] ?? 0) : 0;
    
    // Générer l'URL du profil gamification
    $router = \Core\Router\Router::getInstance();
    try {
        $profileUrl = $router->generate('gamification-profile');
    } catch (\Exception $e) {
        $profileUrl = '/users/profile/gamification';
    }
    
    // Générer le HTML de la barre de progression
    $progressBarHtml = '
    <div id="gamification-progress-bar" class="gamification-progress-bar-collapsed">
        <div class="gamification-progress-bar-toggle" onclick="toggleGamificationProgressBar()">
            <div class="gamification-badge">
                <i class="fa-solid ' . htmlspecialchars($currentPalierIcon, ENT_QUOTES, 'UTF-8') . '"></i>
            </div>
            <div class="gamification-level-name">' . htmlspecialchars($currentPalierName, ENT_QUOTES, 'UTF-8') . '</div>
        </div>
        <div class="gamification-progress-bar-content">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%; margin-bottom: 0.5rem;">
                <div style="flex: 1;"></div>
                <button onclick="toggleGamificationProgressBar()" class="gamification-close-btn" style="background: transparent; border: none; color: #6c757d; font-size: 1.2rem; cursor: pointer; padding: 0.25rem 0.5rem; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.backgroundColor=\'#f0f0f0\'; this.style.color=\'#333\';" onmouseout="this.style.backgroundColor=\'transparent\'; this.style.color=\'#6c757d\';" title="Fermer">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div style="display: flex; align-items: center; gap: 0.75rem; min-width: 200px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: ' . htmlspecialchars($currentPalierColor, ENT_QUOTES, 'UTF-8') . '; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                    <i class="fa-solid ' . htmlspecialchars($currentPalierIcon, ENT_QUOTES, 'UTF-8') . '"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: bold; color: #333; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' . htmlspecialchars($currentPalierName, ENT_QUOTES, 'UTF-8') . '</div>
                    <div style="color: #6c757d; font-size: 0.75rem;">' . number_format($points, 0, ',', ' ') . ' points</div>
                </div>
            </div>
            <div style="flex: 1; min-width: 200px; max-width: 500px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                    <span style="font-size: 0.75rem; color: #6c757d;">';
    
    if ($nextPalier) {
        $progressBarHtml .= 'Prochain palier: <strong>' . htmlspecialchars($nextPalierName, ENT_QUOTES, 'UTF-8') . '</strong> (' . number_format($nextPalierPoints, 0, ',', ' ') . ' pts)';
    } else {
        $progressBarHtml .= '<strong>Palier maximum atteint !</strong>';
    }
    
    $progressBarHtml .= '
                    </span>
                    <span style="font-size: 0.75rem; color: #6c757d; font-weight: bold;">' . round($progressPercent) . '%</span>
                </div>
                <div style="width: 100%; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden;">
                    <div style="width: ' . round($progressPercent) . '%; height: 100%; background: linear-gradient(90deg, ' . htmlspecialchars($currentPalierColor, ENT_QUOTES, 'UTF-8') . ', ' . ($nextPalier ? htmlspecialchars($nextPalier['badge_color'] ?? $currentPalierColor, ENT_QUOTES, 'UTF-8') : $currentPalierColor) . '); transition: width 0.3s ease; border-radius: 4px;"></div>
                </div>
                ';
    
    if ($nextPalier && $pointsRemaining > 0) {
        $progressBarHtml .= '<div style="font-size: 0.7rem; color: #6c757d; margin-top: 0.25rem;">' . number_format($pointsRemaining, 0, ',', ' ') . ' points restants</div>';
    }
    
    $progressBarHtml .= '
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="' . htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') . '" style="padding: 0.5rem 1rem; background: ' . htmlspecialchars($currentPalierColor, ENT_QUOTES, 'UTF-8') . '; color: white; text-decoration: none; border-radius: 4px; font-size: 0.85rem; white-space: nowrap; transition: opacity 0.2s;" onmouseover="this.style.opacity=\'0.9\'" onmouseout="this.style.opacity=\'1\'">
                    <i class="fa-solid fa-trophy"></i> Voir le détail
                </a>
            </div>
        </div>
    </div>
    <style>
    #gamification-progress-bar {
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 1000;
        transition: all 0.3s ease;
    }
    
    /* Mode reduit par defaut - Badge compact en bas a gauche */
    .gamification-progress-bar-collapsed {
        padding: 0;
    }
    
    .gamification-progress-bar-collapsed .gamification-progress-bar-content {
        display: none;
    }
    
    .gamification-progress-bar-collapsed .gamification-progress-bar-toggle {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        padding: 0;
        background: transparent;
    }
    
    .gamification-progress-bar-collapsed .gamification-badge {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: ' . htmlspecialchars($currentPalierColor, ENT_QUOTES, 'UTF-8') . ';
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }
    
    .gamification-progress-bar-collapsed .gamification-badge:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(0,0,0,0.3);
    }
    
    .gamification-progress-bar-collapsed .gamification-level-name {
        font-size: 0.65rem;
        color: #333;
        font-weight: 600;
        text-align: center;
        white-space: nowrap;
        background: white;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        margin-top: 0.25rem;
    }
    
    /* Mode etendu - Panneau qui s ouvre depuis le badge */
    .gamification-progress-bar-expanded {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        padding: 1rem;
        min-width: 320px;
        max-width: 500px;
    }
    
    .gamification-progress-bar-expanded .gamification-progress-bar-content {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .gamification-progress-bar-expanded .gamification-progress-bar-toggle {
        display: none;
    }
    
    .gamification-close-btn {
        flex-shrink: 0;
    }
    
    .gamification-progress-bar-expanded .gamification-progress-bar-content > div:nth-child(2) {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .gamification-progress-bar-expanded .gamification-progress-bar-content > div:nth-child(3) {
        flex: 1;
    }
    
    .gamification-progress-bar-expanded .gamification-progress-bar-content > div:last-child {
        display: flex;
        justify-content: flex-end;
    }
    
    /* Ajuster le padding du body - pas necessaire car c est un badge flottant */
    body {
        padding-bottom: 0;
    }
    
    body.gamification-bar-expanded {
        padding-bottom: 0;
    }
    
    /* Responsive : sur mobile, le panneau s adapte */
    @media (max-width: 768px) {
        #gamification-progress-bar {
            bottom: 15px;
            left: 15px;
        }
        
        .gamification-progress-bar-expanded {
            min-width: calc(100vw - 30px);
            max-width: calc(100vw - 30px);
        }
        
        .gamification-progress-bar-collapsed .gamification-badge {
            width: 50px;
            height: 50px;
            font-size: 1.3rem;
        }
        
        .gamification-progress-bar-collapsed .gamification-level-name {
            font-size: 0.6rem;
        }
    }
    </style>
    <script>
    function toggleGamificationProgressBar() {
        const bar = document.getElementById("gamification-progress-bar");
        const body = document.body;
        if (bar.classList.contains("gamification-progress-bar-collapsed")) {
            bar.classList.remove("gamification-progress-bar-collapsed");
            bar.classList.add("gamification-progress-bar-expanded");
            body.classList.add("gamification-bar-expanded");
            localStorage.setItem("gamification-progress-bar-expanded", "true");
        } else {
            bar.classList.remove("gamification-progress-bar-expanded");
            bar.classList.add("gamification-progress-bar-collapsed");
            body.classList.remove("gamification-bar-expanded");
            localStorage.setItem("gamification-progress-bar-expanded", "false");
        }
    }
    
    // Restaurer l\'état sauvegardé au chargement de la page
    document.addEventListener("DOMContentLoaded", function() {
        const bar = document.getElementById("gamification-progress-bar");
        const body = document.body;
        if (bar) {
            const savedState = localStorage.getItem("gamification-progress-bar-expanded");
            if (savedState === "true") {
                bar.classList.remove("gamification-progress-bar-collapsed");
                bar.classList.add("gamification-progress-bar-expanded");
                body.classList.add("gamification-bar-expanded");
            }
        }
    });
    </script>';
    
    echo $progressBarHtml;
}

/**
 * Hook: user:avatar-menu
 * Ajoute l'entrée "Progression" dans le menu utilisateur
 */
function gamificationUserAvatarMenu(array $data): array
{
    // Important: le hook user:avatar-menu utilise la clé 'items'
    $items = $data['items'] ?? [];
    
    // Le paramètre $data['user'] peut être absent selon l'appelant, on sécurise via UsersManager
    $user = \Core\Auth\UsersManager::getCurrentUser();
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('gamification');
    
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate') || !$user) {
        return $data;
    }
    
    $userId = (int)($user->attributes['id'] ?? 0);
    if ($userId <= 0) {
        return $data;
    }

    $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
    $ctx = new \Core\Navigation\ConditionContext($user);
    
    // On vérifie la politique de progression
    if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('user:progression:view'), $ctx)) {
        return $data;
    }

    try {
        require_once PLUGINS . 'gamification/services/PointsService.php';
        $pointsService = new \Gamification\Services\PointsService();
        $currentPalier = $pointsService->getUserPalier($userId);
        
        $badgeIcon = $currentPalier['badge_icon'] ?? 'fa-solid fa-trophy';
        $badgeColor = $currentPalier['badge_color'] ?? '#95a5a6';
        $badgeName = $currentPalier['name'] ?? '';
        
        $router = \Core\Router\Router::getInstance();
        try {
            $url = $router->generate('profile') . '?tab=gamification';
        } catch (\Exception $e) {
            $url = '/profile?tab=gamification';
        }
        
        $items[] = [
            'label' => 'Ma progression',
            'url' => $url,
            'icon' => $badgeIcon,
            'class' => 'avatar-menu-item-progression',
            'order' => 35,
            'badge_color' => $badgeColor,
            'badge_text' => $badgeName
        ];

        // Add Leaderboard link
        try {
            $leaderboardUrl = $router->generate('gamification-leaderboard');
        } catch (\Exception $e) {
            $leaderboardUrl = '/gamification/leaderboard';
        }
        
        $items[] = [
            'label' => 'Classement',
            'url' => $leaderboardUrl,
            'icon' => 'fa-solid fa-ranking-star',
            'class' => 'avatar-menu-item',
            'order' => 36
        ];

    } catch (\Exception $e) {
        // Ignorer
    }
    
    $data['items'] = $items;
    return $data;
}

/**
 * Hook: user:profile-tabs
 * Ajoute l'onglet "Progression" dans la page profil
 */
function gamificationUserProfileTabs($user): string
{
    // DEBUG LOG
    file_put_contents(DATA . 'logs/gamification_debug.log', date('[Y-m-d H:i:s] ') . "gamificationUserProfileTabs called for user: " . (is_object($user) ? $user->id : 'NOT_OBJECT') . "\n", FILE_APPEND);

    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('gamification');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        file_put_contents(DATA . 'logs/gamification_debug.log', date('[Y-m-d H:i:s] ') . "Plugin check failed: " . ($plugin ? 'Installed: '.$plugin->isInstalled().' Active: '.$plugin->getConfigVal('activate') : 'Plugin not found') . "\n", FILE_APPEND);
        return '';
    }
    
    return '
    <button type="button" class="profile-tab-button" role="tab" aria-selected="false" aria-controls="tab-gamification" id="tab-button-gamification" data-tab="gamification">
        <i class="fa-solid fa-trophy"></i> <span>Progression</span>
    </button>';
}

/**
 * Hook: user:profile-tabs-content
 * Affiche le contenu de l'onglet gamification dans le profil
 */
function gamificationUserProfileTabsContent($user): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('gamification');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return '';
    }

    $userId = (int)($user->attributes['id'] ?? $user->id ?? 0);
    if ($userId <= 0) {
        return '';
    }

    try {
        require_once PLUGINS . 'gamification/services/PointsService.php';
        $pointsService = new \Gamification\Services\PointsService();
        $pointsService->ensureUserHasPalier($userId);
        
        $stats = $pointsService->getUserStats($userId);
        $transactions = $pointsService->getUserTransactions($userId, 50);
        $paliers = $pointsService->getPaliers();
        
        $totalPoints = $stats['total_points'] ?? 0;
        $currentPalier = $stats['current_palier'] ?? null;
        $nextPalier = $stats['next_palier'] ?? null;
        
        // Calculer la progression vers le prochain palier
        $progressPercent = 0;
        $progressPercentRounded = 0;
        $pointsRemaining = 0;
        if ($nextPalier) {
            $pointsRequired = $nextPalier['points_required'] ?? 0;
            if ($pointsRequired > 0) {
                $progressPercent = ($totalPoints / $pointsRequired) * 100;
                $progressPercent = min(100, $progressPercent);
                $progressPercentRounded = round($progressPercent);
                $pointsRemaining = max(0, $pointsRequired - $totalPoints);
            }
        }
        
        // Préparer les paliers
        $paliersPrepared = [];
        foreach ($paliers as $palier) {
            $isCurrent = ($currentPalier && isset($currentPalier['id']) && $palier['id'] == $currentPalier['id']);
            $isReached = ($totalPoints >= ($palier['points_required'] ?? 0));
            
            $borderColor = '#ddd';
            $bgColor = 'white';
            if ($isCurrent) {
                $borderColor = $palier['badge_color'] ?? '#3498db';
                $bgColor = ($palier['badge_color'] ?? '#3498db') . '20';
            }
            
            $paliersPrepared[] = [
                'id' => $palier['id'],
                'name' => $palier['name'] ?? '',
                'points_required' => $palier['points_required'] ?? 0,
                'badge_name' => $palier['badge_name'] ?? '',
                'badge_icon' => $palier['badge_icon'] ?? 'fa-star',
                'badge_color' => $palier['badge_color'] ?? '#3498db',
                'is_current' => $isCurrent,
                'is_reached' => $isReached,
                'border_color' => $borderColor,
                'bg_color' => $bgColor
            ];
        }
        
        // Préparer les transactions
        $transactionsPrepared = [];
        foreach ($transactions as $transaction) {
            $reason = $transaction['reason'] ?? 'unknown';
            $label = 'Action';
            $icon = 'fa-circle';
            
            if ($reason === 'contribution_accepted') {
                $label = 'Contribution acceptée';
                $icon = 'fa-hand-holding-heart';
            } elseif ($reason === 'claim_validated') {
                $label = 'Revendication validée';
                $icon = 'fa-house-user';
            } elseif ($reason === 'login_daily') {
                $label = 'Connexion quotidienne';
                $icon = 'fa-calendar-day';
            }
            
            $transactionsPrepared[] = [
                'date' => $transaction['date'] ?? '',
                'reason' => $reason,
                'label' => $label,
                'icon' => $icon,
                'points' => $transaction['points'] ?? 0
            ];
        }
        
        $actionsCountPrepared = [];
        foreach (($stats['actions_count'] ?? []) as $action => $count) {
            $label = $action;
            if ($action === 'contribution_accepted') $label = 'Contributions acceptées';
            elseif ($action === 'claim_validated') $label = 'Revendications validées';
            elseif ($action === 'login_daily') $label = 'Connexions quotidiennes';
            
            $actionsCountPrepared[] = [
                'action' => $action,
                'label' => $label,
                'count' => $count
            ];
        }
        
        $currentColor = '#3498db';
        if ($currentPalier && isset($currentPalier['badge_color'])) {
            $currentColor = $currentPalier['badge_color'];
        }
        
        $todayMetrics = $stats['today_metrics'] ?? [
            'proposals_today' => 0, 'contributions_today' => 0, 'photos_today' => 0, 'points_today' => 0, 'actions_today' => 0
        ];
        
        $chartHeights = [
            'points' => $todayMetrics['points_today'] > 0 ? min(100, ($todayMetrics['points_today'] / 100) * 100) : 5,
            'actions' => $todayMetrics['actions_today'] > 0 ? min(100, ($todayMetrics['actions_today'] / 10) * 100) : 5,
            'proposals' => $todayMetrics['proposals_today'] > 0 ? min(100, ($todayMetrics['proposals_today'] / 5) * 100) : 5,
            'contributions' => $todayMetrics['contributions_today'] > 0 ? min(100, ($todayMetrics['contributions_today'] / 5) * 100) : 5,
            'photos' => $todayMetrics['photos_today'] > 0 ? min(100, ($todayMetrics['photos_today'] / 10) * 100) : 5
        ];
        
        $statsPrepared = [
            'total_points' => $totalPoints,
            'total_transactions' => $stats['total_transactions'] ?? 0,
            'current_palier' => $currentPalier,
            'next_palier' => $nextPalier,
            'progress_percent' => $progressPercent,
            'progress_percent_rounded' => $progressPercentRounded,
            'points_remaining' => $pointsRemaining,
            'has_current_palier' => ($currentPalier !== null),
            'has_next_palier' => ($nextPalier !== null),
            'current_color' => $currentColor,
            'show_progress_text' => ($progressPercent >= 20),
            'actions_count' => $actionsCountPrepared,
            'today_metrics' => $todayMetrics,
            'chart_heights' => $chartHeights,
            'has_activity_today' => ($todayMetrics['points_today'] > 0 || $todayMetrics['actions_today'] > 0)
        ];
        
        $html = '<div class="profile-tab-content" id="tab-gamification" role="tabpanel" aria-labelledby="tab-button-gamification">';
        
        // Rendre le template de progression du plugin
        $tpl = new \Template\Template(PLUGINS . 'gamification/template/profile.tpl');
        $tpl->set('stats', $statsPrepared);
        $tpl->set('transactions', $transactionsPrepared);
        $tpl->set('paliers', $paliersPrepared);
        $tpl->set('user', $user);
        
        $html .= $tpl->output();
        $html .= '</div>';
        
        return $html;
        
    } catch (\Exception $e) {
        return '<div class="profile-tab-content" id="tab-gamification" role="tabpanel" aria-labelledby="tab-button-gamification">
            <div class="profile-stats">
                <p style="text-align: center; padding: 2rem; color: var(--text-muted);">Impossible de charger les données de progression.</p>
            </div>
        </div>';
    }
}

/**
 * Listener: gamification:badge-unlocked
 * Pont entre le domaine (Gamification) et l'action (Notifications)
 */
function gamificationOnBadgeUnlocked(array $data): void
{
    $userId = (int)($data['user_id'] ?? 0);
    $palier = $data['palier'] ?? null;

    if ($userId <= 0 || !$palier) {
        return;
    }

    \Core\Core::getInstance()->callHook('notifications:send', [
        'user_id' => $userId,
        'type' => 'gamification.badge',
        'source_plugin' => 'gamification',
        'data' => [
            'key' => 'gamification.badge',
            'params' => [
                'badge' => $palier['badge_name'] ?? 'Nouveau Badge'
            ],
            'url' => '/users/profile/gamification',
            'entity' => [
                'type' => 'palier',
                'id' => $palier['id'] ?? null
            ]
        ],
        'external_id' => 'gamification:badge:' . ($palier['id'] ?? 'unknown') . ':' . $userId
    ]);
}

