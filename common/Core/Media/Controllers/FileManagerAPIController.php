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

class FileManagerAPIController extends AdminController {

    protected ?string $dir = null;

    protected array $dirParts = [];

    protected string $fullDir = '';

    protected bool $ajaxView = false;

    protected bool $api = false;

    protected FileManager $filemanager;

    protected $editor = false;

    protected MediaService $media;

    public function __construct() {
        parent::__construct();
        $this->media = $this->core->media();
    }

    public function home() {
        return $this->render();
    }

    public function view() {
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

    public function upload($token) {
        if (!$this->user->isAuthorized()) {
            echo json_encode(['success' => 0]);
            die();
        }
        $this->getSentDir();
        $this->filemanager = new FileManager($this->fullDir);
        if (isset($_FILES['image']['name']) != '') {
            $image = $_FILES['image']['name'];
            if ($this->filemanager->uploadFile('image') !== false) {
                echo json_encode(['success' => 1]);
                die();
            } else {
                echo json_encode(['success' => 0]);
                die();
            }
        }
    }

    public function uploadAPI($token) {
        if (!$this->user->isAuthorized()) {
            header("HTTP/1.1 500 Server Error");
            die();
        }
        $temp = current($_FILES);
        if (is_uploaded_file($temp['tmp_name'])) {
            // Sanitize input
            if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
                header("HTTP/1.1 400 Invalid file name.");
                return;
            }

            // Verify extension
            if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), 
                    ["gif", "jpg", "png", "ico", "bmp", "jpeg"])) {
                header("HTTP/1.1 400 Invalid extension.");
                return;
            }

            $tinyManager = $this->media->makeManager('API');

            $uploaded = $tinyManager->uploadFile('file');
            if ($uploaded !== false) {
                echo json_encode(['location' => $uploaded]);
                die();
            }

            $imageFolder = UPLOAD;

            $filetowrite = $imageFolder . $temp['name'];
            move_uploaded_file($temp['tmp_name'], $filetowrite);

            $baseurl = Util::urlBuild('');

            echo json_encode(['location' => $baseurl . $filetowrite]);
            die();
        }
        header("HTTP/1.1 500 Server Error");
        die();
    }

    public function delete($token) {
        if (!$this->user->isAuthorized()) {
            echo json_encode(['success' => 0]);
            die();
        }
        $this->getSentDir();
        $this->filemanager = new FileManager($this->fullDir);
        if (isset($_POST['filename'])) {
            // Delete File
            $deleted = $this->filemanager->deleteFile($_POST['filename']);
            echo json_encode(['success' => $deleted]);
            die();
        } elseif (isset($_POST['foldername'])) {
            $deleted = $this->filemanager->deleteFolder($_POST['foldername']);
            echo json_encode(['success' => $deleted]);
            die();
        }
    }

    public function create($token) {
        if (!$this->user->isAuthorized()) {
            echo json_encode(['success' => 0]);
            die();
        }
        $this->getSentDir();
        $this->filemanager = new FileManager($this->fullDir);
        $created = $this->filemanager->createFolder($_POST['folderName']);
        echo json_encode(['success' => $created]);
        die();
    }

    public function viewAjax() {
        if (!$this->user->isAuthorized()) {
            echo json_encode(['success' => 0]);
            die();
        }
        $this->ajaxView = true;
        $this->editor = $_POST['editor'];

        return $this->render();
    }

    public function viewAjaxHome($token, $editor = false) {
        if (!$this->user->isAuthorized()) {
            echo json_encode(['success' => 0]);
            die();
        }
        if ($editor === ''){
            $editor = false;
        }
        $this->editor = $editor;
        $this->ajaxView = true;
        $this->dir = '';
        return $this->render();
    }

    protected function render() {
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
        
        // Check if we should stay on the "Manage" tab (from GET or POST)
        $activeTab = $_GET['activeTab'] ?? $_POST['activeTab'] ?? '';
        $tpl->set('activeTab', $activeTab);

        $response->addTemplate($tpl);
        return $response;
    }

    protected function getSentDir() {
        if (!isset($this->dir)) {
            // Priority: POST data (for upload, delete, create actions) > GET parameter (for view after redirect)
            if (isset($_POST['fmFolderToSee'])) {
                $this->dir = $_POST['fmFolderToSee'];
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