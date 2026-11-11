<?php

namespace SearchEngine\Services;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour les statistiques de recherche
 * Note: Cette classe est un stub minimal pour éviter les erreurs.
 * Les statistiques de recherche ne sont pas encore implémentées.
 */
class SearchStatsService
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Retourne le nombre total de recherches
     * 
     * @param string $period 'all', 'day', 'week', 'month'
     * @return int
     */
    public function getTotalSearches(string $period = 'all'): int
    {
        // TODO: Implémenter le tracking des recherches
        return 0;
    }

    /**
     * Retourne les recherches les plus fréquentes
     * 
     * @param int $limit Nombre de résultats
     * @param string $period 'all', 'day', 'week', 'month'
     * @return array
     */
    public function getTopSearches(int $limit = 20, string $period = 'all'): array
    {
        // TODO: Implémenter le tracking des recherches
        return [];
    }

    /**
     * Retourne les POIs les plus souvent dans les résultats
     * 
     * @param int $limit Nombre de résultats
     * @param string $period 'all', 'day', 'week', 'month'
     * @return array
     */
    public function getTopResultPois(int $limit = 20, string $period = 'all'): array
    {
        // TODO: Implémenter le tracking des résultats
        return [];
    }

    /**
     * Retourne les POIs les plus visités
     * 
     * @param int $limit Nombre de résultats
     * @param string $period 'all', 'day', 'week', 'month'
     * @return array
     */
    public function getTopVisitedPois(int $limit = 20, string $period = 'all'): array
    {
        // TODO: Implémenter le tracking des visites
        return [];
    }

    /**
     * Retourne les statistiques d'évolution
     * 
     * @param string $granularity 'DAY', 'WEEK', 'MONTH'
     * @param int $days Nombre de jours
     * @return array
     */
    public function getEvolutionStats(string $granularity = 'DAY', int $days = 30): array
    {
        // TODO: Implémenter les statistiques d'évolution
        return [];
    }
}

