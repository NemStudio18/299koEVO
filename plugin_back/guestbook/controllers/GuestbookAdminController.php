<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;
use Core\Plugin\PluginsManager;
use Guestbook\Entities\GuestbookManager;
use Guestbook\Entities\GuestbookEntry;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class GuestbookAdminController extends AdminController
{
    public function list()
    {
        $guestbookManager = new GuestbookManager();

        $this->runPlugin->setMainTitle(Lang::get('guestbook-admin-list'));
        $this->runPlugin->setTitleTag(Lang::get('guestbook-admin-list'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('guestbook', 'admin-list');

        $entries = [];
        $items = $guestbookManager->getItems();
        
        // DEBUG: Vérifier le chargement
        $dataFile = DATA_PLUGIN . 'guestbook/entries.json';
        $rawJson = file_exists($dataFile) ? file_get_contents($dataFile) : 'FILE NOT FOUND';
        $jsonDecoded = file_exists($dataFile) ? json_decode($rawJson, true) : null;
        $debugInfo = [
            'file_exists' => file_exists($dataFile),
            'file_path' => $dataFile,
            'items_count' => count($items),
            'raw_json' => mb_substr($rawJson, 0, 200),
            'json_decode_result' => is_array($jsonDecoded) ? 'ARRAY with ' . count($jsonDecoded) . ' items' : (is_null($jsonDecoded) ? 'NULL' : 'FALSE'),
            'json_error' => json_last_error() !== JSON_ERROR_NONE ? json_last_error_msg() : 'NO ERROR'
        ];
        
        foreach ($items as $entry) {
            /** @var GuestbookEntry $entry */
            $entryId = $entry->getId();
            $isApproved = $entry->isApproved();
            $entries[] = [
                'id' => $entryId !== null ? intval($entryId) : 0,
                'name' => $entry->getName(),
                'email' => $entry->getEmail(),
                'message' => nl2br(htmlspecialchars($entry->getMessage())),
                'date' => Util::FormatDate($entry->getDate(), 'en', 'fr'),
                'approved' => $isApproved,
                'isPending' => !$isApproved,
                'likesCount' => $entry->getLikesCount(),
                'hasAdminReply' => $entry->hasAdminReply(),
                'adminReply' => $entry->getAdminReply(),
                'adminReplyDate' => $entry->getAdminReplyDate() ? Util::FormatDate($entry->getAdminReplyDate(), 'en', 'fr') : '',
                'adminReplyAuthor' => $entry->getAdminReplyAuthor()
            ];
        }

        $tpl->set('guestbookManager', $guestbookManager);
        $tpl->set('entries', $entries);
        $tpl->set('entriesCount', count($entries));
        $tpl->set('token', $this->user->token);
        
        // Afficher les infos de debug seulement si le mode debug du CMS est actif
        $isDebugMode = $this->core->getConfigVal('debug') == 1 || $this->core->getConfigVal('debug') === true;
        $tpl->set('isDebugMode', $isDebugMode);
        if ($isDebugMode) {
            $tpl->set('debugInfo', $debugInfo);
        }
        $response->addTemplate($tpl);
        return $response;
    }

    public function approve($id, $token)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('guestbook:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        if ($token !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->list();
        }

        $guestbookManager = new GuestbookManager();
        /** @var GuestbookEntry $entry */
        $entry = $guestbookManager->create($id);

        if ($entry) {
            $entry->setApproved('1');
            if ($guestbookManager->saveEntry($entry)) {
                Show::msg(Lang::get('core-item-edited'), 'success');
            } else {
                Show::msg(Lang::get('core-item-not-edited'), 'error');
            }
        } else {
            Show::msg(Lang::get('core-item-not-found'), 'error');
        }

        header('location:' . $this->router->generate('guestbook-admin-list'));
        die();
    }

    public function delete($id, $token)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('guestbook:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        if ($token !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->list();
        }

        $guestbookManager = new GuestbookManager();
        /** @var GuestbookEntry $entry */
        $entry = $guestbookManager->create($id);

        if ($entry && $guestbookManager->delEntry($entry)) {
            Show::msg(Lang::get('core-item-deleted'), 'success');
        } else {
            Show::msg(Lang::get('core-item-not-deleted'), 'error');
        }

        header('location:' . $this->router->generate('guestbook-admin-list'));
        die();
    }

    public function reply($id, $token)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('guestbook:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        if ($token !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->list();
        }

        $guestbookManager = new GuestbookManager();
        /** @var GuestbookEntry $entry */
        $entry = $guestbookManager->create($id);

        if (!$entry) {
            show::msg(lang::get('core-item-not-found'), 'error');
            return $this->list();
        }

        $reply = filter_var($_POST['reply'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!empty($reply)) {
            $entry->setAdminReply($reply);
            $entry->setAdminReplyDate(date('Y-m-d H:i:s'));
            // Utiliser le nom configuré ou l'email ou 'Admin' par défaut
            $adminReplyName = $this->runPlugin->getConfigVal('adminReplyName') ?: ($this->user->email ?? 'Admin');
            $entry->setAdminReplyAuthor($adminReplyName);

            if ($guestbookManager->saveEntry($entry)) {
                Show::msg(Lang::get('guestbook.reply-success'), 'success');
            } else {
                Show::msg(Lang::get('guestbook.reply-error'), 'error');
            }
        } else {
            // Supprimer la réponse si vide
            $entry->setAdminReply('');
            $entry->setAdminReplyDate(null);
            $entry->setAdminReplyAuthor('');
            $guestbookManager->saveEntry($entry);
        }

        header('location:' . $this->router->generate('guestbook-admin-list'));
        die();
    }

    public function params()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('guestbook:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        $this->runPlugin->setMainTitle(Lang::get('guestbook-admin-params'));
        $this->runPlugin->setTitleTag(Lang::get('guestbook-admin-params'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('guestbook', 'param');
        
        // Les valeurs sont récupérées directement dans le template via runPlugin.getConfigVal()
        // Pas besoin de les passer via $tpl->set() car le template utilise {% INCLUDE %} et a accès aux variables globales

        $response->addTemplate($tpl);
        return $response;
    }

    public function saveParams()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('guestbook:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->params();
        }

        if (!isset($_POST['token']) || $_POST['token'] !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->params();
        }

        $pageTitle = filter_var($_POST['pageTitle'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $messagesTabTitle = filter_var($_POST['messagesTabTitle'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        // Utiliser le hook beforeSaveEditor comme les autres plugins pour le contenu TinyMCE
        $messagesTabContent = $this->core->callHook('beforeSaveEditor', $_POST['messagesTabContent'] ?? '');
        $adminReplyName = filter_var($_POST['adminReplyName'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $this->runPlugin->setConfigVal('pageTitle', $pageTitle);
        $this->runPlugin->setConfigVal('messagesTabTitle', $messagesTabTitle);
        $this->runPlugin->setConfigVal('messagesTabContent', $messagesTabContent);
        $this->runPlugin->setConfigVal('adminReplyName', $adminReplyName);
        
        $pluginsManager = PluginsManager::getInstance();
        if ($pluginsManager->savePluginConfig($this->runPlugin)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
            header('location:' . $this->router->generate('guestbook-admin-list'));
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
            header('location:' . $this->router->generate('guestbook-admin-params'));
        }
        die();
    }

    public function deleteReply($id, $token)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('guestbook:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        if ($token !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->list();
        }

        $guestbookManager = new GuestbookManager();
        /** @var GuestbookEntry $entry */
        $entry = $guestbookManager->create($id);

        if (!$entry) {
            show::msg(lang::get('core-item-not-found'), 'error');
            return $this->list();
        }

        // Supprimer la réponse
        $entry->setAdminReply('');
        $entry->setAdminReplyDate(null);
        $entry->setAdminReplyAuthor('');

        if ($guestbookManager->saveEntry($entry)) {
            Show::msg(Lang::get('guestbook.delete-reply-success'), 'success');
        } else {
            Show::msg(Lang::get('guestbook.delete-reply-error'), 'error');
        }

        header('location:' . $this->router->generate('guestbook-admin-list'));
        die();
    }
}
