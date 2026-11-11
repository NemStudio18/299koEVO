<?php

namespace SearchEngine\Services\Search\Core;

defined('ROOT') or exit('Access denied!');

/**
 * Formateur de résultats de recherche
 * 
 * Formate et normalise les résultats de recherche
 * pour garantir une structure cohérente
 */
class SearchResultFormatter
{
    /**
     * Formate les résultats bruts en structure standard
     * 
     * @param array $rawResults Résultats bruts de la BDD
     * @param string $mode Mode de recherche utilisé
     * @return array Résultats formatés
     */
    public function format(array $rawResults, string $mode = 'basic'): array
    {
        if (empty($rawResults['results'])) {
            return [
                'results' => [],
                'total' => 0,
                'mode' => $mode,
                'facets' => [
                    'activities' => [],
                    'cities' => [],
                    'ratings' => []
                ]
            ];
        }

        $formatted = [];

        foreach ($rawResults['results'] as $poi) {
            $formatted[] = $this->formatPoi($poi);
        }

        return [
            'results' => $formatted,
            'total' => $rawResults['total'] ?? count($formatted),
            'mode' => $mode,
            'facets' => $rawResults['facets'] ?? [
                'activities' => [],
                'cities' => [],
                'ratings' => []
            ]
        ];
    }

    /**
     * Formate un POI individuel
     */
    private function formatPoi(array $poi): array
    {
        return [
            'id' => (int) ($poi['id'] ?? $poi['poi_id'] ?? 0),
            'name' => $poi['name'] ?? '',
            'lat' => (float) ($poi['lat'] ?? 0),
            'lon' => (float) ($poi['lon'] ?? 0),
            'address' => $poi['address'] ?? $poi['addr_street'] ?? '',
            'city' => $poi['city'] ?? $poi['addr_city'] ?? '',
            'postcode' => $poi['postcode'] ?? $poi['addr_postcode'] ?? '',
            'phone' => $poi['phone'] ?? $poi['contact_phone'] ?? null,
            'website' => $poi['website'] ?? $poi['contact_website'] ?? null,
            'email' => $poi['email'] ?? $poi['contact_email'] ?? null,
            'category' => $poi['category'] ?? null,
            'subcategory' => $poi['subcategory'] ?? null,
            'is_premium' => (bool) (($poi['is_premium'] ?? false) || ($poi['claimed_by'] ?? null)),
            'is_claimed' => (bool) (($poi['is_claimed'] ?? false) || ($poi['claimed_by'] ?? null)),
            'claimed' => (bool) (($poi['claimed'] ?? false) || ($poi['claimed_by'] ?? null)),
            'claimed_validated' => (bool) (($poi['claimed_validated'] ?? false) || ($poi['claimed_by'] ?? null)),
            'claimed_by' => $poi['claimed_by'] ?? null,
            'is_verified' => (bool) ($poi['is_verified'] ?? false),
            'rating' => isset($poi['rating']) ? (float) $poi['rating'] : null,
            'review_count' => isset($poi['review_count']) ? (int) $poi['review_count'] : 0,
            'distance' => isset($poi['distance']) ? (float) $poi['distance'] : (isset($poi['distance_km']) ? (float) $poi['distance_km'] : null),
            'distance_km' => isset($poi['distance_km']) ? (float) $poi['distance_km'] : (isset($poi['distance']) ? (float) $poi['distance'] : null),
            'score' => isset($poi['legacy_score']) ? (float) $poi['legacy_score'] : (isset($poi['score']) ? (float) $poi['score'] : (isset($poi['relevance_score']) ? (float) $poi['relevance_score'] : (isset($poi['text_score']) ? (float) $poi['text_score'] : null))),
            'facebook_url' => $poi['facebook_url'] ?? null,
            'instagram_url' => $poi['instagram_url'] ?? null,
            'logo' => $poi['logo'] ?? null,
            'stars' => $poi['stars'] ?? null,
            'claim_status' => $poi['claim_status'] ?? null,
            'opening_hours' => $poi['opening_hours'] ?? null,
            'description' => $poi['description'] ?? null,
            'brand' => $poi['brand'] ?? null,
            'internet_access' => $poi['internet_access'] ?? null,
            'parking' => $poi['parking'] ?? null,
            'wheelchair' => $poi['wheelchair'] ?? null,
            'fee' => $poi['fee'] ?? null,
            'background_color' => $poi['background_color'] ?? null,
            'is_open_now' => isset($poi['is_open_now']) ? (bool) $poi['is_open_now'] : null,
            'is_closing_soon' => isset($poi['is_closing_soon']) ? (bool) $poi['is_closing_soon'] : null,
            'opening_hours_today' => $poi['opening_hours_today'] ?? null,
            'datascore' => isset($poi['datascore']) ? (int) $poi['datascore'] : 0,
            'desc_list' => $poi['desc_list'] ?? null,
        ];
    }
}
