<?php

namespace Offers\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Utils\Show;
use Core\Lang;
use Offers\Entities\OfferManager;
use Offers\Services\CodeGenerator;
use Offers\Services\ClaimService;
use Offers\Services\SubscriptionService;

defined('ROOT') or exit('Access denied!');

class OffersPublicController extends PublicController
{
    private OfferManager $offerManager;
    private ClaimService $claimService;
    private SubscriptionService $subscriptionService;

    public function __construct()
    {
        parent::__construct();
        $this->offerManager = new OfferManager();
        $this->claimService = new ClaimService($this->core->getLogger());
        $this->subscriptionService = new SubscriptionService($this->core->getLogger());
    }

    public function index()
    {
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('offers', 'index');
        
        // Récupérer toutes les offres actives
        $allOffers = $this->offerManager->getAll('approved', true);
        $activeOffers = array_filter($allOffers, fn($o) => $o->isCurrentlyValid());
        
        // Collecter les IDs de POI
        $poiIds = [];
        foreach ($activeOffers as $offer) {
            $poiIds[] = (int)$offer->getPoiId();
        }
        $poiIds = array_unique($poiIds);

        // Récupérer les données POI via le Provider
        $poiDataList = [];
        try {
            $provider = $this->core->callHook('entity:provider-request', ['entity' => 'poi']);
            if (is_object($provider) && method_exists($provider, 'getMany')) {
                $poiDataList = $provider->getMany($poiIds);
            }
        } catch (\Exception $e) {
            $this->core->getLogger()->warning("Offers: index - Provider error: " . $e->getMessage());
        }

        // Préparer les offres avec leurs données POI
        $offersPrepared = [];
        foreach ($activeOffers as $offer) {
            $offerData = $offer->toArray();
            $poiId = (int)$offer->getPoiId();
            
            $offerData['viewUrl'] = $this->router->generate('offers-view', ['id' => $offer->getId()]);
            $offerData['unlockUrl'] = $this->router->generate('offers-unlock', ['id' => $offer->getId()]);
            
            // Formater les dates
            try {
                $offerData['start_date_formatted'] = date('d/m/Y', strtotime($offer->getStartDate()));
                $offerData['end_date_formatted'] = date('d/m/Y', strtotime($offer->getEndDate()));
            } catch (\Exception $e) {
                $offerData['start_date_formatted'] = $offer->getStartDate();
                $offerData['end_date_formatted'] = $offer->getEndDate();
            }
            
            // Enrichir avec les infos du POI
            $offerData['poi_name'] = 'POI #' . $poiId;
            $offerData['poi_url'] = null;
            
            if (isset($poiDataList[$poiId])) {
                $poi = $poiDataList[$poiId];
                $offerData['poi_name'] = $poi['name'] ?? ('POI #' . $poiId);
                $offerData['poi_url'] = $poi['url'] ?? null;
            }
            
            $offersPrepared[] = $offerData;
        }
        
        $tpl->set('offers', $offersPrepared);
        $tpl->set('offersCount', count($offersPrepared));
        
        $response->setTitle('Bons plans à Bourges');
        $response->addTemplate($tpl);
        return $response;
    }

    public function myOffers()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('offers:manage'), $ctx)) {
            Show::msg('Vous devez être connecté pour voir vos offres.', 'error');
            return $this->core->redirect($this->router->generate('login'));
        }

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('offers', 'my-offers');
        
        $userId = (int)$this->user->attributes['id'];
        $userOffers = $this->offerManager->getByUser($userId);
        
        // Collecter les IDs de POI
        $poiIds = [];
        foreach ($userOffers as $offer) {
            $poiIds[] = (int)$offer->getPoiId();
        }
        $poiIds = array_unique($poiIds);
        
        // Récupérer les données POI via le Provider
        $poiDataList = [];
        try {
            $provider = $this->core->callHook('entity:provider-request', ['entity' => 'poi']);
            if (is_object($provider) && method_exists($provider, 'getMany')) {
                $poiDataList = $provider->getMany($poiIds);
            }
        } catch (\Exception $e) {
            $this->core->getLogger()->warning("Offers: myOffers - Provider error: " . $e->getMessage());
        }

        // Préparer les offres
        $offersPrepared = [];
        foreach ($userOffers as $offer) {
            $offerData = $offer->toArray();
            $poiId = (int)$offer->getPoiId();
            
            $offerData['viewUrl'] = $this->router->generate('offers-view', ['id' => $offer->getId()]);
            // Formatage des dates
            try {
                $offerData['start_date_formatted'] = date('d/m/Y', strtotime($offer->getStartDate()));
                $offerData['end_date_formatted'] = $offer->getEndDate() ? date('d/m/Y', strtotime($offer->getEndDate())) : '-';
            } catch (\Exception $e) {
                $offerData['start_date_formatted'] = $offer->getStartDate();
                $offerData['end_date_formatted'] = $offer->getEndDate() ?: '-';
            }
            
            // Enrichir avec les infos du POI
            $offerData['poi_name'] = 'POI #' . $poiId;
            if (isset($poiDataList[$poiId])) {
                $poi = $poiDataList[$poiId];
                $offerData['poi_name'] = $poi['name'] ?? ('POI #' . $poiId);
            }

            // Stats
            $stats = $this->offerManager->getStats($offer->getId());
            $offerData['stats'] = $stats;
            
            $offersPrepared[] = $offerData;
        }
        
        $tpl->set('offers', $offersPrepared);
        $tpl->set('title', 'Gérer mes offres');
        
        $response->setTitle('Gérer mes offres');
        $response->addTemplate($tpl);
        return $response;
    }

    public function poiOffers()
    {
        $poiId = (int)$this->router->getParam('poi_id');
        
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('offers', 'poi-offers');
        
        $offers = $this->offerManager->getActiveOffersByPoi($poiId);
        
        // Récupérer le nom du POI via le Provider
        $poiName = 'POI #' . $poiId;
        try {
            $provider = $this->core->callHook('entity:provider-request', ['entity' => 'poi']);
            if (is_object($provider) && method_exists($provider, 'getName')) {
                $poiName = $provider->getName($poiId) ?? $poiName;
            }
        } catch (\Exception $e) {
            // Ignorer
        }

        // Préparer les offres
        $offersPrepared = [];
        foreach ($offers as $offer) {
            $offerData = $offer->toArray();
            $offerData['viewUrl'] = $this->router->generate('offers-view', ['id' => $offer->getId()]);
            $offerData['unlockUrl'] = $this->router->generate('offers-unlock', ['id' => $offer->getId()]);
            
            // Formater les dates
            try {
                $offerData['start_date_formatted'] = date('d/m/Y', strtotime($offer->getStartDate()));
                $offerData['end_date_formatted'] = date('d/m/Y', strtotime($offer->getEndDate()));
            } catch (\Exception $e) {
                $offerData['start_date_formatted'] = $offer->getStartDate();
                $offerData['end_date_formatted'] = $offer->getEndDate();
            }
            
            $offersPrepared[] = $offerData;
        }
        
        $tpl->set('offers', $offersPrepared);
        $tpl->set('poiId', $poiId);
        $tpl->set('poiName', $poiName);
        
        $response->setTitle('Offres disponibles - ' . $poiName);
        $response->addTemplate($tpl);
        return $response;
    }

    public function view()
    {
        $id = (int)$this->router->getParam('id');
        $offer = $this->offerManager->getById($id);
        
        if (!$offer || !$offer->isCurrentlyValid()) {
            Show::msg('Offre introuvable ou expirée.', 'error');
            return $this->core->redirect('/offers');
        }

        // Enregistrer la vue
        $userId = $this->user ? (int)$this->user->attributes['id'] : null;
        $sessionId = session_id();
        $this->offerManager->recordView($id, $userId, $sessionId);
        $this->offerManager->incrementViews($id);

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('offers', 'view');
        
        $offerData = $offer->toArray();
        $offerData['unlockUrl'] = $this->router->generate('offers-unlock', ['id' => $offer->getId()]);
        
        // Enrichir avec les POI partenaires
        $partnerIds = $offer->getPartnerPoiIds();
        $partners = [];
        if (!empty($partnerIds)) {
            try {
                $provider = $this->core->callHook('entity:provider-request', ['entity' => 'poi']);
                if (is_object($provider) && method_exists($provider, 'getMany')) {
                    $partnersRaw = $provider->getMany($partnerIds);
                    foreach ($partnersRaw as $pId => $pData) {
                        $partners[] = [
                            'id' => $pId,
                            'name' => $pData['name'] ?? ('POI #' . $pId)
                        ];
                    }
                }
            } catch (\Exception $e) {}
        }
        $offerData['partners'] = $partners;

        // Enrichir avec l'événement éventuel
        $eventId = $offer->getEventId();
        $eventTitle = null;
        if ($eventId > 0 && class_exists('\Agenda\Entities\Event')) {
            try {
            /** @var \Agenda\Entities\Event $event */
            $event = \Agenda\Entities\Event::findPK($eventId);
            if ($event) {
                $eventTitle = $event->getTitle();
            }
            } catch (\Exception $e) {}
        }
        $offerData['event_title'] = $eventTitle;

        // Formater les dates
        try {
            $offerData['start_date_formatted'] = date('d/m/Y', strtotime($offer->getStartDate()));
            $offerData['end_date_formatted'] = date('d/m/Y', strtotime($offer->getEndDate()));
        } catch (\Exception $e) {
            $offerData['start_date_formatted'] = $offer->getStartDate();
            $offerData['end_date_formatted'] = $offer->getEndDate();
        }
        
        $tpl->set('offer', $offerData);
        
        $response->setTitle($offer->getTitle());
        $response->addTemplate($tpl);
        return $response;
    }

    public function unlock()
    {
        $id = (int)$this->router->getParam('id');
        $offer = $this->offerManager->getById($id);
        
        if (!$offer || !$offer->isCurrentlyValid()) {
            Show::msg('Offre introuvable ou expirée.', 'error');
            return $this->core->redirect('/offers');
        }

        // Pour les offres basées sur le décompte, vérifier si on peut encore générer un code
        if ($offer->getOfferType() === 'count') {
            $maxCodes = $offer->getMaxCodes();
            $currentUnlocks = $offer->getUnlocksCount();
            if ($maxCodes !== null && $currentUnlocks >= $maxCodes) {
                Show::msg('Cette offre a atteint son nombre maximum de codes.', 'error');
                return $this->core->redirect('/offers');
            }
        }

        // Générer le code unique
        $code = CodeGenerator::generateSimpleCode($id);
        $qrCodeUrl = CodeGenerator::generateQrCodeUrl($code);

        // Enregistrer le déblocage
        $userId = $this->user ? (int)$this->user->attributes['id'] : null;
        $sessionId = session_id();
        $this->offerManager->recordUnlock($id, $code, $userId, $sessionId);
        $this->offerManager->incrementUnlocks($id);

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('offers', 'unlock');
        
        $offerData = $offer->toArray();
        $offerData['code'] = $code;
        $offerData['qrCodeUrl'] = $qrCodeUrl;
        
        // Ajouter des infos sur le type d'offre
        $offerData['offer_type'] = $offer->getOfferType();
        if ($offer->getOfferType() === 'count') {
            $offerData['max_codes'] = $offer->getMaxCodes();
            $offerData['remaining_codes'] = max(0, ($offer->getMaxCodes() ?? 0) - ($offer->getUnlocksCount() + 1));
        }
        
        $tpl->set('offer', $offerData);
        
        $response->setTitle('Code de l\'offre');
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Afficher le formulaire de création d'offre (pour les propriétaires)
     */
    public function add()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('offers:create'), $ctx)) {
            Show::msg('Vous devez être connecté pour créer une offre.', 'error');
            return $this->core->redirect($this->router->generate('login'));
        }

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('offers', 'admin-form');
        
        // Pré-remplir le POI ID si fourni en paramètre
        $poiId = isset($_GET['poi_id']) ? (int)$_GET['poi_id'] : 0;
        
        // Vérifier que l'utilisateur est propriétaire du POI (sauf si admin)
        if ($poiId > 0) {
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            $ctx = new \Core\Navigation\ConditionContext($this->user);
            if (!$evaluator->isAdmin($ctx)) {
                if (!$this->claimService->isOwner($userId, $poiId)) {
                    Show::msg('Vous devez être propriétaire de cette fiche pour créer une offre.', 'error');
                    return $this->core->redirect('/claim/my-claims');
                }
            }
        }
        
        $offerData = null;
        if ($poiId > 0) {
            $offerData = ['poi_id' => $poiId];
        }
        
        // Informations complémentaires pour le formulaire
        $allPois = [];
        try {
            $provider = $this->core->callHook('entity:provider-request', ['entity' => 'poi']);
            if (is_object($provider) && method_exists($provider, 'getAll')) {
                $allPois = $provider->getAll();
            }
        } catch (\Exception $e) {}

        $poiEvents = [];
        if ($poiId > 0 && class_exists('\Agenda\Entities\Event')) {
            try {
                $poiEvents = \Agenda\Entities\Event::query()
                    ->where('poi_id', '=', $poiId)
                    ->get();
            } catch (\Exception $e) {}
        }

        $userId = (int)($this->user->attributes['id'] ?? 0);
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        $isSubscriber = $this->subscriptionService->hasActiveSubscription($userId, $userEmail, $username);

        $tpl->set('offer', $offerData);
        $tpl->set('allPois', $allPois);
        $tpl->set('poiEvents', $poiEvents);
        $tpl->set('isSubscriber', $isSubscriber);
        $tpl->set('title', 'Créer une offre');
        $tpl->set('actionUrl', $this->router->generate('offers-add-send'));
        $tpl->set('cancelUrl', '/claim/my-claims');
        $tpl->set('csrfToken', \Core\Security\Csrf::token());
        
        $response->setTitle('Créer une offre');
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Traiter la soumission du formulaire de création d'offre (pour les propriétaires)
     */
    public function addSend()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('offers:create'), $ctx)) {
            Show::msg('Vous devez être connecté pour créer une offre.', 'error');
            return $this->core->redirect($this->router->generate('login'));
        }

        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/offers/add');
        }

        // Extraire les données du formulaire (même logique que OffersAdminController)
        $data = $this->extractOfferData($_POST);
        if ($data === null) {
            Show::msg('Données invalides.', 'error');
            return $this->core->redirect('/offers/add');
        }

        // Vérifier les limites selon l'abonnement
        $userId = (int)($this->user->attributes['id'] ?? 0);
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        
        $maxActive = $this->subscriptionService->getMaxOffersForUser($userId, $userEmail, $username);
        
        $activeCount = $this->offerManager->countActiveByPoi($data['poi_id']);
        if ($activeCount >= $maxActive) {
            $subscriptionText = $maxActive > 1 ? " (abonné)" : " (sans abonnement)";
            Show::msg("Limite d'offres actives atteinte pour ce POI (max: {$maxActive}{$subscriptionText}).", 'error');
            return $this->core->redirect('/offers/add?poi_id=' . $data['poi_id']);
        }

        // Vérifier si le POI est réclamé (sauf si admin)
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            if (!$this->claimService->isOwner($userId, $data['poi_id'])) {
                Show::msg('Vous devez être propriétaire de cette fiche pour créer une offre.', 'error');
                return $this->core->redirect('/claim/my-claims');
            }
        }

        // Créer l'offre
        $offer = $this->offerManager->create($data);
        
        if ($offer) {
            // Si c'est un propriétaire, on auto-approuve
            if (!$evaluator->isAdmin($ctx)) {
                $offer->setStatus('approved');
                $offer->setApprovedAt(date('Y-m-d H:i:s'));
                $offer->setApprovedBy($userId);
                $offer->save();
            }

            Show::msg('Offre créée avec succès.', 'success');
            return $this->core->redirect('/claim/my-claims');
        } else {
            Show::msg('Erreur lors de la création de l\'offre.', 'error');
            return $this->core->redirect('/offers/add?poi_id=' . ($data['poi_id'] ?? 0));
        }
    }

    /**
     * Afficher le formulaire de modification d'offre (pour les propriétaires)
     */
    public function edit()
    {
        $id = (int)$this->router->getParam('id');
        $offer = $this->offerManager->getById($id);

        if (!$offer) {
            Show::msg('Offre introuvable.', 'error');
            return $this->core->redirect($this->router->generate('offers-my-offers'));
        }

        // Vérifier les permissions : admin ou propriétaire
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $userId = (int)($this->user->attributes['id'] ?? 0);
        $isOwner = ((int)$offer->getCreatedBy() === $userId);
        $ctx = new \Core\Navigation\ConditionContext($this->user, ['is_owner' => $isOwner]);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('offers:manage'), $ctx)) {
            Show::msg('Vous n\'avez pas la permission de modifier cette offre.', 'error');
            return $this->core->redirect($this->router->generate('offers-my-offers'));
        }

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('offers', 'admin-form');
        
        $offerData = $offer->toArray();
        // Préparer les flags pour le template
        $offerData['is_date_type'] = ($offer->getOfferType() ?? 'date') === 'date';
        $offerData['is_count_type'] = ($offer->getOfferType() ?? 'date') === 'count';

        // Informations complémentaires pour le formulaire
        $allPois = [];
        try {
            $provider = $this->core->callHook('entity:provider-request', ['entity' => 'poi']);
            if (is_object($provider) && method_exists($provider, 'getAll')) {
                $allPois = $provider->getAll();
            }
        } catch (\Exception $e) {}

        $poiEvents = [];
        $poiId = $offer->getPoiId();
        if ($poiId > 0 && class_exists('\Agenda\Entities\Event')) {
            try {
                $poiEvents = \Agenda\Entities\Event::query()
                    ->where('poi_id', '=', $poiId)
                    ->get();
            } catch (\Exception $e) {}
        }

        $userId = (int)($this->user->attributes['id'] ?? 0);
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        $isSubscriber = $this->subscriptionService->hasActiveSubscription($userId, $userEmail, $username);

        $tpl->set('offer', $offerData);
        $tpl->set('allPois', $allPois);
        $tpl->set('poiEvents', $poiEvents);
        $tpl->set('isSubscriber', $isSubscriber);
        $tpl->set('title', 'Modifier l\'offre');
        $tpl->set('actionUrl', $this->router->generate('offers-edit-send', ['id' => $id]));
        $tpl->set('cancelUrl', $this->router->generate('offers-my-offers'));
        $tpl->set('csrfToken', \Core\Security\Csrf::token());
        
        $response->setTitle('Modifier l\'offre');
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Traiter la modification d'offre
     */
    public function editSend()
    {
        $id = (int)$this->router->getParam('id');
        $offer = $this->offerManager->getById($id);

        if (!$offer) {
            Show::msg('Offre introuvable.', 'error');
            return $this->core->redirect($this->router->generate('offers-my-offers'));
        }

        // Vérifier les permissions
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $userId = (int)($this->user->attributes['id'] ?? 0);
        $isOwner = ((int)$offer->getCreatedBy() === $userId);
        $ctx = new \Core\Navigation\ConditionContext($this->user, ['is_owner' => $isOwner]);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('offers:manage'), $ctx)) {
            Show::msg('Vous n\'avez pas la permission de modifier cette offre.', 'error');
            return $this->core->redirect($this->router->generate('offers-my-offers'));
        }
        $isAdmin = $evaluator->isAdmin($ctx);

        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('offers-edit', ['id' => $id]));
        }

        $data = $this->extractOfferData($_POST);
        if ($data === null) {
            Show::msg('Données invalides.', 'error');
            return $this->core->redirect($this->router->generate('offers-edit', ['id' => $id]));
        }

        // Conserver le statut si pas admin
        if (!$isAdmin) {
            $data['status'] = $offer->getStatus();
        }

        if ($this->offerManager->update($id, $data)) {
            Show::msg('Offre modifiée avec succès.', 'success');
            return $this->core->redirect($this->router->generate('offers-my-offers'));
        } else {
            Show::msg('Erreur lors de la modification de l\'offre.', 'error');
            return $this->core->redirect($this->router->generate('offers-edit', ['id' => $id]));
        }
    }

    /**
     * Extraire les données du formulaire (même logique que OffersAdminController)
     */
    private function extractOfferData(array $post): ?array
    {
        $poiId = isset($post['poi_id']) ? (int)$post['poi_id'] : 0;
        if ($poiId <= 0) {
            return null;
        }

        $title = trim($post['title'] ?? '');
        $description = trim($post['description'] ?? '');
        $advantage = trim($post['advantage'] ?? '');
        $condition = trim($post['condition'] ?? '');
        $location = trim($post['location'] ?? '');
        $offerType = $post['offer_type'] ?? 'date';
        
        if (empty($title) || empty($advantage)) {
            return null;
        }

        $data = [
            'poi_id' => $poiId,
            'title' => $title,
            'description' => $description,
            'advantage' => $advantage,
            'condition' => $condition,
            'location' => $location,
            'offer_type' => $offerType,
            'status' => 'pending', // Les offres créées par les propriétaires sont en attente (addSend surchargera)
            'event_id' => !empty($post['event_id']) ? (int)$post['event_id'] : null,
        ];

        // Gestion des POIs partenaires (offres groupées) - Réservé aux abonnés
        $partnerIds = $post['partner_poi_ids'] ?? [];
        if (!is_array($partnerIds)) {
            $partnerIds = !empty($partnerIds) ? array_map('trim', explode(',', $partnerIds)) : [];
        }
        $partnerIds = array_map('intval', array_filter($partnerIds));

        if (!empty($partnerIds)) {
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;
            
            if ($this->subscriptionService->hasActiveSubscription($userId, $userEmail, $username)) {
                $data['partner_poi_ids'] = json_encode($partnerIds);
            }
        }

        if ($offerType === 'date') {
            $startDate = $post['start_date'] ?? '';
            $endDate = $post['end_date'] ?? '';
            
            if (empty($startDate) || empty($endDate)) {
                return null;
            }
            
            $data['start_date'] = $startDate;
            $data['end_date'] = $endDate;
            $data['max_codes'] = null;
        } else {
            $maxCodes = isset($post['max_codes']) ? (int)$post['max_codes'] : 0;
            if ($maxCodes <= 0) {
                return null;
            }
            
            $data['max_codes'] = $maxCodes;
            $data['start_date'] = date('Y-m-d');
            $data['end_date'] = null;
        }

        return $data;
    }
}

