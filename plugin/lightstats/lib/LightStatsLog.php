<?php

namespace LightStats\Lib;

use donatj\UserAgent\UserAgentParser;

if (!function_exists('donatj\UserAgent\parse_user_agent')) {
    require_once __DIR__ . '/UserAgent/UserAgentInterface.php';
    require_once __DIR__ . '/UserAgent/Browsers.php';
    require_once __DIR__ . '/UserAgent/Platforms.php';
    require_once __DIR__ . '/UserAgent/UserAgent.php';
    require_once __DIR__ . '/UserAgent/UserAgentParser.php';
    require_once __DIR__ . '/UserAgentParser.php';
}

class LightStatsLog {
    
    protected $log;
    
    protected $parser;
    
    protected $ua;
    
    public $ip;
    public $ipHash;
    public $page;
    public $query;
    public $referer;
    public $date;
    public $platform;
    public $browser;
    public $browserVersion;
    public $isBot;
    public $botCategory;
    public $status;
    public $isAdmin;
    public $hasJs;
    public $sessionId;

    public function __construct($log) {
        $this->log = $log;
        $this->parser = new UserAgentParser();
        $this->loadInfos();
    }
    
    protected function loadInfos() {
        $rawUa = $this->log['userAgent'] ?? '';
        $this->ua = $this->parser->parse($rawUa);
        $this->platform = $this->ua->platform();
        $this->browser = $this->ua->browser();
        $this->browserVersion = $this->ua->browserVersion();
        
        $this->ip = $this->log['ip'] ?? 'unknown';
        $this->ipHash = $this->log['ipHash'] ?? null;
        $this->page = $this->log['page'] ?? '/';
        $this->query = $this->log['query'] ?? null;
        $this->referer = $this->log['referer'] ?? '';
        $this->date = $this->log['date'] ?? '1970-01-01 00:00:00';
        $this->isBot = (bool)($this->log['isBot'] ?? false);
        $this->botCategory = $this->log['botCategory'] ?? ($this->isBot ? 'declared_bot' : 'probable_human');
        $this->status = isset($this->log['status']) && is_int($this->log['status']) ? $this->log['status'] : null;
        
        $this->isAdmin = (bool)($this->log['isAdmin'] ?? false);
        if (!$this->isAdmin && (strpos($this->page, '/admin/') === 0 || $this->page === '/admin')) {
            $this->isAdmin = true;
        }
        $this->hasJs = (bool)($this->log['hasJs'] ?? false);
        $this->sessionId = $this->log['sessionId'] ?? null;

        // Rétrocompatibilité / Détection renforcée sur logs historiques
        if (!$this->isBot) {
            if ($rawUa !== '' && preg_match('/([a-z0-9_-]*bot\b|crawler|spider|scraper|archiver|headlesschrome|phantomjs|selenium|puppeteer|crawling|scan|probe|httpclient|curl|wget|python|urllib|guzzle)/i', $rawUa)) {
                $this->isBot = true;
                $this->botCategory = 'declared_bot';
            } elseif ($this->isSuspiciousUri($this->page, (string)($this->query ?? ''))) {
                $this->isBot = true;
                $this->botCategory = 'suspected_tool';
            }
        }
    }

    private function isSuspiciousUri(string $page, string $query = ''): bool
    {
        $uri = strtolower($page . ($query !== '' ? '?' . $query : ''));
        return (bool)preg_match('/\.(sql|sql\.gz|bak|backup|old|save|swp|temp|tmp|env|yml|yaml|ini|conf|config|cfg|sh|bash|key|pem|crt|cer|pfx|p12|id_rsa|log|tar|tar\.gz|tgz|zip|rar|7z)(\?|$)/i', $uri)
            || (bool)preg_match('/(wp-config|phpinfo|web\.xml|settings\.py|\.aws\/|bedrock|phpmyadmin|adminer)/i', $uri);
    }
}

