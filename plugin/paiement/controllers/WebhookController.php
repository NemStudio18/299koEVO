<?php

defined('ROOT') OR exit('Access denied!');

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Core;
use Core\Logger;

require_once PLUGINS . 'paiement/drivers/StripeDriver.php';
require_once PLUGINS . 'paiement/drivers/PaypalDriver.php';
require_once PLUGINS . 'paiement/drivers/MollieDriver.php';

class WebhookController extends PublicController
{
    /**
     * Stripe webhook endpoint
     */
    public function stripe(): PublicResponse
    {
        $payload = file_get_contents('php://input');
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $key = str_replace('_', '-', substr($k, 5));
                $headers[$key] = $v;
            }
        }
        $headers['Stripe-Signature'] = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        $driver = new StripeDriver();
        $event  = $driver->handleWebhook($payload, $headers);

        if ($event && isset($event['type'])) {
            Core::getInstance()->getLogger()->log(Logger::LEVEL_INFO, 'Stripe webhook: ' . $event['type']);

            if ($event['type'] === 'checkout.session.completed') {
                $this->processPaiementSuccess($event['data']['object'] ?? []);
            }
        }

        http_response_code(200);
        $response = new PublicResponse();
        return $response;
    }

    /**
     * PayPal webhook endpoint
     */
    public function paypal(): PublicResponse
    {
        $payload = file_get_contents('php://input');
        $driver  = new PaypalDriver();
        $event   = $driver->handleWebhook($payload, []);

        if ($event && isset($event['event_type'])) {
            Core::getInstance()->getLogger()->log(Logger::LEVEL_INFO, 'PayPal webhook: ' . $event['event_type']);

            if ($event['event_type'] === 'PAYMENT.CAPTURE.COMPLETED') {
                $resource = $event['resource'] ?? [];
                $customId = $resource['custom_id'] ?? '{}';
                $meta = json_decode($customId, true) ?: [];
                $this->activatePaiement($meta);
            }
        }

        http_response_code(200);
        $response = new PublicResponse();
        return $response;
    }

    /**
     * Mollie webhook endpoint
     * Mollie sends POST with 'id' param → fetch payment to verify
     */
    public function mollie(): PublicResponse
    {
        $payload = file_get_contents('php://input');
        $driver  = new MollieDriver();
        $payment = $driver->handleWebhook($payload, []);

        if ($payment && isset($payment['status']) && $payment['status'] === 'paid') {
            Core::getInstance()->getLogger()->log(Logger::LEVEL_INFO, 'Mollie webhook: payment paid ' . ($payment['id'] ?? ''));
            $meta = $payment['metadata'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true) ?: [];
            }
            $this->activatePaiement($meta);
        }

        http_response_code(200);
        $response = new PublicResponse();
        return $response;
    }

    /**
     * Process successful payment — activate tournee inscription or abonnement
     */
    private function processPaiementSuccess(array $session): void
    {
        $meta = $session['metadata'] ?? [];
        $this->activatePaiement($meta);
    }

    private function activatePaiement(array $meta): void
    {
        $type   = $meta['type']    ?? '';
        $refId  = (int)($meta['ref_id']  ?? 0);
        $userId = (int)($meta['user_id'] ?? 0);
        $db = \Core\Database::getInstance()->getPdo();

        if (!$db || !$type || !$userId) return;

        if ($type === 'tournee' && $refId > 0) {
            $stmt = $db->prepare("UPDATE tournee_inscriptions SET paiement = 'paye' WHERE tournee_id = ? AND user_id = ?");
            $stmt->execute([$refId, $userId]);
        } elseif ($type === 'abonnement') {
            $stmt = $db->prepare("UPDATE abonnements SET statut = 'actif', date_debut = date('now'), date_fin = date('now', '+1 year'), updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Planification automatique (4 passages espacés de 12 semaines)
            $stmt = $db->prepare("SELECT quartier FROM abonnements WHERE user_id = ?");
            $stmt->execute([$userId]);
            $abo = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($abo && !empty($abo['quartier'])) {
                $quartier = $abo['quartier'];
                
                // Récupérer toutes les tournées futures pour ce quartier
                $stmtTournees = $db->prepare("
                    SELECT t.id, t.date_tournee, t.places_abonnes,
                           (SELECT COUNT(*) FROM tournee_inscriptions i WHERE i.tournee_id = t.id AND i.paiement = 'inclus_abonnement') as inscrits
                    FROM tournees t
                    WHERE t.quartier = ? AND t.date_tournee >= date('now')
                    ORDER BY t.date_tournee ASC
                ");
                $stmtTournees->execute([$quartier]);
                $tournees = $stmtTournees->fetchAll(\PDO::FETCH_ASSOC);
                
                $passagesCrees = 0;
                $derniereDate = null;
                
                foreach ($tournees as $t) {
                    if ($passagesCrees >= 4) break;
                    
                    // Vérifier si le quota abonnés est atteint
                    if ($t['inscrits'] >= $t['places_abonnes']) {
                        continue; // Tournée pleine pour les abonnés
                    }
                    
                    // Si on a déjà une date, vérifier qu'il y a bien au moins 11 semaines d'écart (on vise 12)
                    if ($derniereDate) {
                        $diff = strtotime($t['date_tournee']) - strtotime($derniereDate);
                        $semainesEcart = $diff / (60 * 60 * 24 * 7);
                        if ($semainesEcart < 11) {
                            continue; // Trop tôt
                        }
                    }
                    
                    // Bloquer la place
                    $stmtIns = $db->prepare("INSERT OR IGNORE INTO tournee_inscriptions (tournee_id, user_id, statut, paiement) VALUES (?, ?, 'confirmee', 'inclus_abonnement')");
                    $stmtIns->execute([$t['id'], $userId]);
                    
                    $derniereDate = $t['date_tournee'];
                    $passagesCrees++;
                }
            }
        }
    }
}

