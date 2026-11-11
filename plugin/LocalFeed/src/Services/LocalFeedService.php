<?php
namespace LocalFeed\Services;

use Core\Database;

class LocalFeedService {

    private static $instance = null;
    private $db;

    private function __construct() {
        $this->db = Database::getInstance();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function fetchAll(string $sql, array $params = []): array {
        try {
            $res = $this->db->query($sql, $params);
            return is_array($res) ? $res : [];
        } catch (\Exception $e) {
            error_log("LocalFeedService fetchAll SQL Error: " . $e->getMessage());
            return [];
        }
    }

    private function fetchOne(string $sql, array $params = []): ?array {
        $rows = $this->fetchAll($sql, $params);
        if (!empty($rows)) {
            return (array)$rows[0];
        }
        return null;
    }

    /**
     * Récupère la timeline locale agrégée pour un utilisateur
     */
    public function getTimeline(?int $userId = null, int $page = 1, int $limit = 10, ?string $filter = null): array {
        $offset = max(0, ($page - 1) * $limit);
        $favoritePoiIds = [];

        if ($userId) {
            $favSql = "SELECT poi_id FROM favorites WHERE user_id = :uid";
            $favRows = $this->fetchAll($favSql, [':uid' => $userId]);
            if (!empty($favRows)) {
                $favoritePoiIds = array_map(function($r) {
                    $item = (array)$r;
                    return (int)($item['poi_id'] ?? 0);
                }, $favRows);
            }
        }

        $items = [];

        // 1. Publications des Commerçants (poi_posts)
        if ($filter === null || $filter === 'posts' || $filter === 'all') {
            $postsSql = "SELECT p.*, 
                                poi.name AS poi_name, poi.slug AS poi_slug,
                                addr.city AS poi_city, addr.street AS poi_street
                         FROM poi_posts p
                         JOIN poi ON poi.id = p.poi_id
                         LEFT JOIN poi_addresses addr ON addr.poi_id = poi.id AND addr.is_primary = 1
                         WHERE p.status = 'published'
                         ORDER BY p.pinned DESC, p.created_at DESC
                         LIMIT 50";
            
            $posts = $this->fetchAll($postsSql);
            foreach ($posts as $post) {
                $pArr = (array)$post;
                $poiId = (int)$pArr['poi_id'];
                $isFav = in_array($poiId, $favoritePoiIds);

                // Score de pondération
                $score = 100;
                if (!empty($pArr['pinned'])) $score += 200;
                if ($isFav) $score += 150;

                // Détermination si liké par l'utilisateur
                $hasLiked = false;
                if ($userId) {
                    $likeCheck = $this->fetchOne(
                        "SELECT 1 FROM poi_post_likes WHERE post_id = :pid AND user_id = :uid",
                        [':pid' => $pArr['id'], ':uid' => $userId]
                    );
                    $hasLiked = !empty($likeCheck);
                }

                $items[] = [
                    'type' => 'post',
                    'id' => (int)$pArr['id'],
                    'poi_id' => $poiId,
                    'poi_name' => $pArr['poi_name'],
                    'poi_slug' => $pArr['poi_slug'] ?? ('p-' . $poiId),
                    'poi_location' => trim(($pArr['poi_street'] ?? '') . ' ' . ($pArr['poi_city'] ?? 'Bourges')),
                    'is_favorite_poi' => $isFav,
                    'title' => $pArr['title'],
                    'content' => $pArr['content'],
                    'media_url' => $pArr['media_url'],
                    'post_type' => $pArr['post_type'],
                    'likes_count' => (int)($pArr['likes_count'] ?? 0),
                    'shares_count' => (int)($pArr['shares_count'] ?? 0),
                    'has_liked' => $hasLiked,
                    'created_at' => $pArr['created_at'],
                    'time_ago' => $this->timeElapsedString($pArr['created_at']),
                    'timestamp' => strtotime($pArr['created_at']),
                    'score' => $score
                ];
            }
        }

        // 2. Événements imminents de l'Agenda municipal
        if ($filter === null || $filter === 'events' || $filter === 'all') {
            try {
                // Enrichissement des images depuis events.json
                $eventImagesByTitle = [];
                $jsonFile = (defined('DATA_PLUGIN') ? DATA_PLUGIN : (defined('DATA') ? DATA . 'plugin/' : __DIR__ . '/../../../../data/plugin/')) . 'agenda/events.json';
                if (file_exists($jsonFile)) {
                    $jsonEvents = json_decode(file_get_contents($jsonFile), true);
                    if (is_array($jsonEvents)) {
                        foreach ($jsonEvents as $je) {
                            $img = $je['image_url'] ?? $je['proof_file'] ?? null;
                            if (!empty($img) && !empty($je['title'])) {
                                $eventImagesByTitle[trim(mb_strtolower($je['title']))] = $img;
                            }
                        }
                    }
                }

                $eventsSql = "SELECT MIN(id) AS id, title, MIN(description) AS description, MIN(start_date) AS start_date, MIN(end_date) AS end_date, MIN(location) AS location, MIN(poi_id) AS poi_id, MIN(proof_file) AS proof_file 
                              FROM agenda_events 
                              WHERE (status = 'approved' OR status IS NULL)
                                AND start_date >= CURDATE()
                                AND start_date < CURDATE() + INTERVAL 21 DAY
                              GROUP BY title, DATE(start_date)
                              ORDER BY start_date ASC 
                              LIMIT 60";
                $events = $this->fetchAll($eventsSql);
                
                // Charger les films du Méga CGR si disponibles
                $cgrFile = DATA_PLUGIN . 'agenda/cgr_movies.json';
                $cgrMovies = [];
                if (file_exists($cgrFile)) {
                    $cgrMovies = json_decode(@file_get_contents($cgrFile), true) ?: [];
                }

                $hasCgrInList = false;
                foreach ($events as $event) {
                    $eArr = (array)$event;
                    $startTs = strtotime($eArr['start_date'] ?? 'now');
                    $isSoon = ($startTs - time()) <= (48 * 3600); // Dans les 48h
                    
                    $score = 80;
                    if ($isSoon) $score += 120;

                    $titleKey = trim(mb_strtolower($eArr['title'] ?? ''));
                    $mediaUrl = !empty($eArr['proof_file']) ? $eArr['proof_file'] : ($eventImagesByTitle[$titleKey] ?? null);

                    // Si pas d'image et qu'il s'agit d'un film ou événement cinéma, chercher dans cgr_movies.json
                    if (empty($mediaUrl) && !empty($cgrMovies)) {
                        $cleanTitle = trim(preg_replace('/^cin[eé]ma\s*:\s*/iu', '', $eArr['title'] ?? ''));
                        $cleanTitleKey = trim(mb_strtolower($cleanTitle));
                        foreach ($cgrMovies as $cm) {
                            $cmTitle = trim(mb_strtolower($cm['title'] ?? ''));
                            if (!empty($cmTitle) && ($cmTitle === $cleanTitleKey || str_contains($cleanTitleKey, $cmTitle) || str_contains($cmTitle, $cleanTitleKey))) {
                                $mediaUrl = $cm['image'] ?? null;
                                break;
                            }
                        }
                    }

                    $isCgrEvent = ($eArr['id'] === 'cgr_master_bourges' || stripos($eArr['title'] ?? '', 'cgr') !== false);
                    if ($isCgrEvent) {
                        $hasCgrInList = true;
                        $score += 90;
                    }

                    $items[] = [
                        'type' => 'event',
                        'id' => $eArr['id'],
                        'title' => $eArr['title'],
                        'content' => $eArr['description'],
                        'media_url' => $mediaUrl,
                        'location' => $eArr['location'] ?? 'Bourges',
                        'start_date' => $eArr['start_date'],
                        'end_date' => $eArr['end_date'],
                        'poi_id' => !empty($eArr['poi_id']) ? (int)$eArr['poi_id'] : null,
                        'time_display' => $this->eventWhen($startTs),
                        'timestamp' => $startTs,
                        'score' => $score,
                        'is_cgr' => $isCgrEvent,
                        'cgr_movies' => $isCgrEvent ? $cgrMovies : []
                    ];
                }

                // Si l'événement CGR n'était pas dans la table SQL agenda_events, l'injecter depuis cgr_movies.json
                if (!$hasCgrInList && !empty($cgrMovies)) {
                    $firstMovie = $cgrMovies[0] ?? [];
                    $movieCount = count($cgrMovies);
                    $items[] = [
                        'type' => 'event',
                        'id' => 'cgr_master_bourges',
                        'title' => "À l'affiche au Méga CGR de Bourges ({$movieCount} films)",
                        'content' => "Découvrez les séances du moment au Méga CGR : programmation complète, versions VF/VOST/ICE et réservation directe de vos e-billets.",
                        'media_url' => $firstMovie['image'] ?? null,
                        'location' => "Méga CGR Bourges, ZAC du Prado",
                        'start_date' => date('Y-m-d'),
                        'end_date' => date('Y-m-d', strtotime('+14 days')),
                        'poi_id' => 925,
                        'time_display' => "Aujourd'hui & cette semaine",
                        'timestamp' => time(),
                        'score' => 195,
                        'is_cgr' => true,
                        'cgr_movies' => $cgrMovies
                    ];
                }
            } catch (\Exception $e) {
                // Table optionnelle
            }
        }

        // 3. Bons plans & Offres promotionnelles actives (GoodDeals lbp_deals & table offers)
        if ($filter === null || $filter === 'offers' || $filter === 'deals' || $filter === 'all') {
            try {
                // Table GoodDeals (lbp_deals)
                $dealsSql = "SELECT d.*, p.name AS poi_name, p.slug AS poi_slug, p.addr_street, p.addr_city
                             FROM lbp_deals d
                             JOIN poi_public p ON p.id = d.poi_id
                             WHERE d.status = 'ACTIVE' AND d.stock > 0
                             ORDER BY d.created_at DESC 
                             LIMIT 10";
                $deals = $this->fetchAll($dealsSql);
                foreach ($deals as $deal) {
                    $dArr = (array)$deal;
                    $poiId = (int)$dArr['poi_id'];
                    $isFav = in_array($poiId, $favoritePoiIds);
                    $score = 95;
                    if ($isFav) $score += 130;

                    $items[] = [
                        'type' => 'offer',
                        'id' => (int)$dArr['id'],
                        'poi_id' => $poiId,
                        'poi_name' => $dArr['poi_name'] ?? 'Commerce de Bourges',
                        'poi_slug' => $dArr['poi_slug'] ?? ('p-' . $poiId),
                        'title' => $dArr['title'],
                        'content' => $dArr['description'] . (!empty($dArr['discounted_price']) ? (' • Prix exclusif : ' . $dArr['discounted_price'] . ' €') : ''),
                        'media_url' => !empty($dArr['photo_1']) ? $dArr['photo_1'] : null,
                        'valid_until' => null,
                        'is_favorite_poi' => $isFav,
                        'timestamp' => strtotime($dArr['created_at']),
                        'score' => $score
                    ];
                }
            } catch (\Throwable $e) {}

            try {
                $offersSql = "SELECT o.*, poi.name AS poi_name, poi.slug AS poi_slug 
                              FROM offers o
                              JOIN poi ON poi.id = o.poi_id
                              WHERE o.status = 'ACTIVE' 
                                AND (o.valid_until IS NULL OR o.valid_until >= NOW())
                              ORDER BY o.created_at DESC 
                              LIMIT 10";
                $offers = $this->fetchAll($offersSql);
                foreach ($offers as $offer) {
                    $oArr = (array)$offer;
                    $poiId = (int)$oArr['poi_id'];
                    $isFav = in_array($poiId, $favoritePoiIds);

                    $score = 90;
                    if ($isFav) $score += 130;

                    $items[] = [
                        'type' => 'offer',
                        'id' => (int)$oArr['id'],
                        'poi_id' => $poiId,
                        'poi_name' => $oArr['poi_name'],
                        'poi_slug' => $oArr['poi_slug'] ?? ('p-' . $poiId),
                        'title' => $oArr['title'],
                        'content' => $oArr['description'],
                        'valid_until' => $oArr['valid_until'],
                        'is_favorite_poi' => $isFav,
                        'timestamp' => strtotime($oArr['created_at']),
                        'score' => $score
                    ];
                }
            } catch (\Exception $e) {
                // Offres optionnelles
            }
        }

        // 4. Articles du Blog Vivre-Bourges
        if ($filter === null || $filter === 'blog' || $filter === 'all') {
            try {
                $blogFile = (defined('DATA') ? DATA : __DIR__ . '/../../../../data/') . 'plugin/blog/blog.json';
                if (file_exists($blogFile)) {
                    $blogData = json_decode(file_get_contents($blogFile), true);
                    if (is_array($blogData)) {
                        // Likes blog depuis la base
                        $blogLikesMap = [];
                        try {
                            $db = \Core\Database::getInstance()->getPdo();
                            $stmtLikes = $db->query("SELECT post_id, COUNT(*) as cnt FROM blog_likes GROUP BY post_id");
                            while ($r = $stmtLikes->fetch(\PDO::FETCH_ASSOC)) {
                                $blogLikesMap[(int)$r['post_id']] = (int)$r['cnt'];
                            }
                        } catch (\Throwable $e) {}

                        foreach ($blogData as $bPost) {
                            if (!empty($bPost['draft'])) continue;
                            $bId = (int)($bPost['id'] ?? 0);
                            $bDate = !empty($bPost['date']) ? strtotime($bPost['date']) : time();
                            $bUrl = !empty($bPost['name']) ? ('/blog/' . \Utils\Util::strToUrl($bPost['name']) . '-' . $bId . '.html') : ('/blog');
                            $bDesc = !empty($bPost['seoDesc']) ? $bPost['seoDesc'] : strip_tags(html_entity_decode($bPost['intro'] ?? ''));

                            $items[] = [
                                'type' => 'blog',
                                'id' => $bId,
                                'title' => $bPost['name'] ?? 'Actualité',
                                'content' => $bDesc,
                                'media_url' => $bPost['img'] ?? null,
                                'url' => $bUrl,
                                'time_ago' => $this->timeElapsedString(date('Y-m-d H:i:s', $bDate)),
                                'likes_count' => $blogLikesMap[$bId] ?? 0,
                                'timestamp' => $bDate,
                                'score' => 150 // Forte visibilité éditoriale
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Blog optionnel
            }
        }

        // 5. Bulletins météo (06h00 et 20h00, prévisions 24 h)
        if ($filter === null || $filter === 'all' || $filter === 'weather') {
            try {
                if (!class_exists(WeatherBulletinService::class, false)) {
                    require_once __DIR__ . '/WeatherBulletinService.php';
                }
                $bulletins = (new WeatherBulletinService())->getBulletins();
                foreach ($bulletins as $idx => $b) {
                    $items[] = [
                        'type' => 'weather',
                        'id' => (int)$b['slot'],
                        'title' => $b['title'],
                        'content' => $b['summary'],
                        'bulletin' => $b,
                        'is_latest' => $idx === 0,
                        'time_ago' => $this->timeElapsedString(date('Y-m-d H:i:s', (int)$b['slot'])),
                        'timestamp' => (int)$b['slot'],
                        'score' => 0
                    ];
                }
            } catch (\Throwable $e) {
                error_log('LocalFeed weather bulletin error: ' . $e->getMessage());
            }
        }

        $items = $this->rankItems($items, $userId);

        // Pagination
        $totalItems = count($items);
        $paginatedItems = array_slice($items, $offset, $limit);
        $hasMore = ($offset + $limit) < $totalItems;

        // Enrichissement des réactions unifiées (content_reactions)
        try {
            $reactionService = ReactionService::getInstance();
            $userReactions = ($userId && $userId > 0) ? $reactionService->getUserReactionsMap($userId, $paginatedItems) : [];
            $reactionCounts = $reactionService->getReactionCountsMap($paginatedItems);

            foreach ($paginatedItems as &$item) {
                $key = ($item['type'] ?? '') . '_' . ($item['id'] ?? 0);
                $item['user_reaction'] = $userReactions[$key] ?? null;
                $item['reactions'] = $reactionCounts[$key] ?? [];
                if (isset($item['reactions']['like'])) {
                    $item['likes_count'] = $item['reactions']['like'];
                }
                if ($item['user_reaction'] === 'like') {
                    $item['has_liked'] = true;
                }
            }
            unset($item);
        } catch (\Throwable $t) {
            // Tolérance si la table n'est pas encore migrée
        }

        return [
            'items' => $paginatedItems,
            'page' => $page,
            'limit' => $limit,
            'total' => $totalItems,
            'has_more' => $hasMore
        ];
    }

    /**
     * Récupère les publications d'un POI spécifique
     */
    public function getPoiPosts(int $poiId, ?int $userId = null): array {
        $sql = "SELECT p.*, poi.name AS poi_name, poi.slug AS poi_slug
                FROM poi_posts p
                JOIN poi ON poi.id = p.poi_id
                WHERE p.poi_id = :poi_id AND p.status = 'published'
                ORDER BY p.pinned DESC, p.created_at DESC";
        
        $posts = $this->fetchAll($sql, [':poi_id' => $poiId]);
        $result = [];

        foreach ($posts as $post) {
            $pArr = (array)$post;
            $hasLiked = false;
            if ($userId) {
                $likeCheck = $this->fetchOne(
                    "SELECT 1 FROM poi_post_likes WHERE post_id = :pid AND user_id = :uid",
                    [':pid' => $pArr['id'], ':uid' => $userId]
                );
                $hasLiked = !empty($likeCheck);
            }

            $pArr['has_liked'] = $hasLiked;
            $pArr['time_ago'] = $this->timeElapsedString($pArr['created_at']);
            $pArr['comments'] = $this->getPostComments((int)$pArr['id']);
            $pArr['comments_count'] = (int)($pArr['comments_count'] ?? count($pArr['comments']));
            $result[] = $pArr;
        }

        return $result;
    }

    /**
     * Toggle Like sur une publication
     */
    public function toggleLike(int $postId, int $userId): array {
        $existing = $this->fetchOne(
            "SELECT id FROM poi_post_likes WHERE post_id = :pid AND user_id = :uid",
            [':pid' => $postId, ':uid' => $userId]
        );

        if ($existing) {
            // Unlike
            $this->db->execute(
                "DELETE FROM poi_post_likes WHERE post_id = :pid AND user_id = :uid",
                [':pid' => $postId, ':uid' => $userId]
            );
            $this->db->execute(
                "UPDATE poi_posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = :id",
                [':id' => $postId]
            );
            $liked = false;
        } else {
            // Like
            $this->db->execute(
                "INSERT INTO poi_post_likes (post_id, user_id, created_at) VALUES (:pid, :uid, NOW())",
                [':pid' => $postId, ':uid' => $userId]
            );
            $this->db->execute(
                "UPDATE poi_posts SET likes_count = likes_count + 1 WHERE id = :id",
                [':id' => $postId]
            );
            $liked = true;
        }

        $post = $this->fetchOne("SELECT likes_count FROM poi_posts WHERE id = :id", [':id' => $postId]);
        $count = $post ? (int)($post['likes_count'] ?? 0) : 0;

        return [
            'success' => true,
            'liked' => $liked,
            'likes_count' => $count
        ];
    }

    /**
     * Enregistre un partage
     */
    public function recordShare(int $postId, ?int $userId = null, string $channel = 'native'): array {
        $this->db->execute(
            "INSERT INTO poi_post_shares (post_id, user_id, channel, created_at) VALUES (:pid, :uid, :ch, NOW())",
            [':pid' => $postId, ':uid' => $userId, ':ch' => $channel]
        );
        $this->db->execute(
            "UPDATE poi_posts SET shares_count = shares_count + 1 WHERE id = :id",
            [':id' => $postId]
        );

        $post = $this->fetchOne("SELECT shares_count FROM poi_posts WHERE id = :id", [':id' => $postId]);
        $count = $post ? (int)($post['shares_count'] ?? 0) : 0;

        return [
            'success' => true,
            'shares_count' => $count
        ];
    }

    /**
     * Crée une nouvelle publication
     */
    public function createPost(int $poiId, int $userId, array $data): int {
        $title = !empty($data['title']) ? trim($data['title']) : null;
        $content = trim($data['content'] ?? '');
        $postType = in_array($data['post_type'] ?? '', ['news', 'special_offer', 'photo', 'event', 'alert']) 
            ? $data['post_type'] 
            : 'news';
        $mediaUrl = !empty($data['media_url']) ? trim($data['media_url']) : null;
        $pinned = !empty($data['pinned']) ? 1 : 0;

        if (empty($content)) {
            throw new \InvalidArgumentException("Le contenu de la publication ne peut pas être vide.");
        }

        $sql = "INSERT INTO poi_posts (poi_id, author_user_id, post_type, title, content, media_url, pinned, status, created_at, updated_at)
                VALUES (:poi_id, :author_id, :post_type, :title, :content, :media_url, :pinned, 'published', NOW(), NOW())";
        
        $this->db->execute($sql, [
            ':poi_id' => $poiId,
            ':author_id' => $userId,
            ':post_type' => $postType,
            ':title' => $title,
            ':content' => $content,
            ':media_url' => $mediaUrl,
            ':pinned' => $pinned
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Supprime une publication
     */
    public function deletePost(int $postId): bool {
        $this->db->execute("DELETE FROM poi_posts WHERE id = :id", [':id' => $postId]);
        return true;
    }

    /**
     * Récupère les commentaires d'une publication
     */
    public function getPostComments(int $postId): array {
        $sql = "SELECT c.id, c.post_id, c.user_id, c.author_name, c.content, c.status, c.created_at, u.username
                FROM poi_post_comments c
                LEFT JOIN users u ON u.id = c.user_id
                WHERE c.post_id = :pid AND c.status = 'published'
                ORDER BY c.created_at ASC";
        
        $rows = $this->fetchAll($sql, [':pid' => $postId]);
        $comments = [];
        foreach ($rows as $row) {
            $r = (array)$row;
            $name = !empty($r['author_name']) ? $r['author_name'] : (!empty($r['username']) ? $r['username'] : 'Citoyen de Bourges');
            $r['display_name'] = $name;
            $r['time_ago'] = $this->timeElapsedString($r['created_at']);
            $comments[] = $r;
        }
        return $comments;
    }

    /**
     * Ajoute un commentaire sur une publication
     */
    public function addComment(int $postId, int $userId, string $content, ?string $authorName = null): array {
        $content = trim($content);
        if (empty($content)) {
            throw new \InvalidArgumentException("Le commentaire ne peut pas être vide.");
        }
        if (mb_strlen($content) > 1000) {
            throw new \InvalidArgumentException("Le commentaire ne peut pas dépasser 1000 caractères.");
        }

        // Vérifier que le post existe
        $post = $this->fetchOne("SELECT id FROM poi_posts WHERE id = :id", [':id' => $postId]);
        if (!$post) {
            throw new \RuntimeException("Publication introuvable.");
        }

        if (empty($authorName)) {
            $userRow = $this->fetchOne("SELECT username FROM users WHERE id = :uid", [':uid' => $userId]);
            $authorName = $userRow ? ($userRow['username'] ?? 'Citoyen') : 'Citoyen';
        }

        $sql = "INSERT INTO poi_post_comments (post_id, user_id, author_name, content, status, created_at)
                VALUES (:pid, :uid, :aname, :content, 'published', NOW())";
        
        $this->db->execute($sql, [
            ':pid' => $postId,
            ':uid' => $userId,
            ':aname' => $authorName,
            ':content' => $content
        ]);

        $commentId = (int)$this->db->lastInsertId();

        // Mettre à jour le compteur sur poi_posts
        $this->db->execute("UPDATE poi_posts SET comments_count = comments_count + 1 WHERE id = :id", [':id' => $postId]);

        $countRow = $this->fetchOne("SELECT comments_count FROM poi_posts WHERE id = :id", [':id' => $postId]);
        $commentsCount = $countRow ? (int)$countRow['comments_count'] : 1;

        return [
            'id' => $commentId,
            'post_id' => $postId,
            'user_id' => $userId,
            'display_name' => $authorName,
            'content' => $content,
            'time_ago' => 'À l\'instant',
            'created_at' => date('Y-m-d H:i:s'),
            'comments_count' => $commentsCount
        ];
    }

    /**
     * Supprime un commentaire
     */
    public function deleteComment(int $commentId, int $userId, bool $canManage = false): bool {
        $comment = $this->fetchOne("SELECT id, post_id, user_id FROM poi_post_comments WHERE id = :id", [':id' => $commentId]);
        if (!$comment) {
            return false;
        }

        if (!$canManage && (int)$comment['user_id'] !== $userId) {
            throw new \RuntimeException("Action non autorisée sur ce commentaire.");
        }

        $postId = (int)$comment['post_id'];
        $this->db->execute("DELETE FROM poi_post_comments WHERE id = :id", [':id' => $commentId]);
        $this->db->execute("UPDATE poi_posts SET comments_count = GREATEST(0, comments_count - 1) WHERE id = :id", [':id' => $postId]);

        return true;
    }

    /**
     * Libellé humain de la date d'un événement (« Aujourd'hui », « Demain à 20:30 », « Sam. 02/11 »).
     */
    private function eventWhen(int $ts): string {
        $days = ['Dim.', 'Lun.', 'Mar.', 'Mer.', 'Jeu.', 'Ven.', 'Sam.'];
        $diffDays = (int)round((strtotime(date('Y-m-d', $ts)) - strtotime(date('Y-m-d'))) / 86400);
        $label = match (true) {
            $diffDays === 0 => "Aujourd'hui",
            $diffDays === 1 => 'Demain',
            default => $days[(int)date('w', $ts)] . ' ' . date('d/m', $ts),
        };
        $time = date('H:i', $ts);
        return $time !== '00:00' ? $label . ' à ' . $time : $label;
    }

    /**
     * Classement « fil d'actualité » :
     *  - fraîcheur (décroissance douce) pour posts, blog, offres, météo ;
     *  - proximité dans le temps pour les événements (un événement remonte à l'approche de sa date) ;
     *  - bonus favoris / épinglés / bulletin météo du moment ;
     *  - légère variation propre à chaque utilisateur et renouvelée chaque heure ;
     *  - puis passe de diversité : jamais plus de 2 cartes du même type à la suite.
     */
    private function rankItems(array $items, ?int $userId): array {
        $now = time();
        
        // Graine dynamique : change à chaque actualisation de page pour donner la sensation d'un fil vivant
        // En session, on conserve un identifiant de session pour que la pagination (infinite scroll) reste cohérente,
        // mais lors d'un F5 / retour sur la page (sans paramètre page > 1), une nouvelle graine est générée !
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        
        $isFirstPage = empty($_GET['page']) || (int)$_GET['page'] === 1;
        if ($isFirstPage || empty($_SESSION['feed_refresh_seed'])) {
            $_SESSION['feed_refresh_seed'] = bin2hex(random_bytes(4));
        }
        $refreshSeed = $_SESSION['feed_refresh_seed'];
        $seed = ($userId ?: 0) . '|' . $refreshSeed;

        foreach ($items as &$it) {
            $ts = (int)($it['timestamp'] ?? $now);
            $ageH = max(0, ($now - $ts) / 3600);
            $fresh = 100 / (1 + $ageH / 12); // 100 à l'instant, 50 à 12 h, ~20 à 2 jours

            switch ($it['type']) {
                case 'event':
                    $untilH = max(0, ($ts - $now) / 3600);
                    $rank = 95 / (1 + $untilH / 24); // aujourd'hui ≈ 95, dans 3 jours ≈ 24
                    if (date('Y-m-d', $ts) === date('Y-m-d', $now)) $rank += 15;
                    break;
                case 'weather':
                    $rank = !empty($it['is_latest']) && $ageH < 14 ? 175 : $fresh * 0.6;
                    break;
                case 'blog':
                    $rank = max(25, $fresh * 1.15);
                    break;
                case 'offer':
                    $rank = max(20, $fresh);
                    if (!empty($it['valid_until']) && strtotime($it['valid_until']) - $now < 72 * 3600) $rank += 20;
                    break;
                default: // post
                    $rank = $fresh;
                    if (!empty($it['score']) && $it['score'] >= 300) $rank += 80; // épinglé
            }
            if (!empty($it['is_favorite_poi'])) $rank += 45;

            // Variation dynamique aléatoire façon Facebook (+/- 25 pts)
            // Permet d'alterner les posts de pertinence similaire à chaque visite/actualisation
            $h = crc32($seed . '|' . $it['type'] . '|' . $it['id']);
            $variation = (($h % 5001) / 100) - 25; // entre -25 et +25 points
            $rank += $variation;

            $it['score'] = round($rank, 2);
        }
        unset($it);

        usort($items, fn($a, $b) => $b['score'] <=> $a['score'] ?: $b['timestamp'] <=> $a['timestamp']);

        // Passe de diversité
        $result = [];
        $pool = $items;
        while ($pool) {
            $n = count($result);
            $blocked = ($n >= 2 && $result[$n - 1]['type'] === $result[$n - 2]['type']) ? $result[$n - 1]['type'] : null;
            $pickIdx = 0;
            if ($blocked !== null) {
                foreach ($pool as $i => $cand) {
                    if ($cand['type'] !== $blocked) { $pickIdx = $i; break; }
                }
            }
            $result[] = $pool[$pickIdx];
            array_splice($pool, $pickIdx, 1);
        }
        return $result;
    }

    /**
     * Sélectionne un échantillon diversifié pour la landing page :
     * Garantit au maximum 1 item par type disponible (Météo, Blog, Post commerçant, Bon Plan, 1 Événement)
     * pour montrer la richesse de l'agrégation territoriale sans saturer ni répéter l'agenda.
     */
    public function getLandingDiversityItems(?int $userId = null, int $limit = 4): array {
        $pool = $this->getTimeline($userId, 1, 40);
        $items = $pool['items'] ?? [];

        // Grouper les items par type
        $byType = [
            'weather' => [],
            'blog'    => [],
            'post'    => [],
            'offer'   => [],
            'event'   => []
        ];

        foreach ($items as $it) {
            $t = $it['type'] ?? 'post';
            if (isset($byType[$t])) {
                $byType[$t][] = $it;
            } else {
                $byType['post'][] = $it;
            }
        }

        $selected = [];
        $typesOrder = ['weather', 'blog', 'post', 'offer', 'event'];

        // Passe 1 : exactement 1 item par type disponible
        foreach ($typesOrder as $t) {
            if (!empty($byType[$t])) {
                $selected[] = array_shift($byType[$t]);
                if (count($selected) >= $limit) {
                    break;
                }
            }
        }

        // Passe 2 : si on a encore de la place et qu'il reste des items, compléter sans dépasser 2 par type
        if (count($selected) < $limit) {
            $typeCount = array_count_values(array_column($selected, 'type'));
            foreach ($items as $cand) {
                $t = $cand['type'];
                // Vérifier si déjà présent
                $already = false;
                foreach ($selected as $s) {
                    if ($s['type'] === $t && $s['id'] === $cand['id']) {
                        $already = true;
                        break;
                    }
                }
                if ($already) continue;

                $currentCount = $typeCount[$t] ?? 0;
                // Pas plus de 1 événement supplémentaire si nécessaire
                if ($currentCount < 2) {
                    $selected[] = $cand;
                    $typeCount[$t] = $currentCount + 1;
                    if (count($selected) >= $limit) break;
                }
            }
        }

        return [
            'items' => $selected,
            'page' => 1,
            'limit' => count($selected),
            'total' => count($selected),
            'has_more' => true
        ];
    }

    /**
     * Formate une date en temps écoulé relatif ("Il y a 2h", "Il y a 3 jours")
     */
    private function timeElapsedString(string $datetime): string {
        $now = new \DateTime();
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        if ($diff->y > 0) return 'Il y a ' . $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
        if ($diff->m > 0) return 'Il y a ' . $diff->m . ' mois';
        if ($diff->d > 0) return 'Il y a ' . $diff->d . ' j';
        if ($diff->h > 0) return 'Il y a ' . $diff->h . ' h';
        if ($diff->i > 0) return 'Il y a ' . $diff->i . ' min';
        return 'À l\'instant';
    }
}


