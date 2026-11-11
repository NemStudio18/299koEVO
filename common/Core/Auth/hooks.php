<?php

namespace Core\Auth;

use Core\Router\Router;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

// Note: Les styles des formulaires d'authentification sont maintenant dans le thème (theme/EVO/css/theme.css)
// Ce fichier peut être utilisé pour d'autres hooks liés à l'authentification si nécessaire

/**
 * Hooks d'URL fournis par le core : utilisés par les layouts de thèmes
 * ({% HOOK.user:login-url('') %}, etc.). Ils ne dépendent donc plus d'un plugin.
 */
function authCoreUrl(string $routeName): string
{
    return Router::getInstance()->generate($routeName);
}

function authCoreLoginUrl($params = ''): string { return authCoreUrl('login'); }
function authCoreRegisterUrl($params = ''): string { return authCoreUrl('register'); }
function authCoreLogoutUrl($params = ''): string { return authCoreUrl('logout'); }
function authCoreProfileUrl($params = ''): string { return authCoreUrl('profile'); }
function authCoreAdminUrl($params = ''): string { return authCoreUrl('admin'); }
