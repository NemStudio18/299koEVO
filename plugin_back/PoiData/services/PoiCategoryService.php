<?php

namespace PoiData\Services;

use PoiData\Entities\Category;
use PoiData\Entities\Subcategory;
use PoiData\Entities\CategoryOverride;
use PoiData\Entities\Poi;
use Core\Core;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour gérer les catégories éditoriales et les overrides
 * Principe : OSM = source brute, table locale = surcouche humaine
 */
class PoiCategoryService
{
    public function __construct()
    {
        // Plus besoin de PDO, les entités gèrent leur stockage
    }

    /**
     * Récupère la catégorie et sous-catégorie pour un POI (override si existe, sinon OSM)
     * @param int $poiId ID du POI
     * @param array $osmData Données OSM brutes (category, subcategory)
     * @return array ['category' => string|null, 'subcategory' => string|null, 'source' => 'override'|'osm', 'category_id' => int|null, 'subcategory_id' => int|null]
     */
    public function getPoiCategory(int|string $poiId, array $osmData = []): array
    {
        // Vérifier si un override existe
        $override = CategoryOverride::findPK((int)$poiId);

        if ($override) {
            $cat = Category::findPK((int)$override->category_id);
            $sub = $override->subcategory_id ? Subcategory::findPK((int)$override->subcategory_id) : null;

            return [
                'category' => $cat ? $cat->slug : null,
                'subcategory' => $sub ? $sub->osm_key : null,
                'category_label' => $cat ? $cat->label_fr : null,
                'subcategory_label' => $sub ? $sub->label_fr : null,
                'category_icon' => $cat ? $cat->icon : null,
                'category_id' => (int) $override->category_id,
                'subcategory_id' => $override->subcategory_id ? (int) $override->subcategory_id : null,
                'source' => $override->source ?? 'admin'
            ];
        }

        // Pas d'override : fallback OSM
        return [
            'category' => $osmData['category'] ?? null,
            'subcategory' => $osmData['subcategory'] ?? null,
            'category_label' => null,
            'subcategory_label' => null,
            'category_icon' => null,
            'category_id' => null,
            'subcategory_id' => null,
            'source' => 'osm'
        ];
    }

    /**
     * Récupère les catégories pour plusieurs POIs en une seule requête (optimisation N+1)
     */
    public function getPoiCategoriesBatch(array $poiIds): array
    {
        if (empty($poiIds)) {
            return [];
        }

        $overrides = CategoryOverride::query()
            ->where('poi_id', 'IN', $poiIds)
            ->get();

        $results = [];
        foreach ($overrides as $override) {
            $poiId = (int) $override->poi_id;
            $cat = Category::findPK((int)$override->category_id);
            $sub = $override->subcategory_id ? Subcategory::findPK((int)$override->subcategory_id) : null;

            $results[$poiId] = [
                'category' => $cat ? $cat->slug : null,
                'subcategory' => $sub ? $sub->osm_key : null,
                'category_label' => $cat ? $cat->label_fr : null,
                'subcategory_label' => $sub ? $sub->label_fr : null,
                'category_icon' => $cat ? $cat->icon : null,
                'category_id' => (int) $override->category_id,
                'subcategory_id' => $override->subcategory_id ? (int) $override->subcategory_id : null,
                'source' => $override->source ?? 'admin'
            ];
        }

        return $results;
    }

    /**
     * Définit un override pour un POI
     */
    public function setPoiCategoryOverride(int $poiId, int $categoryId, ?int $subcategoryId = null, string $source = 'admin'): bool
    {
        try {
            $logger = Core::getInstance()->getLogger();

            $override = CategoryOverride::findPK($poiId) ?: new CategoryOverride(['poi_id' => $poiId]);
            $override->category_id = $categoryId;
            $override->subcategory_id = $subcategoryId;
            $override->source = $source;

            if ($override->save()) {
                // Mettre à jour poi_public avec les catégories éditoriales
                $this->syncPoiSearchCategory($poiId);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $logger = Core::getInstance()->getLogger();
            $logger->log(Logger::LEVEL_ERROR, "PoiCategoryService::setPoiCategoryOverride - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchronise les catégories de poi_public avec les catégories éditoriales si elles existent
     */
    private function syncPoiSearchCategory(int $poiId): bool
    {
        try {
            $poiData = $this->getPoiCategory($poiId); // Récupère avec override si présent
            
            $poi = Poi::findPK($poiId);
            if ($poi && $poiData['source'] !== 'osm') {
                $poi->category = 'editorial_' . $poiData['category'];
                $poi->subcategory = $poiData['subcategory'];
                return $poi->save();
            }

            return false;
        } catch (\Exception $e) {
            $logger = Core::getInstance()->getLogger();
            $logger->log(Logger::LEVEL_ERROR, "PoiCategoryService::syncPoiSearchCategory - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime l'override d'un POI (retour à OSM)
     */
    public function removePoiCategoryOverride(int $poiId): bool
    {
        try {
            $override = CategoryOverride::findPK($poiId);
            if ($override && $override->delete()) {
                 // On ne peut pas facilement revenir à OSM ici sans l'id d'origine ou réimport
                 // Mais on peut au moins vider le flag editorial si besoin
                 return true;
            }
            return false;
        } catch (\Exception $e) {
            error_log("PoiCategoryService::removePoiCategoryOverride - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les catégories éditoriales actives
     */
    public function getAllCategories(): array
    {
        $cats = Category::query()
            ->where('active', '=', 1)
            ->orderBy('priority', 'DESC')
            ->orderBy('label_fr', 'ASC')
            ->get();
            
        $results = [];
        foreach ($cats as $c) {
            $results[] = $c->attributes;
        }
        return $results;
    }

    /**
     * Récupère une catégorie par son ID
     */
    public function getCategoryById(int $categoryId): ?array
    {
        $cat = Category::findPK($categoryId);
        return $cat ? $cat->attributes : null;
    }

    /**
     * Récupère les sous-catégories éditoriales pour une catégorie
     */
    public function getSubcategoriesByCategory(int $categoryId): array
    {
        $subs = Subcategory::query()
            ->where('category_id', '=', $categoryId)
            ->where('active', '=', 1)
            ->orderBy('priority', 'DESC')
            ->orderBy('label_fr', 'ASC')
            ->get();
            
        $results = [];
        foreach ($subs as $s) {
            $results[] = $s->attributes;
        }
        return $results;
    }

    /**
     * Trouve une sous-catégorie éditoriale par sa clé OSM
     */
    public function findSubcategoryByOsmKey(string $osmKey): ?array
    {
        $sub = Subcategory::query()
            ->where('osm_key', '=', $osmKey)
            ->where('active', '=', 1)
            ->first();
            
        return $sub ? $sub->attributes : null;
    }

    /**
     * Crée une catégorie éditoriale
     */
    public function createCategory(string $slug, string $labelFr, ?string $labelEn = null, ?string $icon = null, int $priority = 50)
    {
        try {
            $cat = new Category([
                'slug' => $slug,
                'label_fr' => $labelFr,
                'label_en' => $labelEn,
                'icon' => $icon,
                'priority' => $priority,
                'active' => 1
            ]);
            if ($cat->save()) {
                return (int)$cat->id;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Crée une sous-catégorie éditoriale
     */
    public function createSubcategory(int $categoryId, string $osmKey, string $labelFr, ?string $labelEn = null, int $priority = 50)
    {
        try {
            $sub = new Subcategory([
                'category_id' => $categoryId,
                'osm_key' => $osmKey,
                'label_fr' => $labelFr,
                'label_en' => $labelEn,
                'priority' => $priority,
                'active' => 1
            ]);
            if ($sub->save()) {
                return (int)$sub->id;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Récupère toutes les catégories uniques présentes dans poi_public
     */
    public function getPublicCategories(): array
    {
        // On ne peut pas faire de DISTINCT via le QueryBuilder actuel de JsonActiveRecord facilement
        // Mais on peut faire :
        $all = Poi::all();
        $cats = [];
        foreach ($all as $p) {
            if ($p->category) $cats[$p->category] = true;
        }
        $result = array_keys($cats);
        sort($result);
        return $result;
    }

    /**
     * Récupère toutes les sous-catégories uniques pour une catégorie donnée dans poi_public
     */
    public function getPublicSubcategories(string $category): array
    {
        $all = Poi::query()->where('category', '=', $category)->get();
        $subs = [];
        foreach ($all as $p) {
            if ($p->subcategory) $subs[$p->subcategory] = true;
        }
        $result = array_keys($subs);
        sort($result);
        return $result;
    }
}
