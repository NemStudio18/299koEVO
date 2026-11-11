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
    public const MAX_UPLOAD_SIZE = 10 * 1024 * 1024; // 10 Mo

    public const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico',
        'pdf', 'zip', 'txt', 'md', 'csv', 'doc', 'docx', 'odt', 'xls', 'xlsx', 'ods',
        'mp3', 'ogg', 'mp4', 'webm',
    ];

    protected string $directory = '';

    protected array $subDir = [];

    protected array $subFiles = [];

    public function __construct(string $directory)
    {
        $this->directory = rtrim(str_replace('\\', '/', $directory), '/') . '/';
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0755, true);
        }
        $this->hydrateChildren();
    }

    protected function hydrateChildren(): void
    {
        $this->subDir = [];
        $this->subFiles = [];
        $fileList = glob($this->directory . '*');
        if ($fileList === false) {
            return;
        }
        foreach ($fileList as $path) {
            $name = str_replace($this->directory, '', str_replace('\\', '/', $path));
            if (is_dir($path)) {
                $this->subDir[$name] = new Folder($name, $this->directory);
            } else {
                $this->subFiles[$name] = new File($name, $this->directory);
            }
        }
        uksort($this->subDir, 'strnatcasecmp');
        uksort($this->subFiles, 'strnatcasecmp');
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

    /**
     * Handle a file upload inside the current directory, optimizing images to WebP when possible.
     *
     * @return array{success: bool, file: ?File, url: string, relUrl: string, error: string}
     */
    public function uploadAndGetFile(string $arrayName, array $allowedExtensions = self::ALLOWED_EXTENSIONS): array
    {
        if (!isset($_FILES[$arrayName]) || empty($_FILES[$arrayName]['name'])) {
            return ['success' => false, 'file' => null, 'url' => '', 'relUrl' => '', 'error' => 'no_file'];
        }

        $file = $_FILES[$arrayName];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'file' => null, 'url' => '', 'relUrl' => '', 'error' => 'upload_error_' . ($file['error'] ?? 'unknown')];
        }

        if (($file['size'] ?? 0) > self::MAX_UPLOAD_SIZE) {
            return ['success' => false, 'file' => null, 'url' => '', 'relUrl' => '', 'error' => 'size_error'];
        }

        $ext = mb_strtolower(Util::getFileExtension($file['name']));
        if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
            return ['success' => false, 'file' => null, 'url' => '', 'relUrl' => '', 'error' => 'extension_error'];
        }

        $baseName = Util::strToUrl(pathinfo($file['name'], PATHINFO_FILENAME));
        if ($baseName === '') {
            $baseName = 'fichier';
        }

        // 1. Optimisation automatique en WebP pour les images GD compatibles
        if (\Utils\ImageOptimizer::isOptimizableImage($file['tmp_name'])) {
            $optimizedName = \Utils\ImageOptimizer::optimizeUpload($file['tmp_name'], $this->directory, $baseName);
            if (is_string($optimizedName) && $optimizedName !== '') {
                $this->hydrateChildren();
                $fileObj = new File($optimizedName, $this->directory);
                return [
                    'success' => true,
                    'file' => $fileObj,
                    'url' => $fileObj->getUrl(),
                    'relUrl' => $fileObj->getRelUrl(),
                    'error' => '',
                ];
            }
        }

        // 2. Repli sécurisé via Util::uploadFile (vérification MIME + déplacement)
        $targetBase = $baseName . '-' . time();
        $res = Util::uploadFile($arrayName, $this->directory, $targetBase, [
            'extensions' => $allowedExtensions,
            'size' => self::MAX_UPLOAD_SIZE,
        ]);

        if ($res !== 'success') {
            return ['success' => false, 'file' => null, 'url' => '', 'relUrl' => '', 'error' => (string)$res];
        }

        $targetName = $targetBase . '.' . $ext;
        $this->hydrateChildren();
        $fileObj = new File($targetName, $this->directory);

        return [
            'success' => true,
            'file' => $fileObj,
            'url' => $fileObj->getUrl(),
            'relUrl' => $fileObj->getRelUrl(),
            'error' => '',
        ];
    }

    public function uploadFile(string $arrayName): bool|string
    {
        $result = $this->uploadAndGetFile($arrayName);
        return $result['success'] ? $result['url'] : false;
    }

    public function deleteFile(string $filename): bool
    {
        $filename = basename($filename);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return false;
        }
        if (isset($this->subFiles[$filename]) && $this->subFiles[$filename]->delete()) {
            unset($this->subFiles[$filename]);
            return true;
        }
        $target = $this->directory . $filename;
        if (is_file($target) && @unlink($target)) {
            $this->hydrateChildren();
            return true;
        }
        return false;
    }

    public function deleteFolder(string $foldername): bool
    {
        $foldername = basename($foldername);
        if ($foldername === '' || $foldername === '.' || $foldername === '..') {
            return false;
        }
        $target = $this->directory . $foldername;
        if (!is_dir($target)) {
            unset($this->subDir[$foldername]);
            return false;
        }

        $folder = new Folder($foldername, $this->directory);
        if ($folder->delete()) {
            unset($this->subDir[$foldername]);
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


