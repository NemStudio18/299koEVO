<?php

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

require_once PLUGINS . 'guestbook/entities/GuestbookEntry.php';
require_once PLUGINS . 'guestbook/entities/GuestbookManager.php';

## Fonction d'installation

function guestbookInstall() {
    \Utils\Util::writeJsonFile(DATA_PLUGIN . 'guestbook/entries.json', []);
}

## Hooks

/**
 * Hook: user:profile-stats
 * Envoie les statistiques du livre d'or vers le profil utilisateur
 */
function guestbookUserProfileStats(array $stats): array
{
    $count = 0;
    $user = \Core\Auth\UsersManager::getCurrentUser();
    $userEmail = $user ? ($user->attributes['email'] ?? '') : '';

    if (!empty($userEmail)) {
        $manager = new GuestbookManager();
        $entries = $manager->getItems();
        foreach ($entries as $entry) {
            if ($entry->getEmail() === $userEmail) {
                $count++;
            }
        }
    }

    $stats[] = [
        'label' => 'Messages sur le Livre d\'or',
        'value' => $count,
        'color' => '#f1c40f',
        'icon' => 'fa-solid fa-book-open'
    ];

    return $stats;
}

/**
 * Hook: Injecte le CSS public pour le mode sombre
 */
function guestbookEndFrontHead($param = ''): string
{
    $output = (string)$param;
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/guestbook') === false) {
        return $output;
    }
    
    $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
    $cssUrl = $siteUrl . '/plugin/guestbook/template/public.css';
    
    return $output . '<link rel="stylesheet" href="' . $cssUrl . '?v=' . VERSION . '">' . "\n";
}

## Code relatif au plugin

// Hooks are now registered in hooks.json



