<?php

namespace SearchUI\Controllers;

use Core\Controllers\AdminController as BaseAdminController;
use Core\Responses\AdminResponse;

defined('ROOT') or exit('Access denied!');

class SearchUIAdminController extends BaseAdminController
{
    public function home()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('search-ui:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
        $plugin = $pluginsManager->getPlugin('SearchUi');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \Core\Logger::getInstance()->debug('SearchUi Admin: POST reçu. enableSimulation=' . ($_POST['enableSimulation'] ?? 'off'));
            $enableSimulation = isset($_POST['enableSimulation']) ? '1' : '0';
            $plugin->setConfigVal('enableSimulation', (string)$enableSimulation);
            \Core\Logger::getInstance()->debug('SearchUi Admin: Sauvegarde config. Valeur=' . $enableSimulation);
            
            if ($pluginsManager->savePluginConfig($plugin)) {
                \Utils\Show::msg('Configuration enregistrée avec succès.', 'success');
            } else {
                \Utils\Show::msg('Erreur lors de l\'enregistrement de la configuration.', 'error');
            }
            return $this->core->redirect($this->router->generate('admin-SearchUi-home'));
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('SearchUi', 'param');
        $tpl->set('enableSimulation', $plugin->getConfigVal('enableSimulation') ?? '1');
        $response->addTemplate($tpl);
        return $response;
    }
}