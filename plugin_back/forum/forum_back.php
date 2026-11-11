<?php

namespace Forum {

use Core\Plugin\PluginsManager;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
        $dbDir = DATA_PLUGIN . 'forum';
        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0755, true);
        }
    }

    public static function endFrontHead($param = ''): string
    {
        $plugin = PluginsManager::getInstance()->getPlugin('forum');
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return $param;
        }
        
        $baseUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');
        $output = $param;
        
        // Ajouter le CSS
        $output .= '<link rel="stylesheet" href="' . \Utils\Util::urlBuild(PLUGINS . 'forum/template/public.css') . '" type="text/css"/>' . "\n";
        
        // NOTE: TinyMCE est chargé par le plugin tinymce via tinymceFrontHead
        // Le plugin tinymce détecte automatiquement les pages du forum et charge TinyMCE
        // Pas besoin de charger TinyMCE ici pour éviter les doublons
        
        return $output;
    }
}

}

namespace {

    function forumInstall(): void
    {
        \Forum\Hooks::install();
    }

    function forumBeforeRunPlugin(): void
    {
        // Charger les entités
        foreach (glob(PLUGINS . 'forum/entities/' . '*.php') as $file) {
            require_once $file;
        }
        
        // Charger les services
        if (is_dir(PLUGINS . 'forum/services')) {
            foreach (glob(PLUGINS . 'forum/services/' . '*.php') as $file) {
                require_once $file;
            }
        }
    }

    function forumEndFrontHead($param = '')
    {
        $logger = \Core\Logger::getInstance();
        $logger->log(\Core\Logger::LEVEL_DEBUG, "forumEndFrontHead - Fonction globale appelée, param: " . (is_string($param) ? strlen($param) . " chars" : gettype($param)));
        $result = \Forum\Hooks::endFrontHead($param);
        $logger->log(\Core\Logger::LEVEL_DEBUG, "forumEndFrontHead - Retour de " . strlen($result) . " chars");
        return $result;
    }

    function forumEndFrontBody(): void
    {
        // Désactivé : les notifications sont maintenant unifiées dans le menu avatar
        // via Show::userAvatarMenu() et getNotificationsData()
        return;
    }

    // Charger les entités et services dès l'inclusion du fichier
    // Cela garantit qu'elles sont disponibles avant l'instanciation des contrôleurs
    if (is_dir(PLUGINS . 'forum/entities')) {
        foreach (glob(PLUGINS . 'forum/entities/' . '*.php') as $file) {
            require_once $file;
        }
    }
    if (is_dir(PLUGINS . 'forum/services')) {
        foreach (glob(PLUGINS . 'forum/services/' . '*.php') as $file) {
            require_once $file;
        }
    }
}


