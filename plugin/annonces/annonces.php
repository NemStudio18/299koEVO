<?php

use Core\Core;
use Utils\Util;

defined('ROOT') OR exit('Access denied!');

spl_autoload_register(function (string $class) {
    if (strpos($class, 'Annonces\\') === 0) {
        $rel = substr($class, strlen('Annonces\\'));
        $parts = explode('\\', $rel);
        if (count($parts) >= 2) {
            $folder = strtolower(array_shift($parts));
            $file = __DIR__ . DS . $folder . DS . implode(DS, $parts) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        }
    }
});

/**
 * Installation du plugin Petites Annonces.
 */
function annoncesInstall(): void
{
    $dataDir = DATA_PLUGIN . 'annonces' . DS;
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    if (!is_file($dataDir . 'annonces.json')) {
        Util::writeJsonFile($dataDir . 'annonces.json', []);
    }
    if (!is_file($dataDir . 'reservations.json')) {
        Util::writeJsonFile($dataDir . 'reservations.json', []);
    }
    $uploadDir = UPLOAD . 'annonces' . DS;
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
}

/**
 * Injecte la feuille de style publique du plugin sur les pages /annonces.
 */
function annoncesEndFrontHead(string $param = ''): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/annonces') === false) {
        return $param;
    }
    $siteUrl = Core::getInstance()->getConfigVal('siteUrl');
    $cssFile = __DIR__ . '/template/public.css';
    $v = is_file($cssFile) ? (string) filemtime($cssFile) : VERSION;
    return $param . '<link rel="stylesheet" href="' . $siteUrl . '/plugin/annonces/template/public.css?v=' . $v . '">' . "\n";
}

/**
 * Hook de recherche globale 299search pour remonter les annonces publiées.
 */
function annoncesSearchResultsHook(array $data): array
{
    $query = mb_strtolower(trim((string) ($data['query'] ?? '')));
    if ($query === '') {
        return $data;
    }

    $items = \Annonces\Entities\Annonce::all();
    foreach ($items as $item) {
        if (!$item->isPublic()) {
            continue;
        }
        $title = (string) ($item->attributes['title'] ?? '');
        $desc = strip_tags((string) ($item->attributes['description'] ?? ''));
        $cat = (string) ($item->attributes['category'] ?? '');
        $haystack = mb_strtolower($title . ' ' . $desc . ' ' . $cat);
        if (mb_strpos($haystack, $query) !== false) {
            $data['results'][] = [
                'title'   => $title,
                'url'     => $item->getPublicUrl(),
                'excerpt' => mb_substr(trim($desc), 0, 140),
                'source'  => 'Petites Annonces',
            ];
        }
    }
    return $data;
}

