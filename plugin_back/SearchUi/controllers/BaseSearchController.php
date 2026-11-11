<?php

namespace SearchUI\Controllers;

use Core\Controllers\PublicController;
use Core\Core;
use Core\Logger;
use Core\Response;
use PDO;
use PoiData\Services\ConfigService;
use PoiData\Services\DatabaseService;
use PoiData\Services\Poi\PoiRepository;
use PoiData\Services\PoiDataFormatter;
use SearchEngine\Services\CacheJsonService;
use SearchEngine\Services\Search\SearchService;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

/**
 * Classe de base pour les contrôleurs de recherche
 * Suit les principes SOLID :
 * - Single Responsibility : Délègue la configuration et la DB aux services
 * - Dependency Inversion : Injection des dépendances via les services
 * - Open/Closed : Extensible sans modification
 */
abstract class BaseSearchController extends PublicController
{
    /**
     * Service de configuration
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * Service de base de données
     * @var DatabaseService
     */
    protected DatabaseService $databaseService;

    /**
     * Constructeur avec injection de dépendances
     */
    public function __construct()
    {
        parent::__construct();

        // Logger pour déboguer
        $this->logger->log(\Core\Logger::LEVEL_DEBUG, "BaseSearchController::__construct() - Début");

        // Vérifier que les classes existent
        if (!class_exists('PoiData\Services\ConfigService')) {
            $this->logger->log(\Core\Logger::LEVEL_ERROR, "BaseSearchController::__construct() - ConfigService n'existe pas");
            // Essayer de charger les services (si autoloader pas encore trigger)
            // Note: avec le nouveau système de plugins, l'autoloader devrait être là

            // Vérifier à nouveau
            if (!class_exists('PoiData\Services\ConfigService')) {
                $this->logger->log(\Core\Logger::LEVEL_ERROR, "BaseSearchController::__construct() - ConfigService toujours introuvable");
                throw new \RuntimeException('ConfigService class not found (PoiData plugin missing?).');
            }
        }

        if (!class_exists('PoiData\Services\DatabaseService')) {
            $this->logger->log(\Core\Logger::LEVEL_ERROR, "BaseSearchController::__construct() - DatabaseService n'existe pas");
            throw new \RuntimeException('DatabaseService class not found (PoiData plugin missing?).');
        }

        $this->logger->log(\Core\Logger::LEVEL_DEBUG, "BaseSearchController::__construct() - Instanciation des services");
        $this->configService = new ConfigService($this->logger);
        $this->databaseService = new DatabaseService($this->configService, $this->logger);
        $this->logger->log(\Core\Logger::LEVEL_DEBUG, "BaseSearchController::__construct() - Services instanciés avec succès");
    }

    /**
     * Récupère la configuration
     * 
     * @return array
     */
    protected function getConfig(): array
    {
        return $this->configService->all();
    }

    /**
     * Récupère la connexion PDO
     * 
     * @return PDO
     * @throws \PDOException Si la connexion échoue
     */
    protected function getPdo(): PDO
    {
        return $this->databaseService->getConnection();
    }

    /**
     * Charge les villes depuis agglo.json
     * Utilise le logger du CMS pour les erreurs
     * 
     * @return array Liste des villes
     */
    protected function loadAggloCities(): array
    {
        $aggloFiles = [
            ROOT . 'common' . DS . 'data' . DS . 'agglo.json',
            DATA_PLUGIN . 'search' . DS . 'agglo.json',
            PLUGINS . 'search-engine' . DS . 'param' . DS . 'agglo.json',
        ];

        foreach ($aggloFiles as $aggloFile) {
            if (is_file($aggloFile)) {
                $aggloData = Util::readJsonFile($aggloFile);

                if (is_array($aggloData) && isset($aggloData['communes'])) {
                    $this->logger->log(Logger::LEVEL_DEBUG, "BaseSearchController::loadAggloCities - " . count($aggloData['communes']) . " villes chargées depuis: {$aggloFile}");
                    return $aggloData['communes'];
                }
            }
        }

        $this->logger->log(Logger::LEVEL_WARNING, "BaseSearchController::loadAggloCities - Aucun fichier agglo.json trouvé");
        return [];
    }
}
