<?php

namespace Agenda\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Core\Security\Csrf;
use Utils\Show;
use Core\Lang;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class AgendaAdminController extends AdminController
{
    private $eventManager;

    public function __construct()
    {
        parent::__construct();
        $this->eventManager = new \Agenda\Entities\EventManager();
    }

    public function home()
    {
        $this->core->getLogger()->debug('AgendaAdminController::home called');
        return $this->list();
    }

    public function list()
    {
        $this->core->getLogger()->debug('AgendaAdminController::list called');
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $this->core->getLogger()->debug('AgendaAdminController::list - stage 1');
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('agenda', 'admin-list');
        
        $events = $this->eventManager->getAll(true);
        $tpl->set('events', $events);
        $tpl->set('token', $this->user->token);
        $tpl->set('config', $this->runPlugin->getConfig());
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function saveConfig()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            $this->core->redirect($this->router->generate('admin-agenda-home'));
            return;
        }

        $this->runPlugin->setConfigVal('items_per_page', (int)($_POST['items_per_page'] ?? 20));
        $this->runPlugin->setConfigVal('notify_admin_email', trim($_POST['notify_admin_email'] ?? ''));
        $this->runPlugin->setConfigVal('date_format', trim($_POST['date_format'] ?? 'd/m/Y'));
        
        if (\Core\Plugin\PluginsManager::getInstance()->savePluginConfig($this->runPlugin)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }

        $this->core->redirect($this->router->generate('admin-agenda-events'));
        return null;
    }

    public function add()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('agenda', 'admin-edit');
        $tpl->set('formEvent', $this->formatEventData());
        $tpl->set('isEdit', false);
        $tpl->set('token', $this->user->token);
        $tpl->set('actionUrl', $this->router->generate('admin-agenda-events-add-send'));
        $response->addTemplate($tpl);
        return $response;
    }

    public function addSend()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            $this->core->redirect($this->router->generate('admin-agenda-events'));
        }
        
        if (!Csrf::validate($_POST['_csrf'] ?? '')) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            $this->core->redirect($this->router->generate('admin-agenda-events'));
        }
        
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'start_date' => str_replace('T', ' ', $_POST['start_date'] ?? ''),
            'end_date' => str_replace('T', ' ', $_POST['end_date'] ?? ($_POST['start_date'] ?? '')),
            'location' => $_POST['location'] ?? '',
            'color' => $_POST['color'] ?? '#3498db',
            'latitude' => (isset($_POST['latitude']) && $_POST['latitude'] !== '' && $_POST['latitude'] !== 'latitude') ? (float)$_POST['latitude'] : null,
            'longitude' => (isset($_POST['longitude']) && $_POST['longitude'] !== '' && $_POST['longitude'] !== 'longitude') ? (float)$_POST['longitude'] : null,
        ];
        
        if (empty($data['title']) || empty($data['start_date'])) {
            Show::msg(Lang::get('agenda.invalid-data'), 'error');
            $this->core->redirect($this->router->generate('admin-agenda-events-add'));
        }
        
        $event = $this->eventManager->create($data);
        if ($event) {
            Show::msg(Lang::get('agenda.event-created'), 'success');
            $this->core->redirect($this->router->generate('admin-agenda-events'));
        } else {
            Show::msg(Lang::get('agenda.event-create-error'), 'error');
            $this->core->redirect($this->router->generate('admin-agenda-events-add'));
        }
        
        return null;
    }

    public function edit()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        $response = new AdminResponse();
        $id = $this->router->getParam('id');
        
        $event = $this->eventManager->getById($id);
        if (!$event) {
            return $this->core->redirect('/admin/agenda/events');
        }
        
        $tpl = $response->createPluginTemplate('agenda', 'admin-edit');
        $tpl->set('formEvent', $this->formatEventData($event));
        $tpl->set('isEdit', true);
        $tpl->set('token', $this->user->token);
        $tpl->set('actionUrl', $this->router->generate('admin-agenda-events-edit-send', ['id' => $id]));
        $response->addTemplate($tpl);
        return $response;
    }

    public function editSend()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            $this->core->redirect($this->router->generate('admin-agenda-events'));
        }
        
        if (!Csrf::validate($_POST['_csrf'] ?? '')) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            $this->core->redirect($this->router->generate('admin-agenda-events'));
        }
        
        $id = $this->router->getParam('id') ?? $_POST['id'] ?? null;
        
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'start_date' => str_replace('T', ' ', $_POST['start_date'] ?? ''),
            'end_date' => str_replace('T', ' ', $_POST['end_date'] ?? ($_POST['start_date'] ?? '')),
            'location' => $_POST['location'] ?? '',
            'color' => $_POST['color'] ?? '#3498db',
            'latitude' => (isset($_POST['latitude']) && $_POST['latitude'] !== '' && $_POST['latitude'] !== 'latitude') ? (float)$_POST['latitude'] : null,
            'longitude' => (isset($_POST['longitude']) && $_POST['longitude'] !== '' && $_POST['longitude'] !== 'longitude') ? (float)$_POST['longitude'] : null,
        ];
        
        if ($this->eventManager->update($id, $data)) {
            Show::msg(Lang::get('agenda.event-updated'), 'success');
            $this->core->redirect($this->router->generate('admin-agenda-events'));
        } else {
            Show::msg(Lang::get('agenda.event-update-error'), 'error');
            $this->core->redirect($this->router->generate('admin-agenda-events-edit', ['id' => $id]));
        }
        
        return null;
    }

    public function delete()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            $this->core->redirect($this->router->generate('admin-agenda-events'));
        }
        
        $id = $this->router->getParam('id');
        
        if ($this->eventManager->delete($id)) {
            Show::msg(Lang::get('agenda.event-deleted'), 'success');
        } else {
            Show::msg(Lang::get('agenda.event-delete-error'), 'error');
        }
        
        $this->core->redirect($this->router->generate('admin-agenda-events'));
        return null;
    }

    public function approve()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            $this->core->redirect($this->router->generate('admin-agenda-events'));
            return;
        }

        $id = $this->router->getParam('id');
        $event = $this->eventManager->getById($id);
        
        if ($event) {
            $data = $event->toArray();
            $data['status'] = 'approved';
            if ($this->eventManager->update($id, $data)) {
                Show::msg('Événement approuvé avec succès.', 'success');
            } else {
                Show::msg('Erreur lors de l\'approbation de l\'événement.', 'error');
            }
        } else {
            Show::msg('Événement introuvable.', 'error');
        }
        
        $this->core->redirect($this->router->generate('admin-agenda-events'));
        return null;
    }

    private function formatEventData(?\Agenda\Entities\Event $event = null): array
    {
        $start = $event ? $event->getStartDate() : '';
        if ($start && strlen($start) === 10) $start .= 'T00:00';
        else $start = str_replace(' ', 'T', $start);

        $end = $event ? $event->getEndDate() : '';
        if ($end && strlen($end) === 10) $end .= 'T00:00';
        else $end = str_replace(' ', 'T', $end);

        return [
            'id' => $event ? $event->getId() : '',
            'title' => $event ? $event->getTitle() : '',
            'description' => $event ? $event->getDescription() : '',
            'start_date' => $start,
            'end_date' => $end,
            'location' => $event ? $event->getLocation() : '',
            'color' => $event ? $event->getColor() : '#3498db',
            'latitude' => $event ? $event->getLatitude() : null,
            'longitude' => $event ? $event->getLongitude() : null,
        ];
    }
}

