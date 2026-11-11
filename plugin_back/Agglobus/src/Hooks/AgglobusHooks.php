<?php

namespace Agglobus\Hooks;

use Core\Core;

defined('ROOT') or exit('Access denied!');

class AgglobusHooks
{
    public static function onEndFrontHead($param = ''): string
    {
        $siteUrl = Core::getInstance()->getConfigVal('siteUrl');
        $cssTag = '<link rel="stylesheet" href="' . $siteUrl . '/plugin/Agglobus/assets/css/public.css?v=2" type="text/css"/>';
        return (string)$param . "\n" . $cssTag;
    }
}
