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
        if (isset($this->subDir[$foldername])) {
            $error = $this->subDir[$foldername]->delete();
            if (!$error) {
                unset($this->subDir[$foldername]);
                return true;
            }
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
}


