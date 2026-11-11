<?php

namespace PoiEdit\Services;

use PoiEdit\Entities\Description;
use PoiEdit\Entities\OwnerActivity;
use PoiEdit\Entities\PoiSubcategory;
use PoiEdit\Entities\CustomTag;
use PoiData\Entities\PoiTag;
use PoiData\Entities\PoiPhoto;
use PoiData\Entities\PoiOpeningHour;
use Core\Core;

defined('ROOT') or exit('Access denied!');

class PoiEditService
{
    private ?Core $core;

    public function __construct(?Core $core = null)
    {
        $this->core = $core ?? (class_exists('\Core\Core') ? \Core\Core::getInstance() : null);
    }

    private function log(string $message, string $level = 'info'): void
    {
        if ($this->core && method_exists($this->core, 'getLogger')) {
            $this->core->getLogger()->$level($message);
        } else {
            error_log($message);
        }
    }

    public function getPoi(int $poiId): ?array
    {
        if (class_exists('\PoiData\Entities\Poi')) {
            $poi = \PoiData\Entities\Poi::findPK($poiId);
            return $poi ? $poi->toArray() : null;
        }
        return null;
    }

    public function getPoiById($poiId): ?array
    {
        return $this->getPoi((int)$poiId);
    }

    public function getPoiTags($poiId): array
    {
        if (class_exists('\PoiData\Entities\PoiTag')) {
            return \PoiData\Entities\PoiTag::query()->where('poi_id', '=', $poiId)->get();
        }
        return [];
    }

    public function getPoiPhotos($poiId): array
    {
        if (class_exists('\PoiData\Entities\PoiPhoto')) {
            return \PoiData\Entities\PoiPhoto::query()
                ->where('poi_id', '=', $poiId)
                ->orderBy('is_main', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->get();
        }
        return [];
    }

    public function getPoiDescription($poiId): ?string
    {
        $desc = Description::query()->where('poi_id', '=', $poiId)->first();
        if ($desc) return $desc->content;
        
        $poi = $this->getPoi($poiId);
        return $poi['description'] ?? null;
    }

    public function getPoiLinks($poiId): array
    {
        return \PoiEdit\Entities\PoiLink::query()->where('poi_id', '=', $poiId)->orderBy('created_at', 'DESC')->get();
    }

    public function getPoiOpeningHours($poiId): array
    {
        if (class_exists('\PoiData\Entities\PoiOpeningHour')) {
            return \PoiData\Entities\PoiOpeningHour::query()
                ->where('poi_id', '=', $poiId)
                ->orderBy('day_of_week', 'ASC')
                ->orderBy('open_time', 'ASC')
                ->get();
        }
        return [];
    }

    public function countPoiLinksByOwner($poiId, int $ownerId): int
    {
        return \PoiEdit\Entities\PoiLink::query()
            ->where('poi_id', '=', $poiId)
            ->where('created_by', '=', $ownerId)
            ->count();
    }

    public function countPoiPhotosByOwner($poiId, int $ownerId): int
    {
        if (class_exists('\PoiData\Entities\PoiPhoto')) {
            return \PoiData\Entities\PoiPhoto::query()
                ->where('poi_id', '=', $poiId)
                ->where('uploaded_by', '=', $ownerId)
                ->count();
        }
        return 0;
    }

    public function countPoiCustomTagsByOwner($poiId, int $ownerId): int
    {
        if (class_exists('\PoiData\Entities\PoiTag')) {
            $excludedKeys = ['logo', 'background_color', 'stars', '_normalized'];
            return \PoiData\Entities\PoiTag::query()
                ->where('poi_id', '=', $poiId)
                ->where('created_by', '=', $ownerId)
                ->where('key_name', 'NOT IN', $excludedKeys)
                ->count();
        }
        return 0;
    }

    public function countLogoChangesThisWeek($poiId, int $ownerId): int
    {
        return OwnerActivity::query()
            ->where('poi_id', '=', $poiId)
            ->where('user_id', '=', $ownerId)
            ->where('action', '=', 'logo_change')
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->count();
    }

    public function countPhotoUploadsThisWeek($poiId, int $ownerId): int
    {
        return OwnerActivity::query()
            ->where('poi_id', '=', $poiId)
            ->where('user_id', '=', $ownerId)
            ->where('action', '=', 'photo_upload')
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->count();
    }

    private function logOwnerActivity($poiId, int $ownerId, string $activityType): void
    {
        $log = new OwnerActivity([
            'poi_id' => $poiId,
            'user_id' => $ownerId,
            'action' => $activityType
        ]);
        $log->save();
    }

    public function getPoiTag($poiId, string $keyName): ?string
    {
        if (class_exists('\PoiData\Entities\PoiTag')) {
            $tag = \PoiData\Entities\PoiTag::query()
                ->where('poi_id', '=', $poiId)
                ->where('key_name', '=', $keyName)
                ->first();
            return $tag ? $tag->value_text : null;
        }
        return null;
    }

    public function setPoiTag($poiId, string $keyName, ?string $value, ?int $createdBy = null): bool
    {
        if (!class_exists('\PoiData\Entities\PoiTag')) return false;

        $oldValue = null;
        if ($keyName === 'logo' && $createdBy !== null) {
            $oldValue = $this->getPoiTag($poiId, 'logo');
        }

        if ($value === null || $value === '') {
            $tag = \PoiData\Entities\PoiTag::query()
                ->where('poi_id', '=', $poiId)
                ->where('key_name', '=', $keyName)
                ->first();
            if ($tag) {
                $tag->delete();
                if ($keyName === 'logo' && $createdBy !== null && $oldValue !== null) {
                    $this->logOwnerActivity($poiId, $createdBy, 'logo_change');
                }
                return true;
            }
            return false;
        }

        $tag = \PoiData\Entities\PoiTag::query()
            ->where('poi_id', '=', $poiId)
            ->where('key_name', '=', $keyName)
            ->first();
            
        if (!$tag) {
            $tag = new \PoiData\Entities\PoiTag([
                'poi_id' => $poiId,
                'key_name' => $keyName
            ]);
        }
        
        $tag->value_text = $value;
        $tag->created_by = $createdBy;
        $result = $tag->save();

        if ($keyName === 'logo' && $createdBy !== null && $oldValue !== $value) {
            $this->logOwnerActivity($poiId, $createdBy, 'logo_change');
        }
        
        if ($result) {
            try {
                if (class_exists('\PoiManager\Services\PoiManagerService')) {
                    $manager = new \PoiManager\Services\PoiManagerService();
                    $manager->updateDatascore($poiId);
                }
            } catch (\Exception $e) {}
        }
        
        return $result;
    }

    public function setPoiLink($poiId, string $url, ?string $label = null, ?int $linkId = null, ?int $createdBy = null): bool
    {
        if (empty($url)) {
            if ($linkId) {
                $link = \PoiEdit\Entities\PoiLink::findPK($linkId);
                if ($link && $link->poi_id == $poiId) return $link->delete();
            }
            return false;
        }

        if ($linkId) {
            $link = \PoiEdit\Entities\PoiLink::findPK($linkId);
            if (!$link || $link->poi_id != $poiId) return false;
        } else {
            $link = new \PoiEdit\Entities\PoiLink(['poi_id' => $poiId, 'created_by' => $createdBy]);
        }

        $link->url = $url;
        $link->label = $label;
        return $link->save();
    }

    public function isOwner(int $poiId, int $userId): bool
    {
        if (class_exists('\Claim\Services\ClaimService')) {
            $claimService = new \Claim\Services\ClaimService();
            // Note: On suppose que ClaimService a une méthode isOwner ou équivalent
            return method_exists($claimService, 'isOwner') ? $claimService->isOwner($poiId, $userId) : false;
        }
        return false;
    }

    public function isAdmin(int $userId): bool
    {
        $user = \Core\Auth\User::findPK($userId);
        if ($user) {
            return \Core\Navigation\AccessPolicyEvaluator::getInstance()->isAdmin(
                new \Core\Navigation\ConditionContext($user)
            );
        }
        return false;
    }

    public function getEditPermission(int $poiId, ?int $userId = null): string
    {
        if (!$userId) return 'none';
        if ($this->isAdmin($userId)) return 'admin';
        if ($this->isOwner($poiId, $userId)) return 'edit';
        return 'suggest';
    }

    public function createSuggestion(int $poiId, int $userId, string $fieldName, ?string $oldValue, ?string $newValue): int
    {
        try {
            // Check gamification level to see if user bypasses moderation
            $status = 'pending';
            if (class_exists('\Gamification\Services\PointsService')) {
                $pointsService = new \Gamification\Services\PointsService();
                $profile = $pointsService->getUserPalier($userId);
                $level = $profile['id'] ?? 1;
                if ($level >= 4) {
                    $status = 'approved';
                }
            }

            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO poi_suggestions (poi_id, user_id, field_name, old_value, new_value, status, created_at, updated_at)
                VALUES (:poi_id, :user_id, :field_name, :old_value, :new_value, :status, :created_at, :updated_at)
            ");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                ':poi_id' => $poiId,
                ':user_id' => $userId,
                ':field_name' => $fieldName,
                ':old_value' => $oldValue,
                ':new_value' => $newValue,
                ':status' => $status,
                ':created_at' => $now,
                ':updated_at' => $now
            ]);
            $suggestionId = (int)$pdo->lastInsertId();

            // Auto-apply if approved
            if ($status === 'approved' && $suggestionId > 0) {
                // Here we actually should update the POI.
                // We'll call a method to apply it.
                $this->applySuggestion($suggestionId);
            }

            return $suggestionId;
        } catch (\Exception $e) {
            $this->log("PoiEditService::createSuggestion - ERROR: " . $e->getMessage(), 'error');
            return 0;
        }
    }

    public function applySuggestion(int $suggestionId): bool
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM poi_suggestions WHERE id = :id AND status = 'approved'");
            $stmt->execute([':id' => $suggestionId]);
            $suggestion = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$suggestion) return false;

            $poiId = (int)$suggestion['poi_id'];
            $fieldName = $suggestion['field_name'];
            $newValue = $suggestion['new_value'];

            // We use the existing updatePoi method, simulating a system/admin update
            // We pass 'admin' permission to ensure it can update any field.
            return $this->updatePoi($poiId, [$fieldName => $newValue], 'admin');
        } catch (\Exception $e) {
            $this->log("PoiEditService::applySuggestion - ERROR: " . $e->getMessage(), 'error');
            return false;
        }
    }

    public function createProposal(array $data, int $userId): int
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            
            $data['user_id'] = $userId;
            $data['status'] = 'pending';
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $columns = array_keys($data);
            $valuesParam = array_map(function($col) { return ':' . $col; }, $columns);
            
            $sql = sprintf(
                "INSERT INTO poi_proposals (%s) VALUES (%s)",
                implode(', ', $columns),
                implode(', ', $valuesParam)
            );
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            
            return (int)$pdo->lastInsertId();
        } catch (\Exception $e) {
            $this->log("PoiEditService::createProposal - ERROR: " . $e->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Crée un POI directement en base (admin uniquement).
     * Ne déclenche PAS les hooks de gamification.
     * 
     * @param array $data Champs du POI (name, lat, lon, category, subcategory, etc.)
     * @return int L'ID du POI créé, ou 0 en cas d'échec
     */
    public function createPoiDirect(array $data): int
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();

            // poi_public.id n'a PAS d'AUTO_INCREMENT — générer un ID négatif unique
            // (convention admin : jamais en conflit avec les IDs OSM qui sont positifs)
            $stmtMin = $pdo->query("SELECT COALESCE(MIN(id), 0) FROM poi_public WHERE id < 0");
            $minId   = (int)$stmtMin->fetchColumn();
            $newId   = ($minId < 0) ? ($minId - 1) : -1;

            $lat = isset($data['lat']) && $data['lat'] !== '' ? (float)$data['lat'] : 0.0;
            $lon = isset($data['lon']) && $data['lon'] !== '' ? (float)$data['lon'] : 0.0;

            // Mappage form → colonnes BDD (scalaires uniquement, location gérée séparément)
            $fieldMapping = [
                'name'            => 'name',
                'category'        => 'category',
                'subcategory'     => 'subcategory',
                'street'          => 'addr_street',
                'housenumber'     => 'addr_housenumber',
                'postcode'        => 'addr_postcode',
                'city'            => 'addr_city',
                'phone'           => 'contact_phone',
                'email'           => 'contact_email',
                'website'         => 'website',
                'opening_hours'   => 'opening_hours',
                'wheelchair'      => 'wheelchair',
                'internet_access' => 'internet_access',
                'parking'         => 'parking',
                'fee'             => 'fee',
                'indoor'          => 'indoor',
                'vending'         => 'vending',
                'brand'           => 'brand',
            ];

            $poiData = [
                'id'         => $newId,
                'lat'        => $lat,
                'lon'        => $lon,
                'source'     => 'admin',
                'status'     => 'public',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            foreach ($fieldMapping as $formField => $dbCol) {
                if (isset($data[$formField]) && $data[$formField] !== '') {
                    $poiData[$dbCol] = $data[$formField];
                }
            }

            // location est NOT NULL et doit être POINT(lon, lat) — non-bindable via PDO parameter
            $columns      = array_keys($poiData);
            $valuesParams = array_map(fn($col) => ':' . $col, $columns);
            $colsSql      = implode(', ', $columns) . ', location';
            $valsSql      = implode(', ', $valuesParams) . ', POINT(:loc_lon, :loc_lat)';

            $stmt = $pdo->prepare("INSERT INTO poi_public ({$colsSql}) VALUES ({$valsSql})");
            foreach ($poiData as $col => $val) {
                $stmt->bindValue(':' . $col, $val);
            }
            $stmt->bindValue(':loc_lon', $lon);
            $stmt->bindValue(':loc_lat', $lat);
            $stmt->execute();

            // Le trigger AFTER INSERT alimente poi_search automatiquement — pas de INSERT manuel
            $this->log("PoiEditService::createPoiDirect - POI créé: id={$newId}, name=" . ($poiData['name'] ?? '?'));
            return $newId;

        } catch (\Exception $e) {
            $msg = "PoiEditService::createPoiDirect - ERROR: " . $e->getMessage();
            $this->log($msg, 'error');
            error_log($msg);
            throw $e;
        }
    }

    public function setPoiSubcategories(int $poiId, array $subcategories): bool
    {
        try {
            $existing = PoiSubcategory::query()->where('poi_id', '=', $poiId)->get();
            foreach ($existing as $item) $item->delete();

            foreach ($subcategories as $subcategory) {
                $subcategory = trim($subcategory);
                if (!empty($subcategory)) {
                    $item = new PoiSubcategory(['poi_id' => $poiId, 'subcategory' => $subcategory]);
                    $item->save();
                }
            }
            if (class_exists('\PoiManager\Services\PoiManagerService')) {
                $manager = new \PoiManager\Services\PoiManagerService();
                $manager->updateDatascore($poiId);
            }
            return true;
        } catch (\Exception $e) {
            $this->log("PoiEditService::setPoiSubcategories - Error: " . $e->getMessage(), 'error');
            return false;
        }
    }

    public function getPoiSubcategories(int $poiId): array
    {
        $results = PoiSubcategory::query()->where('poi_id', '=', $poiId)->get();
        return array_column($results, 'subcategory');
    }

    public function setPoiOpeningHours(int $poiId, array $hours): bool
    {
        if (!class_exists('\PoiData\Entities\PoiOpeningHour')) return false;

        try {
            // Supprimer les horaires existants
            $existing = \PoiData\Entities\PoiOpeningHour::query()->where('poi_id', '=', $poiId)->get();
            foreach ($existing as $item) {
                $item->delete();
            }

            // Ajouter les nouveaux horaires
            $result = true;
            foreach ($hours as $h) {
                if (empty($h['day_of_week']) && !isset($h['day_of_week'])) continue;
                
                $openingHour = new \PoiData\Entities\PoiOpeningHour([
                    'poi_id' => $poiId,
                    'day_of_week' => (int)$h['day_of_week'],
                    'open_time' => $h['open_time'] ?? null,
                    'close_time' => $h['close_time'] ?? null,
                    'is_closed' => !empty($h['is_closed'])
                ]);
                $result = $result && $openingHour->save();
            }
            if ($result) {
                try {
                    if (class_exists('\PoiManager\Services\PoiManagerService')) {
                        $manager = new \PoiManager\Services\PoiManagerService();
                        $manager->updateDatascore($poiId);
                    }
                } catch (\Exception $e) {}
            }
            return $result;
        } catch (\Exception $e) {
            $this->log("PoiEditService::setPoiOpeningHours - Error: " . $e->getMessage(), 'error');
            return false;
        }
    }

    public function updatePoi(int $poiId, array $fields, string $permission = 'admin'): bool
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            
            // Check if POI exists first
            $stmt = $pdo->prepare("SELECT * FROM poi_public WHERE id = :id");
            $stmt->execute([':id' => $poiId]);
            $existingPoi = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$existingPoi) return false;

            $fieldMapping = [
                'name' => 'name',
                'category' => 'category', // Family
                'subcategory' => 'subcategory', // Specialty
                'street' => 'addr_street',
                'housenumber' => 'addr_housenumber',
                'postcode' => 'addr_postcode',
                'city' => 'addr_city',
                'phone' => 'contact_phone',
                'email' => 'contact_email',
                'website' => 'website',
                'opening_hours' => 'opening_hours',
                'wheelchair' => 'wheelchair',
                'internet_access' => 'internet_access',
                'parking' => 'parking',
                'fee' => 'fee',
                'indoor' => 'indoor',
                'vending' => 'vending',
                'brand' => 'brand',
                'lat' => 'lat',
                'lon' => 'lon',
            ];

            $allowedFieldsByPermission = [
                'admin' => array_keys($fieldMapping),
                'edit' => array_keys($fieldMapping),
                'suggest' => ['street', 'housenumber', 'postcode', 'city'],
            ];

            $allowedFields = $allowedFieldsByPermission[$permission] ?? [];
            $hasChanged = false;
            
            // Si 'edit' mais pas owner, limiter via Gamification
            $allowedFieldsGamification = null;
            if ($permission === 'edit' && isset($fields['_user_id'])) {
                $isOwner = $this->isOwner($poiId, $fields['_user_id']);
                if (!$isOwner) {
                    $gamificationService = new \Gamification\Services\PointsService();
                    $profile = get_object_vars($gamificationService->getUserPalier($fields['_user_id'])) ?? [];
                    // getUserPalier returns an array currently based on createSuggestion logic
                    $profile = is_array($profile) ? $profile : [];
                    $level = $profile['id'] ?? 1;
                    
                    $allowedFieldsGamification = ['name', 'category', 'subcategory'];
                    if ($level >= 2) $allowedFieldsGamification = array_merge($allowedFieldsGamification, ['street', 'housenumber', 'postcode', 'city']);
                    if ($level >= 3) $allowedFieldsGamification = array_merge($allowedFieldsGamification, ['phone', 'email', 'website']);
                    if ($level >= 4) $allowedFieldsGamification = array_merge($allowedFieldsGamification, ['opening_hours', 'wheelchair', 'internet_access']);
                }
            }

            $updateParts = [];
            $updateValues = [':id' => $poiId];

            foreach ($fields as $field => $value) {
                if ($field === '_user_id') continue; // Meta champ interne
                $isAllowed = in_array($field, $allowedFields);
                if ($isAllowed && $allowedFieldsGamification !== null) {
                    $isAllowed = in_array($field, $allowedFieldsGamification);
                }

                if ($isAllowed && isset($fieldMapping[$field])) {
                    $dbColumn = $fieldMapping[$field];
                    $updateParts[] = "$dbColumn = :$dbColumn";
                    $updateValues[":$dbColumn"] = $value;
                    $existingPoi[$dbColumn] = $value; // Update local array for hooks
                    $hasChanged = true;
                }
            }

            if (!$hasChanged) return false;
            
            $updateParts[] = "updated_at = :updated_at";
            $updateValues[":updated_at"] = date('Y-m-d H:i:s');

            $sql = "UPDATE poi_public SET " . implode(', ', $updateParts) . " WHERE id = :id";
            $stmtUpdate = $pdo->prepare($sql);
            $result = $stmtUpdate->execute($updateValues);
            
            if ($result) {
                // Update poi_search category/subcategory if changed
                if (isset($fields['category']) || isset($fields['subcategory'])) {
                    $category   = $existingPoi['category'] ?? '';
                    $subcategory = $existingPoi['subcategory'] ?? '';
                    try {
                        $stmtSearch = $pdo->prepare("UPDATE poi_search SET category = :category, subcategory = :subcategory WHERE poi_id = :id");
                        $stmtSearch->execute([
                            ':category' => $category,
                            ':subcategory' => $subcategory,
                            ':id' => $poiId
                        ]);
                    } catch (\Exception $e) {}
                }

                $this->updateNormalizedStructure($poiId);
                
                try {
                    if ($this->core) {
                        $this->core->callHook('poi:updated', ['poi_id' => $poiId, 'poi' => $existingPoi]);
                    }
                } catch (\Exception $e) {}
                
                $this->notifyPoiSubscribers($poiId);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->log("PoiEditService::updatePoi - ERROR: " . $e->getMessage(), 'error');
            return false;
        }
    }

    private function notifyPoiSubscribers(int $poiId): void
    {
        try {
            $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
            $favoritesPlugin = $pluginsManager->getPlugin('poi-favorites');
            if (!$favoritesPlugin || !$favoritesPlugin->getConfigVal('activate')) return;
            if ($favoritesPlugin->getConfigVal('notifyOnPoiUpdate') === false) return;
            
            if (file_exists(PLUGINS . 'poi-favorites/services/NotificationService.php')) {
                require_once PLUGINS . 'poi-favorites/services/NotificationService.php';
                $notificationService = new \PoiFavorites\Services\NotificationService();
                $notificationService->notifyPoiSubscribers($poiId, 'poi_updated', null);
            }
        } catch (\Exception $e) {
            $this->log('PoiEditService::notifyPoiSubscribers - Erreur: ' . $e->getMessage(), 'error');
        }
    }


    private function updateNormalizedStructure(int $poiId): void
    {
        try {
            if (!class_exists('\Osm\Services\PoiToNormalizedAdapter')) return;

            $poiRow = $this->getPoiById($poiId);
            if (!$poiRow) return;

            $description = $this->getPoiDescription($poiId);
            if ($description) $poiRow['description'] = $description;

            $adapterClass = '\Osm\Services\PoiToNormalizedAdapter';
            $normalized = call_user_func([$adapterClass, 'build'], $poiRow);
            $structured = array_intersect_key($normalized, array_flip(['contact', 'address', 'accessibility', 'cuisine', 'meta']));

            if (!empty($structured)) {
                $normalizedJson = json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->setPoiTag($poiId, '_normalized', $normalizedJson);
            }
        } catch (\Exception $e) {
            $this->log('Erreur lors de la mise à jour de la structure normalisée pour POI #' . $poiId . ': ' . $e->getMessage(), 'warning');
        }
    }

    public function saveDescription(int $poiId, string $description, ?int $userId = null): bool
    {
        $desc = Description::query()->where('poi_id', '=', $poiId)->first();
        if (!$desc) {
            $desc = new Description(['poi_id' => $poiId]);
        }
        $desc->content = $description;
        $desc->author_id = $userId;
        $result = $desc->save();
        
        if ($result) {
            $this->updateNormalizedStructure($poiId);
            try {
                if (class_exists('\PoiManager\Services\PoiManagerService')) {
                    $manager = new \PoiManager\Services\PoiManagerService();
                    $manager->updateDatascore($poiId);
                }
            } catch (\Exception $e) {}
        }
        return $result;
    }

    public function addPhoto(int $poiId, string $url, ?string $caption = null, ?int $userId = null): int
    {
        if (class_exists('\PoiData\Entities\PoiPhoto')) {
            $photo = new \PoiData\Entities\PoiPhoto([
                'poi_id' => $poiId,
                'url' => $url,
                'caption' => $caption,
                'uploaded_by' => $userId
            ]);
            if ($photo->save()) {
                if ($userId !== null) $this->logOwnerActivity($poiId, $userId, 'photo_upload');
                
                try {
                    if (class_exists('\PoiManager\Services\PoiManagerService')) {
                        $manager = new \PoiManager\Services\PoiManagerService();
                        $manager->updateDatascore($poiId);
                    }
                } catch (\Exception $e) {}
                
                return $photo->id;
            }
        }
        return 0;
    }

    public function updatePhotoCaption(int $photoId, int $poiId, ?string $caption, ?int $userId = null): bool
    {
        if (class_exists('\PoiData\Entities\PoiPhoto')) {
            $photo = \PoiData\Entities\PoiPhoto::findPK($photoId);
            if (!$photo || $photo->poi_id != $poiId) return false;

            if ($userId !== null) {
                if (!$this->isAdmin($userId) && !$this->isOwner($poiId, $userId)) return false;
            }

            $photo->caption = $caption;
            return $photo->save();
        }
        return false;
    }

    public function deletePhoto(int $photoId, int $poiId, ?int $userId = null): bool
    {
        if (class_exists('\PoiData\Entities\PoiPhoto')) {
            $photo = \PoiData\Entities\PoiPhoto::findPK($photoId);
            if (!$photo || $photo->poi_id != $poiId) return false;

            if ($userId !== null) {
                if (!$this->isAdmin($userId) && !$this->isOwner($poiId, $userId)) return false;
            }

            $result = $photo->delete();
            if ($result) {
                try {
                    if (class_exists('\PoiManager\Services\PoiManagerService')) {
                        $manager = new \PoiManager\Services\PoiManagerService();
                        $manager->updateDatascore($poiId);
                    }
                } catch (\Exception $e) {}
            }
            return $result;
        }
        return false;
    }

    public function getPendingSuggestions(int $limit = 50, int $offset = 0): array
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM poi_suggestions WHERE status = 'pending' ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getPendingSuggestionsGroupedByPoi(int $limit = 50, int $offset = 0): array
    {
        $all = $this->getPendingSuggestions(1000, 0); // Temporary manual grouping
        $grouped = [];
        foreach ($all as $s) {
            $id = (string)$s['poi_id'];
            if (!isset($grouped[$id])) {
                $poi = $this->getPoi($s['poi_id']);
                $grouped[$id] = [
                    'poi_id' => $s['poi_id'],
                    'poi_name' => $poi['name'] ?? 'Inconnu',
                    'suggestions' => [],
                    'suggestion_ids' => [],
                    'created_at' => $s['created_at']
                ];
            }
            $grouped[$id]['suggestions'][] = [
                'id' => $s['id'],
                'field_name' => $s['field_name'],
                'old_value' => $s['old_value'],
                'new_value' => $s['new_value']
            ];
            $grouped[$id]['suggestion_ids'][] = $s['id'];
        }
        return array_slice(array_values($grouped), $offset, $limit);
    }

    public function decideAllSuggestionsForPoi(int $poiId, string $decision, int $reviewedBy): bool
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT id FROM poi_suggestions WHERE poi_id = :id AND status = 'pending'");
            $stmt->execute([':id' => $poiId]);
            $suggestions = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($suggestions)) return false;

            foreach ($suggestions as $s) {
                $this->decideSuggestion($s, $decision, $reviewedBy);
            }
            return true;
        } catch (\Exception $e) {
            $this->log("PoiEditService::decideAllSuggestionsForPoi - ERROR: " . $e->getMessage(), 'error');
            return false;
        }
    }

    public function decideSuggestion(int $suggestionId, string $decision, int $reviewedBy): bool
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $s = $this->getSuggestion($suggestionId);
            if (!$s) return false;

            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE poi_suggestions SET status = :decision, moderator_id = :mod_id, moderated_at = :mod_at, updated_at = :upd_at WHERE id = :id");
            $result = $stmt->execute([
                ':decision' => $decision,
                ':mod_id'   => $reviewedBy,
                ':mod_at'   => $now,
                ':upd_at'   => $now,
                ':id'       => $suggestionId
            ]);

            if ($result && $decision === 'approved') {
                $this->updatePoi($s['poi_id'], [$s['field_name'] => $s['new_value']]);
            }
            
            try {
                if ($this->core) {
                    $s['status'] = $decision;
                    $s['moderator_id'] = $reviewedBy;
                    $s['moderated_at'] = $now;
                    $this->core->callHook('poi-edit:suggestion-decided', ['suggestion' => $s, 'decision' => $decision]);
                }
            } catch (\Exception $e) {}

            return $result;
        } catch (\Exception $e) {
            $this->log("PoiEditService::decideSuggestion - ERROR: " . $e->getMessage(), 'error');
            return false;
        }
    }

    public function getSuggestion(int $suggestionId): ?array
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM poi_suggestions WHERE id = :id");
            $stmt->execute([':id' => $suggestionId]);
            $res = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getPendingProposals(int $limit = 50, int $offset = 0): array
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM poi_proposals WHERE status = 'pending' ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getProposal(int $proposalId): ?array
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM poi_proposals WHERE id = :id");
            $stmt->execute([':id' => $proposalId]);
            $res = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function approveProposal(int $proposalId, int $reviewedBy): bool
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            
            $proposal = $this->getProposal($proposalId);
            if (!$proposal || $proposal['status'] === 'approved') return false;

            // Générer un ID négatif unique (même convention que createPoiDirect)
            $stmtMin = $pdo->query("SELECT COALESCE(MIN(id), 0) FROM poi_public WHERE id < 0");
            $minId   = (int)$stmtMin->fetchColumn();
            $newPoiId = ($minId < 0) ? ($minId - 1) : -1;

            $lat = (float)($proposal['lat'] ?? 0);
            $lon = (float)($proposal['lon'] ?? 0);

            // Mapping poi_proposals → poi_public (les noms de colonnes diffèrent)
            $poiData = [
                'id'             => $newPoiId,
                'name'           => $proposal['name'] ?? '',
                'lat'            => $lat,
                'lon'            => $lon,
                'category'       => $proposal['category'] ?? null,
                'subcategory'    => $proposal['type'] ?? null,
                'addr_street'    => $proposal['street'] ?? null,
                'addr_housenumber' => $proposal['housenumber'] ?? null,
                'addr_postcode'  => $proposal['postcode'] ?? null,
                'addr_city'      => $proposal['city'] ?? null,
                'contact_phone'  => $proposal['phone'] ?? null,
                'website'        => $proposal['website'] ?? null,
                'opening_hours'  => $proposal['opening_hours'] ?? null,
                'wheelchair'     => $proposal['wheelchair'] ?? null,
                'source'         => 'user',
                'status'         => 'public',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];
            // Retirer les valeurs null pour ne pas insérer de colonnes inutiles
            $poiData = array_filter($poiData, fn($v) => $v !== null);

            // location est NOT NULL et doit être POINT(lon, lat)
            $columns      = array_keys($poiData);
            $valuesParams = array_map(fn($col) => ':' . $col, $columns);
            $colsSql      = implode(', ', $columns) . ', location';
            $valsSql      = implode(', ', $valuesParams) . ', POINT(:loc_lon, :loc_lat)';

            $stmt = $pdo->prepare("INSERT INTO poi_public ({$colsSql}) VALUES ({$valsSql})");
            foreach ($poiData as $col => $val) {
                $stmt->bindValue(':' . $col, $val);
            }
            $stmt->bindValue(':loc_lon', $lon);
            $stmt->bindValue(':loc_lat', $lat);
            $stmt->execute();

            // Mettre à jour le statut de la proposition
            $now = date('Y-m-d H:i:s');
            $stmtUpdate = $pdo->prepare("UPDATE poi_proposals SET status = 'approved', reviewed_by = :mod_id, reviewed_at = :mod_at WHERE id = :id");
            $stmtUpdate->execute([':mod_id' => $reviewedBy, ':mod_at' => $now, ':id' => $proposalId]);

            // Le trigger AFTER INSERT gère poi_search automatiquement
            try {
                if ($this->core) $this->core->callHook('poi:created', ['poi_id' => $newPoiId, 'poi' => $poiData]);
            } catch (\Exception $e) {}

            return true;
        } catch (\Exception $e) {
            $this->log("PoiEditService::approveProposal - ERROR: " . $e->getMessage(), 'error');
            return false;
        }
    }

    public function rejectProposal(int $proposalId, int $reviewedBy): bool
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE poi_proposals SET status = 'rejected', moderator_id = :mod_id, moderated_at = :mod_at, updated_at = :upd_at WHERE id = :id");
            return $stmt->execute([
                ':mod_id' => $reviewedBy,
                ':mod_at' => $now,
                ':upd_at' => $now,
                ':id' => $proposalId
            ]);
        } catch (\Exception $e) {
            $this->log("PoiEditService::rejectProposal - ERROR: " . $e->getMessage(), 'error');
            return false;
        }
    }

    public function getSuggestionStats(): array
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT status, COUNT(*) as status_count FROM poi_suggestions GROUP BY status");
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $stats = ['pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0];
            foreach ($rows as $row) {
                if ($row['status'] === 'pending') $stats['pending_count'] = (int)$row['status_count'];
                if ($row['status'] === 'approved') $stats['approved_count'] = (int)$row['status_count'];
                if ($row['status'] === 'rejected') $stats['rejected_count'] = (int)$row['status_count'];
            }
            return $stats;
        } catch (\Exception $e) {
            return ['pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0];
        }
    }

    public function getProposalsStats(): array
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT status, COUNT(*) as status_count FROM poi_proposals GROUP BY status");
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $stats = ['pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0];
            foreach ($rows as $row) {
                if ($row['status'] === 'pending') $stats['pending_count'] = (int)$row['status_count'];
                if ($row['status'] === 'approved') $stats['approved_count'] = (int)$row['status_count'];
                if ($row['status'] === 'rejected') $stats['rejected_count'] = (int)$row['status_count'];
            }
            return $stats;
        } catch (\Exception $e) {
            return ['pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0];
        }
    }

    public function getContributorsCount(): int
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as cnt FROM (SELECT user_id FROM poi_suggestions UNION ALL SELECT user_id FROM poi_proposals) as u");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int)($row['cnt'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getCustomTags(): array
    {
        return CustomTag::query()->where('is_active', '=', 1)->orderBy('label', 'ASC')->get();
    }

    public function getAllCustomTags(): array
    {
        return CustomTag::query()->orderBy('label', 'ASC')->get();
    }

    public function getCustomTag(int $id): ?array
    {
        $tag = CustomTag::findPK($id);
        return $tag ? $tag->toArray() : null;
    }

    public function saveCustomTag(array $data): bool
    {
        $id = $data['id'] ?? null;
        $tag = $id ? CustomTag::findPK($id) : new CustomTag();
        if (!$tag) return false;
        $tag->fill($data);
        return $tag->save();
    }

    public function deleteCustomTag(int $id): bool
    {
        $tag = CustomTag::findPK($id);
        return $tag ? $tag->delete() : false;
    }
}
