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
        $role = $_GET['role'] ?? 'admin';
        $userId = match ($role) {
            'membre' => '900001',
            'editeur' => '900002',
            default => '900003',
        };

        // Activer le mode bac à sable (AdminTestMode)
        $testMode->activate($userId);

        $testUsersFile = DATA_PLUGIN . 'demo/test_users.json';
        if (!file_exists($testUsersFile)) {
            $source = PLUGINS . 'demo/asset/test_users.json';
            $dest = DATA_PLUGIN . 'demo/test_users.json';
            if (!is_dir(dirname($dest))) {
                @mkdir(dirname($dest), 0755, true);
            }
            if (file_exists($source)) {
                copy($source, $dest);
            }

            $sourceFixtures = PLUGINS . 'demo/asset/test_fixtures.json';
            $destFixtures = DATA_PLUGIN . 'demo/test_fixtures.json';
            if (file_exists($sourceFixtures)) {
                copy($sourceFixtures, $destFixtures);
            }
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

        $this->core->auth()->logout();

        Show::msg('Mode Démo désactivé.', 'info');
        return $this->core->redirect($this->router->generate('home'));
    }
}
