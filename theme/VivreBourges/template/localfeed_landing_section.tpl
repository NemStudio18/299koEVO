<section class="bourges-live-section" id="vie-locale">
    <div class="bourges-live-container">
        <div class="bourges-live-header">
            <div class="bourges-live-badge">
                <i class="fa-solid fa-bolt-lightning text-gold"></i> En direct de Bourges
            </div>
            <h2 class="bourges-live-title">La Vie Locale en Direct</h2>
            <p class="bourges-live-subtitle">Sorties imminentes, actualités des commerces, bons plans et météo réunis au même endroit.</p>
        </div>

        <div class="bourges-live-grid localfeed-stream">
            <?php 
                $isLandingPreview = true;
                $timeline = $previewTimeline ?? ['items' => []];
                include ROOT . 'plugin/LocalFeed/template/feed_items.tpl';
            ?>
        </div>

        <div class="bourges-live-footer">
            <a href="/mur" class="bourges-live-all-btn">
                <i class="fa-solid fa-layer-group"></i> Découvrir tout le fil de la vie locale <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
<link rel="stylesheet" href="/plugin/LocalFeed/public/css/localfeed.css?v=<?= @filemtime(ROOT . 'plugin/LocalFeed/public/css/localfeed.css') ?>" />
<script src="/plugin/LocalFeed/public/js/localfeed.js?v=<?= @filemtime(ROOT . 'plugin/LocalFeed/public/js/localfeed.js') ?>" defer></script>
