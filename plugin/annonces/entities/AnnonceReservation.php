<?php

namespace Annonces\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') OR exit('Access denied!');

/**
 * Demande de réservation sans paiement associée à une petite annonce.
 */
class AnnonceReservation extends JsonActiveRecord
{
    public const VALID_STATUSES = ['pending', 'confirmed', 'cancelled'];

    protected static string $filePath = DATA_PLUGIN . 'annonces/reservations.json';
    protected static string $tableName = 'annonces_reservations';
    protected static string $primaryKey = 'id';
    protected static bool $isBooted = false;

    protected static function getSchema(): array
    {
        return [
            'annonce_id'      => 'string',
            'annonce_title'   => 'string',
            'requester_name'  => 'string',
            'requester_email' => 'string',
            'requester_phone' => 'string',
            'message'         => 'text',
            'status'          => 'string',
            'created_at'      => 'datetime',
        ];
    }

    public function getId(): string|int
    {
        return $this->attributes['id'] ?? '';
    }

    public function getStatusLabel(): string
    {
        $map = [
            'pending'   => 'En attente',
            'confirmed' => 'Confirmée',
            'cancelled' => 'Annulée',
        ];
        $status = $this->attributes['status'] ?? 'pending';
        return $map[$status] ?? $status;
    }
}
