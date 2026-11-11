<?php

namespace BugTracker\Services;

use BugTracker\Entities\BugReport;
use Core\Core;

defined('ROOT') or exit('Access denied!');

class BugTrackerService
{
    private ?Core $core;

    public function __construct(?Core $core = null)
    {
        $this->core = $core ?? (class_exists('\Core\Core') ? \Core\Core::getInstance() : null);
        $this->ensureDataDir();
    }

    /**
     * Crée le répertoire de données si nécessaire
     */
    private function ensureDataDir(): void
    {
        $dataDir = DATA_PLUGIN . 'BugTracker';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
    }

    /**
     * Crée un nouveau rapport de bug
     */
    public function createBug(array $data): BugReport
    {
        $bug = new BugReport();
        $bug->attributes = [
            'id' => $this->generateId(),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'steps' => $data['steps'] ?? '',
            'expected' => $data['expected'] ?? '',
            'actual' => $data['actual'] ?? '',
            'browser' => $data['browser'] ?? '',
            'os' => $data['os'] ?? '',
            'url' => $data['url'] ?? '',
            'images' => $data['images'] ?? [],
            'status' => BugReport::STATUS_PENDING,
            'priority' => $data['priority'] ?? BugReport::PRIORITY_MEDIUM,
            'reporter_id' => $data['reporter_id'] ?? null,
            'reporter_name' => $data['reporter_name'] ?? '',
            'reporter_email' => $data['reporter_email'] ?? '',
            'comments' => [],
            'created_at' => time(),
            'updated_at' => time(),
        ];
        $bug->save();
        return $bug;
    }

    /**
     * Récupère un bug par son ID
     */
    public function getBug(string $id): ?BugReport
    {
        return BugReport::findPK($id);
    }

    /**
     * Récupère tous les bugs
     */
    public function getAllBugs(): array
    {
        return BugReport::all();
    }

    /**
     * Récupère les bugs par statut
     */
    public function getBugsByStatus(string $status): array
    {
        return BugReport::queryBuilder()
            ->where('status', '=', $status)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Met à jour le statut d'un bug
     */
    public function updateStatus(string $id, string $status, ?string $adminId = null): bool
    {
        $logger = $this->core ? $this->core->getLogger() : null;
        if ($logger) {
            $logger->debug("BugTrackerService::updateStatus - START: id={$id}, status={$status}, adminId={$adminId}");
        }
        
        $bug = $this->getBug($id);
        if (!$bug) {
            if ($logger) {
                $logger->error("BugTrackerService::updateStatus - Bug not found: id={$id}");
            }
            return false;
        }

        $bug->attributes['status'] = $status;
        $bug->attributes['updated_at'] = time();
        if ($adminId) {
            $bug->attributes['assigned_to'] = $adminId;
        }
        
        $result = $bug->save();
        if ($logger) {
            $logger->debug("BugTrackerService::updateStatus - save() returned: " . ($result ? 'true' : 'false'));
            if ($result) {
                $logger->debug("BugTrackerService::updateStatus - Bug attributes after save: " . json_encode($bug->attributes));
            }
        }
        
        return $result;
    }

    /**
     * Met à jour la priorité d'un bug
     */
    public function updatePriority(string $id, string $priority, ?string $adminId = null): bool
    {
        $bug = $this->getBug($id);
        if (!$bug) {
            return false;
        }

        $bug->attributes['priority'] = $priority;
        $bug->attributes['updated_at'] = time();
        if ($adminId) {
            $bug->attributes['assigned_to'] = $adminId;
        }
        return $bug->save();
    }

    /**
     * Ajoute un commentaire à un bug
     */
    public function addComment(string $id, string $comment, string $authorName, ?string $authorId = null, bool $isAdmin = false): bool
    {
        $bug = $this->getBug($id);
        if (!$bug) {
            return false;
        }

        $comments = $bug->attributes['comments'] ?? [];
        $comments[] = [
            'id' => uniqid('comment_', true),
            'author_name' => $authorName,
            'author_id' => $authorId,
            'is_admin' => $isAdmin,
            'comment' => $comment,
            'created_at' => time(),
        ];

        $bug->attributes['comments'] = $comments;
        $bug->attributes['updated_at'] = time();
        return $bug->save();
    }

    /**
     * Supprime un bug
     */
    public function deleteBug(string $id): bool
    {
        $bug = $this->getBug($id);
        if (!$bug) {
            return false;
        }

        // Supprimer les images associées
        $images = $bug->attributes['images'] ?? [];
        foreach ($images as $image) {
            if (isset($image['path']) && file_exists($image['path'])) {
                @unlink($image['path']);
            }
        }

        return $bug->delete();
    }

    /**
     * Génère un ID unique pour un nouveau bug
     */
    private function generateId(): string
    {
        return 'bug_' . time() . '_' . uniqid();
    }

    /**
     * Récupère les statistiques des bugs
     */
    public function getStats(): array
    {
        $allBugs = $this->getAllBugs();
        return [
            'total' => count($allBugs),
            'pending' => count($this->getBugsByStatus(BugReport::STATUS_PENDING)),
            'in_progress' => count($this->getBugsByStatus(BugReport::STATUS_IN_PROGRESS)),
            'fixed' => count($this->getBugsByStatus(BugReport::STATUS_FIXED)),
            'rejected' => count($this->getBugsByStatus(BugReport::STATUS_REJECTED)),
        ];
    }

    /**
     * Sauvegarde une image uploadée
     */
    public function saveImage(array $file): ?array
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'] ?? '', $allowedTypes)) {
            return null;
        }

        $maxSize = 5 * 1024 * 1024; // 5 MB
        if ($file['size'] > $maxSize) {
            return null;
        }

        $uploadDir = DATA_PLUGIN . 'BugTracker' . DS . 'images';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('bug_', true) . '.' . $extension;
        $filepath = $uploadDir . DS . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'path' => $filepath,
                'url' => 'data/plugin/BugTracker/images/' . $filename,
                'name' => $file['name'],
                'size' => $file['size'],
            ];
        }

        return null;
    }
}

