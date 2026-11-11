<?php

namespace Forum\Services;

use Auth\Entities\User;
use Core\Auth\UsersManager;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour enrichir les données utilisateurs du forum
 */
class UserEnrichmentService
{
    /**
     * Vérifie qu'une URL d'avatar est exploitable
     */
    public static function isValidAvatarUrl(string $avatar): bool
    {
        $avatar = trim($avatar);
        if ($avatar === '' || $avatar === '/avatar' || $avatar === 'avatar') {
            return false;
        }
        if (strpos($avatar, '/avatar') !== false && strlen($avatar) <= 8) {
            return false;
        }
        return strpos($avatar, 'http') === 0 || strpos($avatar, '/') === 0 || strpos($avatar, 'upload/') !== false;
    }

    /**
     * Génère les initiales d'un pseudo
     */
    public static function getAvatarInitials(string $username): string
    {
        $username = trim($username);
        if ($username === '') {
            return '?';
        }
        if (function_exists('mb_substr')) {
            return mb_strtoupper(mb_substr($username, 0, 2, 'UTF-8'), 'UTF-8');
        }
        return strtoupper(substr($username, 0, 2));
    }

    /**
     * Génère un indice de teinte déterministe (1..8) basé sur le pseudo
     */
    public static function getAvatarTone(string $username): int
    {
        $hash = crc32($username);
        return (abs($hash) % 8) + 1;
    }

    /**
     * Récupère les données complètes d'un utilisateur par ID (alias pour ForumController)
     */
    public function enrichUser(int $userId): array
    {
        return $this->getUserFullData($userId, null);
    }

    /**
     * Récupère les données enrichies pour une liste d'IDs utilisateurs indexée par user_id
     *
     * @param array<int, int|string> $userIds
     * @return array<int, array>
     */
    public function enrichUsers(array $userIds): array
    {
        $identifiers = [];
        foreach (array_unique($userIds) as $uid) {
            if ($uid !== null && $uid !== '') {
                $id = (int) $uid;
                $identifiers[$id] = ['user_id' => $id];
            }
        }
        return $this->getBatchUsersData($identifiers);
    }

    /**
     * Récupère les données complètes d'un utilisateur (CMS + profil)
     */
    public function getUserFullData(?int $userId, ?string $email = null): array
    {
        $cmsData = null;

        if ($userId !== null) {
            $cmsData = UsersManager::getUserById($userId);
        } elseif ($email !== null) {
            $cmsData = UsersManager::searchUser($email);
        }

        return $this->buildEnrichedUser($userId, $email, $cmsData);
    }

    /**
     * Récupère les données pour plusieurs utilisateurs en une seule fois (optimisation)
     *
     * @param array<int, array{user_id?: int|null, email?: string|null}> $userIdentifiers
     */
    public function getBatchUsersData(array $userIdentifiers): array
    {
        $results = [];

        $cmsUsersById = [];
        $cmsUsersByEmail = [];

        try {
            $allUsers = User::all();
            foreach ($allUsers as $u) {
                $uid = $u->id ?? ($u->attributes['id'] ?? null);
                $uemail = $u->email ?? ($u->attributes['email'] ?? null);
                if ($uid !== null) {
                    $cmsUsersById[(int) $uid] = $u;
                }
                if (!empty($uemail)) {
                    $cmsUsersByEmail[(string) $uemail] = $u;
                }
            }
        } catch (\Throwable $e) {
            // Ignorer si le plugin auth n'est pas chargé
        }

        foreach ($userIdentifiers as $key => $identifier) {
            $userId = isset($identifier['user_id']) && $identifier['user_id'] !== null ? (int) $identifier['user_id'] : null;
            $email = $identifier['email'] ?? null;

            $cmsData = null;
            if ($userId !== null && isset($cmsUsersById[$userId])) {
                $cmsData = $cmsUsersById[$userId];
            } elseif ($email !== null && isset($cmsUsersByEmail[$email])) {
                $cmsData = $cmsUsersByEmail[$email];
            }

            $results[$key] = $this->buildEnrichedUser($userId, $email, $cmsData);
        }

        return $results;
    }

    /**
     * Construit le tableau normalisé d'un auteur pour les vues du forum
     */
    private function buildEnrichedUser(?int $userId, ?string $email, $cmsData): array
    {
        $username = 'Anonyme';
        if ($cmsData) {
            $username = (string) ($cmsData->pseudo ?? $cmsData->username ?? $cmsData->email ?? 'Anonyme');
        } elseif ($email) {
            $username = explode('@', $email)[0];
        }

        $avatar = $cmsData ? (string) ($cmsData->avatar ?? '') : '';
        if (!self::isValidAvatarUrl($avatar)) {
            $avatar = '';
        }

        $sexRaw = $cmsData ? ($cmsData->sex ?? null) : null;
        $sex = null;
        if ($sexRaw === 'M') {
            $sex = 'Homme';
        } elseif ($sexRaw === 'F') {
            $sex = 'Femme';
        } elseif ($sexRaw === 'O') {
            $sex = 'Autre';
        } elseif (!empty($sexRaw)) {
            $sex = (string) $sexRaw;
        }

        $birthyear = $cmsData ? ($cmsData->birthyear ?? null) : null;
        $age = null;
        if (!empty($birthyear) && is_numeric($birthyear)) {
            $age = (int) date('Y') - (int) $birthyear;
        }

        $createdAt = $cmsData ? ($cmsData->created_at ?? null) : null;
        $createdAtFormatted = '';
        if (!empty($createdAt)) {
            $ts = strtotime((string) $createdAt);
            if ($ts) {
                $createdAtFormatted = date('d/m/Y', $ts);
            }
        }

        return [
            'id' => $userId ?? ($cmsData->id ?? null),
            'email' => $email ?? ($cmsData->email ?? ''),
            'username' => $username,
            'pseudo' => $cmsData ? ($cmsData->pseudo ?? null) : null,
            'avatar' => $avatar,
            'has_valid_avatar' => $avatar !== '',
            'avatar_initials' => self::getAvatarInitials($username),
            'avatar_tone' => self::getAvatarTone($username),
            'avatar_color' => 'var(--evo-accent)',
            'bio' => $cmsData ? ($cmsData->bio ?? '') : '',
            'group_id' => $cmsData ? ($cmsData->group_id ?? null) : null,
            'group_name' => $cmsData ? ($cmsData->group_name ?? null) : null,
            'created_at' => $createdAt,
            'created_at_formatted' => $createdAtFormatted,
            'sex' => $sex,
            'age' => $age,
            'city' => $cmsData ? ($cmsData->city ?? null) : null,
            'district' => $cmsData ? ($cmsData->district ?? null) : null,
            'has_cms_data' => $cmsData !== null,
            'has_mysql_data' => !empty($city ?? ($cmsData->city ?? null)) || !empty($age),
            'is_authenticated' => $cmsData !== null,
            'post_count' => 0,
        ];
    }
}
