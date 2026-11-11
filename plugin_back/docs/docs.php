<?php

/**
 * @copyright (C) 2024, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * @author Maxime Blanc <maximeblanc@flexcb.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

use Docs\Entities\WikiPageManager;

require_once PLUGINS . 'docs/entities/WikiPage.php';
require_once PLUGINS . 'docs/entities/WikiPageManager.php';
require_once PLUGINS . 'docs/entities/WikiCategoriesManager.php';
require_once PLUGINS . 'docs/entities/WikiCategory.php';
require_once PLUGINS . 'docs/entities/WikiPageHistory.php';
require_once PLUGINS . 'docs/entities/WikiHistoryManager.php';
require_once PLUGINS . 'docs/entities/WikiActivityManager.php';

/**
 * Fonction d'installation du plugin Wiki
 * 
 * @return void
 */
function docsInstall() {
    // Installation du plugin Docs
}

/**
 * Hook pour ajouter des éléments dans le head de la page publique
 * 
 * @return string Code HTML à ajouter dans le head
 */
function docsEndFrontHead() {
    $core = \Core\Core::getInstance();
    $output = '<script src="' . $core->getConfigVal('siteUrl') . '/plugin/docs/template/public.js"></script>' . "\n";
    
    return $output;
}

/**
 * Hook pour ajouter des éléments dans le head de la page d'administration
 * 
 * @return string Code HTML à ajouter dans le head admin
 */
function docsEndAdminHead() {
    $core = \Core\Core::getInstance();
    $output = '<link rel="stylesheet" href="' . $core->getConfigVal('siteUrl') . '/plugin/docs/template/admin.css" media="all">' . "\n";
    $output .= '<script src="' . $core->getConfigVal('siteUrl') . '/plugin/docs/template/admin.js"></script>' . "\n";
    
    return $output;
}

/**
 * Shortcode pour créer des liens vers des pages Wiki
 * 
 * @param array $attributes Attributs du shortcode ['id' => int, 'name' => string]
 * @return string Code HTML du lien
 */
function docsLinkShortcode(array $attributes): string {
    $wikiPageManager = new WikiPageManager();
    $wikiPage = $wikiPageManager->create((int) $attributes['id']);
    if (!$wikiPage) {
        return '';
    }
    if (!isset($attributes['name'])) {
        $attributes['name'] = $wikiPage->getName();
    }
    return '<a href="' . \Core\Router\Router::getInstance()->generate('docs-read', ['id' => $wikiPage->getId(), 'name' => $wikiPage->getSlug()]) . '">' . $attributes['name'] . '</a>';
}

/**
 * Hook exécuté avant le lancement du plugin
 * 
 * @return void
 */
function docsBeforeRunPlugin() {
    \Content\ContentParser::addShortcode('docsLink', 'docsLinkShortcode');
    
    foreach (glob(PLUGINS . 'docs/controllers/' . "*.php") as $file) {
        include_once $file;
    }
}




## Code relatif au plugin

