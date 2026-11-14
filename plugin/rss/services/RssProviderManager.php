<?php

namespace Rss\Services;

use Core\Plugin\PluginsManager;
use Core\Core;

defined('ROOT') or exit('Access denied!');

class RssProviderManager
{
    /**
     * Retourne tous les fournisseurs de contenu RSS détectés dynamiquement.
     * Seuls les plugins installés et activés (ou modules de base) sont proposés.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getAvailableProviders(): array
    {
        $providers = [];

        // 1. Module natif : Pages du site (toujours disponible)
        $providers['pages'] = [
            'slug' => 'pages',
            'name' => 'Pages du site',
            'icon' => 'fa-file-lines',
            'description' => 'Créations et mises à jour des pages de contenu',
            'is_active' => true,
        ];

        // 2. Détection dynamique des plugins installés et actifs avec adaptateur intégré
        $nativeAdapters = [
            'blog' => [
                'name' => 'Articles de Blog',
                'icon' => 'fa-newspaper',
                'description' => 'Derniers articles et actualités publiés',
            ],
            'agenda' => [
                'name' => 'Événements de l' . "'" . 'Agenda',
                'icon' => 'fa-calendar-days',
                'description' => 'Sorties, manifestations et rendez-vous à venir',
            ],
            'annonces' => [
                'name' => 'Petites Annonces',
                'icon' => 'fa-bullhorn',
                'description' => 'Nouvelles annonces actives publiées',
            ],
            'docs' => [
                'name' => 'Documentation & Guides',
                'icon' => 'fa-book-open',
                'description' => 'Nouveaux guides et mises à jour de la base de connaissances',
            ],
            'ecommerce' => [
                'name' => 'Boutique (Nouveaux Produits)',
                'icon' => 'fa-bag-shopping',
                'description' => 'Nouveautés et réassorts du catalogue en ligne',
            ],
            'forum' => [
                'name' => 'Forum & Discussions',
                'icon' => 'fa-comments',
                'description' => 'Derniers sujets et discussions ouverts',
            ],
            'galerie' => [
                'name' => 'Galerie Photos',
                'icon' => 'fa-images',
                'description' => 'Derniers albums et clichés ajoutés',
            ],
        ];

        foreach ($nativeAdapters as $slug => $meta) {
            if (PluginsManager::isActivePlugin($slug)) {
                $providers[$slug] = array_merge(['slug' => $slug, 'is_active' => true], $meta);
            }
        }

        // 3. Hook d'extension externe : permet à n'importe quel autre plugin existant ou futur de s'enregistrer
        $core = Core::getInstance();
        $hookResult = $core->callHook('rss:providers', []);
        if (is_array($hookResult)) {
            foreach ($hookResult as $slug => $data) {
                if (!isset($providers[$slug]) && is_array($data) && PluginsManager::isActivePlugin($slug)) {
                    $providers[$slug] = [
                        'slug' => $slug,
                        'name' => $data['name'] ?? ucfirst($slug),
                        'icon' => $data['icon'] ?? 'fa-rss',
                        'description' => $data['description'] ?? 'Flux RSS de ' . ucfirst($slug),
                        'is_active' => true,
                        'callback' => $data['callback'] ?? null,
                    ];
                }
            }
        }

        return $providers;
    }

    /**
     * Récupère les éléments de flux d'un fournisseur donné.
     *
     * @return array<array<string, mixed>>
     */
    public static function getItemsForProvider(string $slug, string $siteUrl): array
    {
        $providers = self::getAvailableProviders();
        if (!isset($providers[$slug])) {
            return [];
        }

        $provider = $providers[$slug];

        // 1. Si un callback personnalisé est déclaré via le hook
        if (!empty($provider['callback']) && is_callable($provider['callback'])) {
            try {
                $customItems = call_user_func($provider['callback'], $siteUrl);
                if (is_array($customItems)) {
                    return $customItems;
                }
            } catch (\Throwable $e) {}
        }

        // 2. Adaptateurs natifs pour les plugins 299ko
        switch ($slug) {
            case 'pages':
                return self::getPageItems($siteUrl);
            case 'blog':
                return self::getBlogItems($siteUrl);
            case 'agenda':
                return self::getAgendaItems($siteUrl);
            case 'annonces':
                return self::getAnnoncesItems($siteUrl);
            case 'docs':
                return self::getDocsItems($siteUrl);
            case 'ecommerce':
                return self::getEcommerceItems($siteUrl);
            case 'forum':
                return self::getForumItems($siteUrl);
            case 'galerie':
                return self::getGalerieItems($siteUrl);
            default:
                return [];
        }
    }

    private static function getPageItems(string $siteUrl): array
    {
        $items = [];
        $file = DATA . 'pages.json';
        if (!file_exists($file)) return $items;
        $pages = json_decode((string)@file_get_contents($file), true);
        if (!is_array($pages)) return $items;

        foreach ($pages as $p) {
            if (empty($p['published']) && (isset($p['is_active']) && !$p['is_active'])) continue;
            $slug = $p['slug'] ?? $p['url'] ?? '';
            if (empty($slug)) continue;
            $title = $p['title'] ?? $p['name'] ?? 'Page';
            $ts = !empty($p['updated_at']) ? strtotime($p['updated_at']) : (!empty($p['created_at']) ? strtotime($p['created_at']) : time());
            $desc = strip_tags(html_entity_decode($p['content'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            $items[] = [
                'title' => $title,
                'link' => $siteUrl . '/' . ltrim($slug, '/'),
                'timestamp' => $ts ?: time(),
                'category' => 'Pages',
                'description' => mb_substr($desc, 0, 350)
            ];
        }
        return $items;
    }

    private static function getBlogItems(string $siteUrl): array
    {
        $items = [];
        $file = DATA . 'plugin/blog/articles.json';
        if (!file_exists($file)) return $items;
        $articles = json_decode((string)@file_get_contents($file), true);
        if (!is_array($articles)) return $items;

        foreach ($articles as $art) {
            if (empty($art['published']) && (isset($art['status']) && $art['status'] !== 'published')) continue;
            $title = $art['title'] ?? 'Sans titre';
            $id = $art['id'] ?? '';
            $ts = !empty($art['date']) ? strtotime($art['date']) : time();
            $desc = strip_tags(html_entity_decode($art['chapo'] ?? $art['content'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            $items[] = [
                'title' => $title,
                'link' => $siteUrl . '/blog/article/' . $id,
                'timestamp' => $ts ?: time(),
                'category' => 'Blog',
                'description' => mb_substr($desc, 0, 350)
            ];
        }
        return $items;
    }

    private static function getAgendaItems(string $siteUrl): array
    {
        $items = [];
        if (!class_exists('\Agenda\Entities\EventManager')) return $items;
        try {
            $eventManager = new \Agenda\Entities\EventManager();
            $events = $eventManager->findFutureEvents(date('Y-m-d'));
            foreach ($events as $event) {
                $id = $event->getId();
                $title = $event->getTitle();
                $startDate = $event->getStartDate();
                $ts = !empty($startDate) ? strtotime($startDate) : time();
                $desc = strip_tags(html_entity_decode($event->getDescription() ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($event->getLocation()) {
                    $desc = 'Lieu : ' . $event->getLocation() . ' — ' . $desc;
                }

                $items[] = [
                    'title' => '[Agenda] ' . $title,
                    'link' => $siteUrl . '/agenda/event/' . $id,
                    'timestamp' => $ts ?: time(),
                    'category' => 'Agenda',
                    'description' => mb_substr($desc, 0, 350)
                ];
            }
        } catch (\Throwable $e) {}
        return $items;
    }

    private static function getAnnoncesItems(string $siteUrl): array
    {
        $items = [];
        $file = DATA . 'plugin/annonces/annonces.json';
        if (!file_exists($file)) return $items;
        $annonces = json_decode((string)@file_get_contents($file), true);
        if (!is_array($annonces)) return $items;

        foreach ($annonces as $ann) {
            if (isset($ann['active']) && !$ann['active']) continue;
            $title = $ann['title'] ?? 'Annonce';
            $id = $ann['id'] ?? '';
            $ts = !empty($ann['created_at']) ? strtotime($ann['created_at']) : time();
            $desc = strip_tags(html_entity_decode($ann['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!empty($ann['price'])) {
                $desc = 'Prix : ' . $ann['price'] . ' € — ' . $desc;
            }

            $items[] = [
                'title' => '[Petite Annonce] ' . $title,
                'link' => $siteUrl . '/annonces/detail/' . $id,
                'timestamp' => $ts ?: time(),
                'category' => 'Petites Annonces',
                'description' => mb_substr($desc, 0, 350)
            ];
        }
        return $items;
    }

    private static function getDocsItems(string $siteUrl): array
    {
        $items = [];
        if (!class_exists('\Docs\Entities\WikiPageManager')) return $items;
        try {
            $mgr = new \Docs\Entities\WikiPageManager();
            $pages = $mgr->getItems();
            foreach ($pages as $p) {
                $title = $p->getName();
                $id = $p->getId();
                $ts = !empty($p->getDate()) ? (int)$p->getDate() : time();
                $desc = strip_tags(html_entity_decode($p->getContent() ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                $items[] = [
                    'title' => '[Documentation] ' . $title,
                    'link' => $siteUrl . '/docs/article/' . \Utils\Util::strToUrl($title) . '-' . $id,
                    'timestamp' => $ts ?: time(),
                    'category' => 'Documentation',
                    'description' => mb_substr($desc, 0, 350)
                ];
            }
        } catch (\Throwable $e) {}
        return $items;
    }

    private static function getEcommerceItems(string $siteUrl): array
    {
        $items = [];
        if (!class_exists('\Ecommerce\Entities\ProductManager')) return $items;
        try {
            $mgr = new \Ecommerce\Entities\ProductManager();
            $products = $mgr->getAll();
            foreach ($products as $p) {
                if (method_exists($p, 'isActive') && !$p->isActive()) continue;
                $title = $p->getName();
                $id = $p->getId();
                $price = $p->getPrice();
                $desc = strip_tags(html_entity_decode($p->getDescription() ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                $items[] = [
                    'title' => '[Boutique] ' . $title . ' (' . number_format((float)$price, 2) . ' €)',
                    'link' => $siteUrl . '/shop/product/' . $id,
                    'timestamp' => time(),
                    'category' => 'Boutique',
                    'description' => mb_substr($desc, 0, 350)
                ];
            }
        } catch (\Throwable $e) {}
        return $items;
    }

    private static function getForumItems(string $siteUrl): array
    {
        $items = [];
        $file = DATA . 'plugin/forum/topics.json';
        if (!file_exists($file)) return $items;
        $topics = json_decode((string)@file_get_contents($file), true);
        if (!is_array($topics)) return $items;

        foreach ($topics as $t) {
            $title = $t['title'] ?? 'Sujet';
            $id = $t['id'] ?? '';
            $ts = !empty($t['created_at']) ? strtotime($t['created_at']) : time();
            $desc = strip_tags(html_entity_decode($t['content'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            $items[] = [
                'title' => '[Forum] ' . $title,
                'link' => $siteUrl . '/forum/topic/' . $id,
                'timestamp' => $ts ?: time(),
                'category' => 'Forum',
                'description' => mb_substr($desc, 0, 350)
            ];
        }
        return $items;
    }

    private static function getGalerieItems(string $siteUrl): array
    {
        $items = [];
        $file = DATA . 'plugin/galerie/photos.json';
        if (!file_exists($file)) return $items;
        $photos = json_decode((string)@file_get_contents($file), true);
        if (!is_array($photos)) return $items;

        foreach ($photos as $ph) {
            $title = $ph['title'] ?? $ph['name'] ?? 'Photo';
            $id = $ph['id'] ?? '';
            $ts = !empty($ph['created_at']) ? strtotime($ph['created_at']) : time();

            $items[] = [
                'title' => '[Galerie] ' . $title,
                'link' => $siteUrl . '/galerie/photo/' . $id,
                'timestamp' => $ts ?: time(),
                'category' => 'Galerie',
                'description' => $ph['description'] ?? 'Nouvelle photo ajoutée à la galerie'
            ];
        }
        return $items;
    }
}

