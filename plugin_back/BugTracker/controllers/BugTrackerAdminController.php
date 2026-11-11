<?php

namespace BugTracker\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use BugTracker\Services\BugTrackerService;
use BugTracker\Entities\BugReport;

defined('ROOT') or exit('Access denied!');

class BugTrackerAdminController extends AdminController
{
    private function getService(): BugTrackerService
    {
        return new BugTrackerService($this->core);
    }

    public function dashboard()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('BugTracker', 'admin_dashboard');
        
        $service = $this->getService();
        
        // Récupérer tous les bugs triés par date
        $allBugs = $service->getAllBugs();
        usort($allBugs, function($a, $b) {
            $timeA = $a->attributes['created_at'] ?? 0;
            $timeB = $b->attributes['created_at'] ?? 0;
            return $timeB <=> $timeA; // Plus récent en premier
        });
        
        // Récupérer les bugs par statut
        $pending = $service->getBugsByStatus(BugReport::STATUS_PENDING);
        $inProgress = $service->getBugsByStatus(BugReport::STATUS_IN_PROGRESS);
        $fixed = $service->getBugsByStatus(BugReport::STATUS_FIXED);
        
        // Convertir les objets en tableaux pour les templates
        $convertBugToArray = function($bug) {
            $id = $bug->attributes['id'] ?? '';
            $title = $bug->attributes['title'] ?? '';
            // Extraire la partie courte de l'ID (après "bug_")
            $shortId = strpos($id, '_') !== false ? substr($id, strpos($id, '_') + 1, 10) : substr($id, 0, 10);
            // Tronquer le titre si nécessaire
            $titleLength = mb_strlen($title);
            $titleShort = $titleLength > 50 ? mb_substr($title, 0, 50) : $title;
            $isLongTitle = $titleLength > 50;
            
            return [
                'id' => $id,
                'shortId' => $shortId,
                'title' => $title,
                'titleShort' => $titleShort,
                'isLongTitle' => $isLongTitle,
                'description' => $bug->attributes['description'] ?? '',
                'status' => $bug->attributes['status'] ?? '',
                'priority' => $bug->attributes['priority'] ?? '',
                'created_at' => $bug->attributes['created_at'] ?? 0,
                'updated_at' => $bug->attributes['updated_at'] ?? 0,
                'reporter_id' => $bug->attributes['reporter_id'] ?? null,
                'reporter_name' => $bug->attributes['reporter_name'] ?? '',
                'reporter_email' => $bug->attributes['reporter_email'] ?? '',
                'hasReporterId' => !empty($bug->attributes['reporter_id']),
                'statusLabel' => $bug->getStatusLabel(),
                'statusClass' => $bug->getStatusClass(),
                'priorityLabel' => $bug->getPriorityLabel(),
                'priorityClass' => $bug->getPriorityClass(),
            ];
        };
        
        $allBugsArray = array_map($convertBugToArray, $allBugs);
        $pendingArray = array_map($convertBugToArray, $pending);
        $inProgressArray = array_map($convertBugToArray, $inProgress);
        $fixedArray = array_map($convertBugToArray, $fixed);
        
        $tpl->set('allBugs', $allBugsArray);
        $tpl->set('pending', $pendingArray);
        $tpl->set('inProgress', $inProgressArray);
        $tpl->set('fixed', $fixedArray);
        $tpl->set('stats', $service->getStats());
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        $response->setTitle('Gestion des bugs');
        return $response;
    }

    public function view(string $id)
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('BugTracker', 'admin_view');
        
        $service = $this->getService();
        $bug = $service->getBug($id);
        
        if (!$bug) {
            \Utils\Show::msg('Bug introuvable.', 'error');
            $this->core->redirect($this->router->generate('admin-BugTracker'));
            return null;
        }
        
        // Convertir le bug en tableau pour le template
        // Traiter les champs texte avec nl2br pour convertir les retours à la ligne
        $url = $bug->attributes['url'] ?? '';
        $status = $bug->attributes['status'] ?? '';
        $priority = $bug->attributes['priority'] ?? '';
        $images = $bug->attributes['images'] ?? [];
        $comments = $bug->attributes['comments'] ?? [];
        $bugArray = [
            'id' => $bug->attributes['id'] ?? '',
            'title' => $bug->attributes['title'] ?? '',
            'description' => nl2br(htmlspecialchars($bug->attributes['description'] ?? '')),
            'steps' => !empty($bug->attributes['steps']) ? nl2br(htmlspecialchars($bug->attributes['steps'])) : '',
            'expected' => !empty($bug->attributes['expected']) ? nl2br(htmlspecialchars($bug->attributes['expected'])) : '',
            'actual' => !empty($bug->attributes['actual']) ? nl2br(htmlspecialchars($bug->attributes['actual'])) : '',
            'browser' => $bug->attributes['browser'] ?? '',
            'os' => $bug->attributes['os'] ?? '',
            'url' => $url,
            'hasUrl' => !empty($url),
            'images' => $images,
            'hasImages' => !empty($images),
            'imagesCount' => count($images),
            'status' => $status,
            'isStatusPending' => $status === BugReport::STATUS_PENDING,
            'isStatusInProgress' => $status === BugReport::STATUS_IN_PROGRESS,
            'isStatusFixed' => $status === BugReport::STATUS_FIXED,
            'isStatusRejected' => $status === BugReport::STATUS_REJECTED,
            'priority' => $priority,
            'isPriorityLow' => $priority === BugReport::PRIORITY_LOW,
            'isPriorityMedium' => $priority === BugReport::PRIORITY_MEDIUM,
            'isPriorityHigh' => $priority === BugReport::PRIORITY_HIGH,
            'isPriorityCritical' => $priority === BugReport::PRIORITY_CRITICAL,
            'reporter_id' => $bug->attributes['reporter_id'] ?? null,
            'reporter_name' => $bug->attributes['reporter_name'] ?? '',
            'reporter_email' => $bug->attributes['reporter_email'] ?? '',
            'hasReporterId' => !empty($bug->attributes['reporter_id']),
            'comments' => $this->formatComments($comments),
            'hasComments' => !empty($comments),
            'commentsCount' => count($comments),
            'created_at' => $bug->attributes['created_at'] ?? 0,
            'updated_at' => $bug->attributes['updated_at'] ?? 0,
            'statusLabel' => $bug->getStatusLabel(),
            'statusClass' => $bug->getStatusClass(),
            'priorityLabel' => $bug->getPriorityLabel(),
            'priorityClass' => $bug->getPriorityClass(),
        ];
        
        $tpl->set('bug', $bugArray);
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        $response->setTitle('Bug #' . $bug->attributes['id']);
        return $response;
    }

    public function updateStatus()
    {
        $this->core->getLogger()->debug('BugTrackerAdminController::updateStatus - START');
        $this->core->getLogger()->debug('BugTrackerAdminController::updateStatus - POST data: ' . json_encode($_POST));
        
        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->core->getLogger()->error('BugTrackerAdminController::updateStatus - CSRF token invalid');
            \Utils\Show::msg('Token de sécurité invalide.', 'error');
            header('location:' . $this->router->generate('admin-BugTracker'));
            die();
        }

        $service = $this->getService();
        $id = $_POST['id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        $this->core->getLogger()->debug("BugTrackerAdminController::updateStatus - id={$id}, status={$status}");
        
        if (empty($id) || empty($status)) {
            $this->core->getLogger()->error("BugTrackerAdminController::updateStatus - Invalid parameters: id={$id}, status={$status}");
            \Utils\Show::msg('Paramètres invalides.', 'error');
            header('location:' . $this->router->generate('admin-BugTracker'));
            die();
        }

        $adminId = $this->user->attributes['id'] ?? null;
        $adminName = $this->user->attributes['username'] ?? $this->user->attributes['email'] ?? 'Admin';
        
        $this->core->getLogger()->debug("BugTrackerAdminController::updateStatus - Calling service->updateStatus({$id}, {$status}, {$adminId})");
        
        if ($service->updateStatus($id, $status, $adminId)) {
            $this->core->getLogger()->debug("BugTrackerAdminController::updateStatus - updateStatus returned true");
            // Ajouter un commentaire automatique
            $statusLabels = [
                BugReport::STATUS_PENDING => 'En attente',
                BugReport::STATUS_IN_PROGRESS => 'Pris en charge',
                BugReport::STATUS_FIXED => 'Corrigé',
                BugReport::STATUS_REJECTED => 'Rejeté',
            ];
            $label = $statusLabels[$status] ?? $status;
            $this->core->getLogger()->debug("BugTrackerAdminController::updateStatus - Adding comment: Statut changé en : {$label}");
            $service->addComment($id, "Statut changé en : {$label}", $adminName, $adminId, true);
            
            \Utils\Show::msg('Statut mis à jour avec succès.', 'success');
        } else {
            $this->core->getLogger()->error("BugTrackerAdminController::updateStatus - updateStatus returned false");
            \Utils\Show::msg('Erreur lors de la mise à jour du statut.', 'error');
        }
        
        $redirectUrl = $this->router->generate('admin-BugTracker-view', ['id' => $id]);
        $this->core->getLogger()->debug("BugTrackerAdminController::updateStatus - Redirecting to: {$redirectUrl}");
        header('location:' . $redirectUrl);
        die();
    }

    public function addComment()
    {
        $this->core->getLogger()->debug('BugTrackerAdminController::addComment - START');
        $this->core->getLogger()->debug('BugTrackerAdminController::addComment - POST data: ' . json_encode($_POST));
        
        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->core->getLogger()->error('BugTrackerAdminController::addComment - CSRF token invalid');
            \Utils\Show::msg('Token de sécurité invalide.', 'error');
            header('location:' . $this->router->generate('admin-BugTracker'));
            die();
        }

        $service = $this->getService();
        $id = $_POST['id'] ?? '';
        $comment = trim($_POST['comment'] ?? '');
        
        $this->core->getLogger()->debug("BugTrackerAdminController::addComment - id={$id}, comment length=" . mb_strlen($comment));
        
        if (empty($id) || empty($comment)) {
            $this->core->getLogger()->error("BugTrackerAdminController::addComment - Invalid parameters: id={$id}, comment empty=" . (empty($comment) ? 'true' : 'false'));
            \Utils\Show::msg('Le commentaire ne peut pas être vide.', 'error');
            header('location:' . $this->router->generate('admin-BugTracker-view', ['id' => $id]));
            die();
        }

        $adminId = $this->user->attributes['id'] ?? null;
        $adminName = $this->user->attributes['username'] ?? $this->user->attributes['email'] ?? 'Admin';
        
        $this->core->getLogger()->debug("BugTrackerAdminController::addComment - Calling service->addComment({$id}, comment, {$adminName}, {$adminId}, true)");
        
        if ($service->addComment($id, $comment, $adminName, $adminId, true)) {
            $this->core->getLogger()->debug("BugTrackerAdminController::addComment - addComment returned true");
            \Utils\Show::msg('Commentaire ajouté avec succès.', 'success');
        } else {
            $this->core->getLogger()->error("BugTrackerAdminController::addComment - addComment returned false");
            \Utils\Show::msg('Erreur lors de l\'ajout du commentaire.', 'error');
        }
        
        $redirectUrl = $this->router->generate('admin-BugTracker-view', ['id' => $id]);
        $this->core->getLogger()->debug("BugTrackerAdminController::addComment - Redirecting to: {$redirectUrl}");
        header('location:' . $redirectUrl);
        die();
    }

    public function updatePriority()
    {
        $this->core->getLogger()->debug('BugTrackerAdminController::updatePriority - START');
        $this->core->getLogger()->debug('BugTrackerAdminController::updatePriority - POST data: ' . json_encode($_POST));
        
        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->core->getLogger()->error('BugTrackerAdminController::updatePriority - CSRF token invalid');
            \Utils\Show::msg('Token de sécurité invalide.', 'error');
            header('location:' . $this->router->generate('admin-BugTracker'));
            die();
        }

        $service = $this->getService();
        $id = $_POST['id'] ?? '';
        $priority = $_POST['priority'] ?? '';
        
        $this->core->getLogger()->debug("BugTrackerAdminController::updatePriority - id={$id}, priority={$priority}");
        
        if (empty($id) || empty($priority)) {
            $this->core->getLogger()->error("BugTrackerAdminController::updatePriority - Invalid parameters: id={$id}, priority={$priority}");
            \Utils\Show::msg('Paramètres invalides.', 'error');
            header('location:' . $this->router->generate('admin-BugTracker'));
            die();
        }

        $adminId = $this->user->attributes['id'] ?? null;
        $adminName = $this->user->attributes['username'] ?? $this->user->attributes['email'] ?? 'Admin';
        
        $this->core->getLogger()->debug("BugTrackerAdminController::updatePriority - Calling service->updatePriority({$id}, {$priority}, {$adminId})");
        
        if ($service->updatePriority($id, $priority, $adminId)) {
            $this->core->getLogger()->debug("BugTrackerAdminController::updatePriority - updatePriority returned true");
            // Ajouter un commentaire automatique
            $priorityLabels = [
                BugReport::PRIORITY_LOW => 'Basse',
                BugReport::PRIORITY_MEDIUM => 'Moyenne',
                BugReport::PRIORITY_HIGH => 'Haute',
                BugReport::PRIORITY_CRITICAL => 'Critique',
            ];
            $label = $priorityLabels[$priority] ?? $priority;
            $this->core->getLogger()->debug("BugTrackerAdminController::updatePriority - Adding comment: Priorité changée en : {$label}");
            $service->addComment($id, "Priorité changée en : {$label}", $adminName, $adminId, true);
            
            \Utils\Show::msg('Priorité mise à jour avec succès.', 'success');
        } else {
            $this->core->getLogger()->error("BugTrackerAdminController::updatePriority - updatePriority returned false");
            \Utils\Show::msg('Erreur lors de la mise à jour de la priorité.', 'error');
        }
        
        $redirectUrl = $this->router->generate('admin-BugTracker-view', ['id' => $id]);
        $this->core->getLogger()->debug("BugTrackerAdminController::updatePriority - Redirecting to: {$redirectUrl}");
        header('location:' . $redirectUrl);
        die();
    }

    public function delete()
    {
        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null)) {
            \Utils\Show::msg('Token de sécurité invalide.', 'error');
            header('location:' . $this->router->generate('admin-BugTracker'));
            die();
        }

        $service = $this->getService();
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            \Utils\Show::msg('Bug introuvable.', 'error');
            header('location:' . $this->router->generate('admin-BugTracker'));
            die();
        }

        if ($service->deleteBug($id)) {
            \Utils\Show::msg('Bug supprimé avec succès.', 'success');
        } else {
            \Utils\Show::msg('Erreur lors de la suppression.', 'error');
        }
        
        header('location:' . $this->router->generate('admin-BugTracker'));
        die();
    }

    /**
     * Formate les commentaires avec nl2br
     * 
     * @param array $comments
     * @return array
     */
    private function formatComments(array $comments): array {
        $formatted = [];
        foreach ($comments as $comment) {
            $formatted[] = [
                'author_name' => $comment['author_name'] ?? '',
                'comment' => nl2br(htmlspecialchars($comment['comment'] ?? '')),
                'created_at' => $comment['created_at'] ?? 0,
                'is_admin' => $comment['is_admin'] ?? false,
            ];
        }
        return $formatted;
    }
}

