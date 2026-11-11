<?php

namespace SearchUI\Hooks;

use Core\Core;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

class SearchUIHooks
{
    public static function registerHooks(): void
    {
        // Hooks are now registered in hooks.json
    }

    public static function install(): void
    {
        // Création des dossiers de cache UI si nécessaire
    }

    public static function onEndFrontHead($param = ''): string
    {
        // Injection CSS de la barre de recherche expandable
        $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
        $cssTag = '<link rel="stylesheet" href="' . $siteUrl . '/plugin/SearchUi/assets/css/public.css?v=25" type="text/css"/>';
        return (string)$param . $cssTag;
    }

    public static function onBefore404(): void
    {
        // Gestion URLs courtes (ex: /mon-poi-123)
        // Logique à migrer depuis SearchHooks::onBefore404
    }

    /**
     * Hook poi:detail-actions
     * Affiche le bouton "Signaler" sur la page de détail
     */
    public static function onPoiDetailActions($poi): void
    {
        // Le bouton de signalement est géré directement dans detail.tpl
        // Suppression du doublon
    }

    /**
     * Barre d'outils Mode Test Admin
     */
    public static function onAfterHeader(): void
    {
        $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
        $plugin = $pluginsManager->getPlugin('SearchUi');
        $enableSimulation = $plugin->getConfigVal('enableSimulation') ?? '1';

        $testMode = \Core\Auth\AdminTestMode::getInstance();
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        
        $isActive = $testMode->isActive();
        $isAdmin = $user && $evaluator->isAdmin(new \Core\Navigation\ConditionContext($user));

        // Si la simulation est désactivée globalement, on ne montre rien SAUF pour l'admin
        // qui doit pouvoir la réactiver (si on veut un raccourci, mais ici on va suivre la config).
        if ($enableSimulation !== '1' && !$isActive) {
            return;
        }

        // On affiche la barre si :
        // 1. Le mode test est actif (on fait confiance au flag session car seul un admin a pu l'activer)
        // 2. OU si l'utilisateur est un admin (pour lui permettre d'activer le mode)
        if (!$isActive && !$isAdmin) {
            return;
        }

        $router = \Core\Router\Router::getInstance();
        $users = \Core\Auth\User::all();
        
        echo '<div id="admin-test-mode-bar" onmouseenter="this.style.height=\'60px\'; this.style.padding=\'10px 20px\'" onmouseleave="this.style.height=\'5px\'; this.style.padding=\'0 20px\'">';
        
        echo '<div class="admin-mode-label">';
        echo '<span class="admin-mode-badge">SIMULATION</span>';
        if ($isActive) {
            echo '<div class="admin-mode-status active"><i class="fa-solid fa-flask-vial"></i> Active</div>';
        } else {
            echo '<div class="admin-mode-status inactive"><i class="fa-solid fa-flask"></i> Inactive</div>';
        }
        echo '</div>';

        echo '<div class="admin-mode-controls">';
        
        // Bouton Activer/Désactiver
        $toggleUrl = $router->generate('SearchUi-api-admin-test-mode-toggle');
        $toggleLabel = $isActive ? 'Désactiver' : 'Activer';
        $toggleIcon = $isActive ? 'fa-power-off' : 'fa-play';
        $toggleBtnClass = $isActive ? 'btn-deactivate' : 'btn-activate';
        
        echo '<a href="' . $toggleUrl . '" class="admin-mode-toggle-btn ' . $toggleBtnClass . '">';
        echo '<i class="fa-solid ' . $toggleIcon . '"></i> ' . $toggleLabel;
        echo '</a>';

        if ($isActive) {
            echo '<form action="' . $router->generate('SearchUi-api-admin-test-mode-impersonate') . '" method="post" class="admin-mode-form">';
            echo '<label for="impersonate-user" class="admin-mode-label-text">Profil :</label>';
            echo '<select name="user_id" id="impersonate-user" onchange="this.form.submit()" class="admin-mode-select">';
            echo '<option value="0">--- Moi (Admin) ---</option>';
            
            // Test users standardized
            $testUsersFile = DATA_PLUGIN . 'demo/test_users.json';
            if (is_file($testUsersFile)) {
                $testUsersData = json_decode(file_get_contents($testUsersFile), true);
                if (is_array($testUsersData) && count($testUsersData) > 0) {
                    echo '<optgroup label="🧪 Profils Types (Mocks)">';
                    foreach ($testUsersData as $tu) {
                        $tuId = $tu['id'] ?? '';
                        $tuLabel = $tu['_test_label'] ?? ($tu['username'] ?? 'Test User');
                        $selected = ($testMode->getImpersonatedUserId() == $tuId) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($tuId) . '" ' . $selected . '>' . htmlspecialchars($tuLabel) . '</option>';
                    }
                    echo '</optgroup>';
                }
            }
            
            // Real users
            echo '<optgroup label="Utilisateurs réels">';
            foreach ($users as $u) {
                if (isset($_SESSION['email']) && $u->getEmail() === $_SESSION['email']) continue;
                $selected = ($testMode->getImpersonatedUserId() == $u->attributes['id']) ? 'selected' : '';
                $isAdminUser = $evaluator->isAdmin(new \Core\Navigation\ConditionContext($u)) ? ' [ADMIN]' : '';
                echo '<option value="' . $u->attributes['id'] . '" ' . $selected . '>' . htmlspecialchars($u->username()) . $isAdminUser . '</option>';
            }
            echo '</optgroup>';
            echo '</select>';
            echo '</form>';
        }

        echo '<div class="admin-mode-indicator">';
        echo '<i class="fa-solid fa-shield-halved"></i> <strong>Mode Volatil</strong>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        $tooltip = $isActive ? 'Simulation Active - Survolez pour changer de profil' : 'Administration - Survolez pour activer la simulation';
        echo '<div class="admin-mode-tooltip">' . $tooltip . '</div>';
    }

    /**
     * Hook SearchUi:header-bar
     * Affiche la barre de recherche unifiée dans le header
     */
    public static function onHeaderBar($param = ''): string
    {
        // Utilisation directe de la classe Template du Core
        $tpl = new \Template\Template(PLUGINS . 'SearchUi/template/header_bar.tpl');
        
        // On pourrait passer des paramètres ici (ex: query courante)
        $query = $_GET['q'] ?? '';
        $tpl->set('q', htmlspecialchars($query));
        
        // On retourne le contenu rendu
        return $tpl->output();
    }
}


