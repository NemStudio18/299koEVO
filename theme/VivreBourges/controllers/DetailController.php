<?php

namespace SearchUI\Controllers;

use Core\Responses\PublicResponse;
use Core\Plugin\PluginsManager;
use Core\Auth\UsersManager;
use SearchEngine\Services\Search\SearchService;
use PoiData\Services\CategoryLabelTranslator;
use PoiData\Services\PoiDataFormatter;
use PoiData\Services\PoiCategoryService;
use Core\Core;
use PoiData\Services\Poi\PoiRepository;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

/**
 * Controller pour la page de détail d'un POI (detail.tpl)
 */
class DetailController extends BaseSearchController
{

    /**
     * Page de détail d'un POI (URL courte : /nomdupoi)
     * @param string $name Nom du POI (encodé dans l'URL)
     */
    public function detailShort(string $name)
    {
        // Décoder le nom pour vérification
        // urldecode() convertit les + en espaces, ce qui est correct pour les URLs
        $decodedName = urldecode($name);
        // Nettoyer les espaces en début/fin
        $decodedName = trim($decodedName);

        // Liste des préfixes de routes système à exclure (ne pas traiter comme des POIs)
        $systemRoutePrefixes = [
            'search',
            'admin',
            'users',
            'api',
            'login',
            'logout',
            'register',
            'profile',
            'lost-password',
            'index.php',
            'index',
            'home',
            'contact',
            'about',
            'legal',
            'privacy',
            'terms',
            'sitemap',
            'robots.txt',
            'favicon.ico',
            // Plugins
            'meteo-widget',
            'blog',
            'docs',
            'page',
            'plugin',
            'agenda',
            'offres',
            'offers',
            'claim',
            'subscription',
            'messaging',
            'forum',
            'poi-favorites',
            'poi-edit',
            'SearchUi',
            'search',
            'recherche',
            'gamification',
            // Assets
            'data',
            'assets',
            'css',
            'js',
            'img',
            'images'
        ];

        // IMPORTANT: Si le nom contient des slashes, c'est probablement une route multi-segments
        // (comme /meteo/api/current) qui ne devrait pas être capturée par cette route
        // La route /[*:name] ne devrait capturer que les routes à un seul segment
        if (strpos($decodedName, '/') !== false) {
            // Extraire le premier segment pour vérification
            $segments = explode('/', trim($decodedName, '/'));
            $firstSegment = $segments[0] ?? '';

            // Si le premier segment est un préfixe système, ne pas traiter comme un POI
            if (in_array(strtolower($firstSegment), $systemRoutePrefixes)) {
                // C'est une route système multi-segments, retourner une 404
                $this->core->error404();
                return null;
            }

            // Sinon, c'est une route multi-segments inconnue, ne pas traiter comme un POI
            $this->core->error404();
            return null;
        }

        // Vérifier si c'est une route système (premier segment de l'URL)
        $firstSegment = trim($decodedName, '/');

        // Vérifier si le nom correspond à un préfixe système
        if (in_array(strtolower($firstSegment), $systemRoutePrefixes)) {
            // C'est une route système, ne pas traiter comme un POI
            // Si on arrive ici, c'est que soit le router a matché indûment (si on n'a pas encore mis le type de match personnalisé)
            // soit il n'y a vraiment aucune route pour ce préfixe.
            $this->core->error404();
            return null;
        }

        // Vérifier aussi si l'URL contient des caractères typiques d'une route API (?, &, =)
        if (strpos($decodedName, '?') !== false || strpos($decodedName, '&') !== false || strpos($decodedName, '=') !== false) {
            // C'est probablement une route API avec paramètres, ne pas traiter comme un POI
            $this->core->error404();
            return null;
        }

        // Vérifier si c'est un fichier (extension connue)
        $extensions = ['.php', '.html', '.htm', '.css', '.js', '.json', '.xml', '.txt', '.ico', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.pdf'];
        foreach ($extensions as $ext) {
            if (stripos($decodedName, $ext) !== false) {
                // C'est un fichier, ne pas traiter comme un POI
                $this->core->error404();
                return null;
            }
        }

        // Appeler la méthode detail avec 'poi' comme type par défaut
        return $this->detail('poi', $name);
    }

    /**
     * Page de détail d'un POI
     * @param string $type Type (déprécié mais conservé pour compatibilité avec la route)
     * @param string $name Nom du POI (encodé dans l'URL)
     */
    public function detail(string $type, string $name)
    {
        $cfg = $this->getConfig();
        $service = new SearchService($cfg);
        $translator = new CategoryLabelTranslator();

        // Récupérer le POI via le service de recherche unifié
        // fetchByName utilise le PoiRepository sous-jacent
        $logger = \Core\Core::getInstance()->getLogger();
        try {
            $logger->log(\Core\Logger::LEVEL_DEBUG, "DetailController::detail - Recherche du POI avec name='{$name}', type='{$type}'");
            $row = $service->fetchByName($name);

            if (!$row || empty($row['id'])) {
                $logger->log(\Core\Logger::LEVEL_WARNING, "DetailController::detail - POI non trouvé pour name='{$name}'");
                // TEMP DEBUG
                $logger->log(\Core\Logger::LEVEL_DEBUG, "DetailController::detail - DEBUG: fetchByName('{$name}') failed. Redirecting to home (error404)");
                $this->core->error404();
                return null;
            }

            $logger->log(\Core\Logger::LEVEL_DEBUG, "DetailController::detail - POI trouvé: id={$row['id']}, name='{$row['name']}'");

            // Vérifier si on est sur la route longue /search/poi/xxxx et rediriger vers l'URL courte /xxxx
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            if (($pos = strpos($requestUri, '?')) !== false) {
                $requestUri = substr($requestUri, 0, $pos);
            }

            // Si l'URL commence par /search/poi/ ou /search/type/, rediriger vers l'URL courte
            if (preg_match('#^/search/[^/]+/(.+)$#', $requestUri)) {
                $urlName = $service->generateUrlName($row['name'], $row['id']);
                $logger->log(\Core\Logger::LEVEL_DEBUG, "DetailController::detail - Redirection vers URL courte: /" . $urlName);
                // Rediriger vers l'URL courte
                $this->core->redirect('/' . $urlName);
                return null;
            }
        } catch (\Exception $e) {
            $logger->log(\Core\Logger::LEVEL_ERROR, 'DetailController::detail - Erreur: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            $this->core->error404();
            return null;
        }

        // Initialiser le service de catégories pour les overrides
        $pdo = \Core\Database::getInstance()->getPdo();
        $categoryService = new PoiCategoryService($pdo);

        // Utiliser le formateur centralisé avec le service de catégories
        $poi = PoiDataFormatter::format($row, $translator, false, $categoryService, null, false, (int)$row['id']);

        // Enrichir avec les données spécifiques à la recherche (favoris, stats, etc.)
        $user = \Core\Auth\UsersManager::getCurrentUser();
        if ($user && \Core\Plugin\PluginsManager::isActivePlugin('PoiFavorites')) {
            try {
                $favManager = new \PoiFavorites\Entities\FavoriteManager();
                $poi['is_favorite'] = $favManager->isFavorite($user->id, $poi['id']);
            } catch (\Exception $e) {
                $poi['is_favorite'] = false;
            }
        } else {
            $poi['is_favorite'] = false;
        }

        // Récupérer les avis
        if (\Core\Plugin\PluginsManager::isActivePlugin('PoiReviews')) {
            try {
                $reviewManager = new \PoiReviews\Entities\ReviewManager();
                $poi['reviews'] = $reviewManager->getByPoi($poi['id']);
                $poi['review_count'] = count($poi['reviews']);
            } catch (\Exception $e) {
                $poi['reviews'] = [];
                $poi['review_count'] = 0;
            }
        }

        // Type pour les URLs
        $poiType = $poi['type'] ?? 'poi';
        
        try {
            $loginUrl = $this->router->generate('login');
        } catch (\Exception $e) {
            $loginUrl = '/login';
        }

        // URL de detail (conservée car utilisée par d'autres parties du template)
        $urlName = $service->generateUrlName($poi['name'], $poi['id']);
        try {
            $poi['detail_url'] = $this->router->generate('SearchUi-detail', ['type' => $poiType, 'name' => $urlName]);
            $poi['detail_url_short'] = '/' . $urlName;
        } catch (\Exception $e) {
            $poi['detail_url'] = '/SearchUi/' . urlencode($poiType) . '/' . $urlName;
            $poi['detail_url_short'] = '/' . $urlName;
        }

        // URL d'édition admin (conservée pour l'admin header)
        try {
            $poi['admin_edit_url'] = $this->router->generate('admin-poimanager-edit', ['id' => $poi['id']]);
        } catch (\Exception $e) {
            $poi['admin_edit_url'] = '/admin/PoiManager/edit/' . $poi['id'];
        }

        // URL d'édition publique (Suggestion)
        try {
            $poi['edit_url'] = $this->router->generate('poimanager-suggest', ['id' => $poi['id']]);
        } catch (\Exception $e) {
            $poi['edit_url'] = '/PoiManager/suggest/' . $poi['id'];
        }

        // URL de claim
        try {
            $poi['claim_url'] = $this->router->generate('claim-start', ['id' => $poi['id']]);
        } catch (\Exception $e) {
            $poi['claim_url'] = '/claim/' . $poi['id'];
        }

        // Définir les métas de la page
        $title = $poi['name'] ?? 'Détail du lieu';
        if ($this->runPlugin) {
            $this->runPlugin->setMainTitle($title);
            $this->runPlugin->setTitleTag($title);
        }

        // DECOUPLING: Get offers via hook
        $activeOffers = [];
        $isOffersActive = false;
        try {
            // Check if plugin is active first
            $pluginOffers = \Core\Plugin\PluginsManager::getInstance()->getPlugin('Offers');
            $isOffersActive = $pluginOffers && $pluginOffers->isInstalled() && $pluginOffers->getConfigVal('activate');

            $offersData = $this->core->callHook('poi:get-offers', [
                'poi_id' => (int) $poi['id'],
                'offers' => []
            ]);

            if (!empty($offersData['offers'])) {
                $activeOffers = $offersData['offers'];
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        // [MOD] Gestion du mode embed (pour affichage dans la sidebar)
        $isEmbed = isset($_GET['embed']) || isset($_GET['is_embed']) || (isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST']);
        
        if ($isEmbed) {
            $response = new \Core\Responses\HtmxResponse();
            $tpl = $response->createPluginTemplate('SearchUi', 'detail');
            $tpl->set('isEmbed', true);
        } else {
            $response = new PublicResponse();
            $tpl = $response->createPluginTemplate('SearchUi', 'detail');
            $tpl->set('isEmbed', false);
        }

        // [MOD] Encart Publicitaire: Top 5 VIP (Désactivé si réclamé + premium)
        $showAd = true;
        $isPremium = !empty($poi['is_premium']) || !empty($row['is_premium']); // Selon availability
        if (!empty($poi['claimed_by']) && $isPremium) {
            $showAd = false;
        }

        $topPois = [];
        if ($showAd) {
            try {
                $stmt = $pdo->query("SELECT s.poi_id as id, p.name, t.value_text as logo, p.category, p.subcategory, 
                                            p.addr_housenumber, p.addr_street, p.addr_postcode, p.addr_city, s.lat, s.lon,
                                            p.last_activity_at
                                     FROM poi_search s 
                                     INNER JOIN poi_public p ON s.poi_id = p.id 
                                     LEFT JOIN poi_tags t ON t.poi_id = p.id AND t.key_name = 'logo'
                                     WHERE s.status = 'public' 
                                     ORDER BY p.last_activity_at DESC, s.id DESC 
                                     LIMIT 5");
                $topRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($topRows as $topRow) {
                    // Eviter d'afficher l'établissement en cours dans son propre bloc pub
                    if ($topRow['id'] == $poi['id']) continue;
                    $topPois[] = PoiDataFormatter::format($topRow, $translator, true, $categoryService);
                }
                // Si on a plus de 5 après filtrage (impossible via limit 5 mais au cas où on voudrait exactement 5)
            } catch (\Exception $e) {
                // Erreur silencieuse
            }
        }
        
        $tpl->set('showAd', $showAd);
        $tpl->set('topPois', $topPois);

        $tpl->set('poi', $poi);
        $tpl->set('translator', $translator);
        $tpl->set('isOffersActive', $isOffersActive);
        $tpl->set('activeOffers', $activeOffers);
        $tpl->set('loginUrl', $loginUrl);
        $user = UsersManager::getCurrentUser();
        $isLogged = $user !== null;

        // [HOOK] Analytics: Track View
        $this->core->callHook('poi:show', [
            'poi_id' => (int) $poi['id'],
            'poi' => $poi,
            'user' => $user
        ]);
        
        // --- UNIVERSAL POLICY INTEGRATION (Phase 10) ---
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user, [
            'id' => (int)$poi['id'],
            'type' => 'poi'
        ]);
        
        // Check for ownership/management
        $isOwner = false;
        $claimedBy = $poi['claimed_by'] ?? null;
        if ($user && $claimedBy !== null && (int)$user->getId() === (int)$claimedBy) {
            $isOwner = true;
        }

        // --- ADMIN SIMULATION MODE ---
        $isAdmin = false;
        $isSimulatingOwner = false;
        if ($user) {
            $isAdmin = $evaluator->isAdmin($ctx);
            if ($isAdmin && isset($_GET['sim_owner']) && $_GET['sim_owner'] == '1') {
                $isOwner = true;
                $isSimulatingOwner = true;
                // In simulation mode, we want the UI to hide admin-specific tools
                // and show owner-only tools to test the owner experience.
                $isAdmin = false; 
            }
        }
        // -----------------------------

        // On récupère les permissions atomiques pour le template
        if ($user) {
            $canEdit = $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:edit'), $ctx);
            $canFavorite = $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:favorite'), $ctx);
            $canReview = $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:review:write'), $ctx);
        } else {
            $canEdit = false;
            $canFavorite = false;
            $canReview = false;
        }

        // Ownership rules for "Propose a correction"
        // Admins and owners don't use the "Corriger" button — they have more powerful tools
        // Everyone else (logged or not) can suggest a correction unless POI is claimed (then owner manages it)
        $hasOwner = ($claimedBy !== null);
        // Visitors toujours autorisés à voir Corriger/Chez moi, sauf si leur rôle direct s'y oppose
        $canProposeCorrection = !$isOwner && !$isAdmin;
        
        // Initialisation des variables pour le template (seront envoyées à la fin)
        $tplVars = [
            'IS_LOGGED' => (bool)$isLogged,
            'IS_ADMIN' => (bool)$isAdmin,
            'IS_OWNER' => (bool)$isOwner,
            'IS_SIMULATING_OWNER' => (bool)$isSimulatingOwner,
            'CAN_EDIT' => (bool)$canEdit,
            'CAN_PROPOSE_CORRECTION' => (bool)$canProposeCorrection,
            'CAN_FAVORITE' => (bool)$canFavorite,
            'CAN_REVIEW' => (bool)$canReview,
            'CURRENT_USER_ID' => $user ? (int)$user->id : 0
        ];
        // ------------------------------------------------

        // DECOUPLING: Get favorites via hook
        $isFavoritesActive = false;
        $isFavorite = false;
        $toggleFavoriteUrl = '/PoiFavorites/toggle';

        $favoritesData = $this->core->callHook('poi:get-user-favorite-status', [
            'poi_id' => (int) $poi['id'],
            'user' => $user,
            'is_favorite' => false
        ]);

        if (isset($favoritesData['is_favorite'])) {
            $isFavoritesActive = true;
            $isFavorite = $favoritesData['is_favorite'];
            $toggleFavoriteUrl = $favoritesData['toggle_favorite_url'] ?? '/PoiFavorites/toggle';
        }

        $tpl->set('isFavoritesActive', $isFavoritesActive);
        $tpl->set('isFavorite', $isFavorite);
        $tpl->set('toggleFavoriteUrl', $toggleFavoriteUrl);
        $tpl->set('csrfToken', \Core\Security\Csrf::token());


        // URLs des actions (Signaler, Favoris, Claim, Correction)
        // Pour les Guests, on redirige vers le login avec un message spécifique
        try {
            $reportPoiUrl = $this->router->generate('SearchUi-api-report-poi');
        } catch (\Exception $e) {
            $reportPoiUrl = '/SearchUi/api/report-poi';
        }
        
        $actionRedirects = [
            'report' => $isLogged ? $reportPoiUrl : $loginUrl . '?msg=report',
            'favorite' => $isLogged ? $toggleFavoriteUrl : $loginUrl . '?msg=favorite',
            'claim' => $isLogged ? $poi['claim_url'] : $loginUrl . '?msg=claim',
            'edit' => $isLogged ? $poi['edit_url'] : $loginUrl . '?msg=edit'
        ];
        $tpl->set('actionUrls', $actionRedirects);
        $tpl->set('reportPoiUrl', $reportPoiUrl);

        // DECOUPLING: Get reviews via hook
        $reviewsData = $this->core->callHook('poi:get-reviews', [
            'poi_id' => (int) $poi['id'],
            'average_rating' => 0,
            'rating_count' => 0,
            'reviews_url' => '#',
            'add_review_url' => '#'
        ]);

        $averageRating = $reviewsData['average_rating'] ?? 0;
        $ratingCount = $reviewsData['rating_count'] ?? 0;
        $reviewsUrl = $reviewsData['reviews_url'] ?? '#';
        
        $pluginReviews = PluginsManager::getInstance()->getPlugin('PoiReviews');
        $isReviewsActive = $pluginReviews && $pluginReviews->isInstalled() && $pluginReviews->getConfigVal('activate');

        // ... (reviews data loaded inline above)


        // [MOD] Chargement DIRECT des avis pour éviter le chargement HTMX
        $reviews = [];
        try {
            if (class_exists('\PoiReviews\Entities\ReviewManager')) {
                $reviewManager = new \PoiReviews\Entities\ReviewManager();
                $reviewsRaw = $reviewManager->getByPoi((int)$poi['id']);
                
                foreach ($reviewsRaw as $review) {
                    $item = $review->toArray();
                    
                    // Enrichir avec le nom de l'auteur
                    /** @var \Core\Auth\User|null $author */
                    $author = \Core\Auth\User::findPK($review->getUserId());
                    $item['author_name'] = $author ? $author->username() : 'Anonyme';
                    
                    // Avatar / Initiales
                    $nameFirstChar = mb_substr($item['author_name'], 0, 1);
                    $item['avatar_initials'] = mb_strtoupper($nameFirstChar);
                    $item['avatar_color'] = '#' . substr(md5($item['author_name']), 0, 6);
                    
                    // Formattage date
                    $createdAt = $item['created_at'] ?? date('Y-m-d H:i:s');
                    $item['created_at_formatted'] = date('d/m/Y', strtotime($createdAt));
                    
                    // Étoiles de l'avis
                    $reviewStars = [];
                    for ($j = 1; $j <= 5; $j++) {
                        $reviewStars[] = ['isFull' => $j <= $review->getRating()];
                    }
                    $item['stars'] = $reviewStars;
                    
                    $reviews[] = $item;
                }
            }
        } catch (\Exception $e) {
            $this->core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "Erreur chargement avis: " . $e->getMessage());
        }
        $tplVars['averageRating'] = $averageRating;
        $tplVars['ratingCount'] = $ratingCount;
        $tplVars['reviewsUrl'] = $reviewsUrl;
        $tplVars['reviews'] = $reviews;
        $tplVars['isReviewsActive'] = $isReviewsActive;

        // Préparer les étoiles pour le header
        $stars = [];
        for ($i = 1; $i <= 5; $i++) {
            $stars[] = [
                'isFull' => $i <= $averageRating,
                'isHalf' => ($i - 0.5) <= $averageRating && $i > $averageRating
            ];
        }
        $tpl->set('averageStars', $stars);
        
        // Données pour le formulaire de dépôt d'avis
        $userReview = null;
        if ($isLogged) {
            try {
                if (isset($reviewManager)) {
                    $userReview = $reviewManager->getUserReviewForPoi((int)$poi['id'], \Core\Auth\UsersManager::getCurrentUser()->id);
                }
            } catch (\Exception $e) {}
        }
        $tpl->set('userReview', $userReview);
        $tpl->set('poiId', $poi['id']);
        $tpl->set('addReviewUrl', $reviewsData['add_review_url'] ?? '#');

        // Ajouter loginUrl et isLogged
        $tplVars['IS_LOGGED'] = (bool)$isLogged;
        $tplVars['loginUrl'] = $loginUrl;
        $tplVars['isReviewsActive'] = (bool)$isReviewsActive;

        // DECOUPLING: Get user favorite status via hook
        $favoritesData = $this->core->callHook('poi:get-user-favorite-status', [
            'poi_id' => (int)$poi['id'],
            'user' => $isLogged ? UsersManager::getCurrentUser() : null,
            'is_favorite' => false,
            'toggle_favorite_url' => '/poi-favorites/toggle'
        ]);

        $tpl->set('isFavorite', $favoritesData['is_favorite'] ?? false);
        $tpl->set('toggleFavoriteUrl', $favoritesData['toggle_favorite_url'] ?? '/PoiFavorites/toggle');
        $tpl->set('csrfToken', \Core\Security\Csrf::token());

        // DECOUPLING: Get QR display via hook
        $qrData = $this->core->callHook('poi:get-qr-display', [
            'poi_id' => (int) $poi['id'],
            'user' => $isLogged ? UsersManager::getCurrentUser() : null,
            'show_qr_code' => false,
            'qr_code_url' => null,
            'is_owner' => false
        ]);

        $tpl->set('isQrPoiActive', !empty($qrData['is_active']));
        $tpl->set('showQrCode', !empty($qrData['show_qr_code']));
        $tpl->set('qrCodeUrl', $qrData['qr_code_url'] ?? null);
        $tpl->set('isQrOwner', $qrData['is_owner'] ?? false);
        
        // Activation des plugins pour le template
        $isPoiEditActive = (PluginsManager::getInstance()->isActivePlugin('PoiEdit') && PluginsManager::getInstance()->getPlugin('PoiEdit')->getConfigVal('activate'));
        $tpl->set('isPoiEditActive', $isPoiEditActive);

        // Flags pour les onglets
        $hasEvents = false;
        $isAgendaActive = false;
        try {
            $pluginAgenda = \Core\Plugin\PluginsManager::getInstance()->getPlugin('Agenda');
            $isAgendaActive = $pluginAgenda && $pluginAgenda->isInstalled() && $pluginAgenda->getConfigVal('activate');

            if (class_exists('\Agenda\Entities\Event')) {
                $hasEvents = \Agenda\Entities\Event::query()->where('poi_id', '=', (int)$poi['id'])->count() > 0;
            }
        } catch (\Exception $e) {}

        $tplVars['hasDescription'] = !empty($poi['description']);
        $tplVars['hasPhotos'] = !empty($poi['photos']);
        $tplVars['hasReviews'] = $ratingCount > 0 || $isLogged;
        $tplVars['hasEvents'] = $hasEvents;
        $tplVars['isAgendaActive'] = $isAgendaActive;
        
        // URL pour gérer événements/offres/QR (espace pro)
        // Routes admin (pour vrais admins)
        try {
            $adminEventsUrl = $this->router->generate('admin-agenda-events-add');
            $adminOffersUrl = $this->router->generate('admin-offers-add');
            $adminQrUrl = $this->router->generate('admin-qr-poi-generate', ['poi_id' => $poi['id']]);
        } catch (\Exception $e) {
            $adminEventsUrl = '/admin/agenda/events/add';
            $adminOffersUrl = '/admin/offers/add';
            $adminQrUrl = '/admin/qr-poi/generate/' . $poi['id'];
        }
        
        // Routes publiques propriétaire (pour owners et admin en simulation)
        try {
            $ownerEventsUrl = $this->router->generate('agenda-event-add');
        } catch (\Exception $e) {
            $ownerEventsUrl = '/agenda/event/add';
        }
        try {
            $ownerOffersUrl = $this->router->generate('offers-add');
        } catch (\Exception $e) {
            $ownerOffersUrl = '/offers/add';
        }
        try {
            $ownerQrUrl = $this->router->generate('qr-poi-manage', ['poi_id' => $poi['id']]);
        } catch (\Exception $e) {
            $ownerQrUrl = '/qr-poi/manage/' . $poi['id'];
        }

        // Rétrocompatibilité (ancien nom)
        $manageEventsUrl = $adminEventsUrl;
        $manageOffersUrl = $adminOffersUrl;
        $manageQrUrl = $adminQrUrl;

        $tplVars['adminEventsUrl'] = $adminEventsUrl;
        $tplVars['adminOffersUrl'] = $adminOffersUrl;
        $tplVars['adminQrUrl'] = $adminQrUrl;
        $tplVars['ownerEventsUrl'] = $ownerEventsUrl;
        $tplVars['ownerOffersUrl'] = $ownerOffersUrl;
        $tplVars['ownerQrUrl'] = $ownerQrUrl;
        $tplVars['manageEventsUrl'] = $manageEventsUrl;
        $tplVars['manageOffersUrl'] = $manageOffersUrl;
        $tplVars['manageQrUrl'] = $manageQrUrl;

        // On envoie toutes les variables au template
        foreach ($tplVars as $key => $val) {
            $tpl->set($key, $val);
        }

        $response->setTitle($title);
        $response->addTemplate($tpl);

        return $response;
    }

    /**
     * API: Signaler un POI
     */
    public function reportPoi()
    {
        header('Content-Type: application/json');
        $poiId = $_POST['poi_id'] ?? null;
        $type = $_POST['report_type'] ?? 'other';
        $reason = $_POST['reason'] ?? '';
        $user = \Core\Auth\UsersManager::getCurrentUser();

        if (!$poiId) {
            echo json_encode(['success' => false, 'error' => 'ID manquant']);
            return;
        }

        try {
            // Log the report (could be sent to a DB or email later)
            $this->core->getLogger()->log(\Core\Logger::LEVEL_INFO, "Signalement POI #{$poiId} par " . ($user ? $user->username() : 'Anonyme') . " type: {$type}, raison: {$reason}");
            
            // For now, consider it a success as the BugTracker logic is not fully integrated for POIs specifically here
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Détecte l'icône à utiliser pour un lien de réseau social
     */
    private function detectSocialIcon(string $url, string $label): string
    {
        $urlLower = strtolower($url);
        $labelLower = strtolower($label);

        if (strpos($urlLower, 'facebook') !== false || strpos($labelLower, 'facebook') !== false) {
            return 'fa-brands fa-facebook';
        } elseif (strpos($urlLower, 'instagram') !== false || strpos($labelLower, 'instagram') !== false) {
            return 'fa-brands fa-instagram';
        } elseif (strpos($urlLower, 'twitter') !== false || strpos($urlLower, 'x.com') !== false || strpos($labelLower, 'twitter') !== false) {
            return 'fa-brands fa-x-twitter';
        } elseif (strpos($urlLower, 'linkedin') !== false || strpos($labelLower, 'linkedin') !== false) {
            return 'fa-brands fa-linkedin';
        } else {
            return 'fa-solid fa-link';
        }
    }

}

