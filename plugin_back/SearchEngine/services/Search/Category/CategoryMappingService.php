<?php

namespace SearchEngine\Services\Search\Category;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour mapper les tags OSM vers des Familles UI et Spécialités
 */
class CategoryMappingService
{
    private static ?array $mapping = null;

    /**
     * Retourne la famille correspondante à un couple (category, subcategory) OSM
     */
    public function mapToFamily(string $category, string $subcategory = ''): array
    {
        $this->ensureMappingLoaded();

        // Nettoyage des entrées (OSM utilise parfois des ; pour les multi-valeurs)
        $subparts = explode(';', $subcategory);
        $primarySub = trim($subparts[0]);

        // 1. Recherche spécifique (famille par spécialité)
        if (isset(self::$mapping['specialties'][$primarySub])) {
            return self::$mapping['families'][self::$mapping['specialties'][$primarySub]];
        }

        // 2. Recherche par catégorie OSM
        if (isset(self::$mapping['categories_map'][$category])) {
            return self::$mapping['families'][self::$mapping['categories_map'][$category]];
        }

        // 3. Pas de fallback (Retourne null si aucun type_label projetable)
        return [];
    }

    /**
     * Retourne les critères OSM (catégories et spécialités) pour une famille donnée
     * Utile pour construire la clause WHERE de recherche
     */
    public function getOsmCriteriaForFamily(string $familyKey): array
    {
        $this->ensureMappingLoaded();

        $categories = [];
        $subcategories = [];

        // 1. Trouver les catégories OSM mappées
        foreach (self::$mapping['categories_map'] as $osmCat => $fam) {
            if ($fam === $familyKey) {
                $categories[] = $osmCat;
            }
        }

        // 2. Trouver les spécialités OSM mappées
        foreach (self::$mapping['specialties'] as $osmSub => $fam) {
            if ($fam === $familyKey) {
                $subcategories[] = $osmSub;
            }
        }

        return [
            'categories' => $categories,
            'subcategories' => $subcategories
        ];
    }

    /**
     * Retourne la liste des familles pour les chips (navigation)
     */
    public function getFamilies(): array
    {
        $this->ensureMappingLoaded();
        return self::$mapping['families'];
    }

    /**
     * Charge le dictionnaire de mapping
     */
    private function ensureMappingLoaded(): void
    {
        if (self::$mapping !== null) {
            return;
        }

        self::$mapping = [
            'families' => [
                'shops' => ['key' => 'shops', 'label' => 'family_shops', 'icon' => 'shop'],
                'food' => ['key' => 'food', 'label' => 'family_food', 'icon' => 'utensils'],
                'health' => ['key' => 'health', 'label' => 'family_health', 'icon' => 'heartbeat'],
                'services' => ['key' => 'services', 'label' => 'family_services', 'icon' => 'landmark'],
                'leisure' => ['key' => 'leisure', 'label' => 'family_leisure', 'icon' => 'soccer-ball'],
                'education' => ['key' => 'education', 'label' => 'family_education', 'icon' => 'graduation-cap'],
                'heritage' => ['key' => 'heritage', 'label' => 'family_heritage', 'icon' => 'fort-awesome'],
                'nature' => ['key' => 'nature', 'label' => 'family_nature', 'icon' => 'tree'],
                'craft' => ['key' => 'craft', 'label' => 'family_craft', 'icon' => 'tools'],
                'offices' => ['key' => 'offices', 'label' => 'family_offices', 'icon' => 'briefcase'],
                'emergency' => ['key' => 'emergency', 'label' => 'family_emergency', 'icon' => 'life-ring'],
                'infrastructure' => ['key' => 'infrastructure', 'label' => 'family_infrastructure', 'icon' => 'building'],
            ],
            'categories_map' => [
                'shop' => 'shops',
                'commerce' => 'shops',
                'amenity' => 'services',
                'healthcare' => 'health',
                'leisure' => 'leisure',
                'sport' => 'leisure',
                'education' => 'education',
                'historic' => 'heritage',
                'natural' => 'nature',
                'craft' => 'craft',
                'office' => 'offices',
                'emergency' => 'emergency',
                'man_made' => 'infrastructure',
                'tourism' => 'leisure',

                // French Categories (Database Slugs)
                'restauration-sorties' => 'food',
                'sante-soins' => 'health',
                'education-enfance' => 'education',
                'culture-patrimoine' => 'heritage',
                'nature-espaces-verts' => 'nature',
                'services-bureaux' => 'offices',
                'services-publics-citoyens' => 'services',
                'administration-services-publics' => 'services',
                'transports-mobilite' => 'infrastructure',
                'sport-loisirs' => 'leisure',
                'tourisme-hebergement' => 'leisure',
            ],
            'specialties' => [
                // French Subcategories (Database Slugs)
                'restaurants' => 'food',
                'bars-pubs' => 'food',
                'cafes-salons-de-the' => 'food',

                'alimentation-epicerie' => 'shops',
                'beaute-bien-etre' => 'shops',
                'boulangerie-patisserie' => 'shops',
                'centres-commerciaux' => 'shops',
                'commerce-culture-loisirs' => 'shops',
                'commerce-mobilite' => 'shops',
                'mode-vetements' => 'shops',
                'specialistes' => 'shops',

                'hopitaux-cliniques' => 'health',
                'medecins-specialistes' => 'health',
                'paramedical' => 'health',
                'pharmacies-parapharmacies' => 'health',

                'colleges-lycees' => 'education',
                'ecoles' => 'education',

                'lieux-de-culte' => 'heritage',
                'monuments-historiques-patrimoine' => 'heritage',
                'monuments-memoriaux' => 'heritage',
                'musees-galeries' => 'heritage',

                'parcs-jardins-publics' => 'nature',

                'banques-assurances' => 'offices',
                'banques-finances' => 'offices',
                'immobilier' => 'offices',

                'environnement' => 'services',
                'social' => 'services',
                'mairies-prefectures' => 'services',
                'securite' => 'services',
                'services-postaux' => 'services',

                'auto-ecoles' => 'infrastructure',
                'parkings-stationnement' => 'infrastructure',
                'recharge-electrique' => 'infrastructure',
                'stations-service' => 'infrastructure',

                'complexes-sportifs-stades' => 'leisure',
                'piscines-centres-aquatiques' => 'leisure',
                'hotels' => 'leisure',

                // Raw OSM Food & Drink
                'restaurant' => 'food',
                'cafe' => 'food',
                'bar' => 'food',
                'pub' => 'food',
                'fast_food' => 'food',
                'pizza' => 'food',
                'burger' => 'food',
                'kebab' => 'food',
                'crepe' => 'food',
                'brasserie' => 'food',
                'ice_cream' => 'food',
                'biergarten' => 'food',
                'wine' => 'food',

                // Shops (Commerce)
                'bakery' => 'shops',
                'supermarket' => 'shops',
                'convenience' => 'shops',
                'clothes' => 'shops',
                'hairdresser' => 'shops',
                'butcher' => 'shops',
                'greengrocer' => 'shops',
                'books' => 'shops',
                'optician' => 'shops',
                'florist' => 'shops',
                'shoes' => 'shops',
                'jewelry' => 'shops',
                'gift' => 'shops',
                'electronics' => 'shops',
                'mobile_phone' => 'shops',
                'hardware' => 'shops',
                'furniture' => 'shops',
                'garden_centre' => 'shops',
                'doityourself' => 'shops',
                'bicycle' => 'shops',
                'car_repair' => 'shops',
                'car_parts' => 'shops',
                'beauty' => 'shops',
                'cosmetics' => 'shops',
                'tattoo' => 'shops',
                'laundry' => 'shops',
                'dry_cleaning' => 'shops',
                'toys' => 'shops',
                'musical_instrument' => 'shops',
                'stationery' => 'shops',
                'pet' => 'shops',
                'newsagent' => 'shops',
                'alcohol' => 'shops',
                'beverages' => 'shops',
                'deli' => 'shops',
                'seafood' => 'shops',
                'pastry' => 'shops',
                'chocolate' => 'shops',
                'cheese' => 'shops',
                'tea' => 'shops',
                'coffee' => 'shops',
                'farm' => 'shops',
                'second_hand' => 'shops',
                'antique' => 'shops',
                'charity' => 'shops',
                'pawnbroker' => 'shops',
                'department_store' => 'shops',
                'mall' => 'shops',
                'baby_goods' => 'shops',
                'bag' => 'shops',
                'boutique' => 'shops',
                'fashion' => 'shops',
                'perfumery' => 'shops',

                // Health
                'doctors' => 'health',
                'pharmacy' => 'health',
                'dentist' => 'health',
                'hospital' => 'health',
                'clinic' => 'health',
                'veterinary' => 'health',
                'physiotherapist' => 'health',
                'alternative_medicine' => 'health',
                'psychotherapist' => 'health',
                'laboratory' => 'health',
                'hearing_aids' => 'health',

                // Education
                'school' => 'education',
                'university' => 'education',
                'kindergarten' => 'education',
                'college' => 'education',
                'library' => 'education',
                'driving_school' => 'education',
                'music_school' => 'education',
                'language_school' => 'education',

                // Culture & Leisure
                'cinema' => 'leisure',
                'theatre' => 'leisure',
                'arts_centre' => 'leisure',
                'nightclub' => 'leisure',
                'community_centre' => 'leisure',
                'park' => 'leisure',
                'playground' => 'leisure',
                'sports_centre' => 'leisure',
                'swimming_pool' => 'leisure',
                'stadium' => 'leisure',
                'pitch' => 'leisure',
                'fitness_centre' => 'leisure',
                'dance' => 'leisure',
                'bowling_alley' => 'leisure',
                'golf_course' => 'leisure',
                'water_park' => 'leisure',
                'zoo' => 'leisure',
                'theme_park' => 'leisure',

                // Heritage
                'museum' => 'heritage',
                'artwork' => 'heritage',
                'attraction' => 'heritage',
                'castle' => 'heritage',
                'monument' => 'heritage',
                'memorial' => 'heritage',
                'ruins' => 'heritage',
                'archaeological_site' => 'heritage',
                'church' => 'heritage',
                'viewpoint' => 'heritage',

                // Services
                'bank' => 'services',
                'post_office' => 'services',
                'townhall' => 'services',
                'police' => 'services',
                'courthouse' => 'services',
                'fire_station' => 'services',
                'post_box' => 'services',
                'recycling' => 'services',
                'toilets' => 'services',
                'bench' => 'services',
                'atm' => 'services',
                'social_facility' => 'services',

                // Offices
                'insurance' => 'offices',
                'estate_agent' => 'offices',
                'lawyer' => 'offices',
                'accountant' => 'offices',
                'architect' => 'offices',
                'employment_agency' => 'offices',
                'travel_agent' => 'offices',
                'coworking' => 'offices',
                'government' => 'offices',
                'association' => 'offices',
                'ngo' => 'offices',
                'company' => 'offices',

                // Emergency
                'defibrillator' => 'emergency',
                'ambulance_station' => 'emergency',

                // Craft (Artisanat)
                'carpenter' => 'craft',
                'plumber' => 'craft',
                'electrician' => 'craft',
                'shoemaker' => 'craft',
                'tailor' => 'craft',
                'photographer' => 'craft',
                'locksmith' => 'craft',
                'brewery' => 'craft',
                'winery' => 'craft',
                'pottery' => 'craft',

                // Nature
                'forest' => 'nature',
                'garden' => 'nature',
                'water' => 'nature',
                'beach' => 'nature',
            ]
        ];
    }

    /**
     * Retourne les filtres (spécialités) disponibles pour une famille donnée
     */
    public function getFiltresForFamily(string $familyKey): array
    {
        $this->ensureMappingLoaded();

        $filters = [];
        foreach (self::$mapping['specialties'] as $osmSub => $fam) {
            if ($fam === $familyKey) {
                // Pour l'instant on retourne la clé brute, la traduction sera faite côté UI ou via un service
                $filters[] = [
                    'key' => $osmSub,
                    'label' => $osmSub
                ];
            }
        }
        return $filters;
    }
    /**
     * Retourne tous les critères OSM mentionnés dans le mapping (pour filtrage global)
     */
    public function getAllMappedCriteria(): array
    {
        $this->ensureMappingLoaded();
        return [
            'categories' => array_keys(self::$mapping['categories_map']),
            'subcategories' => array_keys(self::$mapping['specialties'])
        ];
    }
}
