<section class="content customizer-workspace">
    <header class="customizer-header">
        <div>
            <h2><i class="fa-solid fa-palette"></i> Personnaliser le thème « {{ themeName }} »</h2>
            <p class="customizer-subtitle">
                Personnalisez les couleurs de boutons, de survol, d'arrière-plan, d'en-tête, de cartes, de textes, les polices et la largeur du site en mode Clair et Sombre sans écrire de CSS.
            </p>
        </div>
        <div class="customizer-header-actions">
            <a href="{{ siteUrl }}" target="_blank" rel="noopener" class="button secondary">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir le site en direct
            </a>
            <form method="post" action="{{ resetUrl }}" onsubmit="return confirm('Restaurer tous les réglages par défaut du thème {{ themeName }} ?');" style="margin:0;">
                {{ SHOW.tokenField }}
                <button type="submit" class="button outline">
                    <i class="fa-solid fa-rotate-left"></i> Tout réinitialiser
                </button>
            </form>
        </div>
    </header>

    <div class="customizer-grid">
        <!-- Colonne Gauche : Onglets de réglages sans code -->
        <form method="post" action="{{ saveUrl }}" class="customizer-controls-card" id="evoCustomizerForm">
            {{ SHOW.tokenField }}

            <div class="cust-tabs-nav" role="tablist">
                {% for idx, g in groups %}
                    <button type="button" class="cust-tab-btn {% if idx == 0 %}is-active{% endif %}" data-cust-tab="{{ g.id }}">
                        <i class="{{ g.icon }}"></i> <span>{{ g.title }}</span>
                    </button>
                {% endfor %}
            </div>

            {% for idx, g in groups %}
                <div class="cust-tab-pane {% if idx == 0 %}is-active{% endif %}" id="cust-pane-{{ g.id }}" data-pane-group="{{ g.id }}">
                    {% for f in g.fields %}
                        <div class="customizer-field">
                            <label for="cust_{{ f.key }}" class="customizer-label">{{ f.label }}</label>
                            {% if f.description %}
                                <p class="customizer-help">{{ f.description }}</p>
                            {% endif %}

                            {% if f.type == 'color' %}
                                <div class="customizer-color-row">
                                    <input type="color" id="picker_{{ f.key }}" value="{{ f.value ? f.value : '#1d4ed8' }}" class="customizer-color-picker" data-target="cust_{{ f.key }}" data-mode-hint="{{ f.group }}" />
                                    <input type="text" id="cust_{{ f.key }}" name="customizer[{{ f.key }}]" value="{{ f.value }}" placeholder="Auto (défaut du thème)" pattern="^$|^#[0-9a-fA-F]{6}$" class="customizer-color-hex" data-preview-key="{{ f.key }}" data-mode-hint="{{ f.group }}" />
                                    <button type="button" class="button small secondary js-clear-color" data-target="cust_{{ f.key }}" title="Revenir à la valeur native du thème">Auto</button>
                                </div>
                            {% elseif f.type == 'select' %}
                                <select id="cust_{{ f.key }}" name="customizer[{{ f.key }}]" data-preview-key="{{ f.key }}">
                                    {% for optKey, optLabel in f.options %}
                                        <option value="{{ optKey }}" {% if f.value == optKey %}selected{% endif %}>{{ optLabel }}</option>
                                    {% endfor %}
                                </select>
                            {% elseif f.type == 'range' %}
                                <div class="customizer-range-row">
                                    <input type="range" id="cust_{{ f.key }}" name="customizer[{{ f.key }}]" min="{{ f.min }}" max="{{ f.max }}" step="{{ f.step }}" value="{{ f.value }}" data-preview-key="{{ f.key }}" />
                                    <span class="customizer-range-badge" id="badge_{{ f.key }}">
                                        {% if f.value < 0 %}Auto{% else %}{{ f.value }} px{% endif %}
                                    </span>
                                </div>
                            {% elseif f.type == 'boolean' %}
                                <label class="customizer-toggle-line">
                                    <input type="checkbox" id="cust_{{ f.key }}" name="customizer[{{ f.key }}]" value="1" {% if f.value %}checked{% endif %} data-preview-key="{{ f.key }}" />
                                    <span>Activer cette option</span>
                                </label>
                            {% else %}
                                <input type="text" id="cust_{{ f.key }}" name="customizer[{{ f.key }}]" value="{{ f.value }}" data-preview-key="{{ f.key }}" />
                            {% endif %}
                        </div>
                    {% endfor %}
                </div>
            {% endfor %}

            <div class="customizer-submit-bar">
                <button type="submit" class="button primary">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer la personnalisation
                </button>
            </div>
        </form>

        <!-- Colonne Droite : Simulation interactive fidèle (collante au scroll) -->
        <aside class="customizer-preview-panel" aria-label="Aperçu interactif">
            <div class="preview-toolbar">
                <div class="preview-toolbar-left">
                    <span><i class="fa-solid fa-eye"></i> Simulation en direct</span>
                    <span class="lp-width-indicator" id="lpWidthIndicator">Largeur : Auto</span>
                </div>
                <div class="preview-mode-pills">
                    <button type="button" id="btnPreviewLight" class="lp-mode-btn is-active"><i class="fa-regular fa-sun"></i> Clair</button>
                    <button type="button" id="btnPreviewDark" class="lp-mode-btn"><i class="fa-solid fa-moon"></i> Sombre</button>
                </div>
            </div>

            <!-- Cadre simulant l'écran global du navigateur (fond de page + marges latérales selon largeur max) -->
            <div class="live-preview-viewport" id="livePreviewStage">
                <!-- Barre d'en-tête simulée -->
                <div class="lp-site-header" id="lpHeader">
                    <div class="lp-inner-container">
                        <div class="lp-header-row">
                            <strong class="lp-brand">{{ SHOW.siteName }}</strong>
                            <div class="lp-nav-items">
                                <span class="lp-nav-active">Accueil</span>
                                <span>Services</span>
                                <span>Blog</span>
                                <span>Contact</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Corps de page simulé (réagit à la largeur maximale content_max_width) -->
                <div class="lp-inner-container" id="lpContainer">
                    <div class="lp-main-card">
                        <span class="lp-badge">Aperçu Design Tokens</span>
                        <h3 class="lp-heading" id="lpHeading">Bienvenue sur {{ SHOW.siteName }}</h3>
                        <p class="lp-text" id="lpText">
                            Tous vos réglages (fonds de page, cartes, en-tête, titres, textes, bordures, arrondis et largeur) réagissent ici en direct en mode Clair comme en mode Sombre.
                        </p>

                        <div class="lp-cards">
                            <div class="lp-mini-card">
                                <strong class="lp-card-title">Surface &amp; Bordures</strong>
                                <span class="lp-card-muted">Texte secondaire et encarts harmonisés sur tout le site.</span>
                            </div>
                            <div class="lp-mini-card">
                                <strong class="lp-card-title">Contraste Automatique</strong>
                                <span class="lp-card-muted">Lisibilité préservée sur vos pages et vos extensions.</span>
                            </div>
                        </div>

                        <div class="lp-actions">
                            <a href="#preview" onclick="return false;" class="lp-btn-primary" id="lpBtnPrimary" title="Survolez-moi pour tester l'effet au survol">
                                Bouton normal (survolez-moi)
                            </a>
                            <span class="lp-btn-hover-demo" id="lpBtnHoverDemo" title="Témoin permanent de la couleur au survol">
                                <i class="fa-solid fa-hand-pointer"></i> Aperçu au survol
                            </span>
                            <a href="#preview" onclick="return false;" class="lp-btn-secondary" id="lpBtnSecondary">
                                Bouton secondaire
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>

<style>
.customizer-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
.customizer-subtitle { margin: 0.35rem 0 0; color: var(--cb-text-muted, #64748b); max-width: 760px; }
.customizer-header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
.customizer-grid { display: grid; grid-template-columns: minmax(340px, 1.05fr) minmax(340px, 0.95fr); gap: 1.5rem; align-items: start; }
.customizer-controls-card, .customizer-preview-panel { background: var(--cb-card-bg, #ffffff); border: 1px solid var(--cb-border, #e2e8f0); border-radius: 14px; padding: 1.35rem; }
.customizer-preview-panel { position: sticky; top: 82px; }

/* Onglets internes de personnalisation */
.cust-tabs-nav { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid var(--cb-border, #e2e8f0); }
.cust-tab-btn { display: inline-flex; align-items: center; gap: 0.42rem; padding: 0.48rem 0.8rem; border-radius: 8px; border: 1px solid var(--cb-border, #cbd5e1); background: var(--cb-bg-alt, #f8fafc); color: var(--cb-text, #334155); font-size: 0.83rem; font-weight: 600; cursor: pointer; transition: all 0.15s ease; }
.cust-tab-btn.is-active { background: #1d4ed8; border-color: #1d4ed8; color: #ffffff; }
.cust-tab-pane { display: none; }
.cust-tab-pane.is-active { display: block; }

.customizer-field { padding-bottom: 1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--cb-border, #e2e8f0); }
.customizer-field:last-of-type { border-bottom: 0; }
.customizer-label { display: block; font-weight: 600; margin-bottom: 0.2rem; color: var(--cb-text, #0f172a); font-size: 0.92rem; }
.customizer-help { margin: 0 0 0.55rem; font-size: 0.81rem; color: var(--cb-text-muted, #64748b); }
.customizer-color-row { display: flex; align-items: center; gap: 0.55rem; }
.customizer-color-picker { width: 42px; height: 36px; padding: 2px; border-radius: 8px; border: 1px solid var(--cb-border, #cbd5e1); cursor: pointer; background: transparent; flex-shrink: 0; }
.customizer-color-hex { flex: 1; }
.customizer-range-row { display: flex; align-items: center; gap: 0.8rem; }
.customizer-range-row input[type="range"] { flex: 1; }
.customizer-range-badge { min-width: 64px; text-align: center; font-weight: 600; font-size: 0.84rem; padding: 0.25rem 0.55rem; border-radius: 999px; background: var(--cb-bg-alt, #f1f5f9); }
.customizer-toggle-line { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.customizer-submit-bar { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--cb-border, #e2e8f0); display: flex; justify-content: flex-end; }

/* Barre d'outils d'aperçu */
.preview-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.9rem; font-weight: 600; font-size: 0.88rem; }
.preview-toolbar-left { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.lp-width-indicator { font-size: 0.76rem; padding: 0.18rem 0.55rem; border-radius: 999px; background: var(--cb-bg-alt, #f1f5f9); color: var(--cb-text-muted, #475569); }
.preview-mode-pills { display: inline-flex; border: 1px solid var(--cb-border, #cbd5e1); border-radius: 8px; overflow: hidden; }
.lp-mode-btn { border: 0; background: transparent; color: var(--cb-text-muted, #64748b); padding: 0.32rem 0.7rem; font-size: 0.8rem; font-weight: 600; cursor: pointer; }
.lp-mode-btn.is-active { background: #1d4ed8; color: #ffffff; }

/* Scène de simulation complète */
.live-preview-viewport {
    --lp-accent: #1d4ed8;
    --lp-accent-fg: #ffffff;
    --lp-accent-hover: #1e40af;
    --lp-accent-hover-fg: #ffffff;
    --lp-radius: 12px;
    --lp-container-pct: 90%;
    --lp-bg: #faf9f6;
    --lp-header-bg: #ffffff;
    --lp-card: #ffffff;
    --lp-heading: #0f172a;
    --lp-text: #1e293b;
    --lp-muted: #64748b;
    --lp-border: #e2e8f0;
    background: var(--lp-bg);
    color: var(--lp-text);
    border: 1px solid var(--lp-border);
    border-radius: 12px;
    overflow: hidden;
    padding-bottom: 1.4rem;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.lp-site-header {
    background: var(--lp-header-bg);
    border-bottom: 1px solid var(--lp-border);
    padding: 0.7rem 0.85rem;
    margin-bottom: 1.25rem;
    transition: background-color 0.2s ease;
}

.lp-inner-container {
    width: var(--lp-container-pct);
    margin: 0 auto;
    transition: width 0.25s ease;
}

.lp-header-row { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; font-size: 0.82rem; }
.lp-brand { color: var(--lp-heading); font-size: 0.92rem; }
.lp-nav-items { display: flex; gap: 0.65rem; color: var(--lp-muted); font-weight: 500; }
.lp-nav-active { color: var(--lp-accent); font-weight: 700; }

.lp-main-card {
    background: var(--lp-card);
    border: 1px solid var(--lp-border);
    border-radius: var(--lp-radius);
    padding: 1.35rem;
    transition: background-color 0.2s ease, border-color 0.2s ease, border-radius 0.2s ease;
}

.lp-badge { display: inline-block; padding: 0.2rem 0.65rem; border-radius: 999px; font-size: 0.73rem; font-weight: 700; background: var(--lp-accent); color: var(--lp-accent-fg); margin-bottom: 0.75rem; }
.lp-heading { margin: 0 0 0.55rem; font-size: 1.38rem; color: var(--lp-heading); transition: color 0.2s ease; }
.lp-text { margin: 0 0 1.15rem; color: var(--lp-text); line-height: 1.58; font-size: 0.9rem; }
.lp-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 0.7rem; margin-bottom: 1.2rem; }
.lp-mini-card { background: var(--lp-bg); border: 1px solid var(--lp-border); border-radius: var(--lp-radius); padding: 0.8rem; display: flex; flex-direction: column; gap: 0.22rem; }
.lp-card-title { font-size: 0.86rem; color: var(--lp-heading); }
.lp-card-muted { font-size: 0.78rem; color: var(--lp-muted); }

.lp-actions { display: flex; gap: 0.55rem; flex-wrap: wrap; align-items: center; }
.lp-btn-primary, .lp-btn-hover-demo, .lp-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.38rem;
    padding: 0.52rem 0.95rem;
    border-radius: var(--lp-radius);
    font-weight: 600;
    font-size: 0.83rem;
    text-decoration: none;
    transition: background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}
.lp-btn-primary { background: var(--lp-accent); color: var(--lp-accent-fg); border: 1px solid var(--lp-accent); }
.lp-btn-primary:hover { background: var(--lp-accent-hover); color: var(--lp-accent-hover-fg); border-color: var(--lp-accent-hover); text-decoration: none; }
.lp-btn-hover-demo { background: var(--lp-accent-hover); color: var(--lp-accent-hover-fg); border: 1px dashed rgba(255,255,255,0.45); cursor: default; }
.lp-btn-secondary { background: var(--lp-bg); color: var(--lp-text); border: 1px solid var(--lp-border); }

:root[data-theme="dark"] .customizer-controls-card, :root[data-theme="dark"] .customizer-preview-panel, html[data-theme="dark"] .customizer-controls-card, html[data-theme="dark"] .customizer-preview-panel { background: #1e293b; border-color: #334155; }
:root[data-theme="dark"] .customizer-field, :root[data-theme="dark"] .cust-tabs-nav, :root[data-theme="dark"] .customizer-submit-bar, html[data-theme="dark"] .customizer-field, html[data-theme="dark"] .cust-tabs-nav, html[data-theme="dark"] .customizer-submit-bar { border-color: #334155; }
:root[data-theme="dark"] .cust-tab-btn:not(.is-active), html[data-theme="dark"] .cust-tab-btn:not(.is-active) { background: #0f172a; border-color: #334155; color: #cbd5e1; }
:root[data-theme="dark"] .customizer-label, html[data-theme="dark"] .customizer-label { color: #f8fafc; }
:root[data-theme="dark"] .customizer-range-badge, :root[data-theme="dark"] .lp-width-indicator, html[data-theme="dark"] .customizer-range-badge, html[data-theme="dark"] .lp-width-indicator { background: #0f172a; color: #e2e8f0; }

@media (max-width: 980px) {
    .customizer-grid { grid-template-columns: 1fr; }
    .customizer-preview-panel { position: static; }
    .lp-cards { grid-template-columns: 1fr; }
}
</style>

<script>
(function() {
    const stage = document.getElementById('livePreviewStage');
    const heading = document.getElementById('lpHeading');
    const widthBadge = document.getElementById('lpWidthIndicator');
    const btnLight = document.getElementById('btnPreviewLight');
    const btnDark = document.getElementById('btnPreviewDark');

    let previewMode = 'light';

    const defaults = {
        light: {
            bg: '#faf9f6',
            header: '#ffffff',
            card: '#ffffff',
            heading: '#0f172a',
            text: '#1e293b',
            muted: '#64748b',
            border: '#e2e8f0'
        },
        dark: {
            bg: '#0e1116',
            header: '#141e26',
            card: '#18232c',
            heading: '#f8fafc',
            text: '#e2e8f0',
            muted: '#94a3b8',
            border: '#27323f'
        }
    };

    const fontStacks = {
        default: 'inherit',
        system: 'system-ui, -apple-system, sans-serif',
        jakarta: '"Plus Jakarta Sans", Inter, system-ui, sans-serif',
        serif: 'Georgia, "Times New Roman", serif',
        humanist: '"Gill Sans", "Trebuchet MS", sans-serif',
        monospace: 'ui-monospace, monospace'
    };

    const widthScaleMap = {
        'default': { pct: '90%', label: 'Auto (Thème)' },
        '960px':   { pct: '68%', label: '960 px (Étroite)' },
        '1080px':  { pct: '78%', label: '1080 px (Compacte)' },
        '1200px':  { pct: '88%', label: '1200 px (Standard)' },
        '1360px':  { pct: '95%', label: '1360 px (Large)' },
        '100%':    { pct: '100%', label: '100 % (Pleine largeur)' }
    };

    function isHex(v) {
        return typeof v === 'string' && /^#[0-9a-fA-F]{6}$/.test(v.trim());
    }

    function valOf(id, fallback) {
        const el = document.getElementById('cust_' + id);
        if (!el) return fallback;
        const v = el.value.trim();
        return isHex(v) ? v : fallback;
    }

    function contrastFor(hex) {
        if (!isHex(hex)) return '#ffffff';
        const h = hex.trim();
        const r = parseInt(h.slice(1,3), 16) / 255;
        const g = parseInt(h.slice(3,5), 16) / 255;
        const b = parseInt(h.slice(5,7), 16) / 255;
        const lin = (c) => c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        const lum = 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
        return lum > 0.42 ? '#0f172a' : '#ffffff';
    }

    function darkenHex(hex, factor) {
        if (!isHex(hex)) return '#1e40af';
        const r = Math.max(0, Math.round(parseInt(hex.slice(1,3), 16) * factor));
        const g = Math.max(0, Math.round(parseInt(hex.slice(3,5), 16) * factor));
        const b = Math.max(0, Math.round(parseInt(hex.slice(5,7), 16) * factor));
        return '#' + [r, g, b].map((n) => n.toString(16).padStart(2, '0')).join('');
    }

    function setPreviewMode(mode) {
        previewMode = (mode === 'dark') ? 'dark' : 'light';
        if (btnLight) btnLight.classList.toggle('is-active', previewMode === 'light');
        if (btnDark) btnDark.classList.toggle('is-active', previewMode === 'dark');
        syncPreview();
    }

    function syncPreview() {
        const d = defaults[previewMode];
        const prefix = previewMode + '_';

        const accent = valOf('accent_color', previewMode === 'dark' ? '#3b82f6' : '#1d4ed8');
        const hover = valOf('accent_hover_color', darkenHex(accent, 0.82));

        stage.style.setProperty('--lp-accent', accent);
        stage.style.setProperty('--lp-accent-fg', contrastFor(accent));
        stage.style.setProperty('--lp-accent-hover', hover);
        stage.style.setProperty('--lp-accent-hover-fg', contrastFor(hover));

        stage.style.setProperty('--lp-bg', valOf(prefix + 'bg_color', d.bg));
        stage.style.setProperty('--lp-header-bg', valOf(prefix + 'header_bg_color', d.header));
        stage.style.setProperty('--lp-card', valOf(prefix + 'card_bg_color', d.card));
        stage.style.setProperty('--lp-heading', valOf(prefix + 'heading_color', d.heading));
        stage.style.setProperty('--lp-text', valOf(prefix + 'text_color', d.text));
        stage.style.setProperty('--lp-muted', valOf(prefix + 'muted_color', d.muted));
        stage.style.setProperty('--lp-border', valOf(prefix + 'border_color', d.border));

        const radiusEl = document.getElementById('cust_border_radius');
        if (radiusEl) {
            const v = parseInt(radiusEl.value, 10);
            const badge = document.getElementById('badge_border_radius');
            if (badge) badge.textContent = v < 0 ? 'Auto' : (v + ' px');
            stage.style.setProperty('--lp-radius', (v < 0 ? 12 : v) + 'px');
        }

        const widthEl = document.getElementById('cust_content_max_width');
        if (widthEl) {
            const wMeta = widthScaleMap[widthEl.value] || widthScaleMap['default'];
            stage.style.setProperty('--lp-container-pct', wMeta.pct);
            if (widthBadge) widthBadge.textContent = 'Largeur : ' + wMeta.label;
        }

        const bodyFontEl = document.getElementById('cust_font_family');
        if (bodyFontEl) {
            stage.style.fontFamily = fontStacks[bodyFontEl.value] || 'inherit';
        }
        const headFontEl = document.getElementById('cust_heading_font_family');
        if (headFontEl && heading) {
            heading.style.fontFamily = fontStacks[headFontEl.value] || 'inherit';
        }
    }

    // Navigation par onglets de groupes (bascule automatiquement l'aperçu Clair/Sombre selon l'onglet ouvert)
    document.querySelectorAll('[data-cust-tab]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const tabId = btn.dataset.custTab;
            document.querySelectorAll('[data-cust-tab]').forEach((b) => b.classList.toggle('is-active', b === btn));
            document.querySelectorAll('[data-pane-group]').forEach((p) => p.classList.toggle('is-active', p.dataset.paneGroup === tabId));
            if (tabId === 'dark') setPreviewMode('dark');
            else if (tabId === 'light') setPreviewMode('light');
        });
    });

    document.querySelectorAll('.customizer-color-picker').forEach((picker) => {
        picker.addEventListener('input', () => {
            const target = document.getElementById(picker.dataset.target);
            if (picker.dataset.modeHint === 'dark' && previewMode !== 'dark') setPreviewMode('dark');
            if (picker.dataset.modeHint === 'light' && previewMode !== 'light') setPreviewMode('light');
            if (target) { target.value = picker.value; syncPreview(); }
        });
    });

    document.querySelectorAll('.js-clear-color').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.target);
            if (target) { target.value = ''; syncPreview(); }
        });
    });

    document.querySelectorAll('[data-preview-key]').forEach((el) => {
        const handler = () => {
            if (el.dataset.modeHint === 'dark' && previewMode !== 'dark') setPreviewMode('dark');
            if (el.dataset.modeHint === 'light' && previewMode !== 'light') setPreviewMode('light');
            const picker = document.getElementById('picker_' + el.dataset.previewKey);
            if (picker && isHex(el.value)) picker.value = el.value.trim();
            syncPreview();
        };
        el.addEventListener('input', handler);
        el.addEventListener('change', handler);
    });

    if (btnLight) btnLight.addEventListener('click', () => setPreviewMode('light'));
    if (btnDark) btnDark.addEventListener('click', () => setPreviewMode('dark'));

    syncPreview();
})();
</script>
