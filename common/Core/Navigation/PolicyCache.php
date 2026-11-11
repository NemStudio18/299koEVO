<?php

namespace Core\Navigation;

/**
 * Cache dédié aux décisions de politiques d'accès.
 * Optimisé pour la performance avec un TTL court et l'invalidation par version.
 */
class PolicyCache
{
    private static ?PolicyCache $instance = null;
    private array $inMemoryCache = [];
    private string $cacheDir;
    private int $defaultTtl = 300; // 5 minutes par défaut

    private function __construct()
    {
        $this->cacheDir = DATA . 'cache' . DS . 'policies' . DS;
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    public static function getInstance(): PolicyCache
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Récupère une décision du cache.
     */
    public function get(string $key): ?bool
    {
        // 1. Cache en mémoire (Request lifetime)
        if (isset($this->inMemoryCache[$key])) {
            return $this->inMemoryCache[$key];
        }

        // 2. Cache sur disque (Persistent)
        $file = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (isset($data['expiry']) && $data['expiry'] >= time()) {
                $value = (bool)$data['value'];
                $this->inMemoryCache[$key] = $value;
                return $value;
            }
            @unlink($file); // Expiré
        }

        return null;
    }

    /**
     * Sauvegarde une décision dans le cache.
     */
    public function set(string $key, bool $value, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $this->inMemoryCache[$key] = $value;

        $file = $this->cacheDir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expiry' => time() + $ttl,
            'created_at' => time()
        ];
        @file_put_contents($file, json_encode($data));
    }

    /**
     * Invalide tout le cache (ex: changement de version globale).
     */
    public function flush(): void
    {
        $this->inMemoryCache = [];
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    /**
     * Invalide une entrée spécifique.
     */
    public function invalidate(string $key): void
    {
        unset($this->inMemoryCache[$key]);
        $file = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
