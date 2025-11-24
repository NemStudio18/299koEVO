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
ob_start();
const ROOT = '.' . DIRECTORY_SEPARATOR;
const DS = DIRECTORY_SEPARATOR;

include_once(ROOT . 'common' . DS . 'common.php');

if (!$core->isInstalled()) {
    header('location:' . ROOT . 'install.php');
    die();
}

define('IS_LOGGED', \Core\Auth\UsersManager::isLogged());
// For futures versions
define('IS_ADMIN', IS_LOGGED);

\Template\Template::addGlobal('IS_LOGGED', IS_LOGGED);
\Template\Template::addGlobal('IS_ADMIN', IS_ADMIN);

$core->executeCallback($router, $runPlugin);