<?php

namespace Forum\Entities;

defined('ROOT') or exit('Access denied!');

use Core\Plugin\PluginsManager;

class PostManager
{
    public function __construct()
    {
    }

    public function getByTopic(int $topicId, int $page = 1, int $perPage = 20, bool $includePending = false, ?int $currentUserId = null, ?string $currentUserEmail = null): array
    {
        $offset = ($page - 1) * $perPage;

        $query = Post::query()
            ->where('topic_id', '=', $topicId)
            ->orderBy('created_at', 'ASC')
            ->limit($perPage, $offset);
        
        if (!$includePending) {
            $query->where('is_approved', '=', 1);
        }

        $posts = $query->get();
        
        foreach ($posts as $post) {
            $post->like_count = PostLike::query()->where('post_id', '=', $post->id)->count();
            $post->isLikedByCurrentUser = $this->isLikedBy($post->id, $currentUserId, $currentUserEmail);
        }
        
        return $posts;
    }

    public function getById(int $id): ?Post
    {
        return Post::findPK($id);
    }

    public function create(array $data): ?Post
    {
        $plugin = PluginsManager::getInstance()->getPlugin('forum');
        $moderationEnabled = $plugin->getConfigVal('moderationEnabled');
        $forceApprove = !empty($data['force_approve']);
        unset($data['force_approve']);
        
        $post = new Post($data);
        // Auto-approve posts when moderation is disabled, the user is authenticated or approval is forced
        $post->is_approved = ($forceApprove || !$moderationEnabled || !empty($data['author_id'])) ? 1 : 0;
        
        if ($post->save()) {
            // Update topic's last_post_at
            $topicManager = new TopicManager();
            $topicManager->updateLastPost($post->topic_id);
            
            return $post;
        }
        return null;
    }

    public function approve(int $id): bool
    {
        $post = Post::findPK($id);
        if ($post) {
            $post->is_approved = 1;
            return $post->save();
        }
        return false;
    }

    public function delete(int $id): bool
    {
        return Post::deletePK($id);
    }
    
    public function countByTopic(int $topicId): int
    {
        return Post::query()
            ->where('topic_id', '=', $topicId)
            ->where('is_approved', '=', 1)
            ->count();
    }
    
    /**
     * Récupère les réponses d'un topic (exclut le premier post)
     * @param int $topicId
     * @param int $page
     * @param int $perPage
     * @param bool $includePending
     * @param int|null $currentUserId
     * @param string|null $currentUserEmail
     * @return array
     */
    public function getRepliesByTopic(int $topicId, int $page = 1, int $perPage = 20, bool $includePending = false, ?int $currentUserId = null, ?string $currentUserEmail = null): array
    {
        $offset = ($page - 1) * $perPage;
        $sqlOffset = $offset + 1;

        $query = Post::query()
            ->where('topic_id', '=', $topicId)
            ->orderBy('created_at', 'ASC')
            ->limit($perPage, $sqlOffset);
        
        if (!$includePending) {
            $query->where('is_approved', '=', 1);
        }

        $posts = $query->get();
        
        foreach ($posts as $post) {
            $post->like_count = PostLike::query()->where('post_id', '=', $post->id)->count();
            $post->isLikedByCurrentUser = $this->isLikedBy($post->id, $currentUserId, $currentUserEmail);
        }
        
        return $posts;
    }
    
    public function update(int $id, array $data): bool
    {
        $post = Post::findPK($id);
        if (!$post) return false;

        if (isset($data['content'])) {
            $post->content = $data['content'];
        }
        
        $post->updated_at = date('Y-m-d H:i:s');
        $post->edited_at = date('Y-m-d H:i:s');
        $post->edit_count = (int)$post->edit_count + 1;
        
        return $post->save();
    }
    
    public function toggleLike(int $postId, ?int $userId = null, ?string $userEmail = null): bool
    {
        $like = PostLike::query()->where('post_id', '=', $postId);
        if ($userId) {
            $like->where('user_id', '=', $userId);
        } else {
            $like->where('user_id', 'IS NULL', null)->where('user_email', '=', $userEmail);
        }
        
        $existing = $like->first();
        if ($existing) {
            return $existing->delete();
        } else {
            $newLike = new PostLike([
                'post_id' => $postId,
                'user_id' => $userId,
                'user_email' => $userEmail
            ]);
            return $newLike->save();
        }
    }

    public function countLikes(int $postId): int
    {
        return PostLike::query()->where('post_id', '=', $postId)->count();
    }

    public function isLikedBy(int $postId, ?int $userId = null, ?string $userEmail = null): bool
    {
        $query = PostLike::query()->where('post_id', '=', $postId);
        if ($userId) {
            $query->where('user_id', '=', $userId);
        } else {
            $query->where('user_id', 'IS NULL', null)->where('user_email', '=', $userEmail);
        }
        return $query->count() > 0;
    }
    
    public function search(string $query, int $limit = 50): array
    {
        $words = explode(' ', trim($query));
        
        // Comme JsonActiveRecord ne supporte pas encore les JOIN complexes ou LIKE %word%,
        // on va récupérer les posts et filtrer. Pour un gros forum, c'est pas idéal,
        // mais c'est conforme au standard multi-storage actuel.
        
        $posts = Post::query()->where('is_approved', '=', 1)->get();
        $results = [];
        
        foreach ($posts as $post) {
            $match = false;
            foreach ($words as $word) {
                if (stripos($post->content, $word) !== false) {
                    $match = true;
                    break;
                }
            }
            
            if (!$match) {
                // Essayer sur le titre du sujet
                /** @var Topic|null $topic */
                $topic = Topic::findPK($post->topic_id);
                if ($topic) {
                    foreach ($words as $word) {
                        if (stripos((string)$topic->title, $word) !== false) {
                            $match = true;
                            break;
                        }
                    }
                }
            }
            
            if ($match) {
                /** @var Topic $topic */
                $topic = Topic::findPK($post->topic_id);
                /** @var Category|null $category */
                $category = $topic ? Category::findPK($topic->category_id) : null;
                
                $results[] = [
                    'post' => $post,
                    'topic_title' => $topic ? (string)$topic->title : '',
                    'topic_slug' => $topic ? $topic->getSlug() : '',
                    'category_name' => $category ? $category->getName() : '',
                ];
                
                if (count($results) >= $limit) break;
            }
        }
        
        return $results;
    }
    
    public function canEdit(int $postId, ?int $userId = null, ?string $userEmail = null): bool
    {
        $post = $this->getById($postId);
        if (!$post) return false;
        
        if ($userId && $post->getAuthorId() === $userId) return true;
        if ($userEmail && $post->getAuthorEmail() === $userEmail) return true;
        
        return false;
    }

    public function isLastPost(int $topicId, int $postId): bool
    {
        return Post::query()
            ->where('topic_id', '=', $topicId)
            ->where('id', '>', $postId)
            ->where('is_approved', '=', 1)
            ->count() === 0;
    }

    public function getOrphanPosts(): array
    {
        // This is a bit complex for JsonActiveRecord, but let's try to get all and filter
        $posts = Post::query()->all();
        return array_filter($posts, function($post) {
            return Topic::findPK($post->topic_id) === null;
        });
    }

    public function recoverOrphans(int $targetTopicId): int
    {
        $orphans = $this->getOrphanPosts();
        foreach ($orphans as $post) {
            $post->topic_id = $targetTopicId;
            $post->save();
        }
        return count($orphans);
    }
}

