<?php

namespace MdEditor {

use Core\Plugin\PluginsManager;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
        if (PluginsManager::isActivePlugin('tinymce')) {
            $pluginsManager = PluginsManager::getInstance();
            $tiny = $pluginsManager->getPlugin('tinymce');
            if ($tiny) {
                $tiny->setConfigVal('activate', 0);
                $pluginsManager->savePluginConfig($tiny);
            }
        }
    }

    public static function adminAssets(): void
    {
        echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css'>
        <script src='https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js'></script>
        <script>
            var editors = document.getElementsByClassName('editor');
            if (!!editors){
                for (let editor of editors) {
                    const easyMDE = new EasyMDE({
                        toolbar: ['bold','italic','heading','|',
                        'code','quote','unordered-list','ordered-list','table','horizontal-rule','|',
                        'link','image','|',
                        'preview','side-by-side','fullscreen','|',
                        'guide'],
                        spellChecker: false,
                        inputStyle: 'contenteditable',
                        nativeSpellcheck: true,
                        element: editor
                    });
                }
            }
        </script>";
    }

    public static function beforeEdit(string $content = ''): string
    {
        require_once PLUGINS . 'mdeditor/Markdownify.php';
        $converter = new \Markdownify\ConverterExtra();
        return $converter->parseString($content);
    }

    public static function beforeSave(string $content): string
    {
        require_once PLUGINS . 'mdeditor/Parsedown.php';
        $converter = new \ParsedownExtra();
        return $converter->text($content);
    }
}

}

namespace {

    function mdeditorInstall(): void
    {
        \MdEditor\Hooks::install();
    }

}
