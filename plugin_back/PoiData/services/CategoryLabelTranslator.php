<?php

namespace PoiData\Services;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour traduire les catégories OSM en labels français naturels.
 */
class CategoryLabelTranslator
{
    /**
     * Dictionnaire complet de traduction catégorie OSM → label français
     * 
     * @var array<string, string>
     */
    private static array $translations = [
        // CATÉGORIES PRINCIPALES (category) - en minuscules pour correspondre à la BDD
        'amenity' => 'Équipement',
        'shop' => 'Commerce',
        'craft' => 'Artisanat',
        'tourism' => 'Tourisme',
        'leisure' => 'Loisirs',
        'office' => 'Bureau',
        'historic' => 'Historique',
        'natural' => 'Nature',
        'place_of_worship' => 'Lieu de culte',
        'school' => 'École',
        'parking' => 'Parking',
        'post_office' => 'La Poste',
        'bank' => 'Banque',
        'man_made' => 'Aménagement',
        'healthcare' => 'Santé',
        'emergency' => 'Urgence',
        'sport' => 'Sport',
        'office' => 'Bureaux',

        // SOUS-CATÉGORIES (subcategory) - en minuscules
        // RESTAURATION
        'restaurant' => 'Restaurant',
        'cafe' => 'Café',
        'bar' => 'Bar',
        'fast_food' => 'Restauration rapide',
        'food_court' => 'Food Court',
        'ice_cream' => 'Glacier',
        'bistro' => 'Bistrot',
        'pizzeria' => 'Pizzeria',
        'biergarten' => 'Biergarten',

        // COMMERCE
        'bakery' => 'Boulangerie',
        'supermarket' => 'Supermarché',
        'convenience' => 'Épicerie',
        'butcher' => 'Boucherie',
        'greengrocer' => 'Primeur',
        'clothes' => 'Vêtements',
        'car' => 'Concessionnaire auto',
        'car_repair' => 'Garage',
        'hairdresser' => 'Coiffeur',
        'beauty_salon' => 'Beauté',
        'florist' => 'Fleuriste',
        'books' => 'Librairie',
        'electronics' => 'Informatique',
        'computer' => 'Informatique',
        'jewelry' => 'Bijouterie',
        'shoes' => 'Chaussures',
        'shoemaker' => 'Cordonnier',
        'cobbler' => 'Cordonnier',
        'tailor' => 'Couturier',
        'sewing' => 'Couturier',
        'perfumery' => 'Parfumerie',
        'perfume' => 'Parfumerie',
        'bicycle' => 'Vélo',
        'toys' => 'Jouets',
        'pet' => 'Animalerie',
        'coffee' => 'Torréfaction',
        'seafood' => 'Poissonnier',
        'cheese' => 'Crémerie/Fromagerie',
        'tobacco' => 'Bar/Tabac',
        'wine' => 'Caviste',
        'alcohol' => 'Caviste',
        'chocolate' => 'Chocolaterie',
        'farm' => 'Ferme',
        'gift' => 'Cadeaux',
        'stationery' => 'Papeterie',
        'chemist' => 'Parapharmacie',
        'optician' => 'Opticien',
        'carpet' => 'Tapis',
        'furniture' => 'Meubles',
        'hardware' => 'Quincaillerie',
        'laundry' => 'Laverie',
        'dry_cleaning' => 'Pressing',
        'mall' => 'Centre commercial',
        'department_store' => 'Grand magasin',
        'variety_store' => 'Variétés',
        'marketplace' => 'Marché',
        'market' => 'Marché',
        'beauty' => 'Beauté',
        'newsagent' => 'Presse',
        'doityourself' => 'Bricolage',
        'kitchen' => 'Cuisine',
        'interior_decoration' => 'Décoration intérieure',
        'garden_centre' => 'Jardinerie',
        'convenience;gas' => 'Station-service',
        'motorcycle' => 'Moto',
        'trade' => 'Commerce',
        'tattoo' => 'Tatouage',
        'paint' => 'Peinture',
        'electrical' => 'Électricité',
        'vacant' => 'Vacant',
        'e-cigarette' => 'Cigarette électronique',
        'leather' => 'Maroquinerie',
        'hearing_aids' => 'Audioprothèse',
        'gas' => 'Gaz',
        'deli' => 'Traiteur',
        'frozen_food' => 'Surgelés',
        'houseware' => 'Articles ménagers',
        'cosmetics' => 'Cosmétiques',
        'fabric' => 'Tissus',
        'funeral_directors' => 'Pompes funèbres',
        'fireplace' => 'Cheminée',
        'car_parts' => 'Pièces auto',
        'outpost' => 'Dépôt',
        'musical_instrument' => 'Instrument de musique',
        'agrarian' => 'Agrarian',
        'tiles' => 'Carrelage',
        'second_hand' => 'Occasion',
        'pastry' => 'Pâtisserie',
        'fashion_accessories' => 'Accessoires mode',
        'mobile_phone' => 'Téléphonie',
        'caravan' => 'Caravane',
        'antiques' => 'Antiquités',
        'tea' => 'Thé',
        'games' => 'Jeux',
        'pet_grooming' => 'Toilettage animal',
        'truck' => 'Camion',
        'anime' => 'Manga/Anime',
        'tool_hire' => 'Location d\'outils',
        'printing' => 'Imprimerie',
        'watches' => 'Horlogerie',
        'art' => 'Art',
        'video_games' => 'Jeux vidéo',
        'baby_goods' => 'Puériculture',
        'dairy' => 'Laitier',
        'household_linen' => 'Linge de maison',
        'gold_buyer' => 'Rachat d\'or',
        'bathroom_furnishing' => 'Salle de bain',
        'wigs' => 'Perruques',
        'weapons' => 'Armes',
        'medical_supply' => 'Matériel médical',
        'locksmith' => 'Serrurier',
        'pawnbroker' => 'Prêteur sur gages',
        'outdoor' => 'Extérieur',
        'general' => 'Général',
        'groundskeeping' => 'Entretien espaces verts',
        'rental' => 'Location',
        'hunting' => 'Chasse',
        'charity' => 'Charité',
        'glaziery' => 'Vitrerie',
        'repair' => 'Réparation',
        'window_blind' => 'Stores',
        'vacuum_cleaner' => 'Aspirateurs',
        'tyres' => 'Pneus',
        'photo' => 'Photo',
        'confectionery' => 'Confiserie',
        'bed' => 'Literie',

        // SANTÉ
        'pharmacy' => 'Pharmacie',
        'hospital' => 'Hôpital',
        'clinic' => 'Clinique',
        'doctors' => 'Médecin',
        'dentist' => 'Dentiste',
        'veterinary' => 'Vétérinaire',
        'physiotherapist' => 'Kinésithérapeute',
        'optician_health' => 'Ophtalmologiste',
        'health_centre' => 'Centre de santé',

        // SERVICE
        'atm' => 'Distributeur',
        'bureau_de_change' => 'Bureau de change',
        'insurance' => 'Assurance',
        'lawyer' => 'Avocat',
        'notary' => 'Notaire',
        'accountant' => 'Expert-comptable',
        'estate_agent' => 'Agence immobilière',
        'travel_agency' => 'Agence de voyage',
        'car_wash' => 'Lavage auto',
        'car_rental' => 'Location de voiture',
        'bicycle_rental' => 'Location de vélo',
        'library' => 'Bibliothèque',
        'post_box' => 'Boîte aux lettres',

        // HÉBERGEMENT
        'hotel' => 'Hôtel',
        'hostel' => 'Auberge de jeunesse',
        'guest_house' => 'Chambre d\'hôte',
        'motel' => 'Motel',
        'apartment' => 'Appartement',
        'camp_site' => 'Camping',

        // CULTURE
        'museum' => 'Musée',
        'gallery' => 'Galerie d\'art',
        'theatre' => 'Théâtre',
        'cinema' => 'Cinéma',
        'arts_centre' => 'Centre culturel',
        'library_culture' => 'Bibliothèque',
        'artwork' => 'Œuvre d\'art',
        'monument' => 'Monument',

        // SPORT & LOISIRS
        'sports_centre' => 'Complexe sportif',
        'fitness_centre' => 'Salle de sport',
        'swimming_pool' => 'Piscine',
        'stadium' => 'Stade',
        'pitch' => 'Terrain',
        'golf_course' => 'Golf',
        'tennis' => 'Tennis',
        'leisure_sport' => 'Loisir sportif',
        'leisure' => 'Loisirs',

        // TRANSPORT
        'fuel' => 'Station-service',
        'parking' => 'Parking',
        'parking_space' => 'Place de parking',
        'bicycle_parking' => 'Parking vélo',
        'taxi' => 'Taxi',
        'bus_station' => 'Gare routière',
        'car_rental' => 'Location de voiture',
        'bicycle_rental' => 'Location de vélo',
        'fuel' => 'Station-service',
        'train_station' => 'Gare',
        'airport' => 'Aéroport',

        // ÉDUCATION
        'school' => 'École',
        'university' => 'Université',
        'college' => 'Collège',
        'kindergarten' => 'École maternelle',
        'driving_school' => 'Auto-école',
        'language_school' => 'École de langue',
        'music_school' => 'École de musique',
        'dance_school' => 'École de danse',

        // ADMINISTRATION
        'townhall' => 'Mairie',
        'courthouse' => 'Palais de justice',
        'police' => 'Police',
        'fire_station' => 'Pompiers',
        'embassy' => 'Ambassade',
        'community_centre' => 'Centre communautaire',

        // NATURE
        'park' => 'Parc',
        'garden' => 'Jardin',
        'nature_reserve' => 'Réserve naturelle',
        'forest' => 'Forêt',
        'beach' => 'Plage',
        'playground' => 'Aire de jeu',

        // NOUVEAUTÉS
        'financial_advisor' => 'Conseiller financier',
        'forestry' => 'Sylviculture',
        'foundation' => 'Fondation',
        'government' => 'Gouvernement',
        'graphic_design' => 'Graphiste',
        'health' => 'Santé',
        'helping_youth' => 'Aide à la jeunesse',
        'incubator' => 'Incubateur',
        'it' => 'Informatique (IT)',
        'logistics' => 'Logistique',
        'moving_company' => 'Déménagement',
        'newspaper' => 'Journal / Presse',
        'ngo' => 'ONG',
        'personal_services' => 'Services à la personne',
        'personnal_service' => 'Services à la personne',
        'physician' => 'Médecin',
        'political_party' => 'Parti politique',
        'politician' => 'Homme politique',
        'private' => 'Privé',
        'private_investigator' => 'Détective privé',
        'property_developer' => 'Promoteur immobilier',
        'property_management' => 'Gestion immobilière',
        'quango' => 'Organisation Para-publique',
        'reception' => 'Réception',
        'religion' => 'Religion',
        'research' => 'Recherche',
        'security' => 'Sécurité',
        'surveyor' => 'Géomètre',
        'tax_advisor' => 'Conseiller fiscal',
        'telecommunication' => 'Télécoms',
        'therapist' => 'Thérapeute',
        'transport' => 'Transport',
        'travel_agent' => 'Agence de voyage',
        'tutoring' => 'Soutien scolaire',
        'union' => 'Syndicat',
        'water_utility' => 'Service des eaux',
        '3d_printing' => 'Impression 3D',
        'accessoires_poids_lourds' => 'Accessoires Poids Lourds',
        'apiary' => 'Rucher',
        'appliance' => 'Électroménager',
        'bag' => 'Maroquinerie / Sacs',
        'battery' => 'Batteries',
        'beauty_products' => 'Produits de beauté',
        'beverages' => 'Boissons',
        'boat' => 'Bateaux',
        'bookmaker' => 'Bookmaker',
        'boutique' => 'Boutique',
        'build' => 'Construction',
        'building_materials' => 'Matériaux de construction',
        'camera' => 'Photographie',
        'cannabis' => 'Cannabis CBD',
        'canoe_hire' => 'Location canoë',
        'cleaning_supplies' => 'Produits d\'entretien',
        'clothe' => 'Vêtements',
        'collector' => 'Collectionneur',
        'conserves' => 'Conserverie',
        'constructions_store' => 'Magasin de construction',
        'copyshop' => 'Reprographie',
        'curtain' => 'Rideaux',
        'deco' => 'Décoration',
        'doors' => 'Portes / Menuiseries',
        'energy' => 'Énergie',
        'erotic' => 'Érotique (Love Shop)',
        'esoteric' => 'Ésotérique',
        'ethnic_food' => 'Cuisine ethnique',
        'exotic' => 'Exotique',
        'firewood' => 'Bois de chauffage',
        'flooring' => 'Revêtements de sol',
        'flour' => 'Farine / Minoterie',
        'food' => 'Alimentation',
        'frame' => 'Encadrements',
        'garden_furniture' => 'Mobilier de jardin',
        'garden_machinery' => 'Machines de jardin',
        'ticket' => 'Billetterie',
        'grocery' => 'Épicerie',
        'haberdashery' => 'Mercerie',
        'hairdresser_supply' => 'Matériel de coiffure',
        'health_food' => 'Diététique / Bio',
        'herbalist' => 'Herboriste',
        'hifi' => 'Hi-Fi / Son',
        'hobby' => 'Loisirs créatifs',
        'honey' => 'Miel / Apiculteur',
        'household' => 'Maison',
        'industrial_automation' => 'Automatisation',
        'informatique' => 'Informatique',
        'iron_works' => 'Ferronnerie',
        'kiosk' => 'Kiosque',
        'lighting' => 'Luminaires',
        'lottery' => 'Loterie / Jeux',
        'massage' => 'Massage',
        'medical' => 'Médical',
        'miel' => 'Miel / Apiculteur',
        'mobile_phone_accessories' => 'Accessoires téléphonie',
        'model' => 'Modélisme',
        'money_lender' => 'Prêteur',
        'motoculture' => 'Motoculture',
        'motorcycle_repair' => 'Réparation moto',
        'music' => 'Musique',
        'video' => 'Vidéo',
        'nutrition_supplements' => 'Compléments alimentaires',
        'optical_instruments' => 'Optique',
        'party' => 'Articles de fête',
        'photo_studio' => 'Studio Photo',
        'piercing' => 'Piercing',
        'plant_hire' => 'Location de matériel',
        'pool' => 'Piscines',
        'pools' => 'Piscines',
        'printer_ink' => 'Cartouches d\'encre',
        'psychic' => 'Voyance',
        'pyrotechnics' => 'Pyrotechnie',
        'radiotechnics' => 'Radiotechnie',
        'scuba_diving' => 'Plongée',
        'shoe_repair' => 'Cordonnerie',
        'skate' => 'Skate shop',
        'souvenir' => 'Souvenirs',
        'spices' => 'Épices',
        'storage_rental' => 'Location box',
        'stuffs_location_store' => 'Location matériel divers',
        'tractor' => 'Tracteurs',
        'tractor_repair' => 'Réparation tracteurs',
        'trailer' => 'Remorques',
        'truck_repair' => 'Garage poids-lourds',
        'watch' => 'Horlogerie',
        'watches/jewelry' => 'Montres & Bijoux',
        'water_sports' => 'Sports nautiques',
        'wholesale' => 'Grossiste',
        'wool' => 'Laine',
        'aikido' => 'Aïkido',
        'archery' => 'Tir à l\'arc',
        'athletics' => 'Athlétisme',
        'aviron' => 'Aviron',
        'badminton' => 'Badminton',
        'basketball' => 'Basket-ball',
        'billiards' => 'Billard',
        'bmx' => 'BMX',
        'boules' => 'Pétanque',
        'canoe' => 'Canoë',
        'chasse' => 'Chasse',
        'climbing' => 'Escalade',
        'climbing_adventure' => 'Accrobranche',
        'paintball' => 'Paintball',
        'equestrian' => 'Équitation',
        'cycling' => 'Cyclisme',
        'fitness' => 'Fitness',
        'golf' => 'Golf',
        'gymnastics' => 'Gymnastique',
        'handball' => 'Handball',
        'judo' => 'Judo',
        'karate' => 'Karaté',
        'karting' => 'Karting',
        'model_aerodrome' => 'Aéromodélisme',
        'motocross' => 'Motocross',
        'motor' => 'Sport mécanique',
        'rallycross' => 'Rallycross',
        'multi' => 'Omnisports',
        'rowing' => 'Aviron',
        'rugby' => 'Rugby',
        'rugby_union' => 'Rugby à XV',
        'running' => 'Course à pied / Running',
        'swimming' => 'Natation',
        'sailing' => 'Voile',
        'shooting' => 'Tir sportif',
        'soccer' => 'Football',
        'squash' => 'Squash',
        'table_tennis' => 'Tennis de table',
        'twirling_baton' => 'Twirling bâton',
        'ultralight_aviation' => 'ULM',
        'voile_et_paddle' => 'Voile & Paddle',
        'water_ski' => 'Ski nautique',
        'yoga' => 'Yoga',
        'aquarium' => 'Aquarium',
        'attraction' => 'Attraction touristique',
        'camp_pitch' => 'Emplacement camping',
        'caravan_site' => 'Caravaning',
        'chalet' => 'Chalet',
        'Office de tourisme' => 'Office de Tourisme',
        'picnic_site' => 'Aire de pique-nique',
        'spa_resort' => 'Station thermale / Spa',
        'theme_park' => 'Parc à thèmes',
        'trail_riding_station' => 'Centre équestre',
        'viewpoint' => 'Point de vue',
        'wilderness_hut' => 'Refuge',
        'zoo' => 'Zoo',
        'coworking_space' => 'Espace coworking',
        'martial_arts' => 'Arts martiaux',

        // AUTRE
        'toilets' => 'Toilettes',
        'bench' => 'Banc',
        'waste_basket' => 'Poubelle',
        'recycling' => 'Recyclage',
        'drinking_water' => 'Point d\'eau',
        'fountain' => 'Fontaine',
        'shelter' => 'Abri',
        'picnic_table' => 'Table de pique-nique',
        'information' => 'Point d\'information',
        'vending_machine' => 'Distributeur automatique',
        'telephone' => 'Cabine téléphonique',
        'charging_station' => 'Borne de recharge',
    ];

    /**
     * Traduit une catégorie OSM en label français.
     * 
     * @param string|null $categoryOsm Catégorie OSM (ex: 'restaurant', 'hairdresser', 'car')
     * @param string|null $typeMetier Type métier optionnel pour affiner la traduction
     * @return string Label français (ex: 'Restaurant', 'Coiffeur', 'Concessionnaire auto')
     */
    public function translate(?string $categoryOsm, ?string $typeMetier = null): string
    {
        if (empty($categoryOsm)) {
            return 'Non spécifié';
        }

        // Normaliser en minuscules pour la recherche
        $categoryOsmLower = strtolower(trim($categoryOsm));

        // Vérifier d'abord dans le dictionnaire (avec la clé en minuscules)
        if (isset(self::$translations[$categoryOsmLower])) {
            return self::$translations[$categoryOsmLower];
        }

        // Si pas trouvé, capitaliser et remplacer les underscores par des espaces
        // Ex: "car_repair" -> "Car Repair"
        $fallback = ucfirst(str_replace('_', ' ', $categoryOsmLower));

        // Traductions contextuelles selon le type métier
        if ($typeMetier !== null) {
            $contextualTranslations = [
                'commerce' => [
                    'car' => 'Concessionnaire auto',
                    'car_repair' => 'Garage',
                    'clothes' => 'Vêtements',
                    'convenience' => 'Épicerie',
                ],
                'restauration' => [
                    'fast_food' => 'Fast Food',
                    'food_court' => 'Food Court',
                ],
                'sante' => [
                    'pharmacy' => 'Pharmacie',
                    'clinic' => 'Clinique',
                ],
                'service' => [
                    'car_repair' => 'Garage',
                    'car_wash' => 'Lavage auto',
                ],
            ];

            if (isset($contextualTranslations[$typeMetier][$categoryOsmLower])) {
                return $contextualTranslations[$typeMetier][$categoryOsmLower];
            }
        }

        return $fallback;
    }

    /**
     * Retourne toutes les traductions disponibles.
     * 
     * @return array<string, string>
     */
    public static function getAllTranslations(): array
    {
        return self::$translations;
    }

    /**
     * Traduit un label français en clé OSM (traduction inverse).
     * 
     * @param string|null $labelFr Label français (ex: 'Boulangerie', 'Restaurant')
     * @return array<string> Liste des clés OSM possibles (ex: ['bakery'], ['restaurant'])
     */
    public function reverseTranslate(?string $labelFr): array
    {
        if (empty($labelFr)) {
            return [];
        }

        // Normaliser le label français pour la recherche
        $labelFrLower = strtolower(trim($labelFr));
        $labelFrNormalized = str_replace([' ', '-', '_'], '_', $labelFrLower);

        $osmKeys = [];

        // Parcourir le dictionnaire pour trouver les clés OSM correspondantes
        foreach (self::$translations as $osmKey => $frenchLabel) {
            $frenchLabelLower = strtolower(trim($frenchLabel));
            $frenchLabelNormalized = str_replace([' ', '-', '_'], '_', $frenchLabelLower);

            // Correspondance exacte
            if ($frenchLabelLower === $labelFrLower || $frenchLabelNormalized === $labelFrNormalized) {
                $osmKeys[] = $osmKey;
            }
        }

        // Si pas de correspondance exacte, chercher une correspondance partielle
        if (empty($osmKeys)) {
            foreach (self::$translations as $osmKey => $frenchLabel) {
                $frenchLabelLower = strtolower(trim($frenchLabel));

                // Correspondance partielle (contient le label)
                if (strpos($frenchLabelLower, $labelFrLower) !== false || strpos($labelFrLower, $frenchLabelLower) !== false) {
                    $osmKeys[] = $osmKey;
                }
            }
        }

        // Si toujours rien, essayer directement avec le label normalisé comme clé OSM
        if (empty($osmKeys)) {
            $osmKeys[] = $labelFrNormalized;
        }

        return array_unique($osmKeys);
    }
}

