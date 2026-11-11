<?php
namespace LocalFeed\Services;

/**
 * Bulletins météo du Mur de Bourges.
 *
 * Deux bulletins par jour (06h00 et 20h00) présentant les prévisions des 24 heures suivantes.
 * Chaque bulletin est figé (snapshot) dans un fichier JSON afin d'apparaître dans le flux
 * comme une véritable publication datée, les anciens bulletins glissant naturellement vers le bas.
 */
class WeatherBulletinService {

    private const LAT = 47.0810;
    private const LON = 2.3988;
    private const SLOTS = [6, 20];
    private const KEEP = 6; // 3 jours d'historique

    private string $file;

    public function __construct() {
        $dir = (defined('DATA') ? DATA : dirname(__DIR__, 4) . '/data/') . 'plugin/LocalFeed/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $this->file = $dir . 'weather_bulletins.json';
    }

    /**
     * Timestamp du créneau de bulletin courant (dernier 06h ou 20h écoulé).
     */
    public function currentSlot(?int $now = null): int {
        $now = $now ?? time();
        $hour = (int)date('G', $now);
        $today = strtotime(date('Y-m-d', $now));
        if ($hour >= 20) return $today + 20 * 3600;
        if ($hour >= 6) return $today + 6 * 3600;
        return $today - 4 * 3600; // veille 20h
    }

    /**
     * Retourne les bulletins (le plus récent en premier), en générant le bulletin du créneau courant si absent.
     */
    public function getBulletins(): array {
        $bulletins = $this->load();
        $slot = $this->currentSlot();
        if (!isset($bulletins[(string)$slot])) {
            $generated = $this->generate($slot);
            if ($generated) {
                $bulletins = $this->load(); // relu après écriture verrouillée
            }
        }
        krsort($bulletins);
        return array_values($bulletins);
    }

    /**
     * Génère et enregistre le bulletin pour un créneau donné. Retourne le bulletin ou null.
     */
    public function generate(int $slot, bool $force = false): ?array {
        $fp = @fopen($this->file . '.lock', 'c');
        if ($fp && !flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return null; // une autre requête génère déjà le bulletin
        }
        try {
            $bulletins = $this->load();
            if (!$force && isset($bulletins[(string)$slot])) {
                return $bulletins[(string)$slot];
            }
            $hours = $this->fetchHourly($slot);
            if (count($hours) < 12) {
                return null;
            }
            $bulletin = $this->buildBulletin($slot, $hours);
            $bulletins[(string)$slot] = $bulletin;
            krsort($bulletins);
            $bulletins = array_slice($bulletins, 0, self::KEEP, true);
            @file_put_contents($this->file, json_encode($bulletins, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
            return $bulletin;
        } finally {
            if ($fp) {
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
    }

    private function load(): array {
        if (!is_file($this->file)) return [];
        $data = json_decode((string)@file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    /**
     * Récupère 24 heures de prévisions horaires à partir du créneau.
     */
    private function fetchHourly(int $slot): array {
        $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
            'latitude' => self::LAT,
            'longitude' => self::LON,
            'hourly' => 'temperature_2m,weather_code,precipitation_probability,precipitation,wind_speed_10m,wind_gusts_10m',
            'timezone' => 'Europe/Paris',
            'past_days' => 1,
            'forecast_days' => 3,
        ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_FOLLOWLOCATION => true]);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$raw || $code !== 200) return [];
        $data = json_decode($raw, true);
        if (empty($data['hourly']['time'])) return [];

        $h = $data['hourly'];
        $out = [];
        foreach ($h['time'] as $i => $t) {
            $ts = strtotime($t);
            if ($ts < $slot || $ts >= $slot + 24 * 3600) continue;
            $out[] = [
                'ts' => $ts,
                'temp' => $h['temperature_2m'][$i] ?? null,
                'code' => $h['weather_code'][$i] ?? null,
                'pop' => (int)($h['precipitation_probability'][$i] ?? 0),
                'rain' => (float)($h['precipitation'][$i] ?? 0),
                'wind' => (float)($h['wind_speed_10m'][$i] ?? 0),
                'gust' => (float)($h['wind_gusts_10m'][$i] ?? 0),
            ];
        }
        return $out;
    }

    private function buildBulletin(int $slot, array $hours): array {
        $temps = array_filter(array_column($hours, 'temp'), fn($v) => $v !== null);
        $tmin = (int)round(min($temps));
        $tmax = (int)round(max($temps));
        $popMax = max(array_column($hours, 'pop'));
        $rainTotal = round(array_sum(array_column($hours, 'rain')), 1);
        $gustMax = (int)round(max(array_column($hours, 'gust')));

        // Découpage en périodes dans l'ordre chronologique depuis le créneau
        $periods = [];
        foreach ($hours as $hr) {
            $label = $this->periodLabel((int)date('G', $hr['ts']));
            $dayKey = date('Y-m-d', $hr['ts']) . '_' . $label;
            if (!isset($periods[$dayKey])) {
                $periods[$dayKey] = ['label' => $label, 'temps' => [], 'codes' => [], 'pop' => 0, 'ts' => $hr['ts']];
            }
            if ($hr['temp'] !== null) $periods[$dayKey]['temps'][] = $hr['temp'];
            if ($hr['code'] !== null) $periods[$dayKey]['codes'][] = (int)$hr['code'];
            $periods[$dayKey]['pop'] = max($periods[$dayKey]['pop'], $hr['pop']);
        }
        $periodList = [];
        foreach ($periods as $p) {
            if (count($p['temps']) < 2) continue;
            $code = $this->dominantCode($p['codes']);
            $isNight = in_array($p['label'], ['Nuit', 'Soirée'], true);
            $dayPrefix = (date('Y-m-d', $p['ts']) !== date('Y-m-d', $slot)) ? 'Demain ' : '';
            $periodList[] = [
                'label' => $dayPrefix . mb_strtolower($p['label']),
                'temp' => (int)round(array_sum($p['temps']) / count($p['temps'])),
                'icon' => $this->icon($code, $isNight),
                'desc' => $this->describe($code),
                'pop' => $p['pop'],
            ];
        }
        foreach ($periodList as &$pl) {
            $pl['label'] = mb_strtoupper(mb_substr($pl['label'], 0, 1)) . mb_substr($pl['label'], 1);
        }
        unset($pl);

        $mainCode = $this->dominantCode(array_filter(array_column($hours, 'code'), fn($v) => $v !== null));
        $isMorning = (int)date('G', $slot) === 6;

        // Conseils pratiques
        $tips = [];
        if ($popMax >= 60 || $rainTotal >= 3) $tips[] = '☂️ Pensez au parapluie : jusqu\'à ' . $popMax . ' % de risque de pluie (' . $rainTotal . ' mm attendus).';
        elseif ($popMax >= 30) $tips[] = '🌂 Quelques averses possibles (' . $popMax . ' %), un petit parapluie peut servir.';
        if ($tmin <= 0) $tips[] = '🧊 Risque de gel : attention au verglas sur les trottoirs et les routes.';
        elseif ($tmin <= 4) $tips[] = '🧣 Fraîcheur marquée, sortez bien couvert.';
        if ($tmax >= 30) $tips[] = '🥵 Forte chaleur : hydratez-vous et recherchez l\'ombre aux heures chaudes.';
        if ($gustMax >= 60) $tips[] = '💨 Rafales jusqu\'à ' . $gustMax . ' km/h, prudence aux abords des arbres.';
        elseif ($gustMax >= 45) $tips[] = '🍃 Vent soutenu avec rafales à ' . $gustMax . ' km/h.';
        if (empty($tips) && in_array($mainCode, [0, 1], true)) $tips[] = '😎 Belle journée en perspective, idéale pour flâner dans le centre historique ou aux Marais.';
        if (empty($tips)) $tips[] = '👍 Pas de phénomène particulier attendu.';

        // Détection de nuance : si à la fois soleil et nuages présents
        $allCodes = array_map('intval', array_filter(array_column($hours, 'code'), fn($v) => $v !== null));
        $hasSun = false;
        $hasClouds = false;
        foreach ($allCodes as $c) {
            if ($c === 0 || $c === 1) $hasSun = true;
            if ($c === 2 || $c === 3) $hasClouds = true;
        }

        $descText = $this->describe($mainCode);
        $iconText = $this->icon($mainCode, !$isMorning);
        if ($hasSun && $hasClouds && in_array($mainCode, [0, 1, 2], true)) {
            $descText = 'Éclaircies et passages nuageux';
            $iconText = '⛅';
        }

        $title = $isMorning
            ? 'Bonjour Bourges ! La météo de votre journée'
            : 'Bonsoir Bourges ! La météo de la nuit et de demain';
        $summary = sprintf(
            '%s %s, températures entre %d°C et %d°C sur les prochaines 24 heures.',
            $iconText,
            $descText,
            $tmin,
            $tmax
        );

        $mascot = $this->mascotImage($mainCode, $tmin, $popMax, $rainTotal);

        return [
            'slot' => $slot,
            'generated_at' => time(),
            'is_morning' => $isMorning,
            'title' => $title,
            'summary' => $summary,
            'tmin' => $tmin,
            'tmax' => $tmax,
            'pop_max' => $popMax,
            'rain_total' => $rainTotal,
            'gust_max' => $gustMax,
            'main_icon' => $this->icon($mainCode, !$isMorning),
            'mascot' => $mascot,
            'periods' => array_slice($periodList, 0, 5),
            'tips' => $tips,
        ];
    }

    /**
     * Sélectionne What (la mascotte) adaptée au temps du jour à Bourges.
     */
    public function mascotImage(int $mainCode, int $tmin, int $popMax, float $rainTotal): array {
        // 1. Froid / Neige (tmin <= 3°C ou codes neige)
        if ($tmin <= 3 || ($mainCode >= 71 && $mainCode <= 86)) {
            return [
                'webp' => '/images/mascot/what_froid_neige.webp',
                'png'  => '/images/mascot/what_froid_neige.png',
                'alt'  => 'What bien au chaud (froid / neige)',
                'mood' => 'froid'
            ];
        }
        // 2. Pluvieux / averses / précipitations marquées
        if ($popMax >= 45 || $rainTotal >= 1.0 || in_array($mainCode, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99], true)) {
            return [
                'webp' => '/images/mascot/what_pluvieux.webp',
                'png'  => '/images/mascot/what_pluvieux.png',
                'alt'  => 'What avec son parapluie sous la pluie',
                'mood' => 'pluie'
            ];
        }
        // 3. Ensoleillé / temps sec / agréable
        return [
            'webp' => '/images/mascot/what_ensoleille.webp',
            'png'  => '/images/mascot/what_ensoleille.png',
            'alt'  => 'What avec ses lunettes de soleil',
            'mood' => 'soleil'
        ];
    }

    private function periodLabel(int $hour): string {
        if ($hour >= 6 && $hour < 12) return 'Matinée';
        if ($hour >= 12 && $hour < 18) return 'Après-midi';
        if ($hour >= 18) return 'Soirée';
        return 'Nuit';
    }

    /**
     * Code météo le plus « significatif » : le plus fréquent, en privilégiant les précipitations.
     */
    private function dominantCode(array $codes): int {
        if (empty($codes)) return 2;
        $counts = array_count_values(array_map('intval', $codes));
        arsort($counts);
        $top = (int)array_key_first($counts);
        // Si un phénomène de précipitation couvre au moins 3 h, il prime
        foreach ($counts as $c => $n) {
            if ($c >= 51 && $n >= 3 && $c > $top) return (int)$c;
        }
        return $top;
    }

    private function describe(int $code): string {
        return match (true) {
            $code === 0 => 'Grand soleil',
            $code === 1 => 'Ciel dégagé',
            $code === 2 => 'Éclaircies',
            $code === 3 => 'Ciel couvert',
            in_array($code, [45, 48], true) => 'Brouillard',
            in_array($code, [51, 53, 55], true) => 'Bruine',
            in_array($code, [56, 57, 66, 67], true) => 'Pluie verglaçante',
            in_array($code, [61, 63], true) => 'Pluie',
            $code === 65 => 'Forte pluie',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => 'Neige',
            in_array($code, [80, 81, 82], true) => 'Averses',
            in_array($code, [95, 96, 99], true) => 'Orages',
            default => 'Temps variable',
        };
    }

    private function icon(int $code, bool $night = false): string {
        return match (true) {
            $code === 0 => $night ? '🌙' : '☀️',
            $code === 1 => $night ? '🌙' : '🌤️',
            $code === 2 => $night ? '☁️' : '⛅',
            $code === 3 => '☁️',
            in_array($code, [45, 48], true) => '🌫️',
            in_array($code, [51, 53, 55], true) => '🌦️',
            in_array($code, [56, 57, 66, 67], true) => '🌨️',
            in_array($code, [61, 63, 65, 80, 81, 82], true) => '🌧️',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => '❄️',
            in_array($code, [95, 96, 99], true) => '⛈️',
            default => '🌤️',
        };
    }
}
