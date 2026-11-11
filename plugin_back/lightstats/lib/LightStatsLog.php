<?php

namespace LightStats\Lib;

use donatj\UserAgent\UserAgentParser;

defined('ROOT') or exit('No direct script access allowed');

class LightStatsLog {
    
    protected $log;
    
    protected $parser;
    
    protected $ua;
    
    public $ip;
    public $page;
    public $referer;
    public $date;
    public $platform;
    public $browser;
    public $browserVersion;
    public $isBot;

    public function __construct($log) {
        $this->log = $log;
        $this->parser = new UserAgentParser();
        $this->loadInfos();
    }
    
    protected function loadInfos() {
        $this->ua = $this->parser->parse($this->log['userAgent']);
        $this->platform = $this->ua->platform();
        $this->browser = $this->ua->browser();
        $this->browserVersion = $this->ua->browserVersion();
        
        $this->ip = $this->log['ip'];
        $this->page = $this->log['page'];
        $this->referer = $this->log['referer'];
        $this->date = $this->log['date'];
        $this->isBot = $this->log['isBot'];
    }
}