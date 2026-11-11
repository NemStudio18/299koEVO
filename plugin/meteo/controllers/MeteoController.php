<?php

namespace Meteo\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Responses\ApiResponse;
use Core\Plugin\PluginsManager;

defined('ROOT') or exit('Access denied!');

class MeteoController extends PublicController
{
    protected bool $enforceCsrf = false; // Désactiver CSRF pour les API publiques
    
    private function getWeatherService(): ?\Meteo\Entities\WeatherService
    {
        $plugin = PluginsManager::getInstance()->getPlugin('meteo');
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return null;
        }

        // Charger la classe WeatherService si elle n'est pas déjà chargée
        if (!class_exists('\Meteo\Entities\WeatherService', false)) {
            $weatherServiceFile = PLUGINS . 'meteo/entities/WeatherService.php';
            if (file_exists($weatherServiceFile)) {
                require_once $weatherServiceFile;
            } else {
                $this->core->getLogger()->log(
                    \Core\Logger::LEVEL_ERROR,
                    "Meteo: WeatherService file not found at {$weatherServiceFile}"
                );
                return null;
            }
        }

        if (!class_exists('\Meteo\Entities\WeatherService')) {
            $this->core->getLogger()->log(
                \Core\Logger::LEVEL_ERROR,
                "Meteo: WeatherService class not found after require"
            );
            return null;
        }

        return new \Meteo\Entities\WeatherService([
            'defaultCity' => $plugin->getConfigVal('defaultCity') ?? 'Paris',
            'defaultCountry' => $plugin->getConfigVal('defaultCountry') ?? 'FR',
            'latitude' => $plugin->getConfigVal('latitude'),
            'longitude' => $plugin->getConfigVal('longitude'),
            'units' => $plugin->getConfigVal('units') ?? 'metric',
            'language' => $plugin->getConfigVal('language') ?? 'fr',
        ]);
    }

    public function index()
    {
        $response = new PublicResponse();
        $plugin = PluginsManager::getInstance()->getPlugin('meteo');
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return $this->core->redirect('/');
        }

        $tpl = $response->createPluginTemplate('meteo', 'forecast');
        $router = \Core\Router\Router::getInstance();

        $tpl->set('widgetTitle', $plugin->getConfigVal('widgetTitle') ?: \Core\Lang::get('meteo.meteo'));
        $tpl->set('apiCurrentUrl', $router->generate('meteo-api-current'));
        $tpl->set('apiForecastUrl', $router->generate('meteo-api-forecast'));
        $tpl->set('showForecast', true);
        $tpl->set('showForecastFlag', '1');
        $tpl->set('defaultCity', $plugin->getConfigVal('defaultCity') ?? 'Paris');
        $tpl->set('defaultCountry', $plugin->getConfigVal('defaultCountry') ?? 'FR');
        $tpl->set('units', $plugin->getConfigVal('units') ?? 'metric');
        $tpl->set('language', $plugin->getConfigVal('language') ?? 'fr');
        $tpl->set('latitude', $plugin->getConfigVal('latitude') ?? '');
        $tpl->set('longitude', $plugin->getConfigVal('longitude') ?? '');
        $tpl->set('extraClass', '');

        $response->setTitle('Prévisions météo');
        $response->addTemplate($tpl);
        return $response;
    }

    public function apiCurrent()
    {
        $response = new ApiResponse();
        
        $service = $this->getWeatherService();
        if (!$service) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = [
                'status' => 'error',
                'message' => \Core\Lang::get('meteo.api-not-configured')
            ];
            return $response;
        }

        $city = $_GET['city'] ?? '';
        $country = $_GET['country'] ?? '';
        $latitude = isset($_GET['latitude']) ? (float)$_GET['latitude'] : null;
        $longitude = isset($_GET['longitude']) ? (float)$_GET['longitude'] : null;
        
        if (empty($city)) {
            $plugin = PluginsManager::getInstance()->getPlugin('meteo');
            $city = $plugin->getConfigVal('defaultCity') ?? 'Paris';
            $country = $plugin->getConfigVal('defaultCountry') ?? 'FR';
            if ($latitude === null) {
                $latitude = $this->toFloatOrNull($plugin->getConfigVal('latitude'));
            }
            if ($longitude === null) {
                $longitude = $this->toFloatOrNull($plugin->getConfigVal('longitude'));
            }
        }

        $days = 1;
        $plugin = PluginsManager::getInstance()->getPlugin('meteo');
        if ($plugin && (int)$plugin->getConfigVal('showForecast') === 1) {
            $days = 5;
        }

        try {
            $weather = $service->getCurrentWeather($city, $country, $latitude, $longitude, $days);
            
            if ($weather) {
                $response->status = ApiResponse::STATUS_SUCCESS;
                $response->body = [
                    'status' => 'success',
                    'data' => $weather
                ];
            } else {
                $response->status = ApiResponse::STATUS_BAD_REQUEST;
                $response->body = [
                    'status' => 'error',
                    'message' => \Core\Lang::get('meteo.api-error')
                ];
                $this->core->getLogger()->log(
                    \Core\Logger::LEVEL_ERROR,
                    "Meteo API: getCurrentWeather returned null for city={$city}, country={$country}"
                );
            }
        } catch (\Exception $e) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = [
                'status' => 'error',
                'message' => \Core\Lang::get('meteo.api-error')
            ];
            $this->core->getLogger()->log(
                \Core\Logger::LEVEL_ERROR,
                "Meteo API Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()
            );
        } catch (\Throwable $e) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = [
                'status' => 'error',
                'message' => 'Internal server error'
            ];
            $this->core->getLogger()->log(
                \Core\Logger::LEVEL_ERROR,
                "Meteo API Fatal: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()
            );
        }

        return $response;
    }

    public function apiForecast()
    {
        // Désactiver l'affichage des erreurs PHP pour éviter le HTML dans la réponse JSON
        error_reporting(0);
        ini_set('display_errors', 0);
        
        $response = new ApiResponse();
        
        try {
            $service = $this->getWeatherService();
            if (!$service) {
                $response->status = ApiResponse::STATUS_BAD_REQUEST;
                $response->body = [
                    'status' => 'error',
                    'message' => \Core\Lang::get('meteo.api-not-configured')
                ];
                return $response;
            }

        $city = $_GET['city'] ?? '';
        $country = $_GET['country'] ?? '';
        $latitude = isset($_GET['latitude']) ? (float)$_GET['latitude'] : null;
        $longitude = isset($_GET['longitude']) ? (float)$_GET['longitude'] : null;
        $days = (int)($_GET['days'] ?? 5);
        
        if (empty($city)) {
            $plugin = PluginsManager::getInstance()->getPlugin('meteo');
            $city = $plugin->getConfigVal('defaultCity') ?? 'Paris';
            $country = $plugin->getConfigVal('defaultCountry') ?? 'FR';
            if ($latitude === null) {
                $latitude = $this->toFloatOrNull($plugin->getConfigVal('latitude'));
            }
            if ($longitude === null) {
                $longitude = $this->toFloatOrNull($plugin->getConfigVal('longitude'));
            }
        }

            $forecast = $service->getForecast($city, $country, $latitude, $longitude, $days);
            
            if ($forecast) {
                $response->status = ApiResponse::STATUS_SUCCESS;
                $response->body = [
                    'status' => 'success',
                    'data' => $forecast
                ];
            } else {
                $response->status = ApiResponse::STATUS_BAD_REQUEST;
                $response->body = [
                    'status' => 'error',
                    'message' => \Core\Lang::get('meteo.api-error')
                ];
            }
        } catch (\Exception $e) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = [
                'status' => 'error',
                'message' => \Core\Lang::get('meteo.api-error')
            ];
            $this->core->getLogger()->log(
                \Core\Logger::LEVEL_ERROR,
                "Meteo Forecast API Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()
            );
        } catch (\Throwable $e) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = [
                'status' => 'error',
                'message' => 'Internal server error'
            ];
            $this->core->getLogger()->log(
                \Core\Logger::LEVEL_ERROR,
                "Meteo Forecast API Fatal: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()
            );
        }

        return $response;
    }

    private function toFloatOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return is_numeric($value) ? (float)$value : null;
    }
}

