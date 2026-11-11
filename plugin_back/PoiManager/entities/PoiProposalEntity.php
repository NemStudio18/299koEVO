<?php

namespace PoiManager\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Entité PoiProposalEntity
 *
 * Représente une proposition de création d'un nouveau POI soumise par un membre.
 * Stockée dans la table `poi_proposals`.
 *
 * Workflow : status = pending → (admin) approved / rejected
 * Quand approved : un nouveau POI est créé dans poi_public par PoiProposalService.
 */
class PoiProposalEntity extends JsonActiveRecord
{
    protected static string $filePath     = DATA_PLUGIN . 'PoiManager/proposals.json'; // requis par JsonActiveRecord même en mode SQL
    protected static string $tableName    = 'poi_proposals';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey   = 'id';
    protected static array  $casts = [
        'user_id'     => 'integer',
        'reviewed_by' => 'integer',
        'is_owner'    => 'boolean',
        'lat'         => 'float',
        'lon'         => 'float',
    ];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}
