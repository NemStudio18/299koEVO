<?php

namespace SearchEngine\Services;

defined('ROOT') or exit('Access denied!');

/**
 * Interface pour le service de configuration
 * Suit le principe Interface Segregation (SOLID)
 */
interface ConfigServiceInterface
{
    /**
     * Récupère une valeur de configuration
     * 
     * @param string $key Clé de configuration
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Récupère toute la configuration
     * 
     * @return array
     */
    public function all(): array;

    /**
     * Vérifie si une clé existe dans la configuration
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;
}



