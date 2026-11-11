<?php

namespace Offers\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Core\Security\Csrf;
use Offers\Entities\OfferManager;
use Offers\Entities\Offer;
use Offers\Services\ClaimService;
use Offers\Services\SubscriptionService;
use Core\Plugin\PluginsManager;

defined('ROOT') or exit('Access denied!');

class OffersAdminController extends AdminController
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
    
    /**
     * Vérifier si l'utilisateur peut accéder (admin ou propriétaire)
     */
    private function canAccess(): bool
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        // On délègue à la politique de création/gestion globale
        return $evaluator->canAccess(new \Core\Navigation\GenericResource('offers:create'), $ctx);
    }

    public function list()
    {
        if (!$this->canAccess()) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('offers', 'admin-metrics');
        
        // Récupérer toutes les offres pour les statistiques
        $allOffers = $this->offerManager->getAll(null, false);
        
        // Calculer les statistiques globales
        $totalOffers = count($allOffers);
        $activeOffers = 0;
        $pendingOffers = 0;
        $approvedOffers = 0;
        $rejectedOffers = 0;
        $totalViews = 0;
        $totalUnlocks = 0;
        
        // Collecter les IDs de POI
        $poiIds = [];
        foreach ($allOffers as $offer) {
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
            $this->core->getLogger()->warning("OffersAdmin: list - Provider error: " . $e->getMessage());
        }

        $offersData = [];
        foreach ($allOffers as $offer) {
            $offerData = $offer->toArray();
            $poiId = (int)$offer->getPoiId();
            $views = $offer->getViewsCount();
            $unlocks = $offer->getUnlocksCount();
            
            $totalViews += $views;
            $totalUnlocks += $unlocks;
            
            if ($offer->isCurrentlyValid()) {
                $activeOffers++;
            }
            
            $status = $offer->getStatus();
            if ($status === 'pending') {
                $pendingOffers++;
            } elseif ($status === 'approved') {
                $approvedOffers++;
            } elseif ($status === 'rejected') {
                $rejectedOffers++;
            }
            
            // Formater les dates
            try {
                $offerData['start_date_formatted'] = date('d/m/Y', strtotime($offer->getStartDate()));
                $offerData['end_date_formatted'] = $offer->getEndDate() ? date('d/m/Y', strtotime($offer->getEndDate())) : 'Illimité';
            } catch (\Exception $e) {
                $offerData['start_date_formatted'] = $offer->getStartDate();
                $offerData['end_date_formatted'] = $offer->getEndDate() ?? 'Illimité';
            }
            
            $offerData['views_count'] = $views;
            $offerData['unlocks_count'] = $unlocks;
            $offerData['conversion_rate'] = $views > 0 ? round(($unlocks / $views) * 100, 2) : 0;
            $offerData['isCurrentlyValid'] = $offer->isCurrentlyValid();
            $offerData['status'] = $status;
            
            // Enrichir avec le nom du POI
            $offerData['poi_name'] = $poiDataList[$poiId]['name'] ?? ('POI #' . $poiId);
            
            $offersData[] = $offerData;
        }
        
        // Trier par nombre de vues décroissant
        usort($offersData, function($a, $b) {
            return $b['views_count'] - $a['views_count'];
        });
        
        $tpl->set('offers', $offersData);
        $tpl->set('totalOffers', $totalOffers);
        $tpl->set('activeOffers', $activeOffers);
        $tpl->set('pendingOffers', $pendingOffers);
        $tpl->set('approvedOffers', $approvedOffers);
        $tpl->set('rejectedOffers', $rejectedOffers);
        $tpl->set('totalViews', $totalViews);
        $tpl->set('totalUnlocks', $totalUnlocks);
        $tpl->set('globalConversionRate', $totalViews > 0 ? round(($totalUnlocks / $totalViews) * 100, 2) : 0);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function add()
    {
        if (!$this->canAccess()) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->core->redirect($this->router->generate('offers-admin'));
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('offers', 'admin-form');
        
        // Pré-remplir le POI ID si fourni en paramètre
        $poiId = isset($_GET['poi_id']) ? (int)$_GET['poi_id'] : 0;
        $offerData = null;
        $poiName = null;
        $poiIdReadonly = false;
        
        if ($poiId > 0) {
            $offerData = ['poi_id' => $poiId];
            $poiIdReadonly = true;
            
            // Récupérer le nom du POI via le Provider
            try {
                $provider = $this->core->callHook('entity:provider-request', ['entity' => 'poi']);
                if (is_object($provider) && method_exists($provider, 'getName')) {
                    $poiName = $provider->getName($poiId);
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs, on affichera juste l'ID
            }
        }
        
        // Préparer les flags pour le template
        if ($offerData) {
            $offerData['is_date_type'] = ($offerData['offer_type'] ?? 'date') === 'date';
            $offerData['is_count_type'] = ($offerData['offer_type'] ?? 'date') === 'count';
        }
        
        $tpl->set('offer', $offerData);
        $tpl->set('poiIdReadonly', $poiIdReadonly);
        $tpl->set('poiName', $poiName);
        $tpl->set('title', 'Créer une offre');
        $tpl->set('actionUrl', $this->router->generate('admin-offers-add-send'));
        $tpl->set('cancelUrl', $this->router->generate('admin-offers-list'));
        $tpl->set('csrfToken', Csrf::token());
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function addSend()
    {
        if (!$this->canAccess() || !Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/offers');
        }

        $data = $this->extractOfferData($_POST);
        if ($data === null) {
            Show::msg('Données invalides.', 'error');
            return $this->core->redirect('/admin/offers/add');
        }

        // Vérifier les limites selon l'abonnement
        $userId = (int)$this->user->attributes['id'];
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        
        $maxActive = $this->subscriptionService->getMaxOffersForUser($userId, $userEmail, $username);
        
        $activeCount = $this->offerManager->countActiveByPoi($data['poi_id']);
        if ($activeCount >= $maxActive) {
            $subscriptionText = $maxActive > 1 ? " (abonné)" : " (sans abonnement)";
            Show::msg("Limite d'offres actives atteinte pour ce POI (max: {$maxActive}{$subscriptionText}).", 'error');
            return $this->core->redirect('/admin/offers/add');
        }

        // Vérifier si le POI est réclamé (sauf si admin)
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        if (!$evaluator->isAdmin(new \Core\Navigation\ConditionContext($this->user))) {
            if (!$this->claimService->isOwner((int)$this->user->attributes['id'], $data['poi_id'])) {
                Show::msg('Vous devez être propriétaire de cette fiche pour créer une offre.', 'error');
                return $this->core->redirect('/admin/offers/add');
            }
        }

        // Déterminer le statut initial
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('offers');
        $requireModeration = $plugin ? (bool)($plugin->getConfigVal('requireModeration') ?? true) : true;
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $data['status'] = ($evaluator->isAdmin(new \Core\Navigation\ConditionContext($this->user)) || !$requireModeration) ? 'approved' : 'pending';
        $data['created_by'] = (int)$this->user->attributes['id'];
        
        if ($data['status'] === 'approved') {
            $data['approved_by'] = (int)$this->user->attributes['id'];
            $data['approved_at'] = date('Y-m-d H:i:s');
        }

        if ($this->offerManager->create($data)) {
            Show::msg('Offre créée avec succès.', 'success');
            // Déclencher le hook de domaine
            try {
                $this->core->callHook('offer:created', [
                    'poi_id' => $data['poi_id'],
                    'title' => $data['title'],
                    'status' => $data['status'],
                    'created_by' => $data['created_by']
                ]);
            } catch (\Exception $e) {}
        } else {
            Show::msg('Erreur lors de la création de l\'offre.', 'error');
        }

        return $this->core->redirect('/admin/offers');
    }

    public function edit()
    {
        $id = (int)$this->router->getParam('id');
        $offer = $this->offerManager->getById($id);
        
        if (!$offer) {
            Show::msg('Offre introuvable.', 'error');
            return $this->core->redirect('/admin/offers');
        }

        // Vérifier les permissions (propriétaire ou admin)
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $isAdmin = $evaluator->isAdmin(new \Core\Navigation\ConditionContext($this->user));
        if (!$isAdmin && $offer->getCreatedBy() !== (int)$this->user->attributes['id']) {
            Show::msg('Vous n\'avez pas les permissions pour modifier cette offre.', 'error');
            return $this->core->redirect('/admin/offers');
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('offers', 'admin-form');
        
        $offerData = $offer->toArray();
        // Préparer les flags pour le template
        $offerData['is_date_type'] = ($offer->getOfferType() ?? 'date') === 'date';
        $offerData['is_count_type'] = ($offer->getOfferType() ?? 'date') === 'count';
        
        $tpl->set('offer', $offerData);
        $tpl->set('title', 'Modifier l\'offre');
        $tpl->set('actionUrl', $this->router->generate('admin-offers-edit-send', ['id' => $id]));
        $tpl->set('cancelUrl', $this->router->generate('admin-offers-list'));
        $tpl->set('csrfToken', Csrf::token());
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function editSend()
    {
        $id = (int)$this->router->getParam('id');
        $offer = $this->offerManager->getById($id);
        
        if (!$offer) {
            Show::msg('Offre introuvable.', 'error');
            return $this->core->redirect('/admin/offers');
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        // On check is_owner via meta
        $isOwner = $offer->getCreatedBy() === (int)$this->user->attributes['id'];
        $ctx->setResourceMetadata(['is_owner' => $isOwner]);

        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('offers:manage'), $ctx) || !Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/offers');
        }

        // Vérifier les permissions
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $isAdmin = $evaluator->isAdmin(new \Core\Navigation\ConditionContext($this->user));
        if (!$isAdmin && $offer->getCreatedBy() !== (int)$this->user->attributes['id']) {
            Show::msg('Vous n\'avez pas les permissions pour modifier cette offre.', 'error');
            return $this->core->redirect('/admin/offers');
        }

        $data = $this->extractOfferData($_POST, false);
        if ($data === null) {
            Show::msg('Données invalides.', 'error');
            return $this->core->redirect($this->router->generate('admin-offers-edit', ['id' => $id]));
        }

        // Vérifier les limites (si le POI change)
        if ($data['poi_id'] !== $offer->getPoiId()) {
            $userId = (int)$this->user->attributes['id'];
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;
            
            $maxActive = $this->subscriptionService->getMaxOffersForUser($userId, $userEmail, $username);
            
            $activeCount = $this->offerManager->countActiveByPoi($data['poi_id']);
            if ($activeCount >= $maxActive) {
                $subscriptionText = $maxActive > 1 ? " (abonné)" : " (sans abonnement)";
                Show::msg("Limite d'offres actives atteinte pour ce POI (max: {$maxActive}{$subscriptionText}).", 'error');
                return $this->core->redirect($this->router->generate('admin-offers-edit', ['id' => $id]));
            }
        }

        // Conserver le statut existant (sauf si admin modifie)
        if (!$isAdmin) {
            $data['status'] = $offer->getStatus();
        }

        if ($this->offerManager->update($id, $data)) {
            Show::msg('Offre modifiée avec succès.', 'success');
        } else {
            Show::msg('Erreur lors de la modification de l\'offre.', 'error');
        }

        return $this->core->redirect('/admin/offers');
    }

    public function delete()
    {
        $id = (int)$this->router->getParam('id');
        
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/offers');
        }

        $offer = $this->offerManager->getById($id);
        if (!$offer) {
            Show::msg('Offre introuvable.', 'error');
            return $this->core->redirect('/admin/offers');
        }

        // Vérifier les permissions
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $isAdmin = $evaluator->isAdmin(new \Core\Navigation\ConditionContext($this->user));
        if (!$isAdmin && $offer->getCreatedBy() !== (int)$this->user->attributes['id']) {
            Show::msg('Vous n\'avez pas les permissions pour supprimer cette offre.', 'error');
            return $this->core->redirect('/admin/offers');
        }

        if ($this->offerManager->delete($id)) {
            Show::msg('Offre supprimée avec succès.', 'success');
        } else {
            Show::msg('Erreur lors de la suppression de l\'offre.', 'error');
        }

        return $this->core->redirect('/admin/offers');
    }

    public function approve()
    {
        $id = (int)$this->router->getParam('id');
        
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/offers');
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $isAdmin = $evaluator->isAdmin(new \Core\Navigation\ConditionContext($this->user));
        if (!$isAdmin) {
            Show::msg('Vous n\'avez pas les permissions pour approuver une offre.', 'error');
            return $this->core->redirect('/admin/offers');
        }

        if ($this->offerManager->approve($id, (int)$this->user->attributes['id'])) {
            Show::msg('Offre approuvée avec succès.', 'success');
            
            // Déclencher le hook de domaine (Découplé)
            try {
                $offer = $this->offerManager->getById($id);
                if ($offer) {
                    $this->core->callHook('offer:activated', [
                        'offer_id' => $id,
                        'poi_id' => $offer->getPoiId(),
                        'title' => $offer->getTitle()
                    ]);
                }
            } catch (\Exception $e) {
                // Silencieux
            }
        } else {
            Show::msg('Erreur lors de l\'approbation de l\'offre.', 'error');
        }

        return $this->core->redirect('/admin/offers');
    }

    public function reject()
    {
        $id = (int)$this->router->getParam('id');
        
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/offers');
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $isAdmin = $evaluator->isAdmin(new \Core\Navigation\ConditionContext($this->user));
        if (!$isAdmin) {
            Show::msg('Vous n\'avez pas les permissions pour rejeter une offre.', 'error');
            return $this->core->redirect('/admin/offers');
        }

        if ($this->offerManager->reject($id, (int)$this->user->attributes['id'])) {
            Show::msg('Offre rejetée.', 'success');
            // Déclencher le hook de domaine
            try {
                $offer = $this->offerManager->getById($id);
                if ($offer) {
                    $this->core->callHook('offer:rejected', [
                        'offer_id' => $id,
                        'poi_id' => $offer->getPoiId(),
                        'title' => $offer->getTitle(),
                        'rejected_by' => (int)$this->user->attributes['id']
                    ]);
                }
            } catch (\Exception $e) {}
        } else {
            Show::msg('Erreur lors du rejet de l\'offre.', 'error');
        }

        return $this->core->redirect('/admin/offers');
    }

    public function stats()
    {
        if (!$this->canAccess()) {
            return $this->core->redirect('/admin/offers');
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('offers', 'admin-stats');
        
        // Statistiques globales
        $allOffers = $this->offerManager->getAll(null, false);
        $totalOffers = count($allOffers);
        $pendingOffers = count(array_filter($allOffers, fn($o) => $o->isPending()));
        $approvedOffers = count(array_filter($allOffers, fn($o) => $o->isApproved()));
        $activeOffers = count(array_filter($allOffers, fn($o) => $o->isCurrentlyValid()));
        
        $tpl->set('totalOffers', $totalOffers);
        $tpl->set('pendingOffers', $pendingOffers);
        $tpl->set('approvedOffers', $approvedOffers);
        $tpl->set('activeOffers', $activeOffers);
        
        $response->addTemplate($tpl);
        return $response;
    }

    private function extractOfferData(array $input, bool $requireAll = true): ?array
    {
        $poiId = (int)($input['poi_id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $advantage = trim($input['advantage'] ?? '');
        $startDate = trim($input['start_date'] ?? '');
        $offerType = trim($input['offer_type'] ?? 'date');
        $endDate = trim($input['end_date'] ?? '');
        $maxCodes = isset($input['max_codes']) ? (int)$input['max_codes'] : null;

        if ($requireAll && ($poiId < 1 || $title === '' || $advantage === '' || $startDate === '')) {
            return null;
        }

        // Vérifier le type d'offre
        if ($offerType === 'date') {
            if ($requireAll && $endDate === '') {
                return null;
            }
            // Vérifier les dates
            if ($startDate && $endDate) {
                if (strtotime($startDate) > strtotime($endDate)) {
                    return null;
                }
            }
            // Vérifier la durée maximale
            $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('offers');
            $maxDuration = (int)($plugin->getConfigVal('maxOfferDurationDays') ?? 30);
            if ($startDate && $endDate) {
                $days = (strtotime($endDate) - strtotime($startDate)) / 86400;
                if ($days > $maxDuration) {
                    return null;
                }
            }
        } elseif ($offerType === 'count') {
            if ($requireAll && ($maxCodes === null || $maxCodes < 1)) {
                return null;
            }
        } else {
            return null; // Type invalide
        }

        return [
            'poi_id' => $poiId,
            'title' => $title,
            'description' => trim($input['description'] ?? ''),
            'advantage' => $advantage,
            'condition_text' => trim($input['condition_text'] ?? ''),
            'offer_type' => $offerType,
            'start_date' => $startDate,
            'end_date' => $offerType === 'date' ? $endDate : null,
            'max_codes' => $offerType === 'count' ? $maxCodes : null,
        ];
    }
}

