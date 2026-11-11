<?php

namespace PoiReviews\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Security\Csrf;
use Utils\Show;
use Core\Lang;
use PoiReviews\Entities\ReviewManager;

defined('ROOT') or exit('Access denied!');

class ReviewsAdminController extends AdminController
{
    private ReviewManager $reviewManager;

    public function __construct()
    {
        parent::__construct();
        
        
        $this->reviewManager = new ReviewManager();
    }

    public function index()
    {
        $response = new AdminResponse();
        $response->setTitle('Gestion des avis');
        $tpl = $response->createPluginTemplate('PoiReviews', 'admin-list');
        
        $status = $_GET['status'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $reviews = $this->reviewManager->getAll($status, $limit, $offset);
        
        // S'assurer que $reviews est toujours un tableau
        if (!is_array($reviews)) {
            $reviews = [];
        }
        
        $reviewsData = [];
        foreach ($reviews as $review) {
            $reviewData = $review->toArray();
            $reviewData['restoreUrl'] = $this->router->generate('admin-poi-reviews-restore', ['id' => $review->getId()]);
            $reviewData['deleteUrl'] = $this->router->generate('admin-poi-reviews-delete', ['id' => $review->getId()]);
            
            // Formater le commentaire : valeur par défaut et troncature
            $comment = $reviewData['comment'] ?? '';
            if (empty($comment)) {
                $reviewData['comment_display'] = 'Aucun commentaire';
            } else {
                $reviewData['comment_display'] = mb_strlen($comment) > 50 
                    ? mb_substr($comment, 0, 50) . '...' 
                    : $comment;
            }
            
            // Formater la date
            if (!empty($reviewData['created_at'])) {
                try {
                    $reviewData['created_at_formatted'] = date('d/m/Y H:i', strtotime($reviewData['created_at']));
                } catch (\Exception $e) {
                    $reviewData['created_at_formatted'] = $reviewData['created_at'];
                }
            } else {
                $reviewData['created_at_formatted'] = '';
            }
            
            // Préparer les étoiles pour l'affichage (comme dans ReviewsPublicController)
            $reviewStars = [];
            $rating = (int)($reviewData['rating'] ?? 0);
            for ($i = 1; $i <= 5; $i++) {
                $reviewStars[] = [
                    'isFull' => $i <= $rating
                ];
            }
            $reviewData['stars'] = $reviewStars;
            $reviewData['rating'] = $rating; // S'assurer que rating est un entier
            
            $reviewsData[] = $reviewData;
        }
        
        $tpl->set('reviews', $reviewsData);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('currentStatus', $status);
        $tpl->set('currentPage', $page);
        $tpl->set('ROUTER', $this->router);
        
        // Générer les URLs pour les filtres
        // Utiliser des URLs relatives simples car le router supprime les query strings avant le matching
        // Les query strings sont toujours disponibles dans $_GET, donc on peut utiliser des URLs relatives
        $tpl->set('urlActive', '/admin/poi-reviews?status=active');
        $tpl->set('urlModerated', '/admin/poi-reviews?status=moderated');
        $tpl->set('urlDeleted', '/admin/poi-reviews?status=deleted');
        $tpl->set('urlAll', '/admin/poi-reviews');
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function restore()
    {
        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/poi-reviews');
        }

        $id = (int)$this->router->getParam('id');
        
        if ($this->reviewManager->restore($id)) {
            Show::msg('Avis restauré avec succès.', 'success');
        } else {
            Show::msg('Erreur lors de la restauration.', 'error');
        }

        return $this->core->redirect('/admin/poi-reviews');
    }

    public function delete()
    {
        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/poi-reviews');
        }

        $id = (int)$this->router->getParam('id');
        
        if ($this->reviewManager->delete($id)) {
            Show::msg('Avis supprimé avec succès.', 'success');
        } else {
            Show::msg('Erreur lors de la suppression.', 'error');
        }

        return $this->core->redirect('/admin/poi-reviews');
    }
}

