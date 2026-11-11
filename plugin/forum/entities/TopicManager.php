<?php

namespace Forum\Entities;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

class TopicManager
{

    public function __construct()
    {
    }

    public function getByCategory(int $categoryId, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        
        $topics = Topic::query()
            ->where('category_id', '=', $categoryId)
            ->orderBy('is_pinned', 'DESC')
            ->orderBy('last_post_at', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->limit($perPage, $offset)
            ->get();

        foreach ($topics as $topic) {
            $topic->post_count = Post::query()
                ->where('topic_id', '=', $topic->id)
                ->where('is_approved', '=', 1)
                ->count();
        }
        return $topics;
    }

    public function getLast(int $limit = 5): array
    {
        $topics = Topic::query()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        foreach ($topics as $topic) {
            $topic->post_count = Post::query()
                ->where('topic_id', '=', $topic->id)
                ->where('is_approved', '=', 1)
                ->count();
        }
        return $topics;
    }

    public function getById(int $id): ?Topic
    {
        $topic = Topic::findPK($id);
        if ($topic) {
            $topic->post_count = Post::query()
                ->where('topic_id', '=', $topic->id)
                ->where('is_approved', '=', 1)
                ->count();
        }
        return $topic;
    }

    public function incrementViews(int $id): void
    {
        $topic = Topic::findPK($id);
        if ($topic) {
            $topic->views = (int)$topic->views + 1;
            $topic->save();
        }
    }

    public function create(array $data): ?Topic
    {
        if (empty($data['slug'])) {
            $data['slug'] = Util::strToUrl($data['title']);
        }
        
        $topic = new Topic($data);
        if ($topic->save()) {
            return $this->getById($topic->id);
        }
        return null;
    }

    public function updateLastPost(int $id): void
    {
        $topic = Topic::findPK($id);
        if ($topic) {
            $topic->last_post_at = date('Y-m-d H:i:s');
            $topic->save();
        }
    }
    
    public function toggleLock(int $id): bool
    {
        /** @var Topic $topic */
        $topic = Topic::findPK($id);
        if (!$topic) return false;
        $topic->is_locked = $topic->isLocked() ? 0 : 1;
        return $topic->save();
    }
    
    public function togglePin(int $id): bool
    {
        /** @var Topic $topic */
        $topic = Topic::findPK($id);
        if (!$topic) return false;
        $topic->is_pinned = $topic->isPinned() ? 0 : 1;
        return $topic->save();
    }

    public function delete(int $id): bool
    {
        return Topic::deletePK($id);
    }
    
    public function countAll(): int
    {
        return Topic::query()->count();
    }

    public function countByCategory(int $categoryId): int
    {
        return Topic::query()->where('category_id', '=', $categoryId)->count();
    }
    
    public function countByTopic(int $topicId): int
    {
        return Post::query()->where('topic_id', '=', $topicId)->where('is_approved', '=', 1)->count();
    }

    public function getOrphanTopics(): array
    {
        $allCategoryIds = array_map(fn($c) => $c->id, Category::query()->get());
        
        $allTopics = Topic::query()->get();
        $orphans = [];
        foreach ($allTopics as $topic) {
            if (!in_array($topic->category_id, $allCategoryIds)) {
                $orphans[] = $topic;
            }
        }
        return $orphans;
    }

    public function recoverOrphans(int $targetCategoryId): int
    {
        $orphans = $this->getOrphanTopics();
        $count = 0;
        foreach ($orphans as $orphan) {
            $orphan->category_id = $targetCategoryId;
            if ($orphan->save()) {
                $count++;
            }
        }
        return $count;
    }
}

