<?php

namespace Core\Settings;

use Core\Logger;
use Core\Core;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class UpdaterManualManager
{
    protected Logger $logger;
    public bool $isReady = false;
    protected string $updatePath = ROOT . 'update' . DS;

    public function __construct()
    {
        $this->logger = Core::getInstance()->getLogger();
    }

    public function getNextVersion(): string
    {
        $versions = Util::readJsonFile($this->updatePath . 'version.json');
        return $versions['newVersion'];
    }

    public function check(): bool
    {
        if (!$this->checkVersion()) {
            return false;
        }
        if (!$this->checkRequiredFiles()) {
            return false;
        }
        $this->isReady = true;
        return true;
    }

    public function update(): bool
    {
        if (!$this->isReady) {
            return false;
        }
        if (file_exists($this->updatePath . '_beforeChangeFiles.php')) {
            require_once $this->updatePath . '_beforeChangeFiles.php';
        }
        $this->processFiles();
        $this->deleteFiles();
        if (file_exists($this->updatePath . '_afterChangeFiles.php')) {
            require_once $this->updatePath . '_afterChangeFiles.php';
        }
        $this->clearUpdate();
        return true;
    }

    protected function processFiles(): void
    {
        $folder = $this->updatePath . 'files';
        $itemsToSave = Util::scanDirRecursive($folder);
        $dirs = [];
        foreach ($itemsToSave['dir'] as $dir) {
            $dirs[] = str_replace($folder, '', $dir);
        }

        foreach ($dirs as $dir) {
            if (!is_dir(ROOT . $dir)) {
                mkdir(ROOT . $dir, 0755, true);
            }
        }
        foreach ($itemsToSave['file'] as $file) {
            copy($file, ROOT . str_replace($folder, '', $file));
        }
    }

    protected function deleteFiles(): void
    {
        $files = Util::readJsonFile($this->updatePath . 'deleted.json');
        foreach ($files as $file) {
            @unlink(ROOT . $file);
        }
    }

    protected function clearUpdate(): void
    {
        Util::delTree($this->updatePath);
    }

    protected function checkVersion(): bool
    {
        if (!is_file($this->updatePath . 'version.json')) {
            $this->logger->error('Updater: Version file not found');
            return false;
        }
        $versions = Util::readJsonFile($this->updatePath . 'version.json');
        $actualVersion = $versions['version'];
        if (!version_compare($actualVersion, VERSION, '=')) {
            $this->logger->error('Updater: Update folder dont match actual version');
            return false;
        }
        return true;
    }

    protected function checkRequiredFiles(): bool
    {
        if (!is_dir($this->updatePath . 'files')) {
            $this->logger->error('Updater: Folder files not found');
            return false;
        }
        if (!is_file($this->updatePath . 'deleted.json')) {
            $this->logger->error('Updater: Deleted file not found');
            return false;
        }
        return true;
    }
}


