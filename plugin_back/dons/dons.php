<?php

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

require_once PLUGINS . 'dons/entities/Don.php';
require_once PLUGINS . 'dons/entities/DonManager.php';

## Fonction d'installation

function donsInstall() {
    $dataFile = DATA_PLUGIN . 'dons/dons.json';
    $dataDir = dirname($dataFile);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    if (!file_exists($dataFile)) {
        file_put_contents($dataFile, json_encode([]));
    }
}

/**
 * Hook: Injecte le CSS public
 */
function donsEndFrontHead()
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/dons') === false) {
        return '';
    }
    
    $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
    $cssUrl = $siteUrl . '/plugin/dons/template/public.css';
    
    return '<link rel="stylesheet" href="' . $cssUrl . '?v=' . VERSION . '">';
}

/**
 * Hook: Injecte le lien dans la navigation
 */
function donsNavigation($tree)
{
    $tree->addItem(\Core\Navigation\NavigationItem::fromArray([
        'id' => 'plugin.dons.home',
        'parentId' => null,
        'label' => \Core\Lang::get('dons.name'),
        'target' => \Core\Router\Router::getInstance()->generate('dons-home'),
        'position' => 80,
        'source' => 'plugin:dons'
    ]));
}


// Hooks are now registered in hooks.json

// Auto-injection de l'état actif pour le dashboard
if (class_exists('\Core\Core') && class_exists('\Template\Template')) {
    \Template\Template::addGlobal('isDonsActive', true);
}




