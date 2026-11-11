<?php

namespace PublicStats\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Plugin\Plugin;
use Core\Plugin\PluginsManager;

defined('ROOT') or exit('Access denied!');

class PublicStatsAdminController extends AdminController
{
    public function index()
    {
        $response = new AdminResponse();
        $response->setTitle('<i class="fa-solid fa-gears"></i> Configuration - Public Stats');

        $plugin = PluginsManager::getInstance()->getPlugin('publicstats');
        $config = $plugin->getConfig();

        $tpl = $response->createPluginTemplate('publicstats', 'admin-index');
        $tpl->set('config', $config);
        $tpl->set('saveUrl', $this->router->generate('publicstats-admin-save'));
        $tpl->set('publicUrl', $this->router->generate('publicstats-index'));
        $tpl->set('csrfToken', \Core\Security\Csrf::token());

        $response->addTemplate($tpl);
        return $response;
    }

    public function save()
    {
        if (!\Core\Security\Csrf::validate($_POST['csrf_token'])) {
            \Utils\Show::msg('Token CSRF invalide', 'error');
            $this->core->redirect($this->router->generate('publicstats-admin'));
        }

        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('publicstats');
        $config = $plugin->getConfig();

        $config['show_users'] = isset($_POST['show_users']);
        $config['show_polls'] = isset($_POST['show_polls']);
        $config['show_forum'] = isset($_POST['show_forum']);
        $config['show_districts'] = isset($_POST['show_districts']);
        $config['show_general'] = isset($_POST['show_general']);

        $configFile = DATA_PLUGIN . 'publicstats/config.json';
        if (\Utils\Util::writeJsonFile($configFile, $config)) {
            \Utils\Show::msg('Configuration enregistrée', 'success');
        } else {
            \Utils\Show::msg('Erreur lors de l\'enregistrement de la configuration', 'error');
        }

        $this->core->redirect($this->router->generate('publicstats-admin'));
    }
}
