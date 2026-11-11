<?php

namespace PoiEdit\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use PoiEdit\Services\PoiEditService;
use PoiEdit\Services\PoiTypesService;
use PDO;

defined('ROOT') or exit('Access denied!');

class PoiEditPublicController extends PublicController
{
    private function countWords(string $text): int
    {
        return count(preg_split('/\s+/u', trim(strip_tags($text)), -1, PREG_SPLIT_NO_EMPTY));
    }



    /**
     * @var PDO|null
     */
    private $pdo = null;

    private function getDbConfig(): array
    {
        $config = \Core\Database::getInstance()->getFullConfig();
        $dbConfig = $config['database'] ?? [];
        
        // Retourner au format attendu par le code existant
        return [
            'sql_host' => $dbConfig['host'] ?? 'localhost',
            'sql_db' => $dbConfig['dbname'] ?? 'vivrebourges',
            'sql_user' => $dbConfig['user'] ?? '',
            'sql_pass' => $dbConfig['password'] ?? '',
        ];
    }

    private function getPdo(): PDO
    {
        static $pdo = null;
        if (!$pdo) {
            try {
                // Utiliser la connexion du Core
                $pdo = \Core\Database::getInstance()->getPdo();
            } catch (\PDOException $e) {
                if ($this->core) {
                    $this->core->getLogger()->error('Erreur de connexion à la base de données: ' . $e->getMessage());
                }
                throw new \Exception('Erreur de connexion à la base de données');
            }
        }
        return $pdo;
    }

    private function getPoiEditService(): PoiEditService
    {
        if (!class_exists('\PoiEdit\Services\PoiEditService')) {
            require_once PLUGINS . 'poi-edit/services/PoiEditService.php';
        }
        return new PoiEditService($this->core);
    }

    private function getPoiTypesService(): PoiTypesService
    {
        if (!class_exists('\PoiEdit\Services\PoiTypesService')) {
            require_once PLUGINS . 'poi-edit/services/PoiTypesService.php';
        }
        return new PoiTypesService($this->getPdo(), '', $this->core);
    }

    public function home()
    {
        return $this->contribute();
    }

    /**
     * Affiche le formulaire de contribution (nouveau POI)
     */
    public function contribute()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:contribute'), $ctx)) {
            \Utils\Show::msg('Rejoignez la communauté pour proposer de nouveaux lieux !', 'info');
            $this->core->redirect($this->router->generate('register'));
            return;
        }

        if (!class_exists('\SearchEngine\Services\Search\Category\CategoryMappingService')) {
            require_once PLUGINS . 'SearchEngine/services/Search/Category/CategoryMappingService.php';
        }
        if (!class_exists('\PoiData\Services\CategoryLabelTranslator')) {
            require_once PLUGINS . 'poi-data/services/CategoryLabelTranslator.php';
        }
        \Core\Lang::loadLanguageFile(PLUGINS . 'SearchUi/langs/');

        $mappingService = new \SearchEngine\Services\Search\Category\CategoryMappingService();
        $families = $mappingService->getFamilies();
        $translator = new \PoiData\Services\CategoryLabelTranslator();

        $familiesForSelect = [];
        $specialtiesByFamily = [];

        foreach ($families as $key => $f) {
            $familiesForSelect[] = [
                'id' => $key,
                'slug' => $key,
                'label' => \Core\Lang::get($f['label'])
            ];

            $specs = $mappingService->getFiltresForFamily($key);
            $translatedSpecs = [];
            foreach ($specs as $spec) {
                $translatedSpecs[] = [
                    'key' => $spec['key'],
                    'label' => $translator->translate($spec['key'])
                ];
            }
            usort($translatedSpecs, function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
            $specialtiesByFamily[$key] = $translatedSpecs;
        }

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('PoiEdit', 'contribute');
        $tpl->set('familiesForSelect', $familiesForSelect);
        
        $specialtiesByFamilyJson = json_encode($specialtiesByFamily, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        $tpl->set('specialtiesByFamilyJson', $specialtiesByFamilyJson);
        $response->setTitle('Proposer un nouveau POI');
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Soumet une proposition de nouveau POI
     */
    public function submitContribution()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:contribute'), $ctx)) {
            $this->core->redirect($this->router->generate('register'));
            return;
        }

        try {
            $service = $this->getPoiEditService();
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;

            // Récupérer l'ID MySQL de l'utilisateur
            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            if (!$mysqlUserId) {
                \Utils\Show::msg('Erreur: utilisateur non trouvé dans la base de données.', 'error');
                $this->core->redirect($this->router->generate('poi-edit-contribute'));
                return;
            }

            // Récupération des champs
            $name = trim($_POST['name'] ?? '');
            $lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
            $lon = isset($_POST['lon']) && $_POST['lon'] !== '' ? (float)$_POST['lon'] : null;

            // Préparer les données
            $data = [
                'name' => $name,
                'type' => trim($_POST['type'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'subcategory' => (function() {
                    $custom = trim($_POST['subcategory_custom'] ?? '');
                    if ($custom !== '') {
                        // Slugifier la valeur libre pour la stocker proprement
                        return strtolower(preg_replace('/[^a-z0-9_]/', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $custom)));
                    }
                    $sel = trim($_POST['subcategory'] ?? '');
                    return $sel === '__custom__' ? '' : $sel;
                })(),
                'street' => trim($_POST['street'] ?? ''),
                'housenumber' => trim($_POST['housenumber'] ?? ''),
                'postcode' => trim($_POST['postcode'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'website' => trim($_POST['website'] ?? ''),
                'opening_hours' => trim($_POST['opening_hours'] ?? ''),
                'wheelchair' => trim($_POST['wheelchair'] ?? ''),
                'lat' => $lat,
                'lon' => $lon,
                'is_owner' => isset($_POST['is_owner']) && $_POST['is_owner'] === '1',
            ];

            // Si propriétaire, récupérer note et justificatif
            if ($data['is_owner']) {
                $data['note'] = trim($_POST['note'] ?? '');
                $data['proof_path'] = $this->handleUpload();
            }

            // Créer la proposition
            $proposalId = $service->createProposal($data, $mysqlUserId);

            // Gérer les sous-catégories multiples
            if ($proposalId && isset($_POST['subcategories']) && is_array($_POST['subcategories'])) {
                $subcategoryOsmKeys = array_filter(array_map('trim', $_POST['subcategories']));
                if (!empty($subcategoryOsmKeys)) {
                    $service->setPoiSubcategories($proposalId, array_values($subcategoryOsmKeys));
                    
                    // Si aucune subcategory principale n'est définie, utiliser la première
                    if (empty($data['subcategory'])) {
                        $firstKey = reset($subcategoryOsmKeys);
                        $service->updatePoi($proposalId, ['subcategory' => $firstKey], 'admin');
                    }
                }
            }

            // Gérer les horaires structurés pour la proposition
            if ($proposalId && isset($_POST['hours']) && is_array($_POST['hours'])) {
                // Pour une proposition, les horaires structurés sont liés au POI temporaire créé ou stockés différemment ?
                // Dans PoiEditService::createProposal, un nouveau POI est créé avec un ID négatif ou spécifique ?
                // Vérifions PoiEditService::createProposal
                $service->setPoiOpeningHours($proposalId, $_POST['hours']);
            }

            \Utils\Show::msg('Votre proposition a été enregistrée et sera examinée par un administrateur.', 'success');
            $this->core->redirect($this->router->generate('searchEVO-home'));
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditPublicController::submitContribution - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue lors de l\'enregistrement.', 'error');
            $this->core->redirect($this->router->generate('poi-edit-contribute'));
        }
    }

    private function getMySQLUserId(?string $email, ?string $username, int $cmsUserId): ?int
    {
        // Utiliser ClaimService pour synchroniser l'utilisateur
        if (!class_exists('\Claim\Services\ClaimService')) {
            require_once PLUGINS . 'claim/services/ClaimService.php';
        }
        $pdo = $this->getPdo();
        $claimService = new \Claim\Services\ClaimService($pdo);
        try {
            return $claimService->ensureUserExists($cmsUserId, $email ?? '', $username ?? '');
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditPublicController::getMySQLUserId - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si un utilisateur a un abonnement actif
     */
    private function hasActiveSubscription(int $mysqlUserId): bool
    {
        if (!class_exists('\Subscription\Services\SubscriptionService')) {
            return false;
        }
        try {
            $subscriptionService = new \Subscription\Services\SubscriptionService($this->core);
            $subscription = $subscriptionService->getUserSubscription($mysqlUserId);
            return $subscription !== null && isset($subscription['status']) && $subscription['status'] === 'active';
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditPublicController::hasActiveSubscription - ' . $e->getMessage());
            return false;
        }
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

    /**
     * Affiche le formulaire d'édition d'un POI
     */
    public function edit(string $type, $id)
    {
        // Pour les grands IDs OSM, utiliser directement la string pour éviter les dépassements d'entier
        $poiId = $id;
        if (is_numeric($id) && $id <= PHP_INT_MAX && $id >= PHP_INT_MIN) {
            $poiId = (int)$id;
        }
        
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:access'), $ctx)) {
            // Rediriger vers l'inscription avec un paramètre de retour
            $redirectUrl = $this->router->generate('poi-edit-start', ['type' => $type, 'id' => $poiId]);
            \Utils\Show::msg('Créez votre compte pour proposer des corrections et participer à la vie locale !', 'info');
            $this->core->redirect($this->router->generate('register') . '?redirect=' . urlencode($redirectUrl));
            return;
        }

        try {
            $service = $this->getPoiEditService();
            $poi = $service->getPoiById($poiId);

            if (!$poi) {
                \Utils\Show::msg('Fiche introuvable.', 'error');
                $this->core->redirect($this->router->generate('searchEVO-home'));
                return;
            }

            // Utiliser l'ID du POI tel quel (peut être un grand nombre OSM)
            $poiId = $poi['id'];
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;
            
            // Calculer l'appartenance pour la politique
            $isOwner = $service->isOwner($poiId, $userId, $userEmail, $username);
            
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            $ctx = new \Core\Navigation\ConditionContext($this->user, ['is_owner' => $isOwner]);
            
            $canEdit = $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:edit'), $ctx);
            $canSuggest = $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:suggest'), $ctx);
            $isAdmin = $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:admin'), $ctx);

            if (!$canEdit && !$canSuggest) {
                \Utils\Show::msg('Vous n\'avez pas les permissions nécessaires pour éditer cette fiche.', 'error');
                // Générer l'URL de détail avec le nom du POI
                try {
                    require_once PLUGINS . 'SearchEngine/services/Search/SearchService.php';
                    $cfg = $this->getDbConfig();
                    $sqlService = new \SearchEngine\Services\Search\SearchService();
                    $urlName = $sqlService->generateUrlName($poi['name'], (int)$poi['id']);
                    $poiType = !empty($poi['subcategory']) ? $poi['subcategory'] : (!empty($poi['category']) ? $poi['category'] : 'poi');
                    $this->core->redirect($this->router->generate('searchEVO-detail', ['type' => $poiType, 'name' => $urlName]));
                } catch (\Exception $e) {
                    // Fallback vers l'URL courte
                    $urlName = $sqlService->generateUrlName($poi['name'], (int)$poi['id']);
                    $this->core->redirect('/' . $urlName);
                }
                return;
            }

            $permission = $isAdmin ? 'admin' : ($canEdit ? 'edit' : 'suggest');

            // Récupérer les données complètes du POI
            $tags = $service->getPoiTags($poiId);
            $photos = $service->getPoiPhotos($poiId);
            $description = $service->getPoiDescription($poiId);
            $links = $service->getPoiLinks($poiId);
            $subcategories = $service->getPoiSubcategories($poiId);
            
            // Récupérer les tags personnalisés
            $logo = $service->getPoiTag($poiId, 'logo');
            $backgroundColor = $service->getPoiTag($poiId, 'background_color');
            $stars = $service->getPoiTag($poiId, 'stars');

            // Vérifier si l'utilisateur a un abonnement actif (pour les limitations)
            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            $hasSubscription = $mysqlUserId ? $this->hasActiveSubscription($mysqlUserId) : false;
            
            // Compter uniquement les éléments ajoutés par le propriétaire actuel (pour les limitations)
            // Si l'utilisateur n'est pas propriétaire ou n'a pas d'ID MySQL, compter à 0
            if ($mysqlUserId && $permission === 'edit') {
                $linksCount = $service->countPoiLinksByOwner($poiId, $mysqlUserId);
                $photosCount = $service->countPoiPhotosByOwner($poiId, $mysqlUserId);
                $customTagsCount = $service->countPoiCustomTagsByOwner($poiId, $mysqlUserId);
                $logoChangesThisWeek = $service->countLogoChangesThisWeek($poiId, $mysqlUserId);
                $photoUploadsThisWeek = $service->countPhotoUploadsThisWeek($poiId, $mysqlUserId);
            } else {
                // Pour les admins ou non-propriétaires, pas de limitations
                $linksCount = 0;
                $photosCount = 0;
                $customTagsCount = 0;
                $logoChangesThisWeek = 0;
                $photoUploadsThisWeek = 0;
            }
            $descriptionLength = $description ? strlen($description) : 0;

            $response = new PublicResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'edit');
            $tpl->set('poi', $poi);
            $tpl->set('tags', $tags);
            $tpl->set('photos', $photos);
            $tpl->set('description', $description);
            $tpl->set('links', $links);
            $tpl->set('subcategories', $subcategories);
            $tpl->set('logo', $logo);
            $tpl->set('backgroundColor', $backgroundColor);
            $tpl->set('stars', $stars);
            $tpl->set('poiOpeningHours', $service->getPoiOpeningHours($poiId));
            $tpl->set('permission', $permission);
            $tpl->set('canEdit', in_array($permission, ['edit', 'admin']));
            $tpl->set('canSuggest', $permission === 'suggest');
            $tpl->set('isAdmin', $permission === 'admin');
            $tpl->set('hasSubscription', $hasSubscription);
            $tpl->set('poiOpeningHours', $service->getPoiOpeningHours($poiId));
            $tpl->set('linksCount', $linksCount);
            $tpl->set('photosCount', $photosCount);
            $tpl->set('customTagsCount', $customTagsCount);
            $tpl->set('descriptionLength', $descriptionLength);
            $tpl->set('logoChangesThisWeek', $logoChangesThisWeek ?? 0);
            $tpl->set('photoUploadsThisWeek', $photoUploadsThisWeek ?? 0);
            
            // Get user gamification level
            $gamificationLevel = 1;
            if ($mysqlUserId) {
                if (class_exists('\Gamification\Services\PointsService')) {
                    $pointsService = new \Gamification\Services\PointsService();
                    $profile = $pointsService->getUserPalier($mysqlUserId);
                    $gamificationLevel = $profile['id'] ?? 1;
                }
            }
            $tpl->set('gamificationLevel', $gamificationLevel);
            
            // Générer l'URL de détail du POI pour le bouton "Annuler"
            try {
                require_once PLUGINS . 'SearchEngine/services/Search/SearchService.php';
                $cfg = $this->getDbConfig();
                $sqlService = new \SearchEngine\Services\Search\SearchService();
                $urlName = $sqlService->generateUrlName($poi['name'], (int)$poi['id']);
                $tpl->set('poiDetailUrl', '/' . $urlName);
            } catch (\Exception $e) {
                // Fallback vers la page d'accueil
                $tpl->set('poiDetailUrl', $this->router->generate('searchEVO-home'));
            }
            
            // Charger les familles et spécialités via CategoryMappingService
            $mappingService = new \SearchEngine\Services\Search\Category\CategoryMappingService();
            $families = $mappingService->getFamilies();
            $translator = new \PoiData\Services\CategoryLabelTranslator();

            $familiesForSelect = [];
            $specialtiesByFamily = [];

            foreach ($families as $key => $f) {
                $familiesForSelect[] = [
                    'id'    => $key,
                    'slug'  => $key,
                    'label' => \Core\Lang::get($f['label'])
                ];
                $specs = [];
                foreach ($f['specialties'] ?? [] as $specKey => $spec) {
                    $specs[] = [
                        'key'   => $specKey,
                        'label' => \Core\Lang::get($spec['label'] ?? $specKey)
                    ];
                }
                $specialtiesByFamily[$key] = $specs;
            }

            $tpl->set('familiesForSelect', $familiesForSelect);
            $specialtiesByFamilyJson = json_encode($specialtiesByFamily, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $tpl->set('specialtiesByFamilyJson', $specialtiesByFamilyJson);

            // Valeurs actuelles du POI pour présélection
            $selectedCategory = $poi['category'] ?? '';
            $selectedSubcategories = $service->getPoiSubcategories($poiId);
            if (empty($selectedSubcategories) && !empty($poi['subcategory'])) {
                $selectedSubcategories = [$poi['subcategory']];
            }

            $tpl->set('selectedCategory', $selectedCategory);
            $tpl->set('selectedSubcategories', $selectedSubcategories);

            
            // Préparer poiType pour les URLs (subcategory ou "poi" par défaut)
            $poiType = !empty($poi['subcategory']) ? $poi['subcategory'] : 'poi';
            $tpl->set('poiType', $poiType);
            
            // Préparer les valeurs JSON pour le JavaScript
            $poiNameJson = json_encode($poi['name'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $tpl->set('poiNameJson', $poiNameJson);
            
            // Préparer l'adresse formatée
            $address = '';
            if (!empty($poi['addr_housenumber'])) {
                $address .= $poi['addr_housenumber'] . ' ';
            }
            if (!empty($poi['addr_street'])) {
                $address .= $poi['addr_street'];
            }
            if (!empty($poi['addr_postcode']) || !empty($poi['addr_city'])) {
                if ($address) $address .= ', ';
                if (!empty($poi['addr_postcode'])) {
                    $address .= $poi['addr_postcode'] . ' ';
                }
                if (!empty($poi['addr_city'])) {
                    $address .= $poi['addr_city'];
                }
            }
            $poiAddressJson = json_encode($address, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $tpl->set('poiAddressJson', $poiAddressJson);
            
            // Préparer linksCount
            $linksCount = is_array($links) ? count($links) : 0;
            $tpl->set('linksCount', $linksCount);
            
            // Passer les tags comme une chaîne simple (comma-separated) pour le champ texte du template
            $poiTags = $service->getPoiTag($poiId, 'tags') ?? '';
            $tpl->set('poiTags', $poiTags);
            
            $response->setTitle('Édition - ' . ($poi['name'] ?? 'Fiche POI'));
            $response->addTemplate($tpl);
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditPublicController::edit - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('searchEVO-home'));
        }
    }

    /**
     * Soumet une modification directe (propriétaire ou admin)
     */
    public function submit()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:access'), $ctx)) {
            $this->core->redirect($this->router->generate('register'));
            return;
        }

        try {
            $service = $this->getPoiEditService();
            // Accepter les grands IDs OSM (string ou int)
            $poiIdRaw = $_POST['poi_id'] ?? '';
            if (empty($poiIdRaw)) {
                \Utils\Show::msg('POI invalide.', 'error');
                $this->core->redirect($this->router->generate('searchEVO-home'));
                return;
            }
            // Utiliser l'ID tel quel (string ou int) pour éviter les overflows
            $poiId = $poiIdRaw;
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;
            
            // Vérifier que le POI existe
            $poi = $service->getPoiById($poiId);
            if (!$poi || empty($poi['id'])) {
                \Utils\Show::msg('POI invalide.', 'error');
                $this->core->redirect($this->router->generate('searchEVO-home'));
                return;
            }

            // Calculer l'appartenance pour la politique
            $isOwner = $service->isOwner($poiId, $userId, $userEmail, $username);
            
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            $ctx = new \Core\Navigation\ConditionContext($this->user, ['is_owner' => $isOwner]);
            
            $canEdit = $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:edit'), $ctx);
            $canSuggest = $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:suggest'), $ctx);
            $isAdminAccess = $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:admin'), $ctx);

            if (!$canEdit && !$canSuggest) {
                \Utils\Show::msg('Vous n\'avez pas les permissions nécessaires.', 'error');
                $this->core->redirect($this->router->generate('searchEVO-home'));
                return;
            }

            $permission = $isAdminAccess ? 'admin' : ($canEdit ? 'edit' : 'suggest');

            // Vérifier l'abonnement pour les limitations (seulement pour les propriétaires non-admin)
            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            $hasSubscription = ($permission === 'admin' || ($mysqlUserId && $this->hasActiveSubscription($mysqlUserId)));

            // Récupérer les champs modifiables selon la permission
            $fields = [];
            
            // Champs selon la permission
            if ($permission === 'admin' || $permission === 'edit') {
                // Admin ou propriétaire : tous les champs
                $allowedFields = ['name', 'type', 'category', 'street', 'housenumber', 'postcode', 
                                'city', 'phone', 'email', 'website', 'opening_hours', 'wheelchair',
                                'internet_access', 'parking', 'fee', 'indoor', 'vending', 'brand', 'lat', 'lon'];
            } else {
                // Membre non-propriétaire : seulement adresse
                $allowedFields = ['street', 'housenumber', 'postcode', 'city'];
            }
            
            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $value = trim($_POST[$field]);
                    // Inclure tous les champs présents dans le formulaire, même vides
                    // Cela permet de supprimer les valeurs existantes si l'utilisateur vide le champ
                    $fields[$field] = $value !== '' ? $value : null;
                }
            }

            // Description avec limitation pour plan free
            if (isset($_POST['description'])) {
                $description = trim($_POST['description']);
                if (!empty($description)) {
                    $wordCount = $this->countWords($description);
                    if (!$hasSubscription && $wordCount > 150) {
                        \Utils\Show::msg('La description est limitée à 150 mots pour le plan gratuit. Passez au Premium pour lever cette limitation !', 'error');
                        $poi = $service->getPoiById($poiId);
                        if ($poi) {
                            $poiType = !empty($poi['subcategory']) ? $poi['subcategory'] : 'poi';
                            $this->core->redirect($this->router->generate('poi-edit-start', ['type' => $poiType, 'id' => $poiId]));
                        } else {
                            $this->core->redirect($this->router->generate('searchEVO-home'));
                        }
                        return;
                    }
                    // Utiliser l'ID MySQL pour saveDescription
                    $service->saveDescription($poiId, $description, $mysqlUserId);
                }
            }


            // Mise à jour du POI avec la permission
            if (!empty($fields)) {
                $service->updatePoi($poiId, $fields, $permission);
            }

            // Gérer les horaires structurés
            if (isset($_POST['hours']) && is_array($_POST['hours'])) {
                $service->setPoiOpeningHours($poiId, $_POST['hours']);
            }

            // Gérer les tags personnalisés (logo, couleur de fond, étoiles) - seulement pour admin/propriétaire
            if (in_array($permission, ['admin', 'edit'])) {
                // Vérifier les limitations pour plan free
                $existingLinks = $service->getPoiLinks($poiId);
                $existingPhotos = $service->getPoiPhotos($poiId);
                $existingTags = $service->getPoiTags($poiId);
                $customTagsCount = 0;
                foreach ($existingTags as $key => $value) {
                    if (!in_array($key, ['logo', 'background_color', 'stars', '_normalized'])) {
                        $customTagsCount++;
                    }
                }

                if (isset($_POST['logo'])) {
                    $logoValue = trim($_POST['logo']);
                    $existingLogo = $service->getPoiTag($poiId, 'logo');
                    
                    // Vérifier la limite de 2 changements de logo par semaine pour plan free
                    if (!$hasSubscription && $mysqlUserId) {
                        $logoChangesThisWeek = $service->countLogoChangesThisWeek($poiId, $mysqlUserId);
                        // Si on change le logo (nouveau ou différent de l'existant)
                        if (($logoValue !== '' && strtolower($logoValue) !== 'logo' && $logoValue !== $existingLogo) || 
                            (($logoValue === '' || strtolower($logoValue) === 'logo') && $existingLogo !== null)) {
                            if ($logoChangesThisWeek >= 2) {
                                \Utils\Show::msg('Vous ne pouvez changer votre logo que 2 fois par semaine avec le plan gratuit. Les abonnements arriveront bientôt pour lever cette limitation.', 'error');
                                $poi = $service->getPoiById($poiId);
                                if ($poi) {
                                    $poiType = !empty($poi['subcategory']) ? $poi['subcategory'] : 'poi';
                                    $this->core->redirect($this->router->generate('poi-edit-start', ['type' => $poiType, 'id' => $poiId]));
                                } else {
                                    $this->core->redirect($this->router->generate('searchEVO-home'));
                                }
                                return;
                            }
                        }
                    }
                    
                    // Ne pas sauvegarder si vide ou si c'est juste le placeholder
                    if ($logoValue === '' || strtolower($logoValue) === 'logo') {
                        $service->setPoiTag($poiId, 'logo', null, $mysqlUserId);
                    } else {
                        $service->setPoiTag($poiId, 'logo', $logoValue, $mysqlUserId);
                    }
                }
                
                // Bloquer la modification de la couleur de fond pour plan free
                if (isset($_POST['background_color']) && !$hasSubscription) {
                    \Utils\Show::msg('La modification de la couleur de fond n\'est pas disponible avec le plan gratuit. Les abonnements arriveront bientôt pour lever cette limitation.', 'error');
                    $poi = $service->getPoiById($poiId);
                    if ($poi) {
                        $poiType = !empty($poi['subcategory']) ? $poi['subcategory'] : 'poi';
                        $this->core->redirect($this->router->generate('poi-edit-start', ['type' => $poiType, 'id' => $poiId]));
                    } else {
                        $this->core->redirect($this->router->generate('searchEVO-home'));
                    }
                    return;
                }
                
                if (isset($_POST['background_color']) && $hasSubscription) {
                    $bgColor = trim($_POST['background_color']);
                    // Si aucune couleur n'est sélectionnée (valeur vide), supprimer le tag pour utiliser la couleur par défaut du style
                    if ($bgColor === '') {
                        $service->setPoiTag($poiId, 'background_color', null, $mysqlUserId);
                    } else {
                        $service->setPoiTag($poiId, 'background_color', $bgColor, $mysqlUserId);
                    }
                }
                
                if (isset($_POST['stars'])) {
                    $starsValue = trim($_POST['stars']);
                    $service->setPoiTag($poiId, 'stars', $starsValue ?: null, $mysqlUserId);
                }

                // Gérer les liens (réseaux sociaux) avec limitation pour plan free
                if (isset($_POST['links']) && is_array($_POST['links'])) {
                    $newLinks = [];
                    foreach ($_POST['links'] as $linkData) {
                        if (isset($linkData['url']) && !empty(trim($linkData['url']))) {
                            $newLinks[] = $linkData;
                        }
                    }
                    
                    // Vérifier la limite de 1 lien pour plan free (seulement les liens ajoutés par le propriétaire)
                    if (!$hasSubscription && $mysqlUserId) {
                        $currentLinksCount = $service->countPoiLinksByOwner($poiId, $mysqlUserId);
                        $newLinksToAdd = 0;
                        foreach ($newLinks as $linkData) {
                            // Compter seulement les nouveaux liens (sans id)
                            if (!isset($linkData['id'])) {
                                $newLinksToAdd++;
                            }
                        }
                        if ($currentLinksCount + $newLinksToAdd > 1) {
                            \Utils\Show::msg('Vous ne pouvez ajouter qu\'un seul lien de réseau social avec le plan gratuit. Les abonnements arriveront bientôt pour lever cette limitation.', 'error');
                            $poi = $service->getPoiById($poiId);
                            if ($poi) {
                                $poiType = !empty($poi['subcategory']) ? $poi['subcategory'] : 'poi';
                                $this->core->redirect($this->router->generate('poi-edit-start', ['type' => $poiType, 'id' => $poiId]));
                            } else {
                                $this->core->redirect($this->router->generate('searchEVO-home'));
                            }
                            return;
                        }
                    }
                    
                    // Récupérer les liens existants pour éviter les doublons
                    $existingLinks = $service->getPoiLinks($poiId);
                    $existingLinkIds = array_column($existingLinks, 'id');
                    $processedLinkIds = [];
                    
                    foreach ($newLinks as $linkData) {
                        $url = isset($linkData['url']) ? trim($linkData['url']) : '';
                        $label = isset($linkData['label']) ? trim($linkData['label'] ?? '') : '';
                        
                        // Ignorer les liens avec URL vide
                        if (empty($url)) {
                            continue;
                        }
                        
                        if (isset($linkData['id']) && !empty($linkData['id'])) {
                            // Mise à jour d'un lien existant : ne pas changer created_by
                            $linkId = (int)$linkData['id'];
                            if (in_array($linkId, $existingLinkIds)) {
                                $service->setPoiLink($poiId, $url, $label, $linkId);
                                $processedLinkIds[] = $linkId;
                            }
                        } else {
                            // Création : inclure created_by
                            $service->setPoiLink($poiId, $url, $label, null, $mysqlUserId);
                        }
                    }
                    
                    // Supprimer les liens existants créés par cet utilisateur qui n'ont pas été traités
                    if ($mysqlUserId) {
                        foreach ($existingLinks as $existingLink) {
                            if (isset($existingLink['created_by']) && (int)$existingLink['created_by'] === $mysqlUserId) {
                                if (!in_array($existingLink['id'], $processedLinkIds)) {
                                    $service->setPoiLink($poiId, '', null, $existingLink['id']);
                                }
                            }
                        }
                    }
                }

                // Gérer les tags simples (champ texte libre comma-separated)
                if (isset($_POST['tags'])) {
                    $tagsRaw = trim($_POST['tags']);
                    if ($tagsRaw !== '') {
                        // Convertir en array, nettoyer, puis re-stocker en string
                        $tagsList = array_filter(array_map('trim', explode(',', $tagsRaw)));
                        $cleanTags = implode(', ', $tagsList);
                        $service->setPoiTag($poiId, 'tags', $cleanTags ?: null, $mysqlUserId);
                    } else {
                        $service->setPoiTag($poiId, 'tags', null, $mysqlUserId);
                    }
                }
            }

            \Utils\Show::msg('Modifications enregistrées avec succès.', 'success');
            $poi = $service->getPoiById($poiId);
            if ($poi) {
                try {
                    require_once PLUGINS . 'SearchEngine/services/Search/SearchService.php';
                    $cfg = $this->getDbConfig();
                    $sqlService = new \SearchEngine\Services\Search\SearchService();
                    $urlName = $sqlService->generateUrlName($poi['name'], (int)$poi['id']);
                    $poiType = !empty($poi['subcategory']) ? $poi['subcategory'] : (!empty($poi['category']) ? $poi['category'] : 'poi');
                    $this->core->redirect($this->router->generate('searchEVO-detail', ['type' => $poiType, 'name' => $urlName]));
                } catch (\Exception $e) {
                    // Fallback vers l'URL courte
                    $cfg = $this->getDbConfig();
                    $sqlService = new \SearchEngine\Services\Search\SearchService();
                    $urlName = $sqlService->generateUrlName($poi['name'], (int)$poi['id']);
                    $this->core->redirect('/' . $urlName);
                }
            } else {
                $this->core->redirect($this->router->generate('searchEVO-home'));
            }
        } catch (\Exception $e) {
            $errorMsg = 'PoiEditPublicController::submit - ' . $e->getMessage();
            $errorMsg .= ' - POI ID: ' . var_export($poiIdRaw ?? 'N/A', true);
            $errorMsg .= ' - POST poi_id: ' . var_export($_POST['poi_id'] ?? 'N/A', true);
            $this->core->getLogger()->error($errorMsg);
            error_log($errorMsg);
            \Utils\Show::msg('Une erreur est survenue lors de l\'enregistrement: ' . $e->getMessage(), 'error');
            $poi = $service->getPoiById($poiId ?? $poiIdRaw ?? '');
            if ($poi) {
                $poiType = !empty($poi['subcategory']) ? $poi['subcategory'] : 'poi';
                $this->core->redirect($this->router->generate('poi-edit-start', ['type' => $poiType, 'id' => $poi['id']]));
            } else {
                $this->core->redirect($this->router->generate('searchEVO-home'));
            }
        }
    }

    /**
     * Soumet une suggestion de correction (membre classique)
     */
    public function suggest()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:suggest'), $ctx)) {
            \Utils\Show::msg('Vous devez être connecté.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        try {
            $service = $this->getPoiEditService();
            $poiId = (int)($_POST['poi_id'] ?? 0);
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;

            if ($poiId <= 0) {
                \Utils\Show::msg('Données invalides.', 'error');
                $this->core->redirect($this->router->generate('searchEVO-home'));
                return;
            }

            $permission = $service->getEditPermission($poiId, $userId, $userEmail, $username);
            if ($permission !== 'suggest') {
                \Utils\Show::msg('Vous n\'avez pas les permissions nécessaires.', 'error');
                $this->core->redirect($this->router->generate('searchEVO-home'));
                return;
            }

            // Récupérer le POI
            $poi = $service->getPoiById($poiId);
            if (!$poi) {
                \Utils\Show::msg('POI introuvable.', 'error');
                $this->core->redirect($this->router->generate('searchEVO-home'));
                return;
            }

            // Récupérer l'ID MySQL de l'utilisateur
            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            if (!$mysqlUserId) {
                \Utils\Show::msg('Erreur: utilisateur non trouvé dans la base de données.', 'error');
                $this->core->redirect($this->router->generate('searchEVO-home'));
                return;
            }

            // Champs autorisés pour les suggestions (nom, adresse, horaires)
            // Mapping des noms de champs de la table poi_public
            $fieldMapping = [
                'name' => 'name',
                'street' => 'addr_street',
                'housenumber' => 'addr_housenumber',
                'postcode' => 'addr_postcode',
                'city' => 'addr_city',
                'opening_hours' => 'opening_hours',
            ];
            $suggestionsCreated = 0;

            foreach ($fieldMapping as $postField => $dbField) {
                if (isset($_POST[$postField])) {
                    $newValue = trim($_POST[$postField]);
                    $oldValue = $poi[$dbField] ?? null;
                    
                    // Créer une suggestion seulement si la valeur a changé
                    if ($newValue !== $oldValue) {
                        $service->createSuggestion($poiId, $mysqlUserId, $dbField, $oldValue, $newValue);
                        $suggestionsCreated++;
                    }
                }
            }

            if ($suggestionsCreated > 0) {
                \Utils\Show::msg("Vos {$suggestionsCreated} suggestion(s) ont été enregistrées et seront examinées par un administrateur.", 'success');
            } else {
                \Utils\Show::msg('Aucune modification détectée.', 'info');
            }

            try {
                require_once PLUGINS . 'SearchEngine/services/Search/SearchService.php';
                $cfg = $this->getDbConfig();
                $sqlService = new \SearchEngine\Services\Search\SearchService();
                $urlName = $sqlService->generateUrlName($poi['name'], (int)$poi['id']);
                $poiType = !empty($poi['subcategory']) ? $poi['subcategory'] : (!empty($poi['category']) ? $poi['category'] : 'poi');
                $this->core->redirect($this->router->generate('searchEVO-detail', ['type' => $poiType, 'name' => $urlName]));
            } catch (\Exception $e) {
                // Fallback vers l'URL courte
                $cfg = $this->getDbConfig();
                $sqlService = new \SearchEngine\Services\Search\SearchService();
                $urlName = $sqlService->generateUrlName($poi['name'], (int)$poi['id']);
                $this->core->redirect('/' . $urlName);
            }
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditPublicController::suggest - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('searchEVO-home'));
        }
    }

    /**
     * Upload une photo
     */
    public function uploadPhoto()
    {
        // Désactiver la sortie de buffer pour éviter tout HTML avant le JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:access'), $ctx)) {
            echo json_encode(['success' => false, 'message' => 'Non connecté'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        try {
            $service = $this->getPoiEditService();
            // Accepter les grands IDs OSM (string ou int)
            $poiIdRaw = $_POST['poi_id'] ?? '';
            if (empty($poiIdRaw)) {
                echo json_encode(['success' => false, 'message' => 'POI invalide'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            // Utiliser l'ID tel quel (string ou int) pour éviter les overflows
            $poiId = $poiIdRaw;
            $userId = (int)($this->user->attributes['id'] ?? 0);

            // Vérifier que le POI existe
            $poi = $service->getPoiById($poiId);
            if (!$poi || empty($poi['id'])) {
                echo json_encode(['success' => false, 'message' => 'POI invalide'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;
            $permission = $service->getEditPermission($poiId, $userId, $userEmail, $username);
            if (!in_array($permission, ['edit', 'admin'])) {
                echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            // Vérifier les limites de photos pour plan free
            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            $hasSubscription = ($permission === 'admin' || ($mysqlUserId && $this->hasActiveSubscription($mysqlUserId)));
            
            if (!$hasSubscription && $mysqlUserId) {
                // Limite de 4 photos totales
                $existingPhotosCount = $service->countPoiPhotosByOwner($poiId, $mysqlUserId);
                if ($existingPhotosCount >= 4) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Vous ne pouvez ajouter que 4 photos maximum avec le plan gratuit. Les abonnements arriveront bientôt pour lever cette limitation.'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
                
                // Limite de 6 uploads par semaine
                $photoUploadsThisWeek = $service->countPhotoUploadsThisWeek($poiId, $mysqlUserId);
                if ($photoUploadsThisWeek >= 6) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Vous ne pouvez uploader que 6 photos par semaine avec le plan gratuit. Les abonnements arriveront bientôt pour lever cette limitation.'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
            }

            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Erreur d\'upload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $uploadDir = UPLOAD . 'poi-edit' . DS;
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'poi_' . $poiId . '_' . time() . '_' . uniqid() . '.' . $ext;
            $filepath = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
                echo json_encode(['success' => false, 'message' => 'Erreur de sauvegarde'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $url = \Utils\Util::urlBuild(UPLOAD . 'poi-edit/' . $filename);
            $caption = trim($_POST['caption'] ?? '');

            $photoId = $service->addPhoto($poiId, $url, $caption, $mysqlUserId);

            echo json_encode([
                'success' => true,
                'photo_id' => $photoId,
                'url' => $url,
                'caption' => $caption
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditPublicController::uploadPhoto - ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    /**
     * Supprime une photo
     */
    public function deletePhoto(int $photoId)
    {
        // Désactiver la sortie de buffer pour éviter tout HTML avant le JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:access'), $ctx)) {
            echo json_encode(['success' => false, 'message' => 'Non connecté'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        try {
            $service = $this->getPoiEditService();
            // Accepter les grands IDs OSM (string ou int)
            $poiIdRaw = $_POST['poi_id'] ?? '';
            if (empty($poiIdRaw)) {
                echo json_encode(['success' => false, 'message' => 'POI invalide'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            // Utiliser l'ID tel quel (string ou int) pour éviter les overflows
            $poiId = $poiIdRaw;
            $userId = (int)($this->user->attributes['id'] ?? 0);

            // Vérifier que le POI existe
            $poi = $service->getPoiById($poiId);
            if (!$poi || empty($poi['id'])) {
                echo json_encode(['success' => false, 'message' => 'POI invalide'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            // Convertir l'ID CMS en ID MySQL pour les permissions
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;
            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);

            $result = $service->deletePhoto($photoId, $poiId, $mysqlUserId);

            echo json_encode(['success' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditPublicController::deletePhoto - ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }


    /**
     * Propose un nouveau type ou catégorie
     */
    public function proposeType()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:contribute'), $ctx)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
            return;
        }

        try {
            $typesService = $this->getPoiTypesService();
            $poiId = isset($_POST['poi_id']) ? (int)$_POST['poi_id'] : null;
            $proposalType = trim($_POST['proposal_type'] ?? '');
            $label = trim($_POST['label'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $justification = trim($_POST['justification'] ?? '');
            $typeKey = trim($_POST['type_key'] ?? '');
            $categoryKey = trim($_POST['category_key'] ?? '');
            
            if (!in_array($proposalType, ['type', 'category']) || empty($label)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Données invalides']);
                return;
            }
            
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;
            
            // Récupérer l'ID MySQL de l'utilisateur
            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            if (!$mysqlUserId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
                return;
            }
            
            $proposalId = $typesService->proposeTypeOrCategory(
                $mysqlUserId,
                $proposalType,
                $poiId,
                $typeKey ?: null,
                $categoryKey ?: null,
                $label,
                $description ?: null,
                $justification ?: null
            );
            
            if ($proposalId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'proposal_id' => $proposalId]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
            }
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditPublicController::proposeType - ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
        }
    }

    /**
     * Met à jour la légende d'une photo (public)
     */
    public function updatePhotoCaption()
    {
        // Désactiver la sortie de buffer pour éviter tout HTML avant le JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:access'), $ctx)) {
            echo json_encode(['success' => false, 'message' => 'Non connecté'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        try {
            $service = $this->getPoiEditService();
            $photoId = (int)($_POST['photo_id'] ?? 0);
            // Accepter les grands IDs OSM (string ou int)
            $poiIdRaw = $_POST['poi_id'] ?? '';
            if (empty($poiIdRaw) || $photoId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Paramètres invalides'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            // Utiliser l'ID tel quel (string ou int) pour éviter les overflows
            $poiId = $poiIdRaw;
            $caption = isset($_POST['caption']) ? trim($_POST['caption']) : null;
            
            // Vérifier que le POI existe
            $poi = $service->getPoiById($poiId);
            if (!$poi || empty($poi['id'])) {
                echo json_encode(['success' => false, 'message' => 'POI invalide'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $userId = (int)($this->user->attributes['id'] ?? 0);
            // Convertir l'ID CMS en ID MySQL pour les permissions
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;
            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            
            if ($service->updatePhotoCaption($photoId, $poiId, $caption, $mysqlUserId)) {
                echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            exit;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditPublicController::updatePhotoCaption - ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Une erreur est survenue'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }


    private function ensureUploadDir(): string
    {
        $dir = UPLOAD . 'poi-edit' . DS;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Affiche les contributions de l'utilisateur connecté
     */
    public function myContributions()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:access'), $ctx)) {
            \Utils\Show::msg('Vous devez être connecté pour voir vos contributions.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        try {
            $service = $this->getPoiEditService();
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;
            $userId = (int)($this->user->attributes['id'] ?? 0);

            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            if (!$mysqlUserId) {
                \Utils\Show::msg('Erreur: utilisateur non trouvé dans la base de données.', 'error');
                $this->core->redirect($this->router->generate('home'));
                return;
            }

            // Récupérer les propositions
            $proposals = $service->getUserProposals($mysqlUserId);
            
            // Récupérer les suggestions groupées par POI
            $suggestionsGrouped = $service->getUserSuggestionsGroupedByPoi($mysqlUserId);

            // Générer les URLs de détail pour les POIs
            $cfg = $this->getDbConfig();
            $sqlService = null;
            if (class_exists('\SearchEngine\Services\Search\SearchService')) {
                $sqlService = new \SearchEngine\Services\Search\SearchService();
            }
            
            foreach ($suggestionsGrouped as &$poiGroup) {
                if ($sqlService && !empty($poiGroup['poi_name']) && !empty($poiGroup['poi_id'])) {
                    try {
                        $urlName = $sqlService->generateUrlName($poiGroup['poi_name'], (int)$poiGroup['poi_id']);
                        $poiGroup['detail_url'] = '/' . $urlName;
                    } catch (\Exception $e) {
                        $poiGroup['detail_url'] = null;
                    }
                } else {
                    $poiGroup['detail_url'] = null;
                }
            }
            unset($poiGroup);
            
            $response = new PublicResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'my_contributions');
            $tpl->set('proposals', $proposals);
            $tpl->set('suggestions', $suggestionsGrouped);
            $tpl->set('totalContributions', count($proposals) + count($suggestionsGrouped));
            $response->setTitle('Mes contributions');
            $response->addTemplate($tpl);
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditPublicController::myContributions - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('home'));
            return;
        }
    }
}

