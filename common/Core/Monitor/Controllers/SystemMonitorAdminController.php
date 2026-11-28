<?php

namespace Core\Monitor\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Monitor\SystemMonitor;
use Core\Security\Csrf;
use Utils\Show;
use Core\Lang;

/**
 * SystemMonitorAdminController - Contrôleur d'administration pour la télémétrie souveraine.
 */
class SystemMonitorAdminController extends AdminController
{
    public function home()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('configmanager', 'monitor');
        $response->setTitle('Monitoring Système & Télémétrie');

        $monitor = SystemMonitor::getInstance();
        $metrics = $monitor->getMetrics();

        $tpl->set('metrics', $metrics);
        $tpl->set('resetUrl', $this->router->generate('monitor-admin-reset'));
        $tpl->set('apiUrl', $this->router->generate('monitor-admin-api'));
        $tpl->set('token', $this->user->token);

        $response->addTemplate($tpl);
        return $response;
    }

    public function reset()
    {
        if (!Csrf::checkToken($_POST['token'] ?? '')) {
            Show::msg('Jeton de sécurité CSRF expiré ou invalide.', 'error');
            $this->core->redirect($this->router->generate('monitor-admin'));
            return;
        }

        $monitor = SystemMonitor::getInstance();
        $monitor->reset();

        Show::msg('Télémétrie et historique des métriques réinitialisés avec succès.', 'success');
        $this->core->redirect($this->router->generate('monitor-admin'));
    }

    public function apiMetrics(): \Core\Responses\ApiResponse
    {
        $resp = new \Core\Responses\ApiResponse();
        $resp->httpCode = 200;
        $resp->status = \Core\Responses\ApiResponse::STATUS_SUCCESS;
        $resp->body = SystemMonitor::getInstance()->getMetrics();
        return $resp;
    }
}

