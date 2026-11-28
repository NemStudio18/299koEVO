<section class="content customizer-workspace">
    <header class="customizer-header">
        <div>
            <h2><i class="fa-solid fa-palette"></i> Personnaliser le thème « {{ themeName }} »</h2>
            <p class="customizer-subtitle">
                Ajustez les couleurs, typographies et arrondis de votre site sans toucher au code. Vos réglages pilotent automatiquement les Design Tokens <code>--evo-*</code>.
            </p>
        </div>
        <div class="customizer-header-actions">
            <a href="{{ siteUrl }}" target="_blank" rel="noopener" class="button secondary">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir le site en direct
            </a>
            <form method="post" action="{{ resetUrl }}" onsubmit="return confirm('Restaurer tous les réglages par défaut du thème {{ themeName }} ?');" style="margin:0;">
                {{ SHOW.tokenField }}
                <button type="submit" class="button outline">
                    <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                </button>
            </form>
        </div>
    </header>

    <div class="customizer-grid">
        <!-- Colonne Gauche : Contrôles sans code -->
        <form method="post" action="{{ saveUrl }}" class="customizer-controls-card" id="evoCustomizerForm">
            {{ SHOW.tokenField }}

            {% for f in fields %}
                <div class="customizer-field">
                    <label for="cust_{{ f.key }}" class="customizer-label">{{ f.label }}</label>
                    {% if f.description %}
                        <p class="customizer-help">{{ f.description }}</p>
                    {% endif %}

                    {% if f.type == 'color' %}
                        <div class="customizer-color-row">
                            <input type="color" id="picker_{{ f.key }}" value="{{ f.value ? f.value : '#1d4ed8' }}" class="customizer-color-picker" data-target="cust_{{ f.key }}" />
                            <input type="text" id="cust_{{ f.key }}" name="customizer[{{ f.key }}]" value="{{ f.value }}" placeholder="Défaut du thème (ex: #2878a8)" pattern="^$|^#[0-9a-fA-F]{6}$" class="customizer-color-hex" data-preview-key="{{ f.key }}" />
                            <button type="button" class="button small secondary js-clear-color" data-target="cust_{{ f.key }}" title="Utiliser la couleur native du thème">Auto</button>
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

            <div class="customizer-submit-bar">
                <button type="submit" class="button primary">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer la personnalisation
                </button>
            </div>
        </form>

        <!-- Colonne Droite : Aperçu interactif temps réel -->
        <aside class="customizer-preview-panel" aria-label="Aperçu interactif">
            <div class="preview-toolbar">
                <span><i class="fa-solid fa-eye"></i> Simulation interactive en direct</span>
                <button type="button" id="previewDarkSwitch" class="button small secondary">Mode Sombre / Clair</button>
            </div>

            <div class="live-preview-stage" id="livePreviewStage">
                <div class="lp-badge">Aperçu Design Tokens</div>
                <h3 class="lp-heading" id="lpHeading">Bienvenue sur {{ SHOW.siteName }}</h3>
                <p class="lp-text" id="lpText">
                    Personnalisez librement votre identité visuelle : vos boutons, cartes et sections s'adaptent automatiquement sur l'ensemble du site sans écrire une seule ligne de CSS.
                </p>
                <div class="lp-cards">
                    <div class="lp-mini-card">
                        <strong>Identité cohérente</strong>
                        <span>Héritage automatique sur le blog, les formulaires et les sections.</span>
                    </div>
                    <div class="lp-mini-card">
                        <strong>Contraste vérifié</strong>
                        <span>Texte de bouton calculé automatiquement selon la luminosité.</span>
                    </div>
                </div>
                <div class="lp-actions">
                    <a href="#preview" onclick="return false;" class="lp-btn-primary" id="lpBtnPrimary">Nous contacter</a>
                    <a href="#preview" onclick="return false;" class="lp-btn-secondary" id="lpBtnSecondary">En savoir plus</a>
                </div>
            </div>
        </aside>
    </div>
</section>

<style>
.customizer-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
.customizer-subtitle { margin: 0.35rem 0 0; color: var(--cb-text-muted, #64748b); max-width: 720px; }
.customizer-header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
.customizer-grid { display: grid; grid-template-columns: minmax(320px, 1.1fr) minmax(300px, 0.9fr); gap: 1.5rem; align-items: start; }
.customizer-controls-card, .customizer-preview-panel { background: var(--cb-card-bg, #ffffff); border: 1px solid var(--cb-border, #e2e8f0); border-radius: 14px; padding: 1.5rem; }
.customizer-field { padding-bottom: 1.15rem; margin-bottom: 1.15rem; border-bottom: 1px solid var(--cb-border, #e2e8f0); }
.customizer-field:last-of-type { border-bottom: 0; }
.customizer-label { display: block; font-weight: 600; margin-bottom: 0.25rem; color: var(--cb-text, #0f172a); }
.customizer-help { margin: 0 0 0.6rem; font-size: 0.84rem; color: var(--cb-text-muted, #64748b); }
.customizer-color-row { display: flex; align-items: center; gap: 0.6rem; }
.customizer-color-picker { width: 44px; height: 38px; padding: 2px; border-radius: 8px; border: 1px solid var(--cb-border, #cbd5e1); cursor: pointer; background: transparent; }
.customizer-color-hex { flex: 1; }
.customizer-range-row { display: flex; align-items: center; gap: 0.8rem; }
.customizer-range-row input[type="range"] { flex: 1; }
.customizer-range-badge { min-width: 64px; text-align: center; font-weight: 600; font-size: 0.84rem; padding: 0.25rem 0.55rem; border-radius: 999px; background: var(--cb-bg-alt, #f1f5f9); }
.customizer-toggle-line { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.customizer-submit-bar { margin-top: 1rem; display: flex; justify-content: flex-end; }
.preview-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-weight: 600; font-size: 0.9rem; }
.live-preview-stage { --lp-accent: #1d4ed8; --lp-accent-fg: #ffffff; --lp-radius: 12px; --lp-bg: #f8fafc; --lp-card: #ffffff; --lp-text: #0f172a; --lp-muted: #475569; --lp-border: #e2e8f0; background: var(--lp-bg); color: var(--lp-text); border: 1px solid var(--lp-border); border-radius: var(--lp-radius); padding: 1.75rem; transition: all 0.2s ease; }
.live-preview-stage.is-dark { --lp-bg: #0f172a; --lp-card: #1e293b; --lp-text: #f8fafc; --lp-muted: #cbd5e1; --lp-border: #334155; }
.lp-badge { display: inline-block; padding: 0.22rem 0.7rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: var(--lp-accent); color: var(--lp-accent-fg); margin-bottom: 0.85rem; }
.lp-heading { margin: 0 0 0.65rem; font-size: 1.5rem; color: var(--lp-text); }
.lp-text { margin: 0 0 1.25rem; color: var(--lp-muted); line-height: 1.6; font-size: 0.94rem; }
.lp-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.35rem; }
.lp-mini-card { background: var(--lp-card); border: 1px solid var(--lp-border); border-radius: var(--lp-radius); padding: 0.9rem; display: flex; flex-direction: column; gap: 0.25rem; }
.lp-mini-card strong { font-size: 0.9rem; }
.lp-mini-card span { font-size: 0.8rem; color: var(--lp-muted); }
.lp-actions { display: flex; gap: 0.7rem; flex-wrap: wrap; }
.lp-btn-primary, .lp-btn-secondary { display: inline-flex; align-items: center; padding: 0.6rem 1.15rem; border-radius: var(--lp-radius); font-weight: 600; font-size: 0.9rem; text-decoration: none; }
.lp-btn-primary { background: var(--lp-accent); color: var(--lp-accent-fg); }
.lp-btn-secondary { background: var(--lp-card); color: var(--lp-text); border: 1px solid var(--lp-border); }
:root[data-theme="dark"] .customizer-controls-card, :root[data-theme="dark"] .customizer-preview-panel, html[data-theme="dark"] .customizer-controls-card, html[data-theme="dark"] .customizer-preview-panel { background: #1e293b; border-color: #334155; }
:root[data-theme="dark"] .customizer-field, html[data-theme="dark"] .customizer-field { border-color: #334155; }
:root[data-theme="dark"] .customizer-label, html[data-theme="dark"] .customizer-label { color: #f8fafc; }
:root[data-theme="dark"] .customizer-range-badge, html[data-theme="dark"] .customizer-range-badge { background: #0f172a; color: #e2e8f0; }
@media (max-width: 960px) { .customizer-grid { grid-template-columns: 1fr; } .lp-cards { grid-template-columns: 1fr; } }
</style>

<script>
(function() {
    const stage = document.getElementById('livePreviewStage');
    const heading = document.getElementById('lpHeading');
    const fontStacks = {
        default: 'inherit',
        system: 'system-ui, -apple-system, sans-serif',
        jakarta: '"Plus Jakarta Sans", Inter, system-ui, sans-serif',
        serif: 'Georgia, "Times New Roman", serif',
        humanist: '"Gill Sans", "Trebuchet MS", sans-serif',
        monospace: 'ui-monospace, monospace'
    };

    function contrastFor(hex) {
        if (!/^#[0-9a-fA-F]{6}$/.test(hex)) return '#ffffff';
        const r = parseInt(hex.slice(1,3), 16) / 255;
        const g = parseInt(hex.slice(3,5), 16) / 255;
        const b = parseInt(hex.slice(5,7), 16) / 255;
        const lin = (c) => c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        const lum = 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
        return lum > 0.42 ? '#0f172a' : '#ffffff';
    }

    function syncPreview() {
        const accentEl = document.getElementById('cust_accent_color');
        const radiusEl = document.getElementById('cust_border_radius');
        const bodyFontEl = document.getElementById('cust_font_family');
        const headFontEl = document.getElementById('cust_heading_font_family');

        if (accentEl && /^#[0-9a-fA-F]{6}$/.test(accentEl.value.trim())) {
            const hex = accentEl.value.trim();
            stage.style.setProperty('--lp-accent', hex);
            stage.style.setProperty('--lp-accent-fg', contrastFor(hex));
        } else {
            stage.style.setProperty('--lp-accent', '#1d4ed8');
            stage.style.setProperty('--lp-accent-fg', '#ffffff');
        }

        if (radiusEl) {
            const v = parseInt(radiusEl.value, 10);
            const badge = document.getElementById('badge_border_radius');
            if (badge) badge.textContent = v < 0 ? 'Auto' : (v + ' px');
            stage.style.setProperty('--lp-radius', (v < 0 ? 12 : v) + 'px');
        }

        if (bodyFontEl) {
            stage.style.fontFamily = fontStacks[bodyFontEl.value] || 'inherit';
        }
        if (headFontEl && heading) {
            heading.style.fontFamily = fontStacks[headFontEl.value] || 'inherit';
        }
    }

    document.querySelectorAll('.customizer-color-picker').forEach((picker) => {
        picker.addEventListener('input', () => {
            const target = document.getElementById(picker.dataset.target);
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
        el.addEventListener('input', syncPreview);
        el.addEventListener('change', syncPreview);
    });

    const darkBtn = document.getElementById('previewDarkSwitch');
    if (darkBtn) {
        darkBtn.addEventListener('click', () => stage.classList.toggle('is-dark'));
    }

    syncPreview();
})();
</script>
