<?php

/**
 * @copyright (C) 2024, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
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

function tinymceInstall() {
}

## Hooks

/**
 * Génère le script et la configuration TinyMCE
 */
function tinymceGetEditorHeadHtml(bool $isAdmin = false): string {
    $user = \Core\Auth\UsersManager::getCurrentUser();
    $token = $user ? ($user->token ?? '') : '';
    $uploadUrl = ($token && IS_ADMIN) ? \Core\Router\Router::getInstance()->generate('filemanager-upload-api', ['token' => $token]) : '';

    $url = \Utils\Util::urlBuild(PLUGINS . 'tinymce/lib/tinymce/tinymce.min.js');
    $baseUrl = dirname($url);

    $imageUploadConfig = "";
    if ($uploadUrl !== '') {
        $imageUploadConfig = "images_upload_url: '" . $uploadUrl . "',\n        automatic_uploads: true,";
    }

    // Mascotte What (Banque d'images réservée au panel d'administration)
    $mascotToolbar = '';
    $mascotImageListConfig = '';
    $mascotSetupJs = '';

    if ($isAdmin) {
        $mascotDir = ROOT . 'images' . DIRECTORY_SEPARATOR . 'mascot' . DIRECTORY_SEPARATOR;
        $mascotWebBase = '/images/mascot/';
        $titlesMap = [
            'what_ensoleille.webp' => '☀️ What Ensoleillé',
            'what_pluvieux.webp' => '🌧️ What Pluvieux',
            'what_froid_neige.webp' => '❄️ What Froid & Neige',
            'what_hystory.webp' => '📜 What Histoire & Patrimoine',
            'what_skate.webp' => '🛹 What Skateur & Glisse',
            'what_halloween.webp' => '🎃 What Halloween',
            'what_empty.webp' => '📭 What Contenu Vide',
            'what_404.webp' => '❓ What Page Non Trouvée (404)',
            'what_404_2.webp' => '❓ What Page Non Trouvée (404 - v2)',
            'what_500.webp' => '⚠️ What Erreur Serveur (500)',
            'what_500_2.webp' => '⚠️ What Erreur Serveur (500 - v2)',
            'what_403.webp' => '🔒 What Accès Interdit (403)'
        ];

        $mascotList = [];
        if (is_dir($mascotDir)) {
            $files = glob($mascotDir . '*.webp');
            if ($files) {
                // Tri pour mettre météo et usage général en premier
                $priority = ['what_ensoleille.webp', 'what_pluvieux.webp', 'what_froid_neige.webp', 'what_hystory.webp', 'what_halloween.webp', 'what_empty.webp'];
                usort($files, function ($a, $b) use ($priority) {
                    $ba = basename($a);
                    $bb = basename($b);
                    $ia = array_search($ba, $priority);
                    $ib = array_search($bb, $priority);
                    if ($ia !== false && $ib !== false) return $ia - $ib;
                    if ($ia !== false) return -1;
                    if ($ib !== false) return 1;
                    return strcmp($ba, $bb);
                });

                foreach ($files as $f) {
                    $base = basename($f);
                    $title = $titlesMap[$base] ?? ('🦸 What ' . ucfirst(str_replace(['what_', '.webp', '_'], ['', '', ' '], $base)));
                    $mascotList[] = [
                        'title' => $title,
                        'value' => $mascotWebBase . $base
                    ];
                }
            }
        }

        if (!empty($mascotList)) {
            $mascotListJson = json_encode($mascotList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $mascotToolbar = ' mascot-what';
            $mascotImageListConfig = "image_list: " . $mascotListJson . ",\n        ";
            $mascotSetupJs = "const mascotItems = " . $mascotListJson . ";\n" .
                "            editor.ui.registry.addMenuButton('mascot-what', {\n" .
                "              text: '🦸 Mascotte What',\n" .
                "              tooltip: 'Insérer la mascotte What',\n" .
                "              fetch: (callback) => {\n" .
                "                const menuItems = mascotItems.map(m => ({\n" .
                "                  type: 'menuitem',\n" .
                "                  text: m.title,\n" .
                "                  onAction: () => {\n" .
                "                    editor.insertContent('<img src=\"' + m.value + '\" alt=\"' + m.title + '\" style=\"max-width: 240px; height: auto; display: inline-block;\" class=\"mascot-what-img\" />');\n" .
                "                  }\n" .
                "                }));\n" .
                "                callback(menuItems);\n" .
                "              }\n" .
                "            });\n";
        }
    }

    $titleChooseIcon = addslashes(\Core\Lang::get('tiny.choose-icon-title'));
    $labelEnterIcon = addslashes(\Core\Lang::get('tiny.enter-icon-code'));
    $btnCancel = addslashes(\Core\Lang::get('cancel'));
    $btnValidate = addslashes(\Core\Lang::get('validate'));
    $successTitle = addslashes(\Core\Lang::get('warning.success'));
    $warningTitle = addslashes(\Core\Lang::get('warning.warning'));
    $errorTitle = addslashes(\Core\Lang::get('warning.error'));
    $infoTitle = addslashes(\Core\Lang::get('warning.info'));
    $locale = \Core\Lang::getLocale();
    $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl') . '/';
    $editorCss = \Utils\Util::urlBuild(PLUGINS . 'tinymce/template/editor.css');
    $fontIcon = FONTICON;

    $html = <<<HTML
<script src="{$url}"></script>
<script>
window.tinymce = window.tinymce || {};
window.tinymce.baseURL = "{$baseUrl}";

const dialogIcon = {
    title: "{$titleChooseIcon}",
    body: {
      type: "panel",
      items: [
        {
          type: "input",
          name: "iconCode",
          placeholder: '<i class="fa-brands fa-font-awesome"></i>',
          label: "{$labelEnterIcon}"
        }
      ]
    },
    buttons: [
      {
        type: "cancel",
        name: "closeButton",
        text: "{$btnCancel}"
      },
      {
        type: "submit",
        name: "submitButton",
        buttonType: "primary",
        text: "{$btnValidate}"
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

function initTinyMceSafe() {
    if (typeof tinymce === 'undefined') {
        setTimeout(initTinyMceSafe, 50);
        return;
    }

    const mceOptions = {
        selector: 'textarea.editor',
        license_key: 'gpl',
        promotion: false,
        branding: false,
        language: '{$locale}',
        toolbar_mode: 'wrap',
        {$imageUploadConfig}
        {$mascotImageListConfig}convert_urls: false,
        document_base_url: '{$siteUrl}',
        browser_spellcheck: true,
        image_advtab: true,
        sandbox_iframes: false,
        extended_valid_elements: 'iframe[src|title|width|height|allowfullscreen|frameborder|allow|referrerpolicy|loading|class|style]',
        plugins: 'advlist anchor autolink autoresize charmap code codesample emoticons fullscreen help image insertdatetime link lists media nonbreaking searchreplace table visualblocks visualchars wordcount',
        toolbar: 'blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | forecolor backcolor removeformat | insertfile image media link{$mascotToolbar} | dialog-add-icon charmap emoticons codesample | fullscreen',
        max_height: 600,
        image_caption: true,
        content_css: [
            '{$editorCss}',
            '{$fontIcon}'
        ],
        setup: (editor) => {
            editor.ui.registry.addButton('dialog-add-icon', {
              icon: 'user',
              tooltip: '{$titleChooseIcon}',
              onAction: () => editor.windowManager.open(dialogIcon)
            });
            {$mascotSetupJs}
        },
        style_formats: [
            { name: 'success', title: '{$successTitle}', block: 'div', classes: [ 'success' ]},
            { name: 'warning', title: '{$warningTitle}', block: 'div', classes: [ 'warning' ]},
            { name: 'error', title: '{$errorTitle}', block: 'div', classes: [ 'error' ]},
            { name: 'info', title: '{$infoTitle}', block: 'div', classes: [ 'info' ]}
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
            { text: 'C++', value: 'cpp' }
        ]
    };

    tinymce.init(mceOptions);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTinyMceSafe);
} else {
    initTinyMceSafe();
}
</script>
HTML;

    return $html;
}

/**
 * Hook endAdminHead
 */
function tinymceAdminHead($param = ''): string {
    return (string)$param . tinymceGetEditorHeadHtml(true);
}

/**
 * Hook Front Head conditionnel (uniquement sur les pages de formulaire/édition)
 */
function tinymceFrontHead($param = ''): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $isEditorPage = (
        str_contains($uri, '/PoiEdit') || 
        str_contains($uri, '/poi-edit') ||
        str_contains($uri, '/poi/edit') ||
        str_contains($uri, '/poi/create') ||
        str_contains($uri, '/poimanager') ||
        str_contains($uri, '/business-dashboard') ||
        str_contains($uri, '/localfeed') ||
        str_contains($uri, '/p-') ||
        str_contains($uri, '/agenda/event') ||
        str_contains($uri, '/agenda/add') ||
        str_contains($uri, '/agenda/edit') ||
        str_contains($uri, '/offers/create') ||
        str_contains($uri, '/offers/edit') ||
        str_contains($uri, '/contribution')
    );

    if (!$isEditorPage) {
        return (string)$param;
    }

    return (string)$param . tinymceGetEditorHeadHtml(false);
}

function tinymceInsertScriptBeforeEditor() {
    echo '<script>' .
    'function processInsertImgInEditor(editorId, imgUrl) {
        var ed = tinyMCE.get(editorId);
        var range = ed.selection.getRng();
        var newNode = ed.getDoc().createElement ( "img" );
        newNode.src=imgUrl;
        range.insertNode(newNode);  
    }
    </script>';
}
