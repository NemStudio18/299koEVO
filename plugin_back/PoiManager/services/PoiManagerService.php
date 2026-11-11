<?php

namespace PoiManager\Services;

defined('ROOT') or exit('Access denied!');

/**
 * PoiManagerService
 *
 * Service principal pour le CRUD direct sur la table poi_public.
 * Utilisé par l'admin et le propriétaire (édition directe).
 *
 * Note : poi_search est synchronisée automatiquement via triggers MySQL.
 */
class PoiManagerService
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Core\Database::getInstance()->getPdo();
    }

    // =========================================================
    // READ
    // =========================================================

    /**
     * Récupère un POI par son ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT p.*, b.name as brand_name 
            FROM poi_public p
            LEFT JOIN poi_brands b ON p.brand_id = b.id
            WHERE p.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Liste les POIs avec pagination et recherche optionnelle.
     */
    public function getList(int $limit = 30, int $offset = 0, string $search = '', string $ownerFilter = 'all'): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(name LIKE ? OR brand LIKE ?)';
            $pattern = '%' . $search . '%';
            $params[] = [$pattern, \PDO::PARAM_STR];
            $params[] = [$pattern, \PDO::PARAM_STR];
        }

        if ($ownerFilter === 'claimed') {
            $where[] = 'claimed_by IS NOT NULL';
        } elseif ($ownerFilter === 'unclaimed') {
            $where[] = 'claimed_by IS NULL';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM poi_public $whereClause ORDER BY updated_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);

        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex++, $param[0], $param[1]);
        }
        $stmt->bindValue($paramIndex++, $limit, \PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $offset, \PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Compte total des POIs (pour pagination).
     */
    public function countAll(string $search = '', string $ownerFilter = 'all'): int
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(name LIKE ? OR brand LIKE ?)';
            $pattern = '%' . $search . '%';
            $params[] = $pattern;
            $params[] = $pattern;
        }

        if ($ownerFilter === 'claimed') {
            $where[] = 'claimed_by IS NOT NULL';
        } elseif ($ownerFilter === 'unclaimed') {
            $where[] = 'claimed_by IS NULL';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM poi_public $whereClause");
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Récupère les POIs dont claimed_by = $userId.
     */
    public function getByOwner(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM poi_public WHERE claimed_by = ? ORDER BY updated_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // =========================================================
    // CREATE
    // =========================================================

    /**
     * Crée un nouveau POI directement dans poi_public.
     * Retourne l'ID inséré ou null en cas d'erreur.
     */
    public function create(array $data, int $adminId): ?int
    {
        $allowed = $this->getAllowedFields();
        $fields  = array_intersect_key($data, array_flip($allowed));

        // Champs obligatoires & valeurs par défaut
        $fields['created_at'] = date('Y-m-d H:i:s');
        $fields['updated_at'] = date('Y-m-d H:i:s');
        $fields['source']     = $fields['source'] ?? 'admin';
        $fields['status']     = $fields['status'] ?? 'public';
        $fields['datascore']  = (int)($fields['datascore'] ?? 50);

        // Générer un ID négatif unique pour les POIs créés par admin
        if (empty($fields['id'])) {
            $fields['id'] = $this->generateAdminId();
        }

        // Résolution de la marque (name -> brand_id)
        if (isset($data['brand'])) {
            $fields['brand_id'] = $this->getOrCreateBrandId($data['brand']);
            unset($fields['brand']);
        }

        // Construction de la requête
        $cols   = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));

        try {
            $stmt = $this->pdo->prepare("INSERT INTO poi_public ($cols) VALUES ($placeholders)");
            $stmt->execute(array_values($fields));
            return (int)$fields['id'];
        } catch (\Exception $e) {
            \Core\Core::getInstance()->getLogger()->error('PoiManagerService::create error: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================
    // UPDATE
    // =========================================================

    /**
     * Calcule le score de complétion (datascore) d'un POI.
     */
    public function calculateDatascore(int $id): int
    {
        $score = 0;
        $poi = $this->getById($id);
        if (!$poi) return 0;

        // 1. Identité de base (20 pts)
        if (!empty($poi['name'])) $score += 5;
        if (!empty($poi['category']) || !empty($poi['subcategory'])) $score += 5;
        if (!empty($poi['addr_street']) && !empty($poi['addr_city'])) $score += 10;

        // 2. Contacts (15 pts) - Tel, Email, Web
        $contacts = $this->getContacts($id);
        if (!empty($contacts['phone'])) $score += 5;
        if (!empty($contacts['email'])) $score += 5;
        if (!empty($contacts['website'])) $score += 5;

        // 3. Présentation (20 pts)
        if (!empty($poi['desc_list'])) $score += 10;
        $description = $this->getDescription($id);
        if (!empty($description)) $score += 10;

        // 4. Photos & Visuels (10 pts)
        $tags = $this->getTags($id);
        $hasLogo = false;
        foreach ($tags as $tag) {
            if ($tag['key_name'] === 'logo' && !empty($tag['value_text'])) {
                $hasLogo = true;
                break;
            }
        }
        $photos = $this->getPhotos($id);
        if ($hasLogo || !empty($photos)) $score += 10;

        // 5. Horaires d'ouverture (10 pts)
        $hours = $this->getPoiOpeningHours($id);
        if (!empty($hours)) $score += 10;

        // 6. Confiance: Bonus Propriétaire (25 pts)
        if (!empty($poi['claimed_by'])) {
             // 25 points bonus de certification/revendication
             $score += 25;
        }

        return min(100, $score);
    }

    /**
     * Met à jour le datascore dans la table poi_public.
     */
    public function updateDatascore(int $id): void
    {
        $score = $this->calculateDatascore($id);
        $this->pdo->prepare('UPDATE poi_public SET datascore = ? WHERE id = ?')->execute([$score, $id]);
    }

    /**
     * Met à jour un POI existant.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = $this->getAllowedFields();
        $fields  = array_intersect_key($data, array_flip($allowed));

        // Toujours mettre à jour updated_at et last_activity_at
        $fields['updated_at']      = date('Y-m-d H:i:s');
        $fields['last_activity_at'] = date('Y-m-d H:i:s');

        // Retirer l'id de la mise à jour
        unset($fields['id']);

        // Résolution de la marque (name -> brand_id)
        if (isset($data['brand'])) {
            $fields['brand_id'] = $this->getOrCreateBrandId($data['brand']);
            unset($fields['brand']);
        }

        if (empty($fields)) {
            // Si pas de champs directs, on recalcul quand même le score au cas où (satellite tables)
            $this->updateDatascore($id);
            return true;
        }

        $sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($fields)));

        try {
            $stmt = $this->pdo->prepare("UPDATE poi_public SET $sets WHERE id = ?");
            $stmt->execute([...array_values($fields), $id]);
            
            // Recalculer le score après mise à jour
            $this->updateDatascore($id);
            
            return true;
        } catch (\Exception $e) {
            \Core\Core::getInstance()->getLogger()->error('PoiManagerService::update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchronise un POI spécifique vers la table d'indexation poi_search.
     * À appeler après avoir sauvegardé toutes les tables satellites.
     */
    public function syncPoiSearch(int $poiId): void
    {
        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            try {
                $poi = $this->getById($poiId);
                if ($poi) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO poi_search (poi_id, name, type, category, subcategory, lat, lon, addr_street, addr_postcode, addr_city, status, is_closed)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON CONFLICT(poi_id) DO UPDATE SET
                            name = excluded.name,
                            type = excluded.type,
                            category = excluded.category,
                            subcategory = excluded.subcategory,
                            lat = excluded.lat,
                            lon = excluded.lon,
                            addr_street = excluded.addr_street,
                            addr_postcode = excluded.addr_postcode,
                            addr_city = excluded.addr_city,
                            status = excluded.status,
                            is_closed = excluded.is_closed
                    ");
                    $stmt->execute([
                        $poi['id'], $poi['name'] ?? '', $poi['type'] ?? '',
                        $poi['category'] ?? '', $poi['subcategory'] ?? '',
                        $poi['lat'] ?? 0, $poi['lon'] ?? 0,
                        $poi['addr_street'] ?? '', $poi['addr_postcode'] ?? '',
                        $poi['addr_city'] ?? '', $poi['status'] ?? 'published',
                        $poi['is_closed'] ?? 0
                    ]);
                }
            } catch (\Exception $e) {
                \Core\Core::getInstance()->getLogger()->warning('PoiManagerService::syncPoiSearch SQLite fallback: ' . $e->getMessage());
            }
            return;
        }

        try {
            $stmt = $this->pdo->prepare('CALL sp_resync_single_poi_search(?)');
            $stmt->execute([$poiId]);
        } catch (\Exception $e) {
            \Core\Core::getInstance()->getLogger()->warning('PoiManagerService::syncPoiSearch error: ' . $e->getMessage());
        }
    }

    /**
     * Marque un POI comme fermé définitivement.
     */
    public function markClosed(int $id, string $reason = ''): bool
    {
        return $this->update($id, [
            'is_closed'    => 1,
            'closed_at'    => date('Y-m-d H:i:s'),
            'closed_reason' => $reason,
        ]);
    }

    // =========================================================
    // TABLES ANNEXES
    // =========================================================

    /**
     * Récupère les photos d'un POI.
     */
    public function getPhotos(int $poiId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM poi_photos WHERE poi_id = ? ORDER BY created_at ASC');
        $stmt->execute([$poiId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les liens d'un POI.
     */
    public function getLinks(int $poiId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM poi_links WHERE poi_id = ? ORDER BY created_at ASC');
        $stmt->execute([$poiId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Sauvegarde les liens d'un POI (supprime les anciens, insère les nouveaux).
     */
    public function saveLinks(int $poiId, array $links, int $userId): void
    {
        $this->pdo->prepare('DELETE FROM poi_links WHERE poi_id = ?')->execute([$poiId]);
        $stmt = $this->pdo->prepare('INSERT INTO poi_links (poi_id, url, label, created_by) VALUES (?, ?, ?, ?)');
        foreach ($links as $link) {
            $url   = trim($link['url'] ?? '');
            $label = trim($link['label'] ?? '');
            if ($url !== '') {
                $stmt->execute([$poiId, $url, $label, $userId]);
            }
        }
        $this->updateDatascore($poiId);
    }

    /**
     * Récupère la description d'un POI.
     */
    public function getDescription(int $poiId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT description FROM poi_descriptions WHERE poi_id = ?');
        $stmt->execute([$poiId]);
        return $stmt->fetchColumn() ?: null;
    }

    // =========================================================
    // HORAIRES AVANCÉS (PoiData)
    // =========================================================

    public function getPoiOpeningHours(int $poiId): array
    {
        if (class_exists('\PoiData\Entities\PoiOpeningHour')) {
            $hours = \PoiData\Entities\PoiOpeningHour::query()
                ->where('poi_id', '=', $poiId)
                ->orderBy('day_of_week', 'ASC')
                ->orderBy('open_time', 'ASC')
                ->get();
            
            // On convertit les objets en tableaux simples pour que le template Twig (qui ne gère
            // pas toujours bien les attributs magiques ou les objets non-standards) puisse
            // lire proprement 'id', 'day_of_week', etc.
            $result = [];
            foreach ($hours as $h) {
                if (method_exists($h, 'toArray')) {
                    $result[] = $h->toArray();
                } else {
                    // Fallback générique pour extraire les données publiques/protégées accessibles via getter magique
                    $result[] = json_decode(json_encode($h), true) ?? (array)$h;
                }
            }
            return $result;
        }
        return [];
    }

    public function setPoiOpeningHours(int $poiId, array $hours): bool
    {
        if (!class_exists('\PoiData\Entities\PoiOpeningHour')) return false;

        try {
            // --- TRACE LOGGING: BEFORE ---
            $existing = \PoiData\Entities\PoiOpeningHour::query()->where('poi_id', '=', $poiId)->get();
            $beforeLog = [];
            foreach ($existing as $item) {
                $beforeLog[] = [
                    'day' => $item->day_of_week,
                    'open' => $item->open_time,
                    'close' => $item->close_time,
                    'closed' => $item->is_closed
                ];
            }

            // Supprimer les horaires existants
            foreach ($existing as $item) {
                $item->delete();
            }
            
            foreach ($hours as $dayOrIndex => $data) {
                // On s'attend à un index de jour (1-7 pour Lundi-Dimanche)
                // On le mappe en 0-6 pour la base de données
                $dayId = is_numeric($dayOrIndex) ? ((int)$dayOrIndex - 1) : $dayOrIndex;
                if ($dayId < 0) $dayId = 0;

                if (is_array($data) && !empty($data) && (is_array(reset($data)) || isset($data['open_time']))) {
                    // Si c'est un tableau de plages
                    if (is_array(reset($data))) {
                        foreach ($data as $slotIndex => $range) {
                            $this->saveSingleOpeningRange($poiId, (int)$dayId, $range, (int)$slotIndex);
                        }
                    } else {
                        // Cas particulier si une seule plage est envoyée sans index
                        $this->saveSingleOpeningRange($poiId, (int)$dayId, $data, 0);
                    }
                } 
                else if (isset($data['day_of_week'])) {
                    $this->saveSingleOpeningRange($poiId, (int)$data['day_of_week'], $data, (int)($data['slot_index'] ?? 0));
                }
            }

            // --- TRACE LOGGING: WRITE ---
            $afterItems = \PoiData\Entities\PoiOpeningHour::query()->where('poi_id', '=', $poiId)->get();
            $afterLog = [];
            foreach ($afterItems as $item) {
                $afterLog[] = [
                    'day' => $item->day_of_week,
                    'open' => $item->open_time,
                    'close' => $item->close_time,
                    'closed' => $item->is_closed
                ];
            }

            $logMsg = "[" . date('Y-m-d H:i:s') . "] POI ID: $poiId | UID: " . ($_SESSION['user_id'] ?? 'N/A') . "\n";
            $logMsg .= "BEFORE: " . json_encode($beforeLog, JSON_UNESCAPED_UNICODE) . "\n";
            $logMsg .= "AFTER:  " . json_encode($afterLog, JSON_UNESCAPED_UNICODE) . "\n";
            $logMsg .= "--------------------------------------------------------------------------------\n";
            @file_put_contents(ROOT . '/trace_hours.log', $logMsg, FILE_APPEND);

            $this->updateDatascore($poiId);
            return true;
        } catch (\Exception $e) {
             error_log("PoiManagerService::setPoiOpeningHours - Exception: " . $e->getMessage());
             return false;
        }
    }

    private function saveSingleOpeningRange(int $poiId, int $dayOfWeek, array $h, int $slotIndex = 0): void
    {
        $isClosed = !empty($h['is_closed']);
        $openTime = (!empty($h['open_time']) && !$isClosed) ? trim($h['open_time']) : null;
        $closeTime = (!empty($h['close_time']) && !$isClosed) ? trim($h['close_time']) : null;

        // Ne pas enregistrer si pas fermé et pas d'heures (ligne vide)
        if (!$isClosed && !$openTime && !$closeTime) {
            return;
        }

        // Ensure HH:MM:SS format
        if ($openTime && strlen($openTime) === 5) $openTime .= ':00';
        if ($closeTime && strlen($closeTime) === 5) $closeTime .= ':00';

        $openingHour = new \PoiData\Entities\PoiOpeningHour([
            'poi_id'      => $poiId,
            'day_of_week' => $dayOfWeek,
            'slot_index'  => $slotIndex,
            'open_time'   => $openTime,
            'close_time'  => $closeTime,
            'is_closed'   => $isClosed ? 1 : 0
        ]);
        
        $openingHour->save();
    }

    /**
     * Récupère les contacts (téléphone, email, site web).
     */
    public function getContacts(int $poiId): array
    {
        $stmt = $this->pdo->prepare('SELECT type, value FROM poi_contacts WHERE poi_id = ?');
        $stmt->execute([$poiId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $contacts = [];
        foreach ($rows as $row) {
            $contacts[$row['type']] = $row['value'];
        }
        return $contacts;
    }

    /**
     * Sauvegarde les contacts.
     */
    public function saveContacts(int $poiId, array $contacts, int $userId = 0): void
    {
        // Supprimer les anciens contacts (pour simplifier la maj)
        $this->pdo->prepare('DELETE FROM poi_contacts WHERE poi_id = ?')->execute([$poiId]);
        
        $stmt = $this->pdo->prepare(
            'INSERT INTO poi_contacts (poi_id, type, value, is_primary, source) VALUES (?, ?, ?, 1, ?)'
        );
        $source = $userId > 0 ? 'user' : 'admin';
        
        foreach (['phone', 'email', 'website'] as $type) {
            if (!empty($contacts[$type])) {
                $stmt->execute([$poiId, $type, trim($contacts[$type]), $source]);
            }
        }
        $this->updateDatascore($poiId);
    }

    /**
     * Récupère les features (wheelchair, parking, etc).
     */
    public function getFeatures(int $poiId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT f.key_name, fv.value
            FROM poi_feature_values fv
            JOIN poi_features f ON f.id = fv.feature_id
            WHERE fv.poi_id = ?
        ');
        $stmt->execute([$poiId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $features = [];
        foreach ($rows as $row) {
            $features[$row['key_name']] = $row['value'];
        }
        return $features;
    }

    /**
     * Sauvegarde les features booléennes/enum.
     */
    public function saveFeatures(int $poiId, array $features, int $userId = 0): void
    {
        // Supprimer toutes les anciennes features pour ce POI
        $this->pdo->prepare('DELETE FROM poi_feature_values WHERE poi_id = ?')->execute([$poiId]);
        
        $stmt = $this->pdo->prepare(
            'INSERT INTO poi_feature_values (poi_id, feature_id, value, source) VALUES (?, ?, ?, ?)'
        );
        $source = $userId > 0 ? 'user' : 'admin';

        foreach ($features as $key => $value) {
            if (!empty($value)) {
                // Trouver l'ID de la feature
                $fStmt = $this->pdo->prepare('SELECT id FROM poi_features WHERE key_name = ?');
                $fStmt->execute([$key]);
                $fId = $fStmt->fetchColumn();
                if ($fId) {
                    $stmt->execute([$poiId, $fId, trim($value), $source]);
                }
            }
        }
    }

    /**
     * Sauvegarde la description d'un POI (upsert).
     */
    public function saveDescription(int $poiId, string $description, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO poi_descriptions (poi_id, description, created_by) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = NOW()'
        );
        $stmt->execute([$poiId, $description, $userId]);
    }

    /**
     * Récupère les types de cuisine.
     */
    public function getCuisines(int $poiId): array
    {
        $stmt = $this->pdo->prepare('SELECT cuisine FROM poi_cuisines WHERE poi_id = ?');
        $stmt->execute([$poiId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Sauvegarde les types de cuisine.
     */
    public function saveCuisines(int $poiId, array $cuisines): void
    {
        $this->pdo->prepare('DELETE FROM poi_cuisines WHERE poi_id = ?')->execute([$poiId]);
        $stmt = $this->pdo->prepare('INSERT INTO poi_cuisines (poi_id, cuisine) VALUES (?, ?)');
        foreach ($cuisines as $cuisine) {
            $cuisine = trim($cuisine);
            if ($cuisine !== '') {
                $stmt->execute([$poiId, $cuisine]);
            }
        }
    }

    /**
     * Récupère les tags d'un POI.
     */
    public function getTags(int $poiId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM poi_tags WHERE poi_id = ? ORDER BY key_name ASC');
        $stmt->execute([$poiId]);
        $tags = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($tags as &$tag) {
            // Un tag peut être un logo (chemin d'image) ou une autre ressource d'upload
            if ($tag['key_name'] === 'logo' || strpos($tag['value_text'], 'upload/') === 0) {
                $tag['value_text'] = $this->normalizeMediaPath($tag['value_text']);
            }
        }

        return $tags;
    }

    /**
     * Normalise un chemin de média (ajoute data/ si nécessaire)
     */
    private function normalizeMediaPath(?string $path): ?string
    {
        if (empty($path) || trim($path) === '') {
            return null;
        }
        $path = trim($path);
        // Si c'est une URL absolue, ne rien faire
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }
        // Si le chemin commence par upload/ mais pas par data/, ajouter data/
        if (strpos($path, 'upload/') === 0) {
            return 'data/' . $path;
        }
        return $path;
    }

    /**
     * Supprime tous les tags d'un POI.
     */
    public function clearTags(int $poiId): void
    {
        $this->pdo->prepare('DELETE FROM poi_tags WHERE poi_id = ?')->execute([$poiId]);
    }

    /**
     * Définit un tag (upsert).
     */
    public function setTag(int $poiId, string $key, string $value, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO poi_tags (poi_id, key_name, value_text, created_by) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value_text = VALUES(value_text)'
        );
        $stmt->execute([$poiId, $key, $value, $userId]);
    }

    /**
     * Supprime un tag.
     */
    public function deleteTag(int $poiId, string $key): void
    {
        $this->pdo->prepare('DELETE FROM poi_tags WHERE poi_id = ? AND key_name = ?')->execute([$poiId, $key]);
    }

    // =========================================================
    // STATS ADMIN
    // =========================================================

    public function getStats(): array
    {
        $total       = (int)$this->pdo->query('SELECT COUNT(*) FROM poi_public')->fetchColumn();
        $pending     = (int)$this->pdo->query("SELECT COUNT(*) FROM poi_proposals WHERE status = 'pending'")->fetchColumn();
        $suggestions = (int)$this->pdo->query("SELECT COUNT(*) FROM poi_suggestions WHERE status = 'pending'")->fetchColumn();
        $photos      = (int)$this->pdo->query("SELECT COUNT(*) FROM poi_photos WHERE status = 'pending'")->fetchColumn();
        $claimed     = (int)$this->pdo->query('SELECT COUNT(*) FROM poi_public WHERE claimed_by IS NOT NULL')->fetchColumn();

        return [
            'total_pois'          => $total,
            'pending_proposals'   => $pending,
            'pending_suggestions' => $suggestions,
            'pending_photos'      => $photos,
            'claimed_pois'        => $claimed,
        ];
    }

    public function delete(int $poiId): bool
    {
        $pdo = \Core\Database::getInstance()->getPdo();
        
        try {
            $pdo->beginTransaction();

            // Supprimer les photos
            $stmtPhotos = $pdo->prepare('DELETE FROM poi_photos WHERE poi_id = ?');
            $stmtPhotos->execute([$poiId]);

            // Supprimer les liens
            $stmtLinks = $pdo->prepare('DELETE FROM poi_links WHERE poi_id = ?');
            $stmtLinks->execute([$poiId]);

            // Supprimer les tags
            $stmtTags = $pdo->prepare('DELETE FROM poi_tags WHERE poi_id = ?');
            $stmtTags->execute([$poiId]);

            // Supprimer les favoris/utilisateurs
            $stmtUsers = $pdo->prepare('DELETE FROM user_poi WHERE poi_id = ?');
            $stmtUsers->execute([$poiId]);

            // Supprimer les horaires si le plugin PoiData est actif
            if (class_exists('\PoiData\Entities\PoiOpeningHour')) {
                $existing = \PoiData\Entities\PoiOpeningHour::query()->where('poi_id', '=', $poiId)->get();
                foreach ($existing as $item) {
                    $item->delete();
                }
            }

            // Supprimer le POI lui-même
            $stmt = $pdo->prepare('DELETE FROM poi_public WHERE id = ?');
            $stmt->execute([$poiId]);
            $deleted = $stmt->rowCount() > 0;

            if ($deleted) {
                $pdo->commit();
                return true;
            } else {
                $pdo->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            $pdo->rollBack();
            // Assuming $this->logger exists or using a global logger
            // If not, this line might cause an error. Adjust based on actual class structure.
            // For now, assuming it's available or will be handled.
            // If not, replace with error_log or similar.
            // \Core\Core::getInstance()->getLogger()->error("PoiManagerService::delete - Error: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Génère un ID négatif unique pour un POI créé par l'admin.
     * Évite les collisions avec les IDs OSM (positifs).
     */
    private function generateAdminId(): int
    {
        $stmt = $this->pdo->query('SELECT MIN(id) FROM poi_public WHERE id < 0');
        $min  = (int)($stmt->fetchColumn() ?? 0);
        return $min - 1; // Descend à chaque création
    }

    /**
     * Résout un nom de marque en ID (crée la marque si nécessaire).
     */
    public function getOrCreateBrandId(?string $name): ?int
    {
        $name = trim($name ?? '');
        if ($name === '') return null;

        $stmt = $this->pdo->prepare('SELECT id FROM poi_brands WHERE name = ?');
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();

        if ($id) return (int)$id;

        $stmt = $this->pdo->prepare('INSERT INTO poi_brands (name, slug) VALUES (?, ?)');
        $slug = \Utils\Util::strToUrl($name);
        $stmt->execute([$name, $slug]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Liste des colonnes autorisées en écriture dans poi_public.
     */
    private function getAllowedFields(): array
    {
        return [
            'id', 'lat', 'lon', 'name', 'brand_id', 'category', 'subcategory',
            'addr_housenumber', 'addr_street', 'addr_postcode', 'addr_city',
            'datascore', 'status',
            'is_closed', 'closed_at', 'closed_reason', 'source',
            'claimed_by', 'claimed_at', 'stars', 'desc_list', 'last_activity_at',
            'created_at', 'updated_at', 'opening_hours',
        ];
    }
}
