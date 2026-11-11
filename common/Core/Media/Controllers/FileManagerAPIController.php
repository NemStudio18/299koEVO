<?php

namespace Core\Media\Controllers;

use Core\Controllers\AdminController;
use Core\Media\FileManager;
use Core\Media\MediaService;
use Core\Responses\StringResponse;
use Core\Responses\AdminResponse;
use Core\Lang;
use Utils\Util;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class FileManagerAPIController extends AdminController
{

    protected ?string $dir = null;

    protected array $dirParts = [];

    protected string $fullDir = '';

    protected bool $ajaxView = false;

    protected bool $api = false;

    protected FileManager $filemanager;

    protected $editor = false;

    protected MediaService $media;

    public function __construct()
    {
        parent::__construct();
        $this->media = $this->core->media();
    }

    public function home()
    {
        return $this->render();
    }

    public function view()
    {
        // POST/REDIRECT/GET pattern to avoid resubmission on F5
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $folderToSee = $_POST['fmFolderToSee'] ?? '';
            $activeTab = $_POST['activeTab'] ?? '';

            // Build redirect URL with GET parameters
            // http_build_query() already encodes, so don't use urlencode() before
            $params = [];
            if ($folderToSee !== '') {
                $params['dir'] = $folderToSee;
            }
            if ($activeTab !== '') {
                $params['activeTab'] = $activeTab;
            }

            $redirectUrl = $this->router->generate('filemanager-view');
            if (!empty($params)) {
                $redirectUrl .= '?' . http_build_query($params);
            }

            $this->core->redirect($redirectUrl);
            return;
        }

        // GET request - render normally
        return $this->render();
    }

    public function upload($token)
    {
        header('Content-Type: application/json; charset=utf-8');
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            echo json_encode(['success' => 0, 'error' => 'forbidden']);
            die();
        }
        $this->getSentDir();
        $this->filemanager = new FileManager($this->fullDir);

        $fileKey = isset($_FILES['image']) ? 'image' : (isset($_FILES['file']) ? 'file' : '');
        if ($fileKey !== '' && !empty($_FILES[$fileKey]['name'])) {
            $result = $this->filemanager->uploadAndGetFile($fileKey);
            if ($result['success']) {
                echo json_encode([
                    'success' => 1,
                    'url' => $result['url'],
                    'relUrl' => $result['relUrl'],
                    'name' => $result['file']->name ?? '',
                ]);
                die();
            }
            echo json_encode(['success' => 0, 'error' => $result['error']]);
            die();
        }
        echo json_encode(['success' => 0, 'error' => 'no_file']);
        die();
    }

    public function uploadAPI($token)
    {
        header('Content-Type: application/json; charset=utf-8');
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé.']);
            die();
        }

        // Handle TinyMCE/API upload directly into the FileManager root so images remain manageable & WebP-optimized
        $key = key($_FILES);
        if (empty($key)) {
            http_response_code(400);
            echo json_encode(['error' => 'Aucun fichier envoyé.']);
            die();
        }

        $fm = new FileManager($this->media->getRootDir());
        $allowedImageExts = ['gif', 'jpg', 'png', 'ico', 'bmp', 'jpeg', 'webp'];
        $result = $fm->uploadAndGetFile((string)$key, $allowedImageExts);

        if ($result['success']) {
            echo json_encode(['location' => $result['relUrl']]);
            die();
        }

        http_response_code(400);
        echo json_encode(['error' => 'Échec de l’envoi : ' . $result['error']]);
        die();
    }

    public function delete($token)
    {
        header('Content-Type: application/json; charset=utf-8');
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            echo json_encode(['success' => false]);
            die();
        }
        $this->getSentDir();
        $this->filemanager = new FileManager($this->fullDir);
        if (isset($_POST['filename']) && $_POST['filename'] !== '') {
            $deleted = $this->filemanager->deleteFile((string)$_POST['filename']);
            echo json_encode(['success' => $deleted]);
            die();
        } elseif (isset($_POST['foldername']) && $_POST['foldername'] !== '') {
            $deleted = $this->filemanager->deleteFolder((string)$_POST['foldername']);
            echo json_encode(['success' => $deleted]);
            die();
        }
        echo json_encode(['success' => false]);
        die();
    }

    public function create($token)
    {
        header('Content-Type: application/json; charset=utf-8');
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            echo json_encode(['success' => false]);
            die();
        }
        $this->getSentDir();
        $this->filemanager = new FileManager($this->fullDir);
        $folderName = trim((string)($_POST['folderName'] ?? ''));
        $created = $folderName !== '' ? $this->filemanager->createFolder($folderName) : false;

        $response = ['success' => $created];
        if ($created) {
            $response['folderName'] = Util::strToUrl($folderName);
        }

        echo json_encode($response);
        die();
    }

    public function viewAjax()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            echo json_encode(['success' => false]);
            die();
        }
        $this->ajaxView = true;
        $rawEditor = trim((string)($_POST['editor'] ?? ''));
        $this->editor = ($rawEditor === '' || $rawEditor === 'false' || $rawEditor === '0') ? false : $rawEditor;

        return $this->render();
    }

    public function viewAjaxHome($token, $editor = false)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            echo json_encode(['success' => false]);
            die();
        }
        $rawEditor = trim((string)$editor);
        $this->editor = ($rawEditor === '' || $rawEditor === 'false' || $rawEditor === '0') ? false : $rawEditor;
        $this->ajaxView = true;
        $this->dir = '';
        return $this->render();
    }

    protected function render()
    {
        $this->getSentDir();
        $this->filemanager = new FileManager($this->fullDir);
        if ($this->ajaxView) {
            $response = new StringResponse();
        } else {
            $response = new AdminResponse();
        }
        $tpl = $response->createPluginTemplate('filemanager', 'listview');

        $tpl->set('token', $this->user->token);
        $tpl->set('dir', $this->dir);
        $tpl->set('dirParts', $this->dirParts);
        $breadcrumbs = [];
        $acc = '';
        foreach ($this->dirParts as $part) {
            if ($part === '') {
                continue;
            }
            $acc = ($acc === '') ? $part : ($acc . '/' . $part);
            $breadcrumbs[] = ['label' => $part, 'dir' => $acc];
        }
        $tpl->set('breadcrumbs', $breadcrumbs);

        $folders = [];
        foreach ($this->filemanager->getFolders() as $folder) {
            $folders[] = [
                'name' => $folder->name,
                'path' => $this->dir === '' ? $folder->name : ($this->dir . '/' . $folder->name),
            ];
        }
        $files = [];
        foreach ($this->filemanager->getFiles() as $file) {
            $files[] = [
                'name' => $file->name,
                'url' => $file->getUrl(),
                'relUrl' => $file->getRelUrl(),
                'isPicture' => $file->isPicture(),
                'isPictureFlag' => $file->isPicture() ? '1' : '0',
                'iconClass' => $file->getIconClass(),
                'sizeFormatted' => $file->getFormattedSize(),
            ];
        }
        $tpl->set('folders', $folders);
        $tpl->set('files', $files);
        $tpl->set('isEmpty', empty($folders) && empty($files));
        $tpl->set('manager', $this->filemanager);
        $stats = $this->filemanager->getStats();
        $stats['path'] = $this->dir === '' ? '/' : $this->dir;
        $stats['path_label'] = $stats['path'] === '/' ? Lang::get('filemanager.root') : $stats['path'];
        $tpl->set('stats', $stats);
        $tpl->set('ajaxView', $this->ajaxView);
        $tpl->set('uploadUrl', $this->router->generate('filemanager-upload', ['token' => $this->user->token]));
        $tpl->set('deleteUrl', $this->router->generate('filemanager-delete', ['token' => $this->user->token]));
        $tpl->set('createUrl', $this->router->generate('filemanager-create', ['token' => $this->user->token]));
        $tpl->set('redirectUrl', $this->router->generate('filemanager-view'));
        $tpl->set('redirectAjaxUrl', $this->router->generate('filemanager-view-ajax'));
        $tpl->set('editor', $this->editor);
        $tpl->set('hasEditor', $this->editor !== false && $this->editor !== '');
        $cssPath = ADMIN_PATH . 'templates/filemanager/admin.css';
        $jsPath = ADMIN_PATH . 'templates/filemanager/admin.js';
        $tpl->set('fmCssUrl', Util::urlBuild('admin/templates/filemanager/admin.css') . '?v=' . (file_exists($cssPath) ? filemtime($cssPath) : '1'));
        $tpl->set('fmJsUrl', Util::urlBuild('admin/templates/filemanager/admin.js') . '?v=' . (file_exists($jsPath) ? filemtime($jsPath) : '1'));

        $activeTab = $_GET['activeTab'] ?? $_POST['activeTab'] ?? '';
        $tpl->set('activeTab', $activeTab);

        $response->addTemplate($tpl);
        return $response;
    }

    protected function getSentDir()
    {
        if (!isset($this->dir)) {
            if (isset($_POST['fmFolderToSee'])) {
                $this->dir = $_POST['fmFolderToSee'];
            } elseif (isset($_POST['dir'])) {
                $this->dir = $_POST['dir'];
            } elseif (isset($_GET['dir'])) {
                $this->dir = $_GET['dir'];
            } else {
                $this->dir = '';
            }
        }
        [$this->dir, $this->fullDir] = $this->media->normalizePath($this->dir);
        $this->dirParts = $this->dir === '' ? [] : explode('/', $this->dir);
    }

}