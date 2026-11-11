<?php

namespace Highlight\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Lang;
use Utils\Show;

defined('ROOT') or exit('No direct script access allowed');

class HighlightAdminController extends AdminController
{
    public function home() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('highlight:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('highlight', 'config');
        $response->addTemplate($tpl);
        return $response;
    }

    public function save() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('highlight:admin'), $ctx)) {
            return $this->home();
        }
        $this->runPlugin->setConfigVal('theme', trim($_POST['theme']));
        $this->pluginsManager->savePluginConfig($this->runPlugin);

        Show::msg(Lang::get('core-changes-saved'), 'success');
        $this->core->redirect($this->router->generate('highlight-admin'));
    }
}