<?php

namespace LightStats {

use Utils\Util;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
        @mkdir(DATA_PLUGIN . 'lightstats/logs', 0755, true);
        @chmod(DATA_PLUGIN . 'lightstats/logs', 0755);
    }

    public static function addScript(): void
    {
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.2.1/dist/chart.umd.min.js"></script>';
    }

    public static function addVisitor(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isBot = self::isBot($userAgent);
        $date = date('Y-m-d H:i:s');

        $folder = DATA_PLUGIN . 'lightstats/logs/' . date('Y/m') . '/';
        if (!is_dir($folder)) {
            @mkdir($folder, 0755, true);
        }

        $filename = $folder . date('d') . '.json';
        $logs = is_file($filename) ? Util::readJsonFile($filename, true) : [];
        if (!is_array($logs)) {
            $logs = [];
        }

        $logs[] = [
            'ip' => $ip,
            'page' => $_SERVER['REQUEST_URI'] ?? '',
            'referer' => $referer,
            'userAgent' => $userAgent,
            'isBot' => $isBot,
            'date' => $date,
        ];

        Util::writeJsonFile($filename, $logs);
    }

    private static function isBot(string $userAgent): bool
    {
        // Si user-agent vide, c'est probablement un bot
        if (empty(trim($userAgent))) {
            return true;
        }
        
        $bots = [
            // Moteurs de recherche
            'Googlebot',
            'Bingbot',
            'Yahoo',
            'Baiduspider',
            'YandexBot',
            'Applebot',
            'DuckDuckBot',
            'Slurp',
            'Sogou',
            'Exabot',
            'AhrefsBot',
            'SemrushBot',
            'MJ12bot',
            'DotBot',
            // Réseaux sociaux
            'facebookexternalhit',  // Bot de prévisualisation Facebook
            'Facebot',
            'Twitterbot',
            'LinkedInBot',
            'WhatsApp',
            'TelegramBot',
            'SkypeUriPreview',
            'Slackbot',
            'Discordbot',
            // Outils de scraping/curl
            'curl',
            'wget',
            'python-requests',
            'python-urllib',
            'Go-http-client',
            'Java',
            'Apache-HttpClient',
            'PostmanRuntime',
            'Insomnia',
            // Autres bots connus
            'bot',
            'crawler',
            'spider',
            'scraper',
            'monitor',
            'check',
            'validator',
            'uptime',
            'pingdom',
            'GTmetrix',
            'PageSpeed',
        ];
        
        $userAgentLower = strtolower($userAgent);
        foreach ($bots as $bot) {
            if (stripos($userAgentLower, strtolower($bot)) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

}

namespace {

    function lightstatsInstall(): void
    {
        \LightStats\Hooks::install();
    }

}