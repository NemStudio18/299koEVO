<?php

namespace PoiReviews {

use Core\Plugin\PluginsManager;
use Core\Auth\UsersManager;
use Core\Core;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
        $core   = Core::getInstance();
        $logger = $core->getLogger();
        $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-reviews::install] Début installation (création base SQLite)");

        $dbPath = DATA_PLUGIN . 'PoiReviews/database.sqlite3';
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-reviews::install] Création du répertoire '{$dbDir}'");
            @mkdir($dbDir, 0755, true);
        }
        
        $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-reviews::install] Ouverture de la base '{$dbPath}'");
        $db = new \PDO('sqlite:' . $dbPath);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Table reviews
        $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-reviews::install] Création / mise à jour table 'reviews'");
        $db->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            poi_id INTEGER NOT NULL,
            user_id TEXT NOT NULL,
            rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
            comment TEXT,
            status TEXT DEFAULT 'active' CHECK(status IN ('active', 'moderated', 'deleted')),
            moderated_by INTEGER,
            moderated_at DATETIME,
            moderation_reason TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Indexes
        $db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_poi ON reviews(poi_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_user ON reviews(user_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_status ON reviews(status)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_poi_status ON reviews(poi_id, status)");
        
        // Table pour éviter les doublons (un utilisateur ne peut noter qu'une fois par POI)
        $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_reviews_poi_user ON reviews(poi_id, user_id)");

        $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-reviews::install] Installation / migration terminée");
    }

    public static function endFrontHead($param = ''): string
    {
        $plugin = PluginsManager::getInstance()->getPlugin('PoiReviews');
        $output = (string)$param;
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return $output;
        }
        $core   = Core::getInstance();
        $logger = $core->getLogger();
        $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-reviews::endFrontHead] Injection du CSS public pour 'poi-reviews'");
        $output .= '<link rel="stylesheet" href="' . \Utils\Util::urlBuild(PLUGINS . 'PoiReviews/template/public.css') . '" type="text/css"/>' . "\n";
        return $output;
    }

    public static function userProfileTabs($user): string
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiReviews');
        if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
            return '';
        }
        
        return '
        <button type="button" class="profile-tab-button" role="tab" aria-selected="false" aria-controls="tab-reviews" id="tab-button-reviews" data-tab="reviews">
            <i class="fa-solid fa-star"></i> <span>Avis</span>
        </button>';
    }

    public static function userProfileTabsContent($user): string
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiReviews');
        if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
            return '';
        }

        $userId = (int)($user->attributes['id'] ?? $user->id ?? 0);
        if ($userId <= 0) {
            return '';
        }

        try {
            // Retrieve reviews by user
            // Since ReviewManager doesn't have getByUser, we use Review::query()
            $reviews = \PoiReviews\Entities\Review::query()
                ->where('user_id', '=', $userId)
                ->where('status', '=', 'active')
                ->sortBy('created_at', 'DESC')
                ->get();
            
            if (empty($reviews)) {
                return '<div class="profile-tab-content" id="tab-reviews" role="tabpanel" aria-labelledby="tab-button-reviews">
                    <div class="profile-empty-state">
                        <i class="fa-solid fa-star profile-empty-icon"></i>
                        <p class="profile-empty-text">Vous n\'avez pas encore laissé d\'avis.</p>
                    </div>
                </div>';
            }

            // Prepare review data with POI details
            $preparedReviews = [];
            $router = \Core\Router\Router::getInstance();

            foreach ($reviews as $review) {
                $poiId = $review->getPoiId();
                $poi = \PoiData\Entities\Poi::findPK($poiId);
                
                if ($poi) {
                    $preparedReviews[] = [
                        'review' => $review,
                        'poi_name' => $poi->name,
                        'poi_url' => $router->generate('poi-detail', ['id' => $poiId, 'slug' => \Utils\Util::strToUrl($poi->name)]),
                        'created_at_formatted' => \Utils\Util::getNaturalDate($review->getCreatedAt())
                    ];
                }
            }
            
            // Generate HTML manually for simplicity or use a template if consistent
            $html = '<div class="list-group">';
            foreach ($preparedReviews as $item) {
                $rating = $item['review']->getRating();
                $stars = '';
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $rating) {
                        $stars .= '<i class="fa-solid fa-star text-warning"></i>';
                    } else {
                        $stars .= '<i class="fa-regular fa-star text-muted"></i>';
                    }
                }

                $comment = nl2br(htmlspecialchars($item['review']->getComment() ?? ''));
                
                $html .= '
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><a href="' . $item['poi_url'] . '">' . htmlspecialchars($item['poi_name']) . '</a></h5>
                        <small class="text-muted">' . $item['created_at_formatted'] . '</small>
                    </div>
                    <div class="mb-2 text-warning">' . $stars . '</div>
                    <p class="mb-1">' . $comment . '</p>
                </div>';
            }
            $html .= '</div>';

            return '<div class="profile-tab-content" id="tab-reviews" role="tabpanel" aria-labelledby="tab-button-reviews">
                <h2 class="profile-subsection-title">Mes avis (' . count($reviews) . ')</h2>
                ' . $html . '
            </div>';
            
        } catch (\Exception $e) {
            return '<div class="profile-tab-content" id="tab-reviews" role="tabpanel" aria-labelledby="tab-button-reviews">
                <p style="text-align: center; padding: 2rem; color: var(--text-muted);">Impossible de charger vos avis.</p>
            </div>';
        }
    }
}

}

namespace {

function poiReviewsInstall(): void
{
    \PoiReviews\Hooks::install();
}

function poiReviewsBeforeRunPlugin(): void
{
    // Charger les entités
    foreach (glob(PLUGINS . 'PoiReviews/entities/' . '*.php') as $file) {
        require_once $file;
    }
    
    // Charger les services
    if (is_dir(PLUGINS . 'PoiReviews/services')) {
        foreach (glob(PLUGINS . 'PoiReviews/services/' . '*.php') as $file) {
            require_once $file;
        }
    }

    // Charger les routes explicitement (au cas où PluginsManager ne le ferait pas correctement pour ce plugin)
    if (file_exists(PLUGINS . 'PoiReviews/param/routes.php')) {
        require_once PLUGINS . 'PoiReviews/param/routes.php';
    }

    // Hooks are now declared in param/hooks.json
}

function poiReviewsEndFrontHead($param = ''): string
{
    return \PoiReviews\Hooks::endFrontHead($param);
}

/**
 * Hook: poi:get-reviews
 */
function poiReviewsGetReviews(array $data): array
{
    $poiId = (int)($data['poi_id'] ?? 0);
    if ($poiId <= 0) {
        return $data;
    }

    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiReviews');
        if ($plugin && $plugin->isInstalled() && $plugin->getConfigVal('activate')) {
            try {
                $reviewManager = new \PoiReviews\Entities\ReviewManager();
                
                $data['average_rating'] = $reviewManager->getAverageRating($poiId);
                $data['rating_count'] = $reviewManager->getRatingCount($poiId);
                
                $router = \Core\Router\Router::getInstance();
                $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
                $user = \Core\Auth\UsersManager::getCurrentUser();
                $ctx = new \Core\Navigation\ConditionContext($user, ['id' => $poiId, 'type' => 'poi']);

                try {
                    $data['reviews_url'] = $router->generate('poi-reviews-list', ['poi_id' => $poiId]);
                    
                    if ($evaluator->canAccess(new \Core\Navigation\GenericResource('poi:review:write'), $ctx)) {
                        $data['add_review_url'] = $router->generate('poi-reviews-add', ['poi_id' => $poiId]);
                    } else {
                        $data['add_review_url'] = null; // Policy denied
                    }
                } catch (\Exception $e) {
                    $data['reviews_url'] = "/poi-reviews/{$poiId}";
                    $data['add_review_url'] = null;
                }
            } catch (\Exception $e) {
                // Ignorer
            }
        }

    return $data;
}

/**
 * Hook: storage:migration:finished
 */
function poiReviewsOnStorageMigrationFinished(array $data): void
{
    // Valid Models: PoiReviews\Entities\Review or similar
    $model = $data['model'] ?? '';
    
    // Check if the migrated model is related to reviews
    if (strpos($model, 'Review') !== false || strpos($model, 'PoiReviews') !== false) {
        $core = \Core\Core::getInstance();
        $core->getLogger()->log(\Core\Logger::LEVEL_INFO, "[PoiReviews] Storage migration detected for $model. Triggering star sync.");
        
        try {
            $manager = new \PoiReviews\Entities\ReviewManager();
            $manager->syncAllStars();
        } catch (\Exception $e) {
            $core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "[PoiReviews] Star sync failed: " . $e->getMessage());
        }
    }
}


function poiReviewsUserProfileTabs($user): string
{
    return \PoiReviews\Hooks::userProfileTabs($user);
}

function poiReviewsUserProfileTabsContent($user): string
{
    return \PoiReviews\Hooks::userProfileTabsContent($user);
}

} // End namespace

