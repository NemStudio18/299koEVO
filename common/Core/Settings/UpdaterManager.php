<?php

namespace Core\Settings;

defined('ROOT') or exit('Access denied!');

class UpdaterManager
{
    public $lastVersion;
    public $nextVersion;
    protected $metaDatas;
    public bool $isReady;
    protected $tempExtractPath = null;
    const REMOTE = 'https://raw.githubusercontent.com/NemStudio18/299koEVO_Versions/main/';
    const MAIN_REPO = 'https://api.github.com/repos/NemStudio18/299koEVO/zipball/';

    public function __construct()
    {
        if (!ini_get('allow_url_fopen')) {
            logg("Can't get remotes files", 'INFO');
            $this->isReady = false;
            return;
        }

        $fileContent = $this->getRemoteFileContent(self::REMOTE . 'core/versions.json');

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

    public function getNextVersion()
    {
        if (!$this->isReady || !$this->lastVersion) {
            return false;
        }

        // Use semantic version comparison instead of string comparison
        $comparison = $this->compareVersions($this->lastVersion, VERSION);
        
        // Only return next version if remote version is greater than current
        if ($comparison > 0) {
            if (key_exists(VERSION, $this->metaDatas)) {
                return $this->metaDatas[VERSION];
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

        $rawFiles = $this->getRemoteFileContent(self::REMOTE . 'core/' . $nextVersion . '/files.json', 'ERROR');
        if ($rawFiles === false) {
            return false;
        }
        $files = json_decode($rawFiles, true);

        logg("Begin update to v$nextVersion", 'INFO');
        
        // Télécharger et extraire le ZIP complet de la version
        $tempPath = $this->downloadAndExtractVersion($nextVersion);
        if ($tempPath === false) {
            logg('Failed to download or extract version ZIP', 'ERROR');
            return false;
        }
        $this->tempExtractPath = $tempPath;

        if (!$this->runBeforeChangeFiles($nextVersion)) {
            logg('Update aborted', 'ERROR');
            $this->cleanupTempDirectory();
            return;
        }
        
        foreach ($files['M'] as $fileArray) {
            $this->processModify($fileArray, $nextVersion);
        }
        foreach ($files['A'] as $fileArray) {
            $this->processAdd($fileArray, $nextVersion);
        }
        foreach ($files['D'] as $fileArray) {
            $this->processDelete($fileArray);
        }
        
        if (!$this->runAfterChangeFiles($nextVersion)) {
            logg('Update may be not successfull', 'ERROR');
        }
        
        // Nettoyer le dossier temporaire
        $this->cleanupTempDirectory();
        
        logg("End update to v$nextVersion", 'INFO');
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
        $zipUrl = self::MAIN_REPO . 'v' . $version;
        $tempDir = CACHE . 'update_temp_' . $version . '_' . time() . '/';
        
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
        preg_match('/^plugin\/(\w+)\/(.*)$/i', $filename, $matches);
        $plugin = $matches[1];
        if (is_dir(PLUGINS . $plugin) === false && $ignoreExist === false) {
            return false;
        }
        return PLUGINS . $plugin . '/' . $matches[2];
    }

    protected function treatTheme($filename)
    {
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
        $headers = @get_headers($remoteFileUrl);
        if (!$headers || strpos($headers[0], '404') !== false) {
            logg("Remote file $remoteFileUrl dont exist", $severity);
            return false;
        }
        $handle = @fopen($remoteFileUrl, 'r');
        if (!$handle) {
            logg("Cant open $remoteFileUrl", $severity);
            return false;
        } else {
            $content = stream_get_contents($handle);
            fclose($handle);
        }
        if ($content === '404: Not Found') {
            logg("Remote file $remoteFileUrl dont exist", $severity);
            return false;
        }
        return $content;
    }

    protected function runBeforeChangeFiles($nextVersion)
    {
        $remoteFile = self::REMOTE . 'core/' . $nextVersion . '/_beforeChangeFiles.php';
        $content = $this->getRemoteFileContent($remoteFile);
        if ($content === false) {
            return;
        }
        $tmpFile = PLUGINS . 'configmanager' . '/tmp_beforeChangeFiles.php';
        if (@file_put_contents($tmpFile, $content, LOCK_EX)) {
            $success = require_once $tmpFile;
            @unlink($tmpFile);
            return $success;
        }
        return false;
    }

    protected function runAfterChangeFiles($nextVersion)
    {
        $remoteFile = self::REMOTE . 'core/' . $nextVersion . '/_afterChangeFiles.php';
        $content = $this->getRemoteFileContent($remoteFile);
        if ($content === false) {
            return;
        }
        $tmpFile = PLUGINS . 'configmanager' . '/tmp_afterChangeFiles.php';
        if (@file_put_contents($tmpFile, $content, LOCK_EX)) {
            $success = require_once $tmpFile;
            @unlink($tmpFile);
            return $success;
        }
        return false;
    }
}


