<?php

namespace Utils;

use Core\Core;
use Core\Theme;
use Core\Plugin\PluginsManager;
use Core\Extensions\ExtensionsService;
use Core\Settings\SettingsService;
use Core\Media\MediaService;
use Core\Auth\UsersManager;
use Core\Lang;
use Utils\Util;

/**
 * @copyright (C) 2022, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

class Show
{

    /**
     * @var array Modules
     */
    protected static $sidebarPublicModules = [];

    /**
     * Add a message to display in the next view, saved in session
     * 
     * Class can be error, success, info (default), warning
     *
     * @param string Message Content
     * @param string Class Message
     */
    public static function msg($content, $class = 'info')
    {
        if (function_exists('msg')) {
            call_user_func('msg', $content, $class);
            return;
        }

        if (!isset($_SESSION['flash_msg']) || !is_array($_SESSION['flash_msg'])) {
            $_SESSION['flash_msg'] = [];
        }
        $_SESSION['flash_msg'][] = [
            'class' => $class,
            'content' => $content
        ];
    }

    /**
     * Vérifie s'il y a des messages flash en attente
     */
    public static function hasMsg(): bool
    {
        return !empty($_SESSION['flash_msg']) && is_array($_SESSION['flash_msg']);
    }

    /**
     * Display all messages added with 'msg' method, who were saved in session
     */
    public static function displayMsg()
    {
        if (function_exists('displayMsg')) {
            return call_user_func('displayMsg');
        }
        if (!isset($_SESSION['flash_msg']) || !is_array($_SESSION['flash_msg']) || empty($_SESSION['flash_msg'])) {
            return;
        }
        foreach ($_SESSION['flash_msg'] as $msg) {
            $class = htmlspecialchars($msg['class'] ?? 'info');
            $content = $msg['content'] ?? '';
            echo '<div class="msg ' . $class . '"><p>' . $content . '</p><a href="javascript:" class="msg-button-close"><i class="fa-solid fa-xmark"></i></a></div>';
        }
        unset($_SESSION['flash_msg']);
    }

    private static function isAdminAssetsMode(): bool
    {
        return defined('ADMIN_MODE') && \ADMIN_MODE && !Core::getInstance()->isForcePublicRender();
    }

    ## Affiche les balises "link" type css (admin + theme)

    public static function linkTags()
    {
        if (function_exists('linkTags'))
            call_user_func('linkTags');
        else {
            $core = Core::getInstance();
            $theme = new Theme($core->getConfigVal('theme'));
            $pluginsManager = PluginsManager::getInstance();
            $activePlugin = $core->getPluginToCall();
            $isAdminAssets = self::isAdminAssetsMode();
            $coreCss = 'common/css/evo-core.css';
            if (file_exists(ROOT . $coreCss)) {
                $ver = '?v=' . filemtime(ROOT . $coreCss);
                echo '<link href="' . Util::urlBuild($coreCss) . $ver . '" rel="stylesheet" type="text/css" />';
            }
            $tokensCss = 'common/css/ui-tokens.css';
            if (file_exists(ROOT . $tokensCss)) {
                $ver = '?v=' . filemtime(ROOT . $tokensCss);
                echo '<link href="' . Util::urlBuild($tokensCss) . $ver . '" rel="stylesheet" type="text/css" />';
            }
            foreach ($core->getCss() as $k => $v) {
                $ver = file_exists($v) ? ('?v=' . filemtime($v)) : '';
                if (!$isAdminAssets) {
                    echo '<link href="' . Util::urlBuild($v) . $ver . '" rel="stylesheet" type="text/css" media="print" onload="this.media=\'all\'" /><noscript><link href="' . Util::urlBuild($v) . $ver . '" rel="stylesheet" type="text/css" /></noscript>';
                } else {
                    echo '<link href="' . Util::urlBuild($v) . $ver . '" rel="stylesheet" type="text/css" />';
                }
            }
            foreach ($pluginsManager->getPlugins() as $k => $plugin)
                if ($plugin->getConfigval('activate') == 1) {
                    if (!$isAdminAssets && $plugin->getConfigVal('activate') && $plugin->getPublicCssFile()) {
                        $cssFile = $plugin->getPublicCssFile();
                        if (file_exists($cssFile)) {
                            $ver = '?v=' . filemtime($cssFile);
                            $pName = $plugin->getName();
                            $isCritical = ($activePlugin && strcasecmp($activePlugin, $pName) === 0)
                                       || in_array(strtolower($pName), ['weatherheader', 'localfeed']);
                            if ($isCritical) {
                                echo '<link href="' . Util::urlBuild($cssFile) . $ver . '" rel="stylesheet" type="text/css" />';
                            } else {
                                echo '<link href="' . Util::urlBuild($cssFile) . $ver . '" rel="stylesheet" type="text/css" media="print" onload="this.media=\'all\'" /><noscript><link href="' . Util::urlBuild($cssFile) . $ver . '" rel="stylesheet" type="text/css" /></noscript>';
                            }
                        }
                    } elseif ($isAdminAssets && $plugin->getConfigVal('activate') && $plugin->getAdminCssFile()) {
                        $cssFile = $plugin->getAdminCssFile();
                        $ver = file_exists($cssFile) ? ('?v=' . filemtime($cssFile)) : '';
                        echo '<link href="' . $plugin->getAdminCssFile() . $ver . '" rel="stylesheet" type="text/css" />';
                    }
                }
            if ($isAdminAssets) {
                foreach (Core::getInstance()->extensions()->getAdminCssUrls() as $cssUrl) {
                    echo '<link href="' . $cssUrl . '" rel="stylesheet" type="text/css" />';
                }
                foreach (Core::getInstance()->settings()->getAdminCssUrls() as $cssUrl) {
                    echo '<link href="' . $cssUrl . '" rel="stylesheet" type="text/css" />';
                }
                foreach (Core::getInstance()->media()->getAdminCssUrls() as $cssUrl) {
                    echo '<link href="' . $cssUrl . '" rel="stylesheet" type="text/css" />';
                }
            }
            if (!$isAdminAssets)
                echo $theme->getCSSLinks();
        }
    }

    ## Affiche les balises "script" type javascript (admin + theme)

    public static function scriptTags()
    {
        if (function_exists('scriptTags'))
            call_user_func('scriptTags');
        else {
            $core = Core::getInstance();
            $theme = new Theme($core->getConfigVal('theme'));
            $pluginsManager = PluginsManager::getInstance();
            $isAdminAssets = self::isAdminAssetsMode();
            $isFront = !$isAdminAssets;
            $deferAttr = $isFront ? ' defer' : '';
            foreach ($core->getJs() as $k => $v) {
                $ver = file_exists($v) ? ('?v=' . filemtime($v)) : '';
                echo '<script type="text/javascript" src="' . Util::urlBuild($v) . $ver . '"' . $deferAttr . '></script>';
            }
            foreach ($pluginsManager->getPlugins() as $k => $plugin)
                if ($plugin->getConfigval('activate') == 1) {
                    if (!$isAdminAssets && $plugin->getConfigVal('activate') && $plugin->getPublicJsFile()) {
                        $jsFile = $plugin->getPublicJsFile();
                        if (file_exists($jsFile)) {
                            $ver = '?v=' . filemtime($jsFile);
                            echo '<script type="text/javascript" src="' . Util::urlBuild($jsFile) . $ver . '" defer></script>';
                        }
                    } elseif ($isAdminAssets && $plugin->getConfigVal('activate') && $plugin->getAdminJsFile()) {
                        $jsFile = $plugin->getAdminJsFile();
                        $ver = file_exists($jsFile) ? ('?v=' . filemtime($jsFile)) : '';
                        echo '<script type="text/javascript" src="' . $plugin->getAdminJsFile() . $ver . '"></script>';
                    }
                }
            if ($isAdminAssets) {
                foreach (Core::getInstance()->extensions()->getAdminJsUrls() as $jsUrl) {
                    echo '<script type="text/javascript" src="' . $jsUrl . '"></script>';
                }
                foreach (Core::getInstance()->settings()->getAdminJsUrls() as $jsUrl) {
                    echo '<script type="text/javascript" src="' . $jsUrl . '"></script>';
                }
                foreach (Core::getInstance()->media()->getAdminJsUrls() as $jsUrl) {
                    echo '<script type="text/javascript" src="' . $jsUrl . '"></script>';
                }
            }
            if (!$isAdminAssets)
                echo $theme->getJSLinks();
        }
    }

    public static function showMetas()
    {
        $str = '';
        $core = Core::getInstance();
        foreach ($core->getMetas() as $meta) {
            $str .= $meta . "\n";
        }
        echo $str;
    }

    ## Affiche un champ de formulaire contenant le jeton CSRF

    public static function tokenField()
    {
        echo \Core\Security\Csrf::hiddenField();
    }

    ## Affiche le contenu de la meta title (theme)

    public static function titleTag()
    {
        if (function_exists('titleTag'))
            call_user_func('titleTag');
        else {
            $core = Core::getInstance();
            global $runPlugin;
            $title = $core->getTitleTag();
            if ($title === null && $runPlugin) {
                $title = $runPlugin->getTitleTag();
            }
            if ($title === null || $title === '') {
                $title = \Core\Lang::get('core-404-title');
            }
            echo $title . ' - ' . $core->getConfigVal('siteName');
        }
    }

    ## Affiche le contenu de la meta description (theme)

    public static function metaDescriptionTag()
    {
        if (function_exists('metaDescriptionTag')) {
            call_user_func('metaDescriptionTag');
        } else {
            $core = Core::getInstance();
            global $runPlugin;
            $description = $core->getMetaDescription();
            if (($description === null || trim((string)$description) === '') && $runPlugin) {
                $description = $runPlugin->getMetaDescriptionTag();
            }
            if ($description === null || trim((string)$description) === '') {
                $description = (string)$core->getConfigVal('siteDesc');
            }
            $cleanDescription = trim(preg_replace('/\s+/', ' ', strip_tags((string)$description)));
            echo htmlspecialchars($cleanDescription, ENT_QUOTES, 'UTF-8');
        }
    }

    ## Affiche le titre de page (theme)

    public static function mainTitle($format = '<h1>[mainTitle]</h1>')
    {
        if (function_exists('mainTitle'))
            call_user_func('mainTitle', $format);
        else {
            $core = Core::getInstance();
            global $runPlugin;
            $data = $format;
            $mainTitle = $core->getMainTitle();
            if ($mainTitle === null && $runPlugin) {
                $mainTitle = $runPlugin->getMainTitle();
            }
            if ($mainTitle === null || $mainTitle === '') {
                $mainTitle = \Core\Lang::get('core-404-title');
            }
            if ($core->getConfigVal('hideTitles') == 0 && $mainTitle != '') {
                $data = str_replace('[mainTitle]', $mainTitle, $format);
            } else {
                $data = '';
            }
            echo $data;
        }
    }

    ## Affiche le nom du site (theme)

    public static function siteName()
    {
        if (function_exists('siteName'))
            call_user_func('siteName');
        else {
            $core = Core::getInstance();
            echo $core->getConfigVal('siteName');
        }
    }

    public static function siteDesc()
    {
        if (function_exists('siteDesc')) {
            call_user_func('siteDesc');
        } else {
            $core = Core::getInstance();
            // Permettre une surcharge dynamique via les hooks (ex: plugin SEO ou DynamicMeta)
            $dynamicDesc = $core->callHook('site:desc');
            
            if (!empty($dynamicDesc)) {
                echo $dynamicDesc;
            } else {
                echo $core->getConfigVal('siteDesc');
            }
        }
    }

    ## Affiche l'url du site (theme)

    public static function siteUrl()
    {
        if (function_exists('siteUrl'))
            call_user_func('siteUrl');
        else {
            $core = Core::getInstance();
            echo $core->getConfigVal('siteUrl');
        }
    }

    public static function siteLogoUrl()
    {
        if (function_exists('siteLogoUrl')) {
            call_user_func('siteLogoUrl');
            return;
        }
        echo self::getSiteLogoUrl();
    }

    public static function getSiteLogoUrl()
    {
        $core = Core::getInstance();
        $logo = trim((string) ($core->getConfigVal('siteLogo') ?? ''));
        if ($logo === '') {
            return '';
        }
        return Util::urlBuild($logo);
    }

    public static function siteLogoPlacement()
    {
        if (function_exists('siteLogoPlacement')) {
            call_user_func('siteLogoPlacement');
            return;
        }
        echo self::getSiteLogoPlacement();
    }

    public static function getSiteLogoPlacement()
    {
        $core = Core::getInstance();
        return $core->getConfigVal('siteLogoPlacement') ?? 'none';
    }

    ## Affiche la navigation principale (theme)

    public static function mainNavigation($format = '<li><a class="[cssClass]" href="[target]" target="[targetAttribut]">[label]</a>[childrens]</li>')
    {
        if (function_exists('mainNavigation'))
            call_user_func('mainNavigation', $format);
        else {
            // Utiliser le nouveau système de navigation (sans fallback pour exposer les erreurs)
            $navigationService = \Core\Navigation\NavigationService::getInstance();
            $tree = $navigationService->getTreeForChannel('main');

            // Créer le renderer avec le format personnalisé
            $renderer = \Core\Navigation\NavigationRenderer::create(
                $format,
                '<ul>[childrens]</ul>',
                [
                    'escapeHtml' => true,
                    'showIcons' => \Core\Core::getInstance()->getConfigVal('showPageIcons')
                ]
            );

            echo $renderer->render($tree);
        }
    }

    /**
     * Display the Administration items
     * 
     * It only display the <li> items. You have to put it between <ul>.
     * Links are sorted by plugin's name, and current plugin had li.activePlugin class
     */
    /**
     * Affiche le menu d'administration regroupé par catégories pour la barre horizontale.
     */
    public static function adminNavigation()
    {
        if (function_exists('adminNavigation')) {
            call_user_func('adminNavigation');
            return;
        }

        $core = Core::getInstance();
        $siteUrl = rtrim((string)$core->getConfigVal('siteUrl'), '/');
        $currentPlugin = strtolower((string)$core->getPluginToCall());
        $pluginsManager = PluginsManager::getInstance();

        $categoriesMeta = [
            'contenu'    => ['label' => 'Contenu',     'icon' => 'fa-solid fa-pen-nib'],
            'communaute' => ['label' => 'Communauté',  'icon' => 'fa-solid fa-comments'],
            'stats'      => ['label' => 'Stats & SEO', 'icon' => 'fa-solid fa-chart-line'],
            'systeme'    => ['label' => 'Système',     'icon' => 'fa-solid fa-sliders'],
        ];

        $categoryByModule = [
            'page'          => 'contenu',
            'menus'         => 'contenu',
            'blog'          => 'contenu',
            'agenda'        => 'contenu',
            'galerie'       => 'contenu',
            'annonces'      => 'contenu',
            'filemanager'   => 'contenu',
            'searchmaps'    => 'contenu',
            'livechat'      => 'communaute',
            'forum'         => 'communaute',
            'contact'       => 'communaute',
            'newsletter'    => 'communaute',
            'sondage'       => 'communaute',
            'users'         => 'communaute',
            'auth'          => 'communaute',
            'notifications' => 'communaute',
            'lightstats'    => 'stats',
            'publicstats'   => 'stats',
            'seo'           => 'stats',
            'configmanager' => 'systeme',
            'pluginsmanager'=> 'systeme',
            'marketplace'   => 'systeme',
            'antispam'      => 'systeme',
            'pwa'           => 'systeme',
            'meteo'         => 'systeme',
            '299search'     => 'systeme',
        ];

        $grouped = [];
        foreach (array_keys($categoriesMeta) as $catKey) {
            $grouped[$catKey] = [];
        }

        $pushEntry = function (array $entry, ?string $customCat = null) use (&$grouped, $categoryByModule, $categoriesMeta) {
            $idLower = strtolower((string)$entry['name']);
            $cat = ($customCat && isset($categoriesMeta[$customCat]))
                ? $customCat
                : ($categoryByModule[$idLower] ?? 'systeme');
            $grouped[$cat][] = [
                'name'  => (string)$entry['name'],
                'slug'  => $idLower,
                'label' => (string)$entry['label'],
                'icon'  => !empty($entry['icon']) ? (string)$entry['icon'] : 'fa-regular fa-font-awesome',
            ];
        };

        foreach ($pluginsManager->getPlugins() as $plugin) {
            if ($plugin->getConfigVal('activate') && $plugin->getIsCallableOnAdmin()) {
                $customCat = $plugin->getInfoVal('adminCategory');
                $pushEntry([
                    'name'  => $plugin->getName(),
                    'label' => $plugin->getTranslatedName(),
                    'icon'  => $plugin->getInfoVal('icon'),
                ], is_string($customCat) ? strtolower($customCat) : null);
            }
        }

        $coreServices = [
            $core->extensions()->getAdminNavigationEntries(),
            $core->settings()->getAdminNavigationEntries(),
            $core->media()->getAdminNavigationEntries(),
            $core->auth()->getAdminNavEntries(),
            $core->pages()->getAdminNavigationEntries(),
            $core->menus()->getAdminNavigationEntries(),
        ];
        foreach ($coreServices as $entries) {
            foreach ($entries as $entry) {
                $pushEntry($entry);
            }
        }

        $isDashActive = ($currentPlugin === 'dashboard' || $currentPlugin === '');
        $html = '<li class="adm-nav-item' . ($isDashActive ? ' is-active activePlugin' : '') . '">'
              . '<a class="adm-nav-link" href="' . htmlspecialchars($siteUrl . '/admin') . '">'
              . '<i class="fa-solid fa-gauge-high" aria-hidden="true"></i>'
              . '<span>Dashboard</span>'
              . '</a></li>';

        foreach ($categoriesMeta as $catKey => $meta) {
            $items = $grouped[$catKey];
            if (empty($items)) {
                continue;
            }
            usort($items, fn($a, $b) => strcasecmp($a['label'], $b['label']));

            $catActive = false;
            foreach ($items as $it) {
                if ($currentPlugin === $it['slug']) {
                    $catActive = true;
                    break;
                }
            }

            $html .= '<li class="adm-nav-item adm-has-drop' . ($catActive ? ' is-active' : '') . '">';
            $html .= '<button type="button" class="adm-drop-btn" aria-expanded="false">';
            $html .= '<i class="' . htmlspecialchars($meta['icon']) . '" aria-hidden="true"></i>';
            $html .= '<span>' . htmlspecialchars($meta['label']) . '</span>';
            $html .= '<i class="fa-solid fa-chevron-down adm-caret" aria-hidden="true"></i>';
            $html .= '</button>';
            $html .= '<ul class="adm-drop-menu">';
            foreach ($items as $it) {
                $itemActive = ($currentPlugin === $it['slug']);
                $href = $siteUrl . '/admin/' . $it['slug'];
                $html .= '<li' . ($itemActive ? ' class="activePlugin"' : '') . '>';
                $html .= '<a href="' . htmlspecialchars($href) . '"' . ($itemActive ? ' aria-current="page"' : '') . '>';
                $html .= '<i class="' . htmlspecialchars($it['icon']) . '" aria-hidden="true"></i>';
                $html .= '<span>' . htmlspecialchars($it['label']) . '</span>';
                $html .= '</a></li>';
            }
            $html .= '</ul></li>';
        }

        echo $html;
    }

    /**
     * Affiche le menu avatar administrateur à droite de la barre horizontale admin.
     */
    public static function adminAvatarMenu(): void
    {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            return;
        }

        $core = Core::getInstance();
        $router = \Core\Router\Router::getInstance();
        $siteUrl = rtrim((string)$core->getConfigVal('siteUrl'), '/');
        $avatarData = \Core\Auth\UserAvatarHelper::prepareAvatarData($user);

        $profileUrl = $router->generate('profile');
        if ($profileUrl === '#') {
            $profileUrl = $siteUrl . '/users/profile';
        }
        $logoutUrl = $router->generate('logout');
        if ($logoutUrl === '#') {
            $logoutUrl = $siteUrl . '/users/logout';
        }
        $version = defined('VERSION') ? VERSION : '0.1.0';

        $html = '<div class="adm-avatar-wrap adm-has-drop">';
        $html .= '<button type="button" class="adm-avatar-btn" aria-expanded="false" aria-label="Menu compte administrateur">';
        if ($avatarData['has_photo']) {
            $html .= '<img src="' . htmlspecialchars($avatarData['photo_url']) . '" alt="' . htmlspecialchars($user->username()) . '" class="adm-avatar-img">';
        } else {
            $html .= '<span class="adm-avatar-initials" style="background-color:' . htmlspecialchars($avatarData['color']) . ';">' . htmlspecialchars($avatarData['initials']) . '</span>';
        }
        $html .= '<span class="adm-avatar-name">' . htmlspecialchars($user->username()) . '</span>';
        $html .= '<i class="fa-solid fa-chevron-down adm-caret" aria-hidden="true"></i>';
        $html .= '</button>';

        $html .= '<ul class="adm-drop-menu adm-drop-menu-right">';
        $html .= '<li><a href="' . htmlspecialchars($profileUrl) . '"><i class="fa-solid fa-user"></i><span>Mon profil</span></a></li>';
        $html .= '<li><a href="' . htmlspecialchars($siteUrl . '/admin/configmanager') . '"><i class="fa-solid fa-sliders"></i><span>Configuration</span></a></li>';
        $html .= '<li><a href="' . htmlspecialchars($siteUrl . '/admin/pluginsmanager') . '"><i class="fa-solid fa-plug"></i><span>Plugins</span></a></li>';
        $html .= '<li class="adm-drop-sep"><hr></li>';
        $html .= '<li><a class="adm-logout-link" href="' . htmlspecialchars($logoutUrl) . '"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>' . htmlspecialchars(Lang::get('core-disconnection')) . '</span></a></li>';
        $html .= '<li class="adm-drop-version"><span><i class="fa-solid fa-flask"></i> 299koEVO v' . htmlspecialchars((string)$version) . '</span></li>';
        $html .= '</ul></div>';

        echo $html;
    }

    ## Affiche le theme courant (theme)

    public static function theme($format = '<a target="_blank" href="[authorWebsite]">[name]</a>')
    {
        if (function_exists('theme'))
            call_user_func('theme', $format);
        else {
            $core = Core::getInstance();
            $data = $format;
            $data = str_replace('[authorWebsite]', $core->getThemeInfo('authorWebsite'), $data);
            $data = str_replace('[name]', $core->getThemeInfo('name'), $data);
            $data = str_replace('[id]', $core->getConfigVal('theme'), $data);
            echo $data;
        }
    }

    ## Affiche l'identifiant du plugin courant (theme)

    public static function pluginId()
    {
        if (function_exists('pluginId'))
            call_user_func('pluginId');
        else {
            $core = Core::getInstance();
            global $runPlugin;
            if (!$runPlugin)
                echo '';
            else
                echo $runPlugin->getName();
        }
    }

    ## Affiche l'URL courante (theme)

    public static function currentUrl()
    {
        if (function_exists('currentUrl'))
            call_user_func('currentUrl');
        else {
            $core = Core::getInstance();
            echo $core->getConfigVal('siteUrl') . $_SERVER['REQUEST_URI'];
        }
    }

    ## Affiche l'URL de l'icon du thème

    public static function themeIcon()
    {
        if (function_exists('themeIcon'))
            call_user_func('themeIcon');
        $core = Core::getInstance();
        $theme = new Theme($core->getConfigVal('theme'));
        echo $theme->getIconUrl();
    }

    /**
     * Add a module in the Public Sidebar
     * 
     * @param string Title
     * @param string Content
     */
    public static function addSidebarPublicModule(string $title, string $content)
    {
        self::$sidebarPublicModules[] = [
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Display the Public Sidebar
     */
    public static function displayPublicSidebar()
    {
        if (function_exists('displayPublicSidebar')) {
            call_user_func('displayPublicSidebar');
            return;
        }
        if (empty(self::$sidebarPublicModules)) {
            return;
        }
        echo '<aside id="modulesSidebar">';
        foreach (self::$sidebarPublicModules as $module) {
            echo '<div class="sidebarModule card">';
            echo '<header class="sidebarModuleTitle">' . $module['title'] . '</header>';
            echo '<div class="sidebarModuleContent">';
            echo $module['content'];
            echo '</div></div>';
        }
        echo '</aside>';
    }


    /**
     * Affiche le menu avatar utilisateur avec dropdown intelligent
     */
    public static function userAvatarMenu()
    {
        $currentUser = \Core\Auth\CurrentUser::get();
        if (!$currentUser->isLogged()) {
            return;
        }

        $core = Core::getInstance();
        $router = \Core\Router\Router::getInstance();
        
        // 1. Préparer les données d'avatar
        $user = UsersManager::getCurrentUser();
        $avatarData = \Core\Auth\UserAvatarHelper::prepareAvatarData($user);

        // 2. Initialiser les items du menu
        $menuItems = [];
        
        // -- Administration (Réservé aux admins, tout en haut) --
        if ($currentUser->can_access('system:admin-access')) {
            $menuItems[] = [
                'label' => 'Administration',
                'url' => $router->generate('admin'),
                'icon' => 'fa-solid fa-cogs',
                'class' => 'avatar-admin-link',
                'order' => 0
            ];
        }

        // -- Profil (Toujours présent) --
        $menuItems[] = [
            'label' => 'Mon profil',
            'url' => $router->generate('profile'),
            'icon' => 'fa-solid fa-user-gear',
            'order' => 10
        ];

        // TODO: utiliser les hook espace Pro (ex: user:avatar-menu dans le plugin dédié)
        /*
        // -- Espace Pro (Si propriétaire de fiche ou admin) --
        $isAdmin = $currentUser->can_access('system:admin-access') || (isset($user->is_admin) && $user->is_admin);
        if ($currentUser->hasClaims() || $isAdmin) {
            $menuItems[] = [
                'label' => 'Espace Pro',
                'url' => '/business-dashboard',
                'icon' => 'fa-solid fa-gauge-high',
                'class' => 'avatar-pro-link',
                'order' => 15
            ];
        }
        */

        // 3. Récupérer les items du NavigationService (ex: liens de pages personnalisés)
        try {
            $navService = \Core\Navigation\NavigationService::getInstance();
            $avatarTree = $navService->getTreeForChannel('avatar');
            if ($avatarTree) {
                foreach ($avatarTree->getRootItems() as $navItem) {
                    if (!$navItem->isEnabled()) continue;
                    
                    $menuItems[] = [
                        'label' => $navItem->getLabel(),
                        'url' => $navItem->getTarget(),
                        'icon' => $navItem->getIcon(),
                        'class' => $navItem->getCssClass(),
                        'order' => $navItem->getPosition(),
                        'badge' => $navItem->getMetadata()['badge'] ?? 0,
                    ];
                }
            }
        } catch (\Exception $e) {}

        // 4. Appel des hooks plugins (Gérés désormais par les plugins : Claim, Subscription, Messaging, etc.)
        // IMPORTANT : Chaque plugin doit renvoyer ses items dans la clé 'items'
        $hookResult = $core->callHook('user:avatar-menu', [
            'items' => $menuItems
        ]);
        $menuItems = $hookResult['items'] ?? $menuItems;

        // Filtrage des entrées retirées du menu déroulant ("Ma progression", "Revendiquer", "Agenda")
        $excludedLabels = ['Ma progression', 'Revendiquer', 'Agenda (Ajouter)', 'Agenda'];
        $menuItems = array_values(array_filter($menuItems, function ($item) use ($excludedLabels) {
            $label = trim($item['label'] ?? '');
            if (in_array($label, $excludedLabels, true)) {
                return false;
            }
            $url = $item['url'] ?? '';
            if (str_contains($url, 'tab=gamification') && str_contains(mb_strtolower($label), 'progression')) {
                return false;
            }
            if (str_contains($url, 'comment-revendiquer')) {
                return false;
            }
            if (str_contains($url, 'agenda/event/propose')) {
                return false;
            }
            return true;
        }));

        // 5. Tri global par priorité ('order')
        usort($menuItems, function($a, $b) {
            $orderA = (int)($a['order'] ?? 100);
            $orderB = (int)($b['order'] ?? 100);
            return $orderA <=> $orderB;
        });

        // 6. Ajouter la déconnexion à la fin (toujours)
        // On vérifie si un separator est nécessaire
        $menuItems[] = ['class' => 'avatar-menu-separator', 'order' => 998];
        $menuItems[] = [
            'label' => Lang::get('core-disconnection'),
            'url' => $router->generate('logout'),
            'icon' => 'fa-solid fa-sign-out-alt',
            'class' => 'avatar-menu-item-logout',
            'order' => 999
        ];

        // 7. Générer le HTML
        $html = '<li class="nav-user-avatar-menu">';
        $html .= '<a href="' . $router->generate('profile') . '" class="nav-avatar-link" aria-label="Menu utilisateur: ' . htmlspecialchars($currentUser->username()) . '">';
        
        $html .= '<span class="nav-avatar-badge-wrap">';
        $html .= $core->callHook('user:avatar-overlay', '');

        if ($avatarData['has_photo']) {
            $html .= '<img src="' . htmlspecialchars($avatarData['photo_url']) . '" alt="' . htmlspecialchars($currentUser->username()) . '" class="user-avatar-img">';
        } else {
            $html .= '<div class="user-avatar-css" style="background-color: ' . htmlspecialchars($avatarData['color']) . ';">';
            $html .= htmlspecialchars($avatarData['initials']);
            $html .= '</div>';
        }
        $html .= '</span>';
        $html .= '<span class="nav-avatar-username">' . htmlspecialchars($currentUser->username()) . '</span>';
        $html .= '</a>';
        $html .= '<ul>';

        foreach ($menuItems as $item) {
            if (($item['class'] ?? '') === 'avatar-menu-separator') {
                $html .= '<li class="avatar-menu-separator"><hr></li>';
            } else {
                $html .= '<li>';
                $html .= '<a href="' . htmlspecialchars($item['url'] ?? '#') . '" class="' . htmlspecialchars($item['class'] ?? '') . '">';
                if (!empty($item['icon'])) {
                    $html .= '<i class="' . htmlspecialchars($item['icon']) . '"></i> ';
                }
                $html .= htmlspecialchars($item['label'] ?? '');
                
                // Badges
                if (isset($item['badge']) && $item['badge'] > 0) {
                    $html .= ' <span class="nav-badge">' . (int) $item['badge'] . '</span>';
                }
                if (!empty($item['badge_text'])) {
                    $html .= ' <span class="nav-badge-text" style="background-color: ' . htmlspecialchars($item['badge_color'] ?? '#95a5a6') . ';">' . htmlspecialchars($item['badge_text']) . '</span>';
                }
                $html .= '</a>';
                $html .= '</li>';
            }
        }

        $html .= '</ul></li>';
        echo $html;
    }

    /**
     * Renders the search input for the header.
     *
     * @param string|null $position If provided, checks if it matches the plugin's configured position.
     *                              'manual' can be used to force rendering regardless of config position.
     * @return string The HTML for the search input, or an empty string if not active/positioned correctly.
     */
    public static function searchHeaderSearch($position = null): string
    {
        $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
        $plugin = $pluginsManager->getPlugin('search');
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return '';
        }

        $configPos = $plugin->getConfigVal('position') ?: 'header';
        $expandable = $plugin->getConfigVal('expandable') !== '0';

        // Si position est fournie, on vérifie si elle correspond à la config
        // Sauf si c'est 'manual' ou null
        if ($position !== null && $position !== 'manual' && $position !== $configPos) {
            return '';
        }

        $currentPos = ($position === 'manual' || $position === null) ? $configPos : $position;

        $router = \Core\Router\Router::getInstance();
        try {
            $url = $router->generate('search-index');
        } catch (\Exception $e) {
            $url = '/search';
        }
        
        $containerClass = 'header-search-container search-pos-' . $currentPos;
        if (!$expandable && $currentPos === 'header') {
            $containerClass .= ' search-static';
        }

        $html = '<div class="' . $containerClass . '">
            <form action="' . $url . '" method="GET" class="header-search-form">
                <input type="text" name="q" placeholder="Rechercher..." aria-label="Rechercher" autocomplete="off">
                <button type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>';
        
        return $html;
    }

    /**
     * Récupère le nombre de messages non lus
     */
    private static function getUnreadMessagingCount(): int
    {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            return 0;
        }

        $userId = (int) $user->attributes['id'];
        if ($userId <= 0) {
            return 0;
        }

        try {
            $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
            $messagingPlugin = $pluginsManager->getPlugin('messaging');
            if ($messagingPlugin && $messagingPlugin->isInstalled() && $messagingPlugin->getConfigVal('activate')) {
                require_once PLUGINS . 'messaging/services/MessagingService.php';
                $messagingService = new \Messaging\Services\MessagingService();
                return $messagingService->getUnreadCount($userId);
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs
        }

        return 0;
    }

    /**
     * Récupère les données de progression (gamification)
     */
    private static function getProgressionData(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        try {
            if (!\Core\Plugin\PluginsManager::isActivePlugin('gamification')) {
                return null;
            }

            $pointsServiceFile = PLUGINS . 'gamification/services/PointsService.php';
            if (!file_exists($pointsServiceFile)) {
                return null;
            }

            require_once $pointsServiceFile;
            $pointsService = new \Gamification\Services\PointsService();
            $pointsService->ensureUserHasPalier($userId);

            $currentPalier = $pointsService->getUserPalier($userId);
            if (!$currentPalier) {
                return null;
            }

            return [
                'has_data' => true,
                'badge_name' => $currentPalier['badge_name'] ?? 'Débutant',
                'badge_icon' => $currentPalier['badge_icon'] ?? 'fa-seedling',
                'badge_color' => $currentPalier['badge_color'] ?? '#95a5a6'
            ];
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * Affiche la barre de recherche
     * @param string|null $position Position forcée ou null pour utiliser la config
     */
    public static function headerSearch($position = null)
    {
        if (function_exists('searchHeaderSearch')) {
            return searchHeaderSearch($position);
        }
        return '';
    }

    /**
     * Affiche les liens de navigation du footer (pages avec position="footer")
     */
    public static function footerNavigation()
    {
        // Utiliser le nouveau système de navigation
        $navigationService = \Core\Navigation\NavigationService::getInstance();
        $tree = $navigationService->getTreeForChannel('footer');

        // Créer le renderer pour des liens simples (pas de <ul>/<li> par défaut pour le footer)
        $renderer = \Core\Navigation\NavigationRenderer::create(
            '<a class="[cssClass]" href="[target]" target="[targetAttribut]">[label]</a>',
            '[childrens]', // Pas de wrapper ul pour le footer
            [
                'escapeHtml' => true,
                'showIcons' => false
            ]
        );

        return $renderer->render($tree);
    }

    /**
     * Affiche le lien Espace Pro dans la barre de navigation principale si l'utilisateur est un Pro ou Admin
     */
    public static function proNavLink(): string
    {
        if (!defined('IS_LOGGED') || !IS_LOGGED) {
            return '';
        }
        $currentUser = UsersManager::getCurrentUser();
        if (!$currentUser) {
            return '';
        }

        $isAdmin = (defined('IS_ADMIN') && IS_ADMIN) 
            || (!empty($currentUser->is_admin)) 
            || (method_exists($currentUser, 'getGroup') && $currentUser->getGroup() === 'admin');

        $authCurrentUser = \Core\Auth\CurrentUser::get();
        $hasClaims = $authCurrentUser ? $authCurrentUser->hasClaims() : false;

        // TODO: utiliser les hook espace Pro
        /*
        if ($hasClaims || $isAdmin) {
            $currentUri = $_SERVER['REQUEST_URI'] ?? '';
            $isActive = (strpos($currentUri, '/business-dashboard') === 0 || strpos($currentUri, '/BusinessDashboard') === 0 || strpos($currentUri, '/poimanager/edit') === 0) ? ' active' : '';
            return '<li class="nav-item-pro' . $isActive . '"><a class="nav-pro-badge' . $isActive . '" href="/business-dashboard" title="Accéder à votre Espace Pro &amp; Métriques"><i class="fa-solid fa-gauge-high"></i> <span>Espace Pro</span></a></li>';
        }
        */
        return '';
    }

}