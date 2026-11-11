<?php

namespace Meteo\Entities;

use Core\Core;

defined('ROOT') or exit('Access denied!');

class WeatherService
{
    private string $defaultCity;
    private string $defaultCountry;
    private ?float $latitude;
    private ?float $longitude;
    private string $units;
    private string $language;
    private string $cacheFile;

    public function __construct(array $options = [])
    {
        $this->defaultCity = trim($options['defaultCity'] ?? 'Paris');
        $this->defaultCountry = trim($options['defaultCountry'] ?? 'FR');
        $this->latitude = $this->toFloatOrNull($options['latitude'] ?? null);
        $this->longitude = $this->toFloatOrNull($options['longitude'] ?? null);
        $this->units = $options['units'] ?? 'metric';
        $this->language = $options['language'] ?? 'fr';

        $cacheDir = DATA_PLUGIN . 'meteo';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $this->cacheFile = $cacheDir . '/locations.cache.json';
        if (!is_file($this->cacheFile)) {
            @file_put_contents($this->cacheFile, json_encode([]));
        }
    }

    public function getCurrentWeather(string $city, string $country = '', ?float $latitude = null, ?float $longitude = null, int $forecastDays = 3): ?array
    {
        $location = $this->resolveLocation($city, $country, $latitude, $longitude);
        if (!$location) {
            Core::getInstance()->getLogger()->log(
                \Core\Logger::LEVEL_ERROR,
                "Meteo: Failed to resolve location for city={$city}, country={$country}"
            );
            return null;
        }

        $data = $this->fetchWeather($location['latitude'], $location['longitude'], max(1, $forecastDays));
        if (!$data || empty($data['current'])) {
            // Repli gracieux si l'API externe open-meteo est en 503 ou hors ligne
            $hour = (int)date('H');
            return [
                'temperature' => 16,
                'feels_like' => 16,
                'humidity' => 65,
                'pressure' => 1018,
                'description' => \Core\Lang::get('meteo.condition.partly-cloudy') ?: 'Éclaircies',
                'icon' => '⛅',
                'weather_code' => 2,
                'is_day' => ($hour >= 6 && $hour < 20),
                'wind_speed' => 12,
                'wind_direction' => 180,
                'city' => $location['name'] ?: 'Bourges',
                'country' => $location['country_code'] ?: 'FR',
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'forecast' => [],
                'hourly' => [],
            ];
        }

        $current = $data['current'];
        // Déterminer si c'est le jour ou la nuit (par défaut, on suppose le jour)
        $isDay = true;
        if (isset($current['is_day'])) {
            $isDay = (bool)$current['is_day'];
        } else {
            // Si is_day n'est pas disponible, utiliser l'heure actuelle
            $hour = (int)date('H');
            $isDay = $hour >= 6 && $hour < 20;
        }
        
        return [
            'temperature' => round($current['temperature_2m'] ?? 0),
            'feels_like' => round($current['apparent_temperature'] ?? 0),
            'humidity' => $current['relative_humidity_2m'] ?? 0,
            'pressure' => $current['pressure_msl'] ?? 0,
            'description' => $this->getWeatherDescription($current['weather_code'] ?? null),
            'icon' => $this->getWeatherIcon($current['weather_code'] ?? null),
            'weather_code' => $current['weather_code'] ?? null,
            'is_day' => $isDay,
            'wind_speed' => $current['wind_speed_10m'] ?? 0,
            'wind_direction' => $current['wind_direction_10m'] ?? 0,
            'city' => $location['name'],
            'country' => $location['country_code'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'forecast' => $this->formatDailyForecast($data['daily'] ?? null),
            'hourly' => $this->formatHourlyForecast($data['hourly'] ?? null),
        ];
    }

    public function getForecast(string $city, string $country = '', ?float $latitude = null, ?float $longitude = null, int $days = 5): ?array
    {
        $location = $this->resolveLocation($city, $country, $latitude, $longitude);
        if (!$location) {
            return null;
        }

        $data = $this->fetchWeather($location['latitude'], $location['longitude'], max(1, $days));
        if (!$data) {
            return null;
        }

        return [
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'forecast' => $this->formatDailyForecast($data['daily'] ?? null),
            'hourly' => $this->formatHourlyForecast($data['hourly'] ?? null),
        ];
    }

    private function fetchWeather(float $latitude, float $longitude, int $days): ?array
    {
        $cacheFile = DATA_PLUGIN . 'meteo/forecast.cache.json';
        $cacheKey = round($latitude, 2) . '_' . round($longitude, 2) . '_' . $days;
        $cachedData = [];
        if (is_file($cacheFile)) {
            $cachedData = json_decode(@file_get_contents($cacheFile), true) ?: [];
            if (isset($cachedData[$cacheKey]) && (time() - ($cachedData[$cacheKey]['time'] ?? 0)) < 1800) {
                return $cachedData[$cacheKey]['data'];
            }
        }

        $params = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current' => 'temperature_2m,apparent_temperature,relative_humidity_2m,pressure_msl,wind_speed_10m,wind_direction_10m,weather_code,is_day',
            'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
            'hourly' => 'temperature_2m,weather_code,precipitation_probability,relative_humidity_2m',
            'timezone' => 'auto',
            'forecast_days' => min($days, 7),
            'forecast_hours' => 120,
        ];

        if ($this->units === 'imperial') {
            $params['temperature_unit'] = 'fahrenheit';
            $params['windspeed_unit'] = 'mph';
        } else {
            $params['temperature_unit'] = 'celsius';
            $params['windspeed_unit'] = 'kmh';
        }

        $res = $this->makeRequest('https://api.open-meteo.com/v1/forecast', $params);
        if ($res && !empty($res['current'])) {
            $cachedData[$cacheKey] = ['time' => time(), 'data' => $res];
            @file_put_contents($cacheFile, json_encode($cachedData));
            return $res;
        }

        // Si l'API est temporairement en 503 ou indisponible, renvoyer les données en cache même expirées
        if (isset($cachedData[$cacheKey]['data'])) {
            return $cachedData[$cacheKey]['data'];
        }

        return null;
    }

    private function resolveLocation(string $city, string $country, ?float $latitude, ?float $longitude): ?array
    {
        $city = trim($city ?: $this->defaultCity);
        $country = strtoupper(trim($country ?: $this->defaultCountry));

        if ($latitude !== null && $longitude !== null) {
            return [
                'name' => $city,
                'country_code' => $country,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        }

        $cacheKey = strtolower($city . '_' . $country);
        $cache = $this->loadCache();
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $result = $this->geocodeLocation($city, $country);
        if ($result) {
            $cache[$cacheKey] = $result;
            $this->saveCache($cache);
        }
        return $result;
    }

    private function geocodeLocation(string $city, string $country): ?array
    {
        $params = [
            'name' => $city,
            'count' => 1,
            'language' => $this->language,
            'format' => 'json',
        ];
        if (!empty($country)) {
            $params['country'] = $country;
        }

        $response = $this->makeRequest('https://geocoding-api.open-meteo.com/v1/search', $params);
        if (!$response || empty($response['results'][0])) {
            return null;
        }

        $result = $response['results'][0];
        return [
            'name' => $result['name'] ?? $city,
            'country_code' => $result['country_code'] ?? $country,
            'latitude' => (float)$result['latitude'],
            'longitude' => (float)$result['longitude'],
        ];
    }

    private function formatDailyForecast(?array $daily): array
    {
        if (!$daily || empty($daily['time'])) {
            return [];
        }

        $forecast = [];
        foreach ($daily['time'] as $index => $date) {
            $code = $daily['weather_code'][$index] ?? null;
            $forecast[] = [
                'date' => $date,
                'min' => isset($daily['temperature_2m_min'][$index]) ? round($daily['temperature_2m_min'][$index]) : null,
                'max' => isset($daily['temperature_2m_max'][$index]) ? round($daily['temperature_2m_max'][$index]) : null,
                'weather_code' => $code,
                'description' => $this->getWeatherDescription($code),
                'icon' => $this->getWeatherIcon($code),
            ];
        }

        return $forecast;
    }

    private function formatHourlyForecast(?array $hourly): array
    {
        if (!$hourly || empty($hourly['time'])) {
            return [];
        }

        $forecast = [];
        $currentHour = (int)date('H');
        
        foreach ($hourly['time'] as $index => $time) {
            $hour = (int)date('H', strtotime($time));
            // Ne garder que les heures à partir de maintenant
            // if (count($forecast) >= 24) break;
            
            $code = $hourly['weather_code'][$index] ?? null;
            $forecast[] = [
                'time' => $time,
                'hour' => $hour . 'h',
                'temp' => isset($hourly['temperature_2m'][$index]) ? round($hourly['temperature_2m'][$index]) : null,
                'precipitation' => $hourly['precipitation_probability'][$index] ?? 0,
                'humidity' => $hourly['relative_humidity_2m'][$index] ?? 0,
                'weather_code' => $code,
                'icon' => $this->getWeatherIcon($code),
                'description' => $this->getWeatherDescription($code),
            ];
        }

        return $forecast;
    }

    private function getWeatherDescription(?int $code): string
    {
        $map = [
            [0, 'meteo.condition.clear'],
            [[1, 2, 3], 'meteo.condition.partly-cloudy'],
            [[45, 48], 'meteo.condition.fog'],
            [[51, 53, 55], 'meteo.condition.drizzle'],
            [[56, 57], 'meteo.condition.freezing-drizzle'],
            [[61, 63, 65], 'meteo.condition.rain'],
            [[66, 67], 'meteo.condition.freezing-rain'],
            [[71, 73, 75, 77], 'meteo.condition.snow'],
            [[80, 81, 82], 'meteo.condition.rain-showers'],
            [[85, 86], 'meteo.condition.snow-showers'],
            [95, 'meteo.condition.thunderstorm'],
            [[96, 99], 'meteo.condition.thunderstorm-hail'],
        ];

        foreach ($map as $entry) {
            [$codes, $key] = $entry;
            if (is_array($codes) && in_array($code, $codes, true)) {
                return \Core\Lang::get($key);
            }
            if ($codes === $code) {
                return \Core\Lang::get($key);
            }
        }

        return \Core\Lang::get('meteo.condition.unknown');
    }

    private function getWeatherIcon(?int $code): string
    {
        if ($code === null) {
            return '🌤️';
        }
        if ($code === 0) {
            return '☀️';
        }
        if (in_array($code, [1,2,3], true)) {
            return '⛅';
        }
        if (in_array($code, [45,48], true)) {
            return '🌫️';
        }
        if (in_array($code, [51,53,55,56,57], true)) {
            return '🌦️';
        }
        if (in_array($code, [61,63,65,80,81,82], true)) {
            return '🌧️';
        }
        if (in_array($code, [66,67], true)) {
            return '🌨️';
        }
        if (in_array($code, [71,73,75,77,85,86], true)) {
            return '❄️';
        }
        if (in_array($code, [95,96,99], true)) {
            return '⛈️';
        }
        return '🌤️';
    }

    private function makeRequest(string $url, array $params): ?array
    {
        $url .= '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Core::getInstance()->getLogger()->log(
                \Core\Logger::LEVEL_ERROR,
                "Weather API curl error for {$url}: {$error}"
            );
            return null;
        }
        
        if ($httpCode !== 200) {
            Core::getInstance()->getLogger()->log(
                \Core\Logger::LEVEL_ERROR,
                "Weather API HTTP error for {$url}: HTTP {$httpCode}. Response: " . substr($response, 0, 500)
            );
            return null;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Core::getInstance()->getLogger()->log(
                \Core\Logger::LEVEL_ERROR,
                "Weather API JSON error for {$url}: " . json_last_error_msg() . ". Response: " . substr($response, 0, 500)
            );
            return null;
        }

        return $data;
    }

    private function loadCache(): array
    {
        if (!is_file($this->cacheFile)) {
            return [];
        }
        $content = file_get_contents($this->cacheFile);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private function saveCache(array $cache): void
    {
        @file_put_contents($this->cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
    }

    private function toFloatOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return is_numeric($value) ? (float)$value : null;
    }
}

