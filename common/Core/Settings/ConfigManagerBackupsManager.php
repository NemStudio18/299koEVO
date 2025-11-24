<?php

namespace Core\Settings;

use Core\Settings\ConfigManagerBackup;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class ConfigManagerBackupsManager
{
    public static function getAll(): array
    {
        $backups = [];
        $backupFiles = [];
        $directory = DATA_CORE_SETTINGS;
        if (!is_dir($directory)) {
            return [];
        }
        $files = scandir($directory);
        foreach ($files as $file) {
            if (preg_match('/backup-.*\.zip/i', $file)) {
                $backupFiles[] = $file;
            }
        }
        foreach ($backupFiles as $file) {
            $obj = new ConfigManagerBackup($file);
            $timestamp = Util::getTimestampFromDate($obj->date);
            $backups[$timestamp] = $obj;
        }
        krsort($backups, SORT_NUMERIC);
        return $backups;
    }
}


