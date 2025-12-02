<?php

namespace Core\Settings\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Core\Settings\ConfigManagerBackupsManager;
use Core\Storage\Zip;
use Core\Lang;
use Utils\Show;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class ConfigManagerBackupAdminController extends AdminController {

    public function home(): AdminResponse {

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('configmanager', 'backup');
        $response->setTitle(Lang::get('configmanager-backup-desc'));
        $backups = ConfigManagerBackupsManager::getAll();
        $tpl->set('backups', $backups);
        $tpl->set('token', $this->user->token);
        $tpl->set('emptyBackups', empty($backups));
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function create($token) {
        if (!$this->user->isAuthorized()) {
            $this->core->redirect($this->router->generate('configmanager-backup'));
        }
        $dir = $this->core->settings()->backupDirectory();
        $filename = $dir . 'backup-' . date('Y-m-d-H-i-s') . '.zip';
        $ignore = [
            '/.*backup-.*\.zip/i',
            '/.*\.git.*/i'
        ];
        $result = Zip::createZipFromFolder(ROOT, $filename, $ignore);
        $actor = $this->user->email ?? 'unknown';
        if ($result) {
            $this->logger->info('Config backup created by ' . $actor . ' at ' . $filename);
            Show::msg(Lang::get('configmanager-backup-done-success'), 'success');
        } else {
            $this->logger->error('Config backup creation failed for ' . $actor);
            Show::msg(Lang::get('configmanager-backup-done-error'), 'error');
        }
        $this->core->redirect($this->router->generate('configmanager-backup'));
    }

    public function download($token, $timestamp) {
        if (!$this->user->isAuthorized()) {
            $this->core->redirect($this->router->generate('configmanager-backup'));
        }
        $dir = $this->core->settings()->backupDirectory();
        $filename = $dir . 'backup-' . date('Y-m-d-H-i-s', $timestamp) . '.zip';
        header('Content-Type: application/zip');
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"" . basename($filename) . "\""); 
        readfile($filename); 
    }

    public function delete()
    {
        $response = new ApiResponse();
        if (!$this->user->isAuthorized()) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        $backups = ConfigManagerBackupsManager::getAll();
        $id = (int) $this->jsonData['timestamp'] ?? 0;
        $actor = $this->user->email ?? 'unknown';
        $res = false;
        if (isset($backups[$id])) {
            $res = $backups[$id]->delete();
        } else {
            $this->logger->warning('Config backup deletion failed for ' . $actor . ' - backup not found (' . $id . ')');
            $response->status = ApiResponse::STATUS_NOT_FOUND;
            return $response;
        }
        if ($res) {
            $this->logger->info('Config backup deleted by ' . $actor . ' timestamp ' . $id);
            $response->status = ApiResponse::STATUS_NO_CONTENT;
        } else {
            $this->logger->warning('Config backup deletion failed for ' . $actor . ' timestamp ' . $id);
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
        }
        return $response;
    }
        
}