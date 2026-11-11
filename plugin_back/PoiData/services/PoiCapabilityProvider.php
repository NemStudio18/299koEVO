<?php

namespace PoiData\Services;

use Core\Contracts\EntityProviderInterface;
use Core\Logger;
use PoiData\Entities\Poi;

defined('ROOT') or exit('Access denied!');

class PoiCapabilityProvider implements EntityProviderInterface
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = \Core\Core::getInstance()->getLogger();
    }

    /**
     * Vérifie la visibilité publique stricte d'un POI.
     * Règle canonique V2 : status = 'ACTIVE' AND publication_status = 'PUBLISHED'.
     * Politique de sécurité : FAIL-CLOSED strict.
     * Tout échec de requête, exception, erreur PHP ou absence dans la table canonique
     * entraîne un refus d'accès immédiat (false). Aucun repli permissif.
     */
    public function isVisible(int $id): bool
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT status, publication_status FROM poi WHERE id = ?");
            if ($stmt->execute([$id])) {
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    return (($row['status'] ?? '') === 'ACTIVE' && ($row['publication_status'] ?? '') === 'PUBLISHED');
                }
            }
            return false;
        } catch (\Throwable $e) {
            $this->logger->warning("PoiCapabilityProvider::isVisible - Échec de vérification canonique pour POI {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function get(int $id): ?array
    {
        try {
            if (!$this->isVisible($id)) {
                return null;
            }

            $poi = Poi::findPK($id);
            if (!$poi) return null;

            $result = $poi->attributes;

            return array_merge($result, [
                'id' => (int)$result['id'],
                'latitude' => $result['lat'],
                'longitude' => $result['lon'],
                'url' => $this->getUrl((int)$result['id'])
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("PoiCapabilityProvider::get Error: " . $e->getMessage());
            return null;
        }
    }

    public function getMany(array $ids): array
    {
        if (empty($ids)) return [];

        try {
            $results = Poi::query()
                ->where('id', 'IN', array_map('intval', $ids))
                ->get();

            $mapped = [];
            foreach ($results as $poi) {
                $row = $poi->attributes;
                $poiId = (int)$row['id'];
                if (!$this->isVisible($poiId)) {
                    continue;
                }
                $mapped[$poiId] = array_merge($row, [
                    'id' => $poiId,
                    'latitude' => $row['lat'],
                    'longitude' => $row['lon'],
                    'url' => $this->getUrl($poiId)
                ]);
            }
            return $mapped;
        } catch (\Throwable $e) {
            $this->logger->error("PoiCapabilityProvider::getMany Error: " . $e->getMessage());
            return [];
        }
    }

    public function getName(int $id): ?string
    {
        try {
            if (!$this->isVisible($id)) {
                return null;
            }

            $poi = Poi::findPK($id);
            return $poi ? $poi->name : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getUrl(int $id): ?string
    {
        $name = $this->getName($id);
        if (!$name) return null;

        $slug = \Core\Core::getInstance()->callHook('search:generate-url-name', ['name' => $name, 'id' => $id]);
        if ($slug && is_string($slug)) {
            return '/' . $slug;
        }

        return '/searchEVO/poi/' . $id;
    }

    public function getIdBySlug(string $slug): ?int
    {
        // 1. Negative ID format (e.g. "vivre-bourges--123")
        if (preg_match('/^(.+?)--(\d+)$/', $slug, $matches)) {
            $poiId = -(int)$matches[2];
            if ($this->getName($poiId)) {
                return $poiId;
            }
        }

        // 2. Name-ID format (positive OSM IDs)
        if (preg_match('/^(.+?)-(\d+)$/', $slug, $matches)) {
            $poiId = (int)$matches[2];
            if ($this->getName($poiId)) {
                return $poiId;
            }
        }

        // 3. Exact match on name
        $decodedName = str_replace('-', ' ', $slug);
        
        try {
            $poi = Poi::query()
                ->where('name', '=', trim($decodedName))
                ->first();
            
            if ($poi) {
                if (!$this->isVisible((int)$poi->id)) {
                    return null;
                }
                return (int)$poi->id;
            }
            
            return null;
        } catch (\Throwable $e) {
            $this->logger->error("PoiCapabilityProvider::getIdBySlug Error: " . $e->getMessage());
            return null;
        }
    }
}
