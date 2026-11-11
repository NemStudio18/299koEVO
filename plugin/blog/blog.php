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

use Blog\Hooks\BlogHooks;

// 1. Autoloader for Blog namespace (maps to src/)
spl_autoload_register(function ($class) {
    if (strpos($class, 'Blog\\') === 0) {
        $path = str_replace('\\', '/', substr($class, 5));
        $file = __DIR__ . '/src/' . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 2. Register Hooks
// Hooks are now registered via param/hooks.json

// 3. Wrappers for legacy Core calls

function blogInstall()
{
    if (class_exists('\Blog\Hooks\BlogHooks')) {
        BlogHooks::install();
    }
}

