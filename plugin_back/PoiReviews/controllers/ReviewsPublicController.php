<?php

namespace PoiReviews\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Auth\UsersManager;
use Core\Auth\User;
use Core\Security\Csrf;
use Utils\Show;
use Core\Lang;
use Core\Logger;
use PoiReviews\Entities\ReviewManager;
use PoiReviews\Services\ClaimService;

defined('ROOT') or exit('Access denied!');

class ReviewsPublicController extends PublicController
{
    private ReviewManager $reviewManager;
    private ClaimService $claimService;

    public function __construct()
    {
        parent::__construct();
        
        // Les entités sont chargées via l'autoloader
        
        // Charger les services si elles ne sont pas déjà chargées
        if (!class_exists('PoiReviews\Services\ClaimService')) {
            require_once PLUGINS . 'poi-reviews/services/ClaimService.php';
        }
        
        $this->reviewManager = new ReviewManager();
        /** @var Logger $logger */
        $logger = $this->core->getLogger();
        $this->claimService = new ClaimService($logger);
    }

    public function index()
    {
        Show::msg('Veuillez spécifier un POI pour voir ses avis.', 'info');
        return $this->core->redirect('/');
    }

    public function list($poi_id = null)
    {
        $this->core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "ReviewsPublicController::list CALLED with poi_id=" . var_export($poi_id, true));

        // Le paramètre est passé directement par le routeur depuis l'URL
        $poiId = (int)($poi_id ?? 0);
        
        if ($poiId <= 0) {
            $this->core->getLogger()->log(Logger::LEVEL_WARNING, "ReviewsPublicController::list - POI ID invalid: $poiId");
            Show::msg('POI introuvable.', 'error');
            return $this->core->redirect('/');
        }
        
        $this->core->getLogger()->log(Logger::LEVEL_DEBUG, "ReviewsPublicController::list - Processing for POI ID: $poiId");
        
        $response = new PublicResponse();
        $isEmbed = isset($_GET['embed']) || (isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST']);
        $templateName = $isEmbed ? 'list_embed' : 'list';
        $tpl = $response->createPluginTemplate('PoiReviews', $templateName);
        
        $reviews = $this->reviewManager->getByPoi($poiId, true);
        $averageRating = $this->reviewManager->getAverageRating($poiId);
        $ratingCount = $this->reviewManager->getRatingCount($poiId);
        
        // Enrichir les reviews avec les infos utilisateur
        $reviewsData = [];
        foreach ($reviews as $review) {
            $reviewData = $review->toArray();
            
            // S'assurer que rating est un entier
            $reviewData['rating'] = (int)($reviewData['rating'] ?? 0);
            
            // Formater la date de création
            if (!empty($reviewData['created_at'])) {
                try {
                    $date = new \DateTime($reviewData['created_at']);
                    $reviewData['created_at_formatted'] = $date->format('d/m/Y');
                } catch (\Exception $e) {
                    $reviewData['created_at_formatted'] = $reviewData['created_at'] ?? '';
                }
            } else {
                $reviewData['created_at_formatted'] = '';
            }
            
            // Enrichir avec les infos utilisateur depuis CMS
            $userId = $review->getUserId();
            $user = User::find('id', $userId);
            if ($user) {
                $reviewData['author_name'] = $user->attributes['username'] ?? $user->attributes['email'] ?? 'Utilisateur';
                $reviewData['author_email'] = $user->attributes['email'] ?? null;
                // Avatar
                $profilePhoto = $user->attributes['profile_photo'] ?? null;
                if ($profilePhoto && $profilePhoto !== '/avatar' && !empty($profilePhoto)) {
                    $reviewData['author_avatar'] = $profilePhoto;
                    $reviewData['has_avatar'] = true;
                } else {
                    $reviewData['has_avatar'] = false;
                    // Générer avatar CSS
                    $username = $reviewData['author_name'];
                    $reviewData['avatar_color'] = $this->generateAvatarColor($username);
                    $reviewData['avatar_initials'] = $this->generateAvatarInitials($username);
                }
            } else {
                $reviewData['author_name'] = 'Utilisateur';
                $reviewData['has_avatar'] = false;
                $reviewData['avatar_color'] = '#667eea';
                $reviewData['avatar_initials'] = 'U';
            }
            
            // Préparer les étoiles pour chaque review
            $reviewStars = [];
            $rating = (int)($reviewData['rating'] ?? 0);
            for ($i = 1; $i <= 5; $i++) {
                $reviewStars[] = [
                    'isFull' => $i <= $rating
                ];
            }
            $reviewData['stars'] = $reviewStars;
            
            $reviewsData[] = $reviewData;
        }
        
        // Récupérer le nom du POI et générer l'URL de retour
        $poiName = 'POI #' . $poiId;
        $poiDetailUrl = null;
        
        try {
            $provider = $this->core->callHook('entity:provider-request', ['entity' => 'poi']);
            if ($provider && method_exists($provider, 'get')) {
                $poiData = $provider->get($poiId);
                if ($poiData) {
                    $poiName = $poiData['name'] ?? ('POI #' . $poiId);
                    if (!empty($poiData['url'])) {
                        $poiDetailUrl = $poiData['url'];
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs, utiliser les valeurs par défaut
            $this->core->getLogger()->debug('PoiReviews::list - Erreur récupération POI: ' . $e->getMessage());
        }
        
        // Fix: Force use of router for detail URL instead of legacy hook value
        try {
            $poiDetailUrl = $this->router->generate('search-ui-detail', ['id' => $poiId]);
        } catch (\Exception $e) {
            $poiDetailUrl = '/SearchUi/detail/' . $poiId;
        }

        $tpl->set('poiId', $poiId);
        $tpl->set('poiName', $poiName);
        $tpl->set('poiDetailUrl', $poiDetailUrl);
        $tpl->set('reviews', $reviewsData);
        $tpl->set('averageRating', $averageRating);
        $tpl->set('ratingCount', $ratingCount);
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext(UsersManager::getCurrentUser());
        $tpl->set('isLogged', $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:review:access'), $ctx));
        $tpl->set('loginUrl', $this->router->generate('login'));
        
        // Vérifier si l'utilisateur a déjà noté ce POI
        $user = UsersManager::getCurrentUser();
        $userReview = null;
        $isOwner = false;
        if ($user) {
            $userId = (int)($user->attributes['id'] ?? 0);
            if ($userId > 0) {
                // Vérifier si l'utilisateur a déjà laissé un avis actif pour ce POI
                $userReview = $this->reviewManager->getUserReviewForPoi($poiId, $userId);
                // Vérifier si l'utilisateur est propriétaire du POI
                $isOwner = $this->claimService->isOwner($userId, $poiId);
            }
        }
        // Ne passer userReview au template que s'il existe vraiment
        $tpl->set('userReview', ($userReview !== null) ? $userReview->toArray() : null);
        $tpl->set('isOwner', $isOwner);
        $tpl->set('csrfToken', Csrf::token());
        
        // Préparer les étoiles pour l'affichage de la note moyenne
        $stars = [];
        for ($i = 1; $i <= 5; $i++) {
            $stars[] = [
                'isFull' => $i <= $averageRating,
                'isHalf' => ($i - 0.5) <= $averageRating && $i > $averageRating
            ];
        }
        $tpl->set('stars', $stars);
        
        // Préparer un tableau simple pour les étoiles des avis individuels
        $reviewStars = [1, 2, 3, 4, 5];
        $tpl->set('reviewStars', $reviewStars);
        
        // Générer les URLs de modération pour chaque avis
        foreach ($reviewsData as &$reviewData) {
            if ($isOwner && $reviewData['status'] === 'active') {
                try {
                    $reviewData['moderateUrl'] = $this->router->generate('poi-reviews-moderate', ['id' => $reviewData['id']]);
                } catch (\Exception $e) {
                    $reviewData['moderateUrl'] = '/poi-reviews/moderate/' . $reviewData['id'];
                }
            }
        }
        unset($reviewData);
        
        $response->setTitle('Avis et commentaires');
        $response->addTemplate($tpl);
        return $response;
    }

    public function add()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext(UsersManager::getCurrentUser());
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:review:access'), $ctx)) {
            Show::msg('Vous devez être connecté pour laisser un avis.', 'error');
            return $this->core->redirect($this->router->generate('login'));
        }

        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/');
        }

        $user = UsersManager::getCurrentUser();
        $userId = (int)($user->attributes['id'] ?? 0);
        
        if ($userId <= 0) {
            Show::msg('Erreur d\'authentification.', 'error');
            return $this->core->redirect('/');
        }

        $poiId = (int)($_POST['poi_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($poiId <= 0 || $rating < 1 || $rating > 5) {
            Show::msg('Données invalides.', 'error');
            return $this->core->redirect('/');
        }

        // Vérifier si l'utilisateur a déjà noté ce POI
        $existingReview = $this->reviewManager->getUserReviewForPoi($poiId, $userId);
        if ($existingReview) {
            Show::msg('Vous avez déjà noté ce POI.', 'error');
            return $this->core->redirect($this->router->generate('poi-reviews-list', ['poi_id' => $poiId]));
        }

        $data = [
            'poi_id' => $poiId,
            'user_id' => $userId,
            'rating' => $rating,
            'comment' => $comment ?: null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->reviewManager->create($data)) {
            // Hook pour gamification
            $this->core->callHook('poi:review-added', [
                'user_id' => $userId,
                'poi_id' => $poiId,
                'rating' => $rating
            ]);

            Show::msg('Votre avis a été enregistré. Merci !', 'success');
        } else {
            Show::msg('Erreur lors de l\'enregistrement de votre avis.', 'error');
        }

        return $this->core->redirect($this->router->generate('poi-reviews-list', ['poi_id' => $poiId]));
    }

    public function moderate($id = null)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext(UsersManager::getCurrentUser());
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:review:access'), $ctx)) {
            Show::msg('Vous devez être connecté.', 'error');
            return $this->core->redirect('/');
        }

        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/');
        }

        // Le paramètre est passé directement par le routeur depuis l'URL
        $reviewId = (int)($id ?? 0);
        
        if ($reviewId <= 0) {
            Show::msg('Avis introuvable.', 'error');
            return $this->core->redirect('/');
        }
        $review = $this->reviewManager->getById($reviewId);
        
        if (!$review) {
            Show::msg('Avis introuvable.', 'error');
            return $this->core->redirect('/');
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $isOwner = $this->claimService->isOwner($this->user->attributes['id'] ?? 0, $review->getPoiId());
        $ctx = new \Core\Navigation\ConditionContext($this->user, ['is_owner' => $isOwner]);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:review:moderate'), $ctx)) {
            Show::msg('Vous n\'avez pas les permissions pour modérer cet avis.', 'error');
            return $this->core->redirect($this->router->generate('poi-reviews-list', ['poi_id' => $review->getPoiId()]));
        }

        $reason = trim($_POST['reason'] ?? 'Modéré par le propriétaire');
        $userId = $this->user->attributes['id'] ?? 0;
        
        if ($this->reviewManager->moderate($reviewId, $userId, $reason)) {
            Show::msg('Avis modéré avec succès.', 'success');
        } else {
            Show::msg('Erreur lors de la modération.', 'error');
        }

        return $this->core->redirect($this->router->generate('poi-reviews-list', ['poi_id' => $review->getPoiId()]));
    }



    private function generateAvatarColor(string $username): string
    {
        $hash = md5($username);
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private function generateAvatarInitials(string $username): string
    {
        $parts = preg_split('/[\s\-_\.]+/', trim($username));
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        return strtoupper(substr($username, 0, 2));
    }
}

