<?php

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Utils\Show;
use Core\Lang;
use Core\Auth\UsersManager;
use Utils\Util;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class ContactController extends PublicController
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->runPlugin) {
            $this->runPlugin = $this->pluginsManager->getPlugin('contact');
        }
    }

    public function home()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        $isLogged = $evaluator->canAccess(new \Core\Navigation\GenericResource('contact:logged'), $ctx);
        
        $antispamField = '';
        if (!$isLogged) {
            $antispamData = $this->core->callHook('antispam:get-captcha', ['field_html' => '']);
            $antispamField = $antispamData['field_html'] ?? '';
        }

        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-envelope"></i> ' . ($this->runPlugin->getConfigVal('label') ?: 'Contact'));
        
        $tpl = $response->createPluginTemplate('contact', 'contact');

        

        $current = $isLogged ? UsersManager::getCurrentUser() : null;
        $defaultName = $current ? (trim((string) ($current->name ?? '')) ?: $current->username()) : '';
        $defaultFirstName = $current ? trim((string) ($current->firstname ?? '')) : '';
        $defaultEmail = $current ? (string) ($current->email ?? '') : '';

        $tpl->set('name', $_POST['name'] ?? $defaultName);
        $tpl->set('firstname', $_POST['firstname'] ?? $defaultFirstName);
        $tpl->set('email', $_POST['email'] ?? $defaultEmail);
        $incomingMessage = $_POST['message'] ?? ($_GET['message'] ?? '');
        $incomingTopic = $_POST['topic'] ?? ($_GET['topic'] ?? ($_GET['subject'] ?? ''));
        if ($incomingTopic !== '' && !empty($incomingMessage) && strpos($incomingMessage, $incomingTopic) === false) {
            $incomingMessage = "Objet : " . htmlspecialchars($incomingTopic) . "\n\n" . $incomingMessage;
        } elseif ($incomingTopic !== '' && empty($incomingMessage)) {
            $incomingMessage = "Objet : " . htmlspecialchars($incomingTopic) . "\n\n";
        }
        $tpl->set('message', $incomingMessage);
        $tpl->set('selectedTopic', $incomingTopic);
        $tpl->set('acceptation', (trim($this->runPlugin->getConfigVal('acceptation')) != '') ? true : false);
        $tpl->set('antispamField', $antispamField);
        $tpl->set('antispam', !empty($antispamField));
        $tpl->set('isLogged', $isLogged);

        // Pass topics
        $paramFile = DATA_PLUGIN . 'contact/param.json';
        $params = Util::readJsonFile($paramFile);
        $topics = isset($params['topics']) && is_array($params['topics']) ? $params['topics'] : [];
        $tpl->set('topics', $topics);

        $tpl->set('sendUrl', $this->router->generate('contact-send'));
        $response->addTemplate($tpl);
        return $response;
    }

    public function send()
    {
        $sendError = false;
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        $isLogged = $evaluator->canAccess(new \Core\Navigation\GenericResource('contact:logged'), $ctx);
        // quelques contrôles et temps mort volontaire avant le send...
        sleep(2);
        if (!$isLogged) {
            $antispamResult = $this->core->callHook('antispam:validate', ['is_valid' => true]);
            if (!$antispamResult['is_valid']) {
                Show::msg(Lang::get('antispam.invalid-captcha'), 'error');
                $sendError = true;
            }
        }

        // Validation stricte de la case d'acceptation
        if (trim($this->runPlugin->getConfigVal('acceptation')) != '' && !isset($_POST['acceptation']) && !$isLogged) {
            Show::msg("Veuillez cocher la case d'acceptation.", 'error');
            $sendError = true;
        }
        if (!$sendError) {
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            if (($_POST['_name'] ?? '') == '' && ($referer === '' || strchr($referer, 'contact') !== false)) {
                // Si l'utilisateur est connecté, on injecte ses infos manuellement pour contactSend()
                if ($isLogged) {
                    $currentUser = UsersManager::getCurrentUser();
                    $_POST['name'] = trim((string) ($currentUser->name ?? '')) ?: $currentUser->username();
                    $_POST['firstname'] = trim((string) ($currentUser->firstname ?? ''));
                    $_POST['email'] = (string) ($currentUser->email ?? '');
                }

                if (contactSend()) {
                    Show::msg(Lang::get('contact.msg-sent'), 'success');
                    $this->core->redirect($this->router->generate('contact-home'));
                    return null;
                } else {
                    Show::msg(Lang::get('contact.fiels-error'), 'error');
                    $sendError = true;
                }
            } else {
                Show::msg(Lang::get('contact.fiels-error'), 'error');
                $sendError = true;
            }
        }
        return $this->home();
    }
}