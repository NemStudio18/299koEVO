<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin POI Editor
 * Gestion de l'édition des fiches POI avec permissions
 */

// Chargement des classes avant exécution
function poiEditBeforeRunPlugin(): void
{
    foreach (glob(PLUGINS . 'PoiEdit/controllers/' . '*.php') as $file) {
        require_once $file;
    }
    foreach (glob(PLUGINS . 'PoiEdit/services/' . '*.php') as $file) {
        require_once $file;
    }
    
    // Enregistrer les hooks après le chargement du Core
    // Note: navigation:build est enregistré via hooks.json, pas besoin de le réenregistrer ici
        // Hooks are now declared in param/hooks.json
}

// Hook pour enregistrer les items de navigation via le nouveau système
function poiEditNavigationBuild($tree): void
{
    // [MANUAL CONTROL]
    // We disable automatic registration to allow full manual control via the Admin UI.
    // Use Admin > Pages > Add Link/Page to create navigation items for PoiEdit.
    return;

    /*
    try {
        $navigationService = \Core\Navigation\NavigationService::getInstance();
        $user = \Core\Auth\UsersManager::getCurrentUser();
    ...
    } catch (\Exception $e) {
        error_log('poiEditNavigationBuild error: ' . $e->getMessage());
    }
    */
}

// Hook pour ajouter le CSS public du plugin et TinyMCE si disponible
function poiEditEndFrontHead($param = ''): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiEdit');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return (string)$param;
    }

    $baseUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');
    $output = (string)$param;
    
    // Ajouter le CSS
    $cssFile = PLUGINS . 'PoiEdit/template/public.css';
    if (file_exists($cssFile)) {
        $output .= '<link rel="stylesheet" href="' . $baseUrl . '/plugin/PoiEdit/template/public.css" media="all">' . "\n";
    }
    
    // Charger TinyMCE si disponible et si on est sur une page d'édition POI
    $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
    if ($pluginsManager && $pluginsManager->isActivePlugin('tinymce')) {
        // Vérifier si on est sur une page d'édition (contribute, edit)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        // Détecter les routes : /poi-edit/contribute, /poi-edit/[type]/[id], /poi-edit/edit/, /poi-edit/start/
        $isEditPage = (
            strpos($requestUri, '/poi-edit/contribute') !== false ||
            strpos($requestUri, '/poi-edit/edit/') !== false ||
            strpos($requestUri, '/poi-edit/start/') !== false ||
            preg_match('#/poi-edit/[^/]+/[^/]+#', $requestUri) // Route /poi-edit/[type]/[id]
        );
        
        if ($isEditPage) {
            // Charger TinyMCE pour les pages d'édition publiques
            $user = \Core\Auth\UsersManager::getCurrentUser();
            $token = $user && isset($user->token) ? $user->token : '';
            $uploadUrl = \Core\Router\Router::getInstance()->generate('filemanager-upload-api', ['token' => $token]);
            $url = \Utils\Util::urlBuild(PLUGINS . 'tinymce/lib/tinymce/tinymce.min.js');
            $locale = \Core\Lang::getLocale();
            $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
            
            $options = "language: '{$locale}',
                toolbar_mode: 'wrap',
                images_upload_url: '{$uploadUrl}',
                automatic_uploads: true,
                convert_urls: false,
                document_base_url : '{$siteUrl}/',
                browser_spellcheck: true,
                image_advtab: true,
                plugins: 'advlist anchor autolink autoresize charmap code codesample emoticons fullscreen help image insertdatetime link lists media nonbreaking searchreplace table visualblocks visualchars wordcount',
                toolbar: 'blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | forecolor backcolor removeformat | insertfile image media link | dialog-add-icon charmap emoticons codesample | fullscreen',
                max_height: 600,
                image_caption: true,
                content_css: [
                    '" . \Utils\Util::urlBuild(PLUGINS . 'tinymce/template/editor.css') . "',
                    '" . FONTICON . "',
                ],
                setup: (editor) => {
                    editor.ui.registry.addButton('dialog-add-icon', {
                      icon: 'user',
                      tooltip: '" . addslashes(\Core\Lang::get('tiny.choose-icon-title')) . "',
                      onAction: () => editor.windowManager.open(dialogIcon)
                    })
                  },
                style_formats: [
                    { name: 'success', title: '" . addslashes(\Core\Lang::get('warning.success')) . "', block: 'div', classes: [ 'success' ]},
                    { name: 'warning', title: '" . addslashes(\Core\Lang::get('warning.warning')) . "', block: 'div', classes: [ 'warning' ]},
                    { name: 'error', title: '" . addslashes(\Core\Lang::get('warning.error')) . "', block: 'div', classes: [ 'error' ]},
                    { name: 'info', title: '" . addslashes(\Core\Lang::get('warning.info')) . "', block: 'div', classes: [ 'info' ]} 
                ],
                style_formats_merge: true,
                codesample_languages: [
                    { text: 'HTML', value: 'HTML' },
                    { text: 'XML', value: 'XML' },
                    { text: 'Tpl', value: 'tpl' },
                    { text: 'JavaScript', value: 'javascript' },
                    { text: 'CSS', value: 'css' },
                    { text: 'PHP', value: 'php' },
                    { text: 'Ruby', value: 'ruby' },
                    { text: 'Python', value: 'python' },
                    { text: 'Java', value: 'java' },
                    { text: 'C', value: 'c' },
                    { text: 'C#', value: 'csharp' },
                    { text: 'C++', value: 'cpp' },
                ]";
            
            $output .= "<script src='{$url}'></script>\n";
            $output .= "<script>
const dialogIcon = {
    title: '" . addslashes(\Core\Lang::get('tiny.choose-icon-title')) . "',
    body: {
      type: 'panel',
      items: [
        {
          type: 'input',
          name: 'iconCode',
          placeholder: '<i class=\\'fa-brands fa-font-awesome\\'></i>',
          label: '" . addslashes(\Core\Lang::get('tiny.enter-icon-code')) . "'
        }
      ]
    },
    buttons: [
      {
        type: 'cancel',
        name: 'closeButton',
        text: '" . addslashes(\Core\Lang::get('cancel')) . "'
      },
      {
        type: 'submit',
        name: 'submitButton',
        buttonType: 'primary',
        text : '" . addslashes(\Core\Lang::get('validate')) . "'
      }
    ],
    onSubmit: (api) => {
      const data = api.getData();
      let insertText = data.iconCode.replace('<i class=', '<span class=');
      insertText = insertText.replace('></i>', '>&nbsp;</span> ');
      tinymce.activeEditor.execCommand('InsertHTML', false, insertText);
      api.close();
    }
};
document.addEventListener('DOMContentLoaded', function () {
    tinymce.init({
        selector: 'textarea.editor',
        license_key: 'gpl',
        promotion: false,
        branding: false,
        " . $options . "
    });
});
</script>\n";
        }
    }
    
    return $output;
}

// Installation : prépare le dossier data et un config basique
function poiEditInstall(): void
{
    $dataDir = DATA_PLUGIN . 'PoiEdit' . DS;
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    $configFile = $dataDir . 'config.json';
    if (!file_exists($configFile)) {
        @file_put_contents($configFile, json_encode([
            'activate' => true,
            'priority' => 5,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    // Initialiser param.json si nécessaire
    $paramFile = $dataDir . 'param.json';
    if (!file_exists($paramFile)) {
        \Utils\Util::writeJsonFile($paramFile, ['helpUrl' => '']);
    }
}

/**
 * Listeners pour les décisions d'édition (Decoupled bridge)
 */
function poiEditOnSuggestionDecided(array $data): void
{
    $suggestion = $data['suggestion'] ?? null;
    $decision = $data['decision'] ?? '';
    if (!$suggestion || !$decision) return;

    \Core\Core::getInstance()->callHook('notifications:send', [
        'user_id' => (int)$suggestion['user_id'],
        'type' => 'poi-edit.suggestion_' . $decision,
        'source_plugin' => 'poi-edit',
        'data' => [
            'key' => 'poi-edit.suggestion_' . $decision,
            'params' => [
                'field' => $suggestion['field_name'],
                'poi_name' => $suggestion['poi_name'] ?? 'POI #' . $suggestion['poi_id']
            ],
            'url' => '/poi-edit/my-contributions',
            'entity' => [
                'type' => 'poi',
                'id' => $suggestion['poi_id']
            ]
        ]
    ]);
}

function poiEditOnProposalDecided(array $data): void
{
    $proposal = $data['proposal'] ?? null;
    $status = $data['status'] ?? ($proposal['status'] ?? '');
    if (!$proposal || !$status) return;

    \Core\Core::getInstance()->callHook('notifications:send', [
        'user_id' => (int)$proposal['user_id'],
        'type' => 'poi-edit.proposal_' . $status,
        'source_plugin' => 'poi-edit',
        'data' => [
            'key' => 'poi-edit.proposal_' . $status,
            'params' => [
                'name' => $proposal['name']
            ],
            'url' => '/poi-edit/my-contributions',
            'entity' => [
                'type' => 'proposal',
                'id' => $proposal['id']
            ]
        ]
    ]);
}

// poi-edit:suggestion-decided is now in hooks.json
// Closures cannot be in hooks.json, so they stay here:
if (class_exists('\Core\Core')) {
    $core = \Core\Core::getInstance();
    $core->addHook('poi-edit:proposal_approved', function($d) { 
        $d['status'] = 'approved'; 
        poiEditOnProposalDecided($d); 
    });
    $core->addHook('poi-edit:proposal_rejected', function($d) { 
        $d['status'] = 'rejected'; 
        poiEditOnProposalDecided($d); 
    });
}

/**
 * Hook user:avatar-menu SUPPRIMÉ
 * La gestion se fait désormais via poiEditNavigationBuild avec navContext = 'avatar'
 */

/**
 * Hook user:avatar-menu
 * Ajoute "Contribuer" au menu de l'utilisateur quand il est connecté
 */
function poiEditUserAvatarMenu(array $data): array
{
    $items = $data['items'] ?? [];
    $router = \Core\Router\Router::getInstance();
    
    try {
        $contributionsUrl = $router->generate('poi-edit-my-contributions');
    } catch (\Exception $e) {
        $contributionsUrl = '/poi-edit/my-contributions';
    }
    
    $items[] = [
        'label' => 'Mes contributions',
        'url' => $contributionsUrl,
        'icon' => 'fa-solid fa-list-check',
        'order' => 38
    ];
    
    $data['items'] = $items;
    return $data;
}

/**
 * Hook poi:card-actions
 * Affiche le bouton "Modifier" sur la fiche POI si le score est insuffisant
 */
    // Les boutons d'action sont désormais gérés de manière centralisée
    // par le template SearchUi et ConversionOrchestrator.
    return;

/**
 * Hook poi:detail-actions
 * Affiche les boutons d'action sur la page de détail pour PoiEdit
 */
function poiEditPoiDetailActions($poi): void
{
    // Les boutons de détail sont désormais gérés par le template detail.tpl
    // (bloc admin/manager) et par le ConversionOrchestrator (côté visiteurs).
    return;
}

