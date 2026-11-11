<?php

namespace PoiManager\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Entité PoiSuggestionEntity
 *
 * Représente une suggestion de correction d'un POI existant soumise par un membre.
 * Stockée dans la table `poi_suggestions`.
 *
 * Chaque ligne = un champ modifié (field_name / old_value / new_value).
 * Workflow : status = pending → (admin) approved / rejected
 * Quand approved : le champ est appliqué sur poi_public par PoiSuggestionService.
 */
class PoiSuggestionEntity extends JsonActiveRecord
{
    protected static string $filePath     = DATA_PLUGIN . 'PoiManager/suggestions.json'; // requis par JsonActiveRecord même en mode SQL
    protected static string $tableName    = 'poi_suggestions';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey   = 'id';
    protected static array  $casts = [
        'poi_id'      => 'integer',
        'user_id'     => 'integer',
        'reviewed_by' => 'integer',
    ];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}
