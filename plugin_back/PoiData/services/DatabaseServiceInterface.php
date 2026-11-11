<?php

namespace PoiData\Services;

use PDO;

defined('ROOT') or exit('Access denied!');

/**
 * Interface pour le service de base de données
 * Suit le principe Interface Segregation (SOLID)
 */
interface DatabaseServiceInterface
{
    /**
     * Récupère ou crée la connexion PDO
     * 
     * @return PDO
     * @throws \PDOException Si la connexion échoue
     */
    public function getConnection(): PDO;

    /**
     * Ferme la connexion (utile pour les tests)
     */
    public function closeConnection(): void;
}



