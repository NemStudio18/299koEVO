<?php

namespace LibManager\Controllers;

use LibManager\Services\LibUpdater;
use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;

class LibManagerController extends AdminController {

    public function index(): AdminResponse {
        $updater = new LibUpdater();
        $libs = $updater->loadLibs();
        
        // Enrichir avec l'état sur disque
        $enrichedLibs = [];
        foreach ($libs as $k => $item) {
            $item['key'] = $k;
            $item['is_installed'] = file_exists(ROOT . $item['install_path']);
            $enrichedLibs[$k] = $item;
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('LibManager', 'admin/dashboard');
        $tpl->setData([
            'libs'    => $enrichedLibs,
            'checked' => false,
        ]);
        $response->addTemplate($tpl);
        return $response;
    }

    public function check(): void {
        header('Content-Type: application/json; charset=utf-8');
        $force = !empty($_POST['force']) || !empty($_GET['force']);
        $updater = new LibUpdater();
        $results = $updater->checkAll($force);
        echo json_encode([
            'success' => true,
            'libs'    => $results,
            'time'    => date('H:i:s')
        ]);
        exit;
    }

    public function update(): void {
        header('Content-Type: application/json; charset=utf-8');
        $libKey = trim($_POST['lib'] ?? $_GET['lib'] ?? '');
        if ($libKey === '') {
            echo json_encode(['success' => false, 'message' => 'Paramètre lib manquant.']);
            exit;
        }

        $updater = new LibUpdater();
        $result = $updater->update($libKey);
        echo json_encode($result);
        exit;
    }

    public function log(): AdminResponse {
        $updater = new LibUpdater();
        $log = $updater->getLog();

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('LibManager', 'admin/log');
        $tpl->setData([
            'log_content'        => $log ?: "Aucun historique de mise à jour pour le moment.",
            'HEADER_APP_NAME'    => 'LibManager',
            'HEADER_APP_CONTEXT' => 'Historique des mises à jour'
        ]);
        $response->addTemplate($tpl);
        return $response;
    }
}
