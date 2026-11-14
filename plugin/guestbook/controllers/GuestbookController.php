<?php

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;
use Core\Core;
use Utils\VoteProtection;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class GuestbookController extends PublicController
{
    public function home()
    {
        $antispamData = $this->core->callHook('antispam:get-captcha', ['field_html' => '']);
        $antispamField = $antispamData['field_html'] ?? '';
        $guestbookManager = new GuestbookManager();
        $approvedEntries = $guestbookManager->getApprovedItems();

        // Utiliser le pageTitle configuré ou le nom par défaut
        $pageTitle = $this->runPlugin->getConfigVal('pageTitle');
        if (empty($pageTitle)) {
            $pageTitle = Lang::get('guestbook.name');
        }
        
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-book"></i> ' . $pageTitle);

        $tpl = $response->createPluginTemplate('guestbook', 'list');

        $fingerprint = VoteProtection::getFingerprint();
        $entries = [];
        foreach ($approvedEntries as $entry) {
            $entries[] = [
                'id' => $entry->getId(),
                'name' => $entry->getName(),
                'email' => $entry->getEmail(),
                'message' => nl2br(htmlspecialchars($entry->getMessage())),
                'date' => Util::FormatDate($entry->getDate(), 'en', 'fr'),
                'likesCount' => $entry->getLikesCount(),
                'hasLiked' => $entry->hasLiked($fingerprint),
                'likeUrl' => $this->router->generate('guestbook-like', ['id' => $entry->getId()]),
                'hasAdminReply' => $entry->hasAdminReply(),
                'adminReply' => $entry->hasAdminReply() ? nl2br(htmlspecialchars($entry->getAdminReply())) : '',
                'adminReplyDate' => $entry->hasAdminReply() ? Util::FormatDate($entry->getAdminReplyDate(), 'en', 'fr') : '',
                'adminReplyAuthor' => $entry->getAdminReplyAuthor()
            ];
        }

        $messagesTabTitle = $this->runPlugin->getConfigVal('messagesTabTitle') ?: '';
        $messagesTabContent = $this->runPlugin->getConfigVal('messagesTabContent') ?: '';
        // Le contenu HTML est déjà sécurisé dans saveParams, on le passe tel quel
        $tpl->set('messagesTabTitle', $messagesTabTitle);
        $tpl->set('messagesTabContent', $messagesTabContent);
        
        $tpl->set('entries', $entries);
        $tpl->set('guestbookManager', $guestbookManager);
        $tpl->set('addUrl', $this->router->generate('guestbook-add'));
        $tpl->set('requireEmail', $this->runPlugin->getConfigVal('requireEmail') == '1');
        $tpl->set('maxLength', intval($this->runPlugin->getConfigVal('maxMessageLength')));
        $tpl->set('antispamField', $antispamField);
        $response->addTemplate($tpl);
        return $response;
    }

    public function add()
    {
        $guestbookManager = new GuestbookManager();
        
        // Vérification antispam
        $antispamResult = $this->core->callHook('antispam:validate', ['is_valid' => true]);
        if (!$antispamResult['is_valid']) {
            Show::msg(Lang::get('antispam.invalid-captcha'), 'error');
            return $this->home();
        }

        // Vérification du champ honeypot
        if (isset($_POST['_name']) && $_POST['_name'] !== '') {
            Show::msg(Lang::get('guestbook.error'), 'error');
            return $this->home();
        }

        // Vérifier l'acceptation RGPD
        if (!VoteProtection::hasAcceptedRGPD($_POST)) {
            Show::msg(Lang::get('guestbook.rgpd-required'), 'error');
            return $this->home();
        }

        // Récupération des données
        $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Validation
        if (empty($name) || empty($message)) {
            Show::msg(Lang::get('guestbook.error'), 'error');
            return $this->home();
        }

        // Vérifier email si requis
        if ($this->runPlugin->getConfigVal('requireEmail') == '1' && !Util::isEmail($email)) {
            Show::msg(Lang::get('guestbook.error'), 'error');
            return $this->home();
        }

        // Vérifier longueur du message
        $maxLength = intval($this->runPlugin->getConfigVal('maxMessageLength'));
        if ($maxLength > 0 && strlen($message) > $maxLength) {
            Show::msg(Lang::get('guestbook.error'), 'error');
            return $this->home();
        }

        // Vérifier la limite d'envois par fingerprint (RGPD compliant)
        $maxEntriesPerFingerprint = intval($this->runPlugin->getConfigVal('maxEntriesPerFingerprint'));
        if ($maxEntriesPerFingerprint > 0) {
            $fingerprint = VoteProtection::getFingerprint();
            $existingEntries = $guestbookManager->getItems();
            $count = 0;
            foreach ($existingEntries as $existingEntry) {
                if ($existingEntry->getFingerprint() === $fingerprint) {
                    $count++;
                }
            }
            if ($count >= $maxEntriesPerFingerprint) {
                $limitMsg = str_replace('%d', $maxEntriesPerFingerprint, Lang::get('guestbook.limit-reached'));
                Show::msg($limitMsg, 'error');
                return $this->home();
            }
        }

        // Créer l'entrée
        $entry = new GuestbookEntry();
        $entry->setName($name);
        $entry->setEmail($email);
        $entry->setMessage($message);
        $entry->setFingerprint(VoteProtection::getFingerprint()); // Hash au lieu de l'IP en clair

        // Vérifier si modération requise
        $requireModeration = $this->runPlugin->getConfigVal('requireModeration') == '1';
        if ($requireModeration) {
            $entry->setApproved('0');
        } else {
            $entry->setApproved('1');
        }

        // Sauvegarder
        if ($guestbookManager->saveEntry($entry)) {
            // Succès - redirection pour éviter le rechargement du formulaire avec F5
            if ($requireModeration) {
                Show::msg(Lang::get('guestbook.moderated'), 'success');
            } else {
                Show::msg(Lang::get('guestbook.success'), 'success');
            }
            // Redirection POST/redirect/GET pour éviter le message de renvoi du formulaire
            Core::getInstance()->redirect($this->router->generate('guestbook-home'));
        } else {
            Show::msg(Lang::get('guestbook.error'), 'error');
            return $this->home();
        }
    }

    public function like($id)
    {
        $guestbookManager = new GuestbookManager();
        $entry = $guestbookManager->create($id);

        if (!$entry || !$entry->isApproved()) {
            Show::msg(Lang::get('guestbook.error'), 'error');
            return $this->home();
        }

        $fingerprint = VoteProtection::getFingerprint();
        
        if ($entry->hasLiked($fingerprint)) {
            // Retirer le like
            $entry->removeLike($fingerprint);
        } else {
            // Ajouter le like
            $entry->addLike($fingerprint);
        }

        if ($guestbookManager->saveEntry($entry)) {
            // Redirection avec message de succès silencieux
            header('location:' . $this->router->generate('guestbook-home'));
            die();
        } else {
            Show::msg(Lang::get('guestbook.error'), 'error');
            return $this->home();
        }
    }
}
