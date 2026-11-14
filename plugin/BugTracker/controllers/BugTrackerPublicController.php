<?php

namespace BugTracker\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use BugTracker\Services\BugTrackerService;
use BugTracker\Entities\BugReport;

defined('ROOT') or exit('Access denied!');

class BugTrackerPublicController extends PublicController
{
    private function getService(): BugTrackerService
    {
        return new BugTrackerService($this->core);
    }

    public function home()
    {
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('BugTracker', 'home');
        
        $service = $this->getService();
        $stats = $service->getStats();
        
        $tpl->set('stats', $stats);
        $response->addTemplate($tpl);
        $response->setTitle('Rapport de bug');
        return $response;
    }

    public function report()
    {
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('BugTracker', 'report');
        
        $response->addTemplate($tpl);
        $response->setTitle('Signaler un bug');
        return $response;
    }

    public function submit()
    {
        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null)) {
            \Utils\Show::msg('Token de sécurité invalide.', 'error');
            $this->core->redirect($this->router->generate('BugTracker-report'));
            return null;
        }

        $service = $this->getService();
        
        // Traitement des images
        $images = [];
        if (isset($_FILES['image1']) && $_FILES['image1']['error'] === UPLOAD_ERR_OK) {
            $image1 = $service->saveImage($_FILES['image1']);
            if ($image1) {
                $images[] = $image1;
            }
        }
        if (isset($_FILES['image2']) && $_FILES['image2']['error'] === UPLOAD_ERR_OK) {
            $image2 = $service->saveImage($_FILES['image2']);
            if ($image2) {
                $images[] = $image2;
            }
        }

        // Récupérer les informations du navigateur
        $browser = $_POST['browser'] ?? '';
        $os = $_POST['os'] ?? '';
        $url = trim($_POST['url'] ?? '');

        // Récupérer les informations de l'utilisateur connecté si disponible
        $user = $this->user;
        $reporterId = null;
        $reporterName = '';
        $reporterEmail = '';
        
        if ($user && \Core\Navigation\AccessPolicyEvaluator::getInstance()->canAccess(new \Core\Navigation\GenericResource('bugtracker:report'), new \Core\Navigation\ConditionContext($user))) {
            // Utilisateur autorisé : utiliser ses informations
            $reporterId = $user->attributes['id'] ?? null;
            $reporterName = $user->attributes['username'] ?? $user->attributes['email'] ?? '';
            $reporterEmail = $user->attributes['email'] ?? '';
        } else {
            // Utilisateur non connecté : utiliser les champs du formulaire
            $reporterName = trim($_POST['reporter_name'] ?? '');
            $reporterEmail = trim($_POST['reporter_email'] ?? '');
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'steps' => trim($_POST['steps'] ?? ''),
            'expected' => '', // Sera défini par l'admin après analyse
            'actual' => trim($_POST['actual'] ?? ''),
            'browser' => $browser,
            'os' => $os,
            'url' => $url,
            'images' => $images,
            'priority' => BugReport::PRIORITY_MEDIUM, // Priorité par défaut, sera ajustée par l'admin
            'reporter_id' => $reporterId,
            'reporter_name' => $reporterName,
            'reporter_email' => $reporterEmail,
        ];

        // Validation
        if (empty($data['title']) || empty($data['description'])) {
            \Utils\Show::msg('Le titre et la description sont obligatoires.', 'error');
            $this->core->redirect($this->router->generate('BugTracker-report'));
            return null;
        }

        $bug = $service->createBug($data);
        
        \Utils\Show::msg('Votre rapport de bug a été envoyé avec succès. Merci !', 'success');
        $this->core->redirect($this->router->generate('BugTracker-view', ['id' => $bug->attributes['id']]));
        return null;
    }

    public function list()
    {
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('BugTracker', 'list');
        
        $service = $this->getService();
        
        // Récupérer les bugs par statut
        $pending = $service->getBugsByStatus(BugReport::STATUS_PENDING);
        $inProgress = $service->getBugsByStatus(BugReport::STATUS_IN_PROGRESS);
        $fixed = $service->getBugsByStatus(BugReport::STATUS_FIXED);
        
        // Convertir les objets en tableaux pour les templates
        $convertBugToArray = function($bug) {
            $description = $bug->attributes['description'] ?? '';
            $descriptionLength = mb_strlen($description);
            // Tronquer la description pour l'affichage dans la liste
            $descriptionShort = $descriptionLength > 200 ? mb_substr($description, 0, 200) : $description;
            return [
                'id' => $bug->attributes['id'] ?? '',
                'title' => $bug->attributes['title'] ?? '',
                'description' => $description,
                'descriptionShort' => $descriptionShort,
                'descriptionLength' => $descriptionLength,
                'isLongDescription' => $descriptionLength > 200,
                'status' => $bug->attributes['status'] ?? '',
                'priority' => $bug->attributes['priority'] ?? '',
                'created_at' => $bug->attributes['created_at'] ?? 0,
                'reporter_name' => $bug->attributes['reporter_name'] ?? '',
                'statusLabel' => $bug->getStatusLabel(),
                'statusClass' => $bug->getStatusClass(),
                'priorityLabel' => $bug->getPriorityLabel(),
                'priorityClass' => $bug->getPriorityClass(),
            ];
        };
        
        $pendingArray = array_map($convertBugToArray, $pending);
        $inProgressArray = array_map($convertBugToArray, $inProgress);
        $fixedArray = array_map($convertBugToArray, $fixed);
        
        $tpl->set('pending', $pendingArray);
        $tpl->set('inProgress', $inProgressArray);
        $tpl->set('fixed', $fixedArray);
        $tpl->set('pendingCount', count($pendingArray));
        $tpl->set('inProgressCount', count($inProgressArray));
        $tpl->set('fixedCount', count($fixedArray));
        $tpl->set('stats', $service->getStats());
        
        $response->addTemplate($tpl);
        $response->setTitle('Liste des bugs');
        return $response;
    }

    public function view(string $id)
    {
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('BugTracker', 'view');
        
        $service = $this->getService();
        $bug = $service->getBug($id);
        
        if (!$bug) {
            \Utils\Show::msg('Bug introuvable.', 'error');
            $this->core->redirect($this->router->generate('BugTracker-list'));
            return null;
        }
        
        // Convertir le bug en tableau pour le template
        // Traiter les champs texte avec nl2br pour convertir les retours à la ligne
        $url = $bug->attributes['url'] ?? '';
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
            'images' => $bug->attributes['images'] ?? [],
            'status' => $bug->attributes['status'] ?? '',
            'priority' => $bug->attributes['priority'] ?? '',
            'reporter_name' => $bug->attributes['reporter_name'] ?? '',
            'reporter_email' => $bug->attributes['reporter_email'] ?? '',
            'comments' => $this->formatComments($bug->attributes['comments'] ?? []),
            'created_at' => $bug->attributes['created_at'] ?? 0,
            'updated_at' => $bug->attributes['updated_at'] ?? 0,
            'statusLabel' => $bug->getStatusLabel(),
            'statusClass' => $bug->getStatusClass(),
            'priorityLabel' => $bug->getPriorityLabel(),
            'priorityClass' => $bug->getPriorityClass(),
        ];
        
        $tpl->set('bug', $bugArray);
        $response->addTemplate($tpl);
        $response->setTitle('Bug #' . $bug->attributes['id']);
        return $response;
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

