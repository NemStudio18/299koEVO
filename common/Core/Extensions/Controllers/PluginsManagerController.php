<?php

namespace Core\Extensions\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Lang;
use Utils\Show;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class PluginsManagerController extends AdminController
{

    public function list() {
        $priority = array(
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9,
        );
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('pluginsmanager', 'list');
        $response->setTitle(Lang::get('pluginsmanager.name'));

        $plugins = $this->pluginsManager->getPlugins();
        $pluginsToDisplay = [];
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'maintenance' => 0,
        ];
        foreach ($plugins as $plugin) {
            if ($plugin->isRequired()) {
                continue;
            }
            $pluginsToDisplay[] = $plugin;
            $stats['total']++;
            $isActive = (bool) $plugin->getConfigVal('activate');
            if ($isActive) {
                $stats['active']++;
                if (!$plugin->isInstalled()) {
                    $stats['maintenance']++;
                }
            } else {
                $stats['inactive']++;
            }
        }
        $legacyPending = $this->core->extensions()->getPendingLegacyPlugins();
        $tpl->set('plugins', $pluginsToDisplay);
        $tpl->set('pluginStats', $stats);
        $tpl->set('legacyPluginsPending', $legacyPending);
        $tpl->set('legacyPluginsCount', count($legacyPending));
        $tpl->set('legacyPluginsList', implode(', ', $legacyPending));

        $tpl->set('priority', $priority);
        $tpl->set('token', $this->user->token);

        $response->addTemplate($tpl);
        return $response;
    }

    public function save() {
        if (!$this->user->isAuthorized()) {
            return $this->list();
        }
        $error = false;
        foreach ($this->pluginsManager->getPlugins() as $k => $v) {
            if ($v->isRequired())
                continue;
            if (isset($_POST['activate'][$v->getName()])) {
                if (!$v->isInstalled())
                    $this->pluginsManager->installPlugin($v->getName(), true);
                else
                    $v->setConfigVal('activate', 1);
            } else
                $v->setConfigVal('activate', 0);
            if ($v->isInstalled()) {
                $v->setConfigVal('priority', intval($_POST['priority'][$v->getName()]));
                if (!$this->pluginsManager->savePluginConfig($v)) {
                    $error = true;
                }
            }
        }
        if ($error) {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        } else {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        }
        $this->core->redirect($this->router->generate('pluginsmanager-list'));
    }

    public function maintenance($plugin, $token) {
        if (!$this->user->isAuthorized()) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }
        
        // Vérifier le token (le token peut être encodé dans l'URL)
        $decodedToken = urldecode($token);
        if ($decodedToken !== $this->user->token && $token !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->list();
        }
        
        $this->core->getLogger()->log(\Core\Logger::LEVEL_INFO, "Maintenance requested for plugin: $plugin");
        
        $result = $this->pluginsManager->installPlugin($plugin, true);
        if ($result) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
            $this->core->getLogger()->log(\Core\Logger::LEVEL_INFO, "Plugin $plugin successfully installed via maintenance");
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
            $this->core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "Plugin $plugin installation failed via maintenance");
        }
        
        $this->core->redirect($this->router->generate('pluginsmanager-list'));
    }
}