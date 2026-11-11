<?php
namespace BusinessDashboard\Controllers;

use Core\Controllers\Controller;
use Core\Database;
use Core\Auth\UsersManager;
use Core\Core;

class BusinessDashboardController extends Controller {

    public function index() {
        $user = UsersManager::getCurrentUser();
        
        // 1. Check Auth
        if (!$user) {
            $this->core->redirect('/login?redirect=/BusinessDashboard');
            return;
        }

        // 2. Get User's POIs (Owner or Claimed)
        // We assume 'poi' table has 'user_id' OR 'claims' table links user to poi.
        // The previous error showed 'user_id' column not found in 'poi'.
        // So we must use the 'claim' table.
        $db = Database::getInstance();
        $service = new \BusinessDashboard\Services\BusinessDashboardService();

        // 2. Check Addon Status
        if (!$service->isAddonActive($user->id)) {
            return $this->renderNoBusiness();
        }
        
        // 3. Get User's POIs (Owner or Claimed)
        $sql = "SELECT p.id, p.name 
                FROM poi p 
                JOIN poi_claims c ON p.id = c.poi_id 
                WHERE c.user_id = ? AND c.status = 'validated'";
        $pois = $db->query($sql, [$user->id]);
        
        if (empty($pois)) {
            return $this->renderNoBusiness(); // Or a specific "No POI" page
        }

        $poiIds = array_column($pois, 'id');
        
        // 4. Aggregate Stats (Last 30 days) via Service
        $stats = $service->getStats($poiIds);
        
        // 5. Check Plan for Blurring
        $accessService = new \PoiManager\Services\OwnerAccessService();
        $isFree = ($accessService->getPlan($user->id) === 'free');

        // 6. Render Dashboard
        $response = new \Core\Responses\PublicResponse();
        $tpl = $response->createPluginTemplate('BusinessDashboard', 'dashboard');
        
        $tpl->set('user', $user);
        $tpl->set('pois', $pois);
        $tpl->set('totalViews', $stats['total']['view'] ?? 0);
        $tpl->set('totalImpressions', $stats['total']['impression'] ?? 0);
        $tpl->set('dailyStats', json_encode($stats['daily'])); // For Chart.js
        $tpl->set('isFree', $isFree);
        
        $response->setTitle('Tableau de Bord Business');
        $response->addTemplate($tpl);
        
        return $response;
    }

    private function getStats($poiIds) {
        // Redundant with service, but keeping for compatibility if needed or marking as deprecated
        $service = new \BusinessDashboard\Services\BusinessDashboardService();
        return $service->getStats($poiIds);
    }

    private function renderNoBusiness() {
        $response = new \Core\Responses\PublicResponse();
        $tpl = $response->createPluginTemplate('BusinessDashboard', 'no_business');
        $response->setTitle('Espace Pro - Bienvenue');
        $response->addTemplate($tpl);
        return $response;
    }
}
?>
