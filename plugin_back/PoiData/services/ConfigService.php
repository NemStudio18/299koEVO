<?php

namespace PoiData\Services;

use Core\Logger;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

/**
 * Service de gestion de la configuration
 * Suit le principe Single Responsibility (SOLID)
 * Implémente ConfigServiceInterface pour respecter le principe Interface Segregation
 */
class ConfigService implements ConfigServiceInterface
{
    private array $config;
    private Logger $logger;
    private string $configFile;

    public function __construct(Logger $logger, ?string $configFile = null)
    {
        $this->logger = $logger;
        if ($configFile === null) {
            $basePath = defined('DATA_PLUGIN') ? DATA_PLUGIN : (ROOT . 'data' . DS . 'plugin' . DS);
            $configFile = $basePath . 'SearchEngine' . DS . 'search_conf.json';
            
            // Normalize path separators
            $configFile = str_replace(['/', '\\'], DS, $configFile);
            
            $this->logger->log(Logger::LEVEL_DEBUG, "ConfigService::__construct - Chemin config final: " . $configFile);
            $this->logger->log(Logger::LEVEL_DEBUG, "ConfigService::__construct - ROOT: " . (defined('ROOT') ? ROOT : 'NON DÉFINI'));
            $this->logger->log(Logger::LEVEL_DEBUG, "ConfigService::__construct - DATA_PLUGIN: " . (defined('DATA_PLUGIN') ? DATA_PLUGIN : 'NON DÉFINI'));
        }
        $this->configFile = $configFile;
        $this->config = $this->loadConfig();
    }

    /**
     * Charge la configuration depuis le fichier JSON
     * 
     * @return array
     */
    private function loadConfig(): array
    {
        if (!is_file($this->configFile)) {
            $this->logger->log(Logger::LEVEL_WARNING, "ConfigService::loadConfig - Fichier de configuration non trouvé: {$this->configFile}");
            return $this->getDefaultConfig();
        }

        $json = Util::readJsonFile($this->configFile);

        if (!is_array($json)) {
            $this->logger->log(Logger::LEVEL_ERROR, "ConfigService::loadConfig - Erreur de lecture du fichier de configuration: {$this->configFile}");
            return $this->getDefaultConfig();
        }

        return $json;
    }

    /**
     * Retourne la configuration par défaut
     * 
     * @return array
     */
    private function getDefaultConfig(): array
    {
        return [
            'hero_title' => 'Trouvez un lieu',
            'hero_subtitle' => 'Restaurants, cafés, hôtels, services...',
            'default_radius' => 5,
            'sql_host' => 'localhost',
            'sql_db' => 'vivrebourges',
            'sql_user' => '',
            'sql_pass' => '',
        ];
    }

    /**
     * Récupère une valeur de configuration
     * 
     * @param string $key Clé de configuration
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Récupère toute la configuration
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Vérifie si une clé existe dans la configuration
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }
}

