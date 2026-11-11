<?php

namespace Demo\Controllers;

use Core\Controllers\PublicController;
use Core\Auth\AdminTestMode;
use Utils\Show;

defined('ROOT') or exit('Access denied!');

class DemoPublicController extends PublicController
{
    public function activate()
    {
        $testMode = AdminTestMode::getInstance();
        $userId = '900002'; // Marie Martin - Propriétaire POI (pour tests Mes fiches)
        
        // On active le mode test
        $testMode->activate($userId);

        // Auto-login de l'utilisateur fictif
        $testUsersFile = DATA_PLUGIN . 'demo/test_users.json';
        
        // Lazy copy if files are missing (e.g. plugin was already installed)
        if (!file_exists($testUsersFile)) {
             $source = PLUGINS . 'demo/asset/test_users.json';
             $dest = DATA_PLUGIN . 'demo/test_users.json';
             if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0755, true);
             if (file_exists($source)) copy($source, $dest);
             
             $sourceFixtures = PLUGINS . 'demo/asset/test_fixtures.json';
             $destFixtures = DATA_PLUGIN . 'demo/test_fixtures.json';
             if (file_exists($sourceFixtures)) copy($sourceFixtures, $destFixtures);
        }

        $userData = null;
        if (is_file($testUsersFile)) {
            $testUsersData = \Utils\Util::readJsonFile($testUsersFile, true);
            if (is_array($testUsersData)) {
                foreach ($testUsersData as $tu) {
                    if (($tu['id'] ?? '') == $userId) {
                        $userData = $tu;
                        break;
                    }
                }
            }
        }

        if ($userData) {
            $user = new \Core\Auth\User($userData);
            
            // Connexion "Statique" pour éviter de générer un nouveau token qui ne serait pas sauvegardé
            // On utilise le token défini en dur dans le fichier JSON
            $_SESSION['email'] = $user->email;
            $_SESSION['token'] = $user->token;
            
            $_SESSION['is_demo_mode'] = true;
        }
        
        Show::msg('Mode Démo activé ! Vous êtes connecté en tant que ' . ($userData['_test_label'] ?? 'Utilisateur de démonstration') . '.', 'success');
        return $this->core->redirect($this->router->generate('home'));
    }

    public function stop()
    {
        $testMode = AdminTestMode::getInstance();
        $testMode->deactivate();
        unset($_SESSION['is_demo_mode']);
        
        // Déconnecter l'utilisateur test
        $this->core->auth()->logout();
        
        Show::msg('Mode Démo désactivé.', 'info');
        return $this->core->redirect($this->router->generate('home'));
    }
}
