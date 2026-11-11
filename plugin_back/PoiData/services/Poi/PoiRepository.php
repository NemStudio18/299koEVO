<?php

namespace PoiData\Services\Poi;

use PoiData\Entities\Poi;

defined('ROOT') or exit('Access denied!');

/**
 * Repository pour les POIs (Points d'Intérêt)
 * 
 * Centralise l'accès aux données de la table poi_public
 * Gère également la génération de noms d'URL et de slugs
 */
class PoiRepository
{
    public function __construct()
    {
        // Plus besoin de PDO
    }

    /**
     * Récupère un POI par son ID
     * 
     * @param int|string $id ID du POI
     * @return array|null Données brutes du POI
     */
    public function fetchOne($id): ?array
    {
        $poi = Poi::findPK($id);
        return $poi ? $poi->attributes : null;
    }

    /**
     * Récupère un POI par son nom (avec support du format nom-id)
     * 
     * @param string $name Nom du POI (slug)
     * @return array|null Données brutes du POI
     */
    public function fetchByName(string $name): ?array
    {
        try {
            // Vérifier si le format est "nom-id" (double tiret `name--id` pour les ID négatifs, ou simple `name-id` pour les ID positifs)
            if (preg_match('/^(.+?)-(-?\d+)$/', $name, $matches)) {
                $poiIdStr = $matches[2];
                // Si la string capturée a un '-' (e.g. on a matché '--1234'), alors on cast direct en (int)
                $poiId = (int)$poiIdStr;
                
                $result = $this->fetchOne($poiId);
                // Fallback (au cas où - ou autre)
                if (!$result && $poiId > 0) {
                    $result = $this->fetchOne(-$poiId);
                }
                if ($result) {
                    return $result;
                }
            }

            // Format simple : juste le slug du nom
            $decodedName = str_replace('-', ' ', $name);
            $p = Poi::query()
                ->where('name', '=', trim($decodedName))
                ->first();

            if ($p) {
                return $p->attributes;
            }

            // Recherche floue par mots-clés si non trouvé
            $keywords = array_filter(explode('-', $name));
            if (empty($keywords))
                return null;

            $firstKeyword = $keywords[0];
            $candidates = Poi::query()
                ->where('name', 'LIKE', '%' . $firstKeyword . '%')
                ->limit(50)
                ->get();

            foreach ($candidates as $candidate) {
                if ($this->createSlug($candidate->name) === $name) {
                    return $candidate->attributes;
                }
            }

            return null;
        } catch (\Exception $e) {
            error_log("PoiRepository::fetchByName(name=$name) - Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Génère le nom d'URL pour un POI
     */
    public function generateUrlName(string $name, $id, ?array $duplicateNamesMap = null): string
    {
        $slug = $this->createSlug($name);

        // IDs négatifs (utilisateur) -> toujours ID
        if (is_numeric($id) && (int) $id < 0) {
            return $slug . '-' . $id;
        }

        $hasDuplicate = $duplicateNamesMap !== null ? isset($duplicateNamesMap[$name]) : $this->hasDuplicateNames($name);

        return $hasDuplicate ? ($slug . '-' . $id) : $slug;
    }

    /**
     * Vérifie si un nom est en doublon
     */
    public function hasDuplicateNames(string $name): bool
    {
        return Poi::query()->where('name', '=', $name)->count() > 1;
    }

    /**
     * Récupère la map des doublons pour une liste de POIs
     */
    public function getDuplicateNamesMap(array $pois): array
    {
        $names = [];
        foreach ($pois as $poi) {
            $n = $poi['name'] ?? '';
            if ($n)
                $names[$n] = true;
        }

        if (empty($names))
            return [];

        // Note: QueryBuilder doesn't support GROUP BY / HAVING yet, 
        // but we can simulate it for compliance or just use counts individually
        $map = [];
        foreach (array_keys($names) as $name) {
            if ($this->hasDuplicateNames($name)) {
                $map[$name] = true;
            }
        }
        return $map;
    }

    /**
     * Crée un slug URL-friendly
     */
    public function createSlug(string $name): string
    {
        $replacements = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ç' => 'c', 'ñ' => 'n'
        ];
        $slug = strtr(mb_strtolower($name), $replacements);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        return trim($slug, '-');
    }
}
