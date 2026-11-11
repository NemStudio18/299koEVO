<?php

use Core\Controllers\AdminController;
use Core\Responses\StringResponse;
use Core\Lang;

class GalerieSelectorController extends AdminController {
    public function selector($token) {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('galerie:admin'), $ctx)) {
            die('Access denied');
        }
        
        $galerie = new \galerie();
        $response = new StringResponse();
        $tpl = $response->createPluginTemplate('galerie', 'admin-selector');
        
        $tpl->set('items', $galerie->getItems());
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }
}
