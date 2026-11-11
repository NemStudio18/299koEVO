<?php

namespace ConversionOrchestrator;

use Core\Controllers\Controller;
use Core\Router\Router;

class ConversionOrchestratorController extends Controller {

    public function setPendingAction($action, $poiId) {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        // Store the action in session
        $_SESSION['pending_action'] = [
            'type' => $action,
            'poi_id' => $poiId,
            'timestamp' => time()
        ];

        // Redirect to login
        $router = Router::getInstance();
        try {
            $url = $router->generate('login');
        } catch (\Exception $e) {
            $url = '/users/login';
        }

        header('Location: ' . $url);
        exit;
    }

    public function resetOwnership() {
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $isAdmin = $evaluator->isAdmin(new \Core\Navigation\ConditionContext($user));

        if (!$isAdmin) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Permission denied']);
            exit;
        }

        $poiId = (int)($_POST['poi_id'] ?? 0);
        if ($poiId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid POI ID']);
            exit;
        }

        $db = \Core\Database::getInstance();
        $pdo = $db->getPdo();

        try {
            $pdo->beginTransaction();

            // 1. Clear claimed_by in poi_public
            $stmt = $pdo->prepare("UPDATE poi_public SET claimed_by = NULL WHERE id = ?");
            $stmt->execute([$poiId]);

            // 2. Delete/Reject claims in poi_claims
            $stmt = $pdo->prepare("DELETE FROM poi_claims WHERE poi_id = ?");
            $stmt->execute([$poiId]);

            $pdo->commit();

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
