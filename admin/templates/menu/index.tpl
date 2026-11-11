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
                    <h2 class="menus-col-title">Structure du menu</h2>
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
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function idKey(v) {
    if (v === null || v === undefined || v === '' || v === 0 || v === '0') return '0';
    return String(v);
}

function collectExistingRefs(nodes, out = new Set()) {
    (nodes || []).forEach(node => {
        if ((node.type === 'page' || node.type === 'plugin') && node.targetRef) {
            out.add(`${node.type}:${String(node.targetRef).trim()}`);
        }
        if (node.children && node.children.length) {
            collectExistingRefs(node.children, out);
        }
    });
    return out;
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
    const pFilter = document.getElementById('filterPagesInput');
    const mFilter = document.getElementById('filterPluginsInput');
    renderSourcePages(pFilter ? pFilter.value : '');
    renderSourcePlugins(mFilter ? mFilter.value : '');
}

function renderSourcePages(query) {
    const box = document.getElementById('sourcePagesList');
    if (!box) return;
    const existing = collectExistingRefs(treeData);
    const q = (query || '').trim().toLowerCase();
    const filtered = pagesData.filter(p => !q || p.name.toLowerCase().includes(q));
    if (!filtered.length) {
        box.innerHTML = '<div class="menus-source-empty">Aucune page trouvée.</div>';
        return;
    }
    box.innerHTML = filtered.map(p => {
        const already = existing.has(`page:${String(p.id)}`);
        return `
        <label class="source-check-row ${already ? 'is-already-added' : ''}">
            <input type="checkbox" class="src-check-page" value="${esc(p.id)}" data-label="${esc(p.name)}" ${already ? 'disabled' : ''}>
            <span>${esc(p.name)}</span>
            ${already
                ? '<span class="source-added-badge">Déjà dans le menu</span>'
                : (p.isHidden ? '<small>Brouillon</small>' : (p.isHomepage ? '<small>Accueil</small>' : ''))}
        </label>`;
    }).join('');
}

function renderSourcePlugins(query) {
    const box = document.getElementById('sourcePluginsList');
    if (!box) return;
    const existing = collectExistingRefs(treeData);
    const q = (query || '').trim().toLowerCase();
    const filtered = catalogData.filter(m => !q || m.label.toLowerCase().includes(q) || m.plugin.toLowerCase().includes(q));
    if (!filtered.length) {
        box.innerHTML = '<div class="menus-source-empty">Aucun module public.</div>';
        return;
    }
    box.innerHTML = filtered.map(m => {
        const already = existing.has(`plugin:${String(m.key)}`);
        return `
        <label class="source-check-row ${already ? 'is-already-added' : ''}" title="${esc(m.description || m.url)}">
            <input type="checkbox" class="src-check-plugin" value="${esc(m.key)}" data-label="${esc(m.label)}" data-icon="${esc(m.icon)}" ${already ? 'disabled' : ''}>
            <i class="${esc(m.icon)}"></i>
            <span>${esc(m.label)}</span>
            ${already ? '<span class="source-added-badge">Déjà dans le menu</span>' : `<small>${esc(m.plugin)}</small>`}
        </label>`;
    }).join('');
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
    const selector = kind === 'page' ? '.src-check-page:checked:not(:disabled)' : '.src-check-plugin:checked:not(:disabled)';
    const checks = Array.from(document.querySelectorAll(selector));
    if (!checks.length) {
        showToast('Cochez au moins un élément non présent à ajouter.', true);
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
        const data = await apiPost('/admin/menus/add', { location: activeLocation, items });
        checks.forEach(c => c.checked = false);
        renderTree();
        const addedCount = Array.isArray(data.createdIds) ? data.createdIds.length : items.length;
        const skipped = Number(data.skippedCount || 0);
        const msg = skipped > 0
            ? `${addedCount} élément(s) ajouté(s), ${skipped} déjà présent(s) ignoré(s).`
            : `${addedCount} élément(s) ajouté(s) au menu.`;
        showToast(msg);
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
    renderSources();
    const container = document.getElementById('menuTreeContainer');
    if (!container) return;
    if (!treeData.length) {
        container.innerHTML = `<div class="menu-empty-state"><i class="fa-regular fa-compass menus-empty-icon"></i>Aucun élément dans cet emplacement.<br>Ajoutez des pages, plugins ou liens depuis la colonne de gauche.</div>`;
        return;
    }
    container.innerHTML = treeData.map((node, idx) => renderNodeHtml(node, 0, idx, treeData.length)).join('');
}

function renderNodeHtml(node, parentId, idx, siblingsLen) {
    const idStr = idKey(node.id);
    const idAttr = esc(idStr);
    const isRoot = idKey(parentId) === '0';
    const isOpen = openEditors.has(idStr);
    const visMode = (node.visibility && node.visibility.mode) ? node.visibility.mode : 'public';
    const childrenHtml = (node.children && node.children.length)
        ? `<div class="menu-node-children">${node.children.map((c, ci) => renderNodeHtml(c, idStr, ci, node.children.length)).join('')}</div>`
        : '';
    const statusWarn = !node.targetActive ? `<span class="menu-vis-pill menu-vis-pill-warn">Cible inactive</span>` : '';

    return `
        <div class="menu-node-wrap" data-id="${idAttr}">
            <div class="menu-node-card ${node.isEnabled ? '' : 'is-disabled'}"
                 draggable="true"
                 ondragstart="onNodeDragStart(event, '${idAttr}')"
                 ondragover="onNodeDragOver(event, '${idAttr}')"
                 ondragleave="onNodeDragLeave(event)"
                 ondrop="onNodeDrop(event, '${idAttr}')"
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
                        <button type="button" class="menu-icon-btn" title="Monter" ${idx === 0 ? 'disabled' : ''} onclick="moveNode('${idAttr}', -1)"><i class="fa-solid fa-arrow-up"></i></button>
                        <button type="button" class="menu-icon-btn" title="Descendre" ${idx === siblingsLen - 1 ? 'disabled' : ''} onclick="moveNode('${idAttr}', 1)"><i class="fa-solid fa-arrow-down"></i></button>
                        <button type="button" class="menu-icon-btn" title="Mettre en sous-menu" ${idx === 0 ? 'disabled' : ''} onclick="indentNode('${idAttr}')"><i class="fa-solid fa-indent"></i></button>
                        <button type="button" class="menu-icon-btn" title="Remonter d'un niveau" ${isRoot ? 'disabled' : ''} onclick="outdentNode('${idAttr}')"><i class="fa-solid fa-outdent"></i></button>
                        <button type="button" class="menu-icon-btn" title="Modifier" onclick="toggleNodeEditor('${idAttr}')"><i class="fa-solid ${isOpen ? 'fa-chevron-up' : 'fa-pen'}"></i></button>
                    </div>
                </div>
                ${isOpen ? `
                <form class="menu-node-editor" onsubmit="saveNodeEdit(event, '${idAttr}')">
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
                        <input type="text" value="${esc(node.targetLabel || node.url)}" disabled>
                    </label>`}
                    <label>Icône FontAwesome
                        <input type="text" name="icon" value="${esc(node.icon)}" placeholder="ex: fa-solid fa-star">
                    </label>
                    <label>Classe CSS
                        <input type="text" name="css_class" value="${esc(node.cssClass)}">
                    </label>
                    <label>Ouverture & État
                        <div class="menu-editor-split">
                            <select name="target_attr">
                                <option value="_self" ${node.targetAttr === '_self' ? 'selected' : ''}>Même onglet</option>
                                <option value="_blank" ${node.targetAttr === '_blank' ? 'selected' : ''}>Nouvel onglet</option>
                            </select>
                            <select name="is_enabled">
                                <option value="1" ${node.isEnabled ? 'selected' : ''}>Affiché</option>
                                <option value="0" ${!node.isEnabled ? 'selected' : ''}>Masqué</option>
                            </select>
                        </div>
                    </label>
                    <div class="menu-editor-footer">
                        <button type="button" class="menu-btn-danger" onclick="removeNode('${idAttr}')">
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
    const key = idKey(id);
    if (openEditors.has(key)) openEditors.delete(key);
    else openEditors.add(key);
    renderTree();
}

async function saveNodeEdit(e, id) {
    e.preventDefault();
    const form = e.target;
    const payload = {
        id: idKey(id),
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
    const key = idKey(id);
    try {
        openEditors.delete(key);
        await apiPost('/admin/menus/delete', { id: key });
        renderTree();
        showToast('Élément retiré du menu.');
    } catch (err) {
        showToast(err.message, true);
    }
}

function findNodeAndList(list, id, parentNode = null) {
    const key = idKey(id);
    for (let i = 0; i < list.length; i++) {
        if (idKey(list[i].id) === key) return { list, index: i, node: list[i], parentNode };
        if (list[i].children && list[i].children.length) {
            const found = findNodeAndList(list[i].children, key, list[i]);
            if (found) return found;
        }
    }
    return null;
}

function containsNodeId(node, targetId) {
    const targetKey = idKey(targetId);
    if (idKey(node.id) === targetKey) return true;
    return (node.children || []).some(c => containsNodeId(c, targetKey));
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
    const key = idKey(id);
    dragState = { id: key };
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', key);
    setTimeout(() => {
        const card = e.target.closest('.menu-node-card');
        if (card) card.classList.add('is-dragging');
    }, 0);
}

function onNodeDragOver(e, targetId) {
    const targetKey = idKey(targetId);
    if (!dragState || dragState.id === targetKey) return;
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
    const targetKey = idKey(targetId);
    if (!dragState || dragState.id === targetKey) return;

    const src = findNodeAndList(treeData, dragState.id);
    const dst = findNodeAndList(treeData, targetKey);
    if (!src || !dst || containsNodeId(src.node, targetKey)) return;

    const [moved] = src.list.splice(src.index, 1);
    const dstAfter = findNodeAndList(treeData, targetKey);
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

