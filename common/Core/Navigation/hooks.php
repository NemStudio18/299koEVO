<?php

/**
 * Hooks pour le système de navigation
 * 
 * Permet aux plugins d'injecter leurs items de navigation
 */
defined('ROOT') or exit('Access denied!');

/**
 * Exemple d'utilisation pour un plugin :
 * 
 * Dans le fichier du plugin (ex: plugin/monplugin/monplugin.php) :
 * 
 * $core = Core::getInstance();
 * $core->addHook('navigation:build', function($tree) {
 *     $navigationService = \Core\Navigation\NavigationService::getInstance();
 *     $navigationService->register('plugin.monplugin.main', [
 *         'label' => 'Mon Plugin',
 *         'target' => '/monplugin',
 *         'position' => 50,
 *         'source' => 'plugin:monplugin',
 *         'visibility' => ['public' => true]
 *     ]);
 * });
 * 
 * Ou avec sous-menu :
 * 
 * $navigationService->register('plugin.monplugin.main', [
 *     'label' => 'Mon Plugin',
 *     'target' => '/monplugin',
 *     'position' => 50,
 *     'source' => 'plugin:monplugin',
 *     'visibility' => ['public' => true]
 * ]);
 * 
 * $navigationService->register('plugin.monplugin.sub1', [
 *     'label' => 'Sous-menu 1',
 *     'target' => '/monplugin/sub1',
 *     'parent' => 'plugin.monplugin.main',
 *     'position' => 10,
 *     'source' => 'plugin:monplugin',
 *     'visibility' => ['public' => true]
 * ]);
 */


