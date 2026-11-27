<?php

/**
 * @copyright (C) 2022, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */

defined('VERSION') or define('VERSION', '0.4.3');
defined('COMMON') or define('COMMON', ROOT . 'common' . DS);
defined('DATA') or define('DATA', ROOT . 'data' . DS);
defined('DATA_CORE') or define('DATA_CORE', DATA . 'core' . DS);
defined('DATA_CORE_AUTH') or define('DATA_CORE_AUTH', DATA_CORE . 'auth' . DS);
defined('DATA_CORE_PAGE') or define('DATA_CORE_PAGE', DATA_CORE . 'page' . DS);
defined('DATA_CORE_MEDIA') or define('DATA_CORE_MEDIA', DATA_CORE . 'media' . DS);
defined('DATA_CORE_SETTINGS') or define('DATA_CORE_SETTINGS', DATA_CORE . 'settings' . DS);
defined('DATA_CORE_EXTENSIONS') or define('DATA_CORE_EXTENSIONS', DATA_CORE . 'extensions' . DS);
defined('DATA_CORE_CONTENT') or define('DATA_CORE_CONTENT', DATA_CORE . 'content' . DS);
defined('UPLOAD') or define('UPLOAD', DATA . 'upload' . DS);
defined('DATA_PLUGIN') or define('DATA_PLUGIN', DATA . 'plugin' . DS);
defined('CACHE') or define('CACHE', DATA . 'cache' . DS);
defined('THEMES') or define('THEMES', ROOT . 'theme' . DS);
defined('PLUGINS') or define('PLUGINS', ROOT . 'plugin' . DS);
defined('ADMIN_PATH') or define('ADMIN_PATH', ROOT . 'admin' . DS);
defined('FONTICON') or define('FONTICON', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css');
defined('FANCYCSS') or define('FANCYCSS', 'https://cdnjs.cloudflare.com/ajax/libs/fancyapps-ui/4.0.31/fancybox.min.css');
defined('FANCYJS') or define('FANCYJS', 'https://cdnjs.cloudflare.com/ajax/libs/fancyapps-ui/4.0.31/fancybox.umd.min.js');

$filename = DATA . 'key.php';
if (file_exists($filename)) {
    include $filename;
}