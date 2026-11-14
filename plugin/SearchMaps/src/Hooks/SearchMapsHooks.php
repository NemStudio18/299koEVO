<?php

namespace SearchMaps\Hooks;

use Core\Core;
use Core\Logger;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class SearchMapsHooks
{
    private static bool $assetsLoaded = false;

    public static function registerHooks(): void
    {
        if (self::isEnabled() && class_exists('\Content\ContentParser')) {
            \Content\ContentParser::addShortcode('map', [self::class, 'mapShortcode']);
        }
    }

    public static function onBeforeRunPlugin(): void
    {
        if (self::isEnabled() && class_exists('\Content\ContentParser')) {
            \Content\ContentParser::addShortcode('map', [self::class, 'mapShortcode']);
        }
    }

    /**
     * Retourne la configuration par défaut sauvegardée pour SearchMaps.
     */
    public static function getDefaults(): array
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('SearchMaps');
        $cfg = ($plugin && method_exists($plugin, 'getConfig')) ? ($plugin->getConfig() ?: []) : [];
        return [
            'lat'   => isset($cfg['center_lat']) && is_numeric($cfg['center_lat']) ? (float) $cfg['center_lat'] : 47.0833,
            'lon'   => isset($cfg['center_lon']) && is_numeric($cfg['center_lon']) ? (float) $cfg['center_lon'] : 2.4,
            'zoom'  => isset($cfg['zoom_default']) && (int) $cfg['zoom_default'] > 0 ? (int) $cfg['zoom_default'] : 13,
            'debug' => !empty($cfg['debug']),
        ];
    }

    /**
     * Expose les réglages de carte aux éditeurs (ex: bouton TinyMCE) sans couplage fort.
     */
    public static function getEditorConfig($config = []): array
    {
        $result = is_array($config) ? $config : [];
        if (!self::isEnabled()) {
            return $result;
        }
        $defaults = self::getDefaults();
        return array_merge($result, [
            'enabled'    => true,
            'lat'        => $defaults['lat'],
            'lon'        => $defaults['lon'],
            'zoom'       => $defaults['zoom'],
            'leafletCss' => Util::urlBuild('plugin/SearchMaps/assets/vendor/leaflet.css?v=11'),
            'leafletJs'  => Util::urlBuild('plugin/SearchMaps/assets/vendor/leaflet.js?v=11'),
        ]);
    }

    /**
     * Shortcode universel : [map lat="47.0810" lon="2.3986" zoom="14" title="Mon lieu" height="360px"]
     */
    public static function mapShortcode(array $attributes = []): string
    {
        if (!self::isEnabled()) {
            return '';
        }

        $defaults = self::getDefaults();
        $id = !empty($attributes['id'])
            ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $attributes['id'])
            : 'searchmaps_' . substr(md5(uniqid('', true)), 0, 8);

        $lat = isset($attributes['lat']) && is_numeric($attributes['lat']) ? (float) $attributes['lat'] : $defaults['lat'];
        $lon = isset($attributes['lon']) && is_numeric($attributes['lon']) ? (float) $attributes['lon'] : $defaults['lon'];
        $zoom = isset($attributes['zoom']) && is_numeric($attributes['zoom']) ? max(1, min(21, (int) $attributes['zoom'])) : $defaults['zoom'];
        $title = isset($attributes['title']) ? trim((string) $attributes['title']) : '';
        $height = isset($attributes['height']) ? trim((string) $attributes['height']) : '';
        $poisAttr = $attributes['pois'] ?? null;
        $categoryAttr = $attributes['category'] ?? null;

        $markers = [];
        $fitBounds = false;

        if ($poisAttr !== null || $categoryAttr !== null) {
            $allPois = \SearchMaps\Services\PoiManager::getAll();
            foreach ($allPois as $poi) {
                if ($categoryAttr && mb_strtolower($poi['category'] ?? '') !== mb_strtolower($categoryAttr)) {
                    continue;
                }
                $markers[] = [
                    'lat' => (float)$poi['lat'],
                    'lon' => (float)$poi['lon'],
                    'title' => $poi['title'] ?? '',
                    'popup' => '<strong>' . htmlspecialchars($poi['title'] ?? '') . '</strong><br>' .
                               '<small>' . htmlspecialchars($poi['category'] ?? '') . '</small>' .
                               (!empty($poi['address']) ? '<br><small>' . htmlspecialchars($poi['address']) . '</small>' : '') .
                               (!empty($poi['description']) ? '<p style="margin:4px 0 0;font-size:12px;">' . htmlspecialchars($poi['description']) . '</p>' : '')
                ];
            }
            if (!empty($markers)) {
                $fitBounds = true;
            }
        }

        return self::renderMap([
            'id'        => $id,
            'lat'       => $lat,
            'lon'       => $lon,
            'zoom'      => $zoom,
            'title'     => $title,
            'height'    => $height,
            'markers'   => $markers,
            'fitBounds' => $fitBounds,
        ]);
    }

    public static function isEnabled($param = null): bool
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('SearchMaps');
        return $plugin && $plugin->isInstalled() && (bool)$plugin->getConfigVal('activate');
    }

    public static function install(): void
    {
    }

    public static function loadAssets(): string
    {
        if (self::$assetsLoaded) {
            return '';
        }

        $output = '<link rel="stylesheet" href="' . Util::urlBuild('plugin/SearchMaps/assets/vendor/leaflet.css?v=11') . '">' . "\n";
        $output .= '<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" crossorigin="">' . "\n";
        $output .= '<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" crossorigin="">' . "\n";
        $output .= '<script src="' . Util::urlBuild('plugin/SearchMaps/assets/vendor/leaflet.js?v=11') . '" defer></script>' . "\n";
        $output .= '<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js" crossorigin="" defer></script>' . "\n";
        $output .= '<script src="' . Util::urlBuild('plugin/SearchMaps/assets/js/universal-map.js?v=11') . '" defer></script>' . "\n";
        
        self::$assetsLoaded = true;
        return $output;
    }

    public static function onEndFrontHead($param = ''): string
    {
        if (!self::isEnabled()) {
            return (string)$param;
        }

        $output = (string)$param;
        $output .= self::loadAssets();
        $output .= '<link rel="stylesheet" href="' . Util::urlBuild('plugin/SearchMaps/assets/css/search-maps.css?v=11') . '">' . "\n";
        
        // Inject Global Geolocation (only on front)
        if ((!defined('ADMIN_MODE') || !ADMIN_MODE) && (!defined('IS_ADMIN') || !IS_ADMIN)) {
            $output .= '<script src="' . Util::urlBuild('plugin/SearchMaps/js/geolocation.js?v=12') . '" defer></script>' . "\n";
        }

        return $output;
    }

    /**
     * Helper to render a map container and initialize it via UniversalMap
     * Params: id, lat, lon, zoom, editable, onChange, markers, fitBounds
     */
    public static function renderMap($params = []): string
    {
        if (!is_array($params)) {
            $params = [];
        }
        if (!self::isEnabled()) {
            return '';
        }

        $defaults = self::getDefaults();
        $id = $params['id'] ?? 'map';
        $lat = isset($params['lat']) && is_numeric($params['lat']) ? (float)$params['lat'] : $defaults['lat'];
        $lon = isset($params['lon']) && is_numeric($params['lon']) ? (float)$params['lon'] : $defaults['lon'];
        $zoom = isset($params['zoom']) && is_numeric($params['zoom']) ? (int)$params['zoom'] : $defaults['zoom'];
        $editable = !empty($params['editable']);
        $onChange = $params['onChange'] ?? 'null';
        $markers = $params['markers'] ?? [];
        $fitBounds = !empty($params['fitBounds']);
        $title = isset($params['title']) ? trim((string)$params['title']) : '';
        $heightStyle = '';
        if (!empty($params['height']) && preg_match('/^\d+(?:px|vh|rem|em|%)$/i', trim((string)$params['height']))) {
            $heightStyle = ' style="height:' . htmlspecialchars(trim((string)$params['height']), ENT_QUOTES, 'UTF-8') . ';"';
        } elseif (!empty($params['height']) && ctype_digit((string)$params['height'])) {
            $heightStyle = ' style="height:' . (int)$params['height'] . 'px;"';
        }

        $html = self::loadAssets();
        $html .= '<div id="' . htmlspecialchars($id) . '" class="universal-map-container"' . $heightStyle . '></div>';
        $html .= '<script>';
        $html .= 'document.addEventListener("DOMContentLoaded", function() {';
        $html .= '  if (typeof UniversalMap === "undefined") return;';
        if ($defaults['debug']) {
            $html .= '  console.debug("[SearchMaps] Init map", ' . json_encode(['id' => $id, 'lat' => $lat, 'lon' => $lon, 'zoom' => $zoom]) . ');';
        }
        $html .= '  const mapInstance = new UniversalMap("' . htmlspecialchars($id) . '", {';
        $html .= '    lat: ' . $lat . ',';
        $html .= '    lon: ' . $lon . ',';
        $html .= '    zoom: ' . $zoom . ',';
        $html .= '    editable: ' . ($editable ? 'true' : 'false') . ',';
        $html .= '    onChange: ' . $onChange;
        $html .= '  });';
        if ($title !== '') {
            $safeTitle = json_encode(htmlspecialchars($title, ENT_QUOTES, 'UTF-8'), JSON_UNESCAPED_UNICODE);
            $html .= '  if (mapInstance && mapInstance.marker) { mapInstance.marker.bindPopup(' . $safeTitle . '); }';
        }

        if (!empty($markers) && is_array($markers)) {
            $markersJson = json_encode($markers);
            $html .= '  const markersData = ' . $markersJson . ';';
            $html .= '  if (Array.isArray(markersData)) {';
            $html .= '    markersData.forEach(function(m) {';
            $html .= '      if (m.lat && m.lon) {';
            $html .= '        mapInstance.addMarker(parseFloat(m.lat), parseFloat(m.lon), m.popupHtml || m.title || "", {';
            $html .= '          markerFamily: m.family || "other",';
            $html .= '          markerIcon: m.icon || "location-dot"';
            $html .= '        });';
            $html .= '      }';
            $html .= '    });';
            if ($fitBounds) {
                $html .= '    mapInstance.fitMarkers();';
            }
            $html .= '  }';
        }

        $html .= '});';
        $html .= '</script>';

        return $html;
    }
}



