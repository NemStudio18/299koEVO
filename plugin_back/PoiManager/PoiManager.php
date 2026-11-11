<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin PoiManager
 *
 * Gestion complète des fiches POI :
 *  - Admin : CRUD direct sur poi_public
 *  - Membre : proposal (nouveau POI) ou suggestion (correction), soumis à validation admin
 *  - Propriétaire : édition directe de ses fiches (limité par abonnement)
 *
 * Note : la table poi_search est synchronisée automatiquement via triggers MySQL.
 * Ce plugin écrit uniquement dans poi_public et les tables annexes.
 */

// =========================================================
// CHARGEMENT DES CLASSES (hook obligatoire)
// =========================================================

function poiManagerBeforeRunPlugin(): void
{
    foreach (glob(PLUGINS . 'PoiManager/entities/*.php') as $file) {
        require_once $file;
    }
    foreach (glob(PLUGINS . 'PoiManager/services/*.php') as $file) {
        require_once $file;
    }
    foreach (glob(PLUGINS . 'PoiManager/controllers/*.php') as $file) {
        require_once $file;
    }
    
    // Invalider le cache de plan lors d'une mise à jour d'abonnement
    \Core\Core::getInstance()->addHook('subscription:updated', '\PoiManager\Services\OwnerAccessService::clearCache');
}

// =========================================================
// INJECTION CSS/JS FRONT
// =========================================================

function poiManagerEndFrontHead($param = ''): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiManager');
    if (!$plugin || !$plugin->getConfigVal('activate')) {
        return (string)$param;
    }

    $baseUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');
    $output  = (string)$param;

    // CSS public (pages front)
    $cssFile = PLUGINS . 'PoiManager/template/public.css';
    if (file_exists($cssFile)) {
        $output .= '<link rel="stylesheet" href="' . $baseUrl . '/plugin/PoiManager/template/public.css" media="all">' . "\n";
    }

    // CSS admin (pages admin)
    // CSS admin (pages admin)
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($uri, '/admin/')) {
        $adminCss = PLUGINS . 'PoiManager/template/admin.css';
        if (file_exists($adminCss)) {
            $output .= '<link rel="stylesheet" href="' . $baseUrl . '/plugin/PoiManager/template/admin.css" media="all">' . "\n";
        }
    }

    $output .= poiManagerInjectEditAssets($baseUrl, $uri);

    return $output;
}

function poiManagerEndAdminHead($param = ''): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiManager');
    if (!$plugin || !$plugin->getConfigVal('activate')) {
        return (string)$param;
    }

    $baseUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');
    $output  = (string)$param;
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    $output .= poiManagerInjectEditAssets($baseUrl, $uri);

    return $output;
}

/**
 * Fonction helper pour injecter les scripts de carte et d'éditeur sur les pages d'édition (front et admin)
 */
function poiManagerInjectEditAssets(string $baseUrl, string $uri): string
{
    $output = '';
    $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
    $isEditPage = (str_contains($uri, '/admin/PoiManager/edit') || str_contains($uri, '/admin/PoiManager/create') || str_contains($uri, '/PoiManager/edit'));

    if ($isEditPage) {
        if (\Core\Core::getInstance()->callHook('map:is-enabled')) {
            $output .= \Core\Core::getInstance()->callHook('map:load-assets');
        }

        // Injection de TinyMCE si actif
        if ($pluginsManager && $pluginsManager->isActivePlugin('tinymce')) {
            $user = \Core\Auth\UsersManager::getCurrentUser();
            $token = $user->token ?? '';
            $uploadUrl = \Core\Router\Router::getInstance()->generate('filemanager-upload-api', ['token' => $token]);
            $url = \Utils\Util::urlBuild(PLUGINS . 'tinymce/lib/tinymce/tinymce.min.js');
            $locale = \Core\Lang::getLocale();
            $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');

            $options = "language: '{$locale}',
                toolbar_mode: 'wrap',
                images_upload_url: '{$uploadUrl}',
                automatic_uploads: true,
                convert_urls: false,
                document_base_url : '{$siteUrl}/',
                browser_spellcheck: true,
                image_advtab: true,
                plugins: 'advlist anchor autolink autoresize charmap code codesample emoticons fullscreen help image insertdatetime link lists media nonbreaking searchreplace table visualblocks visualchars wordcount',
                toolbar: 'blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | forecolor backcolor removeformat | insertfile image media link | dialog-add-icon charmap emoticons codesample | fullscreen',
                max_height: 600,
                image_caption: true,
                content_css: [
                    '" . \Utils\Util::urlBuild(PLUGINS . 'tinymce/template/editor.css') . "',
                    '" . FONTICON . "',
                ]";

            $output .= "<script src='{$url}'></script>\n";
            $output .= "<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: 'textarea.editor',
            {$options}
        });
    }
});
</script>\n";
        }
    }

    return $output;
}

// =========================================================
// HOOKS SEARCHUI — BOUTONS CONTEXTUELS SUR LES FICHES
// =========================================================

/**
 * Hook poi:card-actions
 * Injecte des boutons sur les cartes de résultats SearchUI.
 *
 * @param  array $data  Données du POI + utilisateur courant
 * @return string       HTML des boutons à injecter
 */
function poiManagerPoiCardActions($data): string
{
    // Les boutons d'action sont désormais gérés de manière centralisée
    // par le template SearchUi et ConversionOrchestrator.
    return '';
}

/**
 * Hook poi:detail-actions
 * Injecte des boutons sur la fiche de détail SearchUI.
 *
 * @param  array $data  Données du POI + utilisateur courant
 * @return string       HTML des boutons à injecter
 */
function poiManagerPoiDetailActions($data): string
{
    // Les boutons de détail sont désormais gérés par le template detail.tpl
    // (bloc admin/manager) et par le ConversionOrchestrator (côté visiteurs).
    return '';
}

// =========================================================
// HELPERS INTERNES
// =========================================================

/**
 * Vérifie si l'utilisateur est administrateur.
 */
function poiManagerIsAdmin($user): bool
{
    if (!$user) return false;
    $role = $user->attributes['role'] ?? $user->role ?? '';
    return $role === 'admin';
}

/**
 * Vérifie si l'utilisateur est propriétaire du POI (claimed_by).
 */
function poiManagerIsOwner($user, int $poiId): bool
{
    if (!$user || !$poiId) return false;
    $userId = (int)($user->attributes['id'] ?? 0);
    if (!$userId) return false;

    // Déléguer au hook entity:ownership-verify (plugin claim)
    $result = \Core\Core::getInstance()->callHook('entity:ownership-verify', [
        'entity_type' => 'poi',
        'entity_id'   => $poiId,
        'user_id'     => $userId,
        'email'       => $user->attributes['email'] ?? null,
        'username'    => $user->attributes['username'] ?? null,
    ]);

    return ($result === true);
}

/**
 * Hook: user:avatar-menu
 */
function poiManagerUserAvatarMenu(array $data): array
{
    $router = \Core\Router\Router::getInstance();
    try {
        $url = $router->generate('poimanager-propose');
    } catch (\Exception $e) {
        $url = '/PoiManager/propose';
    }

    $data['items'][] = [
        'icon' => 'fa-solid fa-plus',
        'label' => 'Participer',
        'url' => $url,
        'order' => 50
    ];

    return $data;
}
