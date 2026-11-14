<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;
use Template\Template;
use Core\Auth\User;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class ContactAdminController extends AdminController {

    public function home() {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('contact', 'admin-contact');

        $selectedUserId = $this->runPlugin->getConfigVal('userMailId');

        Template::addGlobal('contactUsers', User::all());
        Template::addGlobal('contactSelected', $selectedUserId);
        $tpl->set('token', $this->user->token);
        $emails = Util::readJsonFile(DATA_PLUGIN . 'contact/emails.json');
        $tpl->set('emails', is_array($emails) ? implode("\n", $emails) : '');
        
        // Lire showInFooter et topics depuis param.json
        $params = $this->getParams();
        $tpl->set('showInFooter', $params['showInFooter'] ?? false);
        $tpl->set('topics', isset($params['topics']) && is_array($params['topics']) ? implode("\n", $params['topics']) : '');
        
        // Lire les messages
        $messagesFile = DATA_PLUGIN . 'contact/messages.json';
        $messages = [];
        if (file_exists($messagesFile)) {
            $messages = Util::readJsonFile($messagesFile);
        }
        $tpl->set('messages', $messages);
        
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Lit les paramètres depuis param.json
     */
    protected function getParams(): array
    {
        $paramFile = DATA_PLUGIN . 'contact/param.json';
        $params = Util::readJsonFile($paramFile);
        if (!is_array($params)) {
            $params = ['showInFooter' => false];
        }
        return $params;
    }

    /**
     * Sauvegarde les paramètres dans param.json
     */
    protected function saveParamsFile(array $params): bool
    {
        $paramFile = DATA_PLUGIN . 'contact/param.json';
        return Util::writeJsonFile($paramFile, $params);
    }

    public function saveParams() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('contact:admin'), $ctx)) {
            return $this->home();
        }
        if (isset($_POST['label'])) $this->runPlugin->setConfigVal('label', $_POST['label']);
        if (isset($_POST['subtitle'])) $this->runPlugin->setConfigVal('subtitle', $_POST['subtitle']);
        if (isset($_POST['copy'])) $this->runPlugin->setConfigVal('copy', $_POST['copy']);
        if (isset($_POST['acceptation'])) $this->runPlugin->setConfigVal('acceptation', $_POST['acceptation']);
        if (isset($_POST['selectedUser'])) $this->runPlugin->setConfigVal('userMailId', (int) $_POST['selectedUser']);

        // Gestion des contenus (hooks d'éditeur)
        $this->runPlugin->setConfigVal('content1', $this->core->callHook('beforeSaveEditor', $_POST['content1'] ?? ''));
        $this->runPlugin->setConfigVal('content2', $this->core->callHook('beforeSaveEditor', $_POST['content2'] ?? ''));

        // Sauvegarder showInFooter et topics dans param.json
        $params = $this->getParams();
        $params['showInFooter'] = isset($_POST['showInFooter']);
        
        // Traitement des topics (textarea ligne par ligne)
        $topicsRaw = trim($_POST['topics'] ?? '');
        $topics = [];
        if (!empty($topicsRaw)) {
            $lines = explode("\n", $topicsRaw);
            foreach ($lines as $line) {
                $t = trim($line);
                if (!empty($t)) {
                    $topics[] = $t;
                }
            }
        }
        $params['topics'] = $topics;
        
        $this->saveParamsFile($params);

        return $this->savePluginConf();
    }



    public function emptyMails($token) {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('contact:admin'), $ctx)) {
            return $this->home();
        }
        Util::writeJsonFile(DATA_PLUGIN . 'contact/emails.json', []);
        Show::msg(Lang::get('contact.base_deleted'), 'info');
        \Core\Core::getInstance()->getLogger()->log('INFO', Lang::get('contact.base_deleted'));
        return $this->home();
    }

    public function emptyMessages($token) {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('contact:admin'), $ctx)) {
            return $this->home();
        }
        Util::writeJsonFile(DATA_PLUGIN . 'contact/messages.json', []);
        Show::msg('La liste des messages a été vidée.', 'info');
        return $this->home();
    }

    protected function savePluginConf() {
        if ($this->pluginsManager->savePluginConfig($this->runPlugin)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }
        return $this->home();
    }


}