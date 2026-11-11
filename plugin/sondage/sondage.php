<?php

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

use Core\Core;

require_once PLUGINS . 'sondage/entities/Sondage.php';
require_once PLUGINS . 'sondage/entities/SondageManager.php';

if (!class_exists('SondageManager', false)) {
    class_alias(\Sondage\Entities\SondageManager::class, 'SondageManager');
}
if (!class_exists('Sondage', false)) {
    class_alias(\Sondage\Entities\Sondage::class, 'Sondage');
}

## Fonction d'installation

function sondageInstall() {
    \Utils\Util::writeJsonFile(DATA_PLUGIN . 'sondage/sondages.json', []);
}

function sondageUserProfileStats($stats = []) {
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('sondage');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return $stats;
    }

    $user = \Core\Auth\UsersManager::getCurrentUser();
    if (!$user) {
        return $stats;
    }

    try {
        $manager = new SondageManager();
        $items = $manager->getItems();
        $voteCount = 0;
        $userId = (string)$user->attributes['id'];

        foreach ($items as $item) {
            $votes = $item->getVotes();
            if (is_array($votes)) {
                foreach ($votes as $vote) {
                    if (isset($vote['userId']) && (string)$vote['userId'] === $userId) {
                        $voteCount++;
                        break; // Compte un vote par sondage
                    }
                }
            }
        }

        $stats[] = [
            'label' => 'Sondages auxquels j\'ai participé',
            'value' => (isset($voteCount) && $voteCount > 0) ? $voteCount : 0,
            'color' => '#1abc9c',
            'icon' => 'fa-solid fa-square-check'
        ];
    } catch (\Exception $e) {
        $stats[] = [
            'label' => 'Sondages auxquels j\'ai participé',
            'value' => '-',
            'color' => '#1abc9c',
            'icon' => 'fa-solid fa-square-check'
        ];
    }

    return $stats;
}

function sondageUserProfileActivity($user) {
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('sondage');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return [];
    }

    if (!$user) {
        return [];
    }

    $activities = [];
    try {
        $manager = new SondageManager();
        $items = $manager->getItems();
        $userId = (string)$user->attributes['id'];
        $userVotes = [];

        foreach ($items as $item) {
            $votes = $item->getVotes();
            if (is_array($votes)) {
                foreach ($votes as $vote) {
                    if (isset($vote['userId']) && (string)$vote['userId'] === $userId) {
                        $userVotes[] = [
                            'text' => 'A voté au sondage : ' . $item->getTitle(),
                            'subtext' => 'Le ' . (is_numeric($vote['date']) ? date('d/m/Y', $vote['date']) : date('d/m/Y', strtotime($vote['date']))), 
                            'date' => is_numeric($vote['date']) ? date('Y-m-d H:i:s', $vote['date']) : $vote['date'],
                            'url' => \Core\Router\Router::getInstance()->generate('sondage-read', ['id' => $item->getId()]),
                            'icon' => 'fa-solid fa-poll'
                        ];
                        break;
                    }
                }
            }
        }
        
        // Trier par date décroissante
        usort($userVotes, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        // Garder les 5 derniers
        $userVotes = array_slice($userVotes, 0, 5);

        if (!empty($userVotes)) {
            $activities[] = [
                'title' => 'Derniers sondages',
                'items' => $userVotes
            ];
        }

    } catch (\Exception $e) {
        // Ignorer les erreurs
    }

    return $activities;
}

/**
 * Hook pour la recherche globale : cherche dans les sondages
 */
function sondageSearchResultsHook(array $data): array
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('sondage');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return $data;
    }

    $query = $data['query'];
    $results = [];
    try {
        $manager = new SondageManager();
        $items = $manager->getItems();

        foreach ($items as $item) {
            if (stripos($item->getTitle(), $query) !== false) {
                $results[] = [
                    'title' => $item->getTitle(),
                    'excerpt' => 'Sondage : ' . count($item->getOptions()) . ' options disponibles.',
                    'url' => \Core\Router\Router::getInstance()->generate('sondage-read', ['id' => $item->getId()]),
                    'icon' => 'fa-solid fa-poll',
                    'source' => 'sondage',
                    'source_label' => 'Sondage',
                    'date' => $item->getDate(),
                    'date_formatted' => date('d/m/Y', strtotime($item->getDate())),
                    'score' => (stripos($item->getTitle(), $query) !== false ? 10 : 0)
                ];
            }
        }
        $data['results'] = array_merge($data['results'], $results);
    } catch (\Exception $e) {
        // Ignorer
    }
    return $data;
}

/**
 * Hook: Injecte le CSS public pour le mode sombre
 */
function sondageEndFrontHead($param = ''): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('sondage');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return (string)$param;
    }

    $output = (string)$param;
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/sondage') === false) {
        return $output;
    }
    
    $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
    $cssUrl = $siteUrl . '/plugin/sondage/template/public.css';
    
    return $output . '<link rel="stylesheet" href="' . $cssUrl . '?v=' . VERSION . '">' . "\n";
}

/**
 * Hook: navigation:catalog
 * Déclare le plugin dans le catalogue du gestionnaire de menus (/admin/menus).
 */
function sondageNavigationCatalog(array $catalog): array
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('sondage');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return $catalog;
    }

    $catalog[] = [
        'plugin'      => 'sondage',
        'key'         => 'sondage:sondage-home',
        'route'       => 'sondage-home',
        'label'       => 'Sondages',
        'icon'        => 'fa-solid fa-chart-bar',
        'description' => 'Liste des sondages en cours',
    ];
    return $catalog;
}

/**
 * Hook: beforeRunPlugin
 */
function sondageBeforeRunPlugin(): void
{
    // Hooks are now declared in param/hooks.json
}

/**
 * Hook: dashboard:inject-widgets
 * Injecte les sondages actifs dans le dashboard
 */
function sondageInjectDashboardWidgets($widgets)
{
    if (!is_array($widgets)) {
        $widgets = [];
    }
    
    $router = \Core\Router\Router::getInstance();
    $activePollsPrepared = [];
    
    try {
        if (class_exists('\SondageManager')) {
            $sondageManager = new \SondageManager();
            $polls = $sondageManager->getActiveItems();
            // Derniers actifs en premier
            $polls = array_reverse($polls);
            
            foreach ($polls as $k => $p) {
                if (is_object($p)) {
                    // Générer URL
                    $pollUrl = '#';
                    try {
                        $pollUrl = $router->generate('sondage-read', ['id' => $p->getId()]);
                    } catch (\Exception $e) {
                         $pollUrl = $p->getUrl();
                    }

                     $activePollsPrepared[] = [
                        'id' => $p->getId(),
                        'title' => $p->getTitle(),
                        'url' => $pollUrl,
                        'active' => ($k === 0),
                        'index' => $k,
                        'fallback' => false
                    ];
                }
            }
        }
    } catch (\Exception $e) {
        // Erreur lors du chargement des sondages
    }
    
    // Fallback si aucun sondage
    if (empty($activePollsPrepared)) {
        try {
            $url = $router->generate('sondage-home');
        } catch (\Exception $e) {
            $url = '#';
        }
        
        $activePollsPrepared[] = [
            'title' => 'Aucun sondage actif',
            'url' => $url,
            'active' => true,
            'index' => 0,
            'fallback' => true
        ];
    }
    
    $widgets['activePolls'] = $activePollsPrepared;
    return $widgets;
}

function sondageHomeUrl($params = ''): string
{
    try {
        return \Core\Router\Router::getInstance()->generate('sondage-home');
    } catch (\Exception $e) {
        return '#';
    }
}

function sondageReadUrl($params = ''): string
{
    try {
        $id = is_object($params) ? $params->getId() : $params;
        return \Core\Router\Router::getInstance()->generate('sondage-read', ['id' => $id]);
    } catch (\Exception $e) {
        return '#';
    }
}

## Hooks are now declared in param/hooks.json

/**
 * Hook: user:notification-preferences
 */
function sondageUserProfileNotificationPreferences($user): string
{
    $notifSondage = $user->attributes['notif_sondage'] ?? true;
    
    return '
    <label class="setting-checkbox">
        <input type="checkbox" data-setting="notif_sondage" ' . ($notifSondage ? 'checked' : '') . '>
        <span class="checkbox-label">Sondages & Votes</span>
    </label>';
}

/**
 * Hook: user:profile-allowed-settings
 */
function sondageUserProfileAllowedSettings(array $allowed): array
{
    $allowed[] = 'notif_sondage';
    return $allowed;
}

/**
 * Hook: poll:get-stats
 */
function sondageGetStats(array $data): array
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('sondage');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return $data;
    }

    try {
        $sondageManager = new \SondageManager();
        $polls = $sondageManager->getItems();
        $totalVotes = 0;
        $activeCount = 0;
        $closedCount = 0;

        foreach ($polls as $poll) {
            $totalVotes += $poll->getTotalVotes();
            if ($poll->getActive()) {
                $activeCount++;
            } else {
                $closedCount++;
            }
        }

        $data['stats'] = [
            'total' => count($polls),
            'active' => $activeCount,
            'closed' => $closedCount,
            'votes' => $totalVotes,
            'average' => count($polls) > 0 ? round($totalVotes / count($polls), 1) : 0
        ];
    } catch (\Exception $e) {
        // Ignorer
    }
    return $data;
}


