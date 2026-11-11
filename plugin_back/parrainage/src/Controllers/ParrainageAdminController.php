<?php

namespace Parrainage\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Core\Router\Router;
use Core\Navigation\AccessPolicyEvaluator;
use Core\Navigation\ConditionContext;
use Core\Navigation\GenericResource;
use Parrainage\Services\ParrainageService;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class ParrainageAdminController extends AdminController
{
    private ParrainageService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ParrainageService();
    }

    /**
     * Dashboard d'administration du parrainage.
     */
    public function dashboard()
    {
        // 1. Vérification des permissions
        $evaluator = AccessPolicyEvaluator::getInstance();
        $context = new ConditionContext($this->user);
        if (!$evaluator->canAccess(new GenericResource('parrainage:admin'), $context)) {
            $response = new AdminResponse();
            $tpl = $response->createCoreTemplate('errors/403');
            $tpl->set('message', 'Accès refusé au module Parrainage');
            $response->addTemplate($tpl);
            return $response;
        }

        $msg = null;
        $type = 'success';
        $activeTab = $_GET['tab'] ?? 'overview';

        // 2. Traitement des actions (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'add_code') {
                $code = trim($_POST['code'] ?? '');
                $commission = (float)($_POST['commission'] ?? 0);
                $userId = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
                $email = !empty($_POST['email']) ? trim($_POST['email']) : null;

                if (empty($code)) {
                    $msg = "Le code ne peut pas être vide.";
                    $type = 'danger';
                } else {
                    if ($this->service->generateCode($code, $commission, $userId, $email)) {
                        $msg = "Code '$code' ajouté avec succès.";
                        $activeTab = 'codes';
                    } else {
                        $msg = "Erreur : Ce code existe peut-être déjà.";
                        $type = 'danger';
                        $activeTab = 'codes';
                    }
                }
            } elseif ($action === 'export_json') {
                $data = $this->service->getAllCodes();
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="parrainage_export_' . date('Y-m-d') . '.json"');
                echo json_encode($data, JSON_PRETTY_PRINT);
                exit;
            }
        }

        // 3. Récupération des données pour l'affichage
        $codes = $this->service->getAllCodes();
        
        // Calcul des stats pour la vue d'ensemble
        $stats = [
            'total_codes' => count($codes),
            'total_referrals' => array_sum(array_column($codes, 'usage_count')),
        ];

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('parrainage', 'admin_dashboard');
        
        $tpl->set('codes', $codes);
        $tpl->set('stats', $stats);
        $tpl->set('activeTab', $activeTab);
        $tpl->set('msg', $msg);
        $tpl->set('msgType', $type);
        $tpl->set('token', \Core\Security\Csrf::token());
        
        $response->setTitle('Administration Parrainage');
        $response->addTemplate($tpl);
        
        return $response;
    }
}
