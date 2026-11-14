<?php

defined('ROOT') or exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Routes OAuth publiques
$router->map('GET', '/oauth/facebook[/?]', 'OAuth\Controllers\OAuthPublicController#facebookLogin', 'oauth-facebook');
$router->map('GET', '/oauth/facebook/callback[/?]', 'OAuth\Controllers\OAuthPublicController#facebookCallback', 'oauth-facebook-callback');
$router->map('GET', '/oauth/google[/?]', 'OAuth\Controllers\OAuthPublicController#googleLogin', 'oauth-google');
$router->map('GET', '/oauth/google/callback[/?]', 'OAuth\Controllers\OAuthPublicController#googleCallback', 'oauth-google-callback');

// Alias de rétrocompatibilité pour configurations existantes Facebook/Google Console
$router->map('GET', '/auth/facebook[/?]', 'OAuth\Controllers\OAuthPublicController#facebookLogin', 'auth-facebook-alias');
$router->map('GET', '/auth/facebook/callback[/?]', 'OAuth\Controllers\OAuthPublicController#facebookCallback', 'auth-facebook-callback-alias');
$router->map('GET', '/auth/google[/?]', 'OAuth\Controllers\OAuthPublicController#googleLogin', 'auth-google-alias');
$router->map('GET', '/auth/google/callback[/?]', 'OAuth\Controllers\OAuthPublicController#googleCallback', 'auth-google-callback-alias');

// Password Recovery Alias
$router->map('GET', '/users/password-recovery[/?]', 'OAuth\Controllers\OAuthPublicController#passwordRecovery', 'oauth-password-recovery');

// Theme Preference
$router->map('POST', '/oauth/theme/save[/?]', 'OAuth\Controllers\OAuthPublicController#saveThemePreference', 'oauth-save-preference');

// Routes administration
$router->map('GET', '/admin/oauth[/?]', 'OAuth\Controllers\OAuthAdminController#index', 'oauth-admin-home');
$router->map('POST', '/admin/oauth/save[/?]', 'OAuth\Controllers\OAuthAdminController#save', 'oauth-admin-save');
