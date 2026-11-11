<?php

namespace PoiData\Services;

defined('ROOT') or exit('Access denied!');

/**
 * Service centralisé pour formater les données POI avec flags booléens pour les templates
 */
class PoiDataFormatter
{
    /**
     * Formate les données brutes d'un POI pour l'affichage dans les templates
     * Ajoute les flags booléens (has_phone, has_website, etc.) pour faciliter les conditions
     * 
     * @param array $row Données brutes du POI depuis la BDD
     * @param CategoryLabelTranslator $translator Traducteur pour les labels
     * @param bool $truncateDescription Tronquer la description à 200 caractères (pour les résultats)
     * @param PoiCategoryService|null $categoryService Service pour les overrides de catégories (optionnel)
     * @param array|null $preloadedCategories Catégories pré-chargées en batch ['poi_id' => [...], ...] (optionnel, pour éviter N+1)
     * @param bool $asList Indique si on formate pour une liste (affiche desc_list et liens categorisés)
     * @return array Données POI formatées avec flags booléens
     */
    public static function format($row, $translator = null, $truncateDescription = false, $categoryService = null, $preloadedCategories = null, $asList = false, $poiId = 0)
    {
        if ($poiId == 0) {
            $poiId = (int)($row['id'] ?? $row['poi_id'] ?? 0);
        }

        $categoryOverride = null;

        if ($categoryService && $poiId > 0) {

            // Utiliser les catégories pré-chargées si disponibles (optimisation N+1)
            if ($preloadedCategories !== null && isset($preloadedCategories[$poiId])) {
                $categoryOverride = $preloadedCategories[$poiId];
            } else {
                // Fallback : requête individuelle (si pas de batch loading)
                $categoryOverride = $categoryService->getPoiCategory($poiId, [
                    'category' => $row['category'] ?? null,
                    'subcategory' => $row['subcategory'] ?? null
                ]);
            }
        }

        // Utiliser l'override si disponible, sinon OSM
        if ($categoryOverride && $categoryOverride['source'] === 'override') {
            // Override existe : utiliser les valeurs éditoriales
            $categoryKey = $categoryOverride['category'];
            $subcategoryKey = $categoryOverride['subcategory'];
            $categoryLabel = $categoryOverride['category_label'];
            $subcategoryLabel = $categoryOverride['subcategory_label'];
            $categoryIcon = $categoryOverride['category_icon'];
        } else {
            // Pas d'override : utiliser OSM
            $categoryKey = $row['category'] ?? null;
            $subcategoryKey = $row['subcategory'] ?? null;
            $categoryLabel = null;
            $subcategoryLabel = null;
            $categoryIcon = null;
        }

        // S'assurer que le type est toujours une string (pas null) pour la route
        // IMPORTANT: Dans la BDD actuelle, category = catégorie principale (ex: "shop"), subcategory = type (ex: "bakery")
        // Mais dans l'ancienne BDD, ils étaient peut-être inversés. On utilise les valeurs telles quelles.
        $poiType = !empty($subcategoryKey) ? $subcategoryKey : 'poi';

        // Préparer la description (sera récupérée plus bas si nécessaire, ou on garde une base vide)
        $description = null;

        // Données de base
        $poi = [
            'id' => $poiId,
            'name' => $row['name'] ?? '',
            'category' => $categoryKey, // Utiliser l'override si disponible
            'subcategory' => $subcategoryKey, // Utiliser l'override si disponible
            'category_source' => $categoryOverride ? $categoryOverride['source'] : 'osm', // 'override' ou 'osm'
            'stars'       => (float)($row['stars'] ?? 0), // Existing stars entry
            'desc_list'   => $row['desc_list'] ?? null,
            'is_closed'   => $row['is_closed'] ?? false,
            'type' => $poiType,
            'description' => $description,
            'lat' => (float) ($row['lat'] ?? 0.0),
            'lon' => (float) ($row['lon'] ?? 0.0),
            'location' => ['lat' => (float) ($row['lat'] ?? 0.0), 'lon' => (float) ($row['lon'] ?? 0.0)],
            'addr_housenumber' => !empty($row['addr_housenumber']) ? trim($row['addr_housenumber']) : null,
            'addr_street' => !empty($row['addr_street']) ? trim($row['addr_street']) : null,
            'addr_city' => !empty($row['addr_city']) ? trim($row['addr_city']) : null,
            'addr_postcode' => !empty($row['addr_postcode']) ? trim($row['addr_postcode']) : null,
            'full_address' => implode(' ', array_filter([
                !empty($row['addr_housenumber']) ? trim($row['addr_housenumber']) : null,
                !empty($row['addr_street']) ? trim($row['addr_street']) : null,
                !empty($row['addr_postcode']) ? trim($row['addr_postcode']) : null,
                !empty($row['addr_city']) ? trim($row['addr_city']) : null,
            ])),
            'phone' => null,
            'website' => null,
            'opening_hours' => null,
            'brand' => null,
            'datascore' => (int) ($row['datascore'] ?? 0),
            'status' => $row['status'] ?? 'public',
            'claimed_by' => $row['claimed_by'] ?? null,
            'claimed' => !empty($row['claimed_by']),
            'claimed_validated' => !empty($row['claimed_by']),
            'stars_html' => self::formatStars((int)($row['stars'] ?? 0)),
            'tags' => [], // Tags ne sont pas utilisés pour l'affichage
            'custom_tags' => [], // Initialisation pour éviter les erreurs foreach
            'subcategories_labels' => [],
            'links' => [],
            'photos' => [],
            'logo' => self::normalizeMediaPath($row['logo'] ?? null, $poiId),
            'is_open_now_html' => (function($row) {
                if (!array_key_exists('is_open_now', $row) || $row['is_open_now'] === null) return '';
                
                $isOpen = (bool)$row['is_open_now'];
                $isClosingSoon = !empty($row['is_closing_soon']);

                if ($isOpen) {
                    if ($isClosingSoon) {
                        return '<span class="status-closing-soon" title="Ferme bientôt"><i class="fa-solid fa-clock" style="color: #ff9800;"></i> Ferme bientôt</span>';
                    }
                    return '<span class="status-open" title="Ouvert actuellement"><i class="fa-solid fa-clock" style="color: #28a745;"></i> Ouvert</span>';
                }
                return '<span class="status-closed" title="Fermé actuellement"><i class="fa-solid fa-clock" style="color: #dc3545;"></i> Fermé</span>';
            })($row),
            'opening_hours_today' => $row['opening_hours_today'] ?? null,
            'is_open_now' => array_key_exists('is_open_now', $row) && $row['is_open_now'] !== null ? (bool)$row['is_open_now'] : null,
            'is_closing_soon' => !empty($row['is_closing_soon']),
            'opening_hours_short' => !empty($row['opening_hours_today']) ? $row['opening_hours_today'] : 'Horaires disponibles',
            'category_icon' => $categoryIcon,
        ];

        // Traiter les tags personnalisés (JSON string -> Array)
        if (!empty($row['custom_tags'])) {
            if (is_string($row['custom_tags'])) {
                $decoded = json_decode($row['custom_tags'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $poi['custom_tags'] = $decoded;
                }
            } elseif (is_array($row['custom_tags'])) {
                $poi['custom_tags'] = $row['custom_tags'];
            }
        }

        $pdo = \Core\Database::getInstance()->getPdo();
        
        // --- Nouveauté : Sous-catégories et Links ---
        if ($poiId !== 0) {
            // Sous-catégories
            $stmtSub = $pdo->prepare("
                SELECT pse.label_fr as label 
                FROM poi_subcategories ps
                LEFT JOIN poi_subcategories_editorial pse ON pse.osm_key = ps.subcategory
                WHERE ps.poi_id = ? AND pse.active = 1
            ");
            $stmtSub->execute([$poiId]);
            $poi['subcategories_labels'] = $stmtSub->fetchAll(\PDO::FETCH_ASSOC);

            // Liens / Réseaux sociaux
            $stmtL = $pdo->prepare("SELECT label as platform, url FROM poi_links WHERE poi_id = ?");
            $stmtL->execute([$poiId]);
            $linksList = $stmtL->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($linksList)) {
                $poi['has_links'] = true;
                foreach ($linksList as $lk) {
                    $platform = strtolower($lk['platform']);
                    // Prepare icon structure for loops
                    $icon_class = 'fa-solid fa-link';
                    if (strpos($platform, 'facebook') !== false) {
                        $icon_class = 'fa-brands fa-facebook';
                        $poi['facebook'] = $lk['url'];
                        $poi['has_facebook'] = true;
                    } elseif (strpos($platform, 'instagram') !== false) {
                        $icon_class = 'fa-brands fa-instagram';
                        $poi['instagram'] = $lk['url'];
                        $poi['has_instagram'] = true;
                    } elseif (strpos($platform, 'linkedin') !== false) {
                        $icon_class = 'fa-brands fa-linkedin';
                        $poi['linkedin'] = $lk['url'];
                        $poi['has_linkedin'] = true;
                    } elseif (strpos($platform, 'twitter') !== false || strpos($platform, ' x') !== false || $platform === 'x') {
                        $icon_class = 'fa-brands fa-x-twitter';
                        $poi['twitter'] = $lk['url'];
                        $poi['has_twitter'] = true;
                    } elseif (strpos($platform, 'youtube') !== false) {
                        $icon_class = 'fa-brands fa-youtube';
                        $poi['youtube'] = $lk['url'];
                        $poi['has_youtube'] = true;
                    } elseif (strpos($platform, 'tiktok') !== false) {
                        $icon_class = 'fa-brands fa-tiktok';
                        $poi['tiktok'] = $lk['url'];
                        $poi['has_tiktok'] = true;
                    } elseif ($platform === 'website' || strpos($platform, 'site') !== false) {
                        $icon_class = 'fa-solid fa-globe';
                        $poi['website'] = $lk['url'];
                        $poi['has_website'] = true;
                    }
                    
                    $poi['links'][] = [
                        'platform' => $platform,
                        'url' => $lk['url'],
                        'icon_class' => $icon_class,
                        'label' => ucfirst($platform)
                    ];
                }
            } else {
                $poi['has_links'] = false;
            }
            
            // Photos
            $stmtP = $pdo->prepare("SELECT id, url, caption, is_cover, is_verified FROM poi_photos WHERE poi_id = ? ORDER BY is_cover DESC, created_at ASC");
            $stmtP->execute([$poiId]);
            $photos = $stmtP->fetchAll(\PDO::FETCH_ASSOC);
            
            $poi['cover_url'] = null;
            $poi['has_cover'] = false;

            if (!empty($photos)) {
                foreach ($photos as &$p) {
                    $p['url'] = self::normalizeMediaPath($p['url'], $poiId);
                    if ($p['is_cover'] && !$poi['has_cover']) {
                        $poi['cover_url'] = $p['url'];
                        $poi['has_cover'] = true;
                    }
                }
                unset($p);

                // Fallback sur la première photo si pas de cover explicite
                if (!$poi['has_cover'] && !empty($photos)) {
                    $poi['cover_url'] = $photos[0]['url'];
                    $poi['has_cover'] = true;
                }

                $poi['photos'] = $photos;
                $poi['has_photos'] = true;
            } else {
                $poi['has_photos'] = false;
            }

            // Description longue
            $stmtD = $pdo->prepare("SELECT description FROM poi_descriptions WHERE poi_id = ?");
            $stmtD->execute([$poiId]);
            $description = $stmtD->fetchColumn();
            if ($description) {
                if ($truncateDescription && mb_strlen($description) > 200) {
                    $poi['description'] = mb_substr($description, 0, 200) . '...';
                } else {
                    $poi['description'] = $description;
                }
                $poi['has_description'] = true;
            }

            // Cuisines
            $stmtCu = $pdo->prepare("SELECT cuisine FROM poi_cuisines WHERE poi_id = ?");
            $stmtCu->execute([$poiId]);
            $cuisines = $stmtCu->fetchAll(\PDO::FETCH_COLUMN);
            $poi['cuisines'] = $cuisines;
            $poi['has_cuisines'] = !empty($cuisines);
            $poi['cuisine_labels'] = implode(', ', $cuisines);

            // Feature Values (wheelchair, parking, etc)
            $stmtF = $pdo->prepare("
                SELECT f.key_name, fv.value 
                FROM poi_feature_values fv 
                JOIN poi_features f ON f.id = fv.feature_id 
                WHERE fv.poi_id = ?
            ");
            $stmtF->execute([$poiId]);
            $features = $stmtF->fetchAll(\PDO::FETCH_KEY_PAIR);
            foreach ($features as $fKey => $fVal) {
                $poi[$fKey] = $fVal;
                // Boolean compatibility
                if ($fVal === 'no') {
                    $poi['has_' . $fKey] = false;
                } elseif (!empty($fVal)) {
                    $poi['has_' . $fKey] = true;
                } else {
                    $poi['has_' . $fKey] = false;
                }
            }
        }

        // --- Nouveauté : Contacts ---
        // Récupérer les contacts pour ce POI
        $contactPhone = null;
        $contactEmail = null;
        $contactWebsite = null;
        if ($poiId !== 0) {
            $stmtC = $pdo->prepare("SELECT type, value FROM poi_contacts WHERE poi_id = ?");
            $stmtC->execute([$poiId]);
            foreach($stmtC->fetchAll(\PDO::FETCH_ASSOC) as $c) {
                $cType = strtolower(trim($c['type'] ?? ''));
                if ($cType === 'phone' || $cType === 'mobile') $contactPhone = $c['value'];
                if ($cType === 'email') $contactEmail = $c['value'];
                if ($cType === 'website') $contactWebsite = $c['value'];
            }
        }

        // Fallbacks depuis row (ex: vue poi_public) si non trouvé dans poi_contacts
        if (empty($contactPhone) && !empty($row['phone'])) $contactPhone = $row['phone'];
        if (empty($contactEmail) && !empty($row['email'])) $contactEmail = $row['email'];
        if (empty($contactWebsite) && !empty($row['website'])) $contactWebsite = $row['website'];

        // Définir phone et has_phone de manière cohérente
        if ($contactPhone !== null && trim($contactPhone) !== '') {
            $poi['phone'] = trim($contactPhone);
            $poi['has_phone'] = true;
        } else {
            $poi['phone'] = null;
            $poi['has_phone'] = false;
        }

        if ($contactEmail !== null && trim($contactEmail) !== '') {
            $poi['email'] = trim($contactEmail);
            $poi['has_email'] = true;
        } else {
            $poi['email'] = null;
            $poi['has_email'] = false;
        }

        // Si on a un site web dans contacts, l'ajouter à la liste des liens si pas déjà présent
        if ($contactWebsite && trim($contactWebsite) !== '') {
            $cleanWeb = trim($contactWebsite);
            $alreadyInLinks = false;
            if (!empty($poi['links'])) {
                foreach ($poi['links'] as $lk) {
                    if (strpos($lk['url'], $cleanWeb) !== false) {
                        $alreadyInLinks = true;
                        break;
                    }
                }
            }
            if (!$alreadyInLinks) {
                if (!isset($poi['links'])) $poi['links'] = [];
                $poi['links'][] = [
                    'platform' => 'website',
                    'url' => $cleanWeb,
                    'icon_class' => 'fa-solid fa-globe',
                    'label' => 'Site Web'
                ];
            }
            $poi['website'] = $cleanWeb;
            $poi['has_website'] = true;
            $poi['has_links'] = true;
        }

        // Fonction helper pour normaliser une URL
        $normalizeUrl = function ($url) {
            if (empty($url) || trim($url) === '') {
                return null;
            }
            $url = trim($url);
            // Si l'URL ne commence pas par http:// ou https://, ajouter https://
            if (!preg_match('/^https?:\/\//i', $url)) {
                $url = 'https://' . $url;
            }
            // Valider que c'est une URL valide
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                return null;
            }
            return $url;
        };

        // Traiter le site web
        $website = null;
        if ($contactWebsite) {
            $website = $normalizeUrl($contactWebsite);
        }

        // Traiter Facebook depuis poi_links (priorité à facebook_url)
        $facebook = null;
        if (!empty($row['facebook_url']) && trim($row['facebook_url']) !== '') {
            $facebook = $normalizeUrl($row['facebook_url']);
        }

        // Traiter Instagram depuis poi_links
        $instagram = null;
        if (!empty($row['instagram_url']) && trim($row['instagram_url']) !== '') {
            $instagram = $normalizeUrl($row['instagram_url']);
        }

        // Logique : site web en priorité, sinon premier réseau social trouvé
        if ($website) {
            $poi['website'] = $website;
            $poi['contact_website'] = $website; // Pour compatibilité
            $poi['has_website'] = true;
            $poi['social_url'] = null; // Pas de fallback si site web existe
            $poi['social_type'] = null;
            $poi['social_type_label'] = null;
            $poi['has_social'] = false;
        } elseif (!empty($poi['website'])) {
            $poi['contact_website'] = $poi['website'];
            $poi['has_website'] = true;
            $poi['social_url'] = null;
            $poi['social_type'] = null;
            $poi['social_type_label'] = null;
            $poi['has_social'] = false;
        } else {
            $poi['website'] = null;
            $poi['contact_website'] = null;
            $poi['has_website'] = false;

            // Fallback sur le premier réseau social trouvé
            if ($facebook) {
                $poi['social_url'] = $facebook;
                $poi['social_type'] = 'facebook';
                $poi['social_type_label'] = 'Facebook';
                $poi['is_facebook'] = true;
                $poi['is_instagram'] = false;
                $poi['has_social'] = true;
            } elseif ($instagram) {
                $poi['social_url'] = $instagram;
                $poi['social_type'] = 'instagram';
                $poi['social_type_label'] = 'Instagram';
                $poi['is_facebook'] = false;
                $poi['is_instagram'] = true;
                $poi['has_social'] = true;
            } else {
                $poi['social_url'] = null;
                $poi['social_type'] = null;
                $poi['social_type_label'] = null;
                $poi['is_facebook'] = false;
                $poi['is_instagram'] = false;
                $poi['has_social'] = false;
            }
        }

        // Garder les données brutes pour compatibilité
        $poi['facebook'] = $facebook;
        $poi['has_facebook'] = !empty($facebook);
        $poi['instagram'] = $instagram;
        $poi['has_instagram'] = !empty($instagram);

        // Activité récente
        $poi['is_active_this_month'] = false;
        if (!empty($row['last_activity_at'])) {
            $lastActivity = strtotime($row['last_activity_at']);
            // Si activité dans les 30 derniers jours
            if ($lastActivity > strtotime('-30 days')) {
                $poi['is_active_this_month'] = true;
            }
        }

        // Horaires structurés et format humain
        $poi['opening_hours_human'] = [];
        if (isset($row['id'])) {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM poi_opening_hours WHERE poi_id = ? ORDER BY day_of_week ASC, open_time ASC");
            $stmt->execute([$row['id']]);
            $ohRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($ohRows)) {
                $formatter = new OpeningHoursFormatter();
                $poi['opening_hours_human'] = $formatter->format($ohRows);
                $poi['has_opening_hours_human'] = !empty($poi['opening_hours_human']);
            }
        }

        // Traiter la marque
        $poiBrandName = null;
        if (!empty($row['brand_id'])) {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT name FROM poi_brands WHERE id = ?");
            $stmt->execute([$row['brand_id']]);
            $poiBrandName = $stmt->fetchColumn();
        }
        
        if ($poiBrandName) {
            $poi['brand'] = trim($poiBrandName);
            $poi['has_brand'] = true;
        } else {
            // Keep looking for old brand column just in case some other flow returns it
            if (!empty($row['brand']) && trim($row['brand']) !== '') {
                $poi['brand'] = trim($row['brand']);
                $poi['has_brand'] = true;
            } else {
                $poi['has_brand'] = false;
                $poi['brand'] = null;
            }
        }

        // Traiter la description (déjà récupérée si id != 0)
        if (empty($poi['description'])) {
            $poi['description'] = null;
            $poi['has_description'] = false;
        }

        // Formater l'adresse
        $addressData = self::formatAddress($poi);
        $poi['address'] = $addressData['full'];
        $poi['address_line1'] = $addressData['line1'];
        $poi['address_line2'] = $addressData['line2'];
        $poi['has_address'] = !empty($addressData['full']);

        // Ajouter les labels traduits
        // Si override existe, utiliser les labels éditoriaux, sinon traduire OSM
        if ($categoryOverride && $categoryOverride['source'] === 'override') {
            // Override : utiliser les labels éditoriaux
            $poi['category_label'] = $categoryLabel;
            $poi['type_label'] = $subcategoryLabel;
            $poi['category_icon'] = $categoryIcon;
        } else {
            // OSM : traduire avec CategoryLabelTranslator
            $categoryKeyForTranslation = $categoryKey ?? '';
            $subcategoryKeyForTranslation = $subcategoryKey ?? '';

            $poi['category_label'] = !empty($categoryKeyForTranslation) ? $translator->translate($categoryKeyForTranslation) : null;
            $poi['type_label'] = !empty($subcategoryKeyForTranslation) && $subcategoryKeyForTranslation !== 'poi' ? $translator->translate($subcategoryKeyForTranslation) : null;
            $poi['category_icon'] = null;

            // Ne pas afficher si le label est identique à la clé (pas de traduction trouvée)
            if ($poi['category_label'] === $categoryKeyForTranslation) {
                $poi['category_label'] = null;
            }
            if ($poi['type_label'] === $subcategoryKeyForTranslation) {
                $poi['type_label'] = null;
            }
        }

        // Formater les valeurs pour l'affichage (le moteur de template ne supporte pas les filtres)
        $poi['lat_formatted'] = number_format($poi['lat'], 4);
        $poi['lon_formatted'] = number_format($poi['lon'], 4);
        $poi['name_escaped'] = htmlspecialchars($poi['name'], ENT_QUOTES, 'UTF-8');

        // Gérer les URLs de détail (URL courte style /mon-poi-123)
        $poi['detail_url_final'] = $row['detail_url_final'] ?? $row['detail_url'] ?? null;
        if (!$poi['detail_url_final'] && $poiId !== 0) {
            // Pas d'URL de détail, essayer de reconstruire une URL searchEVO simple si on a un ID
            // On utilise le slug pour une URL plus propre
            $slug = self::createSlug($poi['name']);
            // ID positif : /slug-id | ID négatif (admin) : /slug--abs(id)
            if ($poiId < 0) {
                 $poi['detail_url_final'] = '/' . $slug . '--' . abs($poiId);
            } else {
                 $poi['detail_url_final'] = '/' . $slug . '-' . $poiId;
            }
        }
        $poi['has_detail_url'] = !empty($poi['detail_url_final']);

        // name_encoded sera généré par le contrôleur avec vérification des doublons
        $poi['name_encoded'] = urlencode($poi['name']);

        return $poi;
    }

    /**
     * Crée un slug URL-friendly (Version simplifiée de PoiRepository)
     */
    public static function createSlug(string $name): string
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
        $slug = strtr(mb_strtolower($name), $replacements);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Génère le HTML pour afficher les étoiles
     * @param int|null $stars Nombre d'étoiles (1-5)
     * @return string HTML des étoiles
     */
    public static function formatStars(?int $stars): string
    {
        if (!$stars || $stars < 1 || $stars > 5) {
            return '';
        }

        $html = '<span class="stars-badge" title="' . $stars . ' étoiles">';
        for ($i = 1; $i <= 5; $i++) {
            $class = $i <= $stars ? 'star-filled' : 'star-empty';
            $color = $i <= $stars ? '#ffd700' : '#ddd';
            $html .= '<i class="fa-solid fa-star ' . $class . '" style="color: ' . $color . ';"></i>';
        }
        $html .= '</span>';
        return $html;
    }

    /**
     * Formate une adresse complète à partir des données POI
     * 
     * @param array $poi Données POI
     * @return array Array avec 'full', 'line1', 'line2'
     */
    private static function formatAddress(array $poi): array
    {
        $line1 = [];
        $line2 = [];

        if (!empty($poi['addr_housenumber'])) {
            $line1[] = $poi['addr_housenumber'];
        }
        if (!empty($poi['addr_street'])) {
            $line1[] = $poi['addr_street'];
        }

        if (!empty($poi['addr_postcode'])) {
            $line2[] = $poi['addr_postcode'];
        }
        if (!empty($poi['addr_city'])) {
            $line2[] = $poi['addr_city'];
        }

        $sLine1 = implode(' ', $line1);
        $sLine2 = implode(' ', $line2);

        $full = $sLine1;
        if (!empty($sLine2)) {
            $full .= (!empty($full) ? ', ' : '') . $sLine2;
        }

        return [
            'full' => $full,
            'line1' => $sLine1,
            'line2' => $sLine2
        ];
    }

    /**
     * Ajoute la distance à un POI déjà formaté
     * 
     * @param array $poi POI déjà formaté
     * @param float|null $distanceKm Distance en kilomètres
     * @return array POI avec distance ajoutée
     */
    public static function addDistance(array $poi, ?float $distanceKm): array
    {
        if ($distanceKm !== null && $distanceKm >= 0) {
            $poi['distance'] = round($distanceKm, 2);
            $poi['distance_km'] = $poi['distance'];
            $poi['has_distance'] = true;

            // Formater la distance pour l'affichage
            if ($distanceKm < 1) {
                // Moins d'1 km : afficher en mètres
                $poi['distance_formatted'] = round($distanceKm * 1000) . ' m';
            } else {
                // 1 km ou plus : afficher en km avec 1 décimale
                $poi['distance_formatted'] = number_format($distanceKm, 1, ',', ' ') . ' km';
            }
        } else {
            $poi['distance'] = null;
            $poi['distance_km'] = null;
            $poi['has_distance'] = false;
            $poi['distance_formatted'] = null;
        }
          return $poi;
    }

    public static function normalizeMediaPath(?string $path, int $poiId = 0): ?string
    {
        if (empty($path) || trim($path) === '') {
            return null;
        }
        $path = trim($path);

        // Récupérer le siteUrl pour construire des URLs absolues
        $siteUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');

        // Si c'est un média lié à un POI (contient upload/poi/)
        // On force le passage par le proxy pour éviter les erreurs 500 sur /data/upload
        if ($poiId !== 0 && strpos($path, 'upload/poi/') !== false) {
             $filename = basename($path);
             return $siteUrl . '/common/image.php?poi=' . $poiId . '&file=' . urlencode($filename);
        }
        
        // Si c'est une URL absolue restante (externe), on la laisse
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        // Si on a un POI ID et que c'est une image locale (ex: juste un nom de fichier)
        if ($poiId !== 0 && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $path)) {
            $filename = basename($path);
            return $siteUrl . '/common/image.php?poi=' . $poiId . '&file=' . urlencode($filename);
        }

        // Cas général pour les autres médias
        if (strpos($path, 'upload/') === 0) {
            return $siteUrl . '/data/' . $path;
        }
        if (strpos($path, 'data/upload/') === 0) {
            return $siteUrl . '/' . $path;
        }
        
        return $path;
    }
}