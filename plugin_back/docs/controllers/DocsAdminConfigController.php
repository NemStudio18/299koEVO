<?php

namespace Docs\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\ApiResponse;
use Core\Lang;

defined('ROOT') or exit('Access denied!');

class DocsAdminConfigController extends AdminController
{

    public function saveConfig()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('docs:admin'), $ctx)) {
            $this->core->error404();
        }
        
        // Récupérer les données JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['error' => 'Invalid JSON data'];
            return $response;
        }
        
        // Configuration générale
        $this->runPlugin->setConfigVal('pluginName', trim($input['pluginName'] ?? ''));
        $this->runPlugin->setConfigVal('label', trim($input['label'] ?? ''));
        $this->runPlugin->setConfigVal('homeText', $this->core->callHook('beforeSaveEditor', htmlspecialchars($input['homeText'] ?? '')));
        $this->runPlugin->setConfigVal('showLastActivity', (isset($input['showLastActivity']) ? 1 : 0));
        
        // Affichage
        $this->runPlugin->setConfigVal('displayTOC', filter_var($input['displayTOC'] ?? 'no', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->runPlugin->setConfigVal('hideContent', (isset($input['hideContent']) ? 1 : 0));
        
        // Fonctionnalités
        $this->runPlugin->setConfigVal('enableVersioning', (isset($input['enableVersioning']) ? 1 : 0));
        $this->runPlugin->setConfigVal('enableInternalLinks', (isset($input['enableInternalLinks']) ? 1 : 0));
        
        $response = new ApiResponse();
        
        if ($this->pluginsManager->savePluginConfig($this->runPlugin)) {
            $response->status = ApiResponse::STATUS_ACCEPTED;
            $response->body = ['message' => Lang::get('core-changes-saved')];
        } else {
            $response->status = 'HTTP/1.1 500 Internal Server Error';
            $response->body = ['error' => Lang::get('core-changes-not-saved')];
        }
        
        return $response;
    }
}