<?php

namespace PublicStats\Controllers;

use Core\Core;
use Core\Auth\User;
use Core\Router\Router;
use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Responses\ApiResponse;

defined('ROOT') or exit('Access denied!');

class PublicStatsController extends PublicController
{
    public function index()
    {
        // Récupérer les statistiques
        $stats = $this->gatherStats();

        // Créer la réponse
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-chart-pie"></i> Statistiques du site');
        
        $tpl = $response->createPluginTemplate('publicstats', 'index');
        $tpl->set('stats', $stats);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function api()
    {
        $stats = $this->gatherStats();
        $response = new ApiResponse();
        $response->status = ApiResponse::STATUS_SUCCESS;
        $response->body = $stats;
        return $response;
    }

    private function gatherStats(): array
    {
        $stats = [];
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('publicstats');
        $config = $plugin->getConfig();

        // 1. Statistiques utilisateurs
        if (!empty($config['show_users'])) {
            $stats['users'] = $this->getUserStats();
        }

        // 2. Statistiques sondages
        if (!empty($config['show_polls'])) {
            $stats['polls'] = $this->getPollStats();
        }

        // 3. Statistiques forum
        if (!empty($config['show_forum'])) {
            $stats['forum'] = $this->getForumStats();
        }

        // 4. Statistiques quartiers
        if (!empty($config['show_districts'])) {
            $stats['districts'] = $this->getDistrictStats();
        }

        // 5. Statistiques générales
        if (!empty($config['show_general'])) {
            $stats['general'] = $this->getGeneralStats();
        }

        // 6. Activité récente (7 jours)
        $stats['activity'] = $this->getActivityStats();

        // 7. Statistiques avancées (pour les graphiques)
        $stats['advanced'] = $this->getAdvancedStats();
        
        // 8. Préparer les données JSON pour les graphiques (évite les boucles complexes dans le tpl)
        $stats['json'] = $this->prepareJsonData($stats);

        return $stats;
    }

    private function prepareJsonData(array $stats): array
    {
        $json = [];

        // Growth
        $json['growth_labels'] = json_encode(array_keys($stats['advanced']['user_growth']), JSON_UNESCAPED_UNICODE);
        $json['growth_users'] = json_encode(array_values($stats['advanced']['user_growth']));
        $json['growth_topics'] = isset($stats['advanced']['topic_growth']) ? json_encode(array_values($stats['advanced']['topic_growth'])) : '[]';

        // Age
        if (isset($stats['users']['ageDistribution'])) {
            $ageLabels = array_keys($stats['users']['ageDistribution']);
            // Ajouter " ans" aux labels
            $ageLabels = array_map(function($l) { return $l . ' ans'; }, $ageLabels);
            $json['age_labels'] = json_encode($ageLabels, JSON_UNESCAPED_UNICODE);
            $json['age_data'] = json_encode(array_values($stats['users']['ageDistribution']));
        }

        // Districts
        if (isset($stats['districts']['districts'])) {
            $json['districts_labels'] = json_encode(array_keys($stats['districts']['districts']), JSON_UNESCAPED_UNICODE);
            $json['districts_data'] = json_encode(array_values($stats['districts']['districts']));
        }

        // Polls
        if (isset($stats['polls'])) {
             // Pas besoin de json_encode pour des scalaires simples, mais utile pour cohérence si structure complexe
             // Ici on les passe directement dans le TPL via {{ }} c'est simple
        }
        
        return $json;
    }

    private function getUserStats(): array
    {
        $data = $this->core->callHook('user:get-stats', []);
        return $data['stats'] ?? [
            'total' => 0,
            'active' => 0,
            'ageDistribution' => [],
            'sexDistribution' => ['M' => 0, 'F' => 0, 'O' => 0, 'non_specifie' => 0]
        ];
    }

    private function getPollStats(): array
    {
        $data = $this->core->callHook('poll:get-stats', []);
        return $data['stats'] ?? ['total' => 0, 'active' => 0, 'closed' => 0, 'votes' => 0, 'average' => 0];
    }

    private function getForumStats(): array
    {
        $data = $this->core->callHook('forum:get-stats', []);
        return $data['stats'] ?? ['topics' => 0, 'posts' => 0, 'categories' => 0];
    }

    private function getDistrictStats(): array
    {
        // On garde User::all() ici pour l'instant car c'est une feature "interne" 
        // ou on pourrait aussi le passer par le hook user:get-stats si on veut être puriste.
        // Mais User::all() est dans le Core, pas dans un autre plugin.
        $users = \Core\Auth\User::all();
        $districtDistribution = [];
        $cityDistribution = [];

        foreach ($users as $user) {
            $district = $user->attributes['district'] ?? null;
            if ($district && $district !== '') {
                $districtDistribution[$district] = ($districtDistribution[$district] ?? 0) + 1;
            }

            $city = $user->attributes['city'] ?? null;
            if ($city && $city !== '') {
                $cityDistribution[$city] = ($cityDistribution[$city] ?? 0) + 1;
            }
        }

        // Trier par nombre décroissant
        $districtDistribution && arsort($districtDistribution);
        $cityDistribution && arsort($cityDistribution);

        return [
            'districts' => $districtDistribution,
            'cities' => $cityDistribution,
            'topDistrict' => !empty($districtDistribution) ? array_key_first($districtDistribution) : null,
            'topCity' => !empty($cityDistribution) ? array_key_first($cityDistribution) : null
        ];
    }

    private function getGeneralStats(): array
    {
        return [
            'lastUpdate' => date('d/m/Y à H:i'),
            'pluginsActive' => $this->getActivePluginsCount()
        ];
    }

    private function getActivityStats(): array
    {
        $userStats = $this->getUserStats();
        $forumStats = $this->getForumStats();

        return [
            'new_users_7d' => $userStats['new_users_7d'] ?? 0,
            'new_topics_7d' => $forumStats['new_topics_7d'] ?? 0,
            'new_posts_7d' => $forumStats['new_posts_7d'] ?? 0
        ];
    }

    private function getAdvancedStats(): array
    {
        $userStats = $this->getUserStats();
        $forumStats = $this->getForumStats();

        // Cross-Referenced demographics by District
        // On doit le refaire ici car userStatProvider ne le fournit pas par défaut
        // et User::all() est accessible.
        $users = \Core\Auth\User::all();
        $districtsByAge = [];
        $districtsByGender = [];
        
        foreach ($users as $user) {
            $district = $user->attributes['district'] ?? null;
            if (!$district) continue;
            
            // Age
            $age = $user->attributes['age'] ?? null;
            $ageRange = $age ? $this->getAgeRange((int)$age) : 'Inconnu';
            
            if (!isset($districtsByAge[$district])) $districtsByAge[$district] = [];
            if (!isset($districtsByAge[$district][$ageRange])) $districtsByAge[$district][$ageRange] = 0;
            $districtsByAge[$district][$ageRange]++;

            // Gender
            $gender = $user->attributes['sex'] ?? 'non_specifie';
            if (!isset($districtsByGender[$district])) $districtsByGender[$district] = [];
            if (!isset($districtsByGender[$district][$gender])) $districtsByGender[$district][$gender] = 0;
            $districtsByGender[$district][$gender]++;
        }

        return [
            'user_growth' => $userStats['user_growth'] ?? [],
            'topic_growth' => $forumStats['topic_growth'] ?? [],
            'districts_by_age' => $districtsByAge,
            'districts_by_gender' => $districtsByGender
        ];
    }

    private function getAgeRange(int $age): string
    {
        if ($age < 18) return '0-17';
        if ($age < 25) return '18-24';
        if ($age < 35) return '25-34';
        if ($age < 45) return '35-44';
        if ($age < 55) return '45-54';
        if ($age < 65) return '55-64';
        return '65+';
    }

    private function getActivePluginsCount(): int
    {
        $pluginsDir = ROOT . 'plugin/';
        if (!is_dir($pluginsDir)) {
            return 0;
        }

        $count = 0;
        $plugins = scandir($pluginsDir);
        foreach ($plugins as $plugin) {
            if ($plugin === '.' || $plugin === '..') continue;
            $pluginPath = $pluginsDir . $plugin;
            if (is_dir($pluginPath) && file_exists($pluginPath . '/' . $plugin . '.php')) {
                $count++;
            }
        }

        return $count;
    }
}
