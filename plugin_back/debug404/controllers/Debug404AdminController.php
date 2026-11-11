<?php

namespace Debug404\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Util;

class Debug404AdminController extends AdminController
{
    public function list()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('debug404:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $logFile = DATA_PLUGIN . 'debug404' . DS . 'logs.json';
        $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
        if (!is_array($logs)) {
            $logs = [];
        }

        // Trier par date décroissante
        usort($logs, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('debug404', 'admin-list');
        $tpl->set('logs', $logs);
        
        $response->addTemplate($tpl);
        return $response;
    }
}
