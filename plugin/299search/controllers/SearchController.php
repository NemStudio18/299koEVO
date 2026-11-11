<?php

namespace Search299\Controllers;

use Core\Controllers\PublicController;
use Core\Database;
use Core\Navigation\AccessPolicyEvaluator;
use Core\Navigation\ConditionContext;
use Core\Page\Page;
use Core\Plugin\PluginsManager;
use Core\Responses\PublicResponse;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class SearchController extends PublicController
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->runPlugin) {
            $this->runPlugin = $this->pluginsManager->getPlugin('299search');
        }
    }

    public function index(): PublicResponse
    {
        $response = new PublicResponse();
        $query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $typeFilter = isset($_GET['type']) ? trim((string)$_GET['type']) : 'all';
        $siteName = $this->core->getConfigVal('siteName') ?: 'le site';

        $response->setTitle('Recherche : ' . ($query !== '' ? htmlspecialchars($query) : $siteName));

        $tpl = $response->createPluginTemplate('299search', 'search_results');

        $results = [];
        $countsByType = [
            'all'   => 0,
            'page'  => 0,
            'blog'  => 0,
            'event' => 0,
            'poi'   => 0
        ];

        if ($query !== '' && mb_strlen($query) >= 2) {
            $normQuery = $this->normalize($query);

            // 1. Pages réelles du CMS
            foreach ($this->searchSitePages($normQuery) as $p) {
                $countsByType['page']++;
                if ($typeFilter === 'all' || $typeFilter === 'page') {
                    $results[] = $p;
                }
            }

            // 2. Articles du Blog (si plugin actif)
            if (PluginsManager::isActivePlugin('blog')) {
                foreach ($this->searchBlog($normQuery) as $b) {
                    $countsByType['blog']++;
                    if ($typeFilter === 'all' || $typeFilter === 'blog') {
                        $results[] = $b;
                    }
                }
            }

            // 3. Événements de l'Agenda (si plugin actif)
            if (PluginsManager::isActivePlugin('agenda')) {
                foreach ($this->searchEvents($normQuery) as $e) {
                    $countsByType['event']++;
                    if ($typeFilter === 'all' || $typeFilter === 'event') {
                        $results[] = $e;
                    }
                }
            }

            // 4. Fiches Lieux / POI (optionnel si table poi_public présente)
            foreach ($this->searchPois($query, $normQuery) as $poi) {
                $countsByType['poi']++;
                if ($typeFilter === 'all' || $typeFilter === 'poi') {
                    $results[] = $poi;
                }
            }

            $countsByType['all'] = $countsByType['page'] + $countsByType['blog'] + $countsByType['event'] + $countsByType['poi'];
        }

        $tpl->set('siteName', htmlspecialchars($siteName));
        $tpl->set('q', htmlspecialchars($query));
        $tpl->set('typeFilter', $typeFilter);
        $tpl->set('results', $results);
        $tpl->set('resultsCount', count($results));
        $tpl->set('qEnc', rawurlencode($query));
        $tpl->set('hasResults', $query !== '' && count($results) > 0);
        $tpl->set('noResults', $query !== '' && count($results) === 0);
        $tpl->set('noQuery', $query === '');
        $tpl->set('countsByType', $countsByType);
        $tpl->set('homeUrl', $this->core->getConfigVal('siteUrl') ?: '/');

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Recherche dynamique dans les pages publiées du CMS
     */
    private function searchSitePages(string $normQuery): array
    {
        $matches = [];
        $pageManager = new Page();
        $evaluator = AccessPolicyEvaluator::getInstance();
        $ctx = new ConditionContext($this->user ?: null);

        foreach ($pageManager->getItems() as $item) {
            if ($item->getIsHidden() || $item->getPassword() !== '') {
                continue;
            }
            if ($item->targetIs() === 'parent' || !$item->isVisibleOnList()) {
                continue;
            }
            if (!$evaluator->canAccess($item, $ctx)) {
                continue;
            }

            $title = trim((string)($item->getMainTitle() ?: $item->getName()));
            $titlePlain = trim(strip_tags($title));
            if ($titlePlain === '') {
                continue;
            }

            $metaDesc = trim((string)$item->getMetaDescriptionTag());
            $metaTitle = trim((string)$item->getMetaTitleTag());
            $content = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode((string)$item->getContent()))));

            $normHaystack = $this->normalize($titlePlain . ' ' . $metaTitle . ' ' . $metaDesc . ' ' . $content);
            if (mb_strpos($normHaystack, $normQuery) !== false) {
                $excerptSource = $metaDesc !== '' ? $metaDesc : $content;
                $excerpt = $excerptSource !== ''
                    ? (mb_substr($excerptSource, 0, 160) . (mb_strlen($excerptSource) > 160 ? '…' : ''))
                    : 'Page du site';

                $matches[] = [
                    'type' => 'page',
                    'type_label' => 'Page du site',
                    'type_icon' => 'fa-solid fa-file-lines',
                    'badge_class' => 'badge-page',
                    'title' => $titlePlain,
                    'url' => $pageManager->makeUrl($item),
                    'excerpt' => $excerpt,
                    'meta' => $item->getIsHomepage() ? 'Page d\'accueil' : 'Page'
                ];
            }
        }

        return $matches;
    }

    /**
     * Recherche dans les articles du Blog
     */
    private function searchBlog(string $normQuery): array
    {
        $blogFile = DATA_PLUGIN . 'blog/blog.json';
        if (!file_exists($blogFile)) {
            return [];
        }

        $data = json_decode((string)file_get_contents($blogFile), true);
        if (!is_array($data)) {
            return [];
        }

        $matches = [];
        foreach ($data as $post) {
            if (!empty($post['draft'])) {
                continue;
            }
            $title = (string)($post['name'] ?? '');
            $intro = strip_tags(html_entity_decode((string)($post['intro'] ?? '')));
            $body  = strip_tags(html_entity_decode((string)($post['content'] ?? '')));
            $normHaystack = $this->normalize($title . ' ' . $intro . ' ' . $body);

            if (mb_strpos($normHaystack, $normQuery) !== false) {
                $id = (int)($post['id'] ?? 0);
                $url = ($id > 0 && $title !== '')
                    ? $this->router->generate('blog-read', ['name' => Util::strToUrl($title), 'id' => $id])
                    : $this->router->generate('blog-home');

                $excerptBase = trim($intro !== '' ? $intro : $body);
                $matches[] = [
                    'type' => 'blog',
                    'type_label' => 'Article de Blog',
                    'type_icon' => 'fa-solid fa-newspaper',
                    'badge_class' => 'badge-blog',
                    'title' => $title,
                    'url' => $url,
                    'excerpt' => mb_substr($excerptBase, 0, 160) . (mb_strlen($excerptBase) > 160 ? '…' : ''),
                    'meta' => !empty($post['date']) ? ('Publié le ' . $post['date']) : 'Blog'
                ];
            }
        }

        return array_slice($matches, 0, 8);
    }

    /**
     * Recherche dans les événements de l'Agenda (SQLite, MySQL ou JSON via l'entité Event)
     */
    private function searchEvents(string $normQuery): array
    {
        if (!class_exists('\Agenda\Entities\Event')) {
            return [];
        }

        $matches = [];
        try {
            $events = \Agenda\Entities\Event::all();
            $today = date('Y-m-d');

            foreach ($events as $ev) {
                if (method_exists($ev, 'isApproved') && !$ev->isApproved()) {
                    continue;
                }
                $startDate = $ev->getStartDate();
                $endDate = $ev->getEndDate() ?: $startDate;
                if ($endDate !== '' && $endDate < $today && $startDate < $today) {
                    continue;
                }

                $title = $ev->getTitle();
                $desc  = trim(strip_tags(html_entity_decode($ev->getDescription())));
                $loc   = $ev->getLocation();
                $normHaystack = $this->normalize($title . ' ' . $desc . ' ' . $loc);

                if (mb_strpos($normHaystack, $normQuery) !== false) {
                    $url = method_exists($ev, 'getUrl') ? $ev->getUrl() : '/agenda';
                    $metaParts = [];
                    if ($startDate !== '') {
                        $metaParts[] = $startDate;
                    }
                    if ($loc !== '') {
                        $metaParts[] = $loc;
                    }

                    $matches[] = [
                        'type' => 'event',
                        'type_label' => 'Agenda',
                        'type_icon' => 'fa-solid fa-calendar-days',
                        'badge_class' => 'badge-event',
                        'title' => $title,
                        'url' => $url,
                        'excerpt' => mb_substr($desc, 0, 150) . (mb_strlen($desc) > 150 ? '…' : ''),
                        'meta' => !empty($metaParts) ? implode(' • ', $metaParts) : 'Événement'
                    ];
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return array_slice($matches, 0, 8);
    }

    /**
     * Recherche optionnelle dans les fiches lieux/POI si la table poi_public existe
     */
    private function searchPois(string $query, string $normQuery): array
    {
        $matches = [];
        try {
            $db = Database::getInstance();
            $dialect = $db->getDialect();
            $tableCheck = $db->query($dialect->tableExists('poi_public'));
            if (empty($tableCheck)) {
                return [];
            }

            $pdo = $db->getPdo();
            $stmt = $pdo->prepare("
                SELECT id, name, slug, category, subcategory, addr_street, addr_city, description
                FROM poi_public
                WHERE (name LIKE :q OR slug LIKE :norm OR addr_street LIKE :q OR subcategory LIKE :q OR description LIKE :q)
                  AND is_closed = 0
                ORDER BY datascore DESC
                LIMIT 10
            ");
            $stmt->execute([
                ':q' => '%' . $query . '%',
                ':norm' => '%' . str_replace(' ', '-', $normQuery) . '%'
            ]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $r) {
                $slug = !empty($r['slug']) ? ('/' . ltrim($r['slug'], '/')) : ('/p-' . $r['id']);
                $address = trim(($r['addr_street'] ?? '') . ' ' . ($r['addr_city'] ?? ''));
                $desc = !empty($r['description']) ? strip_tags($r['description']) : ($r['subcategory'] ?: $r['category']);

                $matches[] = [
                    'type' => 'poi',
                    'type_label' => 'Lieu & Fiche',
                    'type_icon' => 'fa-solid fa-location-dot',
                    'badge_class' => 'badge-poi',
                    'title' => $r['name'],
                    'url' => $slug,
                    'excerpt' => mb_substr((string)$desc, 0, 150) . (mb_strlen((string)$desc) > 150 ? '…' : ''),
                    'meta' => trim(($address !== '' ? $address . ' • ' : '') . ($r['category'] ?? 'Lieu'))
                ];
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $matches;
    }

    /**
     * Normalise une chaîne en minuscules sans accents
     */
    private function normalize(string $str): string
    {
        $str = mb_strtolower(trim($str), 'UTF-8');
        $trans = [
            'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','ç'=>'c',
            'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i',
            'î'=>'i','ï'=>'i','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o',
            'õ'=>'o','ö'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
            'ý'=>'y','ÿ'=>'y','œ'=>'oe','æ'=>'ae'
        ];
        return strtr($str, $trans);
    }
}
