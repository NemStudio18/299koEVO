<?php

namespace Claim\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Claim\Services\ClaimService;
use PDO;

defined('ROOT') or exit('Access denied!');

class ClaimPublicController extends PublicController
{
    /**
     * @var PDO|null
     */
    private $pdo = null;

    /**
     * Get PDO connection from Core
     * @return PDO
     */
    private function getPdo(): PDO
    {
        if (!$this->pdo) {
            try {
                // Utiliser la connexion du Core
                $this->pdo = \Core\Database::getInstance()->getPdo();
            } catch (\PDOException $e) {
                throw new \Exception('Erreur de connexion à la base de données: ' . $e->getMessage());
            }
        }
        return $this->pdo;
    }

    private function getClaimService(): ClaimService
    {
        // S'assurer que le service est chargé
        if (!class_exists('\Claim\Services\ClaimService')) {
            $serviceFile = PLUGINS . 'claim/services/ClaimService.php';
            if (file_exists($serviceFile)) {
                require_once $serviceFile;
            }
        }
        return new \Claim\Services\ClaimService($this->getPdo());
    }

    public function home()
    {
        return $this->start(0);
    }


    private function ensureUploadDir(): string
    {
        $dir = DATA_PLUGIN . 'claim' . DS . 'uploads' . DS;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function handleUpload(): ?string
    {
        if (!isset($_FILES['proof']) || !is_array($_FILES['proof'])) {
            return null;
        }
        if (!isset($_FILES['proof']['tmp_name']) || !is_uploaded_file($_FILES['proof']['tmp_name'])) {
            return null;
        }
        $dir = $this->ensureUploadDir();
        $ext = pathinfo($_FILES['proof']['name'] ?? '', PATHINFO_EXTENSION);
        $name = uniqid('proof_', true) . ($ext ? '.' . $ext : '');
        $dest = $dir . $name;
        if (@move_uploaded_file($_FILES['proof']['tmp_name'], $dest)) {
            return $dest;
        }
        return null;
    }

    private function fetchPoiName(int $poiId, string $fallback = ''): string
    {
        $service = $this->getClaimService();
        $poi = $service->findPoi($poiId);
        if ($poi && !empty($poi->name)) {
            return trim($poi->name);
        }
        return $fallback;
    }

    public function start($id)
    {
        // Robust ID extraction for legacy or complex paths
        if (is_string($id) && strpos($id, '/') !== false) {
            $parts = explode('/', trim($id, '/'));
            $id = end($parts);
        }
        $id = (int)$id;
        // Si non connecté, rediriger vers l'inscription
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('claim:access'), $ctx)) {
            $this->core->redirect($this->router->generate('login'));
            return null;
        }
        
        $service = $this->getClaimService();
        $poi = $service->findPoi($id);
        
        if (!$poi) {
            \Utils\Show::msg('Fiche introuvable.', 'error');
            $this->core->redirect($this->router->generate('home'));
            return null;
        }
        
        // Vérifier si déjà revendiqué par cet utilisateur
        // SOURCE DE VÉRITÉ : Table SQL `poi_claims` uniquement (pas le statut du POI)
        $cmsUserId = (int)($this->user->attributes['id'] ?? 0);
        $existingClaim = null;
        if ($cmsUserId > 0 && $poi && isset($poi->id)) {
            $poiId = (int)$poi->id;
            if ($poiId > 0) {
                try {
                    // Récupérer l'ID MySQL de l'utilisateur (peut être différent de l'ID CMS)
                    $userEmail = $this->user->attributes['email'] ?? null;
                    $username = $this->user->attributes['username'] ?? null;
                    $mysqlUserId = null;
                    if ($userEmail || $username) {
                        $mysqlUserId = $service->getMySQLUserId($userEmail ?? '', $username ?? '');
                    }
                    
                    // Si l'utilisateur n'existe pas dans MySQL, pas de claim possible
                    if ($mysqlUserId !== null) {
                        // Vérifier directement dans poi_claims (source de vérité unique)
                        $claim = $service->getClaimByPoiAndUser($poiId, $mysqlUserId);
                        // Vérification très stricte : le claim doit exister ET avoir un ID valide
                        if ($claim !== null && isset($claim->id) && !empty($claim->id)) {
                            $claimId = (int)$claim->id;
                            if ($claimId > 0) {
                                // Convert to array for template ease if needed, or stick to object
                                // Using array for existingClaim to handle additional props like formatted date
                                $existingClaim = $claim->attributes; 
                                $existingClaim['id'] = $claim->id; // Ensure id is present
                                $existingClaim['claim_status'] = $claim->status ?? 'pending';
                                // Formater les dates pour l'affichage
                                if (!empty($claim->requested_at)) {
                                    $existingClaim['requested_at_formatted'] = date('d/m/Y à H:i', strtotime($claim->requested_at));
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // En cas d'erreur, ne pas définir existingClaim
                    $this->logger->log('WARNING', 'Error checking existing claim: ' . json_encode([
                        'poi_id' => $poiId,
                        'cms_user_id' => $cmsUserId,
                        'error' => $e->getMessage(),
                    ]));
                }
            }
        }
        
        // Mapper les colonnes poi_public vers les noms attendus par le template
        // Since $poi is object, we can't easily add props unless we convert to array or use separate vars.
        // Let's convert $poi to array for the template if the template expects arrays.
        $poiArray = $poi->attributes;
        $poiArray['id'] = $poi->id;
        $poiArray['name'] = $poi->name;
        $poiArray['street'] = $poi->addr_street ?? null;
        $poiArray['housenumber'] = $poi->addr_housenumber ?? null;
        $poiArray['postcode'] = $poi->addr_postcode ?? null;
        $poiArray['city'] = $poi->addr_city ?? null;
        $poiArray['subcategory'] = $poi->subcategory ?? null;

        
        $response = new PublicResponse();
        $poiName = $poi->name ?? 'POI #' . $id;
        $response->setTitle('Revendiquer : ' . $poiName);
        $tpl = $response->createPluginTemplate('claim', 'start');
        $tpl->set('poi_id', $id);
        $tpl->set('id', $id);
        $poiType = !empty($poi->subcategory) ? $poi->subcategory : 'poi';
        $tpl->set('type', $poiType);
        $tpl->set('poi', $poi);
        // S'assurer que existingClaim est null si aucun claim n'existe (pas un tableau vide)
        // Si existingClaim n'a pas d'ID valide, le mettre à null explicitement
        if ($existingClaim !== null && (!isset($existingClaim['id']) || empty($existingClaim['id']) || (int)$existingClaim['id'] <= 0)) {
            $existingClaim = null;
        }
        $tpl->set('existingClaim', $existingClaim);
        // S'assurer que isLogged est bien défini via la politique
        $tpl->set('isLogged', $evaluator->canAccess(new \Core\Navigation\GenericResource('claim:access'), $ctx));
        // Variable booléenne explicite pour le template (évite les problèmes avec "not" dans le moteur de template)
        $tpl->set('hasExistingClaim', $existingClaim !== null && isset($existingClaim['id']) && !empty($existingClaim['id']));
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function myClaims()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('claim:access'), $ctx)) {
            $this->core->redirect($this->router->generate('login'));
            return null;
        }
        
        $userId = (int)($this->user->attributes['id'] ?? 0);
        if ($userId <= 0) {
            $this->core->error404();
            return null;
        }
        
        $service = $this->getClaimService();
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        $claims = $service->getUserClaims($userId, $userEmail, $username);
        
        // Charger la configuration du plugin search pour générer les URLs courtes
        $searchConfigFile = DATA_PLUGIN . 'search' . DS . 'search_conf.json';
        $searchCfg = \Utils\Util::readJsonFile($searchConfigFile, true) ?: [];
        
        // Charger SqlSearchService pour générer les URLs courtes
        $sqlSearchService = null;
        if (class_exists('\SearchEngine\Services\Search\SearchService')) {
            $sqlSearchService = new \SearchEngine\Services\Search\SearchService();
        }
        
        // Formater les dates et générer les URLs courtes pour l'affichage
        foreach ($claims as &$claim) {
            if (!empty($claim['requested_at'])) {
                $claim['requested_at_formatted'] = date('d/m/Y à H:i', strtotime($claim['requested_at']));
            }
            if (!empty($claim['approved_at'])) {
                $claim['approved_at_formatted'] = date('d/m/Y à H:i', strtotime($claim['approved_at']));
            }
            
            // Générer l'URL courte avec le nom du POI
            if (!empty($claim['poi_name']) && $sqlSearchService) {
                $poiId = (int)($claim['poi_id'] ?? 0);
                $poiName = trim($claim['poi_name']);
                if ($poiId > 0 && !empty($poiName)) {
                    $urlName = $sqlSearchService->generateUrlName($poiName, $poiId);
                    $claim['detail_url_short'] = '/' . $urlName;
                } else {
                    $claim['detail_url_short'] = null;
                }
            } else {
                $claim['detail_url_short'] = null;
            }
        }
        unset($claim);
        
        $response = new PublicResponse();
        $response->setTitle('Mes fiches revendiquées');
        $tpl = $response->createPluginTemplate('claim', 'my_claims');
        $tpl->set('claims', $claims);
        $tpl->set('ROUTER', $this->router);
        $response->addTemplate($tpl);
        return $response;
    }

    public function submit()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('claim:access'), $ctx)) {
            $this->core->redirect($this->router->generate('login'));
            return null;
        }
        $userId = (int)($this->user->attributes['id'] ?? 0);
        if ($userId <= 0) {
            $this->core->error404();
            return null;
        }
        
        $poiId = (int) $this->request->post('poi_id', 0);
        $note = trim((string) $this->request->post('note', ''));
        
        if ($poiId == 0) {
            $this->core->error404();
            return null;
        }

        // --- ENFORCEMENT : Authoritative Server-Side Policy Check (Phase 8) ---
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user, [
            'resource_id' => $poiId,
            'resource_type' => 'poi'
        ]);
        
        // On vérifie la politique 'poi:claim' (définie dans navigation.json ou policies.json)
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:claim'), $ctx)) {
            \Utils\Show::msg('Vous n\'avez pas l\'autorisation de revendiquer cette fiche.', 'error');
            $this->core->redirect($this->router->generate('claim-start', ['id' => $poiId]));
            return null;
        }
        // -----------------------------------------------------------------------

        $service = $this->getClaimService();
        
        // Trouver le POI
        $poi = $service->findPoi($poiId);
        if (!$poi) {
            \Utils\Show::msg('POI introuvable dans la base de données.', 'error');
            $this->core->redirect($this->router->generate('searchEVO-home'));
            return null;
        }

        // Vérifier que le justificatif est présent (obligatoire)
        if (!isset($_FILES['proof']) || !is_array($_FILES['proof']) || empty($_FILES['proof']['tmp_name']) || !is_uploaded_file($_FILES['proof']['tmp_name'])) {
            \Utils\Show::msg('Le justificatif est obligatoire pour valider votre demande de revendication.', 'error');
            $this->core->redirect($this->router->generate('claim-start', ['id' => $poiId]));
            return null;
        }

        $proofPath = $this->handleUpload();
        
        // Vérifier que l'upload a réussi
        if (!$proofPath) {
            \Utils\Show::msg('Erreur lors de l\'upload du justificatif. Veuillez réessayer.', 'error');
            $this->core->redirect($this->router->generate('claim-start', ['id' => $poiId]));
            return null;
        }
        
        // Récupérer l'email et le username de l'utilisateur pour la synchronisation MySQL
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        
        // Créer le claim (l'utilisateur sera créé/synchronisé dans MySQL si nécessaire)
        $claimId = $service->createClaim($poiId, $userId, $note, $proofPath, $userEmail, $username);
        
        \Utils\Show::msg('Votre demande de revendication a été envoyée.', 'success');
        // Rediriger vers la page de détail du POI avec l'URL courte
        if (!empty($poi->name) && class_exists('\SearchEngine\Services\Search\SearchService')) {
            try {
                $sqlSearchService = new \SearchEngine\Services\Search\SearchService();
                $poiName = trim($poi->name);
                // Note: SqlSearchService now uses Core DB internally
                $urlName = $sqlSearchService->generateUrlName($poiName, $poiId);
                $this->core->redirect('/' . $urlName);
            } catch (\Exception $e) {
                // Fallback vers la page d'accueil
                $this->core->redirect($this->router->generate('searchEVO-home'));
            }
        } else {
            // Fallback vers la page d'accueil si le nom n'est pas disponible
            $this->core->redirect($this->router->generate('searchEVO-home'));
        }
        return null;
    }
}

