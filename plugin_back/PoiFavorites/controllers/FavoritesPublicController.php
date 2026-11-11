<?php

namespace PoiFavorites\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Auth\UsersManager;
use Core\Security\Csrf;
use Utils\Show;
use Core\Lang;
use PoiFavorites\Entities\FavoriteManager;
use PoiFavorites\Entities\NotificationManager;

defined('ROOT') or exit('Access denied!');

class FavoritesPublicController extends PublicController
{
    private FavoriteManager $favoriteManager;
    private NotificationManager $notificationManager;

    public function __construct()
    {
        parent::__construct();
        $this->favoriteManager = new FavoriteManager();
        $this->notificationManager = new NotificationManager();
    }

    public function toggle()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:favorite:manage'), $ctx)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Vous devez être connecté']);
            exit;
        }

        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => Lang::get('core.bad-token')]);
            exit;
        }

        $user = UsersManager::getCurrentUser();
        $userId = (int)($user->attributes['id'] ?? 0);
        $poiId = (int)($_POST['poi_id'] ?? 0);

        if ($userId <= 0 || $poiId == 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
            exit;
        }

        $isFavorite = $this->favoriteManager->isFavorite($poiId, $userId);
        
        if ($isFavorite) {
            $result = $this->favoriteManager->remove($poiId, $userId);
            $action = 'removed';
            $newState = false;
        } else {
            $result = $this->favoriteManager->add($poiId, $userId);
            $action = 'added';
            $newState = true;
        }

        if (!$result) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de l\'opération'
            ]);
            exit;
        }

        // Vérifier l'état final pour être sûr
        $finalState = $this->favoriteManager->isFavorite($poiId, $userId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'action' => $action,
            'is_favorite' => $finalState
        ]);
        exit;
    }

    public function myFavorites()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:favorite:manage'), $ctx)) {
            Show::msg('Vous devez être connecté pour voir vos favoris.', 'error');
            return $this->core->redirect($this->router->generate('login'));
        }

        $user = UsersManager::getCurrentUser();
        $userId = (int)($user->attributes['id'] ?? 0);

        $favorites = $this->favoriteManager->getUserFavorites($userId);
        
        // Enrichir avec les URLs de détail des POIs
        $favoritesEnriched = [];
        if (!empty($favorites)) {
            // Collect POI IDs
            $poiIds = [];
            foreach ($favorites as $favorite) {
                if (!empty($favorite['poi_id'])) {
                    $poiIds[] = (int)$favorite['poi_id'];
                }
            }
            $poiIds = array_unique($poiIds);

            // Fetch POI Data via Provider
            $poiDataList = [];
            try {
                $provider = $this->core->callHook('entity:provider-request', ['entity' => 'poi']);
                if ($provider && method_exists($provider, 'getMany')) {
                    $poiDataList = $provider->getMany($poiIds);
                }
            } catch (\Exception $e) {
                $this->core->getLogger()->warning("PoiFavorites::myFavorites - Provider error: " . $e->getMessage());
            }

            foreach ($favorites as $favorite) {
                $poiId = (int)($favorite['poi_id'] ?? 0);
                if ($poiId <= 0) continue;
                
                $favoriteData = $favorite;
                
                // Formater la date
                if (!empty($favorite['created_at'])) {
                    try {
                        $date = new \DateTime($favorite['created_at']);
                        $favoriteData['created_at_formatted'] = $date->format('d/m/Y');
                    } catch (\Exception $e) {
                        $favoriteData['created_at_formatted'] = $favorite['created_at'];
                    }
                } else {
                    $favoriteData['created_at_formatted'] = '';
                }
                
                // Default values
                $favoriteData['poi_name'] = 'POI #' . $poiId;
                $favoriteData['poi_address'] = '';
                $favoriteData['poi_city'] = '';
                $favoriteData['poi_category'] = '';
                $favoriteData['poi_subcategory'] = '';
                $favoriteData['poi_description'] = '';
                $favoriteData['poi_has_location'] = false;
                $favoriteData['detail_url'] = '/searchEVO/poi/' . $poiId;
                $favoriteData['detail_url_short'] = '';
                $favoriteData['detail_url_final'] = $favoriteData['detail_url'];
                $favoriteData['has_active_offers'] = false;
                $favoriteData['offers_count'] = 0;

                // Enrich from Provider Data
                if (isset($poiDataList[$poiId])) {
                    $poi = $poiDataList[$poiId];
                    $favoriteData['poi_name'] = $poi['name'] ?? ('POI #' . $poiId);
                    $favoriteData['poi_address'] = ''; // Provider might not return full address yet in getMany default impl, but we can extend it or accept less data for now.
                    // Actually my PoiCapabilityProvider implementation returned 'category', 'subcategory'.
                    // It did NOT return address fields. If address is needed, I should update the Provider later.
                    // For now, let's use what we have.
                    
                    $favoriteData['poi_category'] = $poi['category'] ?? '';
                    $favoriteData['poi_subcategory'] = $poi['subcategory'] ?? '';
                    $favoriteData['poi_has_location'] = !empty($poi['latitude']) && !empty($poi['longitude']);
                    
                    if (!empty($poi['url'])) {
                        $favoriteData['detail_url'] = $poi['url'];
                        $favoriteData['detail_url_short'] = $poi['url'];
                        $favoriteData['detail_url_final'] = $poi['url'];
                    }
                }

                $favoritesEnriched[] = $favoriteData;
            }
        }
        
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('PoiFavorites', 'my-favorites');
        
        $tpl->set('favorites', $favoritesEnriched);
        $tpl->set('ROUTER', $this->router);
        $tpl->set('csrfToken', Csrf::token());
        
        $response->setTitle('Mes POIs favoris');
        $response->addTemplate($tpl);
        return $response;
    }

    public function remove()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:favorite:manage'), $ctx)) {
            Show::msg('Vous devez être connecté pour retirer un favori.', 'error');
            return $this->core->redirect($this->router->generate('login'));
        }

        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('poi-favorites-my-favorites'));
        }

        $user = UsersManager::getCurrentUser();
        $userId = (int)($user->attributes['id'] ?? 0);
        $poiId = (int)($_POST['poi_id'] ?? 0);

        if ($userId <= 0 || $poiId == 0) {
            Show::msg('Données invalides.', 'error');
            return $this->core->redirect($this->router->generate('poi-favorites-my-favorites'));
        }

        $result = $this->favoriteManager->remove($poiId, $userId);
        
        if ($result) {
            Show::msg('Le POI a été retiré de vos favoris.', 'success');
        } else {
            Show::msg('Erreur lors de la suppression du favori.', 'error');
        }

        return $this->core->redirect($this->router->generate('poi-favorites-my-favorites'));
    }

    public function notifications()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:favorite:manage'), $ctx)) {
            Show::msg('Vous devez être connecté.', 'error');
            return $this->core->redirect($this->router->generate('login'));
        }

        $user = UsersManager::getCurrentUser();
        $userId = (int)($user->attributes['id'] ?? 0);

        // Récupérer toutes les notifications (lues et non lues)
        $allNotifications = $this->notificationManager->getUserNotifications($userId, false);
        // Récupérer uniquement les notifications non lues pour le comptage
        $unreadNotifications = $this->notificationManager->getUserNotifications($userId, true);
        $unreadCount = $this->notificationManager->getUnreadCount($userId);
        
        // Collect POI IDs
        $poiIds = [];
        foreach ($allNotifications as $notif) {
            if (!empty($notif['poi_id'])) {
                $poiIds[] = (int)$notif['poi_id'];
            }
        }
        $poiIds = array_unique($poiIds);

        // Fetch POI Data via Provider
        $poiDataList = [];
        try {
            $provider = $this->core->callHook('entity:provider-request', ['entity' => 'poi']);
            if ($provider && method_exists($provider, 'getMany')) {
                $poiDataList = $provider->getMany($poiIds);
            }
        } catch (\Exception $e) {
            $this->core->getLogger()->warning("PoiFavorites::notifications - Provider error: " . $e->getMessage());
        }

        // Préparer les notifications avec dates formatées, flags booléens et noms de POI
        $notificationsData = [];
        foreach ($allNotifications as $notif) {
            $notifData = $notif;
            $poiId = (int)($notifData['poi_id'] ?? 0);
            
            // Initialiser les valeurs par défaut
            $notifData['poi_name'] = 'POI #' . $poiId;
            $notifData['poi_detail_url'] = '/searchEVO/poi/' . $poiId;
            
            // Récupérer le nom du POI depuis Provider Data
            if (isset($poiDataList[$poiId])) {
                $poi = $poiDataList[$poiId];
                if (!empty($poi['name'])) {
                    $notifData['poi_name'] = $poi['name'];
                }
                if (!empty($poi['url'])) {
                    $notifData['poi_detail_url'] = $poi['url'];
                }
            }
            
            // Formater la date
            if (!empty($notifData['created_at'])) {
                try {
                    $date = new \DateTime($notifData['created_at']);
                    $notifData['created_at_formatted'] = $date->format('d/m/Y H:i');
                } catch (\Exception $e) {
                    $notifData['created_at_formatted'] = $notifData['created_at'] ?? '';
                }
            } else {
                $notifData['created_at_formatted'] = '';
            }
            
            // Ajouter un flag booléen pour is_unread (plus simple pour le template)
            $notifData['is_unread'] = ((int)($notifData['is_read'] ?? 0)) === 0;
            
            // Traduire le type de notification
            $notificationType = $notifData['notification_type'] ?? '';
            $notifData['notification_type_label'] = $notificationType; // Par défaut, garder la valeur originale
            switch ($notificationType) {
                case 'poi_updated':
                    $notifData['notification_type_label'] = 'POI mis à jour';
                    break;
                case 'poi_created':
                    $notifData['notification_type_label'] = 'Nouveau POI';
                    break;
                case 'poi_deleted':
                    $notifData['notification_type_label'] = 'POI supprimé';
                    break;
                default:
                    // Si le type n'est pas reconnu, utiliser notification_data s'il existe, sinon garder le type
                    if (!empty($notifData['notification_data'])) {
                        $notifData['notification_type_label'] = $notifData['notification_data'];
                    }
                    break;
            }
            
            // Si notification_data existe et est différent du label, l'utiliser comme message
            // Sinon, ne pas l'afficher (pour éviter la duplication)
            if (!empty($notifData['notification_data']) && $notifData['notification_data'] !== $notifData['notification_type_label']) {
                $notifData['message'] = $notifData['notification_data'];
            } else {
                $notifData['message'] = '';
            }
            
            $notificationsData[] = $notifData;
        }
        
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('PoiFavorites', 'notifications');
        
        $tpl->set('notifications', $notificationsData);
        $tpl->set('unreadCount', $unreadCount);
        $tpl->set('csrfToken', Csrf::token());
        
        $response->setTitle('Mes notifications');
        $response->addTemplate($tpl);
        return $response;
    }

    public function markRead()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:favorite:manage'), $ctx)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Vous devez être connecté']);
            exit;
        }

        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => Lang::get('core.bad-token')]);
            exit;
        }

        $user = UsersManager::getCurrentUser();
        $userId = (int)($user->attributes['id'] ?? 0);
        $notificationId = (int)($_POST['notification_id'] ?? 0);

        if ($notificationId > 0) {
            $this->notificationManager->markAsRead($notificationId, $userId);
        } else {
            $this->notificationManager->markAllAsRead($userId);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }


}

