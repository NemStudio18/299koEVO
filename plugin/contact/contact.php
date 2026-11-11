<?php

use Utils\Util;
use Core\Lang;
use Core\Core;
use Core\Auth\User;

/**
 * @copyright (C) 2022, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No pagesFileect script access allowed');

## Fonction d'installation

function contactInstall() {
    Util::writeJsonFile(DATA_PLUGIN . 'contact/emails.json', []);
    $data = Util::readJsonFile(DATA_PLUGIN . 'contact/config.json');
    Lang::loadLanguageFile(PLUGINS . 'contact/langs/');
    $data['acceptation'] = Lang::get('contact.default-acceptation');
    Util::writeJsonFile(DATA_PLUGIN . 'contact/config.json', $data);
    
    // Initialiser param.json
    $paramFile = DATA_PLUGIN . 'contact/param.json';
    if (!file_exists($paramFile)) {
        Util::writeJsonFile($paramFile, ['showInFooter' => false]);
    }
}

## Hooks

/**
 * Hook pour ajouter le CSS du plugin côté front
 */
function contactEndFrontHead($param = ''): string
{
    $output = (string)$param;
    $output .= '<link rel="stylesheet" href="' . Util::urlBuild(PLUGINS . 'contact/template/public.css') . '">' . "\n";
    return $output;
}

/**
 * Hook pour ajouter le CSS du plugin côté admin
 */
function contactEndAdminHead(): void
{
    echo '<link rel="stylesheet" href="' . Util::urlBuild(PLUGINS . 'contact/template/public.css') . '">';
}

/**
 * Hook to inject contactUrl into templates
 * This ensures the contactUrl is available globally when the Contact plugin is active
 */
function contactInjectTemplateVars(): void
{
    $router = \Core\Router\Router::getInstance();
    try {
        $contactUrl = $router->generate('contact-home');
        \Template\Template::addGlobal('contactUrl', $contactUrl);
    } catch (\Exception $e) {
        // Route doesn't exist, plugin may not be properly configured
        // Silently fail to avoid breaking the site
    }
}

function contactSave($email) {
    $dir = DATA_PLUGIN . 'contact';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $emailsFile = $dir . DS . 'emails.json';
    $data = file_exists($emailsFile) ? Util::readJsonFile($emailsFile) : [];
    if (!is_array($data)) {
        $data = [];
    }
    $data[] = $email;
    Util::writeJsonFile($emailsFile, array_values(array_unique($data)));
}

/**
 * Résout l'utilisateur destinataire des messages de contact (configuré ou premier admin).
 */
function contactResolveRecipientUser(): ?User
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('contact');
    $userMailId = $plugin ? $plugin->getConfigVal('userMailId') : null;
    if ($userMailId) {
        $user = User::findPK($userMailId);
        if ($user instanceof User) {
            return $user;
        }
    }
    try {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        foreach (User::all() as $candidate) {
            if ($evaluator->isAdmin(new \Core\Navigation\ConditionContext($candidate))) {
                return $candidate;
            }
        }
        $all = User::all();
        if (!empty($all) && $all[0] instanceof User) {
            return $all[0];
        }
    } catch (\Throwable $e) {
    }
    return null;
}

function contactSend() {
    global $runPlugin;
    $core = Core::getInstance();
    $host = preg_replace('/[^a-zA-Z0-9.-]/', '', (string) ($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
    $from = '299ko@' . $host;
    $reply = strip_tags(trim((string) ($_POST['email'] ?? '')));
    $name = strip_tags(trim((string) ($_POST['name'] ?? '')));
    $firstName = strip_tags(trim((string) ($_POST['firstname'] ?? '')));
    $msg = strip_tags(trim((string) ($_POST['message'] ?? '')));
    $topic = strip_tags(trim((string) ($_POST['topic'] ?? '')));

    $paramFile = DATA_PLUGIN . 'contact/param.json';
    $params = file_exists($paramFile) ? Util::readJsonFile($paramFile) : [];
    $configuredTopics = (is_array($params) && !empty($params['topics']) && is_array($params['topics'])) ? $params['topics'] : [];
    if ($topic === '') {
        if (!empty($configuredTopics)) {
            return false;
        }
        $topic = 'Message de contact';
    }

    if (!Util::isEmail($reply) || $name === '' || $msg === '') {
        return false;
    }

    contactSave($reply);

    // Save full message to JSON
    $messageData = [
        'id'        => bin2hex(random_bytes(8)),
        'date'      => date('Y-m-d H:i:s'),
        'name'      => $name,
        'firstname' => $firstName,
        'email'     => $reply,
        'topic'     => $topic,
        'message'   => $msg,
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    $messagesFile = DATA_PLUGIN . 'contact/messages.json';
    $messages = [];
    if (file_exists($messagesFile)) {
        $messages = Util::readJsonFile($messagesFile);
        if (!is_array($messages)) {
            $messages = [];
        }
    }
    array_unshift($messages, $messageData);
    if (!Util::writeJsonFile($messagesFile, $messages)) {
        return false;
    }

    // Déclencher le hook de domaine pour notification (Découplé)
    $core->callHook('contact:message-received', $messageData);

    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('contact');
    $user = contactResolveRecipientUser();
    $to = ($user && isset($user->email) && Util::isEmail((string) $user->email)) ? (string) $user->email : '';
    $subject = 'Contact [' . $topic . '] ' . $core->getConfigVal('siteName');
    $senderLabel = trim($name . ' ' . $firstName);
    $mailBody = $msg . "\n\n----------\n\n" . $senderLabel . " (" . $reply . ")";

    $copy = $plugin ? $plugin->getConfigVal('copy') : '';
    if ($copy !== false && $copy !== null && is_string($copy) && Util::isEmail($copy)) {
        @Util::sendEmail($from, $reply, $copy, $subject, $mailBody);
    }
    if ($to !== '') {
        $sent = @Util::sendEmail($from, $reply, $to, $subject, $mailBody);
        if (!$sent) {
            $core->getLogger()->log(\Core\Logger::LEVEL_WARNING, 'Contact: message sauvegardé dans messages.json mais Util::sendEmail() a échoué vers ' . $to);
        }
    }
    return true;
}

/**
 * Hook pour ajouter un lien vers la page contact dans le footer
 */
function contactEndFooter(): void
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('contact');
    if (!$plugin || !$plugin->getConfigVal('activate')) {
        return;
    }

    // Lire showInFooter depuis param.json
    $paramFile = DATA_PLUGIN . 'contact/param.json';
    $params = Util::readJsonFile($paramFile);
    $showInFooter = (is_array($params) && isset($params['showInFooter'])) ? $params['showInFooter'] : false;
    
    if (!$showInFooter) {
        return;
    }
    
    /** @var \Core\Router\Router $router */
    $router = \Core\Router\Router::getInstance();
    
    $label = $plugin->getConfigVal('label');
    if ($label === false || $label === null || empty($label)) {
        $label = 'Contact';
    } else {
        $label = (string)$label;
    }
    
    try {
        $contactUrl = $router->generate('contact-home');
        echo ' - <a href="' . $contactUrl . '">' . htmlspecialchars($label) . '</a>';
    } catch (\Exception $e) {
        // Ignorer si la route n'existe pas
    }
}

/**
 * Listener: contact:message-received
 * Pont entre le domaine (Contact) et l'action (Notifications)
 */
function contactOnMessageReceived(array $data): void
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('contact');
    if (!$plugin) return;

    $recipient = contactResolveRecipientUser();
    $targetUserId = $recipient ? ($recipient->id ?? ($recipient->attributes['id'] ?? 1)) : ((int)$plugin->getConfigVal('userMailId') ?: 1);
    $senderName = trim(($data['firstname'] ?? '') . ' ' . ($data['name'] ?? '')) ?: ($data['email'] ?? 'Visiteur');
    $topic = (string) ($data['topic'] ?? 'Message de contact');

    \Core\Core::getInstance()->callHook('notifications:send', [
        'user_id' => $targetUserId,
        'type' => 'contact.message',
        'source_plugin' => 'contact',
        'data' => [
            'key' => 'contact.message',
            'params' => [$senderName, $topic],
            'fallback_title' => 'Nouveau message de contact de %s (%s)',
            'url' => '/admin/contact',
            'actor' => [
                'name' => $senderName,
                'email' => $data['email'] ?? null
            ],
            'entity' => [
                'type' => 'contact_message',
                'id' => $data['id'] ?? null
            ]
        ],
    ]);
}

// Hooks are now registered in hooks.json