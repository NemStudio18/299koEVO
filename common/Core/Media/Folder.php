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
        
        // Use recursive directory deletion function
        return $this->rrmdir($target);
    }
    
    /**
     * Recursively delete a directory and all its contents
     */
    private function rrmdir(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                if (!$this->rrmdir($path)) {
                    return false;
                }
            } else {
                if (!@unlink($path)) {
                    return false;
                }
            }
        }
        
        return @rmdir($dir);
    }
}


