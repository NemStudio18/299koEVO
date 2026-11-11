<?php

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

require_once PLUGINS . 'newsletter/entities/NewsletterSubscriber.php';
require_once PLUGINS . 'newsletter/entities/NewsletterManager.php';

use Newsletter\Entities\NewsletterManager;
use Newsletter\Entities\NewsletterSubscriber;

## Fonction d'installation

function newsletterInstall() {
    $dataFile = DATA_PLUGIN . 'newsletter/subscribers.json';
    $dataDir = dirname($dataFile);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    if (!file_exists($dataFile)) {
        file_put_contents($dataFile, json_encode([]));
    }
}

## Hooks

function newsletterBeforeRunPlugin()
{
    // Hooks are now registered in hooks.json
}

function newsletterEndFrontHead($param = ''): string
{
    $output = (string)$param;
    $output .= '<link rel="stylesheet" href="' . \Utils\Util::urlBuild(PLUGINS . 'newsletter/assets/css/newsletter.css') . '">' . "\n";
    return $output;
}

/**
 * Hook: user:profile-settings
 */
function newsletterUserProfileSettings($user): string
{
    $newsletterManager = new NewsletterManager();
    $isSubscribed = $newsletterManager->isUserSubscribed($user->email);
    $router = \Core\Router\Router::getInstance();
    
    $html = '<div class="setting-item" id="newsletter-setting-item">';
    
    if ($isSubscribed) {
        $html .= '
            <i class="fa-solid fa-envelope-circle-check newsletter-icon-subscribed"></i>
            <div class="setting-info" id="newsletter-settings-container">
                <span class="setting-label">' . \Core\Lang::get('newsletter.subscribed-status') . '</span>
                <span class="setting-description">Vous recevez notre newsletter par email.</span>
                <form hx-post="' . ($router->hasRoute('newsletter-profile-unsubscribe') ? $router->generate('newsletter-profile-unsubscribe') : '#') . '" hx-target="#newsletter-setting-item" hx-swap="outerHTML" class="newsletter-settings-form">
                    ' . \Utils\Show::tokenField() . '
                    <button type="submit" class="button alert newsletter-btn-unsubscribe" onclick="return confirm(\'' . \Core\Lang::get('newsletter.confirm-unsubscription') . '\');">
                        <i class="fa-solid fa-envelope-open"></i> ' . \Core\Lang::get('newsletter.unsubscribe') . '
                    </button>
                </form>
            </div>';
    } else {
        $html .= '
            <i class="fa-regular fa-envelope newsletter-icon-unsubscribed"></i>
            <div class="setting-info" id="newsletter-settings-container">
                <span class="setting-label">Newsletter</span>
                <span class="setting-description">Vous n\'êtes pas abonné à notre newsletter.</span>
                <form hx-post="' . ($router->hasRoute('newsletter-quick-subscribe') ? $router->generate('newsletter-quick-subscribe') : '#') . '" hx-target="#newsletter-setting-item" hx-swap="outerHTML" class="newsletter-settings-form">
                    ' . \Utils\Show::tokenField() . '
                    <button type="submit" class="button newsletter-btn-subscribe">
                        <i class="fa-solid fa-envelope"></i> ' . \Core\Lang::get('newsletter.subscribe') . '
                    </button>
                </form>
            </div>';
    }
    
    $html .= '</div>';
    return $html;
}

function newsletterEndAdminHead($param = ''): string
{
    $output = (string)$param;
    $output .= '<link rel="stylesheet" href="' . \Utils\Util::urlBuild(PLUGINS . 'newsletter/assets/css/newsletter.css') . '">' . "\n";
    return $output;
}

function newsletterEndFrontBody() 
{
    $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
    if (!$pluginsManager->isActivePlugin('newsletter')) {
        return;
    }
    
    $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
    $ctx = new \Core\Navigation\ConditionContext(\Core\Auth\UsersManager::getCurrentUser());
    $isLogged = $evaluator->canAccess(new \Core\Navigation\GenericResource('newsletter:logged'), $ctx);
    
    // SKIP if logged in (user will have a link in avatar menu if not subscribed)
    if ($isLogged) {
        return;
    }

    // Visit counting for GUESTS
    $visitCount = (int)($_COOKIE['newsletter_visit_count'] ?? 0);
    // On n'incrémente qu'une fois par session pour éviter le harcèlement au refresh
    if (!isset($_SESSION['newsletter_counted'])) {
        $visitCount++;
        setcookie('newsletter_visit_count', (string)$visitCount, time() + (365 * 24 * 60 * 60), '/');
        $_SESSION['newsletter_counted'] = true;
    }

    // Trigger ONLY on 10th visit (unless Orchestrator is active)
    $orchestratorActive = \Core\Plugin\PluginsManager::isActivePlugin('ConversionOrchestrator');
    if (!$orchestratorActive && $visitCount !== 10) {
        return;
    }
    
    $router = \Core\Router\Router::getInstance();
    $subscribeUrl = $router->generate('newsletter-subscribe');
    
    $templateFile = PLUGINS . 'newsletter/template/modal.tpl';
    if (!file_exists($templateFile)) {
        return;
    }
    
    $tpl = new \Template\Template($templateFile);
    $tpl->set('subscribeUrl', $subscribeUrl);
    $tpl->set('quickSubscribeUrl', null);
    $tpl->set('isLogged', false);
    $tpl->set('isSubscribed', false);
    
    echo $tpl->output();
}


/**
 * Hook: user:avatar-menu
 */
function newsletterUserAvatarMenu(array $data): array
{
    $user = $data['user'] ?? null;
    if (!$user) return $data;

    $newsletterManager = new \Newsletter\Entities\NewsletterManager();
    $isSubscribed = $newsletterManager->isUserSubscribed($user->email);

    if (!$isSubscribed) {
        $router = \Core\Router\Router::getInstance();
        $url = $router->hasRoute('newsletter-quick-subscribe') ? $router->generate('newsletter-quick-subscribe') : ($router->hasRoute('newsletter-home') ? $router->generate('newsletter-home') : '/newsletter');
        
        $data['menuItems'][] = [
            'label' => 'S\'inscrire à la newsletter',
            'url' => $url,
            'icon' => 'fa-solid fa-paper-plane',
            'class' => 'avatar-menu-item'
        ];
    }

    return $data;
}

## Code relatif au plugin

