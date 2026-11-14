<?php

namespace ProRdv\Services;

defined('ROOT') or exit('Access denied!');

class RdvService
{
    private static ?self $instance = null;
    private \PDO $pdo;
    private int $holdMinutes = 15;

    public const PRESETS = [
        'beaute' => [
            'key' => 'beaute',
            'label' => 'Beauté, Bien-être & Soins',
            'desc' => 'Coiffure, barbier, esthétique, onglerie, spa, massage, toilettage',
            'icon' => 'fa-scissors',
            'color' => '#ec4899',
            'config' => [
                'type' => 'beaute',
                'choix_collab' => true,
                'mode_validation' => 'auto',
                'pas_min' => 15,
                'delai_min_h' => 2,
                'horizon_jours' => 60,
                'champs_requis' => [
                    'remarque' => false,
                    'adresse' => false,
                    'objet' => false,
                ],
                'notice' => 'Réservations cadencées avec choix du praticien et confirmation automatique.'
            ]
        ],
        'artisan' => [
            'key' => 'artisan',
            'label' => 'Artisans, BTP & Dépannage',
            'desc' => 'Plombier, électricien, serrurier, peintre, menuisier, jardinier, rénovation',
            'icon' => 'fa-hammer',
            'color' => '#d97706',
            'config' => [
                'type' => 'artisan',
                'choix_collab' => false,
                'mode_validation' => 'manuel',
                'pas_min' => 60,
                'delai_min_h' => 12,
                'horizon_jours' => 30,
                'champs_requis' => [
                    'adresse' => true,
                    'details' => true,
                    'remarque' => false,
                ],
                'notice' => 'Plages d’intervention avec adresse obligatoire et validation manuelle du pro.'
            ]
        ],
        'reparation' => [
            'key' => 'reparation',
            'label' => 'Ateliers, Réparations & Véhicules',
            'desc' => 'Garage auto, contrôle technique, réparation vélo, cordonnerie, smartphone',
            'icon' => 'fa-wrench',
            'color' => '#2563eb',
            'config' => [
                'type' => 'reparation',
                'choix_collab' => false,
                'mode_validation' => 'auto',
                'pas_min' => 30,
                'delai_min_h' => 4,
                'horizon_jours' => 45,
                'champs_requis' => [
                    'modele_vehicule' => true,
                    'panne_details' => true,
                    'remarque' => false,
                ],
                'notice' => 'Créneaux de prise en charge atelier avec détails du véhicule/appareil.'
            ]
        ],
        'conseil' => [
            'key' => 'conseil',
            'label' => 'Conseils, Immobilier & Services',
            'desc' => 'Agence immo, photographe, expert, courtier, coach, cours particuliers, tatoueur',
            'icon' => 'fa-briefcase',
            'color' => '#059669',
            'config' => [
                'type' => 'conseil',
                'choix_collab' => true,
                'mode_validation' => 'manuel',
                'pas_min' => 30,
                'delai_min_h' => 4,
                'horizon_jours' => 60,
                'champs_requis' => [
                    'lieu_rdv' => true,
                    'objet' => true,
                    'remarque' => false,
                ],
                'notice' => 'Choix du lieu (bureau / visio / domicile) et motif de l’échange.'
            ]
        ]
    ];

    public function __construct()
    {
        $this->pdo = \Core\Database::getInstance()->getPdo();
        try {
            $this->pdo->query("SELECT 1 FROM `lbp_rdv_commerces` LIMIT 1");
        } catch (\Throwable $e) {
            $this->installTables();
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne tous les profils métiers disponibles
     */
    public function getPresets(): array
    {
        return self::PRESETS;
    }

    /**
     * Retourne la définition d'un profil métier donné
     */
    public function getPreset(string $type): ?array
    {
        return self::PRESETS[$type] ?? null;
    }

    /**
     * Installation des tables de données du plugin ProRdv
     */
    public function installTables(): bool
    {
        try {
            $isSqlite = ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite');
            if ($isSqlite) {
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `lbp_rdv_commerces` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `poi_id` INTEGER NULL,
                    `user_id` INTEGER NULL,
                    `slug` VARCHAR(60) NOT NULL UNIQUE,
                    `nom` VARCHAR(120) NOT NULL,
                    `type` VARCHAR(30) NOT NULL DEFAULT 'coiffeur',
                    `email` VARCHAR(160) NOT NULL,
                    `telephone` VARCHAR(30) NULL,
                    `mot_de_passe` VARCHAR(255) NULL,
                    `config` TEXT NOT NULL,
                    `is_active` INTEGER NOT NULL DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `lbp_rdv_collaborateurs` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `commerce_id` INTEGER NOT NULL,
                    `nom` VARCHAR(80) NOT NULL,
                    `is_active` INTEGER NOT NULL DEFAULT 1
                )");
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `lbp_rdv_prestations` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `commerce_id` INTEGER NOT NULL,
                    `nom` VARCHAR(120) NOT NULL,
                    `duree_min` INTEGER NOT NULL DEFAULT 30,
                    `prix` DECIMAL(8,2) NULL,
                    `description` TEXT NULL,
                    `is_active` INTEGER NOT NULL DEFAULT 1
                )");
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `lbp_rdv_horaires` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `commerce_id` INTEGER NOT NULL,
                    `jour` INTEGER NOT NULL,
                    `debut` VARCHAR(10) NOT NULL,
                    `fin` VARCHAR(10) NOT NULL
                )");
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `lbp_rdv_rendez_vous` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `commerce_id` INTEGER NOT NULL,
                    `collaborateur_id` INTEGER NULL,
                    `prestation_id` INTEGER NOT NULL,
                    `debut` DATETIME NOT NULL,
                    `fin` DATETIME NOT NULL,
                    `nom` VARCHAR(120) NOT NULL,
                    `tel` VARCHAR(30) NOT NULL,
                    `email` VARCHAR(160) NOT NULL,
                    `extra` TEXT NULL,
                    `statut` VARCHAR(20) NOT NULL DEFAULT 'a_verifier',
                    `token` CHAR(32) NOT NULL UNIQUE,
                    `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            } else {
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `lbp_rdv_commerces` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `poi_id` INT UNSIGNED NULL,
                    `user_id` INT UNSIGNED NULL,
                    `slug` VARCHAR(60) NOT NULL UNIQUE,
                    `nom` VARCHAR(120) NOT NULL,
                    `type` VARCHAR(30) NOT NULL DEFAULT 'coiffeur',
                    `email` VARCHAR(160) NOT NULL,
                    `telephone` VARCHAR(30) NULL,
                    `mot_de_passe` VARCHAR(255) NULL,
                    `config` JSON NOT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (`poi_id`),
                    INDEX (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `lbp_rdv_collaborateurs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `commerce_id` INT NOT NULL,
                    `nom` VARCHAR(80) NOT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    INDEX (`commerce_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `lbp_rdv_prestations` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `commerce_id` INT NOT NULL,
                    `nom` VARCHAR(120) NOT NULL,
                    `duree_min` INT NOT NULL DEFAULT 30,
                    `prix` DECIMAL(8,2) NULL,
                    `description` TEXT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    INDEX (`commerce_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `lbp_rdv_horaires` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `commerce_id` INT NOT NULL,
                    `jour` TINYINT NOT NULL,
                    `debut` TIME NOT NULL,
                    `fin` TIME NOT NULL,
                    INDEX (`commerce_id`, `jour`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `lbp_rdv_rendez_vous` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `commerce_id` INT NOT NULL,
                    `collaborateur_id` INT NULL,
                    `prestation_id` INT NOT NULL,
                    `debut` DATETIME NOT NULL,
                    `fin` DATETIME NOT NULL,
                    `nom` VARCHAR(120) NOT NULL,
                    `tel` VARCHAR(30) NOT NULL,
                    `email` VARCHAR(160) NOT NULL,
                    `extra` JSON NULL,
                    `statut` ENUM('a_verifier','attente','confirme','annule') NOT NULL DEFAULT 'a_verifier',
                    `token` CHAR(32) NOT NULL UNIQUE,
                    `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (`commerce_id`, `debut`),
                    INDEX (`statut`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }

            // Insérer commerce de démonstration si aucun commerce n'existe
            $count = (int)$this->pdo->query("SELECT COUNT(*) FROM `lbp_rdv_commerces`")->fetchColumn();
            if ($count === 0) {
                $this->insertDemoData();
            }

            return true;
        } catch (\Throwable $e) {
            error_log('[ProRdv] Install error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Insère les données de démo pour un commerce local
     */
    private function insertDemoData(): void
    {
        $demoConfig = json_encode([
            'choix_collab' => true,
            'validation_auto' => true,
            'delai_min_h' => 2,
            'pas_min' => 15,
            'horizon_jours' => 60,
            'champs' => [
                ['name' => 'remarque', 'label' => 'Remarque (facultatif)', 'type' => 'textarea', 'required' => false]
            ]
        ], JSON_UNESCAPED_UNICODE);

        $stmt = $this->pdo->prepare("INSERT INTO `lbp_rdv_commerces` (`slug`, `nom`, `type`, `email`, `telephone`, `config`, `is_active`)
            VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute(['demo-coiffure', 'Salon Démo', 'coiffeur', 'contact@example.com', '0100000000', $demoConfig]);
        $cid = (int)$this->pdo->lastInsertId();

        // Collaborateurs
        $this->pdo->prepare("INSERT INTO `lbp_rdv_collaborateurs` (`commerce_id`, `nom`) VALUES (?, ?), (?, ?)")
            ->execute([$cid, 'Sophie', $cid, 'Karim']);

        // Prestations
        $this->pdo->prepare("INSERT INTO `lbp_rdv_prestations` (`commerce_id`, `nom`, `duree_min`, `prix`) VALUES 
            (?, 'Coupe femme & brushing', 45, 35.00),
            (?, 'Coupe homme & coiffage', 30, 20.00),
            (?, 'Couleur & soin complet', 90, 60.00)")
            ->execute([$cid, $cid, $cid]);

        // Horaires (Mardi au Samedi)
        $hStmt = $this->pdo->prepare("INSERT INTO `lbp_rdv_horaires` (`commerce_id`, `jour`, `debut`, `fin`) VALUES (?, ?, ?, ?)");
        // Mardi (2) au Vendredi (5)
        for ($j = 2; $j <= 5; $j++) {
            $hStmt->execute([$cid, $j, '09:00', '12:30']);
            $hStmt->execute([$cid, $j, '14:00', '19:00']);
        }
        // Samedi (6)
        $hStmt->execute([$cid, 6, '09:00', '17:00']);
    }

    /**
     * Récupère un commerce par son slug
     */
    public function getCommerceBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `lbp_rdv_commerces` WHERE `slug` = ? AND `is_active` = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Récupère un commerce par son POI ID
     */
    public function getCommerceByPoiId(int $poiId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `lbp_rdv_commerces` WHERE `poi_id` = ? AND `is_active` = 1 LIMIT 1");
        $stmt->execute([$poiId]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Récupère un commerce par son ID
     */
    public function getCommerceById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `lbp_rdv_commerces` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Récupère un commerce par l'ID de l'utilisateur connecté (pro)
     */
    public function getCommerceByUserId(int $userId, bool $activeOnly = false): ?array
    {
        $activeSql = $activeOnly ? " AND `is_active` = 1" : "";
        $stmt = $this->pdo->prepare("SELECT * FROM `lbp_rdv_commerces` WHERE `user_id` = ?{$activeSql} LIMIT 1");
        $stmt->execute([$userId]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($res) return $res;

        // Si l'utilisateur possède un POI revendiqué ayant un commerce RDV
        $activeSqlPoi = $activeOnly ? " AND c.is_active = 1" : "";
        try {
            $stmt = $this->pdo->prepare("SELECT c.* FROM `lbp_rdv_commerces` c 
                JOIN `poi_public` p ON p.id = c.poi_id 
                WHERE p.claimed_by = ?{$activeSqlPoi} LIMIT 1");
            $stmt->execute([$userId]);
            $res = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Récupère les POIs revendiqués par un utilisateur avec le statut de leur module RDV
     */
    public function getUserClaimedPois(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT p.id, p.name, p.slug, c.id AS commerce_id, c.is_active AS rdv_active
                FROM `poi_public` p
                LEFT JOIN `lbp_rdv_commerces` c ON c.poi_id = p.id
                WHERE p.claimed_by = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Liste des prestations actives d'un commerce
     */
    public function getPrestations(int $commerceId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `lbp_rdv_prestations` WHERE `commerce_id` = ? AND `is_active` = 1 ORDER BY `nom` ASC");
        $stmt->execute([$commerceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Liste des collaborateurs actifs d'un commerce
     */
    public function getCollaborateurs(int $commerceId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `lbp_rdv_collaborateurs` WHERE `commerce_id` = ? AND `is_active` = 1 ORDER BY `nom` ASC");
        $stmt->execute([$commerceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Liste de TOUTES les prestations d'un commerce (pour la gestion pro)
     */
    public function getAllPrestations(int $commerceId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `lbp_rdv_prestations` WHERE `commerce_id` = ? ORDER BY `id` ASC");
        $stmt->execute([$commerceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function addPrestation(int $commerceId, string $nom, int $dureeMin, ?float $prix, ?string $desc = null): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO `lbp_rdv_prestations` (`commerce_id`, `nom`, `duree_min`, `prix`, `description`, `is_active`) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$commerceId, $nom, $dureeMin, $prix, $desc]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updatePrestation(int $commerceId, int $prestaId, string $nom, int $dureeMin, ?float $prix, ?string $desc = null): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `lbp_rdv_prestations` SET `nom` = ?, `duree_min` = ?, `prix` = ?, `description` = ? WHERE `id` = ? AND `commerce_id` = ?");
        return $stmt->execute([$nom, $dureeMin, $prix, $desc, $prestaId, $commerceId]);
    }

    public function togglePrestation(int $commerceId, int $prestaId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `lbp_rdv_prestations` SET `is_active` = 1 - `is_active` WHERE `id` = ? AND `commerce_id` = ?");
        return $stmt->execute([$prestaId, $commerceId]);
    }

    public function deletePrestation(int $commerceId, int $prestaId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `lbp_rdv_prestations` WHERE `id` = ? AND `commerce_id` = ?");
        return $stmt->execute([$prestaId, $commerceId]);
    }

    /**
     * Liste de TOUS les collaborateurs d'un commerce (pour la gestion pro)
     */
    public function getAllCollaborateurs(int $commerceId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `lbp_rdv_collaborateurs` WHERE `commerce_id` = ? ORDER BY `id` ASC");
        $stmt->execute([$commerceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function addCollaborateur(int $commerceId, string $nom): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO `lbp_rdv_collaborateurs` (`commerce_id`, `nom`, `is_active`) VALUES (?, ?, 1)");
        $stmt->execute([$commerceId, $nom]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateCollaborateur(int $commerceId, int $collabId, string $nom): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `lbp_rdv_collaborateurs` SET `nom` = ? WHERE `id` = ? AND `commerce_id` = ?");
        return $stmt->execute([$nom, $collabId, $commerceId]);
    }

    public function toggleCollaborateur(int $commerceId, int $collabId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `lbp_rdv_collaborateurs` SET `is_active` = 1 - `is_active` WHERE `id` = ? AND `commerce_id` = ?");
        return $stmt->execute([$collabId, $commerceId]);
    }

    public function deleteCollaborateur(int $commerceId, int $collabId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `lbp_rdv_collaborateurs` WHERE `id` = ? AND `commerce_id` = ?");
        return $stmt->execute([$collabId, $commerceId]);
    }

    /**
     * Récupère la grille horaire de la semaine pour un commerce
     * Jour 1 = Lundi, ... 7 = Dimanche
     */
    public function getHoraires(int $commerceId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `lbp_rdv_horaires` WHERE `commerce_id` = ? ORDER BY `jour` ASC, `debut` ASC");
        $stmt->execute([$commerceId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];
        for ($j = 1; $j <= 7; $j++) {
            $result[$j] = [];
        }
        foreach ($rows as $r) {
            $result[(int)$r['jour']][] = [
                'debut' => substr($r['debut'], 0, 5),
                'fin'   => substr($r['fin'], 0, 5),
            ];
        }
        return $result;
    }

    /**
     * Sauvegarde la grille horaire hebdomadaire
     */
    public function saveHoraires(int $commerceId, array $horairesByJour): bool
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM `lbp_rdv_horaires` WHERE `commerce_id` = ?")->execute([$commerceId]);
            $ins = $this->pdo->prepare("INSERT INTO `lbp_rdv_horaires` (`commerce_id`, `jour`, `debut`, `fin`) VALUES (?, ?, ?, ?)");
            foreach ($horairesByJour as $jour => $plages) {
                $jourInt = (int)$jour;
                if ($jourInt < 1 || $jourInt > 7) continue;
                foreach ($plages as $p) {
                    $debut = trim($p['debut'] ?? '');
                    $fin = trim($p['fin'] ?? '');
                    if (!empty($debut) && !empty($fin) && $debut < $fin) {
                        $ins->execute([$commerceId, $jourInt, $debut, $fin]);
                    }
                }
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Met à jour la configuration JSON et les métadonnées du commerce
     */
    public function updateCommerceConfig(int $commerceId, array $config, ?string $type = null, ?string $nom = null): bool
    {
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);
        if ($type !== null && $nom !== null) {
            $stmt = $this->pdo->prepare("UPDATE `lbp_rdv_commerces` SET `config` = ?, `type` = ?, `nom` = ? WHERE `id` = ?");
            return $stmt->execute([$configJson, $type, $nom, $commerceId]);
        } elseif ($type !== null) {
            $stmt = $this->pdo->prepare("UPDATE `lbp_rdv_commerces` SET `config` = ?, `type` = ? WHERE `id` = ?");
            return $stmt->execute([$configJson, $type, $commerceId]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE `lbp_rdv_commerces` SET `config` = ? WHERE `id` = ?");
            return $stmt->execute([$configJson, $commerceId]);
        }
    }

    /**
     * Bascule l'état Actif/Inactif du module RDV pour un commerce
     */
    public function toggleCommerceActive(int $commerceId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `lbp_rdv_commerces` SET `is_active` = 1 - `is_active` WHERE `id` = ?");
        return $stmt->execute([$commerceId]);
    }

    /**
     * Applique un preset métier à un commerce
     */
    public function applyPreset(int $commerceId, string $presetKey): bool
    {
        if (!isset(self::PRESETS[$presetKey])) {
            return false;
        }
        $preset = self::PRESETS[$presetKey];
        $current = $this->getCommerceById($commerceId);
        if (!$current) return false;

        $cfg = json_decode($current['config'] ?? '{}', true) ?: [];
        $mergedConfig = array_merge($cfg, $preset['config']);
        return $this->updateCommerceConfig($commerceId, $mergedConfig, $presetKey);
    }

    /**
     * Active le module RDV pour un POI revendiqué par un utilisateur
     */
    public function createOrGetForPoi(int $poiId, int $userId): ?array
    {
        // 1. Vérifier si un commerce existe déjà pour ce POI
        $existing = $this->getCommerceByPoiId($poiId);
        if ($existing) {
            if (empty($existing['user_id'])) {
                $this->pdo->prepare("UPDATE `lbp_rdv_commerces` SET `user_id` = ?, `is_active` = 1 WHERE `id` = ?")->execute([$userId, $existing['id']]);
                $existing['user_id'] = $userId;
                $existing['is_active'] = 1;
            }
            return $existing;
        }

        // 2. Récupérer les infos du POI depuis la table poi_public (ou poi)
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, slug, email, phone FROM poi_public WHERE id = ? LIMIT 1");
            $stmt->execute([$poiId]);
            $poi = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $poi = null;
        }
        if (!$poi) {
            try {
                $stmt = $this->pdo->prepare("SELECT id, name, slug, email, phone FROM poi WHERE id = ? LIMIT 1");
                $stmt->execute([$poiId]);
                $poi = $stmt->fetch(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $poi = null;
            }
        }
        if (!$poi) {
            return null;
        }

        $nom = $poi['name'] ?: 'Commerce #' . $poiId;
        $slug = !empty($poi['slug']) ? $poi['slug'] : 'commerce-' . $poiId;
        // S'assurer que le slug est unique dans lbp_rdv_commerces
        $checkSlug = $this->pdo->prepare("SELECT id FROM `lbp_rdv_commerces` WHERE `slug` = ?");
        $checkSlug->execute([$slug]);
        if ($checkSlug->fetchColumn()) {
            $slug .= '-' . $poiId;
        }

        $email = !empty($poi['email']) ? $poi['email'] : 'contact@299ko.flexcb.fr';
        $tel = $poi['phone'] ?? null;
        $defaultPreset = self::PRESETS['beaute']['config'];
        $configJson = json_encode($defaultPreset, JSON_UNESCAPED_UNICODE);

        $ins = $this->pdo->prepare("INSERT INTO `lbp_rdv_commerces` (`poi_id`, `user_id`, `slug`, `nom`, `type`, `email`, `telephone`, `config`, `is_active`)
            VALUES (?, ?, ?, ?, 'beaute', ?, ?, ?, 1)");
        $ins->execute([$poiId, $userId, $slug, $nom, $email, $tel, $configJson]);
        $cid = (int)$this->pdo->lastInsertId();

        // Insérer une prestation exemple
        $this->addPrestation($cid, 'Prestation découverte', 30, 25.00, 'Prestation de bienvenue');

        // Insérer des horaires par défaut (Mardi au Samedi)
        $hStmt = $this->pdo->prepare("INSERT INTO `lbp_rdv_horaires` (`commerce_id`, `jour`, `debut`, `fin`) VALUES (?, ?, ?, ?)");
        for ($j = 2; $j <= 5; $j++) {
            $hStmt->execute([$cid, $j, '09:00', '12:30']);
            $hStmt->execute([$cid, $j, '14:00', '19:00']);
        }
        $hStmt->execute([$cid, 6, '09:00', '17:00']);

        return $this->getCommerceById($cid);
    }

    /**
     * Crée un établissement RDV autonome (sans dépendance à PoiData)
     */
    public function createStandaloneCommerce(int $userId, string $nom, string $presetKey = 'beaute', string $email = '', ?string $tel = null): ?array
    {
        $nom = trim($nom) ?: 'Mon Espace Rendez-vous';
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $nom), '-')) ?: 'mon-agenda';
        $checkSlug = $this->pdo->prepare("SELECT id FROM `lbp_rdv_commerces` WHERE `slug` = ?");
        $checkSlug->execute([$slug]);
        if ($checkSlug->fetchColumn()) {
            $slug .= '-' . ($userId ?: rand(100, 999));
        }
        $presetKey = isset(self::PRESETS[$presetKey]) ? $presetKey : 'beaute';
        $configJson = json_encode(self::PRESETS[$presetKey]['config'], JSON_UNESCAPED_UNICODE);
        $email = $email ?: (\Core\Core::getInstance()->getConfigVal('adminEmail') ?: 'contact@example.com');

        $ins = $this->pdo->prepare("INSERT INTO `lbp_rdv_commerces` (`poi_id`, `user_id`, `slug`, `nom`, `type`, `email`, `telephone`, `config`, `is_active`)
            VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, 1)");
        $ins->execute([$userId ?: null, $slug, $nom, $presetKey, $email, $tel, $configJson]);
        $cid = (int)$this->pdo->lastInsertId();

        $this->addPrestation($cid, 'Consultation / Rendez-vous standard', 30, 25.00, 'Prestation par défaut');

        $hStmt = $this->pdo->prepare("INSERT INTO `lbp_rdv_horaires` (`commerce_id`, `jour`, `debut`, `fin`) VALUES (?, ?, ?, ?)");
        for ($j = 1; $j <= 5; $j++) {
            $hStmt->execute([$cid, $j, '09:00', '12:00']);
            $hStmt->execute([$cid, $j, '14:00', '18:00']);
        }
        return $this->getCommerceById($cid);
    }
    public function getAvailableSlots(array $commerce, array $presta, ?int $collaborateurId, string $date): array
    {
        $cfg = json_decode($commerce['config'] ?? '{}', true) ?: [];
        $ts = strtotime($date);
        $today = date('Y-m-d');
        
        if (!$ts || $date < $today) {
            return [];
        }
        $horizonDays = (int)($cfg['horizon_jours'] ?? 60);
        if ($ts > strtotime("+{$horizonDays} days")) {
            return [];
        }

        $jourSemaine = (int)date('N', $ts); // 1 = Lundi, 7 = Dimanche

        // Plages horaires du jour
        $stmt = $this->pdo->prepare("SELECT `debut`, `fin` FROM `lbp_rdv_horaires` WHERE `commerce_id` = ? AND `jour` = ? ORDER BY `debut` ASC");
        $stmt->execute([$commerce['id'], $jourSemaine]);
        $plages = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($plages)) {
            return [];
        }

        // RDV déjà pris ou en attente de validation par email (< 15 min)
        $holdCutoff = date('Y-m-d H:i:s', time() - ($this->holdMinutes * 60));
        $sqlPris = "SELECT `debut`, `fin` FROM `lbp_rdv_rendez_vous`
                    WHERE `commerce_id` = ? AND `statut` <> 'annule'
                    AND NOT (`statut` = 'a_verifier' AND `cree_le` < ?)
                    AND DATE(`debut`) = ?";
        $paramsPris = [$commerce['id'], $holdCutoff, $date];

        if ($collaborateurId !== null) {
            $sqlPris .= " AND (`collaborateur_id` = ? OR `collaborateur_id` IS NULL)";
            $paramsPris[] = $collaborateurId;
        }

        $stmtPris = $this->pdo->prepare($sqlPris);
        $stmtPris->execute($paramsPris);
        $pris = $stmtPris->fetchAll(\PDO::FETCH_ASSOC);

        $delaiMinH = max(0, (int)($cfg['delai_min_h'] ?? 2));
        $minTimestamp = time() + ($delaiMinH * 3600);
        $pasMin = (int)($cfg['pas_min'] ?? 15);
        if ($pasMin < 5) {
            $pasMin = 15; // Sécurité absolue anti-boucle infinie
        }
        $pasSecondes = $pasMin * 60;
        $dureeSecondes = max(300, ((int)($presta['duree_min'] ?? 30)) * 60);

        $creneauxLibres = [];

        foreach ($plages as $pl) {
            $debutPlageTs = strtotime("{$date} {$pl['debut']}");
            $finPlageTs = strtotime("{$date} {$pl['fin']}");
            if ($finPlageTs <= $debutPlageTs || $pasSecondes <= 0) {
                continue;
            }
            for ($t = $debutPlageTs; $t + $dureeSecondes <= $finPlageTs; $t += $pasSecondes) {
                if ($t < $minTimestamp) {
                    continue;
                }

                $slotStart = $t;
                $slotEnd = $t + $dureeSecondes;

                // Vérifier chevauchement avec un RDV déjà pris
                $conflit = false;
                foreach ($pris as $r) {
                    $rStart = strtotime($r['debut']);
                    $rEnd = strtotime($r['fin']);
                    if ($slotStart < $rEnd && $slotEnd > $rStart) {
                        $conflit = true;
                        break;
                    }
                }

                if (!$conflit) {
                    $creneauxLibres[] = date('H:i', $t);
                }
            }
        }

        return $creneauxLibres;
    }

    /**
     * Création d'une demande de réservation avec verrouillage temporaire (double opt-in)
     */
    public function createReservation(array $commerce, array $presta, ?int $collabId, string $date, string $heure, array $clientData): array
    {
        $nom = trim($clientData['nom'] ?? '');
        $tel = trim($clientData['tel'] ?? '');
        $email = trim($clientData['email'] ?? '');
        $extra = $clientData['extra'] ?? [];

        if ($nom === '' || $tel === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Nom, téléphone et email valides requis.'];
        }

        // Vérifier limitation anti-spam (max 3 demandes non confirmées par heure)
        $oneHourAgo = date('Y-m-d H:i:s', time() - 3600);
        $stmtSpam = $this->pdo->prepare("SELECT COUNT(*) FROM `lbp_rdv_rendez_vous` 
            WHERE (`email` = ? OR `tel` = ?) AND `statut` = 'a_verifier' AND `cree_le` > ?");
        $stmtSpam->execute([$email, $tel, $oneHourAgo]);
        if ((int)$stmtSpam->fetchColumn() >= 3) {
            return ['success' => false, 'error' => 'Trop de demandes en attente. Confirmez d\'abord les demandes envoyées par email.'];
        }

        // Vérifier disponibilité du créneau
        $slots = $this->getAvailableSlots($commerce, $presta, $collabId, $date);
        if (!in_array($heure, $slots, true)) {
            return ['success' => false, 'error' => 'Ce créneau vient d\'être réservé, veuillez en choisir un autre.'];
        }

        $debutTs = strtotime("{$date} {$heure}");
        $finTs = $debutTs + (((int)$presta['duree_min']) * 60);
        $token = bin2hex(random_bytes(16));
        $statut = 'a_verifier';

        $stmt = $this->pdo->prepare("INSERT INTO `lbp_rdv_rendez_vous` 
            (`commerce_id`, `collaborateur_id`, `prestation_id`, `debut`, `fin`, `nom`, `tel`, `email`, `extra`, `statut`, `token`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $commerce['id'],
            $collabId,
            $presta['id'],
            date('Y-m-d H:i:s', $debutTs),
            date('Y-m-d H:i:s', $finTs),
            $nom,
            $tel,
            $email,
            json_encode($extra, JSON_UNESCAPED_UNICODE),
            $statut,
            $token
        ]);

        $rdvId = (int)$this->pdo->lastInsertId();

        // Envoi de l'email de confirmation (double opt-in)
        $siteUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl') ?: 'https://299ko.flexcb.fr', '/');
        $confirmUrl = "{$siteUrl}/rdv/confirmer/{$token}";
        $quand = date('d/m/Y à H:i', $debutTs);

        $sujet = "Confirmez votre rendez-vous chez {$commerce['nom']}";
        $message = "Bonjour {$nom},\n\n"
                 . "Pour valider définitivement votre rendez-vous ({$presta['nom']} chez {$commerce['nom']}, le {$quand}), "
                 . "cliquez sur le lien suivant dans les {$this->holdMinutes} minutes :\n"
                 . "{$confirmUrl}\n\n"
                 . "Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.\n\n"
                 . "299koEVO — Système de prise de rendez-vous";
        $headers = "From: rdv@299ko.flexcb.fr\r\nContent-Type: text/plain; charset=utf-8";

        @mail($email, $sujet, $message, $headers);

        // Interaction analytique souveraine avec LightStats
        if (class_exists('\\LightStats\\Lib\\LightStatsAnalyticsService')) {
            try {
                $analytics = \LightStats\Lib\LightStatsAnalyticsService::getInstance();
                $sid = $_COOKIE['vb_sid'] ?? session_id() ?: 'sess_anon';
                $analytics->recordEvent(
                    $sid,
                    null,
                    'prordv',
                    'rdv_booked',
                    '/rdv/' . $commerce['slug'],
                    [
                        'commerce' => $commerce['slug'],
                        'commerce_id' => $commerce['id'],
                        'presta' => $presta['nom'],
                        'date' => $date
                    ]
                );
            } catch (\Throwable $e) {}
        }

        return [
            'success' => true,
            'rdv_id' => $rdvId,
            'token' => $token,
            'quand' => $quand,
            'hold_minutes' => $this->holdMinutes
        ];
    }

    /**
     * Confirmation d'un RDV via son token
     */
    public function confirmReservation(string $token): array
    {
        $stmt = $this->pdo->prepare("SELECT r.*, c.nom AS commerce_nom, c.email AS pro_email, c.config, p.nom AS presta_nom
            FROM `lbp_rdv_rendez_vous` r
            JOIN `lbp_rdv_commerces` c ON c.id = r.commerce_id
            JOIN `lbp_rdv_prestations` p ON p.id = r.prestation_id
            WHERE r.token = ? LIMIT 1");
        $stmt->execute([$token]);
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$r) {
            return ['success' => false, 'error' => 'Lien de confirmation invalide.'];
        }

        if ($r['statut'] === 'annule') {
            return ['success' => false, 'error' => 'Cette demande a été annulée ou a expiré. Merci de refaire une réservation.'];
        }

        if ($r['statut'] !== 'a_verifier') {
            return ['success' => true, 'already' => true, 'message' => 'Votre rendez-vous est déjà validé.'];
        }

        // Vérifier si le lien a expiré et si un conflit est survenu entre temps
        $expire = strtotime($r['cree_le']) < (time() - ($this->holdMinutes * 60));
        if ($expire) {
            $stmtConflit = $this->pdo->prepare("SELECT id FROM `lbp_rdv_rendez_vous` 
                WHERE commerce_id = ? AND id <> ? AND (collaborateur_id = ? OR (collaborateur_id IS NULL AND ? IS NULL))
                AND statut IN ('attente','confirme') AND debut < ? AND fin > ?");
            $stmtConflit->execute([$r['commerce_id'], $r['id'], $r['collaborateur_id'], $r['collaborateur_id'], $r['fin'], $r['debut']]);
            if ($stmtConflit->fetchColumn()) {
                return ['success' => false, 'error' => 'Le délai de 15 minutes a expiré et ce créneau a été réattribué. Merci de refaire votre demande.'];
            }
        }

        $cfg = json_decode($r['config'] ?? '{}', true) ?: [];
        $isAuto = (!empty($cfg['validation_auto']) || ($cfg['mode_validation'] ?? 'auto') === 'auto');
        $statut = $isAuto ? 'confirme' : 'attente';

        $this->pdo->prepare("UPDATE `lbp_rdv_rendez_vous` SET `statut` = ? WHERE `id` = ?")
            ->execute([$statut, $r['id']]);

        $quand = date('d/m/Y à H:i', strtotime($r['debut']));
        $siteUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl') ?: 'https://299ko.flexcb.fr', '/');
        $annulUrl = "{$siteUrl}/rdv/annuler/{$r['token']}";

        // Alerte au professionnel
        $msgPro = "Nouveau rendez-vous reçu !\n\nClient : {$r['nom']}\nTéléphone : {$r['tel']}\nEmail : {$r['email']}\n"
                . "Prestation : {$r['presta_nom']}\nDate : {$quand}\nStatut : " . ($statut === 'confirme' ? 'Confirmé automatiquement' : 'À valider dans votre agenda pro')
                . "\n\nAccédez à votre agenda : {$siteUrl}/pro/rdv";
        @mail($r['pro_email'], "Nouveau RDV : {$r['nom']} le {$quand}", $msgPro, "From: rdv@299ko.flexcb.fr\r\nContent-Type: text/plain; charset=utf-8");

        // Confirmation au client
        $msgClient = "Bonjour {$r['nom']},\n\nVotre rendez-vous ({$r['presta_nom']} chez {$r['commerce_nom']}, le {$quand}) est "
                   . ($statut === 'confirme' ? 'confirmé !' : 'bien enregistré et en attente de validation par le professionnel.')
                   . "\n\nPour annuler ce rendez-vous si nécessaire : {$annulUrl}\n\nMerci de votre confiance sur 299koEVO.fr !";
        @mail($r['email'], "Rendez-vous {$r['commerce_nom']} le {$quand}", $msgClient, "From: rdv@299ko.flexcb.fr\r\nContent-Type: text/plain; charset=utf-8");

        // Interaction analytique souveraine avec LightStats
        if (class_exists('\\LightStats\\Lib\\LightStatsAnalyticsService')) {
            try {
                $analytics = \LightStats\Lib\LightStatsAnalyticsService::getInstance();
                $sid = $_COOKIE['vb_sid'] ?? session_id() ?: 'sess_anon';
                $analytics->recordEvent(
                    $sid,
                    null,
                    'prordv',
                    'rdv_confirmed',
                    '/rdv/confirmer/' . $token,
                    [
                        'commerce_id' => $r['commerce_id'],
                        'commerce_nom' => $r['commerce_nom'],
                        'presta' => $r['presta_nom'],
                        'quand' => $quand
                    ]
                );
            } catch (\Throwable $e) {}
        }

        return [
            'success' => true,
            'statut' => $statut,
            'commerce' => $r['commerce_nom'],
            'presta' => $r['presta_nom'],
            'quand' => $quand
        ];
    }

    /**
     * Annulation d'un RDV via son token
     */
    public function cancelReservation(string $token): array
    {
        $stmt = $this->pdo->prepare("SELECT r.*, c.nom AS commerce_nom, c.email AS pro_email, p.nom AS presta_nom
            FROM `lbp_rdv_rendez_vous` r
            JOIN `lbp_rdv_commerces` c ON c.id = r.commerce_id
            JOIN `lbp_rdv_prestations` p ON p.id = r.prestation_id
            WHERE r.token = ? LIMIT 1");
        $stmt->execute([$token]);
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$r) {
            return ['success' => false, 'error' => 'Lien d\'annulation invalide.'];
        }

        if ($r['statut'] === 'annule') {
            return ['success' => true, 'message' => 'Ce rendez-vous est déjà annulé.'];
        }

        $this->pdo->prepare("UPDATE `lbp_rdv_rendez_vous` SET `statut` = 'annule' WHERE `id` = ?")->execute([$r['id']]);

        $quand = date('d/m/Y à H:i', strtotime($r['debut']));
        @mail($r['pro_email'], "RDV Annulé : {$r['nom']} ({$quand})", "Le client {$r['nom']} a annulé son RDV ({$r['presta_nom']} du {$quand}). Ce créneau est de nouveau libre.", "From: rdv@299ko.flexcb.fr\r\nContent-Type: text/plain; charset=utf-8");

        return ['success' => true, 'message' => 'Votre rendez-vous a bien été annulé. Le créneau a été libéré.'];
    }

    /**
     * Agenda pour l'espace Pro (rendez-vous d'un jour donné)
     */
    public function getProAgenda(int $commerceId, string $date): array
    {
        $stmt = $this->pdo->prepare("SELECT r.*, p.nom AS presta_nom, co.nom AS collab_nom 
            FROM `lbp_rdv_rendez_vous` r
            JOIN `lbp_rdv_prestations` p ON p.id = r.prestation_id
            LEFT JOIN `lbp_rdv_collaborateurs` co ON co.id = r.collaborateur_id
            WHERE r.commerce_id = ? AND DATE(r.debut) = ? AND r.statut NOT IN ('annule', 'a_verifier')
            ORDER BY r.debut ASC");
        $stmt->execute([$commerceId, $date]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Met à jour le statut d'un rendez-vous par le Pro
     */
    public function updateStatus(int $commerceId, int $rdvId, string $statut): bool
    {
        if (!in_array($statut, ['confirme', 'annule'], true)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE `lbp_rdv_rendez_vous` SET `statut` = ? WHERE `id` = ? AND `commerce_id` = ?");
        return $stmt->execute([$statut, $rdvId, $commerceId]);
    }

    /**
     * Formate un numéro de téléphone français en format international pour les liens sms:
     */
    public static function formatIntlPhone(string $tel): string
    {
        $t = preg_replace('/\D/', '', $tel);
        if ($t && $t[0] === '0') {
            return '+33' . substr($t, 1);
        }
        return '+' . $t;
    }

    /**
     * Génère l'URL magique pour lien SMS de rappel en 1 clic
     */
    public static function generateSmsReminderUrl(array $rdv, string $commerceNom): string
    {
        $tel = self::formatIntlPhone($rdv['tel'] ?? '');
        $date = date('d/m à H:i', strtotime($rdv['debut']));
        $body = "Bonjour {$rdv['nom']}, rappel de votre rendez-vous ({$rdv['presta_nom']}) chez {$commerceNom} le {$date}. À bientôt !";
        return "sms:{$tel}?body=" . rawurlencode($body);
    }

    /**
     * Liste globale des commerces pour l'administration
     */
    public function getAllCommerces(): array
    {
        $stmt = $this->pdo->query("SELECT c.*, 
            (SELECT COUNT(*) FROM `lbp_rdv_rendez_vous` WHERE `commerce_id` = c.id AND `statut` = 'confirme') AS confirmed_count,
            (SELECT COUNT(*) FROM `lbp_rdv_rendez_vous` WHERE `commerce_id` = c.id) AS total_count
            FROM `lbp_rdv_commerces` c ORDER BY c.nom ASC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
