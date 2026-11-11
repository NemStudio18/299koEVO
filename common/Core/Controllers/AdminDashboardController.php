<?php

namespace Core\Controllers;

use Core\Auth\User;
use Core\Navigation\MenuService;
use Core\Page\Page;
use Core\Plugin\PluginsManager;
use Core\Responses\AdminResponse;

defined('ROOT') or exit('Access denied!');

/**
 * Tableau de bord général obligatoire de l'administration (/admin).
 */
class AdminDashboardController extends AdminController
{
    public function index(): AdminResponse
    {
        $response = new AdminResponse();
        $response->setTitle('<i class="fa-solid fa-gauge-high"></i> Tableau de bord');

        $stats = $this->collectCoreStats();
        $pluginCards = $this->collectPluginStats();
        $extraWidgets = $this->core->callHook('admin:dashboard-widgets', []);
        if (!is_array($extraWidgets)) {
            $extraWidgets = [];
        }

        $quickLinks = $this->buildQuickLinks();

        $tpl = $response->createPluginTemplate('dashboard', 'index');
        $tpl->set('stats', $stats);
        $tpl->set('pluginCards', $pluginCards);
        $tpl->set('extraWidgets', $extraWidgets);
        $tpl->set('quickLinks', $quickLinks);
        $tpl->set('siteName', (string)$this->core->getConfigVal('siteName'));
        $tpl->set('phpVersion', PHP_VERSION);
        $tpl->set('cmsVersion', defined('VERSION') ? VERSION : '0.1.0');
        $tpl->set('storageDriver', (string)(\Core\Database::getInstance()->getConfig('driver') ?? 'json'));
        $response->addTemplate($tpl);

        return $response;
    }

    private function collectCoreStats(): array
    {
        $pageManager = new Page();
        $publishedPages = 0;
        $draftPages = 0;
        foreach ($pageManager->getItems() as $p) {
            if ($p->targetIs() !== 'page') {
                continue;
            }
            if ($p->getIsHidden()) {
                $draftPages++;
            } else {
                $publishedPages++;
            }
        }

        $menuCounts = MenuService::getInstance()->getLocationCounts();
        $totalMenuItems = array_sum($menuCounts);

        $usersCount = 0;
        try {
            $usersCount = User::queryBuilder()->count();
        } catch (\Throwable $e) {
            $usersCount = 0;
        }

        $activePlugins = 0;
        $totalPlugins = 0;
        foreach ($this->pluginsManager->getPlugins() as $plugin) {
            $totalPlugins++;
            if ($plugin->getConfigVal('activate')) {
                $activePlugins++;
            }
        }

        return [
            'publishedPages' => $publishedPages,
            'draftPages'     => $draftPages,
            'totalMenuItems' => $totalMenuItems,
            'usersCount'     => $usersCount,
            'activePlugins'  => $activePlugins,
            'totalPlugins'   => $totalPlugins,
        ];
    }

    private function collectPluginStats(): array
    {
        $cards = [];
        $pdo = null;
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
        } catch (\Throwable $e) {
            $pdo = null;
        }

        if (PluginsManager::isActivePlugin('LiveChat') && $pdo) {
            try {
                $openCount = (int)$pdo->query("SELECT COUNT(*) FROM vb_livechat_conversations WHERE status = 'open'")->fetchColumn();
                $unreadCount = (int)$pdo->query("SELECT COALESCE(SUM(unread_admin), 0) FROM vb_livechat_conversations")->fetchColumn();
                $cards[] = [
                    'title'  => 'LiveChat Direct',
                    'value'  => $unreadCount > 0 ? "{$unreadCount} non lu(s)" : "{$openCount} ouverte(s)",
                    'sub'    => "{$openCount} conversation(s) ouverte(s)",
                    'icon'   => 'fa-solid fa-comments',
                    'url'    => '/admin/livechat',
                    'accent' => $unreadCount > 0 ? 'warn' : 'blue',
                ];
            } catch (\Throwable $e) {
            }
        }

        if (PluginsManager::isActivePlugin('contact') && $pdo) {
            try {
                $msgCount = (int)$pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
                $cards[] = [
                    'title'  => 'Messages Contact',
                    'value'  => (string)$msgCount,
                    'sub'    => 'Demandes reçues',
                    'icon'   => 'fa-solid fa-envelope',
                    'url'    => '/admin/contact',
                    'accent' => 'indigo',
                ];
            } catch (\Throwable $e) {
            }
        }

        if (PluginsManager::isActivePlugin('blog') && $pdo) {
            try {
                $postsCount = (int)$pdo->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
                $cards[] = [
                    'title'  => 'Articles de Blog',
                    'value'  => (string)$postsCount,
                    'sub'    => 'Rédaction & publications',
                    'icon'   => 'fa-regular fa-newspaper',
                    'url'    => '/admin/blog',
                    'accent' => 'green',
                ];
            } catch (\Throwable $e) {
            }
        }

        if (PluginsManager::isActivePlugin('newsletter') && $pdo) {
            try {
                $subsCount = (int)$pdo->query('SELECT COUNT(*) FROM newsletter_subscribers')->fetchColumn();
                $cards[] = [
                    'title'  => 'Newsletter',
                    'value'  => (string)$subsCount,
                    'sub'    => 'Abonnés enregistrés',
                    'icon'   => 'fa-solid fa-paper-plane',
                    'url'    => '/admin/newsletter',
                    'accent' => 'purple',
                ];
            } catch (\Throwable $e) {
            }
        }

        return $cards;
    }

    private function buildQuickLinks(): array
    {
        $links = [
            ['label' => 'Nouvelle page', 'url' => '/admin/page/new', 'icon' => 'fa-solid fa-file-circle-plus'],
            ['label' => 'Gérer les menus', 'url' => '/admin/menus', 'icon' => 'fa-solid fa-bars-staggered'],
            ['label' => 'Configuration', 'url' => '/admin/configmanager', 'icon' => 'fa-solid fa-sliders'],
            ['label' => 'Utilisateurs', 'url' => '/admin/users', 'icon' => 'fa-solid fa-users'],
        ];

        if (PluginsManager::isActivePlugin('blog')) {
            $links[] = ['label' => 'Blog & Articles', 'url' => '/admin/blog', 'icon' => 'fa-regular fa-newspaper'];
        }
        if (PluginsManager::isActivePlugin('LiveChat')) {
            $links[] = ['label' => 'Ouvrir LiveChat', 'url' => '/admin/livechat', 'icon' => 'fa-solid fa-comments'];
        }

        return $links;
    }
}
