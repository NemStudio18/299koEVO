<?php

namespace SearchEngine\Services\Search\Category;

defined('ROOT') or exit('Access denied!');

/**
 * Normalise les catégories OSM vers des catégories métier
 */
final class OsmCategoryNormalizer
{
    private static ?array $map = null;

    /**
     * Charge le mapping depuis osm_category_map.json
     */
    private static function loadMap(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $mapFile = PLUGINS . 'searchEVO' . DS . 'param' . DS . 'osm_category_map.json';
        if (!file_exists($mapFile)) {
            self::$map = [];
            return [];
        }

        $content = file_get_contents($mapFile);
        $map = json_decode($content, true);
        self::$map = is_array($map) ? $map : [];

        return self::$map;
    }

    /**
     * Normalise les catégories OSM brutes vers des catégories métier
     */
    public function normalize(array $raw): array
    {
        $map = self::loadMap();
        if (empty($map)) {
            return [];
        }

        $result = [];

        foreach ($raw as $parent => $items) {
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $rawKey => $count) {
                if (!is_numeric($count)) {
                    continue;
                }

                $keys = self::explodeKeys($rawKey);

                foreach ($keys as $key) {
                    $category = self::resolveCategory($key, $map);

                    if (!$category) {
                        $category = 'Autre';
                    }

                    // Traduire le nom de la catégorie
                    $categoryTranslated = self::translateCategory($category);

                    if (!isset($result[$categoryTranslated])) {
                        $result[$categoryTranslated] = [
                            'label' => $categoryTranslated,
                            'total' => 0,
                            'items' => [],
                            '_original_key' => $category
                        ];
                    }

                    $result[$categoryTranslated]['total'] += (int) $count;

                    $osmKey = $key;
                    $label = self::humanize($key);

                    if (!isset($result[$categoryTranslated]['items'][$osmKey])) {
                        $result[$categoryTranslated]['items'][$osmKey] = [
                            'label' => $label,
                            'count' => 0
                        ];
                    }
                    $result[$categoryTranslated]['items'][$osmKey]['count'] += (int) $count;
                }
            }
        }

        // Trier par total décroissant
        uasort($result, function ($a, $b) {
            return $b['total'] - $a['total'];
        });

        return $result;
    }

    /**
     * Recherche inverse : retrouve les clés OSM possibles depuis un terme recherché
     * Cherche dans les traductions (ex: "boulangerie" -> "bakery")
     * 
     * @param string $term Le terme recherché (ex: "boulangerie")
     * @return array Liste des clés exactes (catégories ou sous-catégories)
     */
    public function reverseLookup(string $term): array
    {
        $term = mb_strtolower(trim($term));
        if (empty($term) || strlen($term) < 3) {
            return [];
        }

        $matches = [];
        
        if (class_exists('\Core\Lang') && method_exists('\Core\Lang', 'getRaw')) {
            $translations = \Core\Lang::getRaw();
            
            if (is_array($translations)) {
                foreach (['category', 'subcategory'] as $type) {
                    $prefix = 'search.' . $type . '.';
                    foreach ($translations as $key => $value) {
                        if (strpos($key, $prefix) === 0) {
                            // Recherche insensible à la casse
                            if (mb_stripos($value, $term) !== false) {
                                $osmKey = str_replace($prefix, '', $key);
                                $matches[] = ['key' => $osmKey, 'type' => $type];
                            }
                        }
                    }
                }
            }
        }

        return $matches;
    }

    private static function explodeKeys(string $key): array
    {
        $keys = preg_split('/[;,\|]/', strtolower(trim($key)));
        return array_filter(array_map('trim', $keys));
    }

    private static function resolveCategory(string $key, array $map): ?string
    {
        $key = strtolower(trim($key));

        foreach ($map as $group) {
            if (!isset($group['keys']) || !is_array($group['keys'])) {
                continue;
            }

            foreach ($group['keys'] as $rule) {
                $rule = strtolower(trim($rule));

                if ($rule === $key) {
                    return $group['category'] ?? null;
                }

                if (strpos($rule, '*') !== false) {
                    $pattern = str_replace('*', '.*', preg_quote($rule, '/'));
                    if (preg_match('/^' . $pattern . '$/', $key)) {
                        return $group['category'] ?? null;
                    }
                }
            }
        }

        return null;
    }

    private function simpleSlug(string $text): string
    {
        $replacements = [
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'ÿ' => 'y',
            'ç' => 'c',
            'ñ' => 'n'
        ];
        $text = strtr(mb_strtolower($text), $replacements);
        $text = preg_replace('/[^a-z0-9]+/i', '_', $text);
        return trim($text, '_');
    }

    /**
     * Traduit une catégorie métier (ex: "Santé" -> "Santé" ou "Autre" -> "Autre")
     * Utilise le système de traduction du CMS si disponible
     */
    public static function translateCategory(string $category): string
    {
        // Normaliser la clé pour la recherche de traduction
        $normalizedKey = strtolower(str_replace(' ', '_', trim($category)));
        $translationKey = 'search.category.' . $normalizedKey;

        // Essayer d'utiliser le système de traduction du CMS
        if (class_exists('\Core\Lang')) {
            $translated = \Core\Lang::get($translationKey);
            // Si la traduction existe (et n'est pas juste la clé retournée), l'utiliser
            if ($translated !== $translationKey) {
                return $translated;
            }
        }

        // Fallback : retourner la catégorie telle quelle
        return $category;
    }

    /**
     * Humanise une clé (ex: "fast_food" -> "Fast food")
     * Utilise le système de traduction du CMS si disponible
     */
    public static function humanize(string $key): string
    {
        // Normaliser la clé pour la recherche de traduction
        $normalizedKey = strtolower(str_replace(' ', '_', trim($key)));
        $translationKey = 'search.subcategory.' . $normalizedKey;

        // Essayer d'utiliser le système de traduction du CMS
        if (class_exists('\Core\Lang')) {
            $translated = \Core\Lang::get($translationKey);
            // Si la traduction existe (et n'est pas juste la clé retournée), l'utiliser
            if ($translated !== $translationKey) {
                return $translated;
            }
        }

        // Fallback : humanisation basique
        $key = str_replace('_', ' ', $key);
        $words = explode(' ', $key);
        $words = array_map('ucfirst', $words);

        return implode(' ', $words);
    }
}
