<?php

namespace Gamification\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Util;

defined('ROOT') or exit('No direct script access allowed');

class GamificationAdminController extends AdminController
{
    public function dashboard()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('gamification', 'admin');
        
        // Charger la configuration
        $configFile = DATA_PLUGIN . 'gamification' . DS . 'gamification_conf.json';
        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        
        // Charger les paliers
        $paliersFile = DATA_PLUGIN . 'gamification' . DS . 'paliers.json';
        $paliers = file_exists($paliersFile) ? json_decode(file_get_contents($paliersFile), true) : [];
        if (!is_array($paliers)) {
            $paliers = [];
        }
        
        // Trier les paliers par points_required
        usort($paliers, function($a, $b) {
            return ($a['points_required'] ?? 0) <=> ($b['points_required'] ?? 0);
        });
        
        $tpl->set('config', $config);
        $tpl->set('paliers', $paliers);
        $tpl->set('linkToHome', $this->router->generate('admin-gamification'));
        $tpl->set('linkSave', $this->router->generate('admin-gamification-save'));
        $tpl->set('linkPalierSave', $this->router->generate('admin-gamification-palier-save'));
        $tpl->set('linkRecalculate', $this->router->generate('admin-gamification-recalculate'));
        
        // Ajouter l'URL de suppression à chaque palier
        foreach ($paliers as &$palier) {
            $palier['delete_url'] = $this->router->generate('admin-gamification-palier-delete', ['id' => $palier['id']]);
        }
        unset($palier); // Libérer la référence
        
        $response->addTemplate($tpl);
        return $response;
    }
    
    public function save()
    {
        $configFile = DATA_PLUGIN . 'gamification' . DS . 'gamification_conf.json';
        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        
        // Mettre à jour la configuration
        $config['points_contribution'] = (int)($_POST['points_contribution'] ?? 10);
        $config['points_contribution_accepted'] = (int)($_POST['points_contribution_accepted'] ?? 50);
        $config['points_edit_poi'] = (int)($_POST['points_edit_poi'] ?? 5);
        $config['points_edit_poi_accepted'] = (int)($_POST['points_edit_poi_accepted'] ?? 25);
        $config['points_claim_poi'] = (int)($_POST['points_claim_poi'] ?? 100);
        $config['points_claim_poi_validated'] = (int)($_POST['points_claim_poi_validated'] ?? 200);
        $config['points_login_daily'] = (int)($_POST['points_login_daily'] ?? 1);
        $config['points_first_contribution'] = (int)($_POST['points_first_contribution'] ?? 50);
        $config['public_leaderboard'] = isset($_POST['public_leaderboard']) && $_POST['public_leaderboard'] === '1';
        
        Util::writeJsonFile($configFile, $config);
        
        return $this->core->redirect($this->router->generate('admin-gamification'));
    }
    
    public function savePalier()
    {
        $paliersFile = DATA_PLUGIN . 'gamification' . DS . 'paliers.json';
        $paliers = file_exists($paliersFile) ? json_decode(file_get_contents($paliersFile), true) : [];
        if (!is_array($paliers)) {
            $paliers = [];
        }
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        
        if ($id) {
            // Modifier un palier existant
            foreach ($paliers as &$palier) {
                if ($palier['id'] == $id) {
                    $palier['name'] = $_POST['name'] ?? '';
                    $palier['points_required'] = (int)($_POST['points_required'] ?? 0);
                    $palier['badge_name'] = $_POST['badge_name'] ?? '';
                    $palier['badge_icon'] = $_POST['badge_icon'] ?? 'fa-star';
                    $palier['badge_color'] = $_POST['badge_color'] ?? '#3498db';
                    break;
                }
            }
        } else {
            // Créer un nouveau palier
            $maxId = 0;
            foreach ($paliers as $palier) {
                if (($palier['id'] ?? 0) > $maxId) {
                    $maxId = $palier['id'];
                }
            }
            
            $paliers[] = [
                'id' => $maxId + 1,
                'name' => $_POST['name'] ?? '',
                'points_required' => (int)($_POST['points_required'] ?? 0),
                'badge_name' => $_POST['badge_name'] ?? '',
                'badge_icon' => $_POST['badge_icon'] ?? 'fa-star',
                'badge_color' => $_POST['badge_color'] ?? '#3498db'
            ];
        }
        
        Util::writeJsonFile($paliersFile, $paliers);
        
        return $this->core->redirect($this->router->generate('admin-gamification'));
    }
    
    public function deletePalier($id)
    {
        $paliersFile = DATA_PLUGIN . 'gamification' . DS . 'paliers.json';
        $paliers = file_exists($paliersFile) ? json_decode(file_get_contents($paliersFile), true) : [];
        if (!is_array($paliers)) {
            $paliers = [];
        }
        
        $paliers = array_filter($paliers, function($palier) use ($id) {
            return ($palier['id'] ?? 0) != $id;
        });
        
        Util::writeJsonFile($paliersFile, array_values($paliers));
        
        return $this->core->redirect($this->router->generate('admin-gamification'));
    }
    
    /**
     * Recalculer les points de gamification pour tous les utilisateurs basés sur leurs stats historiques
     */
    public function recalculate()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('gamification', 'recalculate');
        
        // Charger la configuration
        $configFile = DATA_PLUGIN . 'gamification' . DS . 'gamification_conf.json';
        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        
        $tpl->set('config', $config);
        $tpl->set('linkToHome', $this->router->generate('admin-gamification'));
        $tpl->set('linkRecalculate', $this->router->generate('admin-gamification-recalculate'));
        $tpl->set('linkRecalculateRun', $this->router->generate('admin-gamification-recalculate-run'));
        $tpl->set('csrfToken', \Core\Security\Csrf::token());
        
        $response->addTemplate($tpl);
        return $response;
    }
    
    /**
     * Exécuter le recalcul des points
     */
    public function recalculateRun()
    {
        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null, false)) {
            \Utils\Show::msg(\Core\Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('admin-gamification-recalculate'));
        }
        
        require_once PLUGINS . 'gamification/services/PointsService.php';
        $pointsService = new \Gamification\Services\PointsService();
        
        // Charger la configuration
        $configFile = DATA_PLUGIN . 'gamification' . DS . 'gamification_conf.json';
        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        
        // Récupérer tous les utilisateurs
        $users = \Core\Auth\User::all();
        $processed = 0;
        $errors = 0;
        $totalPointsAdded = 0;
        
        foreach ($users as $user) {
            $userId = (int)($user->attributes['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            
            try {
                // Récupérer les stats de l'utilisateur
                $stats = $this->getUserStatsForRecalculation($userId);
                
                $userPointsAdded = 0;
                
                // POIs possédés (claims approuvés) - points_claim_poi_validated
                if ($stats['pois_owned'] > 0) {
                    $points = ($config['points_claim_poi_validated'] ?? 200) * $stats['pois_owned'];
                    if ($points > 0) {
                        $pointsService->addPoints($userId, $points, 'migration_initiale', [
                            'type' => 'claim_validated',
                            'count' => $stats['pois_owned']
                        ]);
                        $userPointsAdded += $points;
                    }
                }
                
                // Propositions de POI approuvées - points_contribution_accepted (ou points_edit_poi_accepted ?)
                if ($stats['proposals']['approved'] > 0) {
                    $points = ($config['points_contribution_accepted'] ?? 50) * $stats['proposals']['approved'];
                    if ($points > 0) {
                        $pointsService->addPoints($userId, $points, 'migration_initiale', [
                            'type' => 'proposal_approved',
                            'count' => $stats['proposals']['approved']
                        ]);
                        $userPointsAdded += $points;
                    }
                }
                
                // Photos uploadées - points_contribution (ou points_edit_poi ?)
                if ($stats['photos_uploaded'] > 0) {
                    $points = ($config['points_contribution'] ?? 10) * $stats['photos_uploaded'];
                    if ($points > 0) {
                        $pointsService->addPoints($userId, $points, 'migration_initiale', [
                            'type' => 'photo_uploaded',
                            'count' => $stats['photos_uploaded']
                        ]);
                        $userPointsAdded += $points;
                    }
                }
                
                // Liens ajoutés - points_contribution
                if ($stats['links_added'] > 0) {
                    $points = ($config['points_contribution'] ?? 10) * $stats['links_added'];
                    if ($points > 0) {
                        $pointsService->addPoints($userId, $points, 'migration_initiale', [
                            'type' => 'link_added',
                            'count' => $stats['links_added']
                        ]);
                        $userPointsAdded += $points;
                    }
                }
                
                // Tags ajoutés - points_contribution
                if ($stats['tags_added'] > 0) {
                    $points = ($config['points_contribution'] ?? 10) * $stats['tags_added'];
                    if ($points > 0) {
                        $pointsService->addPoints($userId, $points, 'migration_initiale', [
                            'type' => 'tag_added',
                            'count' => $stats['tags_added']
                        ]);
                        $userPointsAdded += $points;
                    }
                }
                
                // Descriptions ajoutées - points_contribution_accepted
                if ($stats['descriptions_added'] > 0) {
                    $points = ($config['points_contribution_accepted'] ?? 50) * $stats['descriptions_added'];
                    if ($points > 0) {
                        $pointsService->addPoints($userId, $points, 'migration_initiale', [
                            'type' => 'description_added',
                            'count' => $stats['descriptions_added']
                        ]);
                        $userPointsAdded += $points;
                    }
                }
                
                $processed++;
                $totalPointsAdded += $userPointsAdded;
                
            } catch (\Exception $e) {
                $errors++;
                $this->core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "GamificationAdminController::recalculateRun - Erreur pour utilisateur {$userId}: " . $e->getMessage());
            }
        }
        
        \Utils\Show::msg("Recalcul terminé : {$processed} utilisateur(s) traité(s), {$totalPointsAdded} points ajoutés au total" . ($errors > 0 ? ", {$errors} erreur(s)" : ""), $errors > 0 ? 'warning' : 'success');
        
        return $this->core->redirect($this->router->generate('admin-gamification-recalculate'));
    }
    
    /**
     * Récupère les stats d'un utilisateur pour le recalcul (similaire à UsersLoginController::getUserStats)
     */
    private function getUserStatsForRecalculation(int $userId): array
    {
        $stats = [
            'pois_owned' => 0,
            'proposals' => ['approved' => 0, 'pending' => 0, 'rejected' => 0, 'total' => 0],
            'photos_uploaded' => 0,
            'links_added' => 0,
            'tags_added' => 0,
            'descriptions_added' => 0,
        ];
        
        try {
            // Récupérer l'utilisateur CMS
            $user = \Core\Auth\User::find('id', $userId);
            if (!$user) {
                return $stats;
            }
            
            $userEmail = $user->attributes['email'] ?? null;
            $username = $user->attributes['username'] ?? null;
            
            // Récupérer l'ID MySQL de l'utilisateur
            $mysqlUserId = $this->getMySQLUserId($userEmail, $username);
            if (!$mysqlUserId) {
                return $stats;
            }
            
            // Configuration de la base de données
            $dbConfig = $this->getDbConfig();
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', 
                $dbConfig['sql_host'] ?? 'localhost', 
                $dbConfig['sql_db'] ?? 'vivrebourges');
            $pdo = new \PDO($dsn, $dbConfig['sql_user'] ?? '', $dbConfig['sql_pass'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
            
            // POIs possédés (claims approuvés)
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT poi_id) as count FROM poi_claims WHERE user_id = ? AND status = 'approved'");
            $stmt->execute([$mysqlUserId]);
            $result = $stmt->fetch();
            $stats['pois_owned'] = (int)($result['count'] ?? 0);
            
            // Propositions de nouveaux POI
            $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM poi_proposals WHERE user_id = ? GROUP BY status");
            $stmt->execute([$mysqlUserId]);
            $proposals = $stmt->fetchAll();
            foreach ($proposals as $proposal) {
                $stats['proposals']['total'] += (int)$proposal['count'];
                $stats['proposals'][$proposal['status']] = (int)$proposal['count'];
            }
            
            // Photos uploadées
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM poi_photos WHERE uploaded_by = ?");
            $stmt->execute([$mysqlUserId]);
            $result = $stmt->fetch();
            $stats['photos_uploaded'] = (int)($result['count'] ?? 0);
            
            // Liens ajoutés
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM poi_links WHERE created_by = ?");
            $stmt->execute([$mysqlUserId]);
            $result = $stmt->fetch();
            $stats['links_added'] = (int)($result['count'] ?? 0);
            
            // Tags ajoutés (excluant logo, background_color, stars, _normalized)
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM poi_tags WHERE created_by = ? AND key_name NOT IN ('logo', 'background_color', 'stars', '_normalized')");
            $stmt->execute([$mysqlUserId]);
            $result = $stmt->fetch();
            $stats['tags_added'] = (int)($result['count'] ?? 0);
            
            // Descriptions ajoutées
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM poi_descriptions WHERE created_by = ?");
            $stmt->execute([$mysqlUserId]);
            $result = $stmt->fetch();
            $stats['descriptions_added'] = (int)($result['count'] ?? 0);
            
        } catch (\Exception $e) {
            $this->core->getLogger()->log(\Core\Logger::LEVEL_WARNING, "GamificationAdminController::getUserStatsForRecalculation - Erreur pour utilisateur {$userId}: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Récupère l'ID MySQL d'un utilisateur à partir de son email ou username
     */
    private function getMySQLUserId(?string $email, ?string $username): ?int
    {
        try {
            $dbConfig = $this->getDbConfig();
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', 
                $dbConfig['sql_host'] ?? 'localhost', 
                $dbConfig['sql_db'] ?? 'vivrebourges');
            $pdo = new \PDO($dsn, $dbConfig['sql_user'] ?? '', $dbConfig['sql_pass'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
            
            if ($email && $username) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
                $stmt->execute([$email, $username]);
            } elseif ($email) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
            } elseif ($username) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
            } else {
                return null;
            }
            
            $result = $stmt->fetch();
            return $result ? (int)$result['id'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Récupère la configuration de la base de données MySQL
     */
    private function getDbConfig(): array
    {
        // Essayer de charger depuis claim_conf.json (source de vérité pour MySQL)
        $configFile = DATA_PLUGIN . 'claim' . DS . 'claim_conf.json';
        if (file_exists($configFile)) {
            return json_decode(file_get_contents($configFile), true) ?: [];
        }
        
        // Fallback : essayer searchEVO
        $configFile = DATA_PLUGIN . 'searchEVO' . DS . 'search_conf.json';
        if (file_exists($configFile)) {
            return json_decode(file_get_contents($configFile), true) ?: [];
        }
        
        return [];
    }
}

