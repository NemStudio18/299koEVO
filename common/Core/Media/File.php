<?php

namespace Core\Media;

use Utils\Util;

/**
 * Core representation of a media file.
 */
defined('ROOT') or exit('Access denied!');

class File
{
    public string $name = '';

    protected string $directory = '';

    public function __construct(string $name, string $directory)
    {
        $this->name = ltrim($name, '/');
        $this->directory = rtrim(str_replace('\\', '/', $directory), '/') . '/';
    }

    public function getUrl(): string
    {
        return Util::urlBuild($this->directory . $this->name);
    }

    /**
     * Return a web-root-relative URL (e.g. /data/upload/files/photo.webp),
     * stripping the filesystem ROOT path.
     */
    public function getRelUrl(): string
    {
        $fullPath = str_replace('\\', '/', $this->directory . $this->name);
        $root = str_replace('\\', '/', ROOT);
        if ($root !== '' && str_starts_with($fullPath, $root)) {
            $rel = substr($fullPath, strlen($root));
            return '/' . ltrim($rel, '/');
        }
        $realRoot = realpath(ROOT);
        $realPath = realpath($this->directory . $this->name);
        if ($realRoot && $realPath) {
            $normRoot = rtrim(str_replace('\\', '/', $realRoot), '/') . '/';
            $normPath = str_replace('\\', '/', $realPath);
            if (str_starts_with($normPath, $normRoot)) {
                return '/' . ltrim(substr($normPath, strlen($normRoot)), '/');
            }
        }
        return '/' . ltrim($fullPath, '/');
    }

    public function getExtension(): string
    {
        return mb_strtolower(Util::getFileExtension($this->name));
    }

    public function isPicture(): bool
    {
        return in_array($this->getExtension(), ['gif', 'jpg', 'jpeg', 'png', 'bmp', 'webp', 'svg'], true);
    }

    public function getIconClass(): string
    {
        return match ($this->getExtension()) {
            'pdf' => 'fa-regular fa-file-pdf',
            'zip', 'tar', 'gz', '7z', 'rar' => 'fa-regular fa-file-zipper',
            'doc', 'docx', 'odt' => 'fa-regular fa-file-word',
            'xls', 'xlsx', 'ods', 'csv' => 'fa-regular fa-file-excel',
            'mp4', 'webm', 'mov', 'avi' => 'fa-regular fa-file-video',
            'mp3', 'ogg', 'wav' => 'fa-regular fa-file-audio',
            'txt', 'md', 'json', 'xml' => 'fa-regular fa-file-code',
            default => 'fa-regular fa-file-lines',
        };
    }

    public function getFormattedSize(): string
    {
        $bytes = $this->getSize();
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return ($i === 0 ? $bytes : round($bytes, 1)) . ' ' . $units[$i];
    }

    public function getFileMTime(): int|false
    {
        return filemtime($this->directory . $this->name);
    }

    public function delete(): bool
    {
        return @unlink($this->directory . $this->name);
    }

    public function getSize(): int
    {
        $path = $this->directory . $this->name;
        $size = filesize($path);
        return ($size === false) ? 0 : $size;
    }
}


