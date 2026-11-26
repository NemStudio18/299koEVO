<?php

namespace Core\Page\Controllers;

use Core\Controllers\PublicController;
use Core\Page\Page;
use Core\Page\PageItem;
use Utils\Util;
use Core\Responses\PublicResponse;
use Template\Template;

/**
 * @copyright (C) 2023, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class PageController extends PublicController
{

    /**
     * page Object
     * @var Page
     */
    protected Page $page;

    /**
     * pageItem Object
     * @var PageItem
     */
    protected ?PageItem $pageItem = null;

    public function home()
    {
        $this->page = new Page();
        $pageItem = $this->page->createHomepage();

        if (!$pageItem instanceof PageItem) {
            $this->core->error404();
        }
        $this->pageItem = $pageItem;
        \Template\Template::addGlobal('IS_HOMEPAGE', true);
        $router = \Core\Router\Router::getInstance();

        // --- DASHBOARD DATA INJECTION ---
        // Initialize with default values to avoid template engine issues if plugins are missing
        \Template\Template::addGlobal('lastNews', []);
        \Template\Template::addGlobal('lastTopic', null);
        \Template\Template::addGlobal('nextEvents', []);
        \Template\Template::addGlobal('lastPoll', null);
        \Template\Template::addGlobal('barometerStats', ['users' => 0, 'ideas' => 0, 'votes' => 0]);
        \Template\Template::addGlobal('isDonsActive', false);

        // === DASHBOARD WIDGETS ===
        // Au lieu de charger directement les données des plugins (couplage fort),
        // on utilise un hook pour que chaque plugin injecte ses propres widgets
        
        $widgets = [
            'lastNews' => [],
            'lastTopics' => [],
            'nextEvents' => [],
            'activePolls' => []
        ];
        
        // Appeler le hook pour que les plugins injectent leurs widgets
        $widgets = $this->core->callHook('dashboard:inject-widgets', $widgets);
        
        // Injecter les widgets dans le template
        \Template\Template::addGlobal('lastNews', $widgets['lastNews'] ?? []);
        \Template\Template::addGlobal('lastTopics', $widgets['lastTopics'] ?? []);
        \Template\Template::addGlobal('nextEvents', $widgets['nextEvents'] ?? []);
        \Template\Template::addGlobal('activePolls', $widgets['activePolls'] ?? []);
        
        // Compatibilité : atelier_event (premier événement)
        if (!empty($widgets['nextEvents'])) {
            \Template\Template::addGlobal('atelier_event', (object)$widgets['nextEvents'][0]);
        }
        
        // Compatibilité : lastPoll (premier sondage)
        if (!empty($widgets['activePolls'])) {
            \Template\Template::addGlobal('lastPoll', (object)($widgets['activePolls'][0] ?? []));
        }

        // 5. Barometer Stats (via hooks)
        $barometerStats = [
            'users' => count(\Core\Auth\User::all()),
            'ideas' => 0,
            'votes' => 0
        ];

        // Utiliser un hook pour que les plugins injectent leurs stats
        $barometerStats = $this->core->callHook('dashboard:inject-barometer-stats', $barometerStats);

        \Template\Template::addGlobal('barometerStats', $barometerStats);

        // --- END DASHBOARD DATA ---
        
        return $this->renderPage();
    }

    public function mentionsLegales()
    {
        $theme = $this->core->getConfigVal('theme');
        $tplFile = THEMES . $theme . '/mentions-legales.tpl';
        if (!file_exists($tplFile)) {
            $tplFile = THEMES . 'default/mentions-legales.tpl';
        }
        if (!file_exists($tplFile)) {
            $tplFile = (defined('ROOT') ? ROOT : '') . 'common/Core/Page/template/mentions-legales.tpl';
        }
        
        $response = new PublicResponse();
        $response->setTitle('Mentions Légales');
        
        $siteName = $this->core->getConfigVal('siteName') ?: 'Déclic';
        $siteUrl = $this->core->getConfigVal('siteUrl') ?: '';

        $tpl = new Template($tplFile);
        $tpl->set('siteName', $siteName);
        $tpl->set('siteUrl', $siteUrl);
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Résolution dynamique d'une page par son slug ou son alias
     * Permet les URLs propres à la racine (ex: /a-propos, /marches-de-noel) sans routes hardcodées.
     */
    public function resolveSlug(string $slug): ?PublicResponse
    {
        $this->page = new Page();
        $pages = $this->page->getItems();
        $normalizedSlug = strtolower(trim($slug, '/'));

        if ($normalizedSlug === '') {
            return null;
        }

        // 1. Recherche par alias personnalisé stocké dans data/core/page/aliases.json s'il existe
        $aliasesFile = (defined('DATA_CORE_PAGE') ? DATA_CORE_PAGE : (ROOT . 'data/core/page/')) . 'aliases.json';
        if (file_exists($aliasesFile)) {
            $aliases = json_decode((string) file_get_contents($aliasesFile), true);
            if (is_array($aliases) && isset($aliases[$normalizedSlug])) {
                $target = $aliases[$normalizedSlug];
                if (is_int($target) || is_numeric($target)) {
                    return $this->read($normalizedSlug, (int) $target);
                }
                if (is_string($target)) {
                    header('Location: /' . ltrim($target, '/'), true, 301);
                    exit;
                }
            }
        }

        // 2. Recherche par correspondance de titre / nom de page converti en slug
        foreach ($pages as $item) {
            if ($item->targetIs() !== 'page') {
                continue;
            }
            $cleanName = preg_replace("#\<i.+\<\/i\>#i", '', (string) $item->getName());
            $itemSlug = strtolower(Util::strToUrl($cleanName));

            if ($itemSlug === $normalizedSlug) {
                return $this->read($itemSlug, $item->getId());
            }
        }

        return null;
    }

    public function read($name, $id)
    {
        $this->page = new Page();
        $pageItem = $this->page->create($id);

        if (!$pageItem instanceof PageItem) {
            $this->core->error404();
        }

        // --- NOUVEAU : Unified Access Policy Enforcement ---
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->core->auth()->getCurrentUser());
        
        if (!$evaluator->canAccess($pageItem, $ctx)) {
            $user = $this->core->auth()->getCurrentUser();
            $this->core->getLogger()->warning("Access denied to Page ID={$id} for " . ($user ? $user->getRole() : 'guest'));
            $this->core->error404(); // Fail-closed
        }
        // ----------------------------------------------------

        $this->pageItem = $pageItem;

        return $this->renderPage();
    }

    protected function renderPage()
    {
        if (!$this->pageItem instanceof PageItem || $this->pageItem->targetIs() !== 'page') {
            $this->core->error404();
        }
        $action = (isset($_POST['unlock'])) ? 'unlock' : '';
        $url = $this->page->makeUrl($this->pageItem);
        if ($action === 'unlock') {
            usleep(350000);

            if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null, false)) {
                \Utils\Show::msg(\Core\Lang::get('core-not-authorized') ?: 'Action non autorisée.', 'error');
            } elseif (isset($_POST['password']) && $this->page->unlock($this->pageItem, (string) $_POST['password'])) {
                \Utils\Show::msg('Page déverrouillée.', 'success');
            } else {
                \Utils\Show::msg('Mot de passe incorrect.', 'error');
            }
            header('location:' . $url);
            die();
        }

        $this->setPageMetas();
        if ($this->page->isUnlocked($this->pageItem)) {
            // template
            $pageFile = ($this->pageItem->getFile()) ? THEMES . $this->core->getConfigVal('theme') . '/' . $this->pageItem->getFile() : false;
        }

        if (isset($pageFile) && $pageFile !== false) {
            if (Util::getFileExtension($this->pageItem->getFile()) === 'tpl') {
                $response = new PublicResponse();
                
                $icon = '';
                $cssClass = $this->pageItem->getCssClass();
                if ($cssClass && strpos($cssClass, 'fa-') !== false) {
                    $icon = '<i class="' . $cssClass . '"></i> ';
                } else {
                    if ($this->pageItem->getIsHomepage()) {
                        $icon = '<i class="fa-solid fa-house"></i> ';
                    } elseif (stripos($this->pageItem->getName(), 'propos') !== false) {
                        $icon = '<i class="fa-solid fa-circle-info"></i> ';
                    } else {
                        // Default icon for pages
                        $icon = '<i class="fa-solid fa-file-lines"></i> ';
                    }
                }
                
                $response->setTitle($icon . $this->pageItem->getName());
                $filephp = preg_replace('"\.tpl$"', '.php', $this->pageItem->getFile());
                $tpl = new Template($pageFile);
                if (file_exists(THEMES . $this->core->getConfigVal('theme') . '/' . $filephp)) {
                    require THEMES . $this->core->getConfigVal('theme') . '/' . $filephp;
                }
            } else {
				$core = $this->core;
                include_once(THEMES . $this->core->getConfigVal('theme') . '/header.php');
                include_once(THEMES . $this->core->getConfigVal('theme') . '/' . $this->pageItem->getFile());
                include_once(THEMES . $this->core->getConfigVal('theme') . '/footer.php');
                die();
            }
        } else {
            $response = new PublicResponse();
            
            $icon = '';
            $cssClass = $this->pageItem->getCssClass();
            if ($cssClass && strpos($cssClass, 'fa-') !== false) {
                $icon = '<i class="' . $cssClass . '"></i> ';
            } else {
                if ($this->pageItem->getIsHomepage()) {
                    $icon = '<i class="fa-solid fa-house"></i> ';
                } elseif (stripos($this->pageItem->getName(), 'propos') !== false) {
                    $icon = '<i class="fa-solid fa-circle-info"></i> ';
                } else {
                    // Default icon for pages
                    $icon = '<i class="fa-solid fa-file-lines"></i> ';
                }
            }

            $response->setTitle($icon . $this->pageItem->getName());
            $tpl = $response->createPluginTemplate('page', 'read');
        }

        $tpl->set('page', $this->page);
        $tpl->set('pageItem', $this->pageItem);
        $tpl->set('sendUrl', $url);
        $tpl->set('_csrfToken', \Core\Security\Csrf::token());

        $response->addTemplate($tpl);
        return $response;
    }

    protected function setPageMetas()
    {
        if ($this->page->isUnlocked($this->pageItem)) {
            # Gestion du titre
            if ($this->core->getConfigVal('hideTitles'))
                $this->core->setMainTitle('');
            else
                $this->core->setMainTitle(($this->pageItem->getMainTitle() != '') ? $this->pageItem->getMainTitle() : $this->pageItem->getName());
            # Gestion des metas
            if ($this->pageItem->getMetaTitleTag())
                $this->core->setTitleTag($this->pageItem->getMetaTitleTag());
            else
                $this->core->setTitleTag($this->pageItem->getName());
            if ($this->pageItem->getMetaDescriptionTag())
                $this->core->setMetaDescription($this->pageItem->getMetaDescriptionTag());
        } else {
            $this->core->setTitleTag('Accès restreint');
            $this->core->setMainTitle('Accès restreint');
        }
    }
}