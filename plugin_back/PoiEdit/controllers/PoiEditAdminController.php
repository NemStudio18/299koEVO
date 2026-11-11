<?php

namespace PoiEdit\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use PoiEdit\Services\PoiEditService;
use PoiEdit\Services\PoiTypesService;
use PoiEdit\Services\PoiFeaturesService;
use PDO;

defined('ROOT') or exit('Access denied!');

class PoiEditAdminController extends AdminController
{
    /**
     * Get PDO connection from Core
     * @return PDO
     */
    private function getPdo(): PDO
    {
        static $pdo = null;
        if (!$pdo) {
            try {
                $pdo = \Core\Database::getInstance()->getPdo();
            } catch (\PDOException $e) {
                throw new \Exception('Erreur de connexion à la base de données: ' . $e->getMessage());
            }
        }
        return $pdo;
    }

    /**
     * Récupère la configuration de la base de données depuis le Core
     * @return array
     */
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

    private function getPoiEditService(): PoiEditService
    {
        if (!class_exists('\PoiEdit\Services\PoiEditService')) {
            require_once PLUGINS . 'PoiEdit/services/PoiEditService.php';
        }
        return new PoiEditService($this->core);
    }

    private function getPoiTypesService(): PoiTypesService
    {
        if (!class_exists('\PoiEdit\Services\PoiTypesService')) {
            require_once PLUGINS . 'PoiEdit/services/PoiTypesService.php';
        }
        return new PoiTypesService($this->getPdo(), '', $this->core);
    }

    public function index()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home')); // Ou une autre page admin
            return;
        }

        try {
            $service = $this->getPoiEditService();
            $pendingSuggestionsGrouped = $service->getPendingSuggestionsGroupedByPoi(10, 0);
            $pendingProposals = $service->getPendingProposals(10, 0);
            $allSuggestionsGrouped = $service->getPendingSuggestionsGroupedByPoi(1000, 0);
            $allProposals = $service->getPendingProposals(1000, 0);
            
            // Statistiques
            $suggestionsStats = $service->getSuggestionStats();
            $proposalsStats = $service->getProposalsStats();
            $contributorsCount = $service->getContributorsCount();
            
            // Configuration
            $configFile = DATA_PLUGIN . 'poi-edit' . DS . 'config.json';
            $config = [];
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true) ?: [];
            }
            
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin');
            
            // Générer les URLs de détail pour les POIs dans les suggestions et propositions
            $cfg = $this->getDbConfig();
            $sqlService = null;
            if (class_exists('\SearchEngine\Services\Search\SearchService')) {
                $sqlService = new \SearchEngine\Services\Search\SearchService();
            }
            
            // Ajouter les URLs aux suggestions groupées
            foreach ($pendingSuggestionsGrouped as &$poiGroup) {
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
            
            foreach ($allSuggestionsGrouped as &$poiGroup) {
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
            
            // Ajouter les URLs aux propositions
            foreach ($pendingProposals as &$proposal) {
                if ($sqlService && !empty($proposal['poi_name']) && !empty($proposal['poi_id'])) {
                    try {
                        $urlName = $sqlService->generateUrlName($proposal['poi_name'], (int)$proposal['poi_id']);
                        $proposal['detail_url'] = '/' . $urlName;
                    } catch (\Exception $e) {
                        $proposal['detail_url'] = null;
                    }
                } else {
                    $proposal['detail_url'] = null;
                }
            }
            unset($proposal);
            
            foreach ($allProposals as &$proposal) {
                if ($sqlService && !empty($proposal['poi_name']) && !empty($proposal['poi_id'])) {
                    try {
                        $urlName = $sqlService->generateUrlName($proposal['poi_name'], (int)$proposal['poi_id']);
                        $proposal['detail_url'] = '/' . $urlName;
                    } catch (\Exception $e) {
                        $proposal['detail_url'] = null;
                    }
                } else {
                    $proposal['detail_url'] = null;
                }
            }
            unset($proposal);
            
            $tpl->set('pendingSuggestions', $pendingSuggestionsGrouped);
            $tpl->set('allSuggestions', $allSuggestionsGrouped);
            $tpl->set('pendingProposals', $pendingProposals);
            $tpl->set('allProposals', $allProposals);
            $tpl->set('suggestionsCount', (int)($suggestionsStats['pending_count'] ?? 0));
            $tpl->set('proposalsCount', (int)($proposalsStats['pending_count'] ?? 0));
            $tpl->set('suggestionsStats', $suggestionsStats);
            $tpl->set('proposalsStats', $proposalsStats);
            $tpl->set('contributorsCount', $contributorsCount);
            // Lire helpUrl depuis param.json
            $params = $this->getParams();
            $tpl->set('helpUrl', $params['helpUrl'] ?? '');
            $response->setTitle('Gestion des éditions POI');
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::dashboard - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    public function suggestions()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        try {
            $service = $this->getPoiEditService();
            $offset = (int)($_GET['offset'] ?? 0);
            $pendingSuggestions = $service->getPendingSuggestionsGroupedByPoi(50, $offset);
            
            // Compter le nombre total de POI avec des suggestions (groupées)
            $allGrouped = $service->getPendingSuggestionsGroupedByPoi(1000, 0);
            $suggestionsCount = count($allGrouped);
            
            // Générer les URLs de détail pour les POIs
            $cfg = $this->getDbConfig();
            $sqlService = null;
            if (class_exists('\SearchEngine\Services\Search\SearchService')) {
                $sqlService = new \SearchEngine\Services\Search\SearchService();
            }
            
            foreach ($pendingSuggestions as &$poiGroup) {
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
            
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_suggestions');
            
            $tpl->set('pendingSuggestions', $pendingSuggestions);
            $tpl->set('suggestionsCount', $suggestionsCount);
            $tpl->set('offset', $offset);
            $response->setTitle('Suggestions en attente');
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::suggestions - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    public function decideSuggestion()
    {
        try {
            $service = $this->getPoiEditService();
            $suggestionId = (int)($_POST['suggestion_id'] ?? 0);
            $poiId = (int)($_POST['poi_id'] ?? 0);
            $decision = trim($_POST['decision'] ?? '');

            // --- ENFORCEMENT : Authoritative Server-Side Policy Check (Phase 8) ---
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            $ctx = new \Core\Navigation\ConditionContext($this->user, [
                'resource_id' => $poiId ?: $suggestionId,
                'resource_type' => $poiId > 0 ? 'poi' : 'suggestion'
            ]);
            
            if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:edit:admin-decide'), $ctx)) {
                \Utils\Show::msg('Permissions insuffisantes.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit-suggestions'));
                return;
            }
            // -----------------------------------------------------------------------

            if (!in_array($decision, ['approved', 'rejected'])) {
                \Utils\Show::msg('Données invalides.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit-suggestions'));
                return;
            }

            $userId = (int)($this->user->attributes['id'] ?? 0);
            
            // Si poi_id est fourni, décider sur toutes les suggestions du POI
            if ($poiId > 0) {
                $result = $service->decideAllSuggestionsForPoi($poiId, $decision, $userId);
                $msg = $decision === 'approved' ? 'Toutes les suggestions pour ce POI ont été approuvées et appliquées.' : 'Toutes les suggestions pour ce POI ont été rejetées.';
            } else {
                // Sinon, décider sur une seule suggestion
                if ($suggestionId <= 0) {
                    \Utils\Show::msg('Données invalides.', 'error');
                    $this->core->redirect($this->router->generate('admin-poi-edit-suggestions'));
                    return;
                }
                $result = $service->decideSuggestion($suggestionId, $decision, $userId);
                $msg = $decision === 'approved' ? 'Suggestion approuvée et appliquée.' : 'Suggestion rejetée.';
            }

            if ($result) {
                \Utils\Show::msg($msg, 'success');
            } else {
                \Utils\Show::msg('Erreur lors du traitement de la suggestion.', 'error');
            }

            $this->core->redirect($this->router->generate('admin-poi-edit-suggestions'));
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::decideSuggestion - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('admin-poi-edit-suggestions'));
        }
    }

    /**
     * Formulaire de création directe d'un POI (admin uniquement, sans proposition ni gamification)
     */
    public function create()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);

        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        try {
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

            $service = $this->getPoiEditService();

            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_create');
            $tpl->set('familiesForSelect', $familiesForSelect);
            $tpl->set('specialtiesByFamilyJson', json_encode($specialtiesByFamily, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $tpl->set('typesCategoriesJson', json_encode($specialtiesByFamily, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $tpl->set('tags', '');
            $tpl->set('customTagsUrl', $this->router->generate('admin-poi-edit-custom-tags'));
            $response->setTitle('Créer un nouveau POI');
            $response->addTemplate($tpl);
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::create - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    /**
     * Enregistrement d'un nouveau POI créé directement par l'admin.
     * Pas de proposition, pas de gamification.
     */
    public function createSave()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);

        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        try {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                \Utils\Show::msg('Le nom du POI est obligatoire.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit-create'));
                return;
            }

            $service = $this->getPoiEditService();

            // Collecter les champs du formulaire
            $data = [];
            $fields = ['name', 'category', 'subcategory', 'street', 'housenumber', 'postcode',
                       'city', 'phone', 'email', 'website', 'opening_hours', 'wheelchair',
                       'internet_access', 'parking', 'fee', 'indoor', 'vending', 'brand'];
            foreach ($fields as $f) {
                if ($f === 'subcategory') {
                    $customSubcat = trim($_POST['subcategory_custom'] ?? '');
                    if ($customSubcat !== '') {
                        // Store the search-friendly version in the main column
                        $data['subcategory'] = strtolower(preg_replace('/[^a-z0-9_]/', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $customSubcat)));
                        // We will store the original label in poi_tags later
                    }
                }
                $val = trim($_POST[$f] ?? '');
                if ($val !== '') {
                    $data[$f] = $val;
                }
            }
            // Lat/lon
            if (isset($_POST['lat']) && $_POST['lat'] !== '') $data['lat'] = (float)$_POST['lat'];
            if (isset($_POST['lon']) && $_POST['lon'] !== '') $data['lon'] = (float)$_POST['lon'];

            // Créer le POI directement (sans proposal, sans gamification)
            $poiId = $service->createPoiDirect($data);

            if ($poiId === 0) {
                \Utils\Show::msg('Erreur lors de la création du POI. Vérifiez les logs.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit-create'));
                return;
            }

            // Gérer la description
            if (!empty($_POST['description'])) {
                $service->saveDescription($poiId, trim($_POST['description']));
            }

            // Gérer les sous-catégories multiples
            if (isset($_POST['subcategories']) && is_array($_POST['subcategories'])) {
                $subcategoryOsmKeys = array_filter(array_map('trim', $_POST['subcategories']));
                if (!empty($subcategoryOsmKeys)) {
                    $service->setPoiSubcategories($poiId, array_values($subcategoryOsmKeys));
                    
                    // Si aucune subcategory principale n'est définie dans les champs initiaux, utiliser la première
                    if (empty($data['subcategory'])) {
                        $firstKey = reset($subcategoryOsmKeys);
                        $service->updatePoi($poiId, ['subcategory' => $firstKey], 'admin');
                    }
                }
            }

            // Récupérer l'ID MySQL de l'admin pour les tags/liens/photos
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username  = $this->user->attributes['username'] ?? null;
            $pdo = $this->getPdo();
            if (!class_exists('\Claim\Services\ClaimService')) {
                require_once PLUGINS . 'claim/services/ClaimService.php';
            }
            $claimService = new \Claim\Services\ClaimService($pdo);
            $mysqlUserId  = $claimService->ensureUserExists($userId, $userEmail ?? '', $username ?? '');

            // Save custom subcategory as a tag if provided
            $customSubcat = trim($_POST['subcategory_custom'] ?? '');
            if ($customSubcat !== '') {
                $service->setPoiTag($poiId, 'subcategory_custom', $customSubcat, $mysqlUserId);
            }

            // Gérer les liens / réseaux sociaux
            if (isset($_POST['links']) && is_array($_POST['links'])) {
                foreach ($_POST['links'] as $linkData) {
                    $url = isset($linkData['url']) ? trim($linkData['url']) : '';
                    if (empty($url)) continue;
                    $label = isset($linkData['label']) ? trim($linkData['label']) : '';
                    $service->setPoiLink($poiId, $url, $label, null, $mysqlUserId);
                }
            }

            // Gérer les tags (champ texte comma-separated)
            if (isset($_POST['tags'])) {
                $tagsRaw = trim($_POST['tags']);
                if ($tagsRaw !== '') {
                    $tagsList  = array_filter(array_map('trim', explode(',', $tagsRaw)));
                    $cleanTags = implode(', ', $tagsList);
                    $service->setPoiTag($poiId, 'tags', $cleanTags ?: null, $mysqlUserId);
                }
            }

            // Gérer la photo
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = UPLOAD . 'poi-edit' . DS;
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $file      = $_FILES['photo'];
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $filename = 'photo_' . $poiId . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $photoUrl = '/upload/poi-edit/' . $filename;
                        $caption  = isset($_POST['photo_caption']) ? trim($_POST['photo_caption']) : null;
                        $service->addPhoto($poiId, $photoUrl, $caption, $mysqlUserId);
                    }
                }
            }

            \Utils\Show::msg('POI "' . htmlspecialchars($name) . '" créé avec succès !', 'success');
            // Rediriger vers l'édition du nouveau POI
            $this->core->redirect($this->router->generate('admin-poi-edit-edit', ['type' => 'poi', 'id' => (string)$poiId]));
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::createSave - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue : ' . $e->getMessage(), 'error');
            $this->core->redirect($this->router->generate('admin-poi-edit-create'));
        }
    }

    public function edit(string $type, $id)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        try {
            // Pour les grands IDs OSM, utiliser directement la string pour éviter les dépassements d'entier
            $poiId = $id;
            if (is_numeric($id) && $id <= PHP_INT_MAX && $id >= PHP_INT_MIN) {
                $poiId = (int)$id;
            }
            
            $service = $this->getPoiEditService();
            $poi = $service->getPoiById($poiId);

            if (!$poi) {
                \Utils\Show::msg('Fiche introuvable.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit'));
                return;
            }

            // Utiliser l'ID du POI tel quel (peut être un grand nombre OSM)
            $poiId = $poi['id'];
            $tags = $service->getPoiTags($poiId);

            // Extract subcategory_custom from tags
            $poi['subcategory_custom'] = null;
            foreach ($tags as $tag) {
                if ($tag['key_name'] === 'subcategory_custom') {
                    $poi['subcategory_custom'] = $tag['value_text'];
                    break;
                }
            }
            $photos = $service->getPoiPhotos($poiId);
            // Préparer les photos pour le template (s'assurer que caption est toujours une string)
            foreach ($photos as &$photo) {
                $photo['caption'] = $photo['caption'] ?? '';
                $photo['caption_escaped'] = htmlspecialchars($photo['caption'] ?? '', ENT_QUOTES, 'UTF-8');
            }
            unset($photo);
            
            $description = $service->getPoiDescription($poiId);
            $links = $service->getPoiLinks($poiId);
            $subcategories = $service->getPoiSubcategories($poiId);
            // S'assurer que links est toujours un tableau
            if (!is_array($links)) {
                $links = [];
            }
            
            // Récupérer les tags personnalisés
            $logo = $service->getPoiTag($poiId, 'logo');
            $backgroundColor = $service->getPoiTag($poiId, 'background_color');
            // Valider et normaliser la couleur (doit être au format #rrggbb)
            if ($backgroundColor && !preg_match('/^#[0-9A-Fa-f]{6}$/', $backgroundColor)) {
                $backgroundColor = null;
            }
            if (!$backgroundColor) {
                $backgroundColor = '#fafafa'; // Valeur par défaut
            }
            $stars = $service->getPoiTag($poiId, 'stars');

            // Sauvegarder la valeur OSM originale pour référence
            $poiOriginalCategory = $poi['category'] ?? null;
            
            // Nettoyer les valeurs NULL pour éviter qu'elles s'affichent comme texte
            $poiCleaned = [];
            foreach ($poi as $key => $value) {
                // Convertir NULL en chaîne vide pour les champs texte
                if ($value === null) {
                    $poiCleaned[$key] = '';
                } else {
                    $poiCleaned[$key] = $value;
                }
            }

            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_edit');
            
            $tpl->set('poi', $poiCleaned);
            $tpl->set('tags', $tags);
            $tpl->set('photos', $photos);
            $tpl->set('subcategories', $subcategories);
            $tpl->set('description', $description);
            $tpl->set('poiOpeningHours', $service->getPoiOpeningHours($poiId));
            $tpl->set('links', $links);
            $tpl->set('subcategories', $subcategories);
            $tpl->set('logo', $logo);
            $tpl->set('backgroundColor', $backgroundColor);
            $tpl->set('stars', $stars);
            $tpl->set('isAdmin', true);
            $tpl->set('canEdit', true);
            
            // Préparer les valeurs pour le JavaScript
            $linksCount = is_array($links) ? count($links) : 0;
            $tpl->set('linksCount', $linksCount);
            $tagsCount = is_array($tags) ? count($tags) : 0;
            $tpl->set('tagsCount', $tagsCount);
            
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

            $tpl->set('familiesForSelect', $familiesForSelect);
            $tpl->set('specialtiesByFamilyJson', json_encode($specialtiesByFamily, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $tpl->set('typesCategoriesJson', json_encode($specialtiesByFamily, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            // Utiliser directement les valeurs category/subcategory du POI (plus d'override éditorial)
            $poiCleaned['category'] = $poi['category'] ?? '';
            $poiCleaned['subcategory'] = $poi['subcategory'] ?? '';
            
            // Récupérer les sous-catégories multiples depuis poi_subcategories
            $selectedSubcategories = $service->getPoiSubcategories($poiId);
            
            // Fallback : si vide, utiliser la subcategory du POI directement
            if (empty($selectedSubcategories) && !empty($poiCleaned['subcategory'])) {
                $selectedSubcategories[] = $poiCleaned['subcategory'];
            }
            
            // Mapper la catégorie OSM vers la famille UI pour le pré-chargement du select
            $family = $mappingService->mapToFamily($poiCleaned['category'], $poiCleaned['subcategory']);
            $selectedCategory = $family['key'] ?? $poiCleaned['category'];

            $tpl->set('poi', $poiCleaned);
            $tpl->set('selectedCategory', $selectedCategory);
            $tpl->set('selectedSubcategories', $selectedSubcategories);
            $tpl->set('selectedSubcategoriesJson', json_encode($selectedSubcategories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            
            // Passer les tags comme chaîne simple (comma-separated)
            $poiTags = $service->getPoiTag($poiId, 'tags') ?? '';
            $tpl->set('tags', $poiTags);

            $response->setTitle('Édition admin - ' . ($poi['name'] ?? 'Fiche POI'));
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $errorMessage = 'PoiEditAdminController::edit - ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString();
            $this->core->getLogger()->error($errorMessage);
            error_log($errorMessage);
            \Utils\Show::msg('Une erreur est survenue : ' . $e->getMessage(), 'error');
            return new AdminResponse();
        }
    }

    public function save()
    {
        try {
            $service = $this->getPoiEditService();
            $rawPoiId = $_POST['poi_id'] ?? '';

            // Handle any numeric ID (positive, negative, large)
            if (is_numeric($rawPoiId) && $rawPoiId != 0) {
                $poiId = $rawPoiId;
                // Cast to int if it fits in PHP_INT range
                if (abs((float)$rawPoiId) <= PHP_INT_MAX) {
                    $poiId = (int)$rawPoiId;
                }
            } else {
                $poiId = 0;
            }

            if (empty($poiId)) {
                \Utils\Show::msg('POI invalide.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit'));
                return;
            }

            // --- ENFORCEMENT : Authoritative Server-Side Policy Check (Phase 8) ---
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            $ctx = new \Core\Navigation\ConditionContext($this->user, [
                'resource_id' => $poiId,
                'resource_type' => 'poi'
            ]);
            
            if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('poi:edit:admin-save'), $ctx)) {
                \Utils\Show::msg('Permissions insuffisantes pour modifier ce POI.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit'));
                return;
            }
            // -----------------------------------------------------------------------

            // Récupérer les champs modifiables (ne pas enregistrer les valeurs vides)
            $fields = [];
            $allowedFields = ['name', 'category', 'subcategory', 'street', 'housenumber', 'postcode', 
                            'city', 'phone', 'email', 'website', 'opening_hours', 'wheelchair',
                            'internet_access', 'parking', 'fee', 'indoor', 'vending', 'brand', 'lat', 'lon'];
            
            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $value = trim($_POST[$field]);
                    // Ne pas enregistrer les valeurs vides (sauf pour certains champs spéciaux)
                    // Les champs numériques (lat, lon) peuvent être 0, donc on les accepte
                    // type et category peuvent être vides (null) pour permettre de les effacer
                    if ($value !== '' || in_array($field, ['lat', 'lon', 'type', 'category'])) {
                        // Pour les champs numériques, convertir en float/int
                        if (in_array($field, ['lat', 'lon'])) {
                            $fields[$field] = $value !== '' ? (float)$value : null;
                        } else {
                            // Pour type et category, accepter même si vide (pour permettre de les effacer)
                            $fields[$field] = $value !== '' ? $value : null;
                        }
                    }
                }
            }

            // Gérer les tags personnalisés (logo, couleur de fond, étoiles)
            $userId = (int)($this->user->attributes['id'] ?? 0);
            
            // Convertir l'ID CMS en ID MySQL pour les opérations nécessitant un ID MySQL
            $mysqlUserId = null;
            if ($userId > 0) {
                $userEmail = $this->user->attributes['email'] ?? null;
                $username = $this->user->attributes['username'] ?? null;
                if (!class_exists('\Claim\Services\ClaimService')) {
                    require_once PLUGINS . 'claim/services/ClaimService.php';
                }
                $pdo = $this->getPdo();
                $claimService = new \Claim\Services\ClaimService($pdo);
                $mysqlUserId = $claimService->ensureUserExists($userId, $userEmail ?? '', $username ?? '');
            }

            // Description (peut être vide)
            if (isset($_POST['description'])) {
                $description = trim($_POST['description']);
                // Enregistrer même si vide pour permettre de vider la description
                $service->saveDescription($poiId, $description, $mysqlUserId);
            }

            // Gérer la sauvegarde des familles (category) et spécialités (subcategory)
            if (isset($_POST['category']) && trim($_POST['category']) !== '') {
                $fields['category'] = trim($_POST['category']);
            }
            if (isset($_POST['subcategory']) || isset($_POST['subcategory_custom'])) {
                $customSubcat = trim($_POST['subcategory_custom'] ?? '');
                if ($customSubcat !== '') {
                    // Slugifier la valeur
                    $fields['subcategory'] = strtolower(preg_replace('/[^a-z0-9_]/', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $customSubcat)));
                    // Save actual label as a tag
                    $service->setPoiTag($poiId, 'subcategory_custom', $customSubcat, $mysqlUserId);
                } else {
                    $selSubcat = trim($_POST['subcategory'] ?? '');
                    $fields['subcategory'] = ($selSubcat === '__custom__' ? null : ($selSubcat ?: null));
                }
            }

            // Gérer les sous-catégories multiples (checkboxes)
            if (isset($_POST['subcategories']) && is_array($_POST['subcategories'])) {
                $subcategoryOsmKeys = array_filter(array_map('trim', $_POST['subcategories']), function($val) {
                    return !empty($val);
                });
                
                if (!empty($subcategoryOsmKeys)) {
                    // Sauvegarder les sous-catégories multiples dans poi_subcategories
                    $service->setPoiSubcategories($poiId, array_values($subcategoryOsmKeys));
                    
                    // Si aucune subcategory principale n'est définie, utiliser la première
                    if (empty($fields['subcategory'])) {
                        $firstSubcategoryKey = reset($subcategoryOsmKeys);
                        $fields['subcategory'] = $firstSubcategoryKey;
                    }
                }
            }

            // Mise à jour du POI (admin peut tout modifier)
            // Cette méthode appelle le hook poi:updated, donc poi_search doit déjà être synchronisé
            if (!empty($fields)) {
                $service->updatePoi($poiId, $fields, 'admin');
            }
            
            // Gérer l'upload du logo
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = UPLOAD . 'poi-edit' . DS;
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                
                $file = $_FILES['logo_file'];
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                
                if (in_array($extension, $allowedExtensions)) {
                    $filename = 'logo_' . $poiId . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $logoUrl = '/upload/poi-edit/' . $filename;
                        $service->setPoiTag($poiId, 'logo', $logoUrl, $mysqlUserId);
                    }
                }
            } elseif (isset($_POST['logo'])) {
                $logoValue = trim($_POST['logo']);
                // Ne pas sauvegarder si vide ou si c'est juste le placeholder
                if ($logoValue === '' || strtolower($logoValue) === 'logo') {
                    $service->setPoiTag($poiId, 'logo', null, $mysqlUserId);
                } else {
                    $service->setPoiTag($poiId, 'logo', $logoValue, $mysqlUserId);
                }
            }
            if (isset($_POST['background_color'])) {
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
            
            // Gérer les sous-catégories multiples (pour compatibilité avec l'existant)
            // Note: Les sous-catégories multiples sont déjà gérées ci-dessus si category_id est défini
            // Sinon, on les gère séparément (cas où on veut juste ajouter des sous-catégories sans override)
            if (!isset($_POST['category']) && isset($_POST['subcategories']) && is_array($_POST['subcategories'])) {
                $subcategories = array_filter(array_map('trim', $_POST['subcategories']), function($val) {
                    return !empty($val);
                });
                if (!empty($subcategories)) {
                    $service->setPoiSubcategories($poiId, array_values($subcategories));
                }
            }

            // Gérer les horaires structurés
            if (isset($_POST['hours']) && is_array($_POST['hours'])) {
                $service->setPoiOpeningHours($poiId, $_POST['hours']);
            }

            // Gérer les liens (réseaux sociaux)
            // Récupérer les liens existants pour éviter les doublons
            $existingLinks = $service->getPoiLinks($poiId);
            $existingLinkIds = array_column($existingLinks, 'id');
            
            if (isset($_POST['links']) && is_array($_POST['links'])) {
                $processedLinkIds = [];
                
                foreach ($_POST['links'] as $linkData) {
                    $url = isset($linkData['url']) ? trim($linkData['url']) : '';
                    $label = isset($linkData['label']) ? trim($linkData['label'] ?? '') : '';
                    
                    // Ignorer les liens avec URL vide
                    if (empty($url)) {
                        continue;
                    }
                    
                    if (isset($linkData['id']) && !empty($linkData['id'])) {
                        // Mise à jour d'un lien existant
                        $linkId = (int)$linkData['id'];
                        if (in_array($linkId, $existingLinkIds)) {
                            $service->setPoiLink($poiId, $url, $label, $linkId);
                            $processedLinkIds[] = $linkId;
                        }
                    } else {
                        // Nouveau lien (création) - utiliser l'ID MySQL
                        $service->setPoiLink($poiId, $url, $label, null, $mysqlUserId);
                    }
                }
                
                // Supprimer les liens existants qui n'ont pas été traités (supprimés par l'utilisateur)
                foreach ($existingLinkIds as $existingId) {
                    if (!in_array($existingId, $processedLinkIds)) {
                        $service->setPoiLink($poiId, '', null, $existingId);
                    }
                }
            } else {
                // Si aucun lien n'est envoyé, supprimer tous les liens existants
                foreach ($existingLinkIds as $existingId) {
                    $service->setPoiLink($poiId, '', null, $existingId);
                }
            }

            // Gérer l'upload de photo
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = UPLOAD . 'poi-edit' . DS;
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                
                $file = $_FILES['photo'];
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($extension, $allowedExtensions)) {
                    $filename = 'photo_' . $poiId . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $photoUrl = \Utils\Util::urlBuild(UPLOAD . 'poi-edit/' . $filename);
                        $caption = isset($_POST['photo_caption']) ? trim($_POST['photo_caption']) : null;
                        $service->addPhoto($poiId, $photoUrl, $caption, $mysqlUserId);
                    }
                }
            }

            // Gérer les tags simples (champ texte libre comma-separated)
            if (isset($_POST['tags'])) {
                $tagsRaw = trim($_POST['tags']);
                if ($tagsRaw !== '') {
                    $tagsList = array_filter(array_map('trim', explode(',', $tagsRaw)));
                    $cleanTags = implode(', ', $tagsList);
                    $service->setPoiTag($poiId, 'tags', $cleanTags ?: null, $mysqlUserId);
                } else {
                    $service->setPoiTag($poiId, 'tags', null, $mysqlUserId);
                }
            }

            \Utils\Show::msg('Modifications enregistrées avec succès.', 'success');
            $poi = $service->getPoiById($poiId);
            if ($poi) {
                // Redirect back to the edit page
                $poiType = $poi['osm_type'] ?? $poi['type'] ?? 'poi';
                $this->core->redirect($this->router->generate('admin-poi-edit-edit', ['type' => $poiType, 'id' => (string)$poi['id']]));
            } else {
                $this->core->redirect($this->router->generate('admin-poi-edit'));
            }
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::save - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue lors de l\'enregistrement.', 'error');
            $this->core->redirect($this->router->generate('admin-poi-edit'));
        }
    }

    public function proposals()
    {
        try {
            $service = $this->getPoiEditService();
            $offset = (int)($_GET['offset'] ?? 0);
            $pendingProposals = $service->getPendingProposals(50, $offset);
            
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_proposals');
            
            $tpl->set('pendingProposals', $pendingProposals);
            $tpl->set('offset', $offset);
            $response->setTitle('Propositions de nouveaux POI');
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::proposals - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    public function decideProposal()
    {
        try {
            $this->core->getLogger()->info('PoiEditAdminController::decideProposal - START');
            $service = $this->getPoiEditService();
            $proposalId = (int)($_POST['proposal_id'] ?? 0);
            $decision = trim($_POST['decision'] ?? '');
            
            $this->core->getLogger()->info("PoiEditAdminController::decideProposal - proposalId={$proposalId}, decision={$decision}");

            if ($proposalId <= 0 || !in_array($decision, ['approved', 'rejected'])) {
                $this->core->getLogger()->warning("PoiEditAdminController::decideProposal - Invalid data: proposalId={$proposalId}, decision={$decision}");
                \Utils\Show::msg('Données invalides.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit-proposals'));
                return;
            }

            // Récupérer l'ID MySQL de l'admin (reviewed_by doit être un ID MySQL)
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;
            $cmsUserId = (int)($this->user->attributes['id'] ?? 0);
            
            $this->core->getLogger()->info("PoiEditAdminController::decideProposal - cmsUserId={$cmsUserId}, email={$userEmail}, username={$username}");
            
            // Utiliser ClaimService pour obtenir l'ID MySQL
            if (!class_exists('\Claim\Services\ClaimService')) {
                require_once PLUGINS . 'claim/services/ClaimService.php';
            }
            $pdo = $this->getPdo();
            $claimService = new \Claim\Services\ClaimService($pdo);
            $mysqlUserId = $claimService->ensureUserExists($cmsUserId, $userEmail ?? '', $username ?? '');
            
            $this->core->getLogger()->info("PoiEditAdminController::decideProposal - mysqlUserId={$mysqlUserId}");
            
            $result = false;

            if ($decision === 'approved') {
                $this->core->getLogger()->info("PoiEditAdminController::decideProposal - Calling approveProposal({$proposalId}, {$mysqlUserId})");
                $result = $service->approveProposal($proposalId, $mysqlUserId);
                $this->core->getLogger()->info("PoiEditAdminController::decideProposal - approveProposal returned: " . ($result ? 'true' : 'false'));
            } else {
                $this->core->getLogger()->info("PoiEditAdminController::decideProposal - Calling rejectProposal({$proposalId}, {$mysqlUserId})");
                $result = $service->rejectProposal($proposalId, $mysqlUserId);
                $this->core->getLogger()->info("PoiEditAdminController::decideProposal - rejectProposal returned: " . ($result ? 'true' : 'false'));
            }

            if ($result) {
                $msg = $decision === 'approved' ? 'Proposition approuvée et POI créé.' : 'Proposition rejetée.';
                \Utils\Show::msg($msg, 'success');
                $this->core->getLogger()->info("PoiEditAdminController::decideProposal - SUCCESS: {$msg}");
            } else {
                \Utils\Show::msg('Erreur lors du traitement de la proposition. Vérifiez les logs pour plus de détails.', 'error');
                $this->core->getLogger()->error('PoiEditAdminController::decideProposal - approveProposal/rejectProposal returned false for proposal ' . $proposalId);
            }

            $this->core->redirect($this->router->generate('admin-poi-edit-proposals'));
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::decideProposal - Exception: ' . $e->getMessage());
            $this->core->getLogger()->error('PoiEditAdminController::decideProposal - Stack trace: ' . $e->getTraceAsString());
            \Utils\Show::msg('Une erreur est survenue: ' . $e->getMessage(), 'error');
            $this->core->redirect($this->router->generate('admin-poi-edit-proposals'));
        }
    }

    /**
     * Page de configuration du plugin
     */
    public function config()
    {
        try {
            $configFile = DATA_PLUGIN . 'poi-edit' . DS . 'config.json';
            $config = [];
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true) ?: [];
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Sauvegarder helpUrl dans param.json
                $params = $this->getParams();
                $params['helpUrl'] = trim($_POST['helpUrl'] ?? '');
                $this->saveParams($params);
                
                \Utils\Show::msg('Configuration enregistrée avec succès.', 'success');
                $this->core->redirect($this->router->generate('admin-poi-edit'));
                return;
            }

            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_config');
            // Lire helpUrl depuis param.json
            $params = $this->getParams();
            $tpl->set('helpUrl', $params['helpUrl'] ?? '');
            $response->setTitle('Configuration POI Editor');
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::config - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    /**
     * Gestion des types et catégories personnalisés
     */
    public function types()
    {
        try {
            $service = $this->getPoiTypesService();
            $types = $service->getAllTypes();
            
            // Construire les catégories par type
            $typesWithCategories = [];
            foreach ($types as $typeKey => $typeData) {
                $typesWithCategories[$typeKey] = [
                    'label' => $typeData['label'],
                    'is_custom' => $typeData['is_custom'] ?? false,
                    'categories' => $service->getCategoriesForType($typeKey),
                ];
            }
            
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_types');
            $tpl->set('types', $typesWithCategories);
            $response->setTitle('Gestion des types et catégories');
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::types - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    /**
     * Ajouter un type personnalisé
     */
    public function addType()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $service = $this->getPoiTypesService();
                $typeKey = trim($_POST['type_key'] ?? '');
                $typeLabel = trim($_POST['type_label'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $icon = trim($_POST['icon'] ?? '');
                $userId = (int)($this->user->attributes['id'] ?? 0);
                
                if (empty($typeKey) || empty($typeLabel)) {
                    \Utils\Show::msg('La clé et le label sont requis.', 'error');
                    $this->core->redirect($this->router->generate('admin-poi-edit-types'));
                    return;
                }
                
                if ($service->addCustomType($typeKey, $typeLabel, $description ?: null, $icon ?: null, $userId)) {
                    \Utils\Show::msg('Type ajouté avec succès.', 'success');
                } else {
                    \Utils\Show::msg('Erreur lors de l\'ajout du type.', 'error');
                }
                
                $this->core->redirect($this->router->generate('admin-poi-edit-types'));
            } catch (\Exception $e) {
                $this->core->getLogger()->error('PoiEditAdminController::addType - ' . $e->getMessage());
                \Utils\Show::msg('Une erreur est survenue.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit-types'));
            }
        } else {
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_type_form');
            $tpl->set('type', null);
            $response->setTitle('Ajouter un type');
            $response->addTemplate($tpl);
            return $response;
        }
    }

    /**
     * Ajouter une catégorie personnalisée
     */
    public function addCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $service = $this->getPoiTypesService();
                $typeKey = trim($_POST['type_key'] ?? '');
                $categoryKey = trim($_POST['category_key'] ?? '');
                $categoryLabel = trim($_POST['category_label'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $icon = trim($_POST['icon'] ?? '');
                $userId = (int)($this->user->attributes['id'] ?? 0);
                
                if (empty($typeKey) || empty($categoryKey)) {
                    \Utils\Show::msg('Le type et la clé de catégorie sont requis.', 'error');
                    $this->core->redirect($this->router->generate('admin-poi-edit-types'));
                    return;
                }
                
                if ($service->addCustomCategory($typeKey, $categoryKey, $categoryLabel ?: null, $description ?: null, $icon ?: null, $userId)) {
                    \Utils\Show::msg('Catégorie ajoutée avec succès.', 'success');
                } else {
                    \Utils\Show::msg('Erreur lors de l\'ajout de la catégorie.', 'error');
                }
                
                $this->core->redirect($this->router->generate('admin-poi-edit-types'));
            } catch (\Exception $e) {
                $this->core->getLogger()->error('PoiEditAdminController::addCategory - ' . $e->getMessage());
                \Utils\Show::msg('Une erreur est survenue.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit-types'));
            }
        } else {
            $service = $this->getPoiTypesService();
            $types = $service->getAllTypes();
            
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_category_form');
            $tpl->set('types', $types);
            $response->setTitle('Ajouter une catégorie');
            $response->addTemplate($tpl);
            return $response;
        }
    }

    /**
     * Gestion des propositions de types/catégories
     */
    public function typeProposals()
    {
        try {
            $service = $this->getPoiTypesService();
            $offset = (int)($_GET['offset'] ?? 0);
            $pendingProposals = $service->getPendingProposals(50, $offset);
            
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_type_proposals');
            $tpl->set('pendingProposals', $pendingProposals);
            $tpl->set('offset', $offset);
            $response->setTitle('Propositions de types/catégories');
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::typeProposals - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    /**
     * Décider sur une proposition de type/catégorie
     */
    public function decideTypeProposal()
    {
        try {
            $service = $this->getPoiTypesService();
            $proposalId = (int)($_POST['proposal_id'] ?? 0);
            $decision = trim($_POST['decision'] ?? '');
            $userId = (int)($this->user->attributes['id'] ?? 0);
            
            if ($proposalId <= 0 || !in_array($decision, ['approved', 'rejected'])) {
                \Utils\Show::msg('Données invalides.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit-type-proposals'));
                return;
            }
            
            $result = false;
            if ($decision === 'approved') {
                $result = $service->approveProposal($proposalId, $userId);
                $msg = 'Proposition approuvée et type/catégorie créé(e).';
            } else {
                $result = $service->rejectProposal($proposalId, $userId);
                $msg = 'Proposition rejetée.';
            }
            
            if ($result) {
                \Utils\Show::msg($msg, 'success');
            } else {
                \Utils\Show::msg('Erreur lors du traitement de la proposition.', 'error');
            }
            
            $this->core->redirect($this->router->generate('admin-poi-edit-type-proposals'));
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::decideTypeProposal - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('admin-poi-edit-type-proposals'));
        }
    }

    /**
     * Lit les paramètres depuis param.json
     */
    private function getParams(): array
    {
        $paramFile = DATA_PLUGIN . 'poi-edit' . DIRECTORY_SEPARATOR . 'param.json';
        $params = \Utils\Util::readJsonFile($paramFile);
        if (!is_array($params)) {
            $params = ['helpUrl' => ''];
        }
        return $params;
    }

    /**
     * Sauvegarde les paramètres dans param.json
     */
    private function saveParams(array $params): bool
    {
        $paramFile = DATA_PLUGIN . 'poi-edit' . DIRECTORY_SEPARATOR . 'param.json';
        return \Utils\Util::writeJsonFile($paramFile, $params);
    }

    /**
     * Liste des tags personnalisés
     */
    public function customTags()
    {
        try {
            $service = $this->getPoiEditService();
            $tagsRaw = $service->getAllCustomTags();
            
            // Préparer les données pour le template
            $tags = [];
            foreach ($tagsRaw as $tag) {
                $tags[] = [
                    'id' => $tag['id'],
                    'key_name' => $tag['key_name'],
                    'emoji' => !empty($tag['emoji']) ? $tag['emoji'] : '',
                    'has_emoji' => !empty($tag['emoji']),
                    'label' => !empty($tag['label']) ? $tag['label'] : $tag['key_name'],
                    'has_label' => !empty($tag['label']),
                    'default_value' => !empty($tag['default_value']) ? $tag['default_value'] : '-',
                    'has_default_value' => !empty($tag['default_value']),
                    'description' => !empty($tag['description']) ? $tag['description'] : '-',
                    'has_description' => !empty($tag['description']),
                    'is_active' => isset($tag['is_active']) && (int)$tag['is_active'] === 1,
                ];
            }
            
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_custom_tags');
            $tpl->set('tags', $tags);
            
            // URLs pour les actions
            $addUrl = $this->router->generate('admin-poi-edit-add-custom-tag');
            $tpl->set('addUrl', $addUrl);
            
            $response->setTitle('Gestion des tags personnalisés');
            $response->addTemplate($tpl);
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::customTags - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    /**
     * Formulaire d'ajout/édition d'un tag personnalisé
     */
    public function addCustomTag()
    {
        try {
            $service = $this->getPoiEditService();
            $tagRaw = null;
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id > 0) {
                $tagRaw = $service->getCustomTag($id);
                if (!$tagRaw) {
                    \Utils\Show::msg('Tag introuvable.', 'error');
                    $this->core->redirect($this->router->generate('admin-poi-edit-custom-tags'));
                    return;
                }
            }
            
            // Préparer les données pour le template
            $tag = null;
            if ($tagRaw) {
                $tag = [
                    'id' => $tagRaw['id'],
                    'key_name' => $tagRaw['key_name'],
                    'label' => !empty($tagRaw['label']) ? $tagRaw['label'] : '',
                    'has_label' => !empty($tagRaw['label']),
                    'emoji' => !empty($tagRaw['emoji']) ? $tagRaw['emoji'] : '',
                    'has_emoji' => !empty($tagRaw['emoji']),
                    'default_value' => !empty($tagRaw['default_value']) ? $tagRaw['default_value'] : 'yes',
                    'has_default_value' => !empty($tagRaw['default_value']),
                    'description' => !empty($tagRaw['description']) ? $tagRaw['description'] : '',
                    'has_description' => !empty($tagRaw['description']),
                    'is_active' => isset($tagRaw['is_active']) && (int)$tagRaw['is_active'] === 1,
                ];
            }
            
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_custom_tag_form');
            $tpl->set('tag', $tag);
            $tpl->set('hasTag', $tag !== null);
            
            // URLs
            $saveUrl = $this->router->generate('admin-poi-edit-save-custom-tag');
            $cancelUrl = $this->router->generate('admin-poi-edit-custom-tags');
            $tpl->set('saveUrl', $saveUrl);
            $tpl->set('cancelUrl', $cancelUrl);
            
            $response->setTitle($tag ? 'Modifier un tag personnalisé' : 'Ajouter un tag personnalisé');
            $response->addTemplate($tpl);
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::addCustomTag - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    /**
     * Sauvegarde d'un tag personnalisé
     */
    public function saveCustomTag()
    {
        try {
            $service = $this->getPoiEditService();
            $data = [
                'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
                'key_name' => trim($_POST['key_name'] ?? ''),
                'default_value' => trim($_POST['default_value'] ?? ''),
                'emoji' => trim($_POST['emoji'] ?? ''),
                'label' => trim($_POST['label'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0,
            ];
            
            if (empty($data['key_name'])) {
                \Utils\Show::msg('Le nom de la clé est requis.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit-add-custom-tag'));
                return;
            }
            
            if ($service->saveCustomTag($data)) {
                \Utils\Show::msg('Tag enregistré avec succès.', 'success');
            } else {
                \Utils\Show::msg('Erreur lors de l\'enregistrement.', 'error');
            }
            
            $this->core->redirect($this->router->generate('admin-poi-edit-custom-tags'));
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::saveCustomTag - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('admin-poi-edit-custom-tags'));
        }
    }

    /**
     * Édition d'un tag personnalisé
     */
    public function editCustomTag(int $id)
    {
        try {
            $service = $this->getPoiEditService();
            $tagRaw = $service->getCustomTag($id);
            
            if (!$tagRaw) {
                \Utils\Show::msg('Tag introuvable.', 'error');
                $this->core->redirect($this->router->generate('admin-poi-edit-custom-tags'));
                return;
            }
            
            // Préparer les données pour le template
            $tag = [
                'id' => $tagRaw['id'],
                'key_name' => $tagRaw['key_name'],
                'label' => !empty($tagRaw['label']) ? $tagRaw['label'] : '',
                'has_label' => !empty($tagRaw['label']),
                'emoji' => !empty($tagRaw['emoji']) ? $tagRaw['emoji'] : '',
                'has_emoji' => !empty($tagRaw['emoji']),
                'default_value' => !empty($tagRaw['default_value']) ? $tagRaw['default_value'] : 'yes',
                'has_default_value' => !empty($tagRaw['default_value']),
                'description' => !empty($tagRaw['description']) ? $tagRaw['description'] : '',
                'has_description' => !empty($tagRaw['description']),
                'is_active' => isset($tagRaw['is_active']) && (int)$tagRaw['is_active'] === 1,
            ];
            
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('PoiEdit', 'admin_custom_tag_form');
            $tpl->set('tag', $tag);
            $tpl->set('hasTag', true);
            
            // URLs
            $saveUrl = $this->router->generate('admin-poi-edit-save-custom-tag');
            $cancelUrl = $this->router->generate('admin-poi-edit-custom-tags');
            $tpl->set('saveUrl', $saveUrl);
            $tpl->set('cancelUrl', $cancelUrl);
            
            $response->setTitle('Modifier un tag personnalisé');
            $response->addTemplate($tpl);
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::editCustomTag - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    /**
     * Suppression d'un tag personnalisé
     */
    public function deleteCustomTag(int $id)
    {
        try {
            $service = $this->getPoiEditService();
            if ($service->deleteCustomTag($id)) {
                \Utils\Show::msg('Tag supprimé avec succès.', 'success');
            } else {
                \Utils\Show::msg('Erreur lors de la suppression.', 'error');
            }
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::deleteCustomTag - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
        }
        
        $this->core->redirect($this->router->generate('admin-poi-edit-custom-tags'));
    }

    /**
     * Upload une photo (admin)
     */
    public function uploadPhoto()
    {
        // Désactiver la sortie de buffer pour éviter tout HTML avant le JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $service = $this->getPoiEditService();
            $rawPoiId = $_POST['poi_id'] ?? '';
            
            // Handle any numeric ID (positive, negative, large)
            if (is_numeric($rawPoiId) && $rawPoiId != 0) {
                $poiId = $rawPoiId;
                // Cast to int if it fits in PHP_INT range
                if (abs((float)$rawPoiId) <= PHP_INT_MAX) {
                    $poiId = (int)$rawPoiId;
                }
            } else {
                $poiId = 0;
            }
            
            if (empty($poiId)) {
                echo json_encode(['success' => false, 'message' => 'POI invalide (ID reçu: ' . htmlspecialchars($rawPoiId) . ')'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Erreur d\'upload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $userId = (int)($this->user->attributes['id'] ?? 0);
            // Convertir l'ID CMS en ID MySQL
            $mysqlUserId = null;
            if ($userId > 0) {
                $userEmail = $this->user->attributes['email'] ?? null;
                $username = $this->user->attributes['username'] ?? null;
                if (!class_exists('\Claim\Services\ClaimService')) {
                    require_once PLUGINS . 'claim/services/ClaimService.php';
                }
                $pdo = $this->getPdo();
                $claimService = new \Claim\Services\ClaimService($pdo);
                $mysqlUserId = $claimService->ensureUserExists($userId, $userEmail ?? '', $username ?? '');
            }
            
            $uploadDir = UPLOAD . 'poi-edit' . DS;
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'photo_' . $poiId . '_' . time() . '_' . uniqid() . '.' . $ext;
            $filepath = $uploadDir . $filename;
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
                echo json_encode(['success' => false, 'message' => 'Erreur de sauvegarde'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $url = '/upload/poi-edit/' . $filename;
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
            $this->core->getLogger()->error('PoiEditAdminController::uploadPhoto - ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    /**
     * Supprime une photo (admin)
     */
    public function deletePhoto(int $photoId)
    {
        // Désactiver la sortie de buffer pour éviter tout HTML avant le JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $this->core->getLogger()->debug('PoiEditAdminController::deletePhoto - photoId: ' . $photoId . ', POST: ' . print_r($_POST, true));
            
            $service = $this->getPoiEditService();
            $poiId = (int)($_POST['poi_id'] ?? 0);
            
            if ($poiId <= 0) {
                $this->core->getLogger()->error('PoiEditAdminController::deletePhoto - POI invalide: ' . $poiId);
                echo json_encode(['success' => false, 'message' => 'POI invalide'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Pour un admin, on peut passer null pour bypasser la vérification de permissions
            // car l'admin a déjà été vérifié dans le contrôleur (via AdminController)
            $this->core->getLogger()->debug('PoiEditAdminController::deletePhoto - Tentative suppression photoId: ' . $photoId . ', poiId: ' . $poiId . ' (admin)');
            
            // Passer null pour userId permet de bypasser la vérification de permissions
            // car on est déjà dans un contrôleur admin (l'utilisateur est forcément admin)
            $result = $service->deletePhoto($photoId, $poiId, null);
            
            if ($result) {
                $this->core->getLogger()->debug('PoiEditAdminController::deletePhoto - Photo supprimée avec succès');
                echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $this->core->getLogger()->error('PoiEditAdminController::deletePhoto - Échec de la suppression (service retourné false)');
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression (vérifiez les permissions ou que la photo existe)'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            exit;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::deletePhoto - Exception: ' . $e->getMessage() . ' - Stack: ' . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Une erreur est survenue: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    /**
     * Met à jour la légende d'une photo (admin)
     */
    public function updatePhotoCaption()
    {
        // Désactiver la sortie de buffer pour éviter tout HTML avant le JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $service = $this->getPoiEditService();
            $photoId = (int)($_POST['photo_id'] ?? 0);
            $poiId = (int)($_POST['poi_id'] ?? 0);
            $caption = isset($_POST['caption']) ? trim($_POST['caption']) : null;
            
            if ($photoId <= 0 || $poiId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Paramètres invalides'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $userId = (int)($this->user->attributes['id'] ?? 0);
            // Convertir l'ID CMS en ID MySQL
            $mysqlUserId = null;
            if ($userId > 0) {
                $userEmail = $this->user->attributes['email'] ?? null;
                $username = $this->user->attributes['username'] ?? null;
                if (!class_exists('\Claim\Services\ClaimService')) {
                    require_once PLUGINS . 'claim/services/ClaimService.php';
                }
                $pdo = $this->getPdo();
                $claimService = new \Claim\Services\ClaimService($pdo);
                $mysqlUserId = $claimService->ensureUserExists($userId, $userEmail ?? '', $username ?? '');
            }
            
            if ($service->updatePhotoCaption($photoId, $poiId, $caption, $mysqlUserId)) {
                echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            exit;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('PoiEditAdminController::updatePhotoCaption - ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Une erreur est survenue'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

}

