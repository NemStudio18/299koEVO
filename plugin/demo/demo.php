<?php

namespace Demo {

    use Core\Core;
    use Core\Plugin\PluginsManager;
    use Core\Auth\AdminTestMode;
    use Core\Router\Router;

    defined('ROOT') or exit('Access denied!');

    class Hooks
    {
        /**
         * Hook pour injecter le bouton "Essayer la Démo" dans la navigation
         */
        /**
         * Hook pour injecter le bouton "Essayer la Démo" dans la navigation
         * UPDATE: Déplacé dans param/config.json pour gestion via Core
         */
        /*
        public static function endMainNavigation(): string
        {
            // ... Logic moved to config.json ...
            return '';
        }
        */

        /**
         * Hook pour afficher une bannière quand on est en mode démo (Front)
         */
        public static function adminAfterHeader(): void
        {
             echo '<!-- DEBUG: adminAfterHeader HOOK FIRED -->';
             $testMode = AdminTestMode::getInstance();
             \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_INFO, "Demo: adminAfterHeader called. IsActive: " . ($testMode->isActive() ? 'YES' : 'NO'));
             
             if ($testMode->isActive()) {
                // Admin-specific Banner
                echo '<style>
                    #admin-test-mode-bar { display: none !important; }
                    .demo-banner-admin {
                        background: #ff9800; 
                        color: white !important; 
                        text-align: center; 
                        padding: 10px; 
                        font-weight: bold; 
                        z-index: 999999; 
                        margin: 0;
                        border-radius: 0;
                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        display: block;
                        font-size: 14px;
                        line-height: normal;
                    }
                    /* Ajuster le header si nécessaire pour ne pas cacher le contenu */
                    #header { padding-top: 40px; }
                </style>';

                echo '<div class="demo-banner-admin">
                    <i class="fa-solid fa-circle-info"></i> MODE DÉMO ADMIN - <small>Les modifications ne sont pas sauvegardées.</small>
                    <a href="/demo/stop" style="color: white; text-decoration: underline; margin-left: 15px;">Quitter</a>
                </div>';
             }
        }

        public static function afterHeader(): void
        {
            $testMode = AdminTestMode::getInstance();
            if ($testMode->isActive()) {
                // Frontend Banner (unchanged logic)
                 echo '<style>
                    #banner { 
                        padding-top: 60px !important; 
                    } 
                    #admin-test-mode-bar { display: none !important; }
                    .demo-banner {
                        background: #ff9800; 
                        color: white; 
                        text-align: center; 
                        padding: 10px; 
                        font-weight: bold; 
                        z-index: 900;
                        position: fixed; 
                        top: 0;
                        left: 0; 
                        width: 100%;
                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                        transition: top 0.3s;
                    }
                </style>';
                
                echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    function adjustDemoBanner() {
                        const header = document.getElementById("header");
                        const banner = document.querySelector(".demo-banner");
                        if (header && banner) {
                            const headerHeight = header.offsetHeight;
                            banner.style.top = headerHeight + "px";
                        }
                    }
                    adjustDemoBanner();
                    window.addEventListener("resize", adjustDemoBanner);
                });
                </script>';

                echo '<div class="demo-banner">
                    <i class="fa-solid fa-circle-info"></i> Vous êtes en <strong>Mode Démo</strong>. Toute modification est fictive.
                    <a href="/demo/stop" style="color: white; text-decoration: underline; margin-left: 15px; background: rgba(0,0,0,0.2); padding: 2px 8px; border-radius: 4px;">Quitter la démo</a>
                </div>';
            }
        }

        /**
         * Hook: navigation:build
         * Injecte l'item de navigation basé sur infos.json ("L'autre méthode")
         */
        public static function buildNavigation(\Core\Navigation\NavigationTree $tree): void
        {
            // Ne pas afficher si on est déjà connecté
            $user = \Core\Auth\UsersManager::getCurrentUser();
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            $ctx = new \Core\Navigation\ConditionContext($user);
            if ($user !== null) {
                return;
            }

            $demoPlugin = PluginsManager::getInstance()->getPlugin('demo');
            if (!$demoPlugin) return;

            // Lecture depuis infos.json (et non config.json)
            $label = "Essayer la Démo"; // Libellé par défaut ou via Lang
            $icon = $demoPlugin->getInfoVal('icon') ?: 'fa-solid fa-flask';
            
            // L'URL doit pointer vers l'activation
            // Si homePublicMethod est défini, on pourrait l'utiliser, mais on force l'activation ici
            $url = '/demo/activate'; 

            try {
                \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_INFO, "Demo: Building nav item. Icon: $icon, Class: demo-nav-link");
                $item = new \Core\Navigation\NavigationItem(
                    'plugin.demo.main',
                    $label,
                    $url,
                    '_self',
                    'demo-nav-link', // Classe CSS définie dans template/public.css
                    $icon,
                    10,
                    null,
                    'plugin:demo'
                );
                $tree->addItem($item);
            } catch (\Exception $e) {
                \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "Demo: Error building nav: " . $e->getMessage());
            }
        }
    }
}

namespace {
    /**
     * Fonction d'installation du plugin
     */
    function demoInstall() {
        $sourceDir = PLUGINS . 'demo/asset/';
        $destDir = DATA_PLUGIN . 'demo/';

        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $files = ['test_users.json', 'test_fixtures.json'];
        foreach ($files as $file) {
            if (file_exists($sourceDir . $file)) {
                copy($sourceDir . $file, $destDir . $file);
            }
        }
    }
}


