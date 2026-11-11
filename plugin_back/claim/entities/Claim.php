<?php

namespace Claim\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Claim (Revendication)
 * 
 * @property int $id
 * @property int $poi_id
 * @property int $user_id
 * @property string $status
 * @property string $note
 * @property string $proof_path
 * @property string $requested_at
 * @property string $approved_at
 * 
 * Entité représentant une demande de revendication d'un POI par un utilisateur.
 */
class Claim extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'claim/claims.json';
    protected static string $tableName = 'poi_claims';
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'poi_id' => 'integer',
        'user_id' => 'integer'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
        if (empty($this->attributes['status'])) {
            $this->attributes['status'] = 'pending';
        }
        if (empty($this->attributes['requested_at'])) {
            $this->attributes['requested_at'] = date('Y-m-d H:i:s');
        }
    }

    // Getters / Setters
    public function getId() { return $this->attributes['id'] ?? null; }
    public function getPoiId() { return $this->attributes['poi_id'] ?? null; }
    public function getUserId() { return $this->attributes['user_id'] ?? null; }
    public function getStatus() { return $this->attributes['status'] ?? 'pending'; }
    public function getNote() { return $this->attributes['note'] ?? ''; }
    public function getProofPath() { return $this->attributes['proof_path'] ?? null; }
    public function getRequestedAt() { return $this->attributes['requested_at'] ?? null; }
    public function getApprovedAt() { return $this->attributes['approved_at'] ?? null; }

    public function setId($val) { $this->attributes['id'] = $val; }
    public function setPoiId($val) { $this->attributes['poi_id'] = (int)$val; }
    public function setUserId($val) { $this->attributes['user_id'] = (int)$val; }
    public function setStatus($val) { $this->attributes['status'] = $val; }
    public function setNote($val) { $this->attributes['note'] = $val; }
    public function setProofPath($val) { $this->attributes['proof_path'] = $val; }
    public function setRequestedAt($val) { $this->attributes['requested_at'] = $val; }
    public function setApprovedAt($val) { $this->attributes['approved_at'] = $val; }
}
