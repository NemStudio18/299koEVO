<?php
namespace BusinessDashboard;

use Core\Core;
use Core\Database;

class DataCollector {

    /**
     * Track a POI View (hook: poi:show)
     */
    public static function trackView($args) {
        $poiId = $args['poi_id'] ?? null;
        $user = $args['user'] ?? null;
        
        if (!$poiId) return;
        
        self::recordEvent($poiId, 'view', $user ? $user->id : null);
    }

    /**
     * Track Search Impressions (hook: search:results-rendered)
     */
    public static function trackImpressions($args) {
        $results = $args['results'] ?? [];
        $query = $args['query'] ?? '';
        $user = $args['user'] ?? null;
        
        if (empty($results)) return;
        
        // Use a transaction or batch insert if possible, but for simplicity loop here
        // (Optimization: In high traffic, this should be buffered/queued)
        foreach ($results as $poi) {
            $poiId = $poi['id'] ?? null;
            if ($poiId) {
                self::recordEvent($poiId, 'impression', $user ? $user->id : null, ['query' => $query]);
            }
        }
    }

    /**
     * Helper to insert event into DB
     */
    private static function recordEvent($poiId, $eventType, $userId = null, $metadata = []) {
        $db = Database::getInstance();
        
        $sql = "INSERT INTO poi_analytics (poi_id, event_type, user_id, metadata) VALUES (?, ?, ?, ?)";
        
        try {
            $metadataJson = !empty($metadata) ? json_encode($metadata) : null;
            $db->query($sql, [$poiId, $eventType, $userId, $metadataJson]);
        } catch (\Exception $e) {
            // Silently fail logging to avoid breaking the page
            error_log("Analytics Error: " . $e->getMessage());
        }
    }
}
?>
