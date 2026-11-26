<?php

namespace Core\Storage;

use Utils\Util;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class Zip
{

    public string $filename;

    public string $filesize;

    public function __construct($filename)
    {
        $this->filename = $filename;
        if (!file_exists($this->filename)) {
            throw new \Exception("File not found: $filename");
        }
        $this->filesize = $this->humanFilesize(filesize($filename));
    }

    protected function humanFilesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = (int) floor((strlen((string)$bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    public static function createZipFromFolder(string $folder, string $filename, array $ignoreRegex = []): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }
        $realRoot = rtrim(str_replace('\\', '/', realpath($folder) ?: $folder), '/');
        $itemsToSave = Util::scanDirRecursive($folder);

        foreach ($itemsToSave['dir'] as $dir) {
            $normalizedDir = str_replace('\\', '/', $dir);
            $localDir = trim(substr($normalizedDir, strlen($realRoot)), '/');
            if ($localDir === '' || $localDir === '.' || $localDir === '..' || str_ends_with($localDir, '/.') || str_ends_with($localDir, '/..')) {
                continue;
            }
            foreach ($ignoreRegex as $regex) {
                if (preg_match($regex, $localDir) || preg_match($regex, $normalizedDir)) {
                    continue 2;
                }
            }
            $zip->addEmptyDir($localDir);
        }
        foreach ($itemsToSave['file'] as $file) {
            $normalizedFile = str_replace('\\', '/', $file);
            $localFile = trim(substr($normalizedFile, strlen($realRoot)), '/');
            if ($localFile === '') {
                continue;
            }
            foreach ($ignoreRegex as $regex) {
                if (preg_match($regex, $localFile) || preg_match($regex, $normalizedFile)) {
                    continue 2;
                }
            }
            $zip->addFile($file, $localFile);
        }
        $zip->close();
        return true;
    }

    public function extractTo(string $targetDir): bool
    {
        if (!class_exists('\ZipArchive') || !file_exists($this->filename)) {
            return false;
        }
        $zip = new \ZipArchive();
        if ($zip->open($this->filename) !== true) {
            return false;
        }
        $targetDir = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            // Sécurité anti-path traversal
            if (str_contains($filename, '..') || str_starts_with($filename, '/') || str_starts_with($filename, '\\')) {
                continue;
            }
            $targetPath = $targetDir . $filename;
            if (str_ends_with($filename, '/')) {
                if (!is_dir($targetPath)) {
                    @mkdir($targetPath, 0755, true);
                }
            } else {
                $dir = dirname($targetPath);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    file_put_contents($targetPath, $content);
                }
            }
        }
        $zip->close();
        return true;
    }
}