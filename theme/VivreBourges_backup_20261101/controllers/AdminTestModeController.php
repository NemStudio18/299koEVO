<?php

namespace SearchUI\Controllers;

use Core\Controllers\PublicController;
use Core\Auth\AdminTestMode;
use Core\Auth\UsersManager;

defined('ROOT') or exit('Access denied!');

class AdminTestModeController extends PublicController
{
    public function toggle()
    {
        $user = UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        
        $testMode = AdminTestMode::getInstance();
        if (!$testMode->isActive() && !$evaluator->canAccess(new \Core\Navigation\GenericResource('search-ui:admin'), $ctx)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }


        if ($testMode->isActive()) {
            $testMode->deactivate();
        } else {
            $testMode->activate();
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'isActive' => $testMode->isActive()]);
            exit;
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?: '/'));
        exit;
    }

    public function impersonate()
    {
        $user = UsersManager::getCurrentUser();
        $testMode = AdminTestMode::getInstance();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        
        // Si on n'est pas admin et que le mode test n'est pas déjà actif (permettant la désactivation), on bloque.
        if (!$testMode->isActive() && !$evaluator->canAccess(new \Core\Navigation\GenericResource('search-ui:admin'), $ctx)) {
            die('Unauthorized');
        }

        $userId = $_POST['user_id'] ?? null;
        
        if ($userId && $userId !== '0') {
            $testMode->activate($userId);
        } else {
            $testMode->activate(null); // Juste mode test actif, pas d'impersonnalisaton
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?: '/'));
        exit;
    }
}
