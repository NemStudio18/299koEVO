<?php
namespace LocalFeed\Controllers;

use Core\Auth\CurrentUser;
use Core\Auth\UsersManager;
use LocalFeed\Services\LocalFeedService;

class LocalFeedController {

    private $feedService;

    public function __construct() {
        $this->feedService = LocalFeedService::getInstance();
    }

    /**
     * Page complète dédiée : /mur ou /localfeed
     */
    public function page() {
        $user = UsersManager::getCurrentUser();
        $userId = $user ? (int)($user->id ?? 0) : null;
        $feedTimeline = $this->feedService->getTimeline($userId, 1, 15);
        $timeline = $feedTimeline;
        $isFeedActive = true;

        $response = new \Core\Responses\PublicResponse();
        $response->setTitle('Le Mur de Bourges');
        $response->hidePageTitle(true);
        \Template\Template::addGlobal('HIDE_PAGE_TITLE', true);
        
        // Template autonome contenant le flux
        $tpl = new \Template\Template(ROOT . 'theme/halloween/template/localfeed_home.tpl');
        $tpl->set('feedTimeline', $feedTimeline);
        $tpl->set('timeline', $timeline);
        $tpl->set('isFeedActive', true);

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Endpoint pour récupérer le flux (page, filter)
     */
    public function feed() {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(30, max(1, (int)$_GET['limit'])) : 10;
        $filter = $_GET['filter'] ?? null;

        $user = UsersManager::getCurrentUser();
        $userId = $user ? (int)($user->id ?? 0) : null;

        $timeline = $this->feedService->getTimeline($userId, $page, $limit, $filter);

        $isHtmx = isset($_SERVER['HTTP_HX_REQUEST']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

        if ($isHtmx) {
            header('Content-Type: text/html; charset=utf-8');
            include __DIR__ . '/../template/feed_items.tpl';
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($timeline, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Endpoint pour le flux d'un POI spécifique (tab /p-{id})
     */
    public function poiFeed(int $id) {
        $user = UsersManager::getCurrentUser();
        $userId = $user ? (int)($user->id ?? 0) : null;

        $posts = $this->feedService->getPoiPosts($id, $userId);

        $isHtmx = isset($_SERVER['HTTP_HX_REQUEST']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

        if ($isHtmx) {
            header('Content-Type: text/html; charset=utf-8');
            include __DIR__ . '/../template/poi_feed_tab.tpl';
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['poi_id' => $id, 'posts' => $posts], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Endpoint unifié pour les réactions citoyennes (like, interest)
     * Accessible en POST /localfeed/react
     * Payload attendu : { type: 'post'|'event'|'offer'|'blog', id: int, reaction: 'like'|'interest' }
     */
    public function react() {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'require_login' => true,
                'error' => 'Veuillez vous connecter pour réagir.'
            ]);
            exit;
        }

        $payload = $_POST;
        if (empty($payload)) {
            $input = file_get_contents('php://input');
            $payload = json_decode($input, true) ?? [];
        }

        $contentType = trim($payload['type'] ?? $payload['content_type'] ?? '');
        $contentId = (int)($payload['id'] ?? $payload['content_id'] ?? 0);
        $reactionType = trim($payload['reaction'] ?? $payload['reaction_type'] ?? 'like');

        if (empty($contentType) || $contentId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Paramètres de réaction invalides.']);
            exit;
        }

        $userId = (int)($user->id ?? 0);

        try {
            $res = \LocalFeed\Services\ReactionService::getInstance()->toggle($userId, $contentType, $contentId, $reactionType);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($res);
            exit;
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        } catch (\RuntimeException $e) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        } catch (\Throwable $t) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Une erreur interne est survenue.']);
            exit;
        }
    }

    /**
     * Variante avec paramètres d'URL : POST /localfeed/react/[a:type]/[i:id]
     */
    public function reactItem(string $type, int $id) {
        if (!isset($_POST['type'])) $_POST['type'] = $type;
        if (!isset($_POST['id'])) $_POST['id'] = $id;
        return $this->react();
    }

    /**
     * Toggle Like sur un post (conservé pour rétrocompatibilité)
     */
    public function toggleLike(int $id) {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Veuillez vous connecter pour aimer cette publication.']);
            exit;
        }

        $userId = (int)($user->id ?? 0);
        $res = $this->feedService->toggleLike($id, $userId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($res);
        exit;
    }

    /**
     * Enregistrer un partage
     */
    public function recordShare(int $id) {
        $user = UsersManager::getCurrentUser();
        $userId = $user ? (int)($user->id ?? 0) : null;
        $channel = $_POST['channel'] ?? 'native';

        $res = $this->feedService->recordShare($id, $userId, $channel);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($res);
        exit;
    }

    /**
     * Publier un post (Réservé au gérant du POI ou admin)
     */
    public function publishPost(int $id) {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }

        $currentUser = CurrentUser::get();
        $isAdmin = (isset($user->is_admin) && $user->is_admin) || (method_exists($user, 'getGroup') && $user->getGroup() === 'admin');
        $canManage = $currentUser && ($currentUser->canManagePoi($id) || $isAdmin);

        if (!$canManage) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Vous n\'êtes pas autorisé à publier sur ce commerce.']);
            exit;
        }

        $payload = $_POST;
        if (empty($payload)) {
            $input = file_get_contents('php://input');
            $payload = json_decode($input, true) ?? [];
        }

        try {
            $postId = $this->feedService->createPost($id, (int)$user->id, $payload);
            
            // Si requête AJAX ou formulaire classique
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'post_id' => $postId, 'message' => 'Publication partagée avec succès !']);
                exit;
            }

            // Redirection vers le dashboard pro
            header('Location: /business-dashboard?post_success=1');
            exit;
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Supprimer un post
     */
    public function deletePost(int $id) {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }

        // Vérification de la propriété du post
        $db = \Core\Database::getInstance();
        $post = $db->findFirst("SELECT poi_id, author_user_id FROM poi_posts WHERE id = :id", [':id' => $id]);
        if (!$post) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Publication introuvable.']);
            exit;
        }

        $poiId = (int)($post['poi_id'] ?? $post->poi_id);
        $currentUser = CurrentUser::get();
        $isAdmin = (isset($user->is_admin) && $user->is_admin) || (method_exists($user, 'getGroup') && $user->getGroup() === 'admin');
        $canManage = $currentUser && ($currentUser->canManagePoi($poiId) || $isAdmin);

        if (!$canManage) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Action non autorisée.']);
            exit;
        }

        $this->feedService->deletePost($id);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'Publication supprimée.']);
        exit;
    }

    /**
     * Récupérer les commentaires d'un post
     */
    public function getComments(int $id) {
        $comments = $this->feedService->getPostComments($id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'post_id' => $id, 'comments' => $comments]);
        exit;
    }

    /**
     * Ajouter un commentaire sur un post
     */
    public function addComment(int $id) {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Veuillez vous connecter pour laisser un commentaire.']);
            exit;
        }

        $userId = (int)($user->id ?? 0);
        $authorName = !empty($user->username) ? $user->username : 'Citoyen';

        $payload = $_POST;
        if (empty($payload)) {
            $input = file_get_contents('php://input');
            $payload = json_decode($input, true) ?? [];
        }

        $content = trim($payload['content'] ?? '');
        if (empty($content)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Le commentaire ne peut pas être vide.']);
            exit;
        }

        try {
            $res = $this->feedService->addComment($id, $userId, $content, $authorName);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'comment' => $res,
                'message' => 'Commentaire publié avec succès !'
            ]);
            exit;
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Supprimer un commentaire
     */
    public function deleteComment(int $id) {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }

        $userId = (int)($user->id ?? 0);
        $isAdmin = (isset($user->is_admin) && $user->is_admin) || (method_exists($user, 'getGroup') && $user->getGroup() === 'admin');

        try {
            $this->feedService->deleteComment($id, $userId, $isAdmin);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Commentaire supprimé.']);
            exit;
        } catch (\Exception $e) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
}
