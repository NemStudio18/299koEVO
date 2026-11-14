<?php

namespace Core\Settings;

defined('ROOT') or exit('Access denied!');

if (!function_exists('Core\Settings\logg')) {
    function logg(string $message, string $level = 'INFO'): void {
        \Core\Logger::getInstance()->log($level, $message);
    }
}

class UpdaterManager
{
    public $lastVersion;
    public ?string $latestCommit = null;
    public $nextVersion;
    protected $metaDatas;
    public bool $isReady;
    protected $tempExtractPath = null;
    protected string $baseUrl = '';
    const REMOTE = 'https://raw.githubusercontent.com/NemStudio18/299koEVO_Versions/main/';
    const MAIN_REPO = 'https://api.github.com/repos/NemStudio18/299koEVO/zipball/';

    protected function resolveApiBaseUrl(): string
    {
        if ($this->baseUrl !== '') {
            return $this->baseUrl;
        }
        $base = '';
        if (class_exists('\Core\Core')) {
            $core = \Core\Core::getInstance();
            $configured = $core->getConfigVal('marketplaceUrl');
            if (!is_string($configured) || trim($configured) === '') {
                $configured = $core->getEnv('marketplaceUrl', '');
            }
            if (is_string($configured) && trim($configured) !== '') {
                $base = rtrim(trim($configured), '/') . '/';
            }
        }
        if ($base === '') {
            $base = 'https://299ko-api.flexcb.fr/';
        }
        $this->baseUrl = $base;
        return $this->baseUrl;
    }

    public function __construct()
    {
        if (!ini_get('allow_url_fopen') && !function_exists('curl_init')) {
            logg("Can't get remotes files", 'INFO');
            $this->isReady = false;
            return;
        }

        $fileContent = $this->getRemoteFileContent($this->resolveApiBaseUrl() . 'repository/api/core/versions');

        if (!$fileContent) {
            $this->isReady = false;
            return;
        }

        $file = json_decode($fileContent, true);
        if (!$file || !isset($file['last_version'])) {
            $this->isReady = false;
            return;
        }

        $this->lastVersion = $file['last_version'];
        $this->latestCommit = isset($file['latest_commit']) && is_string($file['latest_commit']) ? trim($file['latest_commit']) : null;
        $this->metaDatas = $file;
        $this->isReady = true;
    }

    /**
     * Compare two version strings semantically
     * Returns: -1 if $v1 < $v2, 0 if equal, 1 if $v1 > $v2
     */
    protected function compareVersions($v1, $v2)
    {
        // Normalize versions (remove 'v' prefix if present)
        $v1 = ltrim($v1, 'v');
        $v2 = ltrim($v2, 'v');
        
        // Split into parts
        $parts1 = explode('.', $v1);
        $parts2 = explode('.', $v2);
        
        // Pad with zeros to ensure same length
        $maxLength = max(count($parts1), count($parts2));
        $parts1 = array_pad($parts1, $maxLength, 0);
        $parts2 = array_pad($parts2, $maxLength, 0);
        
        // Compare each part
        for ($i = 0; $i < $maxLength; $i++) {
            $part1 = (int)$parts1[$i];
            $part2 = (int)$parts2[$i];
            
            if ($part1 < $part2) {
                return -1;
            } elseif ($part1 > $part2) {
                return 1;
            }
        }
        
        return 0;
    }

    /**
     * Récupère le hash du commit local (depuis core_patch.json ou Git HEAD)
     */
    public function getLocalCommit(): ?string
    {
        $patchFile = DATA_CORE_SETTINGS . 'core_patch.json';
        if (is_file($patchFile)) {
            $data = json_decode((string) @file_get_contents($patchFile), true);
            if (is_array($data)) {
                if (!empty($data['commit']) && is_string($data['commit'])) {
                    return trim($data['commit']);
                }
                if (!empty($data['patch']) && is_string($data['patch'])) {
                    return trim($data['patch']);
                }
            }
        }

        // Si nous sommes dans un clone Git, vérifier le commit courant
        $gitHead = ROOT . '.git' . DIRECTORY_SEPARATOR . 'HEAD';
        if (is_file($gitHead)) {
            $headContent = trim((string) @file_get_contents($gitHead));
            if (str_starts_with($headContent, 'ref: ')) {
                $refFile = ROOT . '.git' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, substr($headContent, 5));
                if (is_file($refFile)) {
                    return trim((string) @file_get_contents($refFile));
                }
            } elseif (preg_match('/^[a-f0-9]{40}$/i', $headContent)) {
                return $headContent;
            }
        }

        return null;
    }

    /**
     * Enregistre le hash du commit appliqué localement
     */
    public function saveLocalCommit(string $commit): void
    {
        if (!is_dir(DATA_CORE_SETTINGS)) {
            @mkdir(DATA_CORE_SETTINGS, 0755, true);
        }
        $data = [
            'version' => defined('VERSION') ? VERSION : $this->lastVersion,
            'commit' => trim($commit),
            'updated_at' => time(),
        ];
        @file_put_contents(DATA_CORE_SETTINGS . 'core_patch.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    /**
     * Détermine si la prochaine mise à jour est un simple correctif/patch de commit
     */
    public function isSecurityPatch(): bool
    {
        $next = $this->getNextVersion();
        if ($next === false) {
            return false;
        }
        return (bool) preg_match('/^[a-f0-9]{7,40}$/i', (string) $next);
    }

    public function getNextVersion()
    {
        if (!$this->isReady || !$this->lastVersion) {
            return false;
        }

        $comparison = $this->compareVersions($this->lastVersion, VERSION);
        
        // 1. Nouvelle version formelle disponible (ex: 0.2.2 > 0.2.1)
        if ($comparison > 0) {
            if (isset($this->metaDatas[VERSION])) {
                return (string) $this->metaDatas[VERSION];
            }
            return (string) $this->lastVersion;
        }
        
        // 2. Correctif de sécurité continu (même version SemVer, nouveau commit sur main)
        if ($comparison === 0 && !empty($this->latestCommit)) {
            $localCommit = $this->getLocalCommit();
            if ($localCommit === null || strtolower(substr($localCommit, 0, 7)) !== strtolower(substr($this->latestCommit, 0, 7))) {
                return (string) $this->latestCommit;
            }
        }
        
        return false;
    }

    public function update()
    {
        $nextVersion = $this->getNextVersion();
        if ($nextVersion === false) {
            return false;
        }

        $isPatch = $this->isSecurityPatch();
        $targetDesc = $isPatch ? ('patch ' . substr((string) $nextVersion, 0, 7)) : ('v' . $nextVersion);
        logg("Begin update to $targetDesc", 'INFO');
        
        // Télécharger et extraire le ZIP complet de la version ou du commit
        $tempPath = $this->downloadAndExtractVersion($nextVersion);
        if ($tempPath === false) {
            logg('Failed to download or extract version ZIP', 'ERROR');
            return false;
        }
        $this->tempExtractPath = $tempPath;

        if (!$this->runBeforeChangeFiles($nextVersion)) {
            logg('Update aborted by before-hook', 'ERROR');
            $this->cleanupTempDirectory();
            return false;
        }

        // Tenter de récupérer le manifest des fichiers s'il existe
        $rawFiles = $this->getRemoteFileContent($this->resolveApiBaseUrl() . 'repository/api/core/files?version=' . rawurlencode((string) $nextVersion), 'INFO');
        $files = $rawFiles ? json_decode($rawFiles, true) : null;

        if (is_array($files) && (isset($files['M']) || isset($files['A']) || isset($files['D']))) {
            foreach ((array) ($files['M'] ?? []) as $fileArray) {
                $this->processModify($fileArray, $nextVersion);
            }
            foreach ((array) ($files['A'] ?? []) as $fileArray) {
                $this->processAdd($fileArray, $nextVersion);
            }
            foreach ((array) ($files['D'] ?? []) as $fileArray) {
                $this->processDelete($fileArray);
            }
        } else {
            // Pas de manifest (patch continu ou archive complète) : copie récursive protégée
            $this->applyExtractedArchive($this->tempExtractPath);
        }
        
        if (!$this->runAfterChangeFiles($nextVersion)) {
            logg('Update after-hook reported warnings', 'WARNING');
        }

        // Enregistrer le commit appliqué
        if ($isPatch) {
            $this->saveLocalCommit((string) $nextVersion);
        } elseif (!empty($this->latestCommit)) {
            $this->saveLocalCommit((string) $this->latestCommit);
        }
        
        // Nettoyage automatique des anciens dossiers obsolètes (plugin_back, scripts, docs)
        $this->cleanupLegacyDirectories();

        // Nettoyer le dossier temporaire
        $this->cleanupTempDirectory();
        
        logg("End update to $targetDesc", 'INFO');
        return true;
    }

    /**
     * Purge automatiquement les dossiers obsolètes de développement ou de backup
     */
    protected function cleanupLegacyDirectories(): void
    {
        $obsoleteDirs = [
            ROOT . 'plugin_back',
            ROOT . 'scripts',
            ROOT . 'docs',
        ];
        foreach ($obsoleteDirs as $dir) {
            if (is_dir($dir)) {
                \Utils\Util::delTree($dir);
                logg("Cleaned up obsolete directory: $dir", 'INFO');
            }
        }
    }

    /**
     * Applique les fichiers de l'archive extraite vers ROOT en protégeant les dossiers critiques
     */
    protected function applyExtractedArchive(string $sourceDir): void
    {
        $sourceDir = rtrim($sourceDir, '/') . '/';
        $targetDir = rtrim(ROOT, '/') . '/';

        $protectedPaths = [
            'data',
            'config',
            '.git',
            '.github',
            '.env',
            'install.php',
            'install',
        ];

        $this->copyDirectoryRecursive($sourceDir, $targetDir, $protectedPaths);
    }

    protected function copyDirectoryRecursive(string $src, string $dst, array $excludes = []): void
    {
        if (!is_dir($src)) {
            return;
        }
        if (!is_dir($dst)) {
            @mkdir($dst, 0775, true);
        }

        $items = scandir($src);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (in_array($item, $excludes, true)) {
                continue;
            }

            $srcPath = $src . $item;
            $dstPath = $dst . $item;

            if (is_dir($srcPath)) {
                $this->copyDirectoryRecursive($srcPath, $dstPath, []);
            } else {
                $this->createDirectories($dstPath);
                @copy($srcPath, $dstPath);
            }
        }
    }

    public function clearCache()
    {
        @unlink(DATA_CORE_SETTINGS . 'cache.json');
    }

    protected function rewritePathFile($filename, $ignoreExist = false)
    {
        if (substr($filename, 0, 7) === 'plugin/') {
            return $this->treatPlugin($filename, $ignoreExist);
        } elseif (substr($filename, 0, 6) === 'theme/') {
            return $this->treatTheme($filename);
        } elseif (substr($filename, 0, 7) === 'common/') {
            return $this->treatCommon($filename);
        } else {
            return ROOT . $filename;
        }
    }

    /**
     * Télécharge le ZIP complet de la version depuis GitHub et l'extrait
     * @param string $version Version à télécharger (ex: "1.0.1")
     * @return string|false Chemin du dossier temporaire d'extraction ou false en cas d'erreur
     */
    protected function downloadAndExtractVersion($version)
    {
        $zipUrl = $this->resolveApiBaseUrl() . 'repository/api/core/download?version=' . rawurlencode(ltrim((string) $version, 'vV'));
        $safeVersion = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) $version);
        $tempDir = CACHE . 'update_temp_' . $safeVersion . '_' . time() . '/';
        
        // Créer le dossier temporaire
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0775, true)) {
                logg("Cannot create temp directory: $tempDir", 'ERROR');
                return false;
            }
        }

        // Télécharger le ZIP
        logg("Downloading ZIP for version $version...", 'INFO');
        $zipFile = $tempDir . 'version.zip';
        
        $zipContent = $this->downloadFile($zipUrl);
        if ($zipContent === false) {
            logg("Failed to download ZIP from $zipUrl", 'ERROR');
            $this->rrmdir($tempDir);
            return false;
        }
        
        // Sauvegarder le ZIP
        if (@file_put_contents($zipFile, $zipContent) === false) {
            logg("Cannot write ZIP file: $zipFile", 'ERROR');
            $this->rrmdir($tempDir);
            return false;
        }

        // Extraire le ZIP
        logg("Extracting ZIP...", 'INFO');
        $extractPath = $tempDir . 'extracted/';
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0775, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== TRUE) {
            logg("Cannot open ZIP file: $zipFile", 'ERROR');
            $this->rrmdir($tempDir);
            return false;
        }

        $zip->extractTo($extractPath);
        $zip->close();
        unlink($zipFile); // Supprimer le ZIP après extraction

        // GitHub ajoute un préfixe au nom du dossier (ex: NemStudio18-299koEVO-abc123/)
        // Trouver le dossier réel
        $extractedDirs = array_filter(glob($extractPath . '*'), 'is_dir');
        if (empty($extractedDirs)) {
            logg("No directory found in extracted ZIP", 'ERROR');
            $this->rrmdir($tempDir);
            return false;
        }
        
        $actualExtractPath = reset($extractedDirs) . '/';
        logg("ZIP extracted to: $actualExtractPath", 'INFO');
        
        return $actualExtractPath;
    }

    /**
     * Télécharge un fichier depuis une URL
     * @param string $url URL du fichier à télécharger
     * @return string|false Contenu du fichier ou false en cas d'erreur
     */
    protected function downloadFile($url)
    {
        // Utiliser file_get_contents avec un contexte pour gérer les timeouts
        $context = stream_context_create([
            'http' => [
                'timeout' => 300, // 5 minutes pour les gros fichiers
                'user_agent' => '299KoEVO-Updater/1.0',
                'follow_location' => true,
                'max_redirects' => 5
            ]
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            // Essayer avec cURL si disponible
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 300);
                curl_setopt($ch, CURLOPT_USERAGENT, '299KoEVO-Updater/1.0');
                $content = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200 || $content === false) {
                    logg("Failed to download file from $url (HTTP $httpCode)", 'ERROR');
                    return false;
                }
            } else {
                logg("Failed to download file from $url", 'ERROR');
                return false;
            }
        }
        
        return $content;
    }

    /**
     * Supprime récursivement un dossier et son contenu
     * @param string $dir Chemin du dossier à supprimer
     */
    protected function rrmdir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * Nettoie le dossier temporaire d'extraction
     */
    protected function cleanupTempDirectory()
    {
        if ($this->tempExtractPath !== null) {
            // Remonter au dossier parent (update_temp_xxx/)
            $tempDir = dirname(dirname($this->tempExtractPath)) . '/';
            if (is_dir($tempDir)) {
                logg("Cleaning up temp directory: $tempDir", 'INFO');
                $this->rrmdir($tempDir);
            }
            $this->tempExtractPath = null;
        }
    }

    protected function treatPlugin($filename, $ignoreExist)
    {
        if ($filename === 'plugin/.gitkeep') {
            return PLUGINS . '.gitkeep';
        }
        if (!preg_match('/^plugin\/([a-zA-Z0-9_-]+)\/(.*)$/i', $filename, $matches)) {
            return false;
        }
        $plugin = $matches[1];
        if (is_dir(PLUGINS . $plugin) === false && $ignoreExist === false) {
            return false;
        }
        return PLUGINS . $plugin . '/' . $matches[2];
    }

    protected function treatTheme($filename)
    {
        // Seul le thème 'default' est géré par les mises à jour du Core
        if (!str_starts_with($filename, 'theme/default/')) {
            return false;
        }
        preg_match('/^theme\/(.*)$/i', $filename, $matches);
        return THEMES . $matches[1];
    }

    protected function treatCommon($filename)
    {
        preg_match('/^common\/(.*)$/i', $filename, $matches);
        return COMMON . $matches[1];
    }

    protected function processModify($file, $version)
    {
        $localFileName = $this->rewritePathFile($file);
        if ($localFileName === false) {
            return;
        }
        
        // Copier depuis le dossier temporaire au lieu de télécharger
        $sourceFile = $this->tempExtractPath . $file;
        if (!file_exists($sourceFile)) {
            logg("Source file not found in extracted ZIP: $sourceFile", 'ERROR');
            return false;
        }
        
        $this->createDirectories($localFileName);
        if (@copy($sourceFile, $localFileName)) {
            logg("$localFileName was modified", 'INFO');
            return true;
        }
        logg("unable to write $localFileName", 'ERROR');
        return false;
    }

    protected function processAdd($file, $version)
    {
        $localFileName = $this->rewritePathFile($file, true);
        if ($localFileName === false) {
            return true;
        }
        
        // Copier depuis le dossier temporaire au lieu de télécharger
        $sourceFile = $this->tempExtractPath . $file;
        if (!file_exists($sourceFile)) {
            logg("Source file not found in extracted ZIP: $sourceFile", 'ERROR');
            return false;
        }
        
        $this->createDirectories($localFileName);
        if (@copy($sourceFile, $localFileName)) {
            logg("file $localFileName Added", 'INFO');
            return true;
        }
        logg("unable to write $localFileName", 'ERROR');
        return false;
    }

    protected function processDelete($file)
    {
        $localFileName = $this->rewritePathFile($file);
        if ($localFileName === false) {
            return;
        }
        if (@unlink($localFileName)) {
            logg("file $localFileName Deleted");
            $this->deleteEmptydirectory($localFileName);
            return true;
        }
        logg("unable to delete $localFileName", 'ERROR');
        return false;
    }

    protected function createDirectories($pathFile)
    {
        $arrPath = explode('/', $pathFile);
        array_pop($arrPath);
        $path = '';
        foreach ($arrPath as $dir) {
            $path .= $dir . '/';
        }
        if (!is_dir($path)) {
            if (mkdir($path, 0775, true)) {
                logg("$path folder has been created");
            } else {
                logg("Impossible to create $path directory", 'ERROR');
            }
        }
    }

    protected function deleteEmptydirectory($pathFile)
    {
        $arrPath = explode('/', $pathFile);
        array_pop($arrPath);
        $path = '';
        foreach ($arrPath as $dir) {
            $path .= $dir . '/';
        }
        if (is_dir($path) && !(new \FilesystemIterator($path))->valid()) {
            if (rmdir($path)) {
                logg("$path (empty folder) has been deleted");
            } else {
                logg("Impossible to delete $path directory", 'ERROR');
            }
        }
    }

    protected function getRemoteFileContent($remoteFileUrl, $severity = 'INFO')
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($remoteFileUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => '299KoEVO-Updater/1.0',
            ]);
            $content = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode === 404) {
                logg("Remote file $remoteFileUrl dont exist", $severity);
                return false;
            }
            if ($content === false || $httpCode >= 300 || $content === '404: Not Found') {
                logg("Cant open $remoteFileUrl (HTTP $httpCode)", $severity);
                return false;
            }
            return $content;
        }
        $headers = @get_headers($remoteFileUrl);
        if (!$headers || strpos($headers[0], '404') !== false) {
            logg("Remote file $remoteFileUrl dont exist", $severity);
            return false;
        }
        $handle = @fopen($remoteFileUrl, 'r');
        if (!$handle) {
            logg("Cant open $remoteFileUrl", $severity);
            return false;
        }
        $content = stream_get_contents($handle);
        fclose($handle);
        if ($content === '404: Not Found') {
            logg("Remote file $remoteFileUrl dont exist", $severity);
            return false;
        }
        return $content;
    }

    protected function runBeforeChangeFiles($nextVersion)
    {
        return $this->runChangeHook((string) $nextVersion, 'before');
    }

    protected function runAfterChangeFiles($nextVersion)
    {
        return $this->runChangeHook((string) $nextVersion, 'after');
    }

    private function runChangeHook(string $nextVersion, string $phase): bool
    {
        $remoteFile = $this->resolveApiBaseUrl() . 'repository/api/core/hook?version=' . rawurlencode($nextVersion) . '&name=' . rawurlencode($phase);
        $content = $this->getRemoteFileContent($remoteFile);
        if ($content === false) {
            return true;
        }
        if (!is_dir(DATA_CORE_SETTINGS)) {
            @mkdir(DATA_CORE_SETTINGS, 0755, true);
        }
        $tmpFile = DATA_CORE_SETTINGS . 'tmp_' . $phase . 'ChangeFiles.php';
        if (@file_put_contents($tmpFile, $content, LOCK_EX) !== false) {
            $success = require_once $tmpFile;
            @unlink($tmpFile);
            return $success !== false;
        }
        return false;
    }
}


