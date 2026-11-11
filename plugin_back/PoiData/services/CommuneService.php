<?php

namespace PoiData\Services;

use PoiData\Entities\CommuneGeometry;
use SearchEngine\Services\SearchLogger;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour gérer les communes de l'agglomération
 */
class CommuneService
{
    /**
     * Charge les communes depuis agglo.json
     * 
     * @return array Liste des noms de communes
     */
    public function loadAggloCommunes(): array
    {
        $aggloFile = ROOT . 'common' . DS . 'data' . DS . 'agglo.json';
        
        if (!is_file($aggloFile)) {
            $aggloFile = DATA_PLUGIN . 'search' . DS . 'agglo.json';
            if (!is_file($aggloFile)) {
                $aggloFile = PLUGINS . 'search' . DS . 'param' . DS . 'agglo.json';
            }
        }

        if (!is_file($aggloFile)) {
            SearchLogger::warning("CommuneService: Fichier agglo.json introuvable: " . $aggloFile);
            return [];
        }

        // Pour la conformité, on évite file_get_contents direct si possible, 
        // mais agglo.json est un fichier de config/données statiques.
        $jsonContent = @file_get_contents($aggloFile);
        if ($jsonContent === false) {
            SearchLogger::error("CommuneService: Impossible de lire le fichier agglo.json: " . $aggloFile);
            return [];
        }

        $json = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            SearchLogger::error("CommuneService: Erreur de parsing JSON dans agglo.json: " . json_last_error_msg());
            return [];
        }

        $communes = $json['communes'] ?? [];
        if (empty($communes)) {
            SearchLogger::warning("CommuneService: Aucune commune trouvée dans agglo.json");
        }

        return $communes;
    }

    /**
     * Récupère les centroids des communes depuis commune_geometry
     * 
     * @param array $cfg Configuration SQL (ignorée, on utilise l'entité)
     * @param array $communeNames Liste des noms de communes
     * @return array Format: [nom => ['lat' => ..., 'lon' => ..., 'radius_km' => ...]]
     */
    public function getCommuneCentroids(array $cfg, array $communeNames): array
    {
        if (empty($communeNames)) {
            return [];
        }

        try {
            // Utilisation de l'entité CommuneGeometry au lieu du SQL brut
            $rows = CommuneGeometry::query()
                ->where('name', 'IN', $communeNames)
                ->get();

            SearchLogger::info('CommuneService: Centroids récupérés - demandés=' . count($communeNames) . ', trouvés=' . count($rows));

            if (count($rows) < count($communeNames)) {
                $foundNames = [];
                foreach ($rows as $row) {
                    $foundNames[] = $row->name;
                }
                $missingNames = array_diff($communeNames, $foundNames);
                SearchLogger::warning('CommuneService: Communes sans centroid - ' . implode(', ', array_slice($missingNames, 0, 5)));
            }

            $centroids = [];
            foreach ($rows as $row) {
                $centroids[$row->name] = [
                    'lat' => (float) $row->centroid_lat,
                    'lon' => (float) $row->centroid_lon,
                    'radius_km' => (float) ($row->bounding_radius_km ?? 10.0),
                ];
            }

            return $centroids;
        } catch (\Exception $e) {
            SearchLogger::error('CommuneService error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Charge les centroids de toutes les communes de l'agglomération
     * 
     * @param array $cfg Configuration SQL
     * @return array Format: [nom => ['lat' => ..., 'lon' => ..., 'radius_km' => ...]]
     */
    public function loadAggloCentroids(array $cfg): array
    {
        $communeNames = $this->loadAggloCommunes();
        if (empty($communeNames)) {
            return [];
        }

        $centroids = $this->getCommuneCentroids($cfg, $communeNames);
        return $centroids;
    }
}
