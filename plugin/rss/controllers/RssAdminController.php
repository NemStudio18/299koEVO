<?php

namespace Rss\Controllers;

use Core\Controllers\AdminController as BaseAdminController;
use Core\Responses\AdminResponse;
use Rss\Services\RssProviderManager;

defined('ROOT') or exit('Access denied!');

class RssAdminController extends BaseAdminController
{
    public function home()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('rss:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $pluginManager = \Core\Plugin\PluginsManager::getInstance();
        $plugin = $pluginManager->getPlugin('rss');
        $config = ($plugin && method_exists($plugin, 'getConfig')) ? $plugin->getConfig() : [];

        // Récupération dynamique de tous les plugins/modules détectés
        $providers = RssProviderManager::getAvailableProviders();
        foreach ($providers as $slug => &$prov) {
            $prov['enabled'] = !empty($config['enable_' . $slug]);
        }
        unset($prov);

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('rss', 'param');
        $tpl->set('config', $config);
        $tpl->set('activateChecked', !empty($config['activate']));
        $tpl->set('feedTitle', $config['feed_title'] ?? 'Flux d\'actualités');
        $tpl->set('feedDesc', $config['feed_description'] ?? 'Toutes les dernières publications et nouveautés du site.');
        $tpl->set('maxItems', (int)($config['max_items'] ?? 30));
        $tpl->set('providers', $providers);
        $tpl->set('providersCount', count($providers));
        $tpl->set('csrfToken', \Core\Security\Csrf::token());

        $response->addTemplate($tpl);
        return $response;
    }

    public function save()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('rss:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? '')) {
            \Utils\Show::msg('Token CSRF invalide.', 'error');
            $this->core->redirect($this->router->generate('admin-rss-home'));
            return;
        }

        $pluginManager = \Core\Plugin\PluginsManager::getInstance();
        $plugin = $pluginManager->getPlugin('rss');

        $data = [
            'activate' => isset($_POST['activate']) ? 1 : 0,
            'feed_title' => trim($_POST['feed_title'] ?? 'Flux d\'actualités'),
            'feed_description' => trim($_POST['feed_description'] ?? 'Toutes les dernières publications du site.'),
            'max_items' => max(5, min(100, (int)($_POST['max_items'] ?? 30))),
        ];

        // Enregistrement dynamique de chaque provider détecté
        $availableProviders = RssProviderManager::getAvailableProviders();
        foreach ($availableProviders as $slug => $provider) {
            $key = 'enable_' . $slug;
            $data[$key] = isset($_POST[$key]) ? 1 : 0;
        }

        foreach ($data as $k => $v) {
            $plugin->setConfigVal($k, $v);
        }

        $pluginManager->savePluginConfig($plugin);
        \Utils\Show::msg('Configuration des flux RSS mise à jour avec succès.', 'success');
        $this->core->redirect($this->router->generate('admin-rss-home'));
    }
}
