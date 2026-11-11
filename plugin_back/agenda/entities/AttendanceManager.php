<?php

namespace Agenda\Entities;

defined('ROOT') or exit('Access denied!');

/**
 * Gestionnaire des présences aux événements
 * Sépare les utilisateurs inscrits des anonymes
 */
class AttendanceManager
{
    public function __construct()
    {
    }

    /**
     * Add or remove attendance for a user
     * @param int $eventId
     * @param int|null $userId User ID if logged in, null for anonymous
     * @param string|null $identifier IP hash or session ID for anonymous users
     * @return bool True if added, False if removed
     */
    public function toggleAttendance($eventId, ?int $userId = null, ?string $identifier = null): bool
    {
        $query = Attendance::query()->where('event_id', '=', (string)$eventId);
        
        if ($userId !== null) {
            $query->where('user_id', '=', $userId);
        } else if ($identifier !== null) {
            $query->where('anonymous_id', '=', $identifier);
        } else {
            return false;
        }

        $existing = $query->first();

        if ($existing) {
            $existing->delete();
            return false;
        } else {
            $attendance = new Attendance([
                'event_id' => (string)$eventId,
                'user_id' => $userId,
                'anonymous_id' => $identifier,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $attendance->save();
            return true;
        }
    }

    /**
     * Check if user is attending an event
     */
    public function isAttending($eventId, ?int $userId = null, ?string $identifier = null): bool
    {
        $query = Attendance::query()->where('event_id', '=', (string)$eventId);
        
        if ($userId !== null) {
            $query->where('user_id', '=', $userId);
        } else if ($identifier !== null) {
            $query->where('anonymous_id', '=', $identifier);
        } else {
            return false;
        }

        return $query->count() > 0;
    }

    /**
     * Get attendance counts for an event
     */
    public function getAttendanceCount($eventId): array
    {
        $all = Attendance::query()->where('event_id', '=', (string)$eventId)->get();
        
        $registered = 0;
        $anonymous = 0;
        
        foreach ($all as $item) {
            if ($item->user_id !== null) {
                $registered++;
            } else if ($item->anonymous_id !== null) {
                $anonymous++;
            }
        }

        return [
            'registered' => $registered,
            'anonymous' => $anonymous,
            'total' => $registered + $anonymous
        ];
    }

    /**
     * Get detailed attendance for admin
     */
    public function getAttendanceDetails($eventId): array
    {
        $all = Attendance::query()->where('event_id', '=', (string)$eventId)->get();
        
        $registered = [];
        $anonymous = [];
        
        foreach ($all as $item) {
            if ($item->user_id !== null) {
                $registered[] = (string)$item->user_id;
            } else if ($item->anonymous_id !== null) {
                $anonymous[] = $item->anonymous_id;
            }
        }

        return [
            'registered' => $registered,
            'anonymous' => $anonymous
        ];
    }

    /**
     * Generate anonymous identifier from IP and User Agent
     */
    public static function generateAnonymousIdentifier(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', $ip . $userAgent);
    }
}
