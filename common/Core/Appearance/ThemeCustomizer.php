<?php

namespace Core\Appearance;

use Core\Core;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

/**
 * Gestionnaire universel de personnalisation sans code des thèmes (Theme Customizer).
 * Surcharge proprement les Design Tokens (--evo-*) sans modifier les fichiers du thème.
 */
class ThemeCustomizer
{
    private static ?self $instance = null;
    private string $storageFile;

    private const ALLOWED_FONTS = [
        'default'   => ['label' => 'Police par défaut du thème', 'stack' => ''],
        'system'    => ['label' => 'System UI Moderne', 'stack' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'],
        'jakarta'   => ['label' => 'Plus Jakarta Sans / Inter', 'stack' => '"Plus Jakarta Sans", Inter, system-ui, sans-serif'],
        'serif'     => ['label' => 'Éditorial Serif (Georgia / Playfair)', 'stack' => 'Georgia, "Playfair Display", "Times New Roman", serif'],
        'humanist'  => ['label' => 'Humaniste (Trebuchet /Gill Sans)', 'stack' => '"Gill Sans", "Trebuchet MS", system-ui, sans-serif'],
        'monospace' => ['label' => 'Technique Monospace', 'stack' => '"JetBrains Mono", "Fira Code", ui-monospace, monospace'],
    ];

    public function __construct()
    {
        $this->storageFile = DATA_CORE_SETTINGS . 'theme_customizer.json';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getActiveThemeSlug(): string
    {
        return (string) (Core::getInstance()->getConfigVal('theme') ?: 'default');
    }

    /**
     * Retourne le schéma déclaratif combinant les options universelles --evo-*
     * et les options éventuelles exposées par le thème actif dans infos.json ("customizer").
     */
    public function getSchema(?string $themeSlug = null): array
    {
        $slug = $themeSlug ?: $this->getActiveThemeSlug();
        $fontOptions = [];
        foreach (self::ALLOWED_FONTS as $key => $meta) {
            $fontOptions[$key] = $meta['label'];
        }

        $universalSchema = [
            'accent_color' => [
                'type' => 'color',
                'label' => 'Couleur principale d\'accentuation',
                'description' => 'Pilote les boutons principaux, liens actifs et mises en avant (--evo-primary / --evo-accent).',
                'default' => '',
                'css_var' => '--evo-accent',
            ],
            'accent_hover_color' => [
                'type' => 'color',
                'label' => 'Couleur principale au survol',
                'description' => 'Teinte appliquée lors du survol des boutons d\'action.',
                'default' => '',
                'css_var' => '--evo-accent-hover',
            ],
            'font_family' => [
                'type' => 'select',
                'label' => 'Typographie générale',
                'description' => 'Police appliquée au corps de texte du site.',
                'options' => $fontOptions,
                'default' => 'default',
            ],
            'heading_font_family' => [
                'type' => 'select',
                'label' => 'Typographie des titres (H1–H3)',
                'description' => 'Police appliquée aux grands titres des pages et sections.',
                'options' => $fontOptions,
                'default' => 'default',
            ],
            'border_radius' => [
                'type' => 'range',
                'label' => 'Arrondi des cartes et boutons (px)',
                'description' => 'Ajuste la douceur des angles (--evo-radius-md). Laisser à -1 pour garder le réglage du thème.',
                'min' => -1,
                'max' => 28,
                'step' => 1,
                'default' => -1,
            ],
            'content_max_width' => [
                'type' => 'select',
                'label' => 'Largeur maximale du contenu',
                'description' => 'Contrôle l\'amplitude visuelle des sections de pages.',
                'options' => [
                    'default' => 'Par défaut du thème',
                    '1024px'  => 'Compacte / Lecture (1024 px)',
                    '1200px'  => 'Standard équilibrée (1200 px)',
                    '1360px'  => 'Large panoramique (1360 px)',
                ],
                'default' => 'default',
            ],
        ];

        $infosPath = THEMES . $slug . '/infos.json';
        $infos = Util::readJsonFile($infosPath, true);
        if (is_array($infos) && isset($infos['customizer']) && is_array($infos['customizer'])) {
            foreach ($infos['customizer'] as $k => $def) {
                if (is_string($k) && preg_match('/^[a-zA-Z0-9_-]{1,48}$/', $k) && is_array($def)) {
                    $universalSchema[$k] = array_merge($universalSchema[$k] ?? [], $def);
                }
            }
        }

        return $universalSchema;
    }

    public function getValues(?string $themeSlug = null): array
    {
        $slug = $themeSlug ?: $this->getActiveThemeSlug();
        $schema = $this->getSchema($slug);
        $allStored = Util::readJsonFile($this->storageFile, true);
        $stored = (is_array($allStored) && isset($allStored[$slug]) && is_array($allStored[$slug]))
            ? $allStored[$slug]
            : [];

        $resolved = [];
        foreach ($schema as $key => $field) {
            $resolved[$key] = array_key_exists($key, $stored) ? $stored[$key] : ($field['default'] ?? '');
        }
        return $resolved;
    }

    /**
     * Valide strictement chaque champ selon le schéma déclaré avant persistance JSON.
     */
    public function saveValues(array $input, ?string $themeSlug = null): bool
    {
        $slug = $themeSlug ?: $this->getActiveThemeSlug();
        $schema = $this->getSchema($slug);
        $clean = [];

        foreach ($schema as $key => $field) {
            $type = $field['type'] ?? 'text';
            if (!array_key_exists($key, $input) && $type !== 'boolean') {
                continue;
            }
            $raw = $input[$key] ?? null;

            switch ($type) {
                case 'color':
                    $val = trim((string) $raw);
                    if ($val === '' || preg_match('/^#[0-9a-fA-F]{6}$/', $val)) {
                        $clean[$key] = strtolower($val);
                    }
                    break;

                case 'select':
                    $val = (string) $raw;
                    $options = is_array($field['options'] ?? null) ? $field['options'] : [];
                    if (array_key_exists($val, $options)) {
                        $clean[$key] = $val;
                    }
                    break;

                case 'range':
                case 'number':
                    $min = isset($field['min']) ? (int) $field['min'] : 0;
                    $max = isset($field['max']) ? (int) $field['max'] : 100;
                    $val = max($min, min($max, (int) $raw));
                    $clean[$key] = $val;
                    break;

                case 'boolean':
                    $clean[$key] = !empty($raw) && $raw !== '0' && $raw !== 'false';
                    break;

                default:
                    $clean[$key] = mb_substr(trim(strip_tags((string) $raw)), 0, 180);
                    break;
            }
        }

        $allStored = Util::readJsonFile($this->storageFile, true);
        if (!is_array($allStored)) {
            $allStored = [];
        }
        $allStored[$slug] = $clean;
        return (bool) Util::writeJsonFile($this->storageFile, $allStored);
    }

    public function resetValues(?string $themeSlug = null): bool
    {
        $slug = $themeSlug ?: $this->getActiveThemeSlug();
        $allStored = Util::readJsonFile($this->storageFile, true);
        if (!is_array($allStored)) {
            return true;
        }
        unset($allStored[$slug]);
        return (bool) Util::writeJsonFile($this->storageFile, $allStored);
    }

    /**
     * Génère la feuille de surcharge CSS propre basée exclusivement sur des variables/sélecteurs sûrs.
     */
    public function buildCssOverrides(?string $themeSlug = null): string
    {
        $slug = $themeSlug ?: $this->getActiveThemeSlug();
        $schema = $this->getSchema($slug);
        $values = $this->getValues($slug);

        $vars = [];
        $extraRules = [];

        $accent = (string) ($values['accent_color'] ?? '');
        if ($accent !== '' && preg_match('/^#[0-9a-f]{6}$/', $accent)) {
            $vars[] = "--evo-accent: {$accent}";
            $vars[] = "--evo-primary: {$accent}";
            $vars[] = "--evo-accent-text: " . self::pickReadableContrast($accent);
        }

        $accentHover = (string) ($values['accent_hover_color'] ?? '');
        if ($accentHover !== '' && preg_match('/^#[0-9a-f]{6}$/', $accentHover)) {
            $vars[] = "--evo-accent-hover: {$accentHover}";
            $vars[] = "--evo-primary-hover: {$accentHover}";
        }

        $radius = isset($values['border_radius']) ? (int) $values['border_radius'] : -1;
        if ($radius >= 0 && $radius <= 28) {
            $sm = max(0, (int) round($radius * 0.65));
            $lg = min(36, (int) round($radius * 1.25));
            $vars[] = "--evo-radius: {$radius}px";
            $vars[] = "--evo-radius-sm: {$sm}px";
            $vars[] = "--evo-radius-md: {$radius}px";
            $vars[] = "--evo-radius-lg: {$lg}px";
        }

        $bodyFontKey = (string) ($values['font_family'] ?? 'default');
        if ($bodyFontKey !== 'default' && !empty(self::ALLOWED_FONTS[$bodyFontKey]['stack'])) {
            $stack = self::ALLOWED_FONTS[$bodyFontKey]['stack'];
            $vars[] = "--evo-font-body: {$stack}";
            $extraRules[] = "body { font-family: var(--evo-font-body); }";
        }

        $headingFontKey = (string) ($values['heading_font_family'] ?? 'default');
        if ($headingFontKey !== 'default' && !empty(self::ALLOWED_FONTS[$headingFontKey]['stack'])) {
            $stack = self::ALLOWED_FONTS[$headingFontKey]['stack'];
            $vars[] = "--evo-font-heading: {$stack}";
            $extraRules[] = "h1, h2, h3 { font-family: var(--evo-font-heading); }";
        }

        $maxWidth = (string) ($values['content_max_width'] ?? 'default');
        if (in_array($maxWidth, ['1024px', '1200px', '1360px'], true)) {
            $vars[] = "--evo-container-max: {$maxWidth}";
            $extraRules[] = ".evo-pb-canvas, .homepage-container, .standard-page-container, #body-page { max-width: var(--evo-container-max); }";
        }

        // Support des éventuelles variables CSS déclarées par un thème tiers dans infos.json
        foreach ($schema as $key => $field) {
            if (in_array($key, ['accent_color', 'accent_hover_color', 'border_radius', 'font_family', 'heading_font_family', 'content_max_width'], true)) {
                continue;
            }
            $cssVar = $field['css_var'] ?? '';
            $val = $values[$key] ?? null;
            if (is_string($cssVar) && preg_match('/^--[a-zA-Z0-9_-]{2,48}$/', $cssVar) && is_string($val) && preg_match('/^#[0-9a-fA-F]{6}$/', $val)) {
                $vars[] = "{$cssVar}: " . strtolower($val);
            }
        }

        if (empty($vars) && empty($extraRules)) {
            return '';
        }

        $css = '';
        if (!empty($vars)) {
            $css .= ':root, html[data-theme="light"], html[data-theme="dark"] {' . implode('; ', $vars) . ';}';
        }
        if (!empty($extraRules)) {
            $css .= ' ' . implode(' ', $extraRules);
        }
        return $css;
    }

    public static function renderHeadStyleTag($param = ''): string
    {
        $output = (string) $param;
        $css = self::getInstance()->buildCssOverrides();
        if ($css === '') {
            return $output;
        }
        return $output . "\n" . '<style id="evo-theme-customizer">' . $css . '</style>';
    }

    private static function pickReadableContrast(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        $lum = 0.2126 * ($r <= 0.03928 ? $r / 12.92 : (($r + 0.055) / 1.055) ** 2.4)
             + 0.7152 * ($g <= 0.03928 ? $g / 12.92 : (($g + 0.055) / 1.055) ** 2.4)
             + 0.0722 * ($b <= 0.03928 ? $b / 12.92 : (($b + 0.055) / 1.055) ** 2.4);
        return $lum > 0.42 ? '#0f172a' : '#ffffff';
    }
}
