<?php

namespace ShortUrl\Controllers;

use Core\Controllers\Controller;
use Core\Database;

defined('ROOT') or exit('Access denied');

class RedirectController extends Controller
{
    public function go($id)
    {
        $this->redirectToPoi((int) $id);
    }

    public function com($slug)
    {
        // 1. Essayer d'extraire l'ID à la fin (format: nom-du-lieu-123)
        if (preg_match('/^(.+?)-(-?\d+)$/', $slug, $matches)) {
            $this->redirectToPoi((int) $matches[2]);
            return;
        }

        // 2. Sinon, essayer de trouver par le slug via Provider
        $provider = \Core\Core::getInstance()->callHook('entity:provider-request', ['entity' => 'poi']);
        if (is_object($provider) && method_exists($provider, 'getIdBySlug')) {
            $id = $provider->getIdBySlug($slug);
            if ($id) {
                $this->redirectToPoi($id);
                return;
            }
        }
        
        $this->redirect404();
    }

    private function redirectToPoi($id)
    {
        if ($id <= 0) {
            $this->redirect404();
            return;
        }

        $provider = \Core\Core::getInstance()->callHook('entity:provider-request', ['entity' => 'poi']);
        if (!is_object($provider) || !method_exists($provider, 'getUrl')) {
            $this->redirect404();
            return;
        }
        
        // We verify existence implicitly if getUrl returns something
        // or check getName first if we want strict existence check
        if (method_exists($provider, 'getName') && !$provider->getName($id)) {
             $this->redirect404();
             return;
        }

        $url = $provider->getUrl($id);
        
        if (!$url) {
            $this->redirect404();
            return;
        }
        
        // Cible : URL courte ou longue fournie par le provider
        $targetUrl = \Core\Core::getInstance()->getConfigVal('siteUrl') . $url;

        // Redirection 301
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $targetUrl);
        exit();
    }

    private function redirect404()
    {
        \Core\Core::getInstance()->error404();
    }
}
