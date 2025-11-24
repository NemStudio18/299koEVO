<?php

namespace Core\Media;

use Core\Media\FileManager;

/**
 * Folder wrapper used by the media service.
 */
defined('ROOT') or exit('Access denied!');

class Folder
{
    public string $name = '';

    protected string $directory = '';

    public function __construct(string $name, string $directory)
    {
        $this->name = trim($name, '/');
        $this->directory = rtrim($directory, '/') . '/';
    }

    public function delete(): bool
    {
        $target = $this->directory . $this->name;
        if (!is_dir($target)) {
            return false;
        }
        $manager = new FileManager($target);

        $error = $manager->deleteAllFiles();
        if ($error) {
            return false;
        }

        $error = $manager->deleteAllFolders();
        if ($error) {
            return false;
        }

        return rmdir($target);
    }
}


