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
            $tplFile = THEMES . 'VivreBourges/mentions-legales.tpl';
        }
        
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-scale-balanced"></i> Mentions Légales');
        $tpl = new Template($tplFile);
        $response->addTemplate($tpl);
        return $response;
    }

    public function espacePro()
    {
        $theme = $this->core->getConfigVal('theme');
        $tplFile = THEMES . $theme . '/pro.tpl';
        if (!file_exists($tplFile)) {
            $tplFile = THEMES . 'halloween/pro.tpl';
        }
        if (!file_exists($tplFile)) {
            $tplFile = THEMES . 'VivreBourges/pro.tpl';
        }
        if (!file_exists($tplFile)) {
            $tplFile = THEMES . $theme . '/comment-revendiquer.tpl';
        }
        if (!file_exists($tplFile)) {
            $tplFile = PLUGINS . 'SearchUi/template/comment-revendiquer.tpl';
        }
        
        $chatConfig = [
            'crisp_website_id' => '',
            'discord_invite_url' => 'https://discord.gg/8Rpsm2zWH',
            'whatsapp_phone' => '33766471292',
            'whatsapp_message' => "Bonjour, je vous contacte depuis Vivre-Bourges.fr concernant mon établissement : ",
        ];
        $chatConfigFile = ROOT . 'data/config_pro_chat.json';
        if (file_exists($chatConfigFile)) {
            $parsed = json_decode(file_get_contents($chatConfigFile), true);
            if (is_array($parsed)) {
                $chatConfig = array_merge($chatConfig, $parsed);
            }
        }

        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-store"></i> Espace Professionnels & Artisans');
        $response->setMetaDescription("Découvrez comment revendiquer gratuitement votre fiche établissement sur Vivre-Bourges.fr et développer votre visibilité locale.");
        $tpl = new Template($tplFile);
        $tpl->set('proChatWhatsappUrl', 'https://wa.me/' . preg_replace('/[^0-9]/', '', $chatConfig['whatsapp_phone']) . '?text=' . rawurlencode($chatConfig['whatsapp_message']));
        $tpl->set('proChatWhatsappPhone', $chatConfig['whatsapp_phone']);
        $tpl->set('discordInviteUrl', $chatConfig['discord_invite_url'] ?? 'https://discord.gg/8Rpsm2zWH');
        $tpl->set('crispWebsiteId', $chatConfig['crisp_website_id'] ?? '');
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Traitement AJAX du tchat direct /pro envoyé vers Discord Webhook
     */
    public function sendProChatAjax()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // Anti-spam honeypot
        if (!empty($_POST['_gotcha'])) {
            echo json_encode(['success' => true, 'message' => 'Message envoyé']);
            exit;
        }

        $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $contact = trim(filter_input(INPUT_POST, 'contact', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

        if (empty($name) || empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Veuillez renseigner votre nom / établissement et votre message.']);
            exit;
        }

        // Configuration
        $chatConfig = [
            'discord_webhook' => '',
            'whatsapp_phone' => '33600000000',
        ];
        $chatConfigFile = ROOT . 'data/config_pro_chat.json';
        if (file_exists($chatConfigFile)) {
            $parsed = json_decode(file_get_contents($chatConfigFile), true);
            if (is_array($parsed)) {
                $chatConfig = array_merge($chatConfig, $parsed);
            }
        }

        // Sauvegarde locale dans les logs
        $logDir = ROOT . 'data/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';
        $logEntry = date('Y-m-d H:i:s') . " | IP: {$ip} | Pro: {$name} | Contact: {$contact} | Msg: {$message}\n";
        @file_put_contents($logDir . '/pro_chat.log', $logEntry, FILE_APPEND);

        // Envoi vers Discord si Webhook configuré
        $webhookUrl = trim($chatConfig['discord_webhook'] ?? '');
        if (!empty($webhookUrl) && filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            $embed = [
                'title' => '💬 Nouveau message Pro reçu !',
                'color' => 16747586, // Orange #ff8c42
                'description' => "Un artisan / commerçant a posé une question depuis la page **/pro** :",
                'fields' => [
                    [
                        'name' => '👤 Établissement / Nom',
                        'value' => substr($name, 0, 250),
                        'inline' => true,
                    ],
                    [
                        'name' => '📞 Contact (Tél / Email)',
                        'value' => !empty($contact) ? substr($contact, 0, 250) : '_Non renseigné_',
                        'inline' => true,
                    ],
                    [
                        'name' => '✉️ Message',
                        'value' => substr($message, 0, 1000),
                        'inline' => false,
                    ]
                ],
                'footer' => [
                    'text' => 'Vivre-Bourges.fr • Espace Pro',
                ],
                'timestamp' => date('c'),
            ];

            $payload = json_encode([
                'username' => 'Vivre-Bourges Pro',
                'embeds' => [$embed],
            ]);

            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_exec($ch);
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Merci ! Votre message a été transmis directement à l\'équipe. Nous vous répondrons très rapidement.'
        ]);
        exit;
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