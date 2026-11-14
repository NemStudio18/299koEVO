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

        $saturday = date('Y-m-d', strtotime('saturday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));
        $currentMonth = date('Y-m');
        $monthNames = ['01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr', '05' => 'Mai', '06' => 'Juin', '07' => 'Juil', '08' => 'Août', '09' => 'Sept', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'];

        $allUpcoming = [];
        $allPast = [];
        foreach ($allEvents as $event) {
            $eventData = $event->toArray();
            $eventData['url'] = $this->router->generate('agenda-event', ['id' => $event->getId()]);
            
            // Plain text & sanitized short description for cards (avoids breaking HTML attributes and layout)
            $rawDesc = $event->getDescription() ?? '';
            $plainDesc = trim(preg_replace('/\s+/', ' ', strip_tags($rawDesc)));
            $eventData['plain_description'] = htmlspecialchars($plainDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $eventData['short_description'] = mb_strlen($plainDesc) > 140 ? mb_substr($plainDesc, 0, 140) . '…' : $plainDesc;

            $startDate = $event->getStartDate();
            $endDate = $event->getEndDate() ?: $startDate;
            $timeStart = strtotime($startDate);

            // Format dates for display
            $eventData['start_date_display'] = date($dateFormat, $timeStart);
            $eventData['end_date_display'] = date($dateFormat, strtotime($endDate));
            $eventData['is_multi_day'] = ($startDate !== $endDate);

            // Visual date parts
            $eventData['day_num'] = date('d', $timeStart);
            $eventData['month_short'] = $monthNames[date('m', $timeStart)] ?? date('M', $timeStart);
            $eventData['year'] = date('Y', $timeStart);

            // Intelligent Categorization
            $t = mb_strtolower($event->getTitle() . ' ' . $event->getDescription());
            $loc = mb_strtolower($event->getLocation() ?? '');
            $eventId = (string)$event->getId();

            $isCgr = ($eventId === 'cgr_master_bourges' || str_contains($t, 'cgr') || str_contains($loc, 'cgr'));
            $isAlhambra = (str_contains($t, 'alhambra') || str_contains($loc, 'alhambra') || str_contains($t, 'maison de la culture') || str_contains($loc, 'maison de la culture'));
            $eventData['is_cgr'] = $isCgr;
            $eventData['is_alhambra'] = $isAlhambra;

            if ($isCgr) {
                $eventData['category'] = 'Méga CGR';
                $eventData['category_slug'] = 'cinema';
                $eventData['category_icon'] = 'fa-film';
            } elseif (str_contains($t, 'cinéma') || str_contains($t, 'cinema') || str_contains($t, 'film') || str_contains($t, 'séance') || str_contains($t, 'seance') || str_contains($t, 'projection') || $isAlhambra) {
                $eventData['category'] = 'Cinéma';
                $eventData['category_slug'] = 'cinema';
                $eventData['category_icon'] = 'fa-film';
            } elseif (str_contains($t, 'exposition') || str_contains($t, 'expo') || str_contains($t, 'vernissage') || str_contains($t, 'sculpture') || str_contains($t, 'peinture') || str_contains($t, 'dessin')) {
                $eventData['category'] = 'Expositions';
                $eventData['category_slug'] = 'exposition';
                $eventData['category_icon'] = 'fa-palette';
            } elseif (str_contains($t, 'concert') || str_contains($t, 'musique') || str_contains($t, 'jam') || str_contains($t, 'chanson') || str_contains($t, 'modulaire') || str_contains($t, 'orchestre') || str_contains($t, 'jazz') || str_contains($t, 'rock')) {
                $eventData['category'] = 'Musique & Concerts';
                $eventData['category_slug'] = 'musique';
                $eventData['category_icon'] = 'fa-music';
            } elseif (str_contains($t, 'théâtre') || str_contains($t, 'theatre') || str_contains($t, 'spectacle') || str_contains($t, 'danse') || str_contains($t, 'comédie') || str_contains($t, 'molière') || str_contains($t, 'décor') || str_contains($t, 'scène')) {
                $eventData['category'] = 'Théâtre & Spectacles';
                $eventData['category_slug'] = 'theatre';
                $eventData['category_icon'] = 'fa-masks-theater';
            } elseif (str_contains($t, 'atelier') || str_contains($t, 'initiation') || str_contains($t, 'stage') || preg_match('/\b(cours|conférence|conference|débat|debat)\b/u', $t)) {
                $eventData['category'] = 'Ateliers & Conférences';
                $eventData['category_slug'] = 'atelier';
                $eventData['category_icon'] = 'fa-lightbulb';
            } elseif (str_contains($t, 'enfant') || str_contains($t, 'jeunesse') || str_contains($t, 'famille') || str_contains($t, 'scolaire') || str_contains($t, 'élève')) {
                $eventData['category'] = 'Jeune Public';
                $eventData['category_slug'] = 'jeune-public';
                $eventData['category_icon'] = 'fa-child';
            } else {
                $eventData['category'] = 'Animations & Culture';
                $eventData['category_slug'] = 'culture';
                $eventData['category_icon'] = 'fa-calendar-star';
            }

            // Period tags for JS filtering
            $periodTags = ['all'];
            if ($startDate <= $now && $endDate >= $now) {
                $periodTags[] = 'today';
                $eventData['is_today'] = true;
            } else {
                $eventData['is_today'] = false;
            }

            if ($startDate <= $sunday && $endDate >= $saturday) {
                $periodTags[] = 'weekend';
                $eventData['is_weekend'] = true;
            } else {
                $eventData['is_weekend'] = false;
            }

            if (str_starts_with($startDate, $currentMonth) || str_starts_with($endDate, $currentMonth)) {
                $periodTags[] = 'month';
                $eventData['is_this_month'] = true;
            } else {
                $eventData['is_this_month'] = false;
            }
            $eventData['period_tags'] = implode(' ', $periodTags);

            // Badge Price
            if (stripos($event->getDescription(), 'gratuit') !== false) {
                $eventData['badge_price'] = 'Gratuit';
            } else {
                $eventData['badge_price'] = '';
            }

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

        // Pagination basée sur les événements à venir (afficher 30 par page pour une grille aérée)
        $items_per_page = $plugin->getConfigVal('items_per_page') ?: $plugin->getConfigVal('eventsPerPage') ?: 30;
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

        // Programmation complète du Méga CGR de Bourges
        $cgrMovies = [];
        $cgrFile = DATA_PLUGIN . 'agenda/cgr_movies.json';
        if (file_exists($cgrFile)) {
            $cgrMovies = json_decode(file_get_contents($cgrFile), true) ?: [];
        }
        $tpl->set('cgrMovies', $cgrMovies);
        $tpl->set('cgrCount', count($cgrMovies));

        $tpl->set('events', $upcomingEvents);
        $tpl->set('upcomingEvents', $upcomingEvents);
        $tpl->set('pastEvents', $pastEvents);
        $tpl->set('totalUpcomingCount', $totalItems);
        $tpl->set('totalPastCount', count($allPast));
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
        $imageUrl = $event->getImageUrl();
        $tpl->set('has_image', !empty($imageUrl));
        $tpl->set('event_image_url', $imageUrl);
        $externalUrl = $event->getExternalUrl();
        $tpl->set('has_external_url', !empty($externalUrl));
        $tpl->set('event_external_url', $externalUrl);

        // Balises Open Graph pour le partage Facebook
        $core = \Core\Core::getInstance();
        $siteUrl = rtrim($core->getConfigVal('siteUrl') ?: '', '/');
        $eventUrl = $siteUrl . '/agenda/event/' . $id;
        $eventDesc = trim(strip_tags(html_entity_decode($event->getDescription() ?? 'Événement', ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $eventImg = $event->getImageUrl() ?: '/theme/' . ($core->getConfigVal('theme') ?: 'default') . '/logo.png';
        if (!preg_match('#^https?://#i', $eventImg)) {
            $eventImg = $siteUrl . (str_starts_with($eventImg, '/') ? '' : '/') . $eventImg;
        }
        $siteName = $core->getConfigVal('siteName') ?: '299koEVO';
        $core->addMeta('<link rel="canonical" href="' . htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') . '">');
        $core->addMeta('<meta property="og:type" content="article">');
        $core->addMeta('<meta property="og:site_name" content="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '">');
        $core->addMeta('<meta property="og:title" content="' . htmlspecialchars($eventTitle . ' | Agenda ' . $siteName, ENT_QUOTES, 'UTF-8') . '">');
        $core->addMeta('<meta property="og:description" content="' . htmlspecialchars(mb_substr($eventDesc, 0, 200), ENT_QUOTES, 'UTF-8') . '">');
        $core->addMeta('<meta property="og:url" content="' . htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') . '">');
        $core->addMeta('<meta property="og:image" content="' . htmlspecialchars($eventImg, ENT_QUOTES, 'UTF-8') . '">');
        $core->addMeta('<meta property="og:image:secure_url" content="' . htmlspecialchars($eventImg, ENT_QUOTES, 'UTF-8') . '">');
        $core->addMeta('<meta name="twitter:card" content="summary_large_image">');
        $core->addMeta('<meta name="twitter:title" content="' . htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') . '">');
        $core->addMeta('<meta name="twitter:image" content="' . htmlspecialchars($eventImg, ENT_QUOTES, 'UTF-8') . '">');
        $tpl->set('event_share_url', $eventUrl);
        $tpl->set('fb_share_url', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($eventUrl));

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
            } catch (\Throwable $e) {}
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
            } catch (\Throwable $e) {}
        }
        $tpl->set('linkedOffers', $linkedOffers);

        // Injecter les films du Méga CGR si événement CGR
        $cgrMovies = [];
        $isCgrMaster = ($id === 'cgr_master_bourges' || stripos($eventTitle, 'CGR') !== false || stripos($event->getLocation() ?? '', 'CGR') !== false);
        $cgrFile = DATA_PLUGIN . 'agenda/cgr_movies.json';
        if (file_exists($cgrFile)) {
            $raw = @file_get_contents($cgrFile);
            $cgrMovies = json_decode($raw, true) ?: [];
        }
        $tpl->set('isCgrMaster', $isCgrMaster);
        $tpl->set('cgrMovies', $cgrMovies);
        $tpl->set('cgrCount', count($cgrMovies));

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
        if (!$user) {
            Show::msg('Vous devez être connecté pour ajouter un événement.', 'error');
            return $this->core->redirect($this->router->generate('login') . '?msg=agenda-add');
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isAdmin = $evaluator->isAdmin($ctx);
        $isOwner = $this->claimService->isOwner(
            (int) $user->attributes['id'],
            $user->attributes['email'] ?? null,
            $user->attributes['username'] ?? null
        );

        if (!$isAdmin && !$isOwner) {
            Show::msg('Seuls les professionnels et propriétaires certifiés peuvent ajouter directement des événements. Vous pouvez proposer votre événement qui sera validé rapidement par notre équipe.', 'info');
            return $this->core->redirect($this->router->generate('agenda-event-propose'));
        }

        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-calendar-plus"></i> ' . 'Ajouter un événement');
        $tpl = $response->createPluginTemplate('agenda', 'event-form');

        $poiId = isset($_GET['poi_id']) ? (int)$_GET['poi_id'] : 0;
        $tpl->set('poiId', $poiId);
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
        if (!$user) {
            Show::msg('Vous devez être connecté.', 'error');
            return $this->core->redirect($this->router->generate('login'));
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isAdmin = $evaluator->isAdmin($ctx);
        $isOwner = $this->claimService->isOwner(
            (int) $user->attributes['id'],
            $user->attributes['email'] ?? null,
            $user->attributes['username'] ?? null
        );

        if (!$isAdmin && !$isOwner) {
            Show::msg('Seuls les propriétaires de fiches validées peuvent publier directement des événements.', 'error');
            return $this->core->redirect($this->router->generate('agenda-event-propose'));
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
        if (!$user) {
            Show::msg('Vous devez être connecté pour proposer un événement.', 'error');
            return $this->core->redirect($this->router->generate('login') . '?msg=agenda-propose');
        }

        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-lightbulb"></i> ' . 'Proposer un événement');
        $tpl = $response->createPluginTemplate('agenda', 'event-form');

        $tpl->set('poiId', isset($_GET['poi_id']) ? (int)$_GET['poi_id'] : 0);
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
        if (!$user) {
            Show::msg('Vous devez être connecté pour proposer un événement.', 'error');
            return $this->core->redirect($this->router->generate('login') . '?msg=agenda-propose');
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

    /**
     * Exporte un événement individuel au format iCal (.ics)
     */
    public function exportIcal($id)
    {
        $event = $this->eventManager->findById((int)$id);
        if (!$event || $event->getStatus() !== 'published') {
            Show::msg('Événement non trouvé ou non publié.', 'error');
            return $this->core->redirect('/agenda');
        }

        $ics = $this->buildIcal([$event], $event->getTitle());
        $filename = 'evenement-' . $event->getId() . '.ics';

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $ics;
        exit;
    }

    /**
     * Exporte tous les événements futurs publiés au format iCal (.ics)
     */
    public function exportAllIcal()
    {
        $today = date('Y-m-d');
        $events = $this->eventManager->findFutureEvents($today);

        $core = \Core\Core::getInstance();
        $siteName = $core->getConfigVal('siteName') ?: '299koEVO';
        $ics = $this->buildIcal($events, 'Agenda ' . $siteName);
        $filename = 'agenda-' . date('Y-m-d') . '.ics';

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $ics;
        exit;
    }

    private function buildIcal(array $events, string $calName): string
    {
        $core = \Core\Core::getInstance();
        $siteUrl = rtrim($core->getConfigVal('siteUrl') ?: 'http://localhost', '/');
        
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//299koEVO//Agenda Plugin//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . $this->escapeIcal($calName),
            'X-WR-TIMEZONE:Europe/Paris'
        ];

        $nowStr = gmdate('Ymd\THis\Z');

        foreach ($events as $event) {
            $id = $event->getId();
            $title = $event->getTitle();
            $desc = strip_tags(html_entity_decode($event->getDescription() ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $loc = $event->getLocation() ?? '';
            $url = $siteUrl . '/agenda/event/' . $id;

            $startDate = $event->getStartDate();
            $endDate = $event->getEndDate() ?: $startDate;

            $dtStart = $this->toIcalTimestamp($startDate);
            $dtEnd = $this->toIcalTimestamp($endDate);

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:agenda-' . $id . '@' . parse_url($siteUrl, PHP_URL_HOST);
            $lines[] = 'DTSTAMP:' . $nowStr;
            $lines[] = 'DTSTART:' . $dtStart;
            $lines[] = 'DTEND:' . $dtEnd;
            $lines[] = 'SUMMARY:' . $this->escapeIcal($title);
            if (!empty($desc)) {
                $lines[] = 'DESCRIPTION:' . $this->escapeIcal($desc);
            }
            if (!empty($loc)) {
                $lines[] = 'LOCATION:' . $this->escapeIcal($loc);
            }
            $lines[] = 'URL:' . $url;
            $lines[] = 'STATUS:CONFIRMED';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';
        return implode("\r\n", $lines) . "\r\n";
    }

    private function toIcalTimestamp(string $dateStr): string
    {
        $ts = strtotime($dateStr);
        if (!$ts) {
            return gmdate('Ymd\THis\Z');
        }
        // If it's a date only (Y-m-d), produce VALUE=DATE format or timestamp
        if (strlen(trim($dateStr)) <= 10) {
            return date('Ymd', $ts);
        }
        return gmdate('Ymd\THis\Z', $ts);
    }

    private function escapeIcal(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace(',', '\,', $text);
        return str_replace(["\r\n", "\n", "\r"], '\n', $text);
    }
}


