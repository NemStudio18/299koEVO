<div class="menus-admin-wrap" id="menusAdminWrap" data-location="{{activeLocation}}" data-csrf="{{csrfToken}}">
    <div class="menus-top-bar">
        <div>
            <h1 class="menus-page-title"><i class="fa-solid fa-bars-staggered"></i> Gestion des Menus</h1>
            <p class="menus-page-sub">Assemblez les menus du site indépendamment du contenu des pages.</p>
        </div>
        <a href="/admin/page" class="menus-btn-secondary">
            <i class="fa-regular fa-file-lines"></i> Gérer les Pages CMS
        </a>
    </div>

    <div class="menus-location-tabs">
        <a href="/admin/menus?location=main" class="menus-loc-tab {% if activeLocation == 'main' %}is-active{% endif %}">
            <i class="fa-solid fa-compass"></i> Menu principal
            <span class="loc-badge" id="count-main">{{counts.main|default(0)}}</span>
        </a>
        <a href="/admin/menus?location=avatar" class="menus-loc-tab {% if activeLocation == 'avatar' %}is-active{% endif %}">
            <i class="fa-solid fa-user-circle"></i> Menu Avatar
            <span class="loc-badge" id="count-avatar">{{counts.avatar|default(0)}}</span>
        </a>
        <a href="/admin/menus?location=footer" class="menus-loc-tab {% if activeLocation == 'footer' %}is-active{% endif %}">
            <i class="fa-solid fa-shoe-prints"></i> Pied de page
            <span class="loc-badge" id="count-footer">{{counts.footer|default(0)}}</span>
        </a>
    </div>

    <div id="menuToast" class="menus-toast is-hidden" role="status"></div>

    <div class="menus-grid">
        <!-- Colonne Gauche : Sources disponibles -->
        <aside class="menus-sources-col">
            <h2 class="menus-col-title">Ajouter au menu</h2>

            <!-- Accordéon Pages CMS -->
            <details class="source-box" open>
                <summary><i class="fa-regular fa-file-lines"></i> Pages CMS</summary>
                <div class="source-body">
                    <input type="search" id="filterPagesInput" class="source-search" placeholder="Filtrer les pages...">
                    <div id="sourcePagesList" class="source-check-list"></div>
                    <div class="source-actions">
                        <button type="button" class="menus-btn-primary" onclick="addCheckedSources('page')">
                            <i class="fa-solid fa-plus"></i> Ajouter les pages
                        </button>
                    </div>
                </div>
            </details>

            <!-- Accordéon Plugins / Modules -->
            <details class="source-box" open>
                <summary><i class="fa-solid fa-puzzle-piece"></i> Modules & Plugins</summary>
                <div class="source-body">
                    <input type="search" id="filterPluginsInput" class="source-search" placeholder="Filtrer les modules...">
                    <div id="sourcePluginsList" class="source-check-list"></div>
                    <div class="source-actions">
                        <button type="button" class="menus-btn-primary" onclick="addCheckedSources('plugin')">
                            <i class="fa-solid fa-plus"></i> Ajouter les modules
                        </button>
                    </div>
                </div>
            </details>

            <!-- Accordéon Lien personnalisé -->
            <details class="source-box">
                <summary><i class="fa-solid fa-link"></i> Lien personnalisé</summary>
                <form class="source-body source-form" onsubmit="addCustomUrl(event)">
                    <label>Libellé
                        <input type="text" id="customUrlLabel" placeholder="Ex: Notre page Facebook" required>
                    </label>
                    <label>URL
                        <input type="text" id="customUrlTarget" placeholder="https://... ou /chemin" required>
                    </label>
                    <label class="inline-check">
                        <input type="checkbox" id="customUrlBlank"> Ouvrir dans un nouvel onglet
                    </label>
                    <div class="source-actions">
                        <button type="submit" class="menus-btn-primary">
                            <i class="fa-solid fa-plus"></i> Ajouter le lien
                        </button>
                    </div>
                </form>
            </details>

            <!-- Accordéon Dossier -->
            <details class="source-box">
                <summary><i class="fa-regular fa-folder-open"></i> Dossier (menu déroulant)</summary>
                <form class="source-body source-form" onsubmit="addFolderItem(event)">
                    <label>Nom du dossier
                        <input type="text" id="folderLabel" placeholder="Ex: Découvrir" required>
                    </label>
                    <label>Icône (optionnel)
                        <input type="text" id="folderIcon" placeholder="ex: fa-solid fa-compass">
                    </label>
                    <div class="source-actions">
                        <button type="submit" class="menus-btn-primary">
                            <i class="fa-solid fa-folder-plus"></i> Ajouter le dossier
                        </button>
                    </div>
                </form>
            </details>
        </aside>

        <!-- Colonne Droite : Arbre du menu actif -->
        <section class="menus-tree-col">
            <div class="menus-tree-header">
                <div>
                    <h2 class="menus-col-title" style="margin:0;">Structure du menu</h2>
                    <small class="menus-hint">Glissez-déposez ou utilisez les flèches pour réordonner et créer des sous-menus (y compris pour les plugins).</small>
                </div>
                <button type="button" id="btnSaveOrder" class="menus-btn-save" onclick="persistCurrentOrder()">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer l'ordre
                </button>
            </div>

            <div id="menuTreeContainer" class="menu-tree-root"></div>
        </section>
    </div>
</div>

<script id="menuTreeJson" type="application/json">{{treeJson|raw}}</script>
<script id="menuCatalogJson" type="application/json">{{catalogJson|raw}}</script>
<script id="menuPagesJson" type="application/json">{{pagesJson|raw}}</script>

<style>
.menus-admin-wrap {
    --mn-accent: #1f62a9;
    --mn-accent-soft: #eff6ff;
    --mn-border: #e2e8f0;
    --mn-text: #0f172a;
    --mn-muted: #64748b;
    max-width: 1320px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.menus-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.menus-page-title {
    margin: 0;
    font-size: 1.55rem;
    font-weight: 800;
    color: var(--mn-text);
    display: flex;
    align-items: center;
    gap: 10px;
}
.menus-page-title i {
    color: var(--mn-accent);
}
.menus-page-sub {
    margin: 4px 0 0;
    color: var(--mn-muted);
    font-size: 0.92rem;
}
.menus-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 15px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: #fff;
    color: #334155;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.9rem;
}
.menus-btn-secondary:hover {
    border-color: var(--mn-accent);
    color: var(--mn-accent);
}
.menus-location-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
.menus-loc-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 10px;
    border: 1px solid var(--mn-border);
    background: #fff;
    color: #475569;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.15s ease;
}
.menus-loc-tab:hover {
    background: #f8fafc;
    color: var(--mn-text);
}
.menus-loc-tab.is-active {
    background: var(--mn-accent);
    border-color: var(--mn-accent);
    color: #fff;
}
.loc-badge {
    background: rgba(15, 23, 42, 0.12);
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 0.78rem;
}
.menus-loc-tab.is-active .loc-badge {
    background: rgba(255, 255, 255, 0.25);
}
.menus-toast {
    margin-bottom: 14px;
    padding: 10px 14px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.menus-toast.is-error {
    background: #fef2f2;
    color: #991b1b;
    border-color: #fecaca;
}
.menus-grid {
    display: grid;
    grid-template-columns: 350px minmax(0, 1fr);
    gap: 20px;
    align-items: start;
}
.menus-sources-col,
.menus-tree-col {
    background: #fff;
    border: 1px solid var(--mn-border);
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}
.menus-col-title {
    margin: 0 0 12px;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--mn-text);
}
.source-box {
    border: 1px solid var(--mn-border);
    border-radius: 10px;
    margin-bottom: 10px;
    overflow: hidden;
    background: #f8fafc;
}
.source-box summary {
    padding: 10px 12px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    color: #1e293b;
    background: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
}
.source-box[open] summary {
    border-bottom: 1px solid var(--mn-border);
}
.source-body {
    padding: 10px 12px;
}
.source-search {
    width: 100%;
    padding: 7px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 8px;
    box-sizing: border-box;
    background: #fff;
}
.source-check-list {
    max-height: 180px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 10px;
}
.source-check-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.86rem;
    color: #334155;
    padding: 4px 6px;
    border-radius: 6px;
    background: #fff;
    border: 1px solid #e2e8f0;
    cursor: pointer;
}
.source-check-row small {
    color: var(--mn-muted);
    margin-left: auto;
    font-size: 0.72rem;
}
.source-form {
    display: flex;
    flex-direction: column;
    gap: 9px;
}
.source-form label {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
}
.source-form input[type="text"] {
    padding: 7px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.86rem;
}
.source-form .inline-check {
    flex-direction: row;
    align-items: center;
    gap: 6px;
}
.source-actions {
    display: flex;
    justify-content: flex-end;
}
.menus-btn-primary,
.menus-btn-save {
    background: var(--mn-accent);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 0.84rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.menus-btn-primary:hover,
.menus-btn-save:hover {
    background: #164980;
}
.menus-tree-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    padding-bottom: 12px;
    margin-bottom: 12px;
    border-bottom: 1px solid var(--mn-border);
}
.menus-hint {
    color: var(--mn-muted);
    font-size: 0.82rem;
}
.menu-tree-root {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.menu-node-wrap {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.menu-node-children {
    margin-left: 28px;
    padding-left: 12px;
    border-left: 2px dashed #cbd5e1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.menu-node-card {
    border: 1px solid var(--mn-border);
    border-radius: 10px;
    background: #fff;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.menu-node-card.is-disabled {
    opacity: 0.6;
    background: #f8fafc;
}
.menu-node-card.is-dragging {
    opacity: 0.45;
    border-style: dashed;
}
.menu-node-card.drag-over-top {
    border-top: 3px solid var(--mn-accent);
}
.menu-node-card.drag-over-inside {
    background: var(--mn-accent-soft);
    border-color: var(--mn-accent);
}
.menu-node-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
}
.menu-drag-handle {
    color: #94a3b8;
    cursor: grab;
    padding: 4px;
}
.menu-node-title {
    font-weight: 700;
    font-size: 0.92rem;
    color: var(--mn-text);
    display: flex;
    align-items: center;
    gap: 7px;
    flex: 1;
    min-width: 0;
}
.menu-type-pill {
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.menu-type-pill.type-page { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.menu-type-pill.type-plugin { background: #f3e8ff; color: #7e22ce; border-color: #d8b4fe; }
.menu-type-pill.type-url { background: #fef3c7; color: #b45309; border-color: #fde68a; }
.menu-type-pill.type-folder { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.menu-vis-pill {
    font-size: 0.7rem;
    padding: 2px 7px;
    border-radius: 999px;
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}
.menu-node-tools {
    display: flex;
    align-items: center;
    gap: 4px;
}
.menu-icon-btn {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    border-radius: 6px;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.78rem;
}
.menu-icon-btn:hover {
    background: #e2e8f0;
    color: var(--mn-text);
}
.menu-node-editor {
    padding: 12px;
    border-top: 1px solid var(--mn-border);
    background: #f8fafc;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}
.menu-node-editor label {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #334155;
}
.menu-node-editor input[type="text"],
.menu-node-editor select {
    padding: 6px 9px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 0.85rem;
    background: #fff;
}
.menu-editor-footer {
    grid-column: 1 / -1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    padding-top: 6px;
}
.menu-btn-danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    border-radius: 7px;
    padding: 6px 11px;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
}
.menu-btn-danger:hover {
    background: #fee2e2;
}
.menu-empty-state {
    text-align: center;
    padding: 36px 20px;
    color: var(--mn-muted);
    border: 2px dashed var(--mn-border);
    border-radius: 12px;
}
.is-hidden {
    display: none !important;
}
@media (max-width: 900px) {
    .menus-grid { grid-template-columns: 1fr; }
    .menu-node-editor { grid-template-columns: 1fr; }
}
</style>

<script>
const wrap = document.getElementById('menusAdminWrap');
const activeLocation = wrap ? wrap.dataset.location : 'main';
let csrfToken = wrap ? wrap.dataset.csrf : '';
let treeData = JSON.parse(document.getElementById('menuTreeJson').textContent || '[]');
const catalogData = JSON.parse(document.getElementById('menuCatalogJson').textContent || '[]');
const pagesData = JSON.parse(document.getElementById('menuPagesJson').textContent || '[]');
const openEditors = new Set();
let dragState = null;

document.addEventListener('DOMContentLoaded', () => {
    renderSources();
    renderTree();

    const pFilter = document.getElementById('filterPagesInput');
    if (pFilter) pFilter.addEventListener('input', () => renderSourcePages(pFilter.value));
    const mFilter = document.getElementById('filterPluginsInput');
    if (mFilter) mFilter.addEventListener('input', () => renderSourcePlugins(mFilter.value));
});

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str == null ? '' : String(str);
    return d.innerHTML;
}

function showToast(msg, isError = false) {
    const t = document.getElementById('menuToast');
    if (!t) return;
    t.textContent = msg;
    t.className = 'menus-toast' + (isError ? ' is-error' : '');
    setTimeout(() => t.classList.add('is-hidden'), 2800);
}

function updateCounts(counts) {
    if (!counts) return;
    ['main', 'avatar', 'footer'].forEach(loc => {
        const el = document.getElementById(`count-${loc}`);
        if (el && counts[loc] !== undefined) el.textContent = String(counts[loc]);
    });
}

function renderSources() {
    renderSourcePages('');
    renderSourcePlugins('');
}

function renderSourcePages(query) {
    const box = document.getElementById('sourcePagesList');
    if (!box) return;
    const q = (query || '').trim().toLowerCase();
    const filtered = pagesData.filter(p => !q || p.name.toLowerCase().includes(q));
    if (!filtered.length) {
        box.innerHTML = '<div style="font-size:0.82rem;color:#64748b;padding:6px;">Aucune page trouvée.</div>';
        return;
    }
    box.innerHTML = filtered.map(p => `
        <label class="source-check-row">
            <input type="checkbox" class="src-check-page" value="${p.id}" data-label="${esc(p.name)}">
            <span>${esc(p.name)}</span>
            ${p.isHidden ? '<small>Brouillon</small>' : (p.isHomepage ? '<small>Accueil</small>' : '')}
        </label>
    `).join('');
}

function renderSourcePlugins(query) {
    const box = document.getElementById('sourcePluginsList');
    if (!box) return;
    const q = (query || '').trim().toLowerCase();
    const filtered = catalogData.filter(m => !q || m.label.toLowerCase().includes(q) || m.plugin.toLowerCase().includes(q));
    if (!filtered.length) {
        box.innerHTML = '<div style="font-size:0.82rem;color:#64748b;padding:6px;">Aucun module public.</div>';
        return;
    }
    box.innerHTML = filtered.map(m => `
        <label class="source-check-row" title="${esc(m.description || m.url)}">
            <input type="checkbox" class="src-check-plugin" value="${esc(m.key)}" data-label="${esc(m.label)}" data-icon="${esc(m.icon)}">
            <i class="${esc(m.icon)}"></i>
            <span>${esc(m.label)}</span>
            <small>${esc(m.plugin)}</small>
        </label>
    `).join('');
}

async function apiPost(url, params) {
    const fd = new FormData();
    fd.append('_csrf', csrfToken);
    Object.entries(params).forEach(([k, v]) => fd.append(k, typeof v === 'string' ? v : JSON.stringify(v)));
    const resp = await fetch(url, { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.csrfToken) csrfToken = data.csrfToken;
    if (!resp.ok || !data.success) {
        throw new Error(data.error || 'Erreur serveur');
    }
    if (Array.isArray(data.tree)) treeData = data.tree;
    if (data.counts) updateCounts(data.counts);
    return data;
}

async function addCheckedSources(kind) {
    const selector = kind === 'page' ? '.src-check-page:checked' : '.src-check-plugin:checked';
    const checks = Array.from(document.querySelectorAll(selector));
    if (!checks.length) {
        showToast('Cochez au moins un élément à ajouter.', true);
        return;
    }
    const items = checks.map(c => ({
        type: kind,
        target_ref: c.value,
        label: c.dataset.label || c.value,
        icon: c.dataset.icon || '',
        target_attr: '_self'
    }));
    try {
        await apiPost('/admin/menus/add', { location: activeLocation, items });
        checks.forEach(c => c.checked = false);
        renderTree();
        showToast(`${items.length} élément(s) ajouté(s) au menu.`);
    } catch (e) {
        showToast(e.message, true);
    }
}

async function addCustomUrl(e) {
    e.preventDefault();
    const labelEl = document.getElementById('customUrlLabel');
    const urlEl = document.getElementById('customUrlTarget');
    const blankEl = document.getElementById('customUrlBlank');
    try {
        await apiPost('/admin/menus/add', {
            location: activeLocation,
            items: [{
                type: 'url',
                target_ref: urlEl.value.trim(),
                label: labelEl.value.trim(),
                target_attr: blankEl.checked ? '_blank' : '_self'
            }]
        });
        labelEl.value = '';
        urlEl.value = '';
        blankEl.checked = false;
        renderTree();
        showToast('Lien personnalisé ajouté.');
    } catch (err) {
        showToast(err.message, true);
    }
}

async function addFolderItem(e) {
    e.preventDefault();
    const labelEl = document.getElementById('folderLabel');
    const iconEl = document.getElementById('folderIcon');
    try {
        await apiPost('/admin/menus/add', {
            location: activeLocation,
            items: [{
                type: 'folder',
                target_ref: '',
                label: labelEl.value.trim(),
                icon: iconEl.value.trim()
            }]
        });
        labelEl.value = '';
        iconEl.value = '';
        renderTree();
        showToast('Dossier ajouté au menu.');
    } catch (err) {
        showToast(err.message, true);
    }
}

function visLabel(mode) {
    return { public: 'Public', guest: 'Invités', logged: 'Connectés', admin: 'Admin' }[mode] || 'Public';
}

function typeLabel(type) {
    return { page: 'Page', plugin: 'Plugin', url: 'Lien', folder: 'Dossier' }[type] || type;
}

function renderTree() {
    const container = document.getElementById('menuTreeContainer');
    if (!container) return;
    if (!treeData.length) {
        container.innerHTML = `<div class="menu-empty-state"><i class="fa-regular fa-compass" style="font-size:1.8rem;margin-bottom:8px;display:block;"></i>Aucun élément dans cet emplacement.<br>Ajoutez des pages, plugins ou liens depuis la colonne de gauche.</div>`;
        return;
    }
    container.innerHTML = treeData.map((node, idx) => renderNodeHtml(node, 0, idx, treeData.length)).join('');
}

function renderNodeHtml(node, parentId, idx, siblingsLen) {
    const isOpen = openEditors.has(node.id);
    const visMode = (node.visibility && node.visibility.mode) ? node.visibility.mode : 'public';
    const childrenHtml = (node.children && node.children.length)
        ? `<div class="menu-node-children">${node.children.map((c, ci) => renderNodeHtml(c, node.id, ci, node.children.length)).join('')}</div>`
        : '';
    const statusWarn = !node.targetActive ? `<span class="menu-vis-pill" style="color:#b91c1c;border-color:#fecaca;">Cible inactive</span>` : '';

    return `
        <div class="menu-node-wrap" data-id="${node.id}">
            <div class="menu-node-card ${node.isEnabled ? '' : 'is-disabled'}"
                 draggable="true"
                 ondragstart="onNodeDragStart(event, ${node.id})"
                 ondragover="onNodeDragOver(event, ${node.id})"
                 ondragleave="onNodeDragLeave(event)"
                 ondrop="onNodeDrop(event, ${node.id})"
                 ondragend="onNodeDragEnd()">
                <div class="menu-node-bar">
                    <span class="menu-drag-handle" title="Glisser pour déplacer"><i class="fa-solid fa-grip-vertical"></i></span>
                    <div class="menu-node-title">
                        ${node.icon ? `<i class="${esc(node.icon)}"></i>` : ''}
                        <span>${esc(node.label)}</span>
                        <span class="menu-type-pill type-${esc(node.type)}">${typeLabel(node.type)}</span>
                        <span class="menu-vis-pill">${visLabel(visMode)}</span>
                        ${statusWarn}
                    </div>
                    <div class="menu-node-tools">
                        <button type="button" class="menu-icon-btn" title="Monter" ${idx === 0 ? 'disabled' : ''} onclick="moveNode(${node.id}, -1)"><i class="fa-solid fa-arrow-up"></i></button>
                        <button type="button" class="menu-icon-btn" title="Descendre" ${idx === siblingsLen - 1 ? 'disabled' : ''} onclick="moveNode(${node.id}, 1)"><i class="fa-solid fa-arrow-down"></i></button>
                        <button type="button" class="menu-icon-btn" title="Mettre en sous-menu" ${idx === 0 ? 'disabled' : ''} onclick="indentNode(${node.id})"><i class="fa-solid fa-indent"></i></button>
                        <button type="button" class="menu-icon-btn" title="Remonter d'un niveau" ${parentId === 0 ? 'disabled' : ''} onclick="outdentNode(${node.id})"><i class="fa-solid fa-outdent"></i></button>
                        <button type="button" class="menu-icon-btn" title="Modifier" onclick="toggleNodeEditor(${node.id})"><i class="fa-solid ${isOpen ? 'fa-chevron-up' : 'fa-pen'}"></i></button>
                    </div>
                </div>
                ${isOpen ? `
                <form class="menu-node-editor" onsubmit="saveNodeEdit(event, ${node.id})">
                    <label>Libellé dans le menu
                        <input type="text" name="label" value="${esc(node.label)}" required>
                    </label>
                    <label>Visibilité
                        <select name="visibility_mode">
                            <option value="public" ${visMode === 'public' ? 'selected' : ''}>Public (tout le monde)</option>
                            <option value="guest" ${visMode === 'guest' ? 'selected' : ''}>Visiteurs non connectés</option>
                            <option value="logged" ${visMode === 'logged' ? 'selected' : ''}>Membres connectés</option>
                            <option value="admin" ${visMode === 'admin' ? 'selected' : ''}>Administrateurs uniquement</option>
                        </select>
                    </label>
                    ${node.type === 'url' ? `
                    <label>URL cible
                        <input type="text" name="target_ref" value="${esc(node.targetRef)}" required>
                    </label>` : `
                    <label>Cible liée
                        <input type="text" value="${esc(node.targetLabel || node.url)}" disabled style="background:#f1f5f9;">
                    </label>`}
                    <label>Icône FontAwesome
                        <input type="text" name="icon" value="${esc(node.icon)}" placeholder="ex: fa-solid fa-star">
                    </label>
                    <label>Classe CSS
                        <input type="text" name="css_class" value="${esc(node.cssClass)}">
                    </label>
                    <label>Ouverture & État
                        <div style="display:flex;gap:10px;align-items:center;padding-top:4px;">
                            <select name="target_attr" style="flex:1;">
                                <option value="_self" ${node.targetAttr === '_self' ? 'selected' : ''}>Même onglet</option>
                                <option value="_blank" ${node.targetAttr === '_blank' ? 'selected' : ''}>Nouvel onglet</option>
                            </select>
                            <select name="is_enabled" style="flex:1;">
                                <option value="1" ${node.isEnabled ? 'selected' : ''}>Affiché</option>
                                <option value="0" ${!node.isEnabled ? 'selected' : ''}>Masqué</option>
                            </select>
                        </div>
                    </label>
                    <div class="menu-editor-footer">
                        <button type="button" class="menu-btn-danger" onclick="removeNode(${node.id})">
                            <i class="fa-solid fa-trash"></i> Retirer du menu
                        </button>
                        <button type="submit" class="menus-btn-primary">
                            <i class="fa-solid fa-check"></i> Appliquer
                        </button>
                    </div>
                </form>
                ` : ''}
            </div>
            ${childrenHtml}
        </div>
    `;
}

function toggleNodeEditor(id) {
    if (openEditors.has(id)) openEditors.delete(id);
    else openEditors.add(id);
    renderTree();
}

async function saveNodeEdit(e, id) {
    e.preventDefault();
    const form = e.target;
    const payload = {
        id,
        label: form.label.value.trim(),
        visibility_mode: form.visibility_mode.value,
        icon: form.icon.value.trim(),
        css_class: form.css_class.value.trim(),
        target_attr: form.target_attr.value,
        is_enabled: form.is_enabled.value,
    };
    if (form.target_ref) payload.target_ref = form.target_ref.value.trim();

    try {
        await apiPost('/admin/menus/update', payload);
        renderTree();
        showToast('Élément mis à jour.');
    } catch (err) {
        showToast(err.message, true);
    }
}

async function removeNode(id) {
    if (!confirm('Retirer cet élément du menu ? (La page ou le plugin associé reste intact.)')) return;
    try {
        openEditors.delete(id);
        await apiPost('/admin/menus/delete', { id });
        renderTree();
        showToast('Élément retiré du menu.');
    } catch (err) {
        showToast(err.message, true);
    }
}

function findNodeAndList(list, id, parentNode = null) {
    for (let i = 0; i < list.length; i++) {
        if (list[i].id === id) return { list, index: i, node: list[i], parentNode };
        if (list[i].children && list[i].children.length) {
            const found = findNodeAndList(list[i].children, id, list[i]);
            if (found) return found;
        }
    }
    return null;
}

function containsNodeId(node, targetId) {
    if (node.id === targetId) return true;
    return (node.children || []).some(c => containsNodeId(c, targetId));
}

function moveNode(id, delta) {
    const found = findNodeAndList(treeData, id);
    if (!found) return;
    const targetIdx = found.index + delta;
    if (targetIdx < 0 || targetIdx >= found.list.length) return;
    const [removed] = found.list.splice(found.index, 1);
    found.list.splice(targetIdx, 0, removed);
    renderTree();
    persistCurrentOrder();
}

function indentNode(id) {
    const found = findNodeAndList(treeData, id);
    if (!found || found.index === 0) return;
    const prevSibling = found.list[found.index - 1];
    const [removed] = found.list.splice(found.index, 1);
    prevSibling.children = prevSibling.children || [];
    prevSibling.children.push(removed);
    renderTree();
    persistCurrentOrder();
}

function outdentNode(id) {
    const found = findNodeAndList(treeData, id);
    if (!found || !found.parentNode) return;
    const parentInfo = findNodeAndList(treeData, found.parentNode.id);
    if (!parentInfo) return;
    const [removed] = found.list.splice(found.index, 1);
    parentInfo.list.splice(parentInfo.index + 1, 0, removed);
    renderTree();
    persistCurrentOrder();
}

function onNodeDragStart(e, id) {
    dragState = { id };
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', String(id));
    setTimeout(() => {
        const card = e.target.closest('.menu-node-card');
        if (card) card.classList.add('is-dragging');
    }, 0);
}

function onNodeDragOver(e, targetId) {
    if (!dragState || dragState.id === targetId) return;
    e.preventDefault();
    const card = e.currentTarget;
    const rect = card.getBoundingClientRect();
    const makeChild = (e.clientX - rect.left) > 55 && (e.clientY - rect.top) > rect.height * 0.35;
    card.classList.toggle('drag-over-inside', makeChild);
    card.classList.toggle('drag-over-top', !makeChild);
}

function onNodeDragLeave(e) {
    e.currentTarget.classList.remove('drag-over-top', 'drag-over-inside');
}

function onNodeDrop(e, targetId) {
    e.preventDefault();
    const card = e.currentTarget;
    const makeChild = card.classList.contains('drag-over-inside');
    card.classList.remove('drag-over-top', 'drag-over-inside');
    if (!dragState || dragState.id === targetId) return;

    const src = findNodeAndList(treeData, dragState.id);
    const dst = findNodeAndList(treeData, targetId);
    if (!src || !dst || containsNodeId(src.node, targetId)) return;

    const [moved] = src.list.splice(src.index, 1);
    const dstAfter = findNodeAndList(treeData, targetId);
    if (!dstAfter) return;

    if (makeChild) {
        dstAfter.node.children = dstAfter.node.children || [];
        dstAfter.node.children.push(moved);
    } else {
        dstAfter.list.splice(dstAfter.index, 0, moved);
    }
    renderTree();
    persistCurrentOrder();
}

function onNodeDragEnd() {
    dragState = null;
    document.querySelectorAll('.menu-node-card').forEach(c => c.classList.remove('is-dragging', 'drag-over-top', 'drag-over-inside'));
}

function flattenOrder(list, parentId = 0, out = []) {
    list.forEach((node, idx) => {
        out.push({ id: node.id, parent_id: parentId, position: (idx + 1) * 10 });
        if (node.children && node.children.length) {
            flattenOrder(node.children, node.id, out);
        }
    });
    return out;
}

async function persistCurrentOrder() {
    const order = flattenOrder(treeData);
    try {
        await apiPost('/admin/menus/save-order', { location: activeLocation, order });
        showToast('Ordre du menu enregistré.');
    } catch (err) {
        showToast(err.message, true);
    }
}
</script>

