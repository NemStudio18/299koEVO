<?php

namespace SearchEngine\Services;

use PDO;
use Core\Core;
use Core\Logger;
use Utils\Util;
use SearchEngine\Services\SearchLogger;

defined('ROOT') or exit('Access denied!');

/**
 * Service de gestion du cache JSON pour les chips, POIs et stats
 */
class CacheJsonService
{
    private ?PDO $pdo;
    private string $cacheDir;
    private int $defaultTtl; // TTL par défaut en secondes (60 minutes)

    public function __construct(?PDO $pdo = null, ?string $cacheDir = null, int $defaultTtl = 3600)
    {
        $this->pdo = $pdo;
        $this->defaultTtl = $defaultTtl; // 60 minutes par défaut (3600 secondes)

        // Définir le répertoire de cache
        if ($cacheDir === null) {
            $this->cacheDir = DATA_PLUGIN . 'search-engine' . DS . 'cache' . DS;
        } else {
            $this->cacheDir = rtrim($cacheDir, DS) . DS;
        }

        // Créer le répertoire s'il n'existe pas
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Génère le nom de fichier pour les chips d'une ville
     */
    public function getChipsFilename(string $city): string
    {
        $safeCity = preg_replace('/[^a-zA-Z0-9_-]/', '_', $city);
        $safeCity = preg_replace('/_+/', '_', $safeCity);
        $safeCity = trim($safeCity, '_');
        return $this->cacheDir . 'chips_' . $safeCity . '.json';
    }

    /**
     * Génère le nom de fichier pour les POIs (regroupés par catégorie)
     */
    public function getPoisFilename(string $city, string $category, ?string $subcategory = null): string
    {
        $safeCity = preg_replace('/[^a-zA-Z0-9_-]/', '_', $city);
        $safeCategory = preg_replace('/[^a-zA-Z0-9_-]/', '_', $category);
        $safeCity = preg_replace('/_+/', '_', $safeCity);
        $safeCategory = preg_replace('/_+/', '_', $safeCategory);
        $safeCity = trim($safeCity, '_');
        $safeCategory = trim($safeCategory, '_');

        return $this->cacheDir . 'pois_' . $safeCity . '_' . $safeCategory . '.json';
    }

    /**
     * Génère le nom de fichier pour les POIs (ancien format - pour compatibilité)
     */
    public function getPoisFilenameOld(string $city, string $category, string $subcategory): string
    {
        $safeCity = preg_replace('/[^a-zA-Z0-9_-]/', '_', $city);
        $safeCategory = preg_replace('/[^a-zA-Z0-9_-]/', '_', $category);
        $safeSubcategory = preg_replace('/[^a-zA-Z0-9_-]/', '_', $subcategory);
        $safeCity = preg_replace('/_+/', '_', $safeCity);
        $safeCategory = preg_replace('/_+/', '_', $safeCategory);
        $safeSubcategory = preg_replace('/_+/', '_', $safeSubcategory);
        $safeCity = trim($safeCity, '_');
        $safeCategory = trim($safeCategory, '_');
        $safeSubcategory = trim($safeSubcategory, '_');
        return $this->cacheDir . 'pois_' . $safeCity . '_' . $safeCategory . '_' . $safeSubcategory . '.json';
    }

    /**
     * Génère le nom de fichier pour les stats home
     */
    public function getStatsFilename(): string
    {
        return $this->cacheDir . 'stats_home.json';
    }

    /**
     * Génère le nom de fichier pour tous les POIs de l'agglomération
     */
    public function getAllPoisFilename(): string
    {
        return $this->cacheDir . 'all_pois_agglo.json';
    }

    /**
     * Génère le nom de fichier pour le cache d'une sous-catégorie
     */
    public function getSubcategoryPoisFilename(string $category, string $subcategory, string $city = ''): string
    {
        $safeCategory = preg_replace('/[^a-zA-Z0-9_-]/', '_', $category);
        $safeSubcategory = preg_replace('/[^a-zA-Z0-9_-]/', '_', $subcategory);
        $safeCity = !empty($city) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $city) : 'agglo';

        $safeCategory = trim(preg_replace('/_+/', '_', $safeCategory), '_');
        $safeSubcategory = trim(preg_replace('/_+/', '_', $safeSubcategory), '_');
        $safeCity = trim(preg_replace('/_+/', '_', $safeCity), '_');

        return $this->cacheDir . 'subcategory_' . $safeCity . '_' . $safeCategory . '_' . $safeSubcategory . '.json';
    }

    /**
     * Récupère ou génère les POIs d'une sous-catégorie
     */
    public function getSubcategoryPois(string $category, string $subcategory, callable $generator, ?int $ttl = null, string $city = ''): array
    {
        $filename = $this->getSubcategoryPoisFilename($category, $subcategory, $city);

        // Vérifier le cache
        if ($this->isCacheValid($filename, $ttl)) {
            $data = $this->readJsonFile($filename);
            if ($data !== null && isset($data['pois'])) {
                SearchLogger::debug(sprintf(
                    "CacheJsonService::getSubcategoryPois - Cache lu: category=%s, subcategory=%s, filename=%s, POIs=%d",
                    $category,
                    $subcategory,
                    basename($filename),
                    count($data['pois'] ?? [])
                ));
                return $data;
            }
        }

        // Cache expiré ou inexistant - essayer de le régénérer
        $fallbackData = null;
        if (file_exists($filename) || file_exists($filename . '.gz')) {
            $fallbackData = $this->readJsonFile($filename);
            if ($fallbackData !== null && isset($fallbackData['pois']) && is_array($fallbackData['pois']) && !empty($fallbackData['pois'])) {
                SearchLogger::debug(sprintf(
                    "CacheJsonService::getSubcategoryPois - Cache expiré mais valide trouvé pour fallback: category=%s, subcategory=%s, POIs=%d",
                    $category,
                    $subcategory,
                    count($fallbackData['pois'])
                ));
            } else {
                $fallbackData = null;
            }
        }

        SearchLogger::debug(sprintf(
            "CacheJsonService::getSubcategoryPois - Cache invalide ou absent, régénération: category=%s, subcategory=%s, filename=%s",
            $category,
            $subcategory,
            basename($filename)
        ));

        try {
            if ($this->pdo === null) {
                throw new \Exception("PDO non disponible - utilisation du cache en fallback");
            }

            $data = $generator();

            if (isset($data['pois']) && is_array($data['pois']) && !empty($data['pois'])) {
                if ($this->writeJsonFile($filename, $data)) {
                    SearchLogger::debug(sprintf(
                        "CacheJsonService::getSubcategoryPois - Cache régénéré: category=%s, subcategory=%s, POIs=%d",
                        $category,
                        $subcategory,
                        count($data['pois'])
                    ));
                }
                return $data;
            } else {
                SearchLogger::warning(sprintf(
                    "CacheJsonService::getSubcategoryPois - Données générées invalides (category=%s, subcategory=%s), utilisation du cache en fallback",
                    $category,
                    $subcategory
                ));
                return $fallbackData !== null ? $fallbackData : $data;
            }
        } catch (\Exception $e) {
            SearchLogger::warning(sprintf(
                "CacheJsonService::getSubcategoryPois - Erreur génération: %s (category=%s, subcategory=%s) - utilisation du cache en fallback",
                $e->getMessage(),
                $category,
                $subcategory
            ));
            return $fallbackData !== null ? $fallbackData : ['pois' => [], 'total' => 0];
        }
    }

    /**
     * Écrit un fichier JSON de manière atomique
     */
    public function writeJsonFile(string $filename, array $data, bool $compress = true): bool
    {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (!is_array($data) || empty($data)) {
            SearchLogger::warning("CacheJsonService::writeJsonFile - Données vides ou non-array pour: " . basename($filename));
            return false;
        }

        // Protection spéciale pour stats_home.json
        if (strpos($filename, 'stats_home.json') !== false) {
            $hasValidTotal = isset($data['total']) && is_numeric($data['total']) && (int) $data['total'] > 0;
            $hasValidCities = isset($data['cities']) && is_array($data['cities']) && !empty($data['cities']);
            $hasValidCategories = isset($data['categories_by_type']) && is_array($data['categories_by_type']) && !empty($data['categories_by_type']);

            if (!$hasValidTotal && !$hasValidCities && !$hasValidCategories) {
                SearchLogger::warning("CacheJsonService::writeJsonFile - Protection: Tentative d'écriture de stats_home.json avec des données vides, ignorée");
                return false;
            }
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false || trim($json) === '' || trim($json) === '{}' || trim($json) === '[]') {
            SearchLogger::warning("CacheJsonService::writeJsonFile - Encodage JSON échoué pour: " . basename($filename) . " - json_error: " . json_last_error_msg());
            return false;
        }

        if ($compress && strlen($json) > 50000) {
            $compressed = gzencode($json, 6);
            $targetFilename = $filename . '.gz';
            $tmpFile = $targetFilename . '.tmp';
            if (file_put_contents($tmpFile, $compressed) === false) {
                return false;
            }
            return rename($tmpFile, $targetFilename);
        }

        return Util::writeJsonFile($filename, $data);
    }

    /**
     * Lit un fichier JSON (gzip ou non)
     */
    public function readJsonFile(string $filename): ?array
    {
        if (file_exists($filename . '.gz')) {
            $content = @gzdecode(file_get_contents($filename . '.gz'));
            if ($content === false)
                return null;
            $data = json_decode($content, true);
            return is_array($data) ? $data : null;
        }

        $data = Util::readJsonFile($filename, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Vérifie si un fichier cache est valide (non expiré)
     */
    public function isCacheValid(string $filename, ?int $ttl = null): bool
    {
        if (!file_exists($filename) && !file_exists($filename . '.gz')) {
            return false;
        }

        $actualFile = file_exists($filename) ? $filename : ($filename . '.gz');
        $ttlToUse = $ttl !== null ? $ttl : $this->defaultTtl;

        if ($ttlToUse === 0) {
            return true;
        }

        $age = time() - filemtime($actualFile);
        return $age < $ttlToUse;
    }

    /**
     * Récupère ou génère les chips pour une ville
     */
    public function getChips(string $city, callable $generator, ?int $ttl = null): array
    {
        $filename = $this->getChipsFilename($city);

        if ($this->isCacheValid($filename, $ttl)) {
            $data = $this->readJsonFile($filename);
            if ($data !== null)
                return $data;
        }

        $fallbackData = null;
        if (file_exists($filename) || file_exists($filename . '.gz')) {
            $fallbackData = $this->readJsonFile($filename);
            if ($fallbackData !== null && is_array($fallbackData) && !empty($fallbackData)) {
                SearchLogger::debug("CacheJsonService::getChips - Cache expiré mais valide trouvé pour fallback (city: $city)");
            } else {
                $fallbackData = null;
            }
        }

        try {
            if ($this->pdo === null) {
                throw new \Exception("PDO non disponible - utilisation du cache en fallback");
            }

            $data = $generator();

            if (is_array($data) && !empty($data)) {
                if ($this->writeJsonFile($filename, $data)) {
                    SearchLogger::debug("CacheJsonService::getChips - Cache régénéré et sauvegardé (city: $city)");
                }
                return $data;
            } else {
                SearchLogger::warning("CacheJsonService::getChips - Données générées invalides (city: $city), utilisation du cache en fallback");
                return $fallbackData !== null ? $fallbackData : $data;
            }
        } catch (\Exception $e) {
            SearchLogger::warning("CacheJsonService::getChips - Erreur génération: " . $e->getMessage() . " (city: $city) - utilisation du cache en fallback");
            return $fallbackData !== null ? $fallbackData : [];
        }
    }

    /**
     * Récupère les POIs pour une sous-catégorie depuis le cache regroupé par catégorie
     */
    public function getPois(string $city, string $category, string $subcategory, ?callable $generator = null, ?int $ttl = null): array
    {
        $filename = $this->getPoisFilename($city, $category);

        if ($this->isCacheValid($filename, $ttl)) {
            $categoryData = $this->readJsonFile($filename);
            if ($categoryData !== null && is_array($categoryData) && isset($categoryData[$subcategory])) {
                return $categoryData[$subcategory];
            }
        }

        if ($generator === null) {
            return ['results' => [], 'total' => 0];
        }

        $fallbackData = null;
        if (file_exists($filename) || file_exists($filename . '.gz')) {
            $categoryData = $this->readJsonFile($filename);
            if ($categoryData !== null && is_array($categoryData) && isset($categoryData[$subcategory])) {
                $subcategoryData = $categoryData[$subcategory];
                if (is_array($subcategoryData) && isset($subcategoryData['results']) && (int) ($subcategoryData['total'] ?? 0) > 0) {
                    $fallbackData = $subcategoryData;
                    SearchLogger::debug("CacheJsonService::getPois - Cache expiré mais valide trouvé pour fallback (city: $city, category: $category, subcategory: $subcategory)");
                }
            }
        }

        try {
            if ($this->pdo === null) {
                throw new \Exception("PDO non disponible - utilisation du cache en fallback");
            }

            $data = $generator();

            if (!is_array($data) || !isset($data['results']) || !isset($data['total'])) {
                return $fallbackData !== null ? $fallbackData : ['results' => [], 'total' => 0];
            }

            if ((int) $data['total'] > 0 && !empty($data['results'] ?? [])) {
                $categoryData = $this->readJsonFile($filename);
                if (!is_array($categoryData))
                    $categoryData = [];
                $categoryData[$subcategory] = $data;
                if ($this->writeJsonFile($filename, $categoryData)) {
                    SearchLogger::debug("CacheJsonService::getPois - Cache régénéré et sauvegardé (city: $city, category: $category, subcategory: $subcategory)");
                }
                return $data;
            } else {
                SearchLogger::warning("CacheJsonService::getPois - Données générées invalides (city: $city, category: $category, subcategory: $subcategory), utilisation du cache en fallback");
                return $fallbackData !== null ? $fallbackData : $data;
            }
        } catch (\Exception $e) {
            SearchLogger::warning("CacheJsonService::getPois - Erreur génération: " . $e->getMessage() . " (city: $city, category: $category, subcategory: $subcategory) - utilisation du cache en fallback");
            return $fallbackData !== null ? $fallbackData : ['results' => [], 'total' => 0];
        }
    }

    /**
     * Récupère tous les POIs d'une catégorie
     */
    public function getPoisByCategory(string $city, string $category, ?int $ttl = null): ?array
    {
        $filename = $this->getPoisFilename($city, $category);
        if ($this->isCacheValid($filename, $ttl)) {
            return $this->readJsonFile($filename);
        }
        return null;
    }

    /**
     * Récupère ou génère les stats home
     */
    public function getStats(callable $generator, ?int $ttl = null): array
    {
        $filename = $this->getStatsFilename();

        if ($this->isCacheValid($filename, $ttl)) {
            $data = $this->readJsonFile($filename);
            if ($data !== null) {
                SearchLogger::debug("CacheJsonService::getStats - Cache lu, total=" . ($data['total'] ?? 'inconnu'));
                return $data;
            }
        }

        $fallbackData = null;
        if (file_exists($filename) || file_exists($filename . '.gz')) {
            $fallbackData = $this->readJsonFile($filename);
            if ($fallbackData !== null && isset($fallbackData['total']) && (int) $fallbackData['total'] > 0) {
                SearchLogger::debug("CacheJsonService::getStats - Cache expiré trouvé pour fallback, total=" . (int) $fallbackData['total']);
            } else {
                $fallbackData = null;
            }
        }

        try {
            if ($this->pdo === null) {
                throw new \Exception("PDO non disponible - utilisation du cache en fallback");
            }

            $data = $generator();

            if (isset($data['total']) && (int) $data['total'] > 0) {
                if ($this->writeJsonFile($filename, $data)) {
                    SearchLogger::debug("CacheJsonService::getStats - Cache régénéré, total=" . (int) $data['total']);
                }
                return $data;
            } else {
                SearchLogger::warning("CacheJsonService::getStats - Données générées invalides, fallback");
                return $fallbackData !== null ? $fallbackData : $data;
            }
        } catch (\Exception $e) {
            SearchLogger::warning("CacheJsonService::getStats - Erreur génération: " . $e->getMessage());
            return $fallbackData !== null ? $fallbackData : ['total' => 0, 'cities' => [], 'categories_by_type' => []];
        }
    }

    /**
     * Récupère ou génère tous les POIs de l'agglomération
     */
    public function getAllPois(callable $generator, ?int $ttl = null): array
    {
        $filename = $this->getAllPoisFilename();

        if ($this->isCacheValid($filename, $ttl)) {
            $data = $this->readJsonFile($filename);
            if ($data !== null) {
                SearchLogger::debug("CacheJsonService::getAllPois - Cache lu: " . basename($filename));
                return $data;
            }
        }

        SearchLogger::debug("CacheJsonService::getAllPois - Génération du cache: " . basename($filename));
        $data = $generator();

        if ($this->writeJsonFile($filename, $data)) {
            SearchLogger::debug("CacheJsonService::getAllPois - Cache sauvegardé: " . basename($filename));
        }

        return $data;
    }

    /**
     * Invalide le cache des chips pour une ville
     */
    public function invalidateChips(string $city): void
    {
        $filename = $this->getChipsFilename($city);
        @unlink($filename);
        @unlink($filename . '.gz');
        $this->triggerCacheRegeneration($city);
    }

    /**
     * Invalide le cache des POIs
     */
    public function invalidatePois(string $city, ?string $category = null, ?string $subcategory = null): void
    {
        if ($category) {
            $filename = $this->getPoisFilename($city, $category);
            @unlink($filename);
            @unlink($filename . '.gz');
        } else {
            $pattern = $this->cacheDir . 'pois_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $city) . '_*.json*';
            foreach (glob($pattern) as $file)
                @unlink($file);
        }
        $this->triggerCacheRegeneration($city);
    }

    /**
     * Invalide le cache des stats
     */
    public function invalidateStats(?callable $generator = null): void
    {
        $filename = $this->getStatsFilename();
        @unlink($filename);
        @unlink($filename . '.gz');

        if ($generator !== null) {
            $this->getStats($generator, 0); // Régénération forcée
        } else {
            $this->triggerCacheRegeneration(null, true);
        }
    }

    /**
     * Lance la régénération du cache en arrière-plan
     */
    private function triggerCacheRegeneration(?string $city = null, bool $statsOnly = false): void
    {
        $scriptPath = dirname(__DIR__) . DS . 'bin' . DS . 'regenerate_cache.php';
        if (!file_exists($scriptPath))
            return;

        $phpPath = PHP_BINARY;
        $command = escapeshellarg($phpPath) . ' ' . escapeshellarg($scriptPath);

        if ($statsOnly) {
            $command .= ' --stats-only';
        } elseif ($city !== null) {
            $command .= ' --city=' . escapeshellarg($city);
        } else {
            $command .= ' --all';
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            @popen('start /B ' . $command . ' > NUL 2>&1', 'r');
        } else {
            @exec($command . ' > /dev/null 2>&1 &');
        }
    }

    public function invalidateSubcategoryPois(string $category, string $subcategory): void
    {
        $filename = $this->getSubcategoryPoisFilename($category, $subcategory);
        @unlink($filename);
        @unlink($filename . '.gz');
        SearchLogger::debug("CacheJsonService::invalidateSubcategoryPois - $category/$subcategory");
    }

    public function invalidateAll(): void
    {
        foreach (glob($this->cacheDir . '*.json*') as $file)
            @unlink($file);
    }

    public function getEtag(string $filename): string
    {
        $actualFile = file_exists($filename) ? $filename : ($filename . '.gz');
        return file_exists($actualFile) ? md5_file($actualFile) : '';
    }

    public function getLastModified(string $filename): int
    {
        $actualFile = file_exists($filename) ? $filename : ($filename . '.gz');
        return file_exists($actualFile) ? filemtime($actualFile) : 0;
    }
}

