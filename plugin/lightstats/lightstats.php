<?php

namespace LightStats {

use Utils\Util;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public const BOT_VERIFIED     = 'verified_bot';     // Moteurs majeurs vérifiés par reverse DNS (Googlebot, Bingbot)
    public const BOT_DECLARED     = 'declared_bot';     // Crawlers déclarant une signature officielle (Applebot, Semrush...)
    public const BOT_TOOL         = 'suspected_tool';   // Outils CLI (curl, python, scrapers, faux Googlebot)
    public const BOT_UNDETERMINED = 'undetermined';     // User-Agent vide ou inhabituel (ni bot avéré, ni humain certain)
    public const BOT_HUMAN        = 'probable_human';   // Navigateur standard avec headers cohérents

    private static array $verifiedDnsCache = [];

    public static function install(): void
    {
        @mkdir(DATA_PLUGIN . 'lightstats/logs', 0755, true);
        @chmod(DATA_PLUGIN . 'lightstats/logs', 0755);
    }

    public static function addScript(): void
    {
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.2.1/dist/chart.umd.min.js"></script>';
    }

    public static function addFrontTracker(): void
    {
        echo '<script src="/plugin/lightstats/assets/js/privacy-tracker.js" defer></script>';
    }

    /**
     * Détection des attaques et scans de vulnérabilités (extensions et fichiers sensibles)
     */
    public static function isSuspiciousRequest(string $page, string $query = ''): bool
    {
        $uri = strtolower($page . ($query !== '' ? '?' . $query : ''));
        
        // Extensions de fichiers sensibles ou d'archives souvent sondées
        if (preg_match('/\.(sql|sql\.gz|bak|backup|old|save|swp|temp|tmp|env|yml|yaml|ini|conf|config|cfg|sh|bash|key|pem|crt|cer|pfx|p12|id_rsa|log|tar|tar\.gz|tgz|zip|rar|7z)(\?|$)/i', $uri)) {
            return true;
        }

        // Fichiers et chemins d'attaque classiques
        if (preg_match('/(wp-config|phpinfo|web\.xml|settings\.py|\.aws\/|bedrock|phpmyadmin|adminer|eval-stdin|invokefunction|database\.sql|\.git\/)/i', $uri)) {
            return true;
        }

        return false;
    }

    /**
     * Détermine si une page fait partie des routes exclues du suivi analytique public :
     * - administration (/admin*)
     * - authentification / gestion de compte (/users/*, /user/*)
     * - environnements de tests / routes de test (.test, .local, /test*, /tests*)
     */
    public static function isExcludedPublicRoute(string $page): bool
    {
        // Routes administration
        if (strpos($page, '/admin/') === 0 || $page === '/admin') {
            return true;
        }

        // Routes utilisateurs système (login, register, logout, profile)
        if (preg_match('#^/(users|user)(/|$)#i', $page)) {
            return true;
        }

        // Routes de test et environnements d'essai
        if (preg_match('#(\.test|\.local|/test(/|$)|/tests(/|$))#i', $page)) {
            return true;
        }

        return false;
    }

    public static function addVisitor($param = null): void
    {
        // Anti-double comptage au sein de la même requête HTTP
        if (!empty($GLOBALS['_lightstats_logged'])) {
            return;
        }
        $GLOBALS['_lightstats_logged'] = true;

        $initialBufferLevel = ob_get_level();
        $pluginBufferOpened = false;

        // La collecte ne doit JAMAIS perturber le rendu public
        try {
            $context = is_array($param) ? $param : [];

            if (class_exists('\Core\Auth\AdminTestMode')
                && \Core\Auth\AdminTestMode::getInstance()->isActive()) {
                return;
            }

            $rawIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $rawReferer = $_SERVER['HTTP_REFERER'] ?? '';
            $userAgent = trim($_SERVER['HTTP_USER_AGENT'] ?? '');
            $date = date('Y-m-d H:i:s');

            // 1. Découpage URI propre et méthode HTTP
            [$page, $query] = self::splitRequestUri($_SERVER['REQUEST_URI'] ?? '/');
            $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

            // 2. Classification multi-niveaux du client (bot / humain / outil / scan)
            $classification = self::classifyClient($userAgent, $rawIp, $page, $query);
            $botCategory = $classification['category'];
            // Seuls les vrais navigateurs humains sont comptabilisés comme non-bot
            $isBot = ($botCategory !== self::BOT_HUMAN);

            // 3. Détection Administrateur (authentifié côté serveur)
            $isAdminUser = false;
            if (defined('IS_ADMIN') && IS_ADMIN) {
                $isAdminUser = true;
            } elseif (!empty($_SESSION['user']) && !empty($_SESSION['user']['admin'])) {
                $isAdminUser = true;
            } elseif (class_exists('\Core\Auth\UsersManager', false)) {
                $u = \Core\Auth\UsersManager::getCurrentUser();
                if ($u && method_exists($u, 'isAdmin') && $u->isAdmin()) {
                    $isAdminUser = true;
                }
            }
            $isAdminPage = (strpos($page, '/admin/') === 0 || $page === '/admin');
            $isAdmin = $isAdminUser || $isAdminPage;

            // 4. Statut HTTP observé au moment du hook
            $statusCode = http_response_code();
            $statusCode = is_int($statusCode) ? $statusCode : 200;

            // 5. Minimisation RGPD : anonymisation de l'IP
            $anonymizedIp = self::anonymizeIp($rawIp);
            $dailyHash = self::getDailyVisitorHash($rawIp);

            // 6. Minimisation RGPD : nettoyage du référent (suppression des paramètres privés et tokens)
            $cleanReferer = self::sanitizeReferer($rawReferer);

            // 7. Gestion unifiée de session côté serveur (PHP Maître)
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            $sessionId = $_COOKIE['vb_sid'] ?? null;
            if (!is_string($sessionId) || !preg_match('/^[a-f0-9]{32}$/', $sessionId)) {
                $sessionId = bin2hex(random_bytes(16));
            }

            // Renouvellement / émission du cookie technique de session (30 min)
            if (!headers_sent()) {
                setcookie('vb_sid', $sessionId, [
                    'expires'  => time() + 1800,
                    'path'     => '/',
                    'secure'   => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            // 8. Génération d'un request_id unique pour cette réponse HTML précise
            $requestId = 'req_' . bin2hex(random_bytes(16));

            // 9. Injection dynamique du contexte dans le HTML sans modifier le cache partagé
            // Gère uniquement le buffer propre au plugin (niveau initial conservé)
            $pluginBufferOpened = ob_start(function($buffer) use ($requestId, $sessionId) {
                // N'injecter que dans les pages HTML
                if (stripos($buffer, '<html') === false && stripos($buffer, '<!DOCTYPE') === false) {
                    return $buffer;
                }
                $contextScript = '<script id="vb-ctx">window.__VB_CTX__={reqId:"' . htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8') . '",sid:"' . htmlspecialchars($sessionId, ENT_QUOTES, 'UTF-8') . '"};</script>';

                // 1. Si le HTML contient déjà un contexte (ex: page servie depuis le cache HTML contenant l'ID d'une précédente requête),
                // on REMPLACE impérativement l'ancien script par le script dynamique frais de CETTE requête précise !
                if (preg_match('/<script id="vb-ctx">.*?<\/script>/is', $buffer)) {
                    return preg_replace('/<script id="vb-ctx">.*?<\/script>/is', $contextScript, $buffer, 1);
                }
                if (stripos($buffer, 'window.__VB_CTX__') !== false) {
                    return preg_replace('/<script[^>]*>.*?window\.__VB_CTX__.*?<\/script>/is', $contextScript, $buffer, 1);
                }

                // 2. Insertion prioritaire avant </head>
                if (stripos($buffer, '</head>') !== false) {
                    return preg_replace('/<\/head>/i', $contextScript . '</head>', $buffer, 1);
                }

                // 2. Fallback si pas de </head> : insertion immédiate après la balise <body...>
                if (preg_match('/<body[^>]*>/i', $buffer)) {
                    return preg_replace('/(<body[^>]*>)/i', '$1' . $contextScript, $buffer, 1);
                }

                // 3. Fallback si pas de balise <body> : insertion avant </html>
                if (stripos($buffer, '</html>') !== false) {
                    return preg_replace('/<\/html>/i', $contextScript . '</html>', $buffer, 1);
                }

                // 4. Échec structurel : document HTML sans balise standard exploitable
                // Politique stricte : ne pas injecter artificiellement dans des flux malformés
                error_log('[LightStats] Diagnostic : Document HTML complet sans balises structurelles exploitables (</head>, <body>, </html>). Contexte non injecté (page non certifiable).');
                return $buffer;
            });

            $hasJs = !empty($_COOKIE['vb_sid']);

            // 10. Dossier de stockage journalier
            $folder = DATA_PLUGIN . 'lightstats/logs/' . date('Y/m') . '/';
            if (!is_dir($folder) && !@mkdir($folder, 0755, true) && !is_dir($folder)) {
                error_log('[LightStats] Impossible de créer le dossier de logs : ' . $folder);
                return;
            }

            // Écriture append-only JSONL
            $filename = $folder . date('d') . '.jsonl';

            $ok = self::writeLogEntry($filename, [
                'method' => $requestMethod,
                'ip' => $anonymizedIp,
                'ipHash' => $dailyHash,
                'page' => $page,
                'query' => $query,
                'referer' => $cleanReferer,
                'userAgent' => $userAgent,
                'isBot' => $isBot,
                'botCategory' => $botCategory,
                'date' => $date,
                'status' => $statusCode,
                'isAdmin' => $isAdmin,
                'hasJs' => $hasJs,
                'sessionId' => $sessionId,
                'requestId' => $requestId,
            ]);

            if (!$ok) {
                error_log('[LightStats] Echec ecriture du log : ' . $filename);
            }

            // Enregistrement dans le moteur Analytics (Mode C) strictement pour les vraies pages publiques humaines conformes AdSense (GET, 200 OK, non-admin, non-bot, routes publiques non-système)
            if ($requestMethod === 'GET' && !$isBot && !$isAdmin && $statusCode === 200 && !self::isSuspiciousRequest($page, $query) && !self::isExcludedPublicRoute($page)) {
                try {
                    require_once __DIR__ . '/lib/LightStatsAnalyticsService.php';
                    $visitorId = $_COOKIE['vb_vid'] ?? null;
                    $analytics = \LightStats\Lib\LightStatsAnalyticsService::getInstance();
                    $analytics->recordPageview(
                        $page,
                        $dailyHash,
                        $visitorId,
                        $sessionId,
                        $cleanReferer,
                        $userAgent,
                        ['query' => $query, 'cached' => !empty($context['cached'])],
                        $requestId
                    );
                    if (mt_rand(1, 200) === 1) {
                        $analytics->purgeOldData(30);
                    }
                } catch (\Throwable $e) {
                    error_log('[LightStatsAnalytics] Hook error: ' . $e->getMessage());
                }
            }

            // Purge automatique probabiliste (1 chance sur 200 à chaque visite)
            if (mt_rand(1, 200) === 1) {
                self::purgeOldLogs(30);
            }
        } catch (\Throwable $e) {
            // Sécurité : si une exception survient, fermer UNIQUEMENT le buffer du plugin s'il a été ouvert
            if ($pluginBufferOpened && ob_get_level() > $initialBufferLevel) {
                @ob_end_clean();
            }
            error_log('[LightStats] Erreur enregistrement de la visite : ' . $e->getMessage());
        }
    }

    /**
     * Classification multi-niveaux du visiteur
     */
    public static function classifyClient(string $userAgent, string $ip, string $page = '', string $query = ''): array
    {
        // 1. Détection immédiate des requêtes d'attaque / sondage de fichiers
        if ($page !== '' && self::isSuspiciousRequest($page, $query)) {
            return [
                'category' => self::BOT_TOOL,
                'name' => 'Scanner de vulnérabilités'
            ];
        }

        if ($userAgent === '') {
            return [
                'category' => self::BOT_UNDETERMINED,
                'name' => 'User-Agent vide'
            ];
        }

        $uaLower = strtolower($userAgent);

        // Outils CLI et bibliothèques de scraping (suspected_tool)
        $tools = [
            'curl/', 'wget/', 'python-requests', 'python-urllib', 'aiohttp',
            'go-http-client', 'apache-httpclient', 'postmanruntime', 'insomnia',
            'libwww-perl', 'scrapy', 'headlesschrome', 'phantomjs', 'selenium',
            'puppeteer', 'playwright', 'zgrab', 'masscan', 'nmap', 'censys'
        ];
        foreach ($tools as $tool) {
            if (strpos($uaLower, $tool) !== false) {
                return [
                    'category' => self::BOT_TOOL,
                    'name' => $tool
                ];
            }
        }

        // Vérification renforcée pour Googlebot et Bingbot (reverse DNS conforme aux specs officielles)
        if (strpos($uaLower, 'googlebot') !== false) {
            if (self::verifyDnsBot($ip, ['.googlebot.com', '.google.com'])) {
                return ['category' => self::BOT_VERIFIED, 'name' => 'Googlebot (Vérifié)'];
            }
            return ['category' => self::BOT_TOOL, 'name' => 'Googlebot (Faux / Imposteur)'];
        }

        // Crawlers Google spécifiques distincts de Googlebot
        if (strpos($uaLower, 'googleother') !== false) {
            return ['category' => self::BOT_DECLARED, 'name' => 'GoogleOther (Crawler Google)'];
        }
        if (strpos($uaLower, 'google-inspectiontool') !== false) {
            return ['category' => self::BOT_DECLARED, 'name' => 'Google InspectionTool'];
        }

        if (strpos($uaLower, 'bingbot') !== false) {
            if (self::verifyDnsBot($ip, ['.search.msn.com'])) {
                return ['category' => self::BOT_VERIFIED, 'name' => 'Bingbot (Vérifié)'];
            }
            return ['category' => self::BOT_TOOL, 'name' => 'Bingbot (Faux / Imposteur)'];
        }

        // Crawlers déclarés officiels (declared_bot)
        $knownBots = [
            'ahrefsbot' => 'AhrefsBot',
            'semrushbot' => 'SemrushBot',
            'applebot' => 'Applebot',
            'duckduckbot' => 'DuckDuckBot',
            'yandexbot' => 'YandexBot',
            'baiduspider' => 'BaiduSpider',
            'facebookexternalhit' => 'Facebook Preview',
            'facebot' => 'Facebot',
            'twitterbot' => 'Twitterbot',
            'linkedinbot' => 'LinkedInBot',
            'slackbot' => 'Slackbot',
            'discordbot' => 'Discordbot',
            'telegrambot' => 'TelegramBot',
            'whatsapp' => 'WhatsApp Preview',
            'mj12bot' => 'MJ12bot',
            'dotbot' => 'DotBot',
            'exabot' => 'Exabot',
            'uptimerobot' => 'UptimeRobot',
            'pingdom' => 'Pingdom',
            'gtmetrix' => 'GTmetrix',
            // Nouveaux bots IA et scrapers intensifs
            'bytespider' => 'ByteDance ByteSpider',
            'gptbot' => 'OpenAI GPTBot',
            'chatgpt-user' => 'ChatGPT User',
            'claudebot' => 'Anthropic ClaudeBot',
            'anthropic-ai' => 'Anthropic AI',
            'perplexitybot' => 'PerplexityBot',
            'amazonbot' => 'AmazonBot',
            'cohere-ai' => 'Cohere AI',
            'dataforseobot' => 'DataForSeoBot',
            'blexbot' => 'BLEXBot',
            // Crawlers additionnels découverts
            'halobot' => 'HaloBot',
            'petalbot' => 'PetalBot',
            'turnitinbot' => 'TurnitinBot',
            'zoominfobot' => 'ZoominfoBot',
            'megaindex' => 'MegaIndex',
            'seekport' => 'Seekport',
            'criteo' => 'CriteoBot',
            'screaming frog' => 'Screaming Frog SEO Spider'
        ];

        foreach ($knownBots as $needle => $name) {
            if (strpos($uaLower, $needle) !== false) {
                return ['category' => self::BOT_DECLARED, 'name' => $name];
            }
        }

        // Regex ciblée pour attraper tout mot se terminant par 'bot' (HaloBot, TurnitinBot, robot...) ou contenant crawler/spider/scraper
        // Ne matche pas 'bottom' ou 'bottle' grâce au délimiteur de fin de mot \b
        if (preg_match('/([a-z0-9_-]*bot\b|crawler|spider|scraper|archiver|crawling)/i', $userAgent)) {
            return ['category' => self::BOT_DECLARED, 'name' => 'Crawler générique'];
        }

        // Vérification de signature d'un vrai navigateur (pour exclure les scripts custom sans signature)
        if (!preg_match('/(Mozilla|Opera|Safari|Chrome|Firefox|Edge|WebKit)/i', $userAgent)) {
            return ['category' => self::BOT_UNDETERMINED, 'name' => 'Client non-navigateur'];
        }

        // Navigateur standard humain
        return ['category' => self::BOT_HUMAN, 'name' => null];
    }

    /**
     * Vérification reverse DNS conforme (Google / Microsoft Bing) avec mise en cache statique
     */
    private static function verifyDnsBot(string $ip, array $allowedDomains): bool
    {
        if (isset(self::$verifiedDnsCache[$ip])) {
            return self::$verifiedDnsCache[$ip];
        }

        // Éviter les appels réseau bloquants sur IP locale ou privée
        if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
            self::$verifiedDnsCache[$ip] = false;
            return false;
        }

        $hostname = @gethostbyaddr($ip);
        if (!$hostname || $hostname === $ip) {
            self::$verifiedDnsCache[$ip] = false;
            return false;
        }

        $validDomain = false;
        foreach ($allowedDomains as $domain) {
            if (str_ends_with($hostname, $domain)) {
                $validDomain = true;
                break;
            }
        }

        if (!$validDomain) {
            self::$verifiedDnsCache[$ip] = false;
            return false;
        }

        // Forward DNS check pour vérifier la correspondance inverse
        $forwardIp = @gethostbyname($hostname);
        $isLegit = ($forwardIp === $ip);
        self::$verifiedDnsCache[$ip] = $isLegit;
        return $isLegit;
    }

    /**
     * Minimisation RGPD de l'IP : masque le dernier octet IPv4 ou les 80 derniers bits IPv6
     */
    public static function anonymizeIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return count($parts) >= 3 ? $parts[0] . ':' . $parts[1] . ':' . $parts[2] . '::' : '::';
        }

        return '0.0.0.0';
    }

    /**
     * Génère une empreinte anonyme rotative journalière pour le calcul des visiteurs uniques du jour.
     */
    public static function getDailyVisitorHash(string $ip): string
    {
        $dailySalt = defined('ROOT') ? ROOT . date('Y-m-d') : 'lightstats_' . date('Y-m-d');
        return substr(hash('sha256', $ip . $dailySalt), 0, 16);
    }

    /**
     * Nettoyage du référent pour ne conserver que le protocole, le domaine et le chemin.
     */
    public static function sanitizeReferer(string $referer): string
    {
        if (empty($referer)) {
            return '';
        }

        $parts = parse_url($referer);
        if (!$parts || !isset($parts['host'])) {
            return '';
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $path = $parts['path'] ?? '/';

        return $scheme . '://' . $host . $path;
    }

    /**
     * Sépare le chemin et la query string en filtrant les traceurs marketing
     */
    public static function splitRequestUri(string $uri): array
    {
        $uri = (string) strtok($uri, '#');
        $qPos = strpos($uri, '?');
        $path = $qPos === false ? $uri : substr($uri, 0, $qPos);
        $query = $qPos === false ? '' : substr($uri, $qPos + 1);
        if ($path === '') {
            $path = '/';
        }

        if ($query !== '') {
            $kept = [];
            foreach (explode('&', $query) as $segment) {
                if ($segment === '') {
                    continue;
                }
                $key = strtolower(urldecode((string) strtok($segment, '=')));
                if (self::isTrackerParam($key)) {
                    continue;
                }
                $kept[] = $segment;
            }
            $query = implode('&', $kept);
        }
        return [$path, $query];
    }

    private static function isTrackerParam(string $key): bool
    {
        if (strpos($key, 'utm_') === 0) {
            return true;
        }
        return in_array($key, ['fbclid', 'gclid', 'dclid', 'msclkid', 'yclid', '_ga', '_gl', 'mc_cid', 'mc_eid'], true);
    }

    /**
     * Écriture atomique JSONL append-only.
     */
    public static function writeLogEntry(string $filename, array $entry): bool
    {
        $json = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return false;
        }
        return @file_put_contents($filename, $json . "\n", FILE_APPEND | LOCK_EX) !== false;
    }

    /**
     * Purge automatique des fichiers de logs bruts de plus de X jours (conformité CNIL / RGPD)
     */
    public static function purgeOldLogs(int $daysToKeep = 30): void
    {
        $baseDir = DATA_PLUGIN . 'lightstats/logs/';
        if (!is_dir($baseDir)) {
            return;
        }

        $cutoffTimestamp = strtotime("-{$daysToKeep} days");

        $years = glob($baseDir . '*', GLOB_ONLYDIR) ?: [];
        foreach ($years as $yearDir) {
            $months = glob($yearDir . '/*', GLOB_ONLYDIR) ?: [];
            foreach ($months as $monthDir) {
                $files = array_merge(glob($monthDir . '/*.json') ?: [], glob($monthDir . '/*.jsonl') ?: []);
                foreach ($files as $file) {
                    if (filemtime($file) < $cutoffTimestamp) {
                        @unlink($file);
                    }
                }
                // Nettoyer le dossier de mois s'il est vide
                if (count(glob($monthDir . '/*')) === 0) {
                    @rmdir($monthDir);
                }
            }
            // Nettoyer le dossier d'année s'il est vide
            if (count(glob($yearDir . '/*')) === 0) {
                @rmdir($yearDir);
            }
        }
    }
}

}

namespace {

    function lightstatsInstall(): void
    {
        \LightStats\Hooks::install();
    }

}
