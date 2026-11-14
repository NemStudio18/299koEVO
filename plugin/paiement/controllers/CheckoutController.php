<?php

defined('ROOT') OR exit('Access denied!');

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Auth\UsersManager;
use Utils\Show;
use Core\Lang;
use Core\Core;

require_once PLUGINS . 'paiement/drivers/StripeDriver.php';
require_once PLUGINS . 'paiement/drivers/PaypalDriver.php';
require_once PLUGINS . 'paiement/drivers/MollieDriver.php';

class CheckoutController extends PublicController
{
    public function home(): PublicResponse
    {
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-credit-card"></i> Paiement');

        $type  = $_POST['type'] ?? $_GET['type'] ?? 'tournee';
        $refId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!UsersManager::isLogged()) {
            Show::msg("Vous devez être connecté pour accéder au paiement.", 'error');
            $tpl = $response->createPluginTemplate('paiement', 'checkout');
            $tpl->set('error', true);
            $response->addTemplate($tpl);
            return $response;
        }

        // Résoudre montant et description
        $db = \Core\Database::getInstance()->getPdo();
        $amount = 0.0;
        $description = '';

        if ($type === 'tournee' && $refId > 0 && $db) {
            $inscriptionId = (int)($_POST['inscription_id'] ?? $_GET['inscription_id'] ?? 0);
            
            $stmt = $db->prepare("
                SELECT t.quartier, t.date_tournee, i.nb_bacs 
                FROM tournees t
                LEFT JOIN tournee_inscriptions i ON i.tournee_id = t.id AND i.id = ?
                WHERE t.id = ?
            ");
            $stmt->execute([$inscriptionId, $refId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $nbBacs = (int)($row['nb_bacs'] ?? 1);
                $amount = ($nbBacs === 2) ? 25.00 : 15.00;
                $description = 'Tournée ' . $row['quartier'] . ' — ' . $row['date_tournee'] . ' (' . $nbBacs . ' bac' . ($nbBacs > 1 ? 's' : '') . ')';
            }
        } elseif ($type === 'abonnement') {
            $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('abonnement');
            $amount = $plugin ? (float)$plugin->getConfigVal('tarif_annuel') : 89.00;
            $description = 'Abonnement annuel NetBerry';
        }

        if ($amount <= 0) {
            Show::msg("Montant invalide.", 'error');
            $tpl = $response->createPluginTemplate('paiement', 'checkout');
            $tpl->set('error', true);
            $response->addTemplate($tpl);
            return $response;
        }

        $amountFormate = number_format($amount, 2, ',', ' ');
        $stripe = new StripeDriver();
        $paypal = new PaypalDriver();
        $mollie = new MollieDriver();

        $tpl = $response->createPluginTemplate('paiement', 'checkout');
        $tpl->set('type', $type);
        $tpl->set('refId', $refId);
        $tpl->set('amount', $amount);
        $tpl->set('amountFormate', $amountFormate);
        $tpl->set('description', $description);
        $tpl->set('stripeConfigured', $stripe->isConfigured());
        $tpl->set('paypalConfigured', $paypal->isConfigured());
        $tpl->set('mollieConfigured', $mollie->isConfigured());
        $tpl->set('stripePublicKey', $stripe->getPublicKey());
        $tpl->set('router', $this->router);
        $response->addTemplate($tpl);
        return $response;
    }

    public function checkout(): PublicResponse
    {
        $provider = $_POST['provider'] ?? $_GET['provider'] ?? '';
        $type     = $_POST['type']     ?? $_GET['type']     ?? 'tournee';
        $refId    = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        if (!UsersManager::isLogged()) {
            Show::msg(Lang::get('paiement.login-required'), 'error');
            return $this->home();
        }

        $user = UsersManager::getCurrentUser();
        $db   = \Core\Database::getInstance()->getPdo();
        $amount = 0.0;
        $description = '';

        if ($type === 'tournee' && $refId > 0 && $db) {
            // Need inscription_id in checkout too. It might not be passed in POST unless we add it to the form.
            // Let's get it from tournee_inscriptions for this user and this tournee that is pending.
            $stmt = $db->prepare("
                SELECT t.quartier, t.date_tournee, i.nb_bacs 
                FROM tournees t
                JOIN tournee_inscriptions i ON i.tournee_id = t.id 
                WHERE t.id = ? AND i.user_id = ? AND i.statut = 'en_attente_paiement'
                ORDER BY i.id DESC LIMIT 1
            ");
            $stmt->execute([$refId, $user->id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $nbBacs = (int)($row['nb_bacs'] ?? 1);
                $amount = ($nbBacs === 2) ? 25.00 : 15.00;
                $description = 'Tournée ' . $row['quartier'] . ' — ' . $row['date_tournee'] . ' (' . $nbBacs . ' bac' . ($nbBacs > 1 ? 's' : '') . ')';
            }
        } elseif ($type === 'abonnement') {
            $abPlugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('abonnement');
            $amount = $abPlugin ? (float)$abPlugin->getConfigVal('tarif_annuel') : 89.00;
            $description = 'Abonnement annuel NetBerry';
        }

        $core = Core::getInstance();
        $siteUrl = $core->getConfigVal('siteUrl');
        $successUrl = $siteUrl . '/paiement/success?type=' . $type . '&id=' . $refId;
        $cancelUrl  = $siteUrl . '/paiement/cancel';
        $metadata   = ['type' => $type, 'ref_id' => $refId, 'user_id' => $user ? $user->id : 0];

        $redirectUrl = false;

        if ($provider === 'stripe') {
            require_once PLUGINS . 'paiement/drivers/StripeDriver.php';
            $driver = new StripeDriver();
            if ($driver->isConfigured()) {
                $redirectUrl = $driver->createSession($amount, $description, $successUrl, $cancelUrl, $metadata);
            }
        } elseif ($provider === 'paypal') {
            require_once PLUGINS . 'paiement/drivers/PaypalDriver.php';
            $driver = new PaypalDriver();
            if ($driver->isConfigured()) {
                $redirectUrl = $driver->createSession($amount, $description, $successUrl, $cancelUrl, $metadata);
            }
        } elseif ($provider === 'mollie') {
            require_once PLUGINS . 'paiement/drivers/MollieDriver.php';
            $driver = new MollieDriver();
            if ($driver->isConfigured()) {
                $redirectUrl = $driver->createSession($amount, $description, $successUrl, $cancelUrl, $metadata);
            }
        } elseif ($provider === 'mock') {
            require_once PLUGINS . 'paiement/drivers/MockDriver.php';
            $driver = new MockDriver();
            if ($driver->isConfigured()) {
                $redirectUrl = $driver->createSession($amount, $description, $successUrl, $cancelUrl, $metadata);
            }
        }

        if ($redirectUrl) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        Show::msg(Lang::get('paiement.not-configured'), 'error');
        return $this->home();
    }

    public function success(): PublicResponse
    {
        $type  = $_GET['type'] ?? '';
        $refId = (int)($_GET['id'] ?? 0);
        $user  = \Core\Auth\UsersManager::getCurrentUser();

        if ($user) {
            $db = \Core\Database::getInstance()->getPdo();
            if ($db) {
                if ($type === 'abonnement') {
                    $stmt = $db->prepare("UPDATE abonnements SET statut = 'actif', date_debut = date('now'), date_fin = date('now', '+1 year') WHERE user_id = ? AND statut = 'en_attente_paiement'");
                    $stmt->execute([$user->id]);
                } elseif ($type === 'tournee' && $refId > 0) {
                    $stmt = $db->prepare("UPDATE tournee_inscriptions SET statut = 'confirmee', paiement = 'paye' WHERE tournee_id = ? AND user_id = ? AND statut = 'en_attente_paiement'");
                    $stmt->execute([$refId, $user->id]);
                }
            }
        }

        $response = new PublicResponse();
        $response->setTitle('Paiement reçu');
        Show::msg(Lang::get('paiement.success'), 'success');
        $tpl = $response->createPluginTemplate('paiement', 'success');
        $tpl->set('router', $this->router);
        $response->addTemplate($tpl);
        return $response;
    }

    public function cancel(): PublicResponse
    {
        Show::msg(Lang::get('paiement.cancel'), 'warning');
        return $this->home();
    }
}

