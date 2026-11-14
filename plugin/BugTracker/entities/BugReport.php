<?php

namespace BugTracker\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class BugReport extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'BugTracker' . DS . 'bugs.json';
    protected static string $tableName = 'bugtracker_reports';
    protected static string $primaryKey = 'id';

    /**
     * Statuts possibles
     */
    const STATUS_PENDING = 'pending';      // En attente
    const STATUS_IN_PROGRESS = 'in_progress'; // Pris en charge
    const STATUS_FIXED = 'fixed';           // Corrigé
    const STATUS_REJECTED = 'rejected';    // Rejeté

    /**
     * Priorités possibles
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    /**
     * Retourne le libellé du statut
     */
    public function getStatusLabel(): string
    {
        return match($this->attributes['status'] ?? self::STATUS_PENDING) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_IN_PROGRESS => 'Pris en charge',
            self::STATUS_FIXED => 'Corrigé',
            self::STATUS_REJECTED => 'Rejeté',
            default => 'Inconnu'
        };
    }

    /**
     * Retourne le libellé de la priorité
     */
    public function getPriorityLabel(): string
    {
        return match($this->attributes['priority'] ?? self::PRIORITY_MEDIUM) {
            self::PRIORITY_LOW => 'Basse',
            self::PRIORITY_MEDIUM => 'Moyenne',
            self::PRIORITY_HIGH => 'Haute',
            self::PRIORITY_CRITICAL => 'Critique',
            default => 'Moyenne'
        };
    }

    /**
     * Retourne la classe CSS pour le statut
     */
    public function getStatusClass(): string
    {
        return match($this->attributes['status'] ?? self::STATUS_PENDING) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_FIXED => 'success',
            self::STATUS_REJECTED => 'alert',
            default => 'secondary'
        };
    }

    /**
     * Retourne la classe CSS pour la priorité
     */
    public function getPriorityClass(): string
    {
        return match($this->attributes['priority'] ?? self::PRIORITY_MEDIUM) {
            self::PRIORITY_LOW => 'secondary',
            self::PRIORITY_MEDIUM => 'info',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_CRITICAL => 'alert',
            default => 'info'
        };
    }
}

