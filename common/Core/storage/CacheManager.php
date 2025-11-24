<?php

namespace Core\Storage;

use Core\Storage\Cache;
use Core\Core;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * The CacheManager class provides advanced cache management features.
 */
class CacheManager
{
    protected Cache $cache;

    public function __construct() {
        $this->cache = new Cache();
    }

    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getStats(): array {
        $cacheDir = CACHE;
        $files = glob($cacheDir . '*.cache.php');
        $totalSize = 0;
        $fileCount = count($files);

        foreach ($files as $file) {
            if (file_exists($file)) {
                $size = filesize($file);
                if ($size !== false) {
                    $totalSize += $size;
                }
            }
        }

        return [
            'files_count' => $fileCount,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize)
        ];
    }

    /**
     * Clear all cache files
     * 
     * @return bool
     */
    public function clearCache(): bool {
        $cacheDir = CACHE;
        $files = glob($cacheDir . '*.cache.php');
        $success = true;

        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Check if cache is enabled
     * 
     * @return bool
     */
    public function isCacheEnabled(): bool {
        // Toujours récupérer la configuration actuelle de core
        return Core::getInstance()->getConfigVal('cache_enabled') ?: true;
    }

    /**
     * Get cache duration
     * 
     * @return int
     */
    public function getCacheDuration(): int {
        // Toujours récupérer la configuration actuelle de core
        return Core::getInstance()->getConfigVal('cache_duration') ?: 3600;
    }

    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }


}