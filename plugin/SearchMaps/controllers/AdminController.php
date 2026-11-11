<?php

namespace SearchMaps\Controllers;

use Core\Controllers\AdminController as BaseAdminController;
use Core\Responses\AdminResponse;

class AdminController extends BaseAdminController
{
    public function home()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('SearchMaps', 'param');
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Return a static map image for a given lat/lon
     * Route: /searchmaps/static?lat=X&lon=Y&zoom=15&width=300&height=200
     */
    public function staticMap()
    {
        $lat = $_GET['lat'] ?? 47.0810;
        $lon = $_GET['lon'] ?? 2.3986;
        $zoom = $_GET['zoom'] ?? 15;
        $width = $_GET['width'] ?? 300;
        $height = $_GET['height'] ?? 200;

        // Use OpenFreeMap as a reliable no-key OSM-based static map service
        // Note: OpenFreeMap uses [longitude, latitude] order
        $url = "https://static.openfreemap.org/static?style=bright&width={$width}&height={$height}&center={$lon},{$lat}&zoom={$zoom}&markers=[{\"lon\":{$lon},\"lat\":{$lat}}]";
        
        // Fallback or legacy comment removed for clarity
        
        // Redirect to the external service
        header("Location: $url");
        exit;
    }
}
