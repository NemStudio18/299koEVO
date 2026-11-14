<?php

namespace SearchMaps\Services;

defined('ROOT') or exit('Access denied!');

class PoiManager
{
    private static ?string $storagePath = null;

    private static function getFilePath(): string
    {
        if (self::$storagePath === null) {
            $dir = DATA . 'plugin/SearchMaps/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            self::$storagePath = $dir . 'pois.json';
        }
        return self::$storagePath;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public static function getAll(): array
    {
        $file = self::getFilePath();
        if (!file_exists($file)) {
            return [];
        }
        $content = file_get_contents($file);
        if (!$content) {
            return [];
        }
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public static function getById(string $id): ?array
    {
        $pois = self::getAll();
        foreach ($pois as $poi) {
            if (($poi['id'] ?? '') === $id) {
                return $poi;
            }
        }
        return null;
    }

    public static function save(array $poiData): bool
    {
        $pois = self::getAll();
        $id = trim($poiData['id'] ?? '');
        
        if (empty($id)) {
            $id = 'poi-' . substr(md5(uniqid((string)mt_rand(), true)), 0, 10);
            $poiData['id'] = $id;
        }

        $poiData['title'] = htmlspecialchars(trim($poiData['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        $poiData['category'] = htmlspecialchars(trim($poiData['category'] ?? 'Général'), ENT_QUOTES, 'UTF-8');
        $poiData['lat'] = (float)($poiData['lat'] ?? 47.0833);
        $poiData['lon'] = (float)($poiData['lon'] ?? 2.4);
        $poiData['address'] = htmlspecialchars(trim($poiData['address'] ?? ''), ENT_QUOTES, 'UTF-8');
        $poiData['phone'] = htmlspecialchars(trim($poiData['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
        $poiData['website'] = filter_var(trim($poiData['website'] ?? ''), FILTER_SANITIZE_URL);
        $poiData['description'] = htmlspecialchars(trim($poiData['description'] ?? ''), ENT_QUOTES, 'UTF-8');
        $poiData['icon'] = htmlspecialchars(trim($poiData['icon'] ?? 'fa-map-pin'), ENT_QUOTES, 'UTF-8');
        $poiData['color'] = htmlspecialchars(trim($poiData['color'] ?? '#0284c7'), ENT_QUOTES, 'UTF-8');

        $found = false;
        foreach ($pois as $k => $item) {
            if (($item['id'] ?? '') === $id) {
                $pois[$k] = $poiData;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $pois[] = $poiData;
        }

        return (bool)file_put_contents(self::getFilePath(), json_encode($pois, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function delete(string $id): bool
    {
        $pois = self::getAll();
        $filtered = array_values(array_filter($pois, fn($item) => ($item['id'] ?? '') !== $id));
        return (bool)file_put_contents(self::getFilePath(), json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return string[]
     */
    public static function getCategories(): array
    {
        $pois = self::getAll();
        $cats = [];
        foreach ($pois as $poi) {
            $cat = trim($poi['category'] ?? '');
            if ($cat !== '' && !in_array($cat, $cats, true)) {
                $cats[] = $cat;
            }
        }
        sort($cats);
        return $cats;
    }
}

