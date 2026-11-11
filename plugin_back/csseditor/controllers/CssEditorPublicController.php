<?php

namespace CssEditor\Controllers;

use Core\Controllers\PublicController;
use Core\Plugin\PluginsManager;

defined('ROOT') or exit('Access denied!');

class CssEditorPublicController extends PublicController {
    
    public function customCss() {
        // Vérifier si le plugin est activé
        $plugin = PluginsManager::getInstance()->getPlugin('csseditor');
        if (!$plugin || $plugin->getConfigVal('enabled') != '1') {
            http_response_code(404);
            exit;
        }
        
        // Chemin vers le fichier CSS custom
        $cssFile = DATA_PLUGIN . 'csseditor/custom.css';
        
        // Vérifier si le fichier existe
        if (!file_exists($cssFile)) {
            http_response_code(404);
            exit;
        }
        
        // Lire le contenu du fichier
        $cssContent = file_get_contents($cssFile);
        if (empty(trim($cssContent))) {
            http_response_code(404);
            exit;
        }
        
        // Définir les headers pour le CSS
        header('Content-Type: text/css; charset=utf-8');
        header('Cache-Control: public, max-age=3600'); // Cache 1 heure
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($cssFile)) . ' GMT');
        header('ETag: "' . md5_file($cssFile) . '"');
        
        // Vérifier si le fichier n'a pas changé (304 Not Modified)
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($ifModifiedSince >= filemtime($cssFile)) {
                http_response_code(304);
                exit;
            }
        }
        
        // Vérifier l'ETag
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $etag = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            if ($etag === md5_file($cssFile)) {
                http_response_code(304);
                exit;
            }
        }
        
        // Afficher le contenu CSS
        echo $cssContent;
        exit;
    }
} 