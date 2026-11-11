<?php

namespace Seo\Entities;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

class SocialConfigManager
{
    private const DATA_DIR = DATA_PLUGIN . 'seo/';
    private const DATA_FILE = self::DATA_DIR . 'social.json';
    private const LEGACY_FILE = DATA_PLUGIN . 'seoExtended/config.json';

    private static function ensureStorage(): void
    {
        if (!is_dir(self::DATA_DIR)) {
            @mkdir(self::DATA_DIR, 0755, true);
        }

        if (!file_exists(self::DATA_FILE)) {
            if (file_exists(self::LEGACY_FILE)) {
                $payload = Util::readJsonFile(self::LEGACY_FILE, true);
                Util::writeJsonFile(self::DATA_FILE, $payload);
            } else {
                Util::writeJsonFile(self::DATA_FILE, self::getDefaultConfig());
            }
        }
    }

    private static function getDefaultConfig(): array
    {
        return [
            'facebook' => [
                'enabled' => false,
                'appId' => '',
                'appSecret' => '',
                'accessToken' => '',
            ],
            'x' => [
                'enabled' => false,
                'bearerToken' => '',
            ],
            'linkedin' => [
                'enabled' => false,
                'clientId' => '',
                'clientSecret' => '',
                'accessToken' => '',
            ],
            'languages' => [],
        ];
    }

    public static function getAll(): array
    {
        self::ensureStorage();
        $config = Util::readJsonFile(self::DATA_FILE, true);
        if (!is_array($config) || empty($config)) {
            $config = self::getDefaultConfig();
        } else {
            $config = array_merge(self::getDefaultConfig(), $config);
        }
        return $config;
    }

    public static function saveAll(array $config): bool
    {
        self::ensureStorage();
        $config = array_merge(self::getDefaultConfig(), $config);
        return Util::writeJsonFile(self::DATA_FILE, $config);
    }

    public static function getEnabledNetworks(): array
    {
        $config = self::getAll();
        $enabled = [];
        foreach (['facebook', 'x', 'linkedin'] as $network) {
            if (!empty($config[$network]['enabled'])) {
                $enabled[$network] = true;
            }
        }
        return $enabled;
    }

    public static function getLanguages(): array
    {
        $config = self::getAll();
        if (empty($config['languages']) || !is_array($config['languages'])) {
            return [];
        }
        return array_values(array_filter(array_map('trim', $config['languages'])));
    }

    public static function postToNetwork(string $network, array $postData): bool
    {
        $cfg = self::getAll();
        if (empty($cfg[$network]) || empty($cfg[$network]['enabled'])) {
            return false;
        }

        $title = htmlspecialchars($postData['name'] ?? '');
        $content = htmlspecialchars(substr(strip_tags($postData['content'] ?? ''), 0, 280));
        $core = \Core\Core::getInstance();
        $url = $core->getConfigVal('siteUrl') . ($postData['url'] ?? '/');
        $image = $postData['img'] ?? $core->getConfigVal('themeUrl') . '/img/default.jpg';

        return match ($network) {
            'facebook' => self::postToFacebook($cfg['facebook'], $title, $content, $url, $image),
            'x'        => self::postToX($cfg['x'], $title, $content, $url),
            'linkedin' => self::postToLinkedIn($cfg['linkedin'], $title, $content, $url, $image),
            default    => false,
        };
    }

    private static function postToFacebook(array $config, string $title, string $content, string $url, string $image): bool
    {
        if (empty($config['accessToken'])) {
            return false;
        }

        $data = [
            'message' => $title . "\n\n" . $content,
            'link'    => $url,
            'picture' => $image,
        ];

        $ch = curl_init('https://graph.facebook.com/v18.0/me/feed');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $config['accessToken']],
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    private static function postToX(array $config, string $title, string $content, string $url): bool
    {
        if (empty($config['bearerToken'])) {
            return false;
        }

        $tweet = trim($title . "\n\n" . $content . "\n\n" . $url);
        if (mb_strlen($tweet) > 280) {
            $tweet = mb_substr($tweet, 0, 277) . '…';
        }

        $ch = curl_init('https://api.twitter.com/2/tweets');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['text' => $tweet]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $config['bearerToken'],
                'Content-Type: application/json',
            ],
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 201;
    }

    private static function postToLinkedIn(array $config, string $title, string $content, string $url, string $image): bool
    {
        if (empty($config['accessToken']) || empty($config['clientId'])) {
            return false;
        }

        $payload = [
            'author' => 'urn:li:person:' . $config['clientId'],
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => ['text' => $title . "\n\n" . $content],
                    'shareMediaCategory' => 'ARTICLE',
                    'media' => [[
                        'status' => 'READY',
                        'description' => ['text' => $title],
                        'originalUrl' => $url,
                        'title' => ['text' => $title],
                        'thumbnails' => [['resolvedUrl' => $image]],
                    ]],
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $ch = curl_init('https://api.linkedin.com/v2/ugcPosts');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $config['accessToken'],
                'Content-Type: application/json',
                'X-Restli-Protocol-Version: 2.0.0',
            ],
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 201;
    }
}

