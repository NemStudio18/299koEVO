<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;
use Core\Core;
use Core\Plugin\PluginsManager;
use Core\Security\Csrf;
use Newsletter\Entities\NewsletterManager;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class NewsletterAdminController extends AdminController
{
    public function list()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('newsletter:admin'), $ctx)) {
            \Utils\Show::msg(Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $newsletterManager = new NewsletterManager();
        $subscribers = $newsletterManager->getSubscribers();

        $this->runPlugin->setMainTitle(Lang::get('newsletter-admin.list-title'));
        $this->runPlugin->setTitleTag(Lang::get('newsletter-admin.list-title'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('newsletter', 'admin-list');

        $subscribersData = [];
        foreach ($subscribers as $sub) {
            $subscribersData[] = [
                'id' => $sub->getId(),
                'email' => $sub->getEmail(),
                'date' => Util::FormatDate($sub->getDate(), 'en', 'fr'),
                'active' => $sub->isActive(),
                'token' => $sub->getToken()
            ];
        }

        $tpl->set('subscribers', $subscribersData);
        $tpl->set('subscribersCount', count($subscribersData));
        $tpl->set('activeCount', count(array_filter($subscribersData, function($s) { return $s['active']; })));
        $tpl->set('token', Csrf::token());
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function delete($id)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('newsletter:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        if (!isset($_POST['token']) || $_POST['token'] !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->list();
        }

        $newsletterManager = new NewsletterManager();
        if ($newsletterManager->deleteSubscriber($id)) {
            Show::msg(Lang::get('newsletter-admin.delete-success'), 'success');
        } else {
            Show::msg(Lang::get('newsletter-admin.delete-error'), 'error');
        }

        header('location:' . $this->router->generate('newsletter-admin-list'));
        die();
    }

    public function send()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('newsletter:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        if (!isset($_POST['token']) || $_POST['token'] !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->list();
        }

        $subject = filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $content = $_POST['content'] ?? '';
        
        if (empty($subject) || empty($content)) {
            Show::msg(Lang::get('newsletter-admin.send-error-empty'), 'error');
            return $this->list();
        }

        $newsletterManager = new NewsletterManager();
        $subscribers = $newsletterManager->getActiveSubscribers();
        
        $core = Core::getInstance();
        $siteEmail = $core->getConfigVal('siteEmail');
        $siteName = $core->getConfigVal('siteName');
        
        $sent = 0;
        foreach ($subscribers as $sub) {
            $to = $sub->getEmail();
            $headers = "From: $siteName <$siteEmail>\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            if (mail($to, $subject, $content, $headers)) {
                $sent++;
            }
        }

        if ($sent > 0) {
            Show::msg(Lang::get('newsletter-admin.send-success', $sent), 'success');
        } else {
            Show::msg(Lang::get('newsletter-admin.send-error'), 'error');
        }

        header('location:' . $this->router->generate('newsletter-admin-list'));
        die();
    }

    public function params()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('newsletter:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        $this->runPlugin->setMainTitle(Lang::get('newsletter-admin.params-title'));
        $this->runPlugin->setTitleTag(Lang::get('newsletter-admin.params-title'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('newsletter', 'param');
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function saveParams()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('newsletter:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->params();
        }

        if (!isset($_POST['token']) || $_POST['token'] !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->params();
        }

        $pageTitle = filter_var($_POST['pageTitle'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $subscriptionMessage = filter_var($_POST['subscriptionMessage'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $unsubscriptionMessage = filter_var($_POST['unsubscriptionMessage'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $alreadySubscribedMessage = filter_var($_POST['alreadySubscribedMessage'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $this->runPlugin->setConfigVal('pageTitle', $pageTitle);
        $this->runPlugin->setConfigVal('subscriptionMessage', $subscriptionMessage);
        $this->runPlugin->setConfigVal('unsubscriptionMessage', $unsubscriptionMessage);
        $this->runPlugin->setConfigVal('alreadySubscribedMessage', $alreadySubscribedMessage);
        
        $pluginsManager = PluginsManager::getInstance();
        if ($pluginsManager->savePluginConfig($this->runPlugin)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
            header('location:' . $this->router->generate('newsletter-admin-list'));
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
            header('location:' . $this->router->generate('newsletter-admin-params'));
        }
        die();
    }
}

