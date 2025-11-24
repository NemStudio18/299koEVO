<?php

namespace Core\Settings;

use Core\Storage\Zip;
use Utils\Util;
use Core\Router\Router;
use Core\Auth\UsersManager;

defined('ROOT') or exit('Access denied!');

class ConfigManagerBackup extends Zip
{
    public string $filename;

    public string $date;

    public int $timestamp = 0;

    public string $url;

    public function __construct($filename)
    {
        $this->filename = DATA_CORE_SETTINGS . $filename;
        parent::__construct($this->filename);
        if (preg_match('/backup-(\d{4})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})/', $filename, $matches)) {
            $this->date = "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}";
            $this->timestamp = Util::getTimestampFromDate($this->date);
        } else {
            throw new Exception("Invalid backup filename: $filename");
        }
        $this->url = Router::getInstance()->generate('configmanager-dl-backup', [
            'token' => UsersManager::getCurrentUser()->token,
            'timestamp' => $this->timestamp
        ]);
    }

    public function delete()
    {
        return @unlink($this->filename);
    }
}


