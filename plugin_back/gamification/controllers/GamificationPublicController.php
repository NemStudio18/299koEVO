<?php

namespace Gamification\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Gamification\Services\PointsService;

defined('ROOT') or exit('No direct script access allowed');

class GamificationPublicController extends PublicController
{
    public function profile()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('user:progression:view'), $ctx)) {
            $this->core->redirect($this->router->generate('login'));
            return;
        }
        
        require_once PLUGINS . 'gamification/services/PointsService.php';
        $pointsService = new PointsService();
        
        $userId = (int)$this->user->attributes['id'];
        $stats = $pointsService->getUserStats($userId);
        $transactions = $pointsService->getUserTransactions($userId, 50);
        $paliers = $pointsService->getPaliers();
        
        // Préparer les données pour le template (calculs, formatage)
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
                if ($progressPercent > 100) {
                    $progressPercent = 100;
                }
                $progressPercentRounded = round($progressPercent);
                $pointsRemaining = max(0, $pointsRequired - $totalPoints);
            }
        }
        
        // Préparer les paliers avec des flags pour le template
        $paliersPrepared = [];
        foreach ($paliers as $palier) {
            $isCurrent = ($currentPalier && isset($currentPalier['id']) && $palier['id'] == $currentPalier['id']);
            $isReached = ($totalPoints >= ($palier['points_required'] ?? 0));
            
            // Calculer les couleurs de bordure et de fond
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
        
        // Préparer les transactions avec des labels formatés
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
        
        // Préparer les actions_count avec des labels
        $actionsCountPrepared = [];
        foreach (($stats['actions_count'] ?? []) as $action => $count) {
            $label = $action;
            if ($action === 'contribution_accepted') {
                $label = 'Contributions acceptées';
            } elseif ($action === 'claim_validated') {
                $label = 'Revendications validées';
            } elseif ($action === 'login_daily') {
                $label = 'Connexions quotidiennes';
            }
            
            $actionsCountPrepared[] = [
                'action' => $action,
                'label' => $label,
                'count' => $count
            ];
        }
        
        // Préparer les stats avec les valeurs calculées
        $currentColor = '#3498db';
        if ($currentPalier && isset($currentPalier['badge_color'])) {
            $currentColor = $currentPalier['badge_color'];
        }
        
        // Récupérer les métriques du jour
        $todayMetrics = $stats['today_metrics'] ?? [
            'proposals_today' => 0,
            'contributions_today' => 0,
            'photos_today' => 0,
            'points_today' => 0,
            'actions_today' => 0
        ];
        
        // Calculer les hauteurs pour le graphique (max 100%)
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
            'show_progress_text' => ($progressPercent >= 20), // Booléen pour afficher le pourcentage
            'actions_count' => $actionsCountPrepared,
            'today_metrics' => $todayMetrics,
            'chart_heights' => $chartHeights,
            'has_activity_today' => ($todayMetrics['points_today'] > 0 || $todayMetrics['actions_today'] > 0)
        ];
        
        $response = new PublicResponse();
        $response->setTitle('Ma progression');
        $tpl = $response->createPluginTemplate('gamification', 'profile');
        
        $tpl->set('stats', $statsPrepared);
        $tpl->set('transactions', $transactionsPrepared);
        $tpl->set('paliers', $paliersPrepared);
        $tpl->set('user', $this->user);
        
        $response->addTemplate($tpl);
        return $response;
    }
    
    public function leaderboard()
    {
        // Vérifier si le leaderboard public est activé
        $configFile = DATA_PLUGIN . 'gamification' . DS . 'gamification_conf.json';
        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        
        if (!($config['public_leaderboard'] ?? false)) {
            $this->core->redirect($this->router->generate('home'));
            return;
        }
        
        require_once PLUGINS . 'gamification/services/PointsService.php';
        $pointsService = new PointsService();
        
        // Récupérer le leaderboard (top 100)
        $leaderboard = $pointsService->getLeaderboard(100);
        
        // Enrichir avec les informations des utilisateurs
        $leaderboardEnriched = [];
        $rank = 1;
        foreach ($leaderboard as $entry) {
            $user = \Core\Auth\User::findPK($entry['user_id']);
            if ($user) {
                // Determine username: username > firstname+lastname > email
                $username = $user->attributes['username'] ?? '';
                if (empty($username)) {
                     $firstname = $user->attributes['firstname'] ?? '';
                     $lastname = $user->attributes['lastname'] ?? '';
                     if (!empty($firstname) || !empty($lastname)) {
                         $username = trim($firstname . ' ' . $lastname);
                     } else {
                         $username = $user->attributes['email'] ?? 'Utilisateur';
                     }
                }

                $leaderboardEnriched[] = [
                    'rank' => $rank,
                    'user_id' => $entry['user_id'],
                    'username' => $username,
                    'email' => $user->attributes['email'] ?? '',
                    'total_points' => $entry['total_points'],
                    'current_palier' => $entry['current_palier'] ?? null
                ];
                $rank++;
            }
        }
        
        $response = new PublicResponse();
        $response->setTitle('Classement - Progression');
        $tpl = $response->createPluginTemplate('gamification', 'leaderboard');
        $tpl->set('leaderboard', $leaderboardEnriched);
        $tpl->set('currentUserId', $this->user ? (int)$this->user->attributes['id'] : null);
        $tpl->set('ROUTER', $this->router);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function publicProfile($id)
    {
        $userId = (int)$id;
        if ($userId <= 0) {
            $this->core->redirect($this->router->generate('gamification-leaderboard'));
            return;
        }

        // Restrict to logged in users
        if (!\Core\Auth\UsersManager::isLogged()) {
            $this->core->redirect($this->router->generate('login'));
            return;
        }
        
        $currentUser = \Core\Auth\UsersManager::getCurrentUser();

        require_once PLUGINS . 'gamification/services/PointsService.php';
        $pointsService = new PointsService();
        
        $targetUser = \Core\Auth\User::findPK($userId);
        if (!$targetUser) {
            $this->core->redirect($this->router->generate('gamification-leaderboard'));
            return;
        }

        // Determine username for display
        $username = $targetUser->attributes['username'] ?? '';
        if (empty($username)) {
             $firstname = $targetUser->attributes['firstname'] ?? '';
             $lastname = $targetUser->attributes['lastname'] ?? '';
             if (!empty($firstname) || !empty($lastname)) {
                 $username = trim($firstname . ' ' . $lastname);
             } else {
                 $username = $targetUser->attributes['email'] ?? 'Utilisateur';
             }
        }
        // Inject display name into local object for template (not saving to DB)
        $targetUser->attributes['username'] = $username;

        $stats = $pointsService->getUserStats($userId);
        $paliers = $pointsService->getPaliers();
        
        $totalPoints = $stats['total_points'] ?? 0;
        $currentPalier = $stats['current_palier'] ?? null;
        $nextPalier = $stats['next_palier'] ?? null;
        
        // Calculer la progression
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

        // Préparer les actions_count
        $actionsCountPrepared = [];
        foreach (($stats['actions_count'] ?? []) as $action => $count) {
            $label = $action;
            if ($action === 'contribution_accepted') {
                $label = 'Contributions acceptées';
            } elseif ($action === 'claim_validated') {
                $label = 'Revendications validées';
            } elseif ($action === 'login_daily') {
                $label = 'Connexions quotidiennes';
            }
            
            $actionsCountPrepared[] = [
                'action' => $action,
                'label' => $label,
                'count' => $count
            ];
        }
        
        $currentColor = $currentPalier['badge_color'] ?? '#3498db';
        
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
            'actions_count' => $actionsCountPrepared
        ];
        
        // Check if messaging plugin is active
        $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
        $messagingPlugin = $pluginsManager->getPlugin('messaging');
        $messagingEnabled = ($messagingPlugin && $messagingPlugin->isInstalled() && $messagingPlugin->getConfigVal('activate'));

        $response = new PublicResponse();
        $response->setTitle('Profil de ' . $username);
        $tpl = $response->createPluginTemplate('gamification', 'public_profile');
        
        $tpl->set('stats', $statsPrepared);
        $tpl->set('paliers', $paliersPrepared);
        $tpl->set('targetUser', $targetUser);
        $tpl->set('currentUser', $currentUser);
        $tpl->set('messagingEnabled', $messagingEnabled);
        $tpl->set('ROUTER', $this->router);
        
        $response->addTemplate($tpl);
        return $response;
    }
}

