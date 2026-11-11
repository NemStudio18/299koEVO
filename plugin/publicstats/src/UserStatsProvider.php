<?php

namespace PublicStats;

use Core\Auth\User;

defined('ROOT') or exit('Access denied!');

class UserStatsProvider
{
    public static function registerHooks(): void
    {
        // Hooks are now declared in param/hooks.json
    }

    public static function getStats(array $data): array
    {
        $users = User::all();
        $totalUsers = count($users);
        $activeUsers = 0;
        $ageDistribution = [];
        $sexDistribution = ['M' => 0, 'F' => 0, 'O' => 0, 'non_specifie' => 0];

        foreach ($users as $user) {
            if ($user->status() === 'active') {
                $activeUsers++;
            }

            $age = $user->attributes['age'] ?? null;
            if ($age) {
                $ageRange = self::getAgeRange((int)$age);
                $ageDistribution[$ageRange] = ($ageDistribution[$ageRange] ?? 0) + 1;
            }

            $sex = $user->attributes['sex'] ?? 'non_specifie';
            if (in_array($sex, ['M', 'F', 'O'])) {
                $sexDistribution[$sex]++;
            } else {
                $sexDistribution['non_specifie']++;
            }
        }

        $stats = [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'ageDistribution' => $ageDistribution,
            'sexDistribution' => $sexDistribution,
            'new_users_7d' => 0,
            'user_growth' => []
        ];

        // Monthly growth (last 12 months)
        $monthlyUsers = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-$i months"));
            $monthlyUsers[$key] = 0;
        }

        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);

        foreach ($users as $user) {
            $id = $user->id;
            if (is_string($id) && strlen($id) >= 13) {
                 $timestamp = hexdec(substr($id, 0, 8));
                 
                 // 7 days activity
                 if ($timestamp > $sevenDaysAgo) {
                     $stats['new_users_7d']++;
                 }

                 // Monthly growth
                 $month = date('Y-m', $timestamp);
                 if (isset($monthlyUsers[$month])) {
                     $monthlyUsers[$month]++;
                 }
            }
        }
        $stats['user_growth'] = $monthlyUsers;

        $data['stats'] = $stats;
        return $data;
    }

    private static function getAgeRange(int $age): string
    {
        if ($age < 18) return '0-17';
        if ($age < 25) return '18-24';
        if ($age < 35) return '25-34';
        if ($age < 45) return '35-44';
        if ($age < 55) return '45-54';
        if ($age < 65) return '55-64';
        return '65+';
    }

    /**
     * Hook: core:stats-collector
     */
    public static function collectStats(array $stats): array
    {
        $userStats = self::getStats([]);
        if (!empty($userStats['stats'])) {
            $stats['auth'] = $userStats['stats'];
            if (isset($userStats['stats']['total'])) {
                $stats['counts']['users'] = $userStats['stats']['total'];
            }
        }
        return $stats;
    }
}
