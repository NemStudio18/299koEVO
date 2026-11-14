<?php

/**
 * @copyright (C) 2022, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

## Fonction d'installation

function csseditorInstall() {
    // Créer le dossier de sauvegarde
    if (!is_dir(DATA_PLUGIN . 'csseditor/backups')) {
        mkdir(DATA_PLUGIN . 'csseditor/backups', 0755, true);
    }
}

## Hooks

function csseditorEndFrontHead() {
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('csseditor');
    
    // Vérifier si le plugin est activé
    if ($plugin->getConfigVal('enabled') != '1') {
        return;
    }
    
    // Vérifier si le fichier CSS custom existe
    $customCssFile = DATA_PLUGIN . 'csseditor/custom.css';
    if (!file_exists($customCssFile)) {
        return;
    }
    
    // Vérifier que le fichier n'est pas vide
    $cssContent = file_get_contents($customCssFile);
    if (empty(trim($cssContent))) {
        return;
    }
    
    // Générer l'URL via le contrôleur public
    $router = \Core\Router\Router::getInstance();
    $cssUrl = $router->generate('csseditor-public-custom-css');
    
    // Injecter le lien CSS en dernier (pour surcharger le CSS du thème)
    // Ajouter un timestamp pour éviter le cache
    echo '<link rel="stylesheet" href="' . $cssUrl . '?v=' . filemtime($customCssFile) . '" />' . "\n";
} 