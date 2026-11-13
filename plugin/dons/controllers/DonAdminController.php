<?php

namespace Dons\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;
use Core\Plugin\PluginsManager;
use Dons\Entities\Don;
use Dons\Entities\DonManager;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class DonAdminController extends AdminController
{
    /**
     * Checks if the current user has access to the 'dons:admin' resource.
     * If not, displays an error message and redirects to the admin home.
     *
     * @return bool True if access is granted, false otherwise (and handles redirection).
     */
    private function checkAdminAccess(): bool
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('dons:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return false;
        }
        return true;
    }

    public function list()
    {
        if (!$this->checkAdminAccess()) {
            return;
        }

        $donManager = new DonManager();
        $dons = $donManager->getDons();

        $this->runPlugin->setMainTitle(Lang::get('dons-admin.list-title'));
        $this->runPlugin->setTitleTag($this->runPlugin->getMainTitle());

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('dons', 'admin-list');

        $donsData = [];
        foreach ($dons as $don) {
            $donsData[] = [
                'id' => $don->getId(),
                'amount' => $don->getAmount(),
                'amountFormatted' => number_format($don->getAmount(), 2, ',', ' '),
                'firstName' => $don->getFirstName(),
                'lastName' => $don->getLastName(),
                'isAnonymous' => $don->isAnonymous(),
                'email' => $don->getEmail(),
                'message' => $don->getMessage(),
                'gateway' => $don->getGateway(),
                'status' => $don->getStatus(),
                'date' => Util::FormatDate($don->getDate(), 'en', 'fr'),
                'transactionId' => $don->getTransactionId()
            ];
        }

        // Trier par date décroissante
        usort($donsData, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        $totalAmount = $donManager->getTotalAmount();
        $tpl->set('dons', $donsData);
        $tpl->set('totalAmount', $totalAmount);
        $tpl->set('totalAmountFormatted', number_format($totalAmount, 2, ',', ' '));
        $tpl->set('token', $this->user->token);
        
        // Config data for the "Configuration" tab
        $tpl->set('pageTitle', $this->runPlugin->getConfigVal('pageTitle'));
        $tpl->set('description', $this->runPlugin->getConfigVal('description'));
        $tpl->set('targetAmount', $this->runPlugin->getConfigVal('targetAmount'));
        $tpl->set('paypalClientId', $this->runPlugin->getConfigVal('paypalClientId'));
        $tpl->set('paypalSecret', $this->runPlugin->getConfigVal('paypalSecret'));
        $tpl->set('paypalMode', $this->runPlugin->getConfigVal('paypalMode'));
        $tpl->set('stripePublishableKey', $this->runPlugin->getConfigVal('stripePublishableKey'));
        $tpl->set('stripeSecretKey', $this->runPlugin->getConfigVal('stripeSecretKey'));
        $tpl->set('stripeMode', $this->runPlugin->getConfigVal('stripeMode'));

        $response->addTemplate($tpl);
        return $response;
    }

    public function stats()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('dons:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        $donManager = new DonManager();
        $totalAmount = $donManager->getTotalAmount();
        $targetAmount = floatval($this->runPlugin->getConfigVal('targetAmount'));
        $progress = $targetAmount > 0 ? ($totalAmount / $targetAmount) * 100 : 0;
        if ($progress > 100) $progress = 100;

        header('Content-Type: application/json');
        echo json_encode([
            'totalAmount' => $totalAmount,
            'targetAmount' => $targetAmount,
            'progress' => round($progress, 2),
            'count' => count($donManager->getCompletedDons())
        ]);
        die();
    }

    public function params()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('dons:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        $this->runPlugin->setMainTitle(Lang::get('dons-admin.params-title'));
        $this->runPlugin->setTitleTag($this->runPlugin->getMainTitle());

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('dons', 'param');
        
        $tpl->set('token', $this->user->token);
        $tpl->set('pageTitle', $this->runPlugin->getConfigVal('pageTitle'));
        $tpl->set('description', $this->runPlugin->getConfigVal('description'));
        $tpl->set('targetAmount', $this->runPlugin->getConfigVal('targetAmount'));
        $tpl->set('paypalClientId', $this->runPlugin->getConfigVal('paypalClientId'));
        $tpl->set('paypalSecret', $this->runPlugin->getConfigVal('paypalSecret'));
        $tpl->set('paypalMode', $this->runPlugin->getConfigVal('paypalMode'));
        $tpl->set('stripePublishableKey', $this->runPlugin->getConfigVal('stripePublishableKey'));
        $tpl->set('stripeSecretKey', $this->runPlugin->getConfigVal('stripeSecretKey'));
        $tpl->set('stripeMode', $this->runPlugin->getConfigVal('stripeMode'));
        $tpl->set('showDonorsList', $this->runPlugin->getConfigVal('showDonorsList'));
        $tpl->set('continueAfterTarget', $this->runPlugin->getConfigVal('continueAfterTarget'));
        $tpl->set('showDonationGraph', $this->runPlugin->getConfigVal('showDonationGraph'));

        $response->addTemplate($tpl);
        return $response;
    }

    public function saveParams()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('dons:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->params();
        }

        if (!isset($_POST['token']) || $_POST['token'] !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->params();
        }

        $pageTitle = filter_var($_POST['pageTitle'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $targetAmount = floatval($_POST['targetAmount'] ?? 0);
        $paypalClientId = trim(strip_tags((string)($_POST['paypalClientId'] ?? '')));
        $paypalSecret = trim(strip_tags((string)($_POST['paypalSecret'] ?? '')));
        $paypalMode = trim(strip_tags((string)($_POST['paypalMode'] ?? 'sandbox')));
        $stripePublishableKey = trim(strip_tags((string)($_POST['stripePublishableKey'] ?? '')));
        $stripeSecretKey = trim(strip_tags((string)($_POST['stripeSecretKey'] ?? '')));
        $stripeMode = trim(strip_tags((string)($_POST['stripeMode'] ?? 'test')));
        $showDonorsList = isset($_POST['showDonorsList']) ? 1 : 0;

        $this->runPlugin->setConfigVal('pageTitle', $pageTitle);
        $this->runPlugin->setConfigVal('description', $description);
        $this->runPlugin->setConfigVal('targetAmount', $targetAmount);
        $this->runPlugin->setConfigVal('paypalClientId', $paypalClientId);
        $this->runPlugin->setConfigVal('paypalSecret', $paypalSecret);
        $this->runPlugin->setConfigVal('paypalMode', $paypalMode);
        $this->runPlugin->setConfigVal('stripePublishableKey', $stripePublishableKey);
        $this->runPlugin->setConfigVal('stripeSecretKey', $stripeSecretKey);
        $this->runPlugin->setConfigVal('stripeMode', $stripeMode);
        $this->runPlugin->setConfigVal('showDonorsList', $showDonorsList);
        $this->runPlugin->setConfigVal('continueAfterTarget', isset($_POST['continueAfterTarget']) ? 1 : 0);
        $this->runPlugin->setConfigVal('showDonationGraph', isset($_POST['showDonationGraph']) ? 1 : 0);
        
        $pluginsManager = PluginsManager::getInstance();
        if ($pluginsManager->savePluginConfig($this->runPlugin)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
            header('location:' . $this->router->generate('dons-admin-list'));
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
            header('location:' . $this->router->generate('dons-admin-params'));
        }
        die();
    }

    public function chartData()
    {
        header('Content-Type: application/json');
        $donManager = new DonManager();
        $dailyData = $donManager->getDailyDonations(30);
        echo json_encode($dailyData);
        die();
    }
}

