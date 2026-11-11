<?php

namespace Utils;

defined('ROOT') or exit('Access denied!');

/**
 * Rate Limiter simple basé sur des fichiers
 * Utilise l'algorithme Token Bucket / Fixed Window
 */
class RateLimiter
{
    private string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = DATA . 'cache/ratelimit/';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Vérifie si l'IP actuelle a dépassé la limite
     * 
     * @param string $key Identifiant unique (ex: 'api_search', 'login_attempt')
     * @param int $limit Nombre de requêtes autorisées
     * @param int $windowSeconds Fenêtre de temps en secondes
     * @return bool True si la requête est autorisée, False si bloquée
     */
    public function check(string $key, int $limit = 60, int $windowSeconds = 60): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $fileKey = md5($ip . '_' . $key);
        $file = $this->cacheDir . $fileKey;

        $data = ['count' => 0, 'startTime' => time()];

        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content) {
                $decoded = json_decode($content, true);
                if ($decoded) {
                    $data = $decoded;
                }
            }
        }

        // Si la fenêtre est écoulée, on reset
        if (time() - $data['startTime'] > $windowSeconds) {
            $data['count'] = 0;
            $data['startTime'] = time();
        }

        if ($data['count'] >= $limit) {
            return false;
        }

        $data['count']++;
        @file_put_contents($file, json_encode($data));
        return true;
    }

    /**
     * Nettoie les fichiers de cache expirés (à appeler via cron ou occasionnellement)
     */
    public function cleanup(int $maxAgeSeconds = 3600): void
    {
        foreach (glob($this->cacheDir . '*') as $file) {
            if (is_file($file) && (time() - filemtime($file) > $maxAgeSeconds)) {
                @unlink($file);
            }
        }
    }
}
