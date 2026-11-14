<?php

namespace Highlight\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Auth\AccessPolicyEvaluator;
use Core\Security\Csrf;
use Core\Lang;
use Utils\Show;

defined('ROOT') or exit('No direct script access allowed');

class HighlightAdminController extends AdminController
{
    private function checkAdminAccess(): bool
    {
        if (class_exists(AccessPolicyEvaluator::class)) {
            $evaluator = AccessPolicyEvaluator::fromLoadedPlugins();
            $decision = $evaluator->evaluate($this->user, 'highlight:admin');
            if (!$decision->allowed) {
                Show::msg(Lang::get('core-not-authorized'), 'error');
                $this->core->redirect($this->router->generate('admin-home'));
                return false;
            }
        }
        return true;
    }

    public function home()
    {
        if (!$this->checkAdminAccess()) return null;
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('highlight', 'config');
        $response->addTemplate($tpl);
        return $response;
    }

    public function save()
    {
        if (!$this->checkAdminAccess()) return null;
        if (!$this->user->isAuthorized() || !Csrf::validate($_POST['token'] ?? null)) {
            $this->core->error404();
        }
        $this->runPlugin->setConfigVal('theme', trim((string)($_POST['theme'] ?? 'default')));
        $this->pluginsManager->savePluginConfig($this->runPlugin);

        Show::msg(Lang::get('core-changes-saved'), 'success');
        $this->core->redirect($this->router->generate('highlight-admin'));
    }
}
