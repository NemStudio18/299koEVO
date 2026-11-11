<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin Bug Tracker
 */

// Chargement des classes avant exécution
function bugTrackerBeforeRunPlugin(): void
{
    foreach (glob(PLUGINS . 'BugTracker/controllers/' . '*.php') as $file) {
        require_once $file;
    }
    foreach (glob(PLUGINS . 'BugTracker/services/' . '*.php') as $file) {
        require_once $file;
    }
    foreach (glob(PLUGINS . 'BugTracker/entities/' . '*.php') as $file) {
        require_once $file;
    }
    
    // Hooks are now registered in hooks.json
}

// Installation : prépare le dossier data
function bugTrackerInstall(): void
{
    $dataDir = DATA_PLUGIN . 'BugTracker' . DS;
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    
    // Créer le dossier pour les images
    $imagesDir = $dataDir . 'images' . DS;
    if (!is_dir($imagesDir)) {
        @mkdir($imagesDir, 0755, true);
    }
    
    // Dossiers créés. Le fichier JSON sera géré par JsonActiveRecord.
}

// Hook pour ajouter le CSS dans le head (hook de filtre)
function bugTrackerEndFrontHead($param = ''): string
{
    $baseUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');
    $cssFile = PLUGINS . 'BugTracker/template/BugTracker.css';
    $output = (string)$param;
    if (file_exists($cssFile)) {
        $output .= '<link rel="stylesheet" href="' . $baseUrl . '/plugin/BugTracker/template/BugTracker.css" media="all">' . "\n";
    }
    return $output;
}

/**
 * Hook: user:avatar-menu
 * Ajoute un lien vers le Bug Tracker dans le menu avatar
 */
function bugTrackerUserAvatarMenu(array $data): array
{
    $items = $data['items'] ?? [];
    $user = \Core\Auth\UsersManager::getCurrentUser();
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('BugTracker');
    
    if ($plugin && $plugin->isInstalled() && $plugin->getConfigVal('activate') && $user) {
        $router = \Core\Router\Router::getInstance();
        try {
            $url = $router->generate('BugTracker-home');
        } catch (\Exception $e) {
            $url = '/BugTracker';
        }

        $items[] = [
            'label' => 'Signaler un bug',
            'url' => $url,
            'icon' => 'fa-solid fa-bug',
            'order' => 39
        ];
    }
    $data['items'] = $items;
    return $data;
}

// bugTrackerNavigationBuild supprimé — la navigation est désormais gérée par Page::addToNavigation() (legacy pages.json)

