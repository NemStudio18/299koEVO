<?php

namespace Core\Settings\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Core\Settings\ConfigManagerBackupsManager;
use Core\Settings\ConfigManagerBackup;
use Core\Storage\Zip;
use Core\Lang;
use Utils\Show;

defined('ROOT') or exit('Access denied!');

class ConfigManagerBackupAdminController extends AdminController
{
    public function home(): AdminResponse
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('configmanager', 'backup');
        $response->setTitle(Lang::get('configmanager-backup-desc') ?: 'Sauvegardes & Restauration');
        $backups = ConfigManagerBackupsManager::getAll();
        $tpl->set('backups', $backups);
        $tpl->set('token', $this->user->token);
        $tpl->set('emptyBackups', empty($backups));
        $tpl->set('createBackupUrl', $this->router->generate('configmanager-create-backup', ['token' => $this->user->token]));
        $tpl->set('restoreBackupUrl', $this->router->generate('configmanager-restore-backup', ['token' => $this->user->token]));
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function create($token = '')
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            $this->core->redirect($this->router->generate('configmanager-backup'));
        }

        $scope = $_GET['scope'] ?? ($_POST['scope'] ?? 'all');
        if (!in_array($scope, ['all', 'data', 'code'], true)) {
            $scope = 'all';
        }

        $dir = $this->core->settings()->backupDirectory();
        $filename = $dir . 'backup-' . $scope . '-' . date('Y-m-d-H-i-s') . '.zip';

        $ignore = [
            '/.*backup-.*\.zip/i',
            '/.*\.git.*/i',
            '/.*\.cache.*/i',
            '/data\/cache(\/.*)?$/i',
        ];

        $targetFolder = ROOT;
        if ($scope === 'data') {
            $targetFolder = DATA;
        } elseif ($scope === 'code') {
            $ignore[] = '/^data\/.*/i';
        }

        $result = Zip::createZipFromFolder($targetFolder, $filename, $ignore);
        $actor = $this->user->email ?? 'unknown';

        if ($result) {
            $this->logger->info("Backup ($scope) created by $actor at $filename");
            Show::msg(Lang::get('configmanager-backup-done-success') ?: 'Sauvegarde créée avec succès.', 'success');
        } else {
            $this->logger->error("Backup ($scope) creation failed for $actor");
            Show::msg(Lang::get('configmanager-backup-done-error') ?: 'Échec de création de la sauvegarde.', 'error');
        }

        $this->core->redirect($this->router->generate('configmanager-backup'));
    }

    public function restore($token)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            Show::msg('Action non autorisée.', 'error');
            $this->core->redirect($this->router->generate('configmanager-backup'));
        }

        $fileParam = trim((string)($_POST['file'] ?? $_GET['file'] ?? ''));
        $backups = ConfigManagerBackupsManager::getAll();
        $targetBackup = null;

        foreach ($backups as $b) {
            if ($b->basename === $fileParam || (string)$b->timestamp === $fileParam) {
                $targetBackup = $b;
                break;
            }
        }

        if (!$targetBackup || !file_exists($targetBackup->filename)) {
            Show::msg('Fichier de sauvegarde introuvable.', 'error');
            $this->core->redirect($this->router->generate('configmanager-backup'));
        }

        $actor = $this->user->email ?? 'unknown';
        if ($targetBackup->restore()) {
            $this->logger->info("Backup restored by $actor from {$targetBackup->filename}");
            Show::msg("Restauration réussie depuis la sauvegarde du {$targetBackup->date}.", 'success');
        } else {
            $this->logger->error("Backup restoration failed for $actor from {$targetBackup->filename}");
            Show::msg("Échec de la restauration de la sauvegarde.", 'error');
        }

        $this->core->redirect($this->router->generate('configmanager-backup'));
    }

    public function download($token, $timestamp)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            $this->core->redirect($this->router->generate('configmanager-backup'));
        }

        $backups = ConfigManagerBackupsManager::getAll();
        $targetFile = null;
        foreach ($backups as $b) {
            if ($b->timestamp === (int)$timestamp || (string)$b->timestamp === (string)$timestamp) {
                $targetFile = $b->filename;
                break;
            }
        }

        if (!$targetFile || !file_exists($targetFile)) {
            $dir = $this->core->settings()->backupDirectory();
            $targetFile = $dir . 'backup-' . date('Y-m-d-H-i-s', (int)$timestamp) . '.zip';
        }

        if (!file_exists($targetFile)) {
            Show::msg('Sauvegarde introuvable.', 'error');
            $this->core->redirect($this->router->generate('configmanager-backup'));
        }

        header('Content-Type: application/zip');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($targetFile) . "\"");
        readfile($targetFile);
        exit;
    }

    public function delete()
    {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }

        $backups = ConfigManagerBackupsManager::getAll();
        $id = $this->jsonData['timestamp'] ?? $this->jsonData['file'] ?? '';
        $actor = $this->user->email ?? 'unknown';
        $res = false;

        foreach ($backups as $b) {
            if ($b->basename === (string)$id || $b->timestamp === (int)$id || (string)$b->timestamp === (string)$id) {
                $res = $b->delete();
                break;
            }
        }

        if ($res) {
            $this->logger->info('Config backup deleted by ' . $actor . ' file ' . $id);
            $response->status = ApiResponse::STATUS_NO_CONTENT;
        } else {
            $this->logger->warning('Config backup deletion failed for ' . $actor . ' file ' . $id);
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
        }
        return $response;
    }
}
