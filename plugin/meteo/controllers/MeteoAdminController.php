<?php

namespace Meteo\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;
use Core\Plugin\PluginsManager;

defined('ROOT') or exit('Access denied!');

class MeteoAdminController extends AdminController
{
    public function home()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('meteo', 'admin-home');
        
        $plugin = PluginsManager::getInstance()->getPlugin('meteo');
        
        $tpl->set('defaultCity', $plugin->getConfigVal('defaultCity') ?? 'Paris');
        $tpl->set('defaultCountry', $plugin->getConfigVal('defaultCountry') ?? 'FR');
        $tpl->set('latitude', $plugin->getConfigVal('latitude') ?? '');
        $tpl->set('longitude', $plugin->getConfigVal('longitude') ?? '');
        $tpl->set('units', $plugin->getConfigVal('units') ?? 'metric');
        $tpl->set('language', $plugin->getConfigVal('language') ?? 'fr');
        $tpl->set('showForecast', (bool)$plugin->getConfigVal('showForecast'));
        $tpl->set('position', $this->resolvePosition($plugin));
        $tpl->set('widgetTitle', $plugin->getConfigVal('widgetTitle') ?? 'Météo');
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function save()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('meteo:admin'), $ctx)) {
            Show::msg(Lang::get('core.not-authorized'), 'error');
            return $this->core->redirect('/admin/meteo');
        }
        
        $csrfToken = $_POST['_csrf'] ?? null;
        if ($csrfToken === null || !\Core\Security\Csrf::validate($csrfToken)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/meteo');
        }
        
        $plugin = PluginsManager::getInstance()->getPlugin('meteo');
        
        $config = [
            'defaultCity' => trim($_POST['default_city'] ?? 'Paris'),
            'defaultCountry' => strtoupper(trim($_POST['default_country'] ?? 'FR')),
            'latitude' => $this->sanitizeCoordinate($_POST['latitude'] ?? ''),
            'longitude' => $this->sanitizeCoordinate($_POST['longitude'] ?? ''),
            'units' => $_POST['units'] ?? 'metric',
            'language' => $_POST['language'] ?? 'fr',
            'showForecast' => isset($_POST['show_forecast']) ? 1 : 0,
            'widgetTitle' => trim($_POST['widget_title'] ?? 'Météo'),
            'position' => $this->sanitizePosition($_POST['position'] ?? 'manual'),
        ];
        
        $pluginsManager = PluginsManager::getInstance();
        foreach ($config as $key => $value) {
            $plugin->setConfigVal($key, $value);
        }
        $pluginsManager->savePluginConfig($plugin);
        
        Show::msg(Lang::get('meteo.config-saved'), 'success');
        return $this->core->redirect('/admin/meteo');
    }

    private function resolvePosition($plugin): string
    {
        $position = $plugin->getConfigVal('position');
        if ($position) {
            return $position;
        }
        return ((int)$plugin->getConfigVal('autoDisplay') === 1) ? 'bottom' : 'manual';
    }

    private function sanitizeCoordinate($value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        return is_numeric($value) ? (string)$value : '';
    }

    private function sanitizePosition(string $position): string
    {
        $allowed = ['manual', 'header', 'below_header', 'sidebar', 'footer', 'bottom'];
        return in_array($position, $allowed, true) ? $position : 'manual';
    }
}

