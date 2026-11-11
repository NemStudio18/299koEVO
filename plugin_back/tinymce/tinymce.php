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

function tinymceAdminHead() {
    $user = \Core\Auth\UsersManager::getCurrentUser();
    $token = $user ? ($user->token ?? '') : '';
    $uploadUrl = ($token && IS_ADMIN) ? \Core\Router\Router::getInstance()->generate('filemanager-upload-api', ['token' => $token]) : '';

    $url = \Utils\Util::urlBuild(PLUGINS . 'tinymce/lib/tinymce/tinymce.min.js');

    $imageUploadConfig = "";
    if ($uploadUrl !== '') {
        $imageUploadConfig = "images_upload_url: '" . $uploadUrl . "',
        automatic_uploads: true,";
    }

    $options = "language: '". \Core\Lang::getLocale(). "',
        toolbar_mode: 'wrap',
        " . $imageUploadConfig . "
        convert_urls: false,
        document_base_url : '" . \Core\Core::getInstance()->getConfigVal('siteUrl') . "/',
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
              tooltip: '". addslashes(\Core\Lang::get('tiny.choose-icon-title')) . "',
              onAction: () => editor.windowManager.open(dialogIcon)
            })
          },
        style_formats: [
            { name: 'success', title: '".addslashes(\Core\Lang::get('warning.success')). "', block: 'div', classes: [ 'success' ]},
            { name: 'warning', title: '".addslashes(\Core\Lang::get('warning.warning')). "', block: 'div', classes: [ 'warning' ]},
            { name: 'error', title: '".addslashes(\Core\Lang::get('warning.error')). "', block: 'div', classes: [ 'error' ]},
            { name: 'info', title: '". addslashes(\Core\Lang::get('warning.info')). "', block: 'div', classes: [ 'info' ]} 
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

    echo "<script src='" . $url . "'></script>
<script>
const dialogIcon =  {
    title: '". addslashes(\Core\Lang::get('tiny.choose-icon-title')) . "',
    body: {
      type: 'panel',
      items: [
        {
          type: 'input',
          name: 'iconCode',
          placeholder: '<i class=\'fa-brands fa-font-awesome\'></i>',
          label: '". addslashes(\Core\Lang::get('tiny.enter-icon-code')) . "'
        }
      ]
    },
    buttons: [
      {
        type: 'cancel',
        name: 'closeButton',
        text: '". addslashes(\Core\Lang::get('cancel')) ."'
      },
      {
        type: 'submit',
        name: 'submitButton',
        buttonType: 'primary',
        text : '". addslashes(\Core\Lang::get('validate')) ."'
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
            selector: 'textarea.editor',license_key: 'gpl', promotion: false, branding: false, " . $options . "
        });

        function mceAdd(item){
            tinyMCE.execCommand('mceAddEditor', false, { id: item.id, options: {" . $options . "}})
        }

        function mceEnd(item){
            tinyMCE.execCommand('mceRemoveEditor', false, { id: item.id});
        }

        Fancybox.bind('[data-src=\'#param_panel\']', {
            trapFocus: false,
            placeFocusBack: false,
            on: {
                'ready' : (fancybox, eventName) => {
                    document.querySelectorAll('#param_panel textarea.editor').forEach(function (item, index) {
                        mceAdd(item);
                    });
                },
                'init' : (fancybox, eventName) => {
                    document.querySelectorAll('#param_panel textarea.editor').forEach(function (item, index) {
                        mceEnd(item);
                    });
                }
            }
        });
    });
</script>";
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
