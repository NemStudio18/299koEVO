<?php

namespace PoiEdit\Services;

use PDO;
use PDOException;
use Core\Core;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour gérer les types et catégories personnalisés
 */
class PoiTypesService
{
    private PDO $pdo;
    private string $tablePrefix;
    private ?Core $core;

    public function __construct(PDO $pdo, string $tablePrefix = '', ?Core $core = null)
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
        $this->core = $core ?? (class_exists('\Core\Core') ? \Core\Core::getInstance() : null);
        $this->ensureSchema();
    }

    private function log(string $message, string $level = 'info'): void
    {
        if ($this->core && method_exists($this->core, 'getLogger')) {
            $logger = $this->core->getLogger();
            if ($level === 'error') {
                $logger->error($message);
            } elseif ($level === 'warning') {
                $logger->warning($message);
            } else {
                $logger->info($message);
            }
        } else {
            error_log($message);
        }
    }

    /**
     * Crée les tables nécessaires
     */
    private function ensureSchema(): void
    {
        try {
            // Table poi_custom_types : types personnalisés ajoutés par l'admin
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->tablePrefix}poi_custom_types (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                type_key VARCHAR(50) NOT NULL,
                type_label VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                icon VARCHAR(100) DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_by BIGINT(20) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_type_key (type_key),
                KEY idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                error_log("PoiTypesService::ensureSchema - Error creating poi_custom_types: " . $e->getMessage());
            }
        }

        try {
            // Table poi_custom_categories : catégories personnalisées par type
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->tablePrefix}poi_custom_categories (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                type_key VARCHAR(50) NOT NULL,
                category_key VARCHAR(100) NOT NULL,
                category_label VARCHAR(255) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                icon VARCHAR(100) DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_by BIGINT(20) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_type_category (type_key, category_key),
                KEY idx_type_key (type_key),
                KEY idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                error_log("PoiTypesService::ensureSchema - Error creating poi_custom_categories: " . $e->getMessage());
            }
        }

        try {
            // Table poi_type_proposals : propositions de types/catégories par les propriétaires
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->tablePrefix}poi_type_proposals (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                poi_id BIGINT(20) DEFAULT NULL,
                user_id BIGINT(20) NOT NULL,
                proposal_type ENUM('type', 'category') NOT NULL,
                type_key VARCHAR(50) DEFAULT NULL,
                category_key VARCHAR(100) DEFAULT NULL,
                label VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                justification TEXT DEFAULT NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                reviewed_by BIGINT(20) DEFAULT NULL,
                reviewed_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_poi_id (poi_id),
                KEY idx_user_id (user_id),
                KEY idx_status (status),
                KEY idx_proposal_type (proposal_type),
                CONSTRAINT fk_type_proposals_poi FOREIGN KEY (poi_id) REFERENCES {$this->tablePrefix}poi (id) ON DELETE SET NULL,
                CONSTRAINT fk_type_proposals_user FOREIGN KEY (user_id) REFERENCES {$this->tablePrefix}users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                error_log("PoiTypesService::ensureSchema - Error creating poi_type_proposals: " . $e->getMessage());
            }
        }
    }

    /**
     * Récupère tous les types (standards + personnalisés)
     */
    public function getAllTypes(): array
    {
        // Types standards (14 types métier)
        $standardTypes = [
            'restauration' => 'Restauration',
            'commerce' => 'Commerce',
            'service' => 'Service',
            'artisanat' => 'Artisanat',
            'sante' => 'Santé',
            'education' => 'Éducation',
            'administration' => 'Administration',
            'transport' => 'Transport',
            'culture' => 'Culture',
            'sport' => 'Sport',
            'tourisme' => 'Tourisme',
            'hebergement' => 'Hébergement',
            'nature' => 'Nature',
            'autre' => 'Autre',
        ];

        // Types personnalisés depuis la BDD
        $customTypes = [];
        try {
            $stmt = $this->pdo->query("SELECT type_key, type_label, description, icon 
                                       FROM {$this->tablePrefix}poi_custom_types 
                                       WHERE is_active = 1 
                                       ORDER BY type_label ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $customTypes[$row['type_key']] = [
                    'label' => $row['type_label'],
                    'description' => $row['description'],
                    'icon' => $row['icon'],
                    'is_custom' => true,
                ];
            }
        } catch (PDOException $e) {
            $this->log("Error fetching custom types: " . $e->getMessage(), 'error');
        }

        // Fusionner (les personnalisés écrasent les standards si même clé)
        $allTypes = [];
        foreach ($standardTypes as $key => $label) {
            $allTypes[$key] = [
                'label' => $label,
                'is_custom' => false,
            ];
        }
        foreach ($customTypes as $key => $data) {
            $allTypes[$key] = $data;
        }

        return $allTypes;
    }

    /**
     * Récupère toutes les catégories pour un type donné
     * Inclut les catégories personnalisées ET les catégories existantes dans poi.category
     */
    public function getCategoriesForType(string $typeKey): array
    {
        $categories = [];

        // 1. Catégories personnalisées depuis poi_custom_categories
        try {
            $stmt = $this->pdo->prepare("SELECT category_key, category_label, description, icon 
                                        FROM {$this->tablePrefix}poi_custom_categories 
                                        WHERE type_key = ? AND is_active = 1 
                                        ORDER BY category_label ASC");
            $stmt->execute([$typeKey]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $key = $row['category_key'];
                $categories[$key] = [
                    'label' => $row['category_label'] ?: $key,
                    'description' => $row['description'],
                    'icon' => $row['icon'],
                    'is_custom' => true,
                ];
            }
        } catch (PDOException $e) {
            $this->log("Error fetching custom categories for type {$typeKey}: " . $e->getMessage(), 'error');
        }

        // 2. Catégories existantes dans poi_public.subcategory pour ce type (découverte automatique)
        // Note: typeKey est un type personnalisé (ex: "commerce"), on doit mapper vers la valeur OSM (ex: "shop")
        // puis chercher les subcategory OSM correspondantes
        $typeToOsmMapping = [
            'commerce' => 'shop',
            'service' => 'amenity',
            'artisanat' => 'craft',
            'sante' => 'healthcare',
            'education' => 'school',
            'administration' => 'office',
            'transport' => 'public_transport',
            'tourisme' => 'tourism',
            'culture' => 'leisure',
            'sport' => 'sport',
            'hebergement' => 'accommodation',
            'nature' => 'natural',
        ];
        
        $osmCategory = isset($typeToOsmMapping[$typeKey]) ? $typeToOsmMapping[$typeKey] : $typeKey;
        
        try {
            $stmt = $this->pdo->prepare("SELECT DISTINCT subcategory, COUNT(*) as count 
                                        FROM {$this->tablePrefix}poi_public 
                                        WHERE category = ? AND subcategory IS NOT NULL AND subcategory != '' 
                                        GROUP BY subcategory 
                                        ORDER BY count DESC, subcategory ASC");
            $stmt->execute([$osmCategory]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $key = $row['subcategory'];
                // Ne pas écraser une catégorie personnalisée si elle existe déjà
                if (!isset($categories[$key])) {
                    $categories[$key] = [
                        'label' => $key, // Utiliser la clé comme label par défaut
                        'description' => null,
                        'icon' => null,
                        'is_custom' => false,
                        'count' => (int)$row['count'], // Nombre de POI avec cette catégorie
                    ];
                }
            }
        } catch (PDOException $e) {
            $this->log("Error fetching existing categories for type {$typeKey}: " . $e->getMessage(), 'error');
        }

        return $categories;
    }

    /**
     * Ajoute un type personnalisé
     */
    public function addCustomType(string $typeKey, string $typeLabel, ?string $description = null, ?string $icon = null, ?int $userId = null): bool
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO {$this->tablePrefix}poi_custom_types 
                                        (type_key, type_label, description, icon, created_by) 
                                        VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$typeKey, $typeLabel, $description, $icon, $userId]);
        } catch (PDOException $e) {
            $this->log("Error adding custom type: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Ajoute une catégorie personnalisée
     */
    public function addCustomCategory(string $typeKey, string $categoryKey, ?string $categoryLabel = null, ?string $description = null, ?string $icon = null, ?int $userId = null): bool
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO {$this->tablePrefix}poi_custom_categories 
                                        (type_key, category_key, category_label, description, icon, created_by) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$typeKey, $categoryKey, $categoryLabel ?: $categoryKey, $description, $icon, $userId]);
        } catch (PDOException $e) {
            $this->log("Error adding custom category: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Propose un nouveau type ou catégorie (par un propriétaire)
     */
    public function proposeTypeOrCategory(int $userId, string $proposalType, ?int $poiId, ?string $typeKey, ?string $categoryKey, string $label, ?string $description = null, ?string $justification = null): ?int
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO {$this->tablePrefix}poi_type_proposals 
                                        (poi_id, user_id, proposal_type, type_key, category_key, label, description, justification) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$poiId, $userId, $proposalType, $typeKey, $categoryKey, $label, $description, $justification]);
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->log("Error proposing type/category: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Récupère les propositions en attente
     */
    public function getPendingProposals(int $limit = 50, int $offset = 0): array
    {
        try {
            // LIMIT et OFFSET doivent être des entiers, pas des placeholders
            // S'assurer que les valeurs sont bien des entiers pour éviter les injections SQL
            $limit = max(1, min(1000, (int)$limit));
            $offset = max(0, (int)$offset);
            
            $stmt = $this->pdo->prepare("SELECT p.*, u.username, u.email, poi.name as poi_name
                                       FROM {$this->tablePrefix}poi_type_proposals p
                                       LEFT JOIN {$this->tablePrefix}users u ON p.user_id = u.id
                                       LEFT JOIN {$this->tablePrefix}poi poi ON p.poi_id = poi.id
                                       WHERE p.status = 'pending'
                                       ORDER BY p.created_at DESC
                                       LIMIT {$limit} OFFSET {$offset}");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Error fetching pending proposals: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Approuve une proposition et crée le type/catégorie
     */
    public function approveProposal(int $proposalId, int $reviewedBy): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Récupérer la proposition
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->tablePrefix}poi_type_proposals WHERE id = ?");
            $stmt->execute([$proposalId]);
            $proposal = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$proposal || $proposal['status'] !== 'pending') {
                $this->pdo->rollBack();
                return false;
            }

            // Créer le type ou la catégorie
            if ($proposal['proposal_type'] === 'type') {
                $typeKey = $proposal['type_key'] ?: $this->slugify($proposal['label']);
                $this->addCustomType($typeKey, $proposal['label'], $proposal['description'], null, $reviewedBy);
            } else {
                // Catégorie
                if (!$proposal['type_key']) {
                    $this->pdo->rollBack();
                    return false;
                }
                $categoryKey = $proposal['category_key'] ?: $this->slugify($proposal['label']);
                $this->addCustomCategory($proposal['type_key'], $categoryKey, $proposal['label'], $proposal['description'], null, $reviewedBy);
            }

            // Marquer la proposition comme approuvée
            $stmt = $this->pdo->prepare("UPDATE {$this->tablePrefix}poi_type_proposals 
                                        SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() 
                                        WHERE id = ?");
            $stmt->execute([$reviewedBy, $proposalId]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->log("Error approving proposal: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Rejette une proposition
     */
    public function rejectProposal(int $proposalId, int $reviewedBy): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE {$this->tablePrefix}poi_type_proposals 
                                       SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() 
                                       WHERE id = ?");
            return $stmt->execute([$reviewedBy, $proposalId]);
        } catch (PDOException $e) {
            $this->log("Error rejecting proposal: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Génère un slug à partir d'un label
     */
    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Construit la structure types/catégories pour les templates
     */
    public function buildTypesCategoriesForTemplate(): array
    {
        $types = $this->getAllTypes();
        $categories = [];

        foreach ($types as $typeKey => $typeData) {
            $categories[$typeKey] = $this->getCategoriesForType($typeKey);
        }

        // Construire le tableau des types (clé => label)
        $typesArray = [];
        foreach ($types as $typeKey => $typeData) {
            $typesArray[$typeKey] = $typeData['label'];
        }
        
        // Construire le tableau des catégories avec labels pour le template
        // Format: typeKey => [catKey => catLabel, ...]
        $categoriesArray = [];
        // Instancier le traducteur
        $translator = new \PoiData\Services\CategoryLabelTranslator();

        foreach ($categories as $typeKey => $cats) {
            $categoriesArray[$typeKey] = [];
            foreach ($cats as $catKey => $catData) {
                // Si catData est un tableau avec 'label' défini explicitement (custom), l'utiliser
                // Sinon, essayer de traduire la clé via le traducteur
                if (is_array($catData) && isset($catData['label']) && $catData['is_custom'] === true) {
                    $categoriesArray[$typeKey][$catKey] = $catData['label'];
                } else {
                    // Traduction automatique basÃ©e sur la clÃ© OSM
                    // On passe le type métier pour le contexte (ex: 'restaurant' vs 'shop')
                    $categoriesArray[$typeKey][$catKey] = $translator->translate($catKey, $typeKey);
                }
            }
        }
        
        return [
            'types' => $typesArray,
            'categories' => $categoriesArray,
        ];
    }
}

