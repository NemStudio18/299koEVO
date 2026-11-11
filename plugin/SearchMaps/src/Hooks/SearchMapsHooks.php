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
        // Hooks are now declared in param/hooks.json
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
        if (!defined('IS_ADMIN') || !IS_ADMIN) {
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

        $id = $params['id'] ?? 'map';
        $lat = (float)($params['lat'] ?? 47.0810);
        $lon = (float)($params['lon'] ?? 2.3986);
        $zoom = (int)($params['zoom'] ?? 13);
        $editable = !empty($params['editable']);
        $onChange = $params['onChange'] ?? 'null';
        $markers = $params['markers'] ?? [];
        $fitBounds = !empty($params['fitBounds']);

        $html = self::loadAssets();
        $html .= '<div id="' . htmlspecialchars($id) . '" class="universal-map-container"></div>';
        $html .= '<script>';
        $html .= 'document.addEventListener("DOMContentLoaded", function() {';
        $html .= '  if (typeof UniversalMap === "undefined") return;';
        $html .= '  const mapInstance = new UniversalMap("' . htmlspecialchars($id) . '", {';
        $html .= '    lat: ' . $lat . ',';
        $html .= '    lon: ' . $lon . ',';
        $html .= '    zoom: ' . $zoom . ',';
        $html .= '    editable: ' . ($editable ? 'true' : 'false') . ',';
        $html .= '    onChange: ' . $onChange;
        $html .= '  });';

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



