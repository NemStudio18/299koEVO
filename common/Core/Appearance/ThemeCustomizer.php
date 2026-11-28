<?php

namespace Core\Appearance;

use Core\Core;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

/**
 * Gestionnaire universel de personnalisation sans code des thèmes (Theme Customizer).
 * Pilote l'ensemble des Design Tokens (--evo-*, --dc-*, --ag-*) en mode Clair et Sombre
 * sans modifier aucun fichier source du thème.
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
        'humanist'  => ['label' => 'Humaniste (Gill Sans / Trebuchet)', 'stack' => '"Gill Sans", "Trebuchet MS", system-ui, sans-serif'],
        'monospace' => ['label' => 'Technique Monospace', 'stack' => '"JetBrains Mono", "Fira Code", ui-monospace, monospace'],
    ];

    private const ALLOWED_WIDTHS = [
        'default' => 'Par défaut du thème',
        '960px'   => 'Étroite / Lecture (960 px)',
        '1080px'  => 'Compacte (1080 px)',
        '1200px'  => 'Standard équilibrée (1200 px)',
        '1360px'  => 'Large panoramique (1360 px)',
        '100%'    => 'Pleine largeur fluide (100 %)',
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

    public function getSchema(?string $themeSlug = null): array
    {
        $slug = $themeSlug ?: $this->getActiveThemeSlug();
        $fontOptions = [];
        foreach (self::ALLOWED_FONTS as $key => $meta) {
            $fontOptions[$key] = $meta['label'];
        }

        $universalSchema = [
            // --- Groupe 1 : Identité & Boutons ---
            'accent_color' => [
                'group' => 'identity',
                'type' => 'color',
                'label' => 'Couleur principale d\'accentuation',
                'description' => 'Pilote les boutons principaux, liens actifs et badges.',
                'default' => '',
            ],
            'accent_hover_color' => [
                'group' => 'identity',
                'type' => 'color',
                'label' => 'Couleur principale au survol',
                'description' => 'Teinte appliquée lors du survol des boutons d\'action.',
                'default' => '',
            ],

            // --- Groupe 2 : Fonds & Textes (Mode Clair) ---
            'light_bg_color' => [
                'group' => 'light',
                'type' => 'color',
                'label' => 'Fond général de la page (Clair)',
                'description' => 'Arrière-plan global du site en mode clair.',
                'default' => '',
            ],
            'light_header_bg_color' => [
                'group' => 'light',
                'type' => 'color',
                'label' => 'Fond de l\'en-tête / Navigation (Clair)',
                'description' => 'Couleur de fond de la barre supérieure.',
                'default' => '',
            ],
            'light_card_bg_color' => [
                'group' => 'light',
                'type' => 'color',
                'label' => 'Fond des cartes et blocs (Clair)',
                'description' => 'Surface des articles, encarts et sections de contenu.',
                'default' => '',
            ],
            'light_heading_color' => [
                'group' => 'light',
                'type' => 'color',
                'label' => 'Couleur des titres H1–H3 (Clair)',
                'description' => 'Couleur des titres principaux en mode clair.',
                'default' => '',
            ],
            'light_text_color' => [
                'group' => 'light',
                'type' => 'color',
                'label' => 'Couleur du texte principal (Clair)',
                'description' => 'Couleur des paragraphes de lecture.',
                'default' => '',
            ],
            'light_muted_color' => [
                'group' => 'light',
                'type' => 'color',
                'label' => 'Couleur du texte secondaire (Clair)',
                'description' => 'Dates, légendes et métadonnées discrètes.',
                'default' => '',
            ],
            'light_border_color' => [
                'group' => 'light',
                'type' => 'color',
                'label' => 'Couleur des bordures (Clair)',
                'description' => 'Contours des cartes, champs et séparateurs.',
                'default' => '',
            ],

            // --- Groupe 3 : Fonds & Textes (Mode Sombre) ---
            'dark_bg_color' => [
                'group' => 'dark',
                'type' => 'color',
                'label' => 'Fond général de la page (Sombre)',
                'description' => 'Arrière-plan global du site en mode sombre.',
                'default' => '',
            ],
            'dark_header_bg_color' => [
                'group' => 'dark',
                'type' => 'color',
                'label' => 'Fond de l\'en-tête / Navigation (Sombre)',
                'description' => 'Couleur de la barre supérieure en mode sombre.',
                'default' => '',
            ],
            'dark_card_bg_color' => [
                'group' => 'dark',
                'type' => 'color',
                'label' => 'Fond des cartes et blocs (Sombre)',
                'description' => 'Surface des cartes et conteneurs en mode sombre.',
                'default' => '',
            ],
            'dark_heading_color' => [
                'group' => 'dark',
                'type' => 'color',
                'label' => 'Couleur des titres H1–H3 (Sombre)',
                'description' => 'Couleur des titres principaux en mode sombre.',
                'default' => '',
            ],
            'dark_text_color' => [
                'group' => 'dark',
                'type' => 'color',
                'label' => 'Couleur du texte principal (Sombre)',
                'description' => 'Couleur des paragraphes en mode sombre.',
                'default' => '',
            ],
            'dark_muted_color' => [
                'group' => 'dark',
                'type' => 'color',
                'label' => 'Couleur du texte secondaire (Sombre)',
                'description' => 'Légendes et métadonnées en mode sombre.',
                'default' => '',
            ],
            'dark_border_color' => [
                'group' => 'dark',
                'type' => 'color',
                'label' => 'Couleur des bordures (Sombre)',
                'description' => 'Contours des cartes et séparateurs en mode sombre.',
                'default' => '',
            ],

            // --- Groupe 4 : Typographie & Mise en page ---
            'font_family' => [
                'group' => 'layout',
                'type' => 'select',
                'label' => 'Typographie générale',
                'description' => 'Police appliquée au corps de texte du site.',
                'options' => $fontOptions,
                'default' => 'default',
            ],
            'heading_font_family' => [
                'group' => 'layout',
                'type' => 'select',
                'label' => 'Typographie des titres (H1–H3)',
                'description' => 'Police appliquée aux grands titres des pages et sections.',
                'options' => $fontOptions,
                'default' => 'default',
            ],
            'border_radius' => [
                'group' => 'layout',
                'type' => 'range',
                'label' => 'Arrondi des cartes et boutons (px)',
                'description' => 'Ajuste la douceur des angles. Laisser à -1 (Auto) pour garder le réglage du thème.',
                'min' => -1,
                'max' => 28,
                'step' => 1,
                'default' => -1,
            ],
            'content_max_width' => [
                'group' => 'layout',
                'type' => 'select',
                'label' => 'Largeur maximale du contenu',
                'description' => 'Contrôle l\'amplitude horizontale du conteneur principal.',
                'options' => self::ALLOWED_WIDTHS,
                'default' => 'default',
            ],
        ];

        $infosPath = THEMES . $slug . '/infos.json';
        $infos = Util::readJsonFile($infosPath, true);
        if (is_array($infos) && isset($infos['customizer']) && is_array($infos['customizer'])) {
            foreach ($infos['customizer'] as $k => $def) {
                if (is_string($k) && preg_match('/^[a-zA-Z0-9_-]{1,48}$/', $k) && is_array($def)) {
                    $def['group'] = $def['group'] ?? 'theme';
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
                    $clean[$key] = max($min, min($max, (int) $raw));
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

    public function buildCssOverrides(?string $themeSlug = null): string
    {
        $slug = $themeSlug ?: $this->getActiveThemeSlug();
        $schema = $this->getSchema($slug);
        $values = $this->getValues($slug);

        $globalVars = [];
        $lightVars = [];
        $darkVars = [];
        $extraRules = [];

        $isHex = static fn($v): bool => is_string($v) && (bool) preg_match('/^#[0-9a-f]{6}$/', $v);

        // 1. Identité & Survol
        $accent = (string) ($values['accent_color'] ?? '');
        if ($isHex($accent)) {
            $contrast = self::pickReadableContrast($accent);
            $globalVars[] = "--evo-accent: {$accent}";
            $globalVars[] = "--evo-primary: {$accent}";
            $globalVars[] = "--evo-accent-text: {$contrast}";
            $globalVars[] = "--dc-accent: {$accent}";
            $globalVars[] = "--ag-accent: {$accent}";
            $globalVars[] = "--primary-color: {$accent}";
        }

        $accentHover = (string) ($values['accent_hover_color'] ?? '');
        if ($isHex($accentHover)) {
            $globalVars[] = "--evo-accent-hover: {$accentHover}";
            $globalVars[] = "--evo-primary-hover: {$accentHover}";
            $globalVars[] = "--dc-accent-hover: {$accentHover}";
            $globalVars[] = "--ag-accent-hover: {$accentHover}";
            $globalVars[] = "--primary-hover-color: {$accentHover}";
        }

        // 2. Palette Mode Clair
        if ($isHex($values['light_bg_color'] ?? '')) {
            $c = $values['light_bg_color'];
            $lightVars[] = "--evo-bg: {$c}";
            $lightVars[] = "--dc-bg: {$c}";
            $lightVars[] = "--ag-bg: {$c}";
            $lightVars[] = "--background-color: {$c}";
        }
        if ($isHex($values['light_header_bg_color'] ?? '')) {
            $c = $values['light_header_bg_color'];
            $lightVars[] = "--header-bg: {$c}";
            $lightVars[] = "--dc-header-bg: {$c}";
            $lightVars[] = "--ag-header-bg: {$c}";
            $extraRules[] = 'html:not([data-theme="dark"]):not(.theme-dark) #header { background: ' . $c . '; }';
        }
        if ($isHex($values['light_card_bg_color'] ?? '')) {
            $c = $values['light_card_bg_color'];
            $lightVars[] = "--evo-card-bg: {$c}";
            $lightVars[] = "--evo-surface: {$c}";
            $lightVars[] = "--dc-surface: {$c}";
            $lightVars[] = "--ag-surface: {$c}";
            $lightVars[] = "--section-bg-color: {$c}";
            $lightVars[] = "--panel-bg: {$c}";
        }
        if ($isHex($values['light_heading_color'] ?? '')) {
            $c = $values['light_heading_color'];
            $lightVars[] = "--evo-heading: {$c}";
            $extraRules[] = 'html:not([data-theme="dark"]):not(.theme-dark) main h1, html:not([data-theme="dark"]):not(.theme-dark) main h2, html:not([data-theme="dark"]):not(.theme-dark) main h3 { color: ' . $c . '; }';
        }
        if ($isHex($values['light_text_color'] ?? '')) {
            $c = $values['light_text_color'];
            $lightVars[] = "--evo-text: {$c}";
            $lightVars[] = "--dc-text: {$c}";
            $lightVars[] = "--ag-text: {$c}";
            $lightVars[] = "--font-color: {$c}";
        }
        if ($isHex($values['light_muted_color'] ?? '')) {
            $c = $values['light_muted_color'];
            $lightVars[] = "--evo-text-muted: {$c}";
            $lightVars[] = "--dc-text-muted: {$c}";
            $lightVars[] = "--ag-text-muted: {$c}";
            $lightVars[] = "--text-muted: {$c}";
            $lightVars[] = "--font-secondary: {$c}";
        }
        if ($isHex($values['light_border_color'] ?? '')) {
            $c = $values['light_border_color'];
            $lightVars[] = "--evo-border: {$c}";
            $lightVars[] = "--dc-border: {$c}";
            $lightVars[] = "--ag-border: {$c}";
            $lightVars[] = "--border-color: {$c}";
            $lightVars[] = "--panel-border: {$c}";
        }

        // 3. Palette Mode Sombre
        if ($isHex($values['dark_bg_color'] ?? '')) {
            $c = $values['dark_bg_color'];
            $darkVars[] = "--evo-bg: {$c}";
            $darkVars[] = "--dc-bg: {$c}";
            $darkVars[] = "--ag-bg: {$c}";
            $darkVars[] = "--background-color: {$c}";
        }
        if ($isHex($values['dark_header_bg_color'] ?? '')) {
            $c = $values['dark_header_bg_color'];
            $darkVars[] = "--header-bg: {$c}";
            $darkVars[] = "--dc-header-bg: {$c}";
            $darkVars[] = "--ag-header-bg: {$c}";
            $extraRules[] = 'html[data-theme="dark"] #header, html.theme-dark #header { background: ' . $c . '; }';
        }
        if ($isHex($values['dark_card_bg_color'] ?? '')) {
            $c = $values['dark_card_bg_color'];
            $darkVars[] = "--evo-card-bg: {$c}";
            $darkVars[] = "--evo-surface: {$c}";
            $darkVars[] = "--dc-surface: {$c}";
            $darkVars[] = "--ag-surface: {$c}";
            $darkVars[] = "--section-bg-color: {$c}";
            $darkVars[] = "--panel-bg: {$c}";
        }
        if ($isHex($values['dark_heading_color'] ?? '')) {
            $c = $values['dark_heading_color'];
            $darkVars[] = "--evo-heading: {$c}";
            $extraRules[] = 'html[data-theme="dark"] main h1, html[data-theme="dark"] main h2, html[data-theme="dark"] main h3 { color: ' . $c . '; }';
        }
        if ($isHex($values['dark_text_color'] ?? '')) {
            $c = $values['dark_text_color'];
            $darkVars[] = "--evo-text: {$c}";
            $darkVars[] = "--dc-text: {$c}";
            $darkVars[] = "--ag-text: {$c}";
            $darkVars[] = "--font-color: {$c}";
        }
        if ($isHex($values['dark_muted_color'] ?? '')) {
            $c = $values['dark_muted_color'];
            $darkVars[] = "--evo-text-muted: {$c}";
            $darkVars[] = "--dc-text-muted: {$c}";
            $darkVars[] = "--ag-text-muted: {$c}";
            $darkVars[] = "--text-muted: {$c}";
            $darkVars[] = "--font-secondary: {$c}";
        }
        if ($isHex($values['dark_border_color'] ?? '')) {
            $c = $values['dark_border_color'];
            $darkVars[] = "--evo-border: {$c}";
            $darkVars[] = "--dc-border: {$c}";
            $darkVars[] = "--ag-border: {$c}";
            $darkVars[] = "--border-color: {$c}";
            $darkVars[] = "--panel-border: {$c}";
        }

        // 4. Typographie, Arrondi & Largeur
        $radius = isset($values['border_radius']) ? (int) $values['border_radius'] : -1;
        if ($radius >= 0 && $radius <= 28) {
            $sm = max(0, (int) round($radius * 0.65));
            $lg = min(36, (int) round($radius * 1.25));
            $globalVars[] = "--evo-radius: {$radius}px";
            $globalVars[] = "--evo-radius-sm: {$sm}px";
            $globalVars[] = "--evo-radius-md: {$radius}px";
            $globalVars[] = "--evo-radius-lg: {$lg}px";
            $globalVars[] = "--dc-radius: {$radius}px";
            $globalVars[] = "--dc-radius-sm: {$sm}px";
            $globalVars[] = "--ag-radius: {$radius}px";
            $globalVars[] = "--section-radius: {$radius}px";
        }

        $bodyFontKey = (string) ($values['font_family'] ?? 'default');
        if ($bodyFontKey !== 'default' && !empty(self::ALLOWED_FONTS[$bodyFontKey]['stack'])) {
            $stack = self::ALLOWED_FONTS[$bodyFontKey]['stack'];
            $globalVars[] = "--evo-font-body: {$stack}";
            $globalVars[] = "--dc-font-sans: {$stack}";
            $extraRules[] = "body { font-family: var(--evo-font-body); }";
        }

        $headingFontKey = (string) ($values['heading_font_family'] ?? 'default');
        if ($headingFontKey !== 'default' && !empty(self::ALLOWED_FONTS[$headingFontKey]['stack'])) {
            $stack = self::ALLOWED_FONTS[$headingFontKey]['stack'];
            $globalVars[] = "--evo-font-heading: {$stack}";
            $globalVars[] = "--dc-font-serif: {$stack}";
            $extraRules[] = "h1, h2, h3 { font-family: var(--evo-font-heading); }";
        }

        $maxWidth = (string) ($values['content_max_width'] ?? 'default');
        if ($maxWidth !== 'default' && array_key_exists($maxWidth, self::ALLOWED_WIDTHS)) {
            $globalVars[] = "--evo-container-max: {$maxWidth}";
            $globalVars[] = "--dc-container: {$maxWidth}";
            $globalVars[] = "--ag-container: {$maxWidth}";
            $extraRules[] = ".declic-main, .declic-header-inner, .declic-banner-inner, .declic-footer-inner, .standard-page-container, .header-container, .homepage-container, #body-page, #header_content { max-width: {$maxWidth}; }";
        }

        // Variables personnalisées additionnelles déclarées par un thème tiers
        foreach ($schema as $key => $field) {
            if (($field['group'] ?? '') !== 'theme') {
                continue;
            }
            $cssVar = $field['css_var'] ?? '';
            $val = $values[$key] ?? null;
            if (is_string($cssVar) && preg_match('/^--[a-zA-Z0-9_-]{2,48}$/', $cssVar) && is_string($val) && preg_match('/^#[0-9a-fA-F]{6}$/', $val)) {
                $globalVars[] = "{$cssVar}: " . strtolower($val);
            }
        }

        $chunks = [];
        if (!empty($globalVars)) {
            $chunks[] = ':root, html, body, html[data-theme="light"], html[data-theme="dark"], body.theme-dark {' . implode('; ', $globalVars) . ';}';
        }
        if (!empty($lightVars)) {
            $chunks[] = ':root:not([data-theme="dark"]):not(.theme-dark), html[data-theme="light"], html:not([data-theme="dark"]):not(.theme-dark) body {' . implode('; ', $lightVars) . ';}';
        }
        if (!empty($darkVars)) {
            $chunks[] = ':root[data-theme="dark"], html[data-theme="dark"], html.theme-dark, body.theme-dark, html[data-theme="dark"] body {' . implode('; ', $darkVars) . ';}';
        }
        if (!empty($extraRules)) {
            $chunks[] = implode(' ', $extraRules);
        }

        return implode(' ', $chunks);
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
