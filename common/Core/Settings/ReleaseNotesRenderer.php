<?php

namespace Core\Settings;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

/**
 * Rendu automatique en direct des Notes de Version depuis l'API GitHub Releases
 * avec cache disque résilient (fallback hors-ligne / anti rate-limit).
 */
class ReleaseNotesRenderer
{
    private const DEFAULT_REPO = 'NemStudio18/299koEVO';
    private const CACHE_TTL = 900; // 15 minutes

    public static function renderShortcode(array $attrs = []): string
    {
        $repo = isset($attrs['repo']) && preg_match('#^[a-zA-Z0-9_.-]+/[a-zA-Z0-9_.-]+$#', (string) $attrs['repo'])
            ? (string) $attrs['repo']
            : self::DEFAULT_REPO;
        $limit = isset($attrs['limit']) ? max(1, min(25, (int) $attrs['limit'])) : 12;

        $releases = self::fetchReleases($repo, $limit);
        if (empty($releases)) {
            return '<div class="evo-release-card"><p>Les notes de version sont momentanément indisponibles. Consultez directement <a href="https://github.com/' . htmlspecialchars($repo, ENT_QUOTES, 'UTF-8') . '/releases" target="_blank" rel="noopener">les publications officielles sur GitHub</a>.</p></div>';
        }

        $monthsFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        $html = '<div class="evo-live-releases">';
        foreach ($releases as $idx => $rel) {
            if (!is_array($rel) || !empty($rel['draft'])) {
                continue;
            }
            $tag = (string) ($rel['tag_name'] ?? '');
            $name = trim((string) ($rel['name'] ?? $tag));
            if ($name === '') {
                $name = $tag;
            }
            $htmlUrl = (string) ($rel['html_url'] ?? ('https://github.com/' . $repo . '/releases/tag/' . rawurlencode($tag)));
            $publishedAt = (string) ($rel['published_at'] ?? ($rel['created_at'] ?? ''));
            $ts = $publishedAt !== '' ? strtotime($publishedAt) : false;
            $dateFr = $ts ? ((int) date('j', $ts) . ' ' . ($monthsFr[(int) date('n', $ts)] ?? date('F', $ts)) . ' ' . date('Y', $ts)) : '';

            // Détecter l'archive ZIP attachée ou fallback zipball
            $downloadUrl = (string) ($rel['zipball_url'] ?? $htmlUrl);
            if (!empty($rel['assets']) && is_array($rel['assets'])) {
                foreach ($rel['assets'] as $asset) {
                    if (!empty($asset['browser_download_url']) && str_ends_with(strtolower((string) ($asset['name'] ?? '')), '.zip')) {
                        $downloadUrl = (string) $asset['browser_download_url'];
                        break;
                    }
                }
            }

            $isLatest = ($idx === 0);
            $badgeHtml = $isLatest
                ? '<span class="evo-pill" style="background: #10b981; color: #ffffff; font-weight: 700; font-size: 0.8rem;">VERSION ACTUELLE</span>'
                : '<span class="evo-pill" style="font-weight: 600; font-size: 0.8rem;">ARCHIVE STABLE</span>';

            $bodyMd = trim((string) ($rel['body'] ?? ''));
            $bodyHtml = $bodyMd !== ''
                ? self::markdownToHtml($bodyMd)
                : '<p style="color: var(--evo-text-muted, #64748b);">Consultez le détail complet des commits associés à cette version sur GitHub.</p>';

            $html .= '<article class="evo-release-card" id="release-' . htmlspecialchars(preg_replace('/[^a-zA-Z0-9_.-]/', '-', $tag), ENT_QUOTES, 'UTF-8') . '" style="margin-bottom: 2rem;">';
            $html .= '<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--evo-border, #e2e8f0); padding-bottom: 1.25rem; margin-bottom: 1.5rem; gap: 1rem;">';
            $html .= '<div>';
            $html .= '<div style="display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem; margin-bottom: 0.35rem;">';
            $html .= $badgeHtml;
            $html .= '<h2 style="margin: 0; font-size: 1.55rem; color: var(--evo-text);">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</h2>';
            $html .= '</div>';
            $html .= '<span style="font-size: 0.9rem; color: var(--evo-text-muted, #64748b);">';
            if ($dateFr !== '') {
                $html .= '<i class="fa-regular fa-calendar"></i> Publiée le ' . htmlspecialchars($dateFr, ENT_QUOTES, 'UTF-8') . ' &bull; ';
            }
            $html .= 'Tag Git : <code>' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '</code>';
            $html .= '</span>';
            $html .= '</div>';
            $html .= '<div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">';
            $html .= '<a class="evo-btn-primary button" href="' . htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') . '" style="padding: 0.6rem 1.15rem; font-size: 0.9rem;">';
            $html .= '<i class="fa-solid fa-download"></i> Télécharger ' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '</a>';
            $html .= '<a class="evo-btn-secondary button secondary" href="' . htmlspecialchars($htmlUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" style="padding: 0.6rem 1.15rem; font-size: 0.9rem;">';
            $html .= '<i class="fa-brands fa-github"></i> Release GitHub</a>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="evo-release-body">' . $bodyHtml . '</div>';
            $html .= '</article>';
        }
        $html .= '</div>';

        return $html;
    }

    private static function fetchReleases(string $repo, int $limit): array
    {
        $cacheDir = DATA . 'cache' . DS;
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $cacheFile = $cacheDir . 'github_releases_' . md5($repo) . '.json';
        $cached = Util::readJsonFile($cacheFile, true);

        if (is_array($cached) && isset($cached['fetched_at'], $cached['items']) && (time() - (int) $cached['fetched_at'] < self::CACHE_TTL)) {
            return array_slice($cached['items'], 0, $limit);
        }

        $apiUrl = 'https://api.github.com/repos/' . $repo . '/releases?per_page=' . max(10, $limit);
        $raw = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/vnd.github+json',
                    'User-Agent: 299koEVO-ReleaseNotesRenderer/1.0',
                ],
            ]);
            $resp = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (is_string($resp) && $code >= 200 && $code < 300) {
                $raw = $resp;
            }
        }

        if ($raw !== null) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                Util::writeJsonFile($cacheFile, [
                    'fetched_at' => time(),
                    'items' => $decoded,
                ]);
                return array_slice($decoded, 0, $limit);
            }
        }

        // Fallback résilient sur le dernier cache connu même expiré
        if (is_array($cached) && !empty($cached['items']) && is_array($cached['items'])) {
            return array_slice($cached['items'], 0, $limit);
        }

        return [];
    }

    private static function markdownToHtml(string $md): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $md) ?: [];
        $out = [];
        $inList = false;

        $formatInline = static function (string $text): string {
            $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            $safe = preg_replace('/`([^`]+)`/', '<code>$1</code>', $safe);
            $safe = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $safe);
            $safe = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $safe);
            $safe = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/', static function ($m) {
                return '<a href="' . $m[2] . '" target="_blank" rel="noopener">' . $m[1] . '</a>';
            }, $safe);
            return $safe;
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || $trimmed === '---') {
                if ($inList) {
                    $out[] = '</ul>';
                    $inList = false;
                }
                continue;
            }

            if (preg_match('/^###\s+(.+)$/', $trimmed, $m)) {
                if ($inList) { $out[] = '</ul>'; $inList = false; }
                $out[] = '<h4 style="color: var(--evo-accent, #1d4ed8); margin: 1.25rem 0 0.55rem; font-size: 1.08rem;">' . $formatInline($m[1]) . '</h4>';
            } elseif (preg_match('/^##\s+(.+)$/', $trimmed, $m)) {
                if ($inList) { $out[] = '</ul>'; $inList = false; }
                $out[] = '<h3 style="color: var(--evo-accent, #1d4ed8); margin: 1.5rem 0 0.65rem; font-size: 1.22rem;">' . $formatInline($m[1]) . '</h3>';
            } elseif (preg_match('/^#\s+(.+)$/', $trimmed, $m)) {
                if ($inList) { $out[] = '</ul>'; $inList = false; }
                $out[] = '<h3 style="margin: 1.5rem 0 0.65rem; font-size: 1.3rem;">' . $formatInline($m[1]) . '</h3>';
            } elseif (preg_match('/^[-*]\s+(.+)$/', $trimmed, $m)) {
                if (!$inList) {
                    $out[] = '<ul style="margin: 0.4rem 0 1rem 1.25rem; line-height: 1.7;">';
                    $inList = true;
                }
                $out[] = '<li>' . $formatInline($m[1]) . '</li>';
            } else {
                if ($inList) { $out[] = '</ul>'; $inList = false; }
                $out[] = '<p style="margin: 0.5rem 0;">' . $formatInline($trimmed) . '</p>';
            }
        }

        if ($inList) {
            $out[] = '</ul>';
        }

        return implode("\n", $out);
    }
}
