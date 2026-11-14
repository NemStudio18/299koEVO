<?php

namespace ProRdv\Controllers;

use Core\Controllers\Controller;
use Core\Responses\PublicResponse;
use ProRdv\Services\RdvService;
use Template\Template;

defined('ROOT') or exit('Access denied!');

class PublicRdvController extends Controller
{
    private RdvService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = RdvService::getInstance();
    }

    /**
     * Page d'accueil par défaut (/rdv) : charge le premier établissement actif
     */
    public function defaultIndex()
    {
        $all = $this->service->getAllCommerces();
        foreach ($all as $c) {
            if (!empty($c['is_active'])) {
                return $this->index($c['slug']);
            }
        }
        if (!empty($all)) {
            return $this->index($all[0]['slug']);
        }
        $this->service->installTables();
        $all = $this->service->getAllCommerces();
        if (!empty($all)) {
            return $this->index($all[0]['slug']);
        }
        $response = new PublicResponse();
        $response->setTitle("Prise de rendez-vous en ligne");
        $tpl = new Template();
        $tpl->setContent('<div class="container"><p>Aucun agenda de rendez-vous configuré pour le moment.</p></div>');
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Page publique de prise de rendez-vous (/rdv/{slug})
     */
    public function index(string $slug)
    {
        $commerce = $this->service->getCommerceBySlug($slug);
        if (!$commerce) {
            header("HTTP/1.0 404 Not Found");
            echo "Commerce introuvable.";
            exit;
        }

        $cfg = json_decode($commerce['config'] ?? '{}', true) ?: [];
        $prestas = $this->service->getPrestations((int)$commerce['id']);
        $collabs = !empty($cfg['choix_collab']) ? $this->service->getCollaborateurs((int)$commerce['id']) : [];

        $pid = (int)($_REQUEST['p'] ?? 0);
        $presta = null;
        foreach ($prestas as $p) {
            if ((int)$p['id'] === $pid) {
                $presta = $p;
                break;
            }
        }
        if (!$presta && !empty($prestas)) {
            $presta = $prestas[0];
            $pid = (int)$presta['id'];
        }

        $collabId = !empty($_REQUEST['k']) ? (int)$_REQUEST['k'] : null;
        $date = $_REQUEST['d'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date('Y-m-d')) {
            $date = date('Y-m-d');
        }

        $slots = $presta ? $this->service->getAvailableSlots($commerce, $presta, $collabId, $date) : [];

        $response = new PublicResponse();
        $response->setTitle("Prendre rendez-vous chez {$commerce['nom']}");
        $response->setMetaDescription("Réservez votre créneau en ligne chez {$commerce['nom']} Service rapide, confirmation immédiate et 100% souverain.");

        $tpl = new Template(ROOT . 'plugin/ProRdv/template/booking.tpl');
        $tpl->set('commerce', $commerce);
        $tpl->set('cfg', $cfg);
        $tpl->set('prestas', $prestas);
        $tpl->set('collabs', $collabs);
        $tpl->set('selectedPresta', $presta);
        $tpl->set('selectedCollabId', $collabId);
        $tpl->set('selectedDate', $date);
        $tpl->set('slots', $slots);
        $tpl->set('success', false);
        $tpl->set('error', '');

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Endpoint JSON pour le rafraîchissement dynamique des créneaux en AJAX
     */
    public function slotsJson()
    {
        header('Content-Type: application/json; charset=utf-8');
        $cid = (int)($_GET['commerce_id'] ?? 0);
        $pid = (int)($_GET['presta_id'] ?? 0);
        $collabId = !empty($_GET['collab_id']) ? (int)$_GET['collab_id'] : null;
        $date = $_GET['date'] ?? date('Y-m-d');

        $commerce = $this->service->getCommerceById($cid);
        if (!$commerce) {
            echo json_encode(['success' => false, 'error' => 'Commerce non trouvé']);
            exit;
        }

        $prestas = $this->service->getPrestations($cid);
        $presta = null;
        foreach ($prestas as $p) {
            if ((int)$p['id'] === $pid) {
                $presta = $p;
                break;
            }
        }

        if (!$presta) {
            echo json_encode(['success' => false, 'error' => 'Prestation non trouvée']);
            exit;
        }

        $slots = $this->service->getAvailableSlots($commerce, $presta, $collabId, $date);
        echo json_encode(['success' => true, 'slots' => $slots, 'count' => count($slots)]);
        exit;
    }

    /**
     * Traitement du formulaire de réservation POST
     */
    public function postReserve()
    {
        // Piège à robots (honeypot)
        if (!empty($_POST['website_hp'])) {
            header("HTTP/1.1 200 OK");
            echo "OK";
            exit;
        }

        $cid = (int)($_POST['commerce_id'] ?? 0);
        $pid = (int)($_POST['presta_id'] ?? 0);
        $collabId = !empty($_POST['collab_id']) ? (int)$_POST['collab_id'] : null;
        $date = $_POST['date'] ?? '';
        $heure = $_POST['heure'] ?? '';

        $commerce = $this->service->getCommerceById($cid);
        if (!$commerce) {
            exit('Commerce introuvable');
        }

        $prestas = $this->service->getPrestations($cid);
        $presta = null;
        foreach ($prestas as $p) {
            if ((int)$p['id'] === $pid) {
                $presta = $p;
                break;
            }
        }
        if (!$presta) {
            exit('Prestation introuvable');
        }

        $extra = [];
        if (!empty($_POST['adresse'])) $extra['adresse'] = trim($_POST['adresse']);
        if (!empty($_POST['details'])) $extra['details'] = trim($_POST['details']);
        if (!empty($_POST['marque_modele'])) $extra['marque_modele'] = trim($_POST['marque_modele']);
        if (!empty($_POST['lieu_rdv'])) $extra['lieu_rdv'] = trim($_POST['lieu_rdv']);
        if (!empty($_POST['objet'])) $extra['objet'] = trim($_POST['objet']);
        if (!empty($_POST['remarque'])) $extra['remarque'] = trim($_POST['remarque']);

        $clientData = [
            'nom' => $_POST['nom'] ?? '',
            'tel' => $_POST['tel'] ?? '',
            'email' => $_POST['email'] ?? '',
            'extra' => $extra
        ];

        $res = $this->service->createReservation($commerce, $presta, $collabId, $date, $heure, $clientData);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($res);
            exit;
        }

        $response = new PublicResponse();
        $response->setTitle("Validation du rendez-vous - {$commerce['nom']}");

        $tpl = new Template(ROOT . 'plugin/ProRdv/template/booking.tpl');
        $tpl->set('commerce', $commerce);
        $tpl->set('cfg', json_decode($commerce['config'] ?? '{}', true) ?: []);
        $tpl->set('prestas', $prestas);
        $tpl->set('collabs', []);
        $tpl->set('selectedPresta', $presta);
        $tpl->set('selectedCollabId', $collabId);
        $tpl->set('selectedDate', $date);
        $tpl->set('slots', []);
        $tpl->set('success', $res['success']);
        $tpl->set('error', $res['error'] ?? '');
        $tpl->set('resData', $res);

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Confirmation double opt-in client (/rdv/confirmer/{token})
     */
    public function confirm(string $token)
    {
        $res = $this->service->confirmReservation($token);

        $response = new PublicResponse();
        $response->setTitle("Confirmation de rendez-vous");

        $tpl = new Template(ROOT . 'plugin/ProRdv/template/confirmation.tpl');
        $tpl->set('res', $res);

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Annulation d'un rendez-vous par le client (/rdv/annuler/{token})
     */
    public function cancel(string $token)
    {
        $res = $this->service->cancelReservation($token);

        $response = new PublicResponse();
        $response->setTitle("Annulation de rendez-vous");

        $tpl = new Template(ROOT . 'plugin/ProRdv/template/cancellation.tpl');
        $tpl->set('res', $res);

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Redirection d'un POI vers sa page RDV (/rdv/poi/{id})
     */
    public function byPoi(int $poiId)
    {
        $commerce = $this->service->getCommerceByPoiId($poiId);
        if ($commerce) {
            header("Location: /rdv/{$commerce['slug']}");
            exit;
        }
        header("Location: /");
        exit;
    }
}
