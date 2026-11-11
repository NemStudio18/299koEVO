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
    $data = Util::readJsonFile(DATA_PLUGIN . 'contact/emails.json');
    $data[] = $email;
    Util::writeJsonFile(DATA_PLUGIN . 'contact/emails.json', array_unique($data));
}

function contactSend() {
    global $runPlugin;
    $core = Core::getInstance();
    $from = '299ko@' . $_SERVER['SERVER_NAME'];
    $reply = strip_tags(trim($_POST['email']));
    $name = strip_tags(trim($_POST['name']));
    $firstName = strip_tags(trim($_POST['firstname']));
    $msg = strip_tags(trim($_POST['message']));
    $topic = strip_tags(trim($_POST['topic'] ?? ''));
    if (!Util::isEmail($reply) || $name == '' || $firstName == '' || $msg == '' || $topic == '')
        return false;
    
    contactSave($reply);
    
    // Save full message to JSON
    $messageData = [
        'id' => uniqid(),
        'date' => date('Y-m-d H:i:s'),
        'name' => $name,
        'firstname' => $firstName,
        'email' => $reply,
        'topic' => $topic,
        'message' => $msg,
        'ip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $messagesFile = DATA_PLUGIN . 'contact/messages.json';
    $messages = [];
    if (file_exists($messagesFile)) {
        $messages = Util::readJsonFile($messagesFile);
        if (!is_array($messages)) $messages = [];
    }
    // Prepend new message
    array_unshift($messages, $messageData);
    Util::writeJsonFile($messagesFile, $messages);

    // Déclencher le hook de domaine pour notification (Découplé)
    $core->callHook('contact:message-received', $messageData);

    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('contact');
    if (!$plugin) return false;

    $userMailId = $plugin->getConfigVal('userMailId');
    if (!$userMailId) {
        return false;
    }
    /** @var User|null $user */
    $user = User::findPK($userMailId);
    if (!$user || !isset($user->email)) {
        return false;
    }
    // @phpstan-ignore-next-line - $user vérifié non-null ci-dessus
    $to = $user->email;
    $subject = 'Contact [' . $topic . '] ' . $core->getConfigVal('siteName');
    $msg = $msg . "\n\n----------\n\n" . $name . " " . $firstName . " (" . $reply . ")";
    $copy = $plugin->getConfigVal('copy');
    // @phpstan-ignore-next-line - $copy est vérifié non-null et string ci-dessous
    if ($copy !== false && $copy !== null && is_string($copy) && Util::isEmail($copy)) {
        Util::sendEmail($from, $reply, $copy, $subject, $msg);
    }
    return Util::sendEmail($from, $reply, $to, $subject, $msg);
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
    $core = Core::getInstance();
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('contact');
    if (!$plugin) return;

    // Le destinataire est l'admin configuré dans le plugin
    $targetUserId = (int)$plugin->getConfigVal('userMailId');
    if (!$targetUserId) $targetUserId = 1; // Fallback admin

    \Core\Core::getInstance()->callHook('notifications:send', [
        'user_id' => $targetUserId,
        'type' => 'contact.message',
        'source_plugin' => 'contact',
        'data' => [
            'key' => 'contact.message',
            'params' => [
                'actor' => ($data['firstname'] ?? '') . ' ' . ($data['name'] ?? ''),
                'topic' => $data['topic'] ?? 'Sujet inconnu'
            ],
            'url' => '/admin/contact', // TODO: URL vers la gestion des messages contact
            'actor' => [
                'name' => ($data['firstname'] ?? '') . ' ' . ($data['name'] ?? ''),
                'email' => $data['email'] ?? null
            ],
            'entity' => [
                'type' => 'contact_message',
                'id' => $data['id'] ?? null
            ]
        ],
        // Pas d'external_id spécifique ici (chaque message est unique)
    ]);
}

// Hooks are now registered in hooks.json