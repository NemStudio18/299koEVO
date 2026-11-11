<?php
// Gateway Theme - Logic
// Implements Smart Search and Hero Zone Orchestration

$core = \Core\Core::getInstance();

/**
 * Hook: layout:header-search
 * Logic: Display legacy search ONLY on non-POI pages (Blog, Agenda, etc.)
 */
$core->addHook('layout:header-search', function() use ($core) {
    $currentPlugin = $core->getPluginToCall();
    
    // Define the "POI Ecosystem" where legacy search should be HIDDEN
    // SearchUi is the new entry point, so we don't want the old search there.
    $poiPlugins = [
        'SearchUi', 
        'SearchMaps', 
        'PoiEdit', 
        'PoiData', 
        'QrPoi', 
        'PoiFavorites', 
        'PoiReviews',
        'claim',
        'offers', 
        'subscription' // Business pages also don't need general search
    ];
    
    // Check if we are inside the POI ecosystem
    $isPoiContext = in_array($currentPlugin, $poiPlugins);
    
    if (!$isPoiContext) {
        // We are on a Content page (Blog, Agenda, Forum, etc.)
        // Display the Legacy Search discretely
        echo '<div class="smart-search-container">';
        // Trigger the legacy hook that the 'search' plugin listens to
        $core->callHook('headerSearch', 'header');
        echo '</div>';
    }
});

/**
 * Hook: layout:hero-zone
 * Logic: The new dedicated zone for Hero content (Meteo, CTA, Dashboard)
 * ConversionOrchestrator will prioritize what to show here.
 */
$core->addHook('layout:hero-zone', function() use ($core) {
    echo '<div id="hero-zone">';
    // We allow plugins to inject content here via a sub-hook or direct listener
    // This replaces the chaotic 'afterHeader'
    $core->callHook('layout:hero-content');
    echo '</div>';
});
?>
