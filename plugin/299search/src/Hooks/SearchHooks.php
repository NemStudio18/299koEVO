<?php

namespace Search299\Hooks;

use Core\Core;
use Template\Template;

defined('ROOT') or exit('Access denied!');

class SearchHooks
{
    /**
     * Injection des styles CSS de la loupe pilule
     */
    public static function onEndFrontHead(string $param = ''): string
    {
        $siteUrl = Core::getInstance()->getConfigVal('siteUrl') ?: '';
        $cssFile = ROOT . 'plugin/299search/assets/css/search.css';
        $v = file_exists($cssFile) ? filemtime($cssFile) : '1.0';
        $cssTag = '<link rel="stylesheet" href="' . rtrim($siteUrl, '/') . '/plugin/299search/assets/css/search.css?v=' . $v . '" type="text/css"/>';
        return $param . $cssTag;
    }

    /**
     * Rendu de la loupe pilule dans la barre de navigation
     */
    public static function onNavbar(string $param = ''): string
    {
        $tplPath = ROOT . 'plugin/299search/template/navbar_search.tpl';
        if (!file_exists($tplPath)) {
            return '';
        }

        $tpl = new Template($tplPath);
        $q = isset($_GET['q']) ? htmlspecialchars(trim($_GET['q'])) : '';
        $tpl->set('q', $q);

        return $tpl->output();
    }
}
