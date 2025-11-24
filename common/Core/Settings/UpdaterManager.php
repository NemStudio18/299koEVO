<?php

namespace Core\Settings;

defined('ROOT') or exit('Access denied!');

class UpdaterManager
{
    public $lastVersion;
    public $nextVersion;
    protected $metaDatas;
    public bool $isReady;
    const REMOTE = 'https://raw.githubusercontent.com/299Ko/';

    public function __construct()
    {
        if (!ini_get('allow_url_fopen')) {
            logg("Can't get remotes files", 'INFO');
            $this->isReady = false;
        }

        $fileContent = $this->getRemoteFileContent(self::REMOTE . 'versions/main/core/versions.json');

        if (!$fileContent) {
            $this->isReady = false;
        }

        $file = json_decode($fileContent, true);
        $this->lastVersion = $file['last_version'];
        $this->metaDatas = $file;
        $this->isReady = true;
    }

    public function getNextVersion()
    {
        if ($this->lastVersion > VERSION) {
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

        $rawFiles = $this->getRemoteFileContent(self::REMOTE . 'versions/main/core/' . $nextVersion . '/files.json', 'ERROR');
        if ($rawFiles === false) {
            return false;
        }
        $files = json_decode($rawFiles, true);

        logg("Begin update to v$nextVersion", 'INFO');
        if (!$this->runBeforeChangeFiles($nextVersion)) {
            logg('Update aborted', 'ERROR');
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

    protected function getRemoteFile($filename, $version)
    {
        return self::REMOTE . '299ko/v' . $version . '/' . $filename;
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
        $remoteFile = $this->getRemoteFile($file, $version);
        $content = $this->getRemoteFileContent($remoteFile, 'ERROR');
        if ($content === false) {
            return;
        }
        $this->createDirectories($localFileName);
        if (@file_put_contents($localFileName, $content, LOCK_EX)) {
            logg("$localFileName was modified", 'INFO');
            return true;
        }
        logg("unable to write $localFileName", 'ERROR');
        return false;
    }

    protected function processAdd($file, $version)
    {
        $localFileName = $this->rewritePathFile($file, true);
        $remoteFile = $this->getRemoteFile($file, $version);
        $content = $this->getRemoteFileContent($remoteFile, 'ERROR');
        if ($content === false) {
            return;
        }
        $this->createDirectories($localFileName);
        if (@file_put_contents($localFileName, $content, LOCK_EX)) {
            logg("file $localFileName Added");
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
        $remoteFile = self::REMOTE . 'versions/main/core/' . $nextVersion . '/_beforeChangeFiles.php';
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
        $remoteFile = self::REMOTE . 'versions/main/core/' . $nextVersion . '/_afterChangeFiles.php';
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


