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
        $this->directory = rtrim($directory, '/') . '/';
    }

    public function getUrl(): string
    {
        return Util::urlBuild($this->directory . $this->name);
    }

    public function getRelUrl(): string
    {
        $parts = explode('/', $this->directory);
        $dir = '';
        foreach ($parts as $part) {
            if ($part === '.' || $part === '..' || $part === '') {
                continue;
            }
            $dir .= $part . '/';
        }
        return $dir . $this->name;
    }

    public function isPicture(): bool
    {
        return in_array(Util::getFileExtension($this->name), ['gif', 'jpg', 'jpeg', 'png', 'bmp'], true);
    }

    public function getFileMTime(): int|false
    {
        return filemtime($this->directory . $this->name);
    }

    public function delete(): bool
    {
        return @unlink($this->directory . $this->name);
    }
}


