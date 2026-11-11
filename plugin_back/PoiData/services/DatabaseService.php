<?php

namespace PoiData\Services;

use Core\Logger;
use PDO;
use PoiData\Services\ConfigServiceInterface;

defined('ROOT') or exit('Access denied!');

/**
 * Service de gestion de la connexion à la base de données
 * Suit le principe Single Responsibility (SOLID)
 * Utilise le pattern Singleton pour éviter les connexions multiples
 * Implémente DatabaseServiceInterface pour respecter le principe Interface Segregation
 */
class DatabaseService implements DatabaseServiceInterface
{
    private ?PDO $pdo = null;
    private ConfigServiceInterface $config;
    private Logger $logger;

    public function __construct(ConfigServiceInterface $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Récupère ou crée la connexion PDO
     * 
     * @return PDO
     * @throws \PDOException Si la connexion échoue
     */
    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            try {
                // Utiliser le singleton Core\Database
                if (!class_exists('\Core\Database')) {
                    require_once COMMON . 'Core/Database.php';
                }
                $this->pdo = \Core\Database::getInstance()->getPdo();

                $this->logger->log(Logger::LEVEL_DEBUG, "DatabaseService::getConnection - Connexion PDO établie via Core\Database");
            } catch (\PDOException $e) {
                $this->logger->log(Logger::LEVEL_ERROR, "DatabaseService::getConnection - Erreur de connexion: " . $e->getMessage());
                throw $e;
            }
        }

        return $this->pdo;
    }

    /**
     * Ferme la connexion (utile pour les tests)
     */
    public function closeConnection(): void
    {
        $this->pdo = null;
    }
}

