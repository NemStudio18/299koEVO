<?php

namespace Meteo {

    use Core\Plugin\Plugin;
    use Core\Plugin\PluginsManager;

    defined('ROOT') or exit('No direct script access allowed');

    class Hooks
    {
        private const TEMPLATE = PLUGINS . 'meteo/template/widget.tpl';

        public static function install(): void
        {
            $dataDir = DATA_PLUGIN . 'meteo';
            if (!is_dir($dataDir)) {
                @mkdir($dataDir, 0755, true);
            }
            $cacheFile = $dataDir . '/locations.cache.json';
            if (!is_file($cacheFile)) {
                @file_put_contents($cacheFile, json_encode([]));
            }
        }

        public static function endFrontHead($param = ''): string
        {
            $plugin = self::getPlugin();
            $output = (string)$param;
            if (!$plugin) {
                return $output;
            }
            $cssPath = PLUGINS . 'meteo/template/public.css';
            if (file_exists($cssPath)) {
                $output .= '<link rel="stylesheet" href="' . \Utils\Util::urlBuild($cssPath) . '" type="text/css"/>' . "\n";
            }
            $compactCss = PLUGINS . 'meteo/template/widget-compact.css';
            $vCompact = file_exists($compactCss) ? filemtime($compactCss) : '1.0';
            $output .= '<link rel="stylesheet" href="' . \Utils\Util::urlBuild('plugin/meteo/template/widget-compact.css') . '?v=' . $vCompact . '" type="text/css"/>' . "\n";

            if (self::getPosition($plugin) === 'sidebar') {
                $html = self::renderWidgetHtml($plugin, 'sidebar');
                \Utils\Show::addSidebarPublicModule(
                    $plugin->getConfigVal('widgetTitle') ?: \Core\Lang::get('meteo.meteo'),
                    $html
                );
            }
            return $output;
        }

        public static function endMainNavigation(): void
        {
            $plugin = self::getPlugin();
            if (!$plugin || self::getPosition($plugin) !== 'header') {
                return;
            }
            echo '<li class="meteo-nav-widget">';
            echo self::renderWidgetHtml($plugin, 'header');
            echo '</li>';
        }

        public static function afterHeader(): void
        {
            self::renderForPosition('below_header');
        }

        public static function footer(): void
        {
            self::renderForPosition('footer');
        }

        public static function endFrontBody(): void
        {
            self::renderForPosition('bottom');
        }

        public static function renderWidget(string $position = 'manual'): void
        {
            $plugin = self::getPlugin();
            if (!$plugin) {
                return;
            }
            
            // Si position est 'header', utiliser le template compact
            if ($position === 'header' || $position === 'compact') {
                echo self::renderCompactWidgetHtml($plugin);
            } else {
                echo self::renderWidgetHtml($plugin, $position);
            }
        }

        private static function renderCompactWidgetHtml(Plugin $plugin): string
        {
            \Core\Lang::loadLanguageFile(PLUGINS . 'meteo' . DS . 'langs' . DS);

            $templateFile = PLUGINS . 'meteo/template/widget-compact.tpl';
            if (!is_file($templateFile)) {
                \Core\Core::getInstance()->getLogger()->log(
                    \Core\Logger::LEVEL_ERROR,
                    "Meteo: compact template file not found (" . $templateFile . ")"
                );
                return '';
            }

            $router = \Core\Router\Router::getInstance();
            $tpl = new \Template\Template($templateFile);
            $tpl->set('apiCurrentUrl', $router->generate('meteo-api-current'));
            $tpl->set('defaultCity', $plugin->getConfigVal('defaultCity') ?? '');
            $tpl->set('defaultCountry', $plugin->getConfigVal('defaultCountry') ?? '');
            $tpl->set('units', $plugin->getConfigVal('units') ?? 'metric');
            $tpl->set('language', $plugin->getConfigVal('language') ?? 'fr');
            $tpl->set('latitude', $plugin->getConfigVal('latitude') ?? '');
            $tpl->set('longitude', $plugin->getConfigVal('longitude') ?? '');

            return $tpl->output();
        }

        private static function renderForPosition(string $position): void
        {
            $plugin = self::getPlugin();
            if (!$plugin || self::getPosition($plugin) !== $position) {
                return;
            }
            echo self::renderWidgetHtml($plugin, $position);
        }

        private static function renderWidgetHtml(Plugin $plugin, string $position): string
        {
            \Core\Lang::loadLanguageFile(PLUGINS . 'meteo' . DS . 'langs' . DS);

            if (!is_file(self::TEMPLATE)) {
                \Core\Core::getInstance()->getLogger()->log(
                    \Core\Logger::LEVEL_ERROR,
                    "Meteo: template file not found (" . self::TEMPLATE . ")"
                );
                return '';
            }

            $router = \Core\Router\Router::getInstance();
            $tpl = new \Template\Template(self::TEMPLATE);
            $tpl->set('widgetTitle', $plugin->getConfigVal('widgetTitle') ?: \Core\Lang::get('meteo.meteo'));
            $showForecast = (bool) $plugin->getConfigVal('showForecast');
            $tpl->set('showForecast', $showForecast);
            $tpl->set('showForecastFlag', $showForecast ? '1' : '0');
            $tpl->set('apiCurrentUrl', $router->generate('meteo-api-current'));
            $tpl->set('apiForecastUrl', $router->generate('meteo-api-forecast'));
            $tpl->set('defaultCity', $plugin->getConfigVal('defaultCity') ?? '');
            $tpl->set('defaultCountry', $plugin->getConfigVal('defaultCountry') ?? '');
            $tpl->set('units', $plugin->getConfigVal('units') ?? 'metric');
            $tpl->set('language', $plugin->getConfigVal('language') ?? 'fr');
            $tpl->set('latitude', $plugin->getConfigVal('latitude') ?? '');
            $tpl->set('longitude', $plugin->getConfigVal('longitude') ?? '');
            $tpl->set('position', $position);
            $tpl->set('extraClass', self::getExtraClass($position));

            return $tpl->output();
        }

        private static function getExtraClass(string $position): string
        {
            return match ($position) {
                'header' => 'meteo-widget--nav',
                'below_header' => 'meteo-widget--below-header',
                'sidebar' => 'meteo-widget--sidebar',
                'footer' => 'meteo-widget--footer',
                'bottom' => 'meteo-widget--bottom',
                default => '',
            };
        }

        private static function getPlugin(): ?Plugin
        {
            static $plugin = null;
            if ($plugin === false) {
                return null;
            }
            if ($plugin === null) {
                $plugin = PluginsManager::getInstance()->getPlugin('meteo');
                if (!$plugin || !$plugin->getConfigVal('activate')) {
                    $plugin = false;
                    return null;
                }
            }
            return $plugin ?: null;
        }

        private static function getPosition(Plugin $plugin): string
        {
            $position = $plugin->getConfigVal('position');
            if (!$position) {
                return ((int) $plugin->getConfigVal('autoDisplay') === 1) ? 'bottom' : 'manual';
            }
            return $position;
        }

        /**
         * Hook: notifications:collect
         */
        public static function notificationsCollect(array $data): array
        {
            // Météo n'a pas de notifications
            return $data;
        }
    }

}

namespace {

    function meteoInstall(): void
    {
        \Meteo\Hooks::install();
    }

    function meteoEndFrontHead($param = ''): string
    {
        return \Meteo\Hooks::endFrontHead($param);
    }

    // Note: meteoEndMainNavigation est un widget, pas un item de navigation classique
    // Il peut rester tel quel ou être adapté selon les besoins
    function meteoEndMainNavigation(): void
    {
        \Meteo\Hooks::endMainNavigation();
    }

    // Hook pour enregistrer un item de navigation si nécessaire
    function meteoNavigationBuild($tree): void
    {
        // Le widget météo s'affiche via endMainNavigation
        // Si besoin d'un item de menu classique, l'ajouter ici
    }

    function meteoAfterHeader(): void
    {
        \Meteo\Hooks::afterHeader();
    }

    function meteoFooter(): void
    {
        \Meteo\Hooks::footer();
    }

    function meteoEndFrontBody(): void
    {
        \Meteo\Hooks::endFrontBody();
    }

    function meteoWidget(string $position = 'manual'): void
    {
        \Meteo\Hooks::renderWidget($position);
    }

    function meteoWidgetHeader(): void
    {
        \Meteo\Hooks::renderWidget('header');
    }

    /**
     * Hook: notifications:collect
     */
    function meteoNotificationsCollect(array $data): array
    {
        return \Meteo\Hooks::notificationsCollect($data);
    }
}
