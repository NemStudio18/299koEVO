<?php

namespace Core\Media;

use Core\Media\File;
use Core\Media\Folder;
use Utils\Util;

/**
 * FileManager manipulates folders/files under the upload root.
 */
defined('ROOT') or exit('Access denied!');

class FileManager
{
    protected string $directory = '';

    protected array $subDir = [];

    protected array $subFiles = [];

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/') . '/';
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0755, true);
        }
        $this->hydrateChildren();
    }

    protected function hydrateChildren(): void
    {
        $fileList = glob($this->directory . '*');
        if ($fileList === false) {
            return;
        }
        foreach ($fileList as $path) {
            $name = str_replace($this->directory, '', $path);
            if (is_dir($path)) {
                $this->subDir[$name] = new Folder($name, $this->directory);
            } else {
                $this->subFiles[$name] = new File($name, $this->directory);
            }
        }
    }

    public function getFolders(): array
    {
        return $this->subDir;
    }

    public function getFiles(): array
    {
        return $this->subFiles;
    }

    public function getFilesCount(): int
    {
        return count($this->subFiles);
    }

    public function getFoldersCount(): int
    {
        return count($this->subDir);
    }

    public function getPicturesCount(): int
    {
        $count = 0;
        foreach ($this->subFiles as $file) {
            if ($file->isPicture()) {
                $count++;
            }
        }
        return $count;
    }

    public function getTotalSize(): int
    {
        $size = 0;
        foreach ($this->subFiles as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    public function getStats(): array
    {
        $size = $this->getTotalSize();
        return [
            'files' => $this->getFilesCount(),
            'folders' => $this->getFoldersCount(),
            'pictures' => $this->getPicturesCount(),
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
        ];
    }

    public function uploadFile(string $arrayName): bool|string
    {
        if (!isset($_FILES[$arrayName])) {
            return false;
        }
        $file = $_FILES[$arrayName];
        $fileName = Util::strToUrl(pathinfo($file['name'], PATHINFO_FILENAME));
        $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetName = $fileName . '-' . time() . '.' . $fileExt;
        if (move_uploaded_file($file['tmp_name'], $this->directory . $targetName)) {
            $file = new File($targetName, $this->directory);
            return $file->getUrl();
        }

        return false;
    }

    public function deleteFile(string $filename): bool
    {
        if (isset($this->subFiles[$filename])) {
            $error = $this->subFiles[$filename]->delete();
            if (!$error) {
                unset($this->subFiles[$filename]);
                return true;
            }
        }
        return false;
    }

    public function deleteFolder(string $foldername): bool
    {
        // Always check filesystem directly for reliability
        $target = $this->directory . $foldername;
        if (!is_dir($target)) {
            // Folder doesn't exist
            if (isset($this->subDir[$foldername])) {
                unset($this->subDir[$foldername]);
            }
            return false;
        }
        
        // Create Folder object and delete it
        $folder = new Folder($foldername, $this->directory);
        $success = $folder->delete();
        
        if ($success) {
            // Remove from subDir if it was there
            if (isset($this->subDir[$foldername])) {
                unset($this->subDir[$foldername]);
            }
            // Reload children to sync with filesystem
            $this->hydrateChildren();
            return true;
        }
        
        return false;
    }

    public function deleteAllFiles(): bool
    {
        $error = false;
        foreach ($this->subFiles as $file) {
            if (!$file->delete()) {
                $error = true;
            }
        }
        return $error;
    }

    public function deleteAllFolders(): bool
    {
        $error = false;
        foreach ($this->subDir as $folder) {
            if (!$folder->delete()) {
                $error = true;
            }
        }
        return $error;
    }

    public function createFolder(string $name): bool
    {
        $safeName = Util::strToUrl($name);
        if ($safeName === '') {
            return false;
        }
        return @mkdir($this->directory . $safeName, 0755);
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $bytes = max(0, $bytes);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}


