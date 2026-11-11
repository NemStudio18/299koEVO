<?php

namespace Agenda\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Responses\ApiResponse;
use Utils\Show;
use Core\Lang;
use Core\Security\Csrf;
use Agenda\Services\ClaimService;

defined('ROOT') or exit('Access denied!');

class AgendaController extends PublicController
{
    private $eventManager;
    private $claimService;

    public function __construct()
    {
        parent::__construct();

        // Charger le service ClaimService si nécessaire
        if (!class_exists('Agenda\Services\ClaimService')) {
            $claimServiceFile = PLUGINS . 'agenda/services/ClaimService.php';
            if (file_exists($claimServiceFile)) {
                require_once $claimServiceFile;
            }
        }

        $this->eventManager = new \Agenda\Entities\EventManager();
        $this->claimService = new ClaimService($this->core->getLogger());
    }

    public function index($currentPage = 1)
    {
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-calendar"></i> ' . Lang::get('agenda.agenda'));
        $tpl = $response->createPluginTemplate('agenda', 'index');

        $plugin = $this->runPlugin;
        $allEvents = $this->eventManager->getAll(true, true); // showPast=true, onlyApproved=true
        $now = date('Y-m-d');
        $dateFormat = $plugin->getConfigVal('date_format') ?: 'd/m/Y';

        $allUpcoming = [];
        $allPast = [];

        foreach ($allEvents as $event) {
            $eventData = $event->toArray();
            $eventData['url'] = $this->router->generate('agenda-event', ['id' => $event->getId()]);
            
            // Format dates for display
            $eventData['start_date_display'] = date($dateFormat, strtotime($event->getStartDate()));
            $eventData['end_date_display'] = date($dateFormat, strtotime($event->getEndDate() ?: $event->getStartDate()));

            $endDate = $event->getEndDate() ?: $event->getStartDate();
            if ($endDate >= $now) {
                $allUpcoming[] = $eventData;
            } else {
                $allPast[] = $eventData;
            }
        }

        // Trier les événements à venir : chronologique ASC (du plus proche au plus lointain)
        usort($allUpcoming, function($a, $b) {
            return strcmp($a['start_date'], $b['start_date']);
        });

        // Trier les événements passés : anti-chronologique DESC (du plus récent au plus ancien)
        usort($allPast, function($a, $b) {
            return strcmp($b['start_date'], $a['start_date']);
        });

        // Pagination basée sur les événements à venir
        $items_per_page = $plugin->getConfigVal('items_per_page') ?: $plugin->getConfigVal('eventsPerPage') ?: 20;
        $totalItems = count($allUpcoming);
        $totalSafePages = ceil($totalItems / $items_per_page);
        $currentPage = max(1, min($currentPage, $totalSafePages ?: 1));
        
        $startOffset = ($currentPage - 1) * $items_per_page;
        $upcomingEvents = array_slice($allUpcoming, $startOffset, $items_per_page);

        $pagination = false;
        if ($totalSafePages > 1) {
            $pagination = [];
            for ($i = 1; $i <= $totalSafePages; $i++) {
                $pagination[] = [
                    'num' => $i,
                    'url' => $this->router->generate('agenda-index-page', ['page' => $i]),
                    'active' => ($i == $currentPage)
                ];
            }
        }

        // Affichage des archives passées récentes (sur la page 1 ou si aucun à venir)
        $pastEvents = ($currentPage === 1) ? array_slice($allPast, 0, 10) : [];

        // Vérifier si l'utilisateur est propriétaire
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $isOwner = false;
        if ($user) {
            $isOwner = $this->claimService->isOwner(
                (int) $user->attributes['id'],
                $user->attributes['email'] ?? null,
                $user->attributes['username'] ?? null
            );
        }

        $tpl->set('events', $upcomingEvents);
        $tpl->set('upcomingEvents', $upcomingEvents);
        $tpl->set('pastEvents', $pastEvents);
        $tpl->set('pagination', $pagination);
        $tpl->set('currentPage', $currentPage);
        $tpl->set('isOwner', $isOwner);
        $tpl->set('isLogged', $user !== null);
        if ($user) {
            $tpl->set('addEventUrl', $this->router->generate('agenda-event-add'));
            $tpl->set('proposeEventUrl', $this->router->generate('agenda-event-propose'));
        }

        $response->addTemplate($tpl);
        return $response;
    }

    public function event()
    {
        $response = new PublicResponse();
        $id = $this->router->getParam('id');

        $event = $this->eventManager->getById($id);
        if (!$event) {
            return \Core\Core::getInstance()->redirect('/agenda');
        }

        $eventTitle = $event->getTitle() ?? 'Événement';
        $response->setTitle('<i class="fa-solid fa-calendar-day"></i> ' . $eventTitle);
        $tpl = $response->createPluginTemplate('agenda', 'event');
        $plugin = $this->runPlugin;
        $dateFormat = $plugin->getConfigVal('date_format') ?: 'd/m/Y';
        $tpl->set('start_date_display', date($dateFormat, strtotime($event->getStartDate())));
        $tpl->set('end_date_display', date($dateFormat, strtotime($event->getEndDate())));
        $tpl->set('event', $event);

        // Map Hook calculation
        $lat = $event->getLatitude();
        $lon = $event->getLongitude();
        // Fallback sur le POI associé si l'événement a un poi_id
        if ((!$lat || !$lon) && $event->getPoiId()) {
            try {
                $db = \Core\Database::getInstance()->getPdo();
                $stmtPoi = $db->prepare("SELECT lat, lon FROM poi_public WHERE id = ?");
                $stmtPoi->execute([(int)$event->getPoiId()]);
                $poiCoord = $stmtPoi->fetch(\PDO::FETCH_ASSOC);
                if (!empty($poiCoord['lat']) && !empty($poiCoord['lon'])) {
                    $lat = (float)$poiCoord['lat'];
                    $lon = (float)$poiCoord['lon'];
                }
            } catch (\Exception $e) {}
        }

        $mapHtml = '';
        if ($lat && $lon) {
            $mapHtml = \Core\Core::getInstance()->callHook('map:render', [
                'id' => 'event-map',
                'lat' => $lat,
                'lon' => $lon,
                'zoom' => 14,
                'editable' => false,
                'markers' => [[
                    'lat' => $lat,
                    'lon' => $lon,
                    'title' => $eventTitle,
                    'icon' => 'calendar-check',
                    'family' => 'event'
                ]]
            ]);
        }
        $tpl->set('hasMap', !empty($mapHtml));
        $tpl->set('mapHtml', $mapHtml);

        // Load attendance data
        $attendanceManager = new \Agenda\Entities\AttendanceManager();
        $attendanceCount = $attendanceManager->getAttendanceCount($id);
        $tpl->set('attendanceCount', $attendanceCount);

        // Check if current user is attending
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $isAttending = false;
        if ($user) {
            $isAttending = $attendanceManager->isAttending($id, (int)$user->attributes['id']);
        } else {
            $identifier = \Agenda\Entities\AttendanceManager::generateAnonymousIdentifier();
            $isAttending = $attendanceManager->isAttending($id, null, $identifier);
        }
        $tpl->set('isAttending', $isAttending);
        $tpl->set('isLogged', $user !== null);

        // Fetch linked offers
        $linkedOffers = [];
        if (class_exists('\Offers\Entities\Offer')) {
            try {
                $linkedOffers = \Offers\Entities\Offer::query()
                    ->where('event_id', '=', $id)
                    ->get();
            } catch (\Exception $e) {}
        }
        $tpl->set('linkedOffers', $linkedOffers);

        $response->addTemplate($tpl);
        return $response;
    }

    public function toggleAttendance()
    {
        $response = new ApiResponse();
        $id = $this->router->getParam('id');

        $event = $this->eventManager->getById($id);
        if (!$event) {
            $response->status = ApiResponse::STATUS_NOT_FOUND;
            $response->body = ['message' => 'Événement introuvable'];
            return $response;
        }

        $attendanceManager = new \Agenda\Entities\AttendanceManager();
        $user = \Core\Auth\UsersManager::getCurrentUser();
        
        if ($user) {
            $isAdded = $attendanceManager->toggleAttendance($id, (int)$user->attributes['id']);
        } else {
            $identifier = \Agenda\Entities\AttendanceManager::generateAnonymousIdentifier();
            $isAdded = $attendanceManager->toggleAttendance($id, null, $identifier);
        }

        $attendanceCount = $attendanceManager->getAttendanceCount($id);

        $response->status = ApiResponse::STATUS_SUCCESS;
        $response->body = [
            'isAttending' => $isAdded,
            'count' => $attendanceCount
        ];

        return $response;
    }

    public function apiEvents()
    {
        $response = new ApiResponse();

        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');

        $events = $this->eventManager->getByDateRange($start, $end);

        $formatted = [];
        foreach ($events as $event) {
            $formatted[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $event->getStartDate(),
                'end' => $event->getEndDate(),
                'color' => $event->getColor(),
                'url' => $event->getUrl(),
            ];
        }

        $response->status = ApiResponse::STATUS_SUCCESS;
        $response->body = $formatted;

        return $response;
    }

    public function add()
    {
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:admin'), $ctx)) {
            Show::msg('Seuls les propriétaires de POI peuvent ajouter directement des événements. Vous pouvez proposer un événement qui sera soumis à validation.', 'error');
            return $this->core->redirect('/agenda');
        }

        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-calendar-plus"></i> ' . 'Ajouter un événement');
        $tpl = $response->createPluginTemplate('agenda', 'event-form');

        $tpl->set('isProposal', false);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('actionUrl', $this->router->generate('agenda-event-add-send'));
        $tpl->set('cancelUrl', $this->router->generate('agenda-index'));

        $response->addTemplate($tpl);
        return $response;
    }

    public function addSend()
    {
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:admin'), $ctx)) {
            Show::msg('Seuls les propriétaires de POI peuvent ajouter directement des événements.', 'error');
            return $this->core->redirect('/agenda');
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'end_date' => trim($_POST['end_date'] ?? trim($_POST['start_date'] ?? '')),
            'location' => trim($_POST['location'] ?? ''),
            'color' => trim($_POST['color'] ?? '#3498db'),
            'status' => 'approved', // Directement approuvé pour les propriétaires
            'created_by' => (int) $user->attributes['id'],
            'poi_id' => isset($_POST['poi_id']) ? (int) $_POST['poi_id'] : null,
            'latitude' => $_POST['latitude'] ?? null,
            'longitude' => $_POST['longitude'] ?? null,
        ];

        if (empty($data['title']) || empty($data['start_date'])) {
            Show::msg('Le titre et la date de début sont obligatoires.', 'error');
            return $this->core->redirect($this->router->generate('agenda-event-add'));
        }

        $event = $this->eventManager->create($data);
        if ($event) {
            Show::msg('Événement créé avec succès.', 'success');
        } else {
            Show::msg('Erreur lors de la création de l\'événement.', 'error');
        }

        return $this->core->redirect('/agenda');
    }

    public function propose()
    {
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:post'), $ctx)) {
            Show::msg('Vous devez être connecté pour proposer un événement.', 'error');
            return $this->core->redirect('/agenda');
        }

        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-lightbulb"></i> ' . 'Proposer un événement');
        $tpl = $response->createPluginTemplate('agenda', 'event-form');

        $tpl->set('isProposal', true);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('actionUrl', $this->router->generate('agenda-event-propose-send'));
        $tpl->set('cancelUrl', $this->router->generate('agenda-index'));

        $response->addTemplate($tpl);
        return $response;
    }

    public function proposeSend()
    {
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('agenda:post'), $ctx)) {
            Show::msg('Vous devez être autorisé.', 'error');
            return $this->core->redirect('/agenda');
        }

        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/agenda');
        }

        // Validation du fichier de preuve (obligatoire)
        if (!isset($_FILES['proof_file']['error']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
            Show::msg('Le fichier de preuve est obligatoire pour proposer un événement.', 'error');
            return $this->core->redirect($this->router->generate('agenda-event-propose'));
        }

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'doc', 'docx'];
        $uploadDir = DATA_PLUGIN . 'agenda' . DS . 'proofs' . DS;

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $baseName = \Utils\Util::strToUrl(pathinfo($_FILES['proof_file']['name'], PATHINFO_FILENAME)) . '-' . time();

        $result = \Utils\Util::uploadFile('proof_file', $uploadDir, $baseName, [
            'extensions' => $allowedExtensions,
            'size' => 10 * 1024 * 1024 // 10MB
        ]);

        if ($result !== 'success') {
            if ($result === 'extension error') {
                Show::msg('Format de fichier non autorisé. Formats acceptés : ' . implode(', ', $allowedExtensions), 'error');
            } elseif ($result === 'size error') {
                Show::msg('Le fichier est trop volumineux. Taille maximale : 10MB', 'error');
            } else {
                Show::msg('Erreur lors de l\'upload du fichier de preuve.', 'error');
            }
            return $this->core->redirect($this->router->generate('agenda-event-propose'));
        }

        // Chemin relatif pour stockage dans la base de données
        $uploadedExt = mb_strtolower(\Utils\Util::getFileExtension($_FILES['proof_file']['name']));
        $targetFileName = $baseName . '.' . $uploadedExt;
        $proofFilePath = 'agenda/proofs/' . $targetFileName;
        $targetPath = $uploadDir . $targetFileName;

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'end_date' => trim($_POST['end_date'] ?? trim($_POST['start_date'] ?? '')),
            'location' => trim($_POST['location'] ?? ''),
            'color' => trim($_POST['color'] ?? '#3498db'),
            'status' => 'pending', // En attente de validation
            'created_by' => (int) $user->attributes['id'],
            'poi_id' => isset($_POST['poi_id']) ? (int) $_POST['poi_id'] : null,
            'proof_file' => $proofFilePath, // Chemin du fichier de preuve
            'latitude' => $_POST['latitude'] ?? null,
            'longitude' => $_POST['longitude'] ?? null,
        ];

        if (empty($data['title']) || empty($data['start_date'])) {
            // Supprimer le fichier uploadé si la validation échoue
            @unlink($targetPath);
            Show::msg('Le titre et la date de début sont obligatoires.', 'error');
            return $this->core->redirect($this->router->generate('agenda-event-propose'));
        }

        $event = $this->eventManager->create($data);
        if ($event) {
            Show::msg('Événement proposé avec succès. Il sera soumis à validation par un administrateur.', 'success');
        } else {
            // Supprimer le fichier uploadé si la création échoue
            @unlink($targetPath);
            Show::msg('Erreur lors de la proposition de l\'événement.', 'error');
        }

        return $this->core->redirect('/agenda');
    }
}

