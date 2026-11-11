<?php
/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace Core\Auth;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class UserFingerprint extends JsonActiveRecord
{
    protected static string $filePath = DATA_CORE_AUTH . 'user_fingerprints.json';
    protected static string $tableName = 'user_fingerprints';
    protected static string $primaryKey = 'id';

    public static function create($userId, string $fingerprintHash): bool
    {
        $fingerprint = new self();
        $fingerprint->user_id = (string)$userId;
        $fingerprint->fingerprint_hash = $fingerprintHash;
        $fingerprint->created_at = time();
        return $fingerprint->save();
    }

    /**
     * Finds other users sharing the same fingerprint.
     */
    public static function findOtherUsers(string $hash, $currentUserId): array
    {
        $all = self::all();
        $userIds = [];
        foreach ($all as $fp) {
            if ($fp->fingerprint_hash === $hash && (string)$fp->user_id !== (string)$currentUserId) {
                $userIds[] = (string)$fp->user_id;
            }
        }
        return array_values(array_unique($userIds));
    }

    /**
     * Deletes records older than $days.
     */
    public static function cleanup(int $days = 90): int
    {
        $threshold = time() - ($days * 24 * 3600);
        $all = self::all();
        $deletedCount = 0;
        foreach ($all as $fp) {
            if ($fp->created_at < $threshold) {
                $fp->delete();
                $deletedCount++;
            }
        }
        return $deletedCount;
    }
}
