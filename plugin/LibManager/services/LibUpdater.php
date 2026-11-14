<?php

namespace LibManager\Services;

class LibUpdater {

    private array $libs;
    private string $logFile;
    private string $libsConfig;
    private string $cacheFile;
    private int $cacheTtl = 3600; // 1 heure de cache

    public function __construct() {
        $this->logFile    = DATA . 'libmanager/updates.log';
        $this->libsConfig = PLUGINS . 'LibManager/param/libs.json';
        $this->cacheFile  = DATA . 'libmanager/versions_cache.json';
        
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->libs = $this->loadLibs();
    }

    public function loadLibs(): array {
        if (!file_exists($this->libsConfig)) return [];
        return json_decode(file_get_contents($this->libsConfig), true) ?? [];
    }

    /**
     * Vérifier toutes les bibliothèques avec cache et requêtes parallèles
     */
    public function checkAll(bool $force = false): array {
        if (!$force && file_exists($this->cacheFile)) {
            $cacheData = json_decode(file_get_contents($this->cacheFile), true);
            if (is_array($cacheData) && isset($cacheData['timestamp'], $cacheData['results'])) {
                if ((time() - $cacheData['timestamp']) < $this->cacheTtl) {
                    // Enrichir avec l'état d'installation actuel sur disque
                    foreach ($cacheData['results'] as $key => &$row) {
                        $lib = $this->libs[$key] ?? null;
                        $row['is_installed'] = $lib ? file_exists(ROOT . $lib['install_path']) : false;
                        $row['current'] = $lib['current_version'] ?? $row['current'];
                        $row['outdated'] = ($row['latest'] && $row['latest'] !== 'N/A') ? version_compare($row['latest'], $row['current'], '>') : false;
                    }
                    unset($row);
                    return $cacheData['results'];
                }
            }
        }

        // Exécution des requêtes en parallèle via curl_multi
        $results = $this->fetchParallelLatestVersions();

        // Enregistrer en cache
        file_put_contents($this->cacheFile, json_encode([
            'timestamp' => time(),
            'checked_at' => date('Y-m-d H:i:s'),
            'results' => $results
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $results;
    }

    /**
     * Récupère la dernière version de toutes les bibliothèques en parallèle via curl_multi
     */
    private function fetchParallelLatestVersions(): array {
        $results = [];
        $mh = curl_multi_init();
        $handles = [];

        foreach ($this->libs as $key => $lib) {
            $url = "https://api.github.com/repos/{$lib['github']}/releases/latest";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'User-Agent: 299Ko-EVO-LibManager/1.0',
                    'Accept: application/vnd.github.v3+json',
                ],
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HEADER         => true,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        // Exécution simultanée
        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc === CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc === CURLM_OK) {
            if (curl_multi_select($mh, 0.5) !== -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc === CURLM_CALL_MULTI_PERFORM);
            }
        }

        $fallbackKeys = [];

        foreach ($handles as $key => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body = substr($response, $headerSize);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            $lib = $this->libs[$key];
            $current = $lib['current_version'];
            $isInstalled = file_exists(ROOT . $lib['install_path']);

            $latest = null;
            $tag = null;
            $rateLimitHit = ($httpCode === 403 && str_contains($body, 'API rate limit exceeded'));

            if ($httpCode === 200 && $body) {
                $data = json_decode($body, true);
                $tag = $data['tag_name'] ?? null;
                if ($tag) {
                    $latest = ltrim($tag, 'v');
                }
            } elseif ($httpCode === 404) {
                // Noter pour fallback vers tags
                $fallbackKeys[] = $key;
            }

            // Si la version release trouvée est plus ancienne que la version courante (ex: mpdf), vérifier les tags
            if ($latest && version_compare($latest, $current, '<')) {
                $fallbackKeys[] = $key;
            }

            $results[$key] = [
                'name'         => $lib['name'],
                'description'  => $lib['description'],
                'type'         => $lib['type'],
                'install_path' => $lib['install_path'],
                'is_installed' => $isInstalled,
                'current'      => $current,
                'latest'       => $latest ?? 'N/A',
                'tag'          => $tag,
                'outdated'     => $latest && version_compare($latest, $current, '>'),
                'error'        => ($latest === null && !$rateLimitHit),
                'rate_limited' => $rateLimitHit,
            ];
        }

        curl_multi_close($mh);

        // Fallback séquentiel doux pour les rares dépôts sans release formelle (ex: tags purs)
        foreach ($fallbackKeys as $key) {
            $lib = $this->libs[$key];
            $tagInfo = $this->fetchLatestTag($lib['github']);
            if ($tagInfo) {
                $results[$key]['latest']   = $tagInfo['version'];
                $results[$key]['tag']      = $tagInfo['tag'];
                $results[$key]['outdated'] = version_compare($tagInfo['version'], $results[$key]['current'], '>');
                $results[$key]['error']    = false;
            }
        }

        return $results;
    }

    private function fetchLatestTag(string $githubRepo): ?array {
        $url = "https://api.github.com/repos/{$githubRepo}/tags";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: 299Ko-EVO-LibManager/1.0',
                'Accept: application/vnd.github.v3+json',
            ],
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $tags = json_decode($response, true);
            if (!empty($tags) && isset($tags[0]['name'])) {
                $tagName = $tags[0]['name'];
                return [
                    'version' => ltrim($tagName, 'v'),
                    'tag'     => $tagName
                ];
            }
        }
        return null;
    }

    /**
     * Mettre à jour une bibliothèque de façon sécurisée
     */
    public function update(string $libKey): array {
        $lib = $this->libs[$libKey] ?? null;
        if (!$lib) {
            return ['success' => false, 'message' => "Bibliothèque inconnue : {$libKey}"];
        }

        // Récupérer la dernière version via cache ou appel direct
        $all = $this->checkAll(true);
        $libInfo = $all[$libKey] ?? null;

        if (!$libInfo || empty($libInfo['latest']) || $libInfo['latest'] === 'N/A') {
            return ['success' => false, 'message' => "Impossible de récupérer la dernière version depuis GitHub pour {$lib['name']}."];
        }

        $latest = $libInfo['latest'];
        $tagName = $libInfo['tag'] ?? ('v' . $latest);

        if (!version_compare($latest, $lib['current_version'], '>')) {
            return ['success' => true, 'message' => "{$lib['name']} est déjà à jour (v{$latest}).", 'already_latest' => true];
        }

        $assetPattern = $lib['asset_pattern'] ?? 'v{version}.tar.gz';
        $assetName = str_replace('{version}', $latest, $assetPattern);

        if ($lib['type'] === 'js' && !str_ends_with($assetName, '.tar.gz') && !str_ends_with($assetName, '.tgz')) {
            $downloadUrl = "https://github.com/{$lib['github']}/releases/download/{$tagName}/{$assetName}";
        } else {
            $downloadUrl = "https://github.com/{$lib['github']}/archive/refs/tags/{$tagName}.tar.gz";
        }

        $tmpFile = sys_get_temp_dir() . '/libmanager_' . $libKey . '_' . $latest . '.tar.gz';
        $downloadRes = $this->download($downloadUrl, $tmpFile);
        if (!$downloadRes['success']) {
            return $downloadRes;
        }

        if (filesize($tmpFile) < 1024) {
            @unlink($tmpFile);
            return ['success' => false, 'message' => 'Fichier téléchargé corrompu ou invalide (taille trop faible).'];
        }

        $installPath = ROOT . $lib['install_path'];
        if (!is_dir($installPath)) {
            mkdir($installPath, 0755, true);
        }

        if ($lib['type'] === 'php') {
            $result = $this->installPhpLib($tmpFile, $installPath, $libKey, $latest);
        } else {
            $result = $this->installJsLib($tmpFile, $installPath, $lib, $latest);
        }

        @unlink($tmpFile);

        if ($result['success']) {
            $this->updateVersion($libKey, $latest);
            $this->log($libKey, $lib['current_version'], $latest);
        }

        return $result;
    }

    private function download(string $url, string $dest): array {
        $fp = fopen($dest, 'w+');
        if (!$fp) return ['success' => false, 'message' => 'Impossible de créer le fichier temporaire'];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['User-Agent: 299Ko-EVO-LibManager/1.0'],
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($httpCode !== 200 || $error) {
            $msg = "Erreur téléchargement HTTP {$httpCode}: {$error}";
            $this->logError($msg . " (URL: {$url})");
            @unlink($dest);
            return ['success' => false, 'message' => $msg];
        }

        return ['success' => true];
    }

    private function installPhpLib(string $archive, string $dest, string $libKey, string $version): array {
        $backup = rtrim($dest, '/') . '_backup_' . date('YmdHis');
        if (is_dir($dest)) {
            rename($dest, $backup);
        }

        $extractDir = sys_get_temp_dir() . '/libmanager_extract_' . $libKey . '_' . time();
        @mkdir($extractDir, 0755, true);

        try {
            $phar = new \PharData($archive);
            $phar->extractTo($extractDir);
        } catch (\Throwable $e) {
            if (is_dir($backup)) rename($backup, $dest);
            $this->rmdir($extractDir);
            return ['success' => false, 'message' => 'Erreur extraction archive: ' . $e->getMessage()];
        }

        $subdirs = glob($extractDir . '/*', GLOB_ONLYDIR);
        if (empty($subdirs)) {
            if (is_dir($backup)) rename($backup, $dest);
            $this->rmdir($extractDir);
            return ['success' => false, 'message' => 'Structure d\'archive GitHub inattendue.'];
        }

        $extractedSource = $subdirs[0];

        // Pour mPDF ou PHPWord : si le repo a un sous-dossier src/, on installe l'arborescence proprement
        if (!rename($extractedSource, $dest)) {
            if (is_dir($backup)) rename($backup, $dest);
            $this->rmdir($extractDir);
            return ['success' => false, 'message' => 'Impossible de déplacer la bibliothèque vers sa destination.'];
        }

        // Préservation des polices existantes si mPDF
        if ($libKey === 'mpdf') {
            $oldFonts = $backup . '/ttfonts';
            $newFonts = $dest . '/ttfonts';
            if (is_dir($oldFonts) && (!is_dir($newFonts) || count(scandir($newFonts)) <= 2)) {
                $this->copyDir($oldFonts, $newFonts);
            }
        }

        // Nettoyage backup
        if (is_dir($backup)) {
            $this->rmdir($backup);
        }
        $this->rmdir($extractDir);

        return ['success' => true, 'message' => "Mise à jour v{$version} installée avec succès.", 'version' => $version];
    }

    private function installJsLib(string $tmpFile, string $dest, array $lib, string $version): array {
        $fileName = $lib['file'] ?? basename($lib['install_path']);
        $targetFile = rtrim($dest, '/') . '/' . $fileName;

        if (file_exists($targetFile)) {
            copy($targetFile, $targetFile . '.backup_' . date('YmdHis'));
        }

        if (!copy($tmpFile, $targetFile)) {
            return ['success' => false, 'message' => 'Impossible de copier le fichier JavaScript vers la destination.'];
        }

        return ['success' => true, 'message' => "Mise à jour v{$version} installée avec succès.", 'version' => $version];
    }

    private function updateVersion(string $libKey, string $version): void {
        $this->libs[$libKey]['current_version'] = $version;
        file_put_contents(
            $this->libsConfig,
            json_encode($this->libs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Mettre à jour le cache
        if (file_exists($this->cacheFile)) {
            $cache = json_decode(file_get_contents($this->cacheFile), true);
            if (isset($cache['results'][$libKey])) {
                $cache['results'][$libKey]['current'] = $version;
                $cache['results'][$libKey]['outdated'] = false;
                file_put_contents($this->cacheFile, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }
    }

    private function copyDir(string $src, string $dst): void {
        @mkdir($dst, 0755, true);
        $files = scandir($src);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $s = $src . '/' . $file;
            $d = $dst . '/' . $file;
            if (is_dir($s)) {
                $this->copyDir($s, $d);
            } else {
                copy($s, $d);
            }
        }
    }

    private function log(string $lib, string $from, string $to): void {
        $line = sprintf("[%s] %s : %s → %s\n", date('Y-m-d H:i:s'), $lib, $from, $to);
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }

    public function getLog(): string {
        if (!file_exists($this->logFile)) return '';
        return file_get_contents($this->logFile);
    }

    private function logError(string $message): void {
        $line = sprintf("[%s] ERROR : %s\n", date('Y-m-d H:i:s'), $message);
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }

    private function rmdir(string $dir): void {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $f) {
            $f->isDir() ? rmdir($f->getRealPath()) : unlink($f->getRealPath());
        }
        @rmdir($dir);
    }
}
