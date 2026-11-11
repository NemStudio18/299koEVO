<?php
$router = \Core\Router\Router::getInstance();

// Social Login
$router->map('GET', '/auth/facebook', 'Auth\Controllers\AuthPublicController#facebookLogin', 'auth-facebook');
$router->map('GET', '/auth/facebook/callback', 'Auth\Controllers\AuthPublicController#facebookCallback', 'auth-facebook-callback');
$router->map('GET', '/auth/google', 'Auth\Controllers\AuthPublicController#googleLogin', 'auth-google');
$router->map('GET', '/auth/google/callback', 'Auth\Controllers\AuthPublicController#googleCallback', 'auth-google-callback');

// Password Recovery Alias
$router->map('GET', '/users/password-recovery[/?]', 'Auth\Controllers\AuthPublicController#passwordRecovery', 'auth-password-recovery');

// Theme Preference
$router->map('POST', '/auth/theme/save', 'Auth\Controllers\AuthPublicController#saveThemePreference', 'user-save-preference');

// Admin
$router->map('GET', '/admin/auth', 'Auth\Controllers\AuthAdminController#index', 'auth-admin-home');
$router->map('POST', '/admin/auth/save', 'Auth\Controllers\AuthAdminController#save', 'auth-admin-save');
$router->map('GET', '/admin/auth/users', 'Auth\Controllers\AuthAdminController#users', 'auth-admin-users');
