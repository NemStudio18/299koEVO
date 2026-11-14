<?php

/**
 * PellEditor Plugin - WYSIWYG based on Pell library
 * Integrated with File Manager and Galerie
 */
defined('ROOT') OR exit('Access denied!');

if (file_exists(__DIR__ . '/libs/export_bootstrap.php')) {
    require_once __DIR__ . '/libs/export_bootstrap.php';
}

/**
 * Hook d'installation : gère les conflits avec les autres éditeurs
 */
\Core\Core::getInstance()->addHook('frontHead', 'pelleditorAdminHead');
\Core\Core::getInstance()->addHook('adminHead', 'pelleditorAdminHead');
// NOTE: Le hook 'pelleditor:actions-json' et la fonction pelleditorDefaultPlusMenu()
// ont été déplacés dans le plugin DocActions. Voir plugin/DocActions/DocActions.php.

function pelleditorInstall() {
    $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
    
    // Si TinyMCE est actif, on l'éteint pour éviter les doubles éditeurs
    if (\Core\Plugin\PluginsManager::isActivePlugin('tinymce')) {
        $tiny = $pluginsManager->getPlugin('tinymce');
        if ($tiny) {
            $tiny->setConfigVal('activate', 0);
            $pluginsManager->savePluginConfig($tiny);
        }
    }
    
    // Si MDEditor est actif, on l'éteint aussi
    if (\Core\Plugin\PluginsManager::isActivePlugin('mdeditor')) {
        $md = $pluginsManager->getPlugin('mdeditor');
        if ($md) {
            $md->setConfigVal('activate', 0);
            $pluginsManager->savePluginConfig($md);
        }
    }
}

function pelleditorGetActionsJson() {
    $actions = [];
    $actions = \Core\Core::getInstance()->callHook('pelleditor:actions-json', [$actions]);
    $json = json_encode($actions ?: []);
    return ($json === false) ? "[]" : $json;
}

/**
 * Hook admin:head - Injection des assets
 */
function pelleditorAdminHead() {
    $router = \Core\Router\Router::getInstance();
    
    // Pell library assets
    $pellJsUrl = '/plugin/PellEditor/assets/pell/dist/pell.min.js';
    $pellCssUrl = '/plugin/PellEditor/assets/pell/dist/pell.min.css';
    $v = time();
    $pelleditorJsUrl = '/plugin/PellEditor/template/pelleditor.js?v=' . $v;
    $pelleditorCssUrl = '/plugin/PellEditor/template/pelleditor.css?v=' . $v;

    // Export translations and config to JS
    $translations = [
        'bold'           => \Core\Lang::get('pelleditor-bold') ?: 'Gras',
        'italic'         => \Core\Lang::get('pelleditor-italic') ?: 'Italique',
        'underline'      => \Core\Lang::get('pelleditor-underline') ?: 'Souligné',
        'strikethrough'  => \Core\Lang::get('pelleditor-strikethrough') ?: 'Barré',
        'heading1'       => \Core\Lang::get('pelleditor-heading1') ?: 'Titre 1',
        'heading2'       => \Core\Lang::get('pelleditor-heading2') ?: 'Titre 2',
        'heading3'       => \Core\Lang::get('pelleditor-heading3') ?: 'Titre 3',
        'paragraph'      => \Core\Lang::get('pelleditor-paragraph') ?: 'Paragraphe',
        'quote'          => \Core\Lang::get('pelleditor-quote') ?: 'Citation',
        'olist'          => \Core\Lang::get('pelleditor-olist') ?: 'Liste numérotée',
        'ulist'          => \Core\Lang::get('pelleditor-ulist') ?: 'Liste à puces',
        'code'           => \Core\Lang::get('pelleditor-code') ?: 'Code',
        'line'           => \Core\Lang::get('pelleditor-line') ?: 'Ligne',
        'link'           => \Core\Lang::get('pelleditor-link') ?: 'Lien',
        'image'          => \Core\Lang::get('pelleditor-image') ?: 'Image',
        'gallery'        => \Core\Lang::get('pelleditor-gallery') ?: 'Galerie',
        'undo'           => \Core\Lang::get('pelleditor-undo') ?: 'Annuler',
        'redo'           => \Core\Lang::get('pelleditor-redo') ?: 'Rétablir',
        'format'         => \Core\Lang::get('pelleditor-format') ?: 'Format',
        'font'           => \Core\Lang::get('pelleditor-font') ?: 'Police',
        'color'          => \Core\Lang::get('pelleditor-color') ?: 'Couleur',
        'highlight'      => \Core\Lang::get('pelleditor-highlight') ?: 'Surligner',
        'left'           => \Core\Lang::get('pelleditor-align-left') ?: 'Gauche',
        'center'         => \Core\Lang::get('pelleditor-align-center') ?: 'Centrer',
        'right'          => \Core\Lang::get('pelleditor-align-right') ?: 'Droite',
        'justify left'   => \Core\Lang::get('pelleditor-align-left') ?: 'Gauche',
        'justify center' => \Core\Lang::get('pelleditor-align-center') ?: 'Centrer',
        'justify right'  => \Core\Lang::get('pelleditor-align-right') ?: 'Droite',
        'clear'          => \Core\Lang::get('pelleditor-clear') ?: 'Effacer',
        'table'          => \Core\Lang::get('pelleditor-table') ?: 'Tableau',
        'fullscreen'     => \Core\Lang::get('pelleditor-fullscreen') ?: 'Plein écran',
        'print'          => \Core\Lang::get('pelleditor-print') ?: 'Imprimer',
        'plus'           => \Core\Lang::get('pelleditor-plus') ?: 'Plus...'
    ];

    $token = \Core\Auth\CurrentUser::get()->isLogged() ? \Core\Security\Csrf::token() : '';
    
    echo "<script>
        window.PELL_EDITOR_TOKEN = '" . $token . "';
        window.PELL_EDITOR_TRANSLATIONS = " . json_encode($translations) . ";
        window.PELL_EXTRA_ACTIONS = " . pelleditorGetActionsJson() . ";
    </script>" . PHP_EOL;

    echo "    <link rel='stylesheet' href='{$pellCssUrl}' type='text/css'/>\n";
    echo "    <link rel='stylesheet' href='{$pelleditorCssUrl}' type='text/css'/>\n";
    echo "    <script src='{$pellJsUrl}'></script>\n";
    echo "    <script src='{$pelleditorJsUrl}' defer></script>\n";
}

// FIN DU PLUGIN PELLEDITOR
// Le menu Plus... est géré par le plugin DocActions.
