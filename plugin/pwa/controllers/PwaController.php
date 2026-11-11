<?php

namespace Pwa\Controllers;

use Core\Controllers\PublicController;
use Core\Core;
use Core\Logger;
use Core\Responses\StringResponse;
use Utils\Util;

defined('ROOT') OR exit('No direct script access allowed');

class PwaController extends PublicController
{
    public function home()
    {
        // Page publique optionnelle (pour l'instant redirection home)
        Core::getInstance()->redirect($this->router->generate('home'));
    }

    public function serviceWorker()
    {
        // Désactiver le traitement de la réponse par le système
        $this->core->getLogger()->log(Logger::LEVEL_INFO, 'PWA: serviceWorker endpoint hit (router match successful)');
        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $swContent = file_get_contents(PLUGINS . 'pwa/sw.js');
        if ($swContent === false) {
            http_response_code(404);
            $this->core->getLogger()->log(Logger::LEVEL_ERROR, 'PWA: sw.js file missing at ' . PLUGINS . 'pwa/sw.js');
            echo 'Service Worker not found';
            die();
        }
        echo $swContent;
        die();
    }

    public function manifest()
    {
        // Désactiver le traitement de la réponse par le système et forcer l'accès public
        $this->core->getLogger()->log(Logger::LEVEL_INFO, 'PWA: manifest endpoint hit (router match successful)');

        // Headers essentiels
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Access-Control-Allow-Origin: *'); // Autoriser l'accès cross-origin pour le manifest

        $core = Core::getInstance();
        $siteName = $core->getConfigVal('siteName') ?: '299Ko';
        $siteUrl = $core->getConfigVal('siteUrl') ?: '/';

        $defaultIcon192 = 'plugin/pwa/icons/icon-192.png';
        $defaultIcon512 = 'plugin/pwa/icons/icon-512.png';
        $icon192Path = $defaultIcon192;
        $icon512Path = $defaultIcon512;

        if ($this->runPlugin) {
            $icon192Path = $this->runPlugin->getConfigVal('icon192') ?: $defaultIcon192;
            $icon512Path = $this->runPlugin->getConfigVal('icon512') ?: $defaultIcon512;
        }

        $manifest = [
            'name' => $siteName,
            'short_name' => $siteName,
            'description' => $this->runPlugin ? ($this->runPlugin->getConfigVal('description') ?: 'Progressive Web App') : 'Progressive Web App',
            'start_url' => $siteUrl, // S'assurer que ça pointe vers la racine
            'display' => 'standalone',
            'background_color' => $this->runPlugin ? ($this->runPlugin->getConfigVal('backgroundColor') ?: '#ffffff') : '#ffffff',
            'theme_color' => $this->runPlugin ? ($this->runPlugin->getConfigVal('themeColor') ?: '#000000') : '#000000',
            'orientation' => 'portrait',
            'icons' => []
        ];

        $iconEntries = [
            ['size' => '192x192', 'path' => $icon192Path],
            ['size' => '512x512', 'path' => $icon512Path],
        ];

        foreach ($iconEntries as $icon) {
            $url = $this->formatIconUrl($icon['path']);
            if ($url) {
                // S'assurer que l'URL est absolue si possible ou valide
                $manifest['icons'][] = [
                    'src' => $url,
                    'sizes' => $icon['size'],
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ];
            }
        }

        $this->core->getLogger()->log(Logger::LEVEL_INFO, 'PWA: manifest payload generated');
        echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit; // Utiliser exit au lieu de die pour plus de propreté
    }

    public function getPublicKey()
    {
        require_once PLUGINS . 'pwa/pwa.php';
        $vapidKeys = pwaGetVapidKeys();
        $key = $vapidKeys ? trim((string) $vapidKeys['publicKey']) : '';
        header('Content-Type: text/plain; charset=utf-8');
        echo $key;
        die();
    }

    public function subscribe()
    {
        // Expect JSON body: { endpoint, keys: {p256dh, auth} }
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['endpoint']) || empty($data['keys']['p256dh']) || empty($data['keys']['auth'])) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Invalid subscription';
            die();
        }
        $file = DATA_PLUGIN . 'pwa/subscriptions.json';
        $subs = Util::readJsonFile($file, true);
        if (!is_array($subs)) {
            $subs = [];
        }
        // Deduplicate by endpoint
        $exists = false;
        foreach ($subs as $s) {
            if (isset($s['endpoint']) && $s['endpoint'] === $data['endpoint']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $user = \Core\Auth\UsersManager::getCurrentUser();
            $userId = $user ? (int) $user->attributes['id'] : 0;

            $subs[] = [
                'endpoint' => $data['endpoint'],
                'keys' => [
                    'p256dh' => $data['keys']['p256dh'],
                    'auth' => $data['keys']['auth']
                ],
                'user_id' => $userId,
                'createdAt' => date('c')
            ];
            Util::writeJsonFile($file, $subs);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        die();
    }

    public function unsubscribe()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['endpoint'])) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Invalid endpoint';
            die();
        }
        $file = DATA_PLUGIN . 'pwa/subscriptions.json';
        $subs = Util::readJsonFile($file, true);
        if (!is_array($subs)) {
            $subs = [];
        }
        $subs = array_values(array_filter($subs, function ($s) use ($data) {
            return !isset($s['endpoint']) || $s['endpoint'] !== $data['endpoint'];
        }));
        Util::writeJsonFile($file, $subs);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        die();
    }
    private function formatIconUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if ($path[0] === '/') {
            return $path;
        }
        return Util::urlBuild($path);
    }
}


