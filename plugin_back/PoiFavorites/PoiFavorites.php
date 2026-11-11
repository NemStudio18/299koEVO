<?php

namespace PoiFavorites {

use Core\Plugin\PluginsManager;
use Core\Core;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
        $core   = Core::getInstance();
        $logger = $core->getLogger();
        $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-favorites::install] Début installation (création base SQLite)");

        $dbPath = DATA_PLUGIN . 'poi-favorites/database.sqlite3';
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-favorites::install] Création du répertoire '{$dbDir}'");
            @mkdir($dbDir, 0755, true);
        }
        
        $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-favorites::install] Ouverture de la base '{$dbPath}'");
        $db = new \PDO('sqlite:' . $dbPath);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Table favorites
        $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-favorites::install] Création / mise à jour table 'poi_favorites'");
        $db->exec("CREATE TABLE IF NOT EXISTS poi_favorites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            poi_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(poi_id, user_id)
        )");
        
        // Indexes
        $db->exec("CREATE INDEX IF NOT EXISTS idx_favorites_user ON poi_favorites(user_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_favorites_poi ON poi_favorites(poi_id)");


        $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-favorites::install] Installation / migration terminée");
    }

    public static function endFrontHead($param = ''): string
    {
        $plugin = PluginsManager::getInstance()->getPlugin('PoiFavorites');
        $output = (string)$param;

        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return $output;
        }
        $core   = Core::getInstance();
        $logger = $core->getLogger();
        $logger->log(\Core\Logger::LEVEL_DEBUG, "[poi-favorites::endFrontHead] Injection du CSS public pour 'poi-favorites'");
        $output .= '<link rel="stylesheet" href="' . \Utils\Util::urlBuild(PLUGINS . 'PoiFavorites/assets/css/public.css') . '" type="text/css"/>' . "\n";
        return $output;
    }

    public static function notificationsCollect(array $data): array
    {
        // Les notifications de favoris sont désormais gérées par le plugin global 'notifications'
        // via les hooks poi:updated et offer:activated.
        return $data;
    }
}

}

namespace {

function poiFavoritesInstall(): void
{
    \PoiFavorites\Hooks::install();
}

function poiFavoritesBeforeRunPlugin(): void
{
    // Plus besoin de charger manuellement, l'autoloader s'en occupe
}

function poiFavoritesEndFrontHead($param = ''): string
{
    return \PoiFavorites\Hooks::endFrontHead($param);
}

function poiFavoritesPoiUpdated(array $params): void
{
    $poiId = (int)($params['poi_id'] ?? 0);
    if ($poiId <= 0) return;
    
    try {
        $notificationService = new \PoiFavorites\Services\NotificationService();
        $notificationService->notifyPoiSubscribers($poiId, 'updated', [
            'poi_name' => $params['poi']['name'] ?? null
        ]);
    } catch (\Exception $e) {
        // Silencieux
    }
}

function poiFavoritesOfferActivated(array $params): void
{
    $poiId = (int)($params['poi_id'] ?? 0);
    if ($poiId <= 0) return;
    
    try {
        $notificationService = new \PoiFavorites\Services\NotificationService();
        $notificationService->notifyPoiSubscribers($poiId, 'offer_activated', [
            'poi_name' => $params['poi_name'] ?? null
        ]);
    } catch (\Exception $e) {
        // Silencieux
    }
}

    // Hooks are now declared in param/hooks.json

/**
 * Hook: notifications:collect
 */
function poiFavoritesNotificationsCollect(array $data): array
{
    return \PoiFavorites\Hooks::notificationsCollect($data);
}


/**
 * Hook: user:avatar-menu
 * Ajoute l'entrée "Mes favoris" dans le menu utilisateur
 */
function poiFavoritesUserAvatarMenu(array $data): array
{
    $items = $data['items'] ?? [];
    $user = \Core\Auth\UsersManager::getCurrentUser();
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiFavorites');
    
    if ($plugin && $plugin->isInstalled() && $plugin->getConfigVal('activate') && $user) {
        $router = \Core\Router\Router::getInstance();
        try {
            $url = $router->generate('profile') . '?tab=favorites';
        } catch (\Exception $e) {
            $url = '/profile?tab=favorites';
        }

        $items[] = [
            'label' => 'Mes favoris',
            'url' => $url,
            'icon' => 'fa-solid fa-heart',
            'order' => 30
        ];
    }
    $data['items'] = $items;
    return $data;
}

/**
 * Hook: user:notifications
 * Redondant avec le système global
 */
function poiFavoritesUserNotifications(array $data): array
{
    return $data;
}

/**
 * Hook: poi:get-user-favorite-status
 */
function poiFavoritesGetUserFavoriteStatus(array $data): array
{
    $poiId = (int)($data['poi_id'] ?? 0);
    $userId = (int)($data['user_id'] ?? 0);
    
    if ($poiId > 0 && $userId > 0) {
        try {
            $favoriteManager = new \PoiFavorites\Entities\FavoriteManager();
            $data['is_favorite'] = $favoriteManager->isFavorite($poiId, $userId);
        } catch (\Exception $e) {
            $data['is_favorite'] = false;
        }
    } else {
        $data['is_favorite'] = false;
    }
    
    return $data;
}

/**
 * Hook: user:profile-tabs
 */
function poiFavoritesUserProfileTabs($user): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiFavorites');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return '';
    }
    
    return '
    <button type="button" class="profile-tab-button" role="tab" aria-selected="false" aria-controls="tab-favorites" id="tab-button-favorites" data-tab="favorites">
        <i class="fa-solid fa-heart"></i> <span>Favoris</span>
    </button>';
}

/**
 * Hook: user:profile-tabs-content
 */
function poiFavoritesUserProfileTabsContent($user): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiFavorites');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return '';
    }

    $userId = (int)($user->attributes['id'] ?? $user->id ?? 0);
    if ($userId <= 0) {
        return '';
    }

    try {
        $favoriteManager = new \PoiFavorites\Entities\FavoriteManager();
        $favorites = $favoriteManager->getUserFavorites($userId);
        
        // Prepare favorites data with URLs and formatted dates
        $preparedFavorites = [];
        $router = \Core\Router\Router::getInstance();
        
        foreach ($favorites as $favorite) {
            $poiId = $favorite->getPoiId();
            // Use PoiData entity directly
            $poi = class_exists('\PoiData\Entities\Poi') ? \PoiData\Entities\Poi::findPK($poiId) : null;
            
            if ($poi) {
                $favData = [
                    'poi_id' => $poiId,
                    'poi_name' => $poi->name ?? '',
                    'poi_category' => $poi->type_label ?? ($poi->category ?? ''), // Fallback
                    'poi_subcategory' => $poi->subcategory ?? '',
                    'poi_address' => $poi->address ?? '',
                    'poi_city' => $poi->city ?? '',
                    'poi_description' => $poi->description ?? '',
                    'created_at_formatted' => \Utils\Util::getNaturalDate($favorite->getCreatedAt()),
                    'detail_url_final' => $router->generate('poi-detail', ['id' => $poiId, 'slug' => \Utils\Util::strToUrl($poi->name ?? 'poi')]),
                    'has_active_offers' => false, // Default
                    'offers_count' => 0
                ];

                // Simple check for offers if plugin exists
                if (\Core\Plugin\PluginsManager::isActivePlugin('offers')) {
                    $offerManager = new \Offers\Entities\OfferManager();
                    $offers = $offerManager->getByPoi($poiId, true);
                    $favData['has_active_offers'] = !empty($offers);
                    $favData['offers_count'] = count($offers);
                }

                $preparedFavorites[] = $favData;
            }
        }
        
        $tpl = new \Template\Template(PLUGINS . 'PoiFavorites/template/my-favorites.tpl');
        $tpl->set('favorites', $preparedFavorites);
        $tpl->set('csrfToken', \Core\Security\Csrf::token());
        $tpl->set('ROUTER', $router);
        
        $output = $tpl->output();
        
        // Remove the <h1> from the template for profile tab integration
        $output = preg_replace('/<h1>.*?<\/h1>/si', '', $output);

        return '<div class="profile-tab-content" id="tab-favorites" role="tabpanel" aria-labelledby="tab-button-favorites">' . $output . '</div>';
        
    } catch (\Exception $e) {
        return '<div class="profile-tab-content" id="tab-favorites" role="tabpanel" aria-labelledby="tab-button-favorites">
            <p style="text-align: center; padding: 2rem; color: var(--text-muted);">Impossible de charger vos favoris.</p>
        </div>';
    }
}


}
