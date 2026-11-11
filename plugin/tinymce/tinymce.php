<?php

/**
 * @copyright (C) 2024, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

## Fonction d'installation

function tinymceInstall() {
}

## Hooks

/**
 * Génère le script et la configuration TinyMCE
 */
function tinymceGetEditorHeadHtml(bool $isAdmin = false): string {
    $user = \Core\Auth\UsersManager::getCurrentUser();
    $token = $user ? ($user->token ?? '') : '';
    $uploadUrl = ($token && IS_ADMIN) ? \Core\Router\Router::getInstance()->generate('filemanager-upload-api', ['token' => $token]) : '';

    $url = \Utils\Util::urlBuild(PLUGINS . 'tinymce/lib/tinymce/tinymce.min.js');
    $baseUrl = dirname($url);

    $imageUploadConfig = "";
    if ($uploadUrl !== '') {
        $imageUploadConfig = "images_upload_url: '" . $uploadUrl . "',\n        automatic_uploads: true,";
    }

    // Mascotte What (Banque d'images réservée au panel d'administration)
    $mascotToolbar = '';
    $mascotImageListConfig = '';
    $mascotSetupJs = '';

    if ($isAdmin) {
        $mascotDir = ROOT . 'images' . DIRECTORY_SEPARATOR . 'mascot' . DIRECTORY_SEPARATOR;
        $mascotWebBase = '/images/mascot/';
        $titlesMap = [
            'what_ensoleille.webp' => '☀️ What Ensoleillé',
            'what_pluvieux.webp' => '🌧️ What Pluvieux',
            'what_froid_neige.webp' => '❄️ What Froid & Neige',
            'what_hystory.webp' => '📜 What Histoire & Patrimoine',
            'what_skate.webp' => '🛹 What Skateur & Glisse',
            'what_halloween.webp' => '🎃 What Halloween',
            'what_empty.webp' => '📭 What Contenu Vide',
            'what_404.webp' => '❓ What Page Non Trouvée (404)',
            'what_404_2.webp' => '❓ What Page Non Trouvée (404 - v2)',
            'what_500.webp' => '⚠️ What Erreur Serveur (500)',
            'what_500_2.webp' => '⚠️ What Erreur Serveur (500 - v2)',
            'what_403.webp' => '🔒 What Accès Interdit (403)'
        ];

        $mascotList = [];
        if (is_dir($mascotDir)) {
            $files = glob($mascotDir . '*.webp');
            if ($files) {
                // Tri pour mettre météo et usage général en premier
                $priority = ['what_ensoleille.webp', 'what_pluvieux.webp', 'what_froid_neige.webp', 'what_hystory.webp', 'what_halloween.webp', 'what_empty.webp'];
                usort($files, function ($a, $b) use ($priority) {
                    $ba = basename($a);
                    $bb = basename($b);
                    $ia = array_search($ba, $priority);
                    $ib = array_search($bb, $priority);
                    if ($ia !== false && $ib !== false) return $ia - $ib;
                    if ($ia !== false) return -1;
                    if ($ib !== false) return 1;
                    return strcmp($ba, $bb);
                });

                foreach ($files as $f) {
                    $base = basename($f);
                    $title = $titlesMap[$base] ?? ('🦸 What ' . ucfirst(str_replace(['what_', '.webp', '_'], ['', '', ' '], $base)));
                    $mascotList[] = [
                        'title' => $title,
                        'value' => $mascotWebBase . $base
                    ];
                }
            }
        }

        if (!empty($mascotList)) {
            $mascotListJson = json_encode($mascotList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $mascotToolbar = ' mascot-what';
            $mascotImageListConfig = "image_list: " . $mascotListJson . ",\n        ";
            $mascotSetupJs = "const mascotItems = " . $mascotListJson . ";\n" .
                "            editor.ui.registry.addMenuButton('mascot-what', {\n" .
                "              text: '🦸 Mascotte What',\n" .
                "              tooltip: 'Insérer la mascotte What',\n" .
                "              fetch: (callback) => {\n" .
                "                const menuItems = mascotItems.map(m => ({\n" .
                "                  type: 'menuitem',\n" .
                "                  text: m.title,\n" .
                "                  onAction: () => {\n" .
                "                    editor.insertContent('<img src=\"' + m.value + '\" alt=\"' + m.title + '\" style=\"max-width: 240px; height: auto; display: inline-block;\" class=\"mascot-what-img\" />');\n" .
                "                  }\n" .
                "                }));\n" .
                "                callback(menuItems);\n" .
                "              }\n" .
                "            });\n";
        }
    }

    $titleChooseIcon = addslashes(\Core\Lang::get('tiny.choose-icon-title'));
    $labelEnterIcon = addslashes(\Core\Lang::get('tiny.enter-icon-code'));
    $btnCancel = addslashes(\Core\Lang::get('cancel'));
    $btnValidate = addslashes(\Core\Lang::get('validate'));
    $successTitle = addslashes(\Core\Lang::get('warning.success'));
    $warningTitle = addslashes(\Core\Lang::get('warning.warning'));
    $errorTitle = addslashes(\Core\Lang::get('warning.error'));
    $infoTitle = addslashes(\Core\Lang::get('warning.info'));
    $mapToolbar = '';
    $mapDialogJs = '';
    $mapSetupJs = '';

    if ($isAdmin) {
        $mapConfig = \Core\Core::getInstance()->callHook('map:editor-config', []);
        if (is_array($mapConfig) && !empty($mapConfig['enabled'])) {
            $defLat = (float)($mapConfig['lat'] ?? 47.0833);
            $defLon = (float)($mapConfig['lon'] ?? 2.4);
            $defZoom = (int)($mapConfig['zoom'] ?? 13);
            $leafletCssUrl = htmlspecialchars((string)($mapConfig['leafletCss'] ?? ''), ENT_QUOTES, 'UTF-8');
            $leafletJsUrl = htmlspecialchars((string)($mapConfig['leafletJs'] ?? ''), ENT_QUOTES, 'UTF-8');
            $mapToolbar = ' dialog-add-map';
            $mapDialogJs = <<<JS
window.__tinymceMapState = {
    editor: null,
    map: null,
    marker: null,
    assetsLoaded: false,
    defLat: {$defLat},
    defLon: {$defLon},
    defZoom: {$defZoom},
    leafletCss: "{$leafletCssUrl}",
    leafletJs: "{$leafletJsUrl}"
};

function ensureTinyMapAssets(cb) {
    const st = window.__tinymceMapState;
    if (typeof L !== 'undefined') {
        st.assetsLoaded = true;
        cb();
        return;
    }
    if (st.leafletCss && !document.querySelector('link[data-tiny-leaflet="1"]')) {
        const lnk = document.createElement('link');
        lnk.rel = 'stylesheet';
        lnk.href = st.leafletCss;
        lnk.setAttribute('data-tiny-leaflet', '1');
        document.head.appendChild(lnk);
    }
    if (st.leafletJs) {
        let scr = document.querySelector('script[data-tiny-leaflet="1"]');
        if (!scr) {
            scr = document.createElement('script');
            scr.src = st.leafletJs;
            scr.setAttribute('data-tiny-leaflet', '1');
            document.head.appendChild(scr);
        }
        scr.addEventListener('load', () => {
            st.assetsLoaded = true;
            cb();
        }, { once: true });
    }
}

function openTinyMapPicker(editor) {
    const st = window.__tinymceMapState;
    st.editor = editor;
    let modal = document.getElementById('tinymce-map-picker');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'tinymce-map-picker';
        modal.style.cssText = 'display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.62); padding:18px; overflow-y:auto;';
        modal.innerHTML = `
            <div style="max-width:740px; margin:20px auto; background:#fff; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 24px 48px rgba(15,23,42,0.28); overflow:hidden; font-family:inherit;">
                <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                    <strong style="font-size:1rem; color:#0f172a;"><i class="fa-solid fa-map-location-dot" style="color:#1d4ed8;"></i> Sélectionner un emplacement sur la carte</strong>
                    <button type="button" id="tinyMapClose" style="background:none; border:none; font-size:1.15rem; color:#64748b; cursor:pointer; padding:4px 8px;"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div style="padding:16px 18px; display:flex; flex-direction:column; gap:12px;">
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <input type="text" id="tinyMapSearch" placeholder="Rechercher une adresse ou une ville (ex : Bourges)..." style="flex:1; min-width:220px; margin:0;" />
                        <button type="button" id="tinyMapSearchBtn" class="button small"><i class="fa-solid fa-magnifying-glass"></i> Chercher</button>
                    </div>
                    <div style="font-size:0.82rem; color:#475569;"><i class="fa-solid fa-computer-mouse"></i> Cliquez directement sur la carte ou faites glisser le marqueur pour choisir le point.</div>
                    <div id="tinyMapCanvas" style="width:100%; height:320px; border-radius:10px; border:1px solid #cbd5e1; background:#f1f5f9;"></div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:10px;">
                        <div>
                            <label style="font-size:0.78rem; font-weight:600;">Latitude</label>
                            <input type="text" id="tinyMapLat" style="width:100%; margin:4px 0 0;" />
                        </div>
                        <div>
                            <label style="font-size:0.78rem; font-weight:600;">Longitude</label>
                            <input type="text" id="tinyMapLon" style="width:100%; margin:4px 0 0;" />
                        </div>
                        <div>
                            <label style="font-size:0.78rem; font-weight:600;">Zoom</label>
                            <input type="number" id="tinyMapZoom" min="1" max="21" style="width:100%; margin:4px 0 0;" />
                        </div>
                        <div>
                            <label style="font-size:0.78rem; font-weight:600;">Hauteur (px, optionnel)</label>
                            <input type="number" id="tinyMapHeight" placeholder="360" min="150" max="1200" style="width:100%; margin:4px 0 0;" />
                        </div>
                    </div>
                    <div>
                        <label style="font-size:0.78rem; font-weight:600;">Titre du marqueur / info-bulle (optionnel)</label>
                        <input type="text" id="tinyMapTitle" placeholder="Ex : Point de rendez-vous" style="width:100%; margin:4px 0 0;" />
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px; padding:12px 18px; background:#f8fafc; border-top:1px solid #e2e8f0;">
                    <button type="button" id="tinyMapCancel" class="button">{$btnCancel}</button>
                    <button type="button" id="tinyMapInsert" class="button primary"><i class="fa-solid fa-check"></i> Insérer la carte</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        const closeModal = () => { modal.style.display = 'none'; };
        document.getElementById('tinyMapClose').addEventListener('click', closeModal);
        document.getElementById('tinyMapCancel').addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

        const latIn = document.getElementById('tinyMapLat');
        const lonIn = document.getElementById('tinyMapLon');
        const zoomIn = document.getElementById('tinyMapZoom');

        function moveMarker(lat, lon, pan = true) {
            latIn.value = Number(lat).toFixed(6);
            lonIn.value = Number(lon).toFixed(6);
            if (st.marker) {
                st.marker.setLatLng([lat, lon]);
            } else if (st.map) {
                st.marker = L.marker([lat, lon], { draggable: true }).addTo(st.map);
                st.marker.on('dragend', (ev) => {
                    const p = ev.target.getLatLng();
                    latIn.value = p.lat.toFixed(6);
                    lonIn.value = p.lng.toFixed(6);
                });
            }
            if (pan && st.map) st.map.panTo([lat, lon]);
        }

        document.getElementById('tinyMapSearchBtn').addEventListener('click', async () => {
            const q = document.getElementById('tinyMapSearch').value.trim();
            if (!q) return;
            try {
                const res = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q));
                const list = await res.json();
                if (Array.isArray(list) && list.length > 0) {
                    const la = parseFloat(list[0].lat);
                    const lo = parseFloat(list[0].lon);
                    if (!isNaN(la) && !isNaN(lo)) {
                        moveMarker(la, lo, true);
                        if (st.map) st.map.setView([la, lo], Math.max(st.map.getZoom(), 14));
                    }
                } else {
                    alert('Lieu introuvable.');
                }
            } catch (err) {
                alert('Recherche indisponible.');
            }
        });
        document.getElementById('tinyMapSearch').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('tinyMapSearchBtn').click();
            }
        });

        [latIn, lonIn].forEach(el => {
            el.addEventListener('change', () => {
                const la = parseFloat(latIn.value);
                const lo = parseFloat(lonIn.value);
                if (!isNaN(la) && !isNaN(lo)) moveMarker(la, lo, true);
            });
        });

        zoomIn.addEventListener('change', () => {
            const z = parseInt(zoomIn.value, 10);
            if (!isNaN(z) && st.map) st.map.setZoom(Math.max(1, Math.min(21, z)));
        });

        document.getElementById('tinyMapInsert').addEventListener('click', () => {
            const escAttr = (v) => String(v || '').replace(/"/g, '').trim();
            const parts = [];
            if (escAttr(latIn.value)) parts.push('lat="' + escAttr(latIn.value) + '"');
            if (escAttr(lonIn.value)) parts.push('lon="' + escAttr(lonIn.value) + '"');
            if (escAttr(zoomIn.value)) parts.push('zoom="' + escAttr(zoomIn.value) + '"');
            const ttl = escAttr(document.getElementById('tinyMapTitle').value);
            const hgt = escAttr(document.getElementById('tinyMapHeight').value);
            if (ttl) parts.push('title="' + ttl + '"');
            if (hgt) parts.push('height="' + hgt + '"');
            const shortcode = '[map' + (parts.length ? ' ' + parts.join(' ') : '') + ']';
            if (st.editor) {
                st.editor.execCommand('InsertHTML', false, '<p>' + shortcode + '</p>');
            }
            closeModal();
        });
    }

    modal.style.display = 'block';
    document.getElementById('tinyMapLat').value = st.defLat.toFixed(6);
    document.getElementById('tinyMapLon').value = st.defLon.toFixed(6);
    document.getElementById('tinyMapZoom').value = st.defZoom;
    document.getElementById('tinyMapTitle').value = '';
    document.getElementById('tinyMapHeight').value = '';

    ensureTinyMapAssets(() => {
        if (!st.map) {
            st.map = L.map('tinyMapCanvas').setView([st.defLat, st.defLon], st.defZoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
                maxZoom: 19
            }).addTo(st.map);
            st.marker = L.marker([st.defLat, st.defLon], { draggable: true }).addTo(st.map);
            st.marker.on('dragend', (ev) => {
                const p = ev.target.getLatLng();
                document.getElementById('tinyMapLat').value = p.lat.toFixed(6);
                document.getElementById('tinyMapLon').value = p.lng.toFixed(6);
            });
            st.map.on('click', (e) => {
                document.getElementById('tinyMapLat').value = e.latlng.lat.toFixed(6);
                document.getElementById('tinyMapLon').value = e.latlng.lng.toFixed(6);
                if (st.marker) st.marker.setLatLng(e.latlng);
            });
            st.map.on('zoomend', () => {
                document.getElementById('tinyMapZoom').value = st.map.getZoom();
            });
        } else {
            st.map.setView([st.defLat, st.defLon], st.defZoom);
            if (st.marker) st.marker.setLatLng([st.defLat, st.defLon]);
        }
        setTimeout(() => { if (st.map) st.map.invalidateSize(); }, 120);
    });
}
JS;
            $mapSetupJs = "            editor.ui.registry.addButton('dialog-add-map', {\n" .
                "              text: 'Carte',\n" .
                "              tooltip: 'Insérer une carte interactive [map]',\n" .
                "              onAction: () => openTinyMapPicker(editor)\n" .
                "            });\n";
        }
    }

    $locale = \Core\Lang::getLocale();
    $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl') . '/';
    $editorCss = \Utils\Util::urlBuild(PLUGINS . 'tinymce/template/editor.css');
    $fontIcon = FONTICON;

    $html = <<<HTML
<script src="{$url}"></script>
<script>
window.tinymce = window.tinymce || {};
window.tinymce.baseURL = "{$baseUrl}";

const dialogIcon = {
    title: "{$titleChooseIcon}",
    body: {
      type: "panel",
      items: [
        {
          type: "input",
          name: "iconCode",
          placeholder: '<i class="fa-brands fa-font-awesome"></i>',
          label: "{$labelEnterIcon}"
        }
      ]
    },
    buttons: [
      {
        type: "cancel",
        name: "closeButton",
        text: "{$btnCancel}"
      },
      {
        type: "submit",
        name: "submitButton",
        buttonType: "primary",
        text: "{$btnValidate}"
      }
    ],
    onSubmit: (api) => {
      const data = api.getData();
      let insertText = data.iconCode.replace('<i class=', '<span class=');
      insertText = insertText.replace('></i>', '>&nbsp;</span> ');
      tinymce.activeEditor.execCommand('InsertHTML', false, insertText);
      api.close();
    }
};
{$mapDialogJs}

function initTinyMceSafe() {
    if (typeof tinymce === 'undefined') {
        setTimeout(initTinyMceSafe, 50);
        return;
    }

    const mceOptions = {
        selector: 'textarea.editor',
        license_key: 'gpl',
        promotion: false,
        branding: false,
        language: '{$locale}',
        toolbar_mode: 'wrap',
        {$imageUploadConfig}
        {$mascotImageListConfig}convert_urls: false,
        document_base_url: '{$siteUrl}',
        browser_spellcheck: true,
        image_advtab: true,
        sandbox_iframes: false,
        extended_valid_elements: 'iframe[src|title|width|height|allowfullscreen|frameborder|allow|referrerpolicy|loading|class|style]',
        plugins: 'advlist anchor autolink autoresize charmap code codesample emoticons fullscreen help image insertdatetime link lists media nonbreaking searchreplace table visualblocks visualchars wordcount',
        toolbar: 'blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | forecolor backcolor removeformat | insertfile image media link{$mascotToolbar} | dialog-add-icon{$mapToolbar} charmap emoticons codesample | fullscreen',
        max_height: 600,
        image_caption: true,
        content_css: [
            '{$editorCss}',
            '{$fontIcon}'
        ],
        setup: (editor) => {
            editor.ui.registry.addButton('dialog-add-icon', {
              icon: 'user',
              tooltip: '{$titleChooseIcon}',
              onAction: () => editor.windowManager.open(dialogIcon)
            });
            {$mascotSetupJs}{$mapSetupJs}
        },
        style_formats: [
            { name: 'success', title: '{$successTitle}', block: 'div', classes: [ 'success' ]},
            { name: 'warning', title: '{$warningTitle}', block: 'div', classes: [ 'warning' ]},
            { name: 'error', title: '{$errorTitle}', block: 'div', classes: [ 'error' ]},
            { name: 'info', title: '{$infoTitle}', block: 'div', classes: [ 'info' ]}
        ],
        style_formats_merge: true,
        codesample_languages: [
            { text: 'HTML', value: 'HTML' },
            { text: 'XML', value: 'XML' },
            { text: 'Tpl', value: 'tpl' },
            { text: 'JavaScript', value: 'javascript' },
            { text: 'CSS', value: 'css' },
            { text: 'PHP', value: 'php' },
            { text: 'Ruby', value: 'ruby' },
            { text: 'Python', value: 'python' },
            { text: 'Java', value: 'java' },
            { text: 'C', value: 'c' },
            { text: 'C#', value: 'csharp' },
            { text: 'C++', value: 'cpp' }
        ]
    };

    tinymce.init(mceOptions);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTinyMceSafe);
} else {
    initTinyMceSafe();
}
</script>
HTML;

    return $html;
}

/**
 * Hook endAdminHead
 */
function tinymceAdminHead($param = ''): string {
    return (string)$param . tinymceGetEditorHeadHtml(true);
}

/**
 * Hook Front Head conditionnel (uniquement sur les pages de formulaire/édition)
 */
function tinymceFrontHead($param = ''): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $isEditorPage = (
        str_contains($uri, '/PoiEdit') || 
        str_contains($uri, '/poi-edit') ||
        str_contains($uri, '/poi/edit') ||
        str_contains($uri, '/poi/create') ||
        str_contains($uri, '/poimanager') ||
        str_contains($uri, '/business-dashboard') ||
        str_contains($uri, '/localfeed') ||
        str_contains($uri, '/p-') ||
        str_contains($uri, '/agenda/event') ||
        str_contains($uri, '/agenda/add') ||
        str_contains($uri, '/agenda/edit') ||
        str_contains($uri, '/offers/create') ||
        str_contains($uri, '/offers/edit') ||
        str_contains($uri, '/contribution')
    );

    if (!$isEditorPage) {
        return (string)$param;
    }

    return (string)$param . tinymceGetEditorHeadHtml(false);
}

function tinymceInsertScriptBeforeEditor() {
    echo '<script>
    function processInsertImgInEditor(editorId, imgUrl, isPicture) {
        var isPic = (typeof isPicture === "boolean") ? isPicture : /\.(jpe?g|png|gif|webp|bmp|svg|ico)(\?.*)?$/i.test(String(imgUrl || ""));
        var safeUrl = String(imgUrl || "").replace(/"/g, "&quot;");
        var html = isPic
            ? \'<img src="\' + safeUrl + \'" alt="" />\'
            : \'<a href="\' + safeUrl + \'" target="_blank" rel="noopener">\' + safeUrl.split("/").pop() + \'</a>\';

        var ed = (typeof tinyMCE !== "undefined" && editorId) ? tinyMCE.get(editorId) : null;
        if (ed) {
            ed.insertContent(html);
            return;
        }
        var field = editorId ? document.getElementById(editorId) : null;
        if (field) {
            field.value += html;
        }
    }
    </script>';
}
