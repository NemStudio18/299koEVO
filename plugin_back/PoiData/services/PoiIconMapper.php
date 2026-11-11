<?php

namespace PoiData\Services;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour mapper les types/categories de POI vers des icônes FontAwesome.
 */
class PoiIconMapper
{
    private array $icons = [];

    public function __construct()
    {
        $this->loadIcons();
    }

    private function loadIcons(): void
    {
        $file = PLUGINS . 'searchEVO' . DS . 'param' . DS . 'category_icons.json';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->icons = json_decode($content, true) ?: [];
        }
    }

    /**
     * Retourne l'icône FontAwesome pour un type/category de POI.
     * 
     * @param string|null $type Type métier (ex: 'restauration', 'commerce')
     * @param string|null $category Category OSM (ex: 'restaurant', 'bakery')
     * @return string Nom de l'icône FontAwesome (sans préfixe 'fa-')
     */
    public function getIcon(?string $type, ?string $category): string
    {
        // 1. Chercher dans le mapping JSON chargé (priorité max)
        if (!empty($category) && isset($this->icons[$category])) {
            $icon = $this->icons[$category];
            return is_array($icon) ? ($icon['icon'] ?? 'map-marker-alt') : $icon;
        }
        if (!empty($type) && isset($this->icons[$type])) {
            $icon = $this->icons[$type];
            return is_array($icon) ? ($icon['icon'] ?? 'map-marker-alt') : $icon;
        }

        // 2. Mapping PHP par défaut (fallback)
        $categoryIcons = [
            // Restauration
            'restaurant' => 'utensils',
            'cafe' => 'coffee',
            'bar' => 'wine-glass',
            'fast_food' => 'hamburger',
            'ice_cream' => 'ice-cream',
            'food_court' => 'utensils',

            // Commerce
            'bakery' => 'bread-slice',
            'supermarket' => 'shopping-cart',
            'convenience' => 'store',
            'clothes' => 'tshirt',
            'shoes' => 'shoe-prints',
            'pharmacy' => 'pills',
            'bookshop' => 'book',
            'books' => 'book',
            'hairdresser' => 'scissors',
            'beauty_salon' => 'spa',
            'tobacco' => 'smoking',
            'seafood' => 'fish',
            'butcher' => 'cow',
            'cheese' => 'cheese',
            'coffee' => 'coffee',
            'wine' => 'wine-bottle',
            'alcohol' => 'wine-bottle',
            'chocolate' => 'cookie',
            'jewelry' => 'ring',
            'marketplace' => 'basket-shopping',
            'market' => 'basket-shopping',
            'shoemaker' => 'shoe-prints',
            'cobbler' => 'shoe-prints',
            'tailor' => 'scissors',
            'sewing' => 'scissors',
            'perfumery' => 'spray-can',
            'perfume' => 'spray-can',
            'computer' => 'laptop',

            // Santé
            'hospital' => 'hospital',
            'doctors' => 'user-md',
            'dentist' => 'tooth',
            'veterinary' => 'paw',
            'clinic' => 'clinic-medical',
            'optician' => 'glasses',
            'optician_health' => 'glasses',
            'physiotherapist' => 'hand-holding-heart',

            // Sport
            'swimming_pool' => 'swimming-pool',
            'gym' => 'dumbbell',
            'sports_centre' => 'futbol',
            'stadium' => 'futbol',
            'pitch' => 'futbol',

            // Culture
            'museum' => 'university',
            'theatre' => 'theater-masks',
            'cinema' => 'film',
            'library' => 'book',
            'artwork' => 'palette',

            // Transport
            'bus_station' => 'bus',
            'taxi' => 'taxi',
            'parking' => 'parking',
            'fuel' => 'gas-pump',
            'car_rental' => 'car',
            'bicycle_rental' => 'bicycle',

            // Hébergement
            'hotel' => 'bed',
            'hostel' => 'bed',
            'guest_house' => 'home',
            'bed_and_breakfast' => 'home',
            'motel' => 'bed',

            // Education
            'school' => 'school',
            'university' => 'university',
            'kindergarten' => 'child',

            // Autres
            'waste_disposal' => 'trash',
            'bench' => 'chair',
            'toilets' => 'restroom',
            'drinking_water' => 'tint',
            'fountain' => 'fountain',
            'atm' => 'money-bill-wave',
            'bank' => 'university',
        ];

        // Mapping par category (plus spécifique) d'abord, priorité à la catégorie OSM
        if (!empty($category) && isset($categoryIcons[$category])) {
            return $categoryIcons[$category];
        }

        // Mapping par type (plus général) en fallback
        $typeIcons = [
            'restauration' => 'utensils',
            'Restauration' => 'utensils',
            'commerce' => 'store',
            'Commerce' => 'store',
            'service' => 'briefcase',
            'Services publics' => 'building',
            'sante' => 'hospital',
            'Santé' => 'plus',
            'culture' => 'palette',
            'Culture' => 'palette',
            'sport' => 'dumbbell',
            'Sport' => 'dumbbell',
            'transport' => 'bus',
            'Transport' => 'bus',
            'hebergement' => 'bed',
            'Tourisme' => 'map-signs',
            'education' => 'graduation-cap',
            'Éducation' => 'graduation-cap',
            'nature' => 'tree',
            'Loisirs' => 'futbol',
            'artisanat' => 'hammer',
            'administration' => 'building',
            'religion' => 'place-of-worship',
            'Religion' => 'place-of-worship',
            'finance' => 'credit-card',
            'Finance' => 'credit-card',
            'autre' => 'map-marker-alt',
            'Autre' => 'map-marker-alt',
        ];

        if (!empty($type) && isset($typeIcons[$type])) {
            return $typeIcons[$type];
        }

        // Icône par défaut
        return 'map-marker-alt';
    }
}
