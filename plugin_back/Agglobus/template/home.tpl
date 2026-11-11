{% if CORE.callHook('map:is-enabled') %}
    {{ CORE.callHook('map:load-assets')|raw }}
{% endif %}
<!-- AGGLOBUS LUMIERE V3 REFINED DESIGN -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<div class="agglo-lumiere-container">
    <header class="agglo-header">
        <div class="logo">
            <div class="logo-icon">AG</div>
            <div class="logo-text">agglo<span>bus</span></div>
        </div>
        <div class="header-status">
            <div class="update-ticker" id="last-sync-tag">
                <i class="fa-solid fa-rotate"></i> <span id="sync-time">à l'instant</span>
            </div>
            <div class="live-pill">
                <div class="live-dot"></div>
                Direct
            </div>
        </div>
    </header>

    <div class="agglo-controls">
        <div class="agglo-controls-left">
            <!-- BOUTON TIROIR GAUCHE (AFFICHER / MASQUER LIGNES) -->
            <button id="toggle-sidebar-btn" class="drawer-toggle-btn active" onclick="toggleSidebar()" title="Afficher ou masquer la liste des lignes">
                <i class="fa-solid fa-bars"></i><span>Lignes</span>
            </button>

            <div class="search-box-v3">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="agglo-search-input" placeholder="Ligne ou arrêt..." oninput="handleSearch(this.value)">
                <button id="clear-search" class="hidden" onclick="clearSearch()"><i class="fa-solid fa-circle-xmark"></i></button>
            </div>
            <button id="agglo-locate-btn" class="btn-v3 primary">
                <i class="fa-solid fa-location-crosshairs"></i> Autour de moi
            </button>
        </div>

        <div class="agglo-controls-right">
            <!-- SÉLECTEUR DE VUE CARTE (HORS MAP) -->
            <div class="map-view-switch">
                <button class="view-switch-btn active" id="btn-layer-standard" onclick="setMapLayer('standard', this)" title="Plan standard">
                    <i class="fa-solid fa-map"></i> <span>Plan</span>
                </button>
                <button class="view-switch-btn" id="btn-layer-satellite" onclick="setMapLayer('satellite', this)" title="Vue satellite">
                    <i class="fa-solid fa-satellite"></i> <span>Satellite</span>
                </button>
                <button class="view-switch-btn" id="btn-layer-night" onclick="setMapLayer('night', this)" title="Mode nuit">
                    <i class="fa-solid fa-moon"></i> <span>Nuit</span>
                </button>
            </div>

            <!-- OUTILS NAVIGATION CARTE (HORS MAP) -->
            <div class="map-nav-tools">
                <button class="tool-btn-icon" onclick="resetMap()" title="Recentrer sur Bourges"><i class="fa-solid fa-house"></i></button>
                <div class="tool-zoom-group">
                    <button class="tool-btn-icon" onclick="zoomInMap()" title="Zoom avant"><i class="fa-solid fa-plus"></i></button>
                    <button class="tool-btn-icon" onclick="zoomOutMap()" title="Zoom arrière"><i class="fa-solid fa-minus"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- ZONE CENTRALE : TIROIR GAUCHE + CARTE -->
    <div class="agglo-main">
        <!-- SIDEBAR GAUCHE (TIROIR RETRACTABLE) -->
        <aside class="agglo-sidebar" id="agglo-sidebar">
            <div class="sidebar-header-row">
                <div class="sidebar-title" id="sidebar-context-title">Lignes en service</div>
                <div class="sidebar-header-actions">
                    <button class="btn-icon-tiny hidden" id="back-to-lines" onclick="clearSearch()" title="Retour aux lignes">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <button class="btn-icon-tiny" onclick="toggleSidebar()" title="Replier le panneau des lignes">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                </div>
            </div>
            <div id="lines-list-container" class="scrollable">
                <div class="loading-state">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Chargement des lignes...</p>
                </div>
            </div>
        </aside>

        <!-- CARTE CENTRALE (PLEIN ÉCRAN QUAND TIROIR REPLIÉ) -->
        <main class="agglo-content">
            <div id="agglobus-map"></div>
        </main>
    </div>
</div>

<!-- DETAIL DU TRAJET / ARRÊT (SORTI DU CONTENEUR DE LA CARTE, PROPREMENT EN DESSOUS) -->
<section id="agglo-detail-panel" class="detail-bottom-section detail-panel-v3 hidden">
    <div class="detail-header">
        <div class="detail-badge" id="detail-line-badge">--</div>
        <div class="detail-titles">
            <h3 id="detail-line-name">Sélectionnez une ligne</h3>
            <div class="detail-direction" id="detail-line-dir">Direction...</div>
        </div>
        <div class="detail-header-actions">
            <button class="header-action-btn switch-dir" onclick="toggleDirection()" title="Inverser le sens de la ligne"><i class="fa-solid fa-right-left"></i></button>
            <button class="header-action-btn close-detail-btn" onclick="closeDetail()" title="Fermer le volet"><i class="fa-solid fa-xmark"></i></button>
        </div>
    </div>

    <!-- CORRESPONDANCES (TRANSFER HUB) -->
    <div id="transfer-hub-section" class="transfer-hub hidden">
        <div class="transfer-hub-label">
            <i class="fa-solid fa-shuffle"></i> Lignes en correspondance
        </div>
        <div id="transfer-pills-container" class="transfer-pills-row"></div>
    </div>

    <div class="detail-tabs">
        <button class="tab-btn active" id="tab-btn-passages" onclick="switchTab('passages', this)">Départs</button>
        <button class="tab-btn" id="tab-btn-horaires" onclick="switchTab('horaires', this)">Horaires</button>
        <button class="tab-btn" id="tab-btn-arrets" onclick="switchTab('arrets', this)">Itinéraire</button>
        <button class="tab-btn" id="tab-btn-infos" onclick="switchTab('infos', this)">Infos</button>
    </div>

    <div class="tab-body">
        <!-- TAB PASSAGES / GRAND TABLEAU DES DÉPARTS -->
        <div id="tab-passages" class="tab-pane active">
            <div class="station-board">
                <div class="board-top-bar">
                    <div class="board-title">
                        <div class="board-live-indicator"></div>
                        Tableau des départs
                    </div>
                    <div class="board-clock" id="board-live-clock">--:--</div>
                </div>
                <div class="board-columns-header">
                    <div>Ligne</div>
                    <div>Destination</div>
                    <div>Prévu</div>
                    <div style="text-align:right;">Départ</div>
                </div>
                <div id="passages-list-v3" class="board-rows">
                    <p style="color:var(--t-board-muted);font-size:12px;padding:12px;text-align:center;">Chargement des départs...</p>
                </div>
            </div>

            <button id="cta-all-day-btn" class="all-day-cta-btn hidden" onclick="switchTab('horaires', document.getElementById('tab-btn-horaires'))">
                <i class="fa-solid fa-calendar-days"></i> Consulter toute la grille du jour (<span id="all-day-total-badge">0</span> passages)
            </button>
        </div>

        <!-- TAB TOUTES LES HORAIRES -->
        <div id="tab-horaires" class="tab-pane">
            <div class="section-label">Toutes les horaires de la journée</div>
            <div id="all-day-filters" class="sched-filters"></div>
            <div id="all-day-schedule-v3"></div>
        </div>

        <!-- TAB ITINÉRAIRE -->
        <div id="tab-arrets" class="tab-pane">
            <div class="section-label">Itinéraire complet</div>
            <div id="stops-timeline-v3" class="v3-timeline"></div>
        </div>

        <!-- TAB INFOS -->
        <div id="tab-infos" class="tab-pane">
            <div class="section-label">Information réseau</div>
            <div class="info-card-v3">
                <p><strong>Ligne / Arrêt :</strong> <span id="info-line-num">--</span></p>
                <p><strong>Réseau :</strong> AggloBus Bourges</p>
                <p><strong>Service :</strong> Régulier urbain</p>
                <p><strong>Actualisation :</strong> Estimation GTFS directe</p>
                <div class="alert-box hidden" id="line-alert"></div>
            </div>
        </div>
    </div>
</section>

<link rel="stylesheet" href="/plugin/Agglobus/assets/css/public.css?v=5">

<script>
let aggloMap = null;
let activeRoutes = new Set();
let routeShapes = {}; // id => polyline
let busMarkers = {}; // trip_id => marker
let routeColors = {};
let allLines = [];
let currentLine = null;
let currentDirection = "0";
let selectedStopId = null;
let isSearching = false;
let isSidebarOpen = true;

function toggleSidebar() {
    isSidebarOpen = !isSidebarOpen;
    const sidebar = document.getElementById('agglo-sidebar');
    const toggleBtn = document.getElementById('toggle-sidebar-btn');
    if (sidebar) {
        sidebar.classList.toggle('collapsed', !isSidebarOpen);
    }
    if (toggleBtn) {
        toggleBtn.classList.toggle('active', isSidebarOpen);
    }
    setTimeout(() => {
        if (aggloMap && aggloMap.map) {
            aggloMap.map.invalidateSize();
        }
    }, 320);
}

document.addEventListener('DOMContentLoaded', async function() {
    if (typeof UniversalMap !== 'undefined') {
        aggloMap = new UniversalMap("agglobus-map", {
            lat: 47.0810, lon: 2.3986, zoom: 13, markerIcon: 'bus'
        });
        
        // Fix double zoom: Remove default Leaflet controls
        if (aggloMap && aggloMap.map) {
            aggloMap.map.zoomControl.remove();
        }
    } else {
        const mapEl = document.getElementById('agglobus-map');
        if (mapEl) {
            mapEl.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#888;font-size:1.1rem;gap:10px;"><i class="fa-solid fa-map-location-dot" style="font-size:2rem;color:#ccc;"></i><span>Service cartographique inactif</span></div>';
        }
        const rightControls = document.querySelector('.agglo-controls-right');
        if (rightControls) rightControls.style.display = 'none';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDetail();
    });

    await loadInitialData();
    startBusTracking();
    updateBoardClock();
    setInterval(updateBoardClock, 1000);
});

async function safeJson(response) {
    if (!response.ok) {
        console.warn("HTTP error:", response.status);
        return {};
    }
    const text = await response.text();
    if (!text || text.trim() === '') return {};
    try { return JSON.parse(text); }
    catch (e) {
        const start = text.search(/\{|\[/);
        if (start >= 0) {
            try { return JSON.parse(text.substring(start)); } catch (_) {}
        }
        return {};
    }
}

async function loadInitialData() {
    try {
        const response = await fetch('/agglobus/api/routes');
        allLines = await safeJson(response);
        if (!Array.isArray(allLines)) {
            console.warn("Routes data is not an array:", allLines);
            allLines = [];
        }
        renderLinesList(allLines);
        allLines.forEach(l => { routeColors[l.id] = l.color; });
    } catch (e) { console.error("Err routes:", e); }
}

function renderLinesList(lines) {
    const container = document.getElementById('lines-list-container');
    if (!container) return;
    if (!Array.isArray(lines)) {
        container.innerHTML = '<div class="p-3 text-muted">Données indisponibles</div>';
        return;
    }
    container.innerHTML = lines.map(l => {
        const isActive = activeRoutes.has(l.id);
        return `
            <div class="line-card-v3 ${isActive ? 'active' : ''}" onclick="handleLineClick('${l.id}')" style="--lc: ${l.color}">
                <div class="line-badge-v3">${l.short_name}</div>
                <div class="l-info">
                    <div class="l-name">${l.long_name}</div>
                    <div class="l-meta">Direct disponible</div>
                </div>
                <div class="multi-toggle">
                    ${isActive ? '<i class="fa-solid fa-check"></i>' : ''}
                </div>
            </div>
        `;
    }).join('');
}

async function handleLineClick(id) {
    // Multi-select logic: Toggle shape
    if (activeRoutes.has(id)) {
        activeRoutes.delete(id);
        removeRouteShape(id);
    } else {
        activeRoutes.add(id);
        await addRouteShape(id);
    }
    
    // Primary focus logic: Open detail panel
    currentLine = allLines.find(l => l.id == id);
    currentDirection = "0";
    selectedStopId = null; 
    const detailPanel = document.getElementById('agglo-detail-panel');
    if (detailPanel) {
        detailPanel.classList.remove('hidden');
        setTimeout(() => {
            detailPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 120);
    }
    updateDetailHeader();
    await loadLineStops();
    
    renderLinesList(isSearching ? getFilteredLines() : allLines);
}

function updateDetailHeader() {
    const badge = document.getElementById('detail-line-badge');
    badge.textContent = currentLine.short_name;
    badge.style.setProperty('--lc', currentLine.color);
    document.getElementById('detail-line-name').textContent = currentLine.long_name;
    document.getElementById('info-line-num').textContent = currentLine.short_name;
}

let lineStopMarkers = [];

function clearLineStopMarkers() {
    if (aggloMap && aggloMap.map) {
        lineStopMarkers.forEach(m => aggloMap.map.removeLayer(m));
    }
    lineStopMarkers = [];
}

function plotLineStopsOnMap(stops) {
    clearLineStopMarkers();
    if (!aggloMap || !aggloMap.map || !Array.isArray(stops)) return;
    const color = (currentLine && currentLine.color) ? currentLine.color : '#ff8c42';
    
    stops.forEach(s => {
        if (!s.lat || !s.lon) return;
        const marker = L.circleMarker([s.lat, s.lon], {
            radius: 6,
            fillColor: '#ffffff',
            color: color,
            weight: 3,
            opacity: 0.95,
            fillOpacity: 1
        }).addTo(aggloMap.map);

        marker.bindTooltip(`<strong>${s.stop_name}</strong><br><span style="font-size:11px;color:#888">Cliquer pour voir les départs</span>`, { direction: 'top' });
        marker.on('click', () => {
            selectStop(s.stop_id, s.stop_name, s.lat, s.lon);
        });
        lineStopMarkers.push(marker);
    });
}

async function loadLineStops() {
    if (!currentLine) return;
    try {
        const res = await fetch(`/agglobus/api/route-stops?route_id=${currentLine.id}`);
        const data = await safeJson(res);
        if (!currentLine) return;
        currentLine.directions_data = data;
        const dirKeys = Object.keys(data).sort();
        if (dirKeys.length === 0) {
            document.getElementById('detail-line-dir').textContent = "Terminus non renseigné";
            document.getElementById('stops-timeline-v3').innerHTML = '<div class="p-3 text-muted">Aucun arrêt répertorié pour cette ligne.</div>';
            clearLineStopMarkers();
            return;
        }
        const dirKey = dirKeys[currentDirection == "0" ? 0 : 1] || dirKeys[0];
        const stops = data[dirKey];
        if (Array.isArray(stops) && stops.length > 0) {
            const terminus = stops[stops.length - 1];
            document.getElementById('detail-line-dir').textContent = "Vers " + (terminus ? terminus.stop_name : "Terminus");
            renderTimeline(stops);
            plotLineStopsOnMap(stops);
            refreshPassages(stops[0].stop_id);
        } else {
            document.getElementById('detail-line-dir').textContent = "Terminus non renseigné";
            document.getElementById('stops-timeline-v3').innerHTML = '<div class="p-3 text-muted">Aucun arrêt pour cette direction.</div>';
            clearLineStopMarkers();
        }
    } catch (e) { console.error("Err stops:", e); }
}

function renderTimeline(stops) {
    const el = document.getElementById('stops-timeline-v3');
    if (!el) return;
    if (!Array.isArray(stops) || stops.length === 0) {
        el.innerHTML = '<div class="p-3 text-muted">Aucun arrêt.</div>';
        return;
    }
    el.innerHTML = stops.map(s => `
        <div class="stop-v3" onclick="selectStop('${s.stop_id}', '${(s.stop_name || '').replace(/'/g, "\\'")}', ${s.lat}, ${s.lon})">
            <div class="stop-v3-dot"></div>
            <span class="stop-v3-name">${s.stop_name}</span>
        </div>
    `).join('');
}

function updateBoardClock() {
    const clockEl = document.getElementById('board-live-clock');
    if (!clockEl) return;
    const now = new Date();
    clockEl.textContent = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

let currentStopUpcoming = [];
let currentStopAllDay = [];
let currentStopLines = [];
let activeBoardFilter = null;
let passageRefreshInterval = null;

async function selectStop(id, name, lat, lon, fromNearby = false) {
    selectedStopId = id;
    activeBoardFilter = null;
    document.querySelectorAll('.stop-v3').forEach(i => i.classList.remove('active'));
    
    // If coming from nearby, we focus SOLELY on the stop, so clear line context
    if (fromNearby) {
        currentLine = null;
        document.querySelectorAll('.line-card-v3').forEach(c => c.classList.remove('active'));
    }

    // Handle specific line stop selection vs global stop selection
    const stopEl = Array.from(document.querySelectorAll('.stop-v3-name')).find(el => el.textContent === name);
    if (stopEl) stopEl.parentElement.classList.add('active');

    if (aggloMap) {
        aggloMap.clearMarkers();
        aggloMap.addMarker(lat, lon, `<strong>${name}</strong>`);
        aggloMap.map.setView([lat, lon], 16);
    }
    
    const detailPanel = document.getElementById('agglo-detail-panel');
    if (detailPanel) {
        detailPanel.classList.remove('hidden');
        setTimeout(() => {
            detailPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 120);
    }
    
    if (!currentLine) {
        // Stop specific mode (no line context)
        const badge = document.getElementById('detail-line-badge');
        badge.textContent = '🚏';
        badge.style.setProperty('--lc', '#10b981');
        document.getElementById('detail-line-name').textContent = name;
        document.getElementById('detail-line-dir').textContent = "Chargement des correspondances...";
        document.getElementById('stops-timeline-v3').innerHTML = '<p style="color:var(--t-muted);font-size:12px;padding:20px;text-align:center;">Sélectionnez une ligne dans la liste ou une correspondance ci-dessus pour afficher son tracé complet.</p>';
        document.querySelector('.switch-dir').classList.add('hidden');
    } else {
        document.querySelector('.switch-dir').classList.remove('hidden');
    }

    switchTab('passages', document.getElementById('tab-btn-passages'));
    await refreshPassages(id);
}

async function refreshPassages(stopId) {
    if (passageRefreshInterval) clearInterval(passageRefreshInterval);
    
    const updateFunc = async () => {
        try {
            const res = await fetch(`/agglobus/api/rt?stop_id=${stopId}`);
            const data = await safeJson(res);
            
            currentStopUpcoming = Array.isArray(data.upcoming) ? data.upcoming : (Array.isArray(data.arrivals) ? data.arrivals : []);
            currentStopAllDay = Array.isArray(data.all_day) ? data.all_day : [];
            currentStopLines = Array.isArray(data.lines) ? data.lines : [];

            // Update stop header info if in stop mode
            if (!currentLine) {
                const subEl = document.getElementById('detail-line-dir');
                if (subEl) {
                    const lCount = currentStopLines.length;
                    const dCount = currentStopAllDay.length;
                    subEl.textContent = `${lCount} ligne${lCount > 1 ? 's' : ''} en correspondance • ${dCount} passage${dCount > 1 ? 's' : ''} aujourd'hui`;
                }
            }

            // 1. Render Transfer Hub
            renderTransferHub();

            // 2. Render Station Board
            renderStationBoard();

            // 3. Update CTA for All-day timetable
            const ctaBtn = document.getElementById('cta-all-day-btn');
            const totalBadge = document.getElementById('all-day-total-badge');
            if (currentStopAllDay.length > 0) {
                if (ctaBtn) ctaBtn.classList.remove('hidden');
                if (totalBadge) totalBadge.textContent = currentStopAllDay.length;
            } else {
                if (ctaBtn) ctaBtn.classList.add('hidden');
            }

            // 4. Render All-day schedule
            renderAllDaySchedule();

        } catch (e) {
            console.error("refreshPassages error:", e);
        }
    };

    await updateFunc();
    passageRefreshInterval = setInterval(updateFunc, 30000);
}

function renderTransferHub() {
    const hubSec = document.getElementById('transfer-hub-section');
    const pillsCont = document.getElementById('transfer-pills-container');
    if (!hubSec || !pillsCont) return;

    if (!currentStopLines || currentStopLines.length === 0) {
        hubSec.classList.add('hidden');
        return;
    }

    hubSec.classList.remove('hidden');
    pillsCont.innerHTML = `
        <button class="transfer-pill ${!activeBoardFilter ? 'active' : ''}" onclick="toggleLineFilter(null)">
            <span>Toutes (${currentStopLines.length})</span>
        </button>
    ` + currentStopLines.map(l => {
        const isActive = activeBoardFilter === l.short_name;
        const dests = Array.isArray(l.destinations) ? l.destinations.join(' / ') : '';
        return `
            <button class="transfer-pill ${isActive ? 'active' : ''}" onclick="toggleLineFilter('${l.short_name}', '${l.route_id}')" title="${dests} (${l.passages_count} passages)">
                <span class="transfer-pill-badge" style="background:${l.color || '#ff8c42'}; color:${l.text_color || '#ffffff'};">${l.short_name}</span>
                <span>Ligne ${l.short_name}</span>
            </button>
        `;
    }).join('');
}

function toggleLineFilter(lineName, routeId = null) {
    activeBoardFilter = (activeBoardFilter === lineName) ? null : lineName;
    renderTransferHub();
    renderStationBoard();
    renderAllDaySchedule();

    if (routeId) {
        addRouteShape(routeId);
    }
}

function renderStationBoard() {
    const list = document.getElementById('passages-list-v3');
    if (!list) return;

    let items = currentStopUpcoming;
    if (activeBoardFilter) {
        items = items.filter(i => String(i.short_name || i.route_id || '').trim() === activeBoardFilter);
    }

    if (!items || items.length === 0) {
        list.innerHTML = `<p style="color:var(--t-board-muted);font-size:12px;padding:20px;text-align:center;">
            ${activeBoardFilter ? 'Aucun départ imminent pour la ligne ' + activeBoardFilter : 'Aucun départ imminent pour le moment.'}
        </p>`;
        return;
    }

    list.innerHTML = items.slice(0, 10).map(a => {
        const mins = a.minutes;
        let pillClass = 'normal';
        let pillText = mins + ' min';

        if (mins <= 1 && mins >= 0) {
            pillClass = 'approaching';
            pillText = (mins === 0 ? 'À quai' : '1 min');
        } else if (mins <= 10 && mins > 1) {
            pillClass = 'soon';
            pillText = mins + ' min';
        } else if (mins < 0) {
            pillClass = 'passed';
            pillText = 'Parti';
        }

        const headsign = a.headsign || a.trip_headsign || ('Direction ' + (a.short_name || a.route_id));
        const lineName = a.short_name || a.route_id;
        const color = a.color || '#ff8c42';
        const textColor = a.text_color || '#ffffff';

        return `
            <div class="board-row" onclick="focusDepartureLine('${a.route_id}')" title="Cliquer pour afficher la ligne ${lineName} sur la carte">
                <div class="board-line-badge" style="background:${color}; color:${textColor};">${lineName}</div>
                <div class="board-dest-cell">
                    <div class="board-dest-name">${headsign}</div>
                    <div class="board-dest-sub">Ligne ${lineName}</div>
                </div>
                <div class="board-sched-time">${a.scheduled_time || '--:--'}</div>
                <div class="board-countdown-cell">
                    <span class="board-countdown-pill ${pillClass}">${pillText}</span>
                </div>
            </div>
        `;
    }).join('');
}

function focusDepartureLine(routeId) {
    if (!routeId) return;
    addRouteShape(routeId);
    const targetLine = allLines.find(l => l.id == routeId);
    if (targetLine) {
        const notif = document.createElement('div');
        notif.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#0f172a;color:#38bdf8;padding:8px 16px;border-radius:20px;font-size:12px;font-weight:700;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.3);border:1px solid rgba(56,189,248,0.3);';
        notif.innerHTML = `<i class="fa-solid fa-route"></i> Tracé de la Ligne ${targetLine.short_name} affiché sur la carte`;
        document.body.appendChild(notif);
        setTimeout(() => notif.remove(), 2500);
    }
}

function renderAllDaySchedule() {
    const container = document.getElementById('all-day-schedule-v3');
    const filtersContainer = document.getElementById('all-day-filters');
    if (!container) return;

    if (!currentStopAllDay || currentStopAllDay.length === 0) {
        container.innerHTML = '<p style="color:var(--t-muted);font-size:12px;padding:16px 0;text-align:center;">Aucune grille horaire disponible pour cet arrêt aujourd\'hui.</p>';
        if (filtersContainer) filtersContainer.classList.add('hidden');
        return;
    }

    // Line filters for all-day tab
    if (filtersContainer) {
        const linesMap = new Map();
        currentStopAllDay.forEach(p => {
            const name = String(p.short_name || p.route_id || '').trim();
            if (!linesMap.has(name)) {
                linesMap.set(name, { name, color: p.color || '#ff8c42', count: 0 });
            }
            linesMap.get(name).count++;
        });

        if (linesMap.size > 1) {
            filtersContainer.innerHTML = `
                <button class="sched-filter-btn ${!activeBoardFilter ? 'active' : ''}" onclick="toggleLineFilter(null)">
                    Toutes (${currentStopAllDay.length})
                </button>
            ` + Array.from(linesMap.values()).map(l => `
                <button class="sched-filter-btn ${activeBoardFilter === l.name ? 'active' : ''}" onclick="toggleLineFilter('${l.name}')">
                    Ligne ${l.name} (${l.count})
                </button>
            `).join('');
            filtersContainer.classList.remove('hidden');
        } else {
            filtersContainer.classList.add('hidden');
        }
    }

    // Filter items
    const filtered = activeBoardFilter 
        ? currentStopAllDay.filter(p => String(p.short_name || p.route_id || '').trim() === activeBoardFilter)
        : currentStopAllDay;

    if (filtered.length === 0) {
        container.innerHTML = '<p style="color:var(--t-muted);font-size:12px;padding:12px 0;">Aucun horaire trouvé pour ce filtre.</p>';
        return;
    }

    // Group by hour
    const byHour = {};
    filtered.forEach(p => {
        const timeStr = p.scheduled_time || p.arrival_time || '00:00';
        const hour = timeStr.substring(0, 2) + 'h';
        if (!byHour[hour]) byHour[hour] = [];
        byHour[hour].push(p);
    });

    container.innerHTML = Object.keys(byHour).sort().map(h => {
        const pills = byHour[h].map(p => {
            const isSoon = p.minutes >= 0 && p.minutes <= 30;
            return `
                <div class="sched-pill ${isSoon ? 'upcoming-soon' : ''}" title="Ligne ${p.short_name || p.route_id} à ${p.scheduled_time}">
                    <span class="sched-pill-line" style="background:${p.color || '#ff8c42'}; color:${p.text_color || '#fff'}">${p.short_name || p.route_id}</span>
                    <span>${p.scheduled_time}</span>
                </div>
            `;
        }).join('');
        return `
            <div class="sched-hour-block">
                <div class="sched-hour-badge">${h}</div>
                <div class="sched-hour-pills">${pills}</div>
            </div>
        `;
    }).join('');
}

async function addRouteShape(id) {
    if (routeShapes[id]) return;
    try {
        const res = await fetch(`/agglobus/api/shape?route_id=${id}`);
        const coords = await safeJson(res);
        if (aggloMap && aggloMap.map) {
            const poly = L.polyline(coords.map(c => [c.lat, c.lon]), {
                color: routeColors[id] || '#ff8c42', weight: 4, opacity: 0.7, lineJoin: 'round'
            }).addTo(aggloMap.map);
            routeShapes[id] = poly;
            // Fit bounds only if it's the first or primary
            if (activeRoutes.size === 1) aggloMap.map.fitBounds(poly.getBounds().pad(0.1));
        }
    } catch (e) {}
}

function removeRouteShape(id) {
    if (routeShapes[id] && aggloMap.map) {
        aggloMap.map.removeLayer(routeShapes[id]);
        delete routeShapes[id];
    }
}

function resetMap() {
    if (aggloMap && aggloMap.map) {
        aggloMap.map.setView([47.0810, 2.3986], 13);
    }
}

function zoomInMap() {
    if (aggloMap && aggloMap.map) {
        aggloMap.map.zoomIn();
    }
}

function zoomOutMap() {
    if (aggloMap && aggloMap.map) {
        aggloMap.map.zoomOut();
    }
}

function setMapLayer(type, btnEl) {
    const mapEl = document.getElementById('agglobus-map');
    if (mapEl) {
        if (type === 'night') {
            mapEl.classList.add('map-night-mode');
        } else {
            mapEl.classList.remove('map-night-mode');
        }
    }
    if (aggloMap && typeof aggloMap.setLayer === 'function') {
        aggloMap.setLayer(type);
    }
    document.querySelectorAll('.view-switch-btn').forEach(b => b.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');
}

function closeDetail() {
    document.getElementById('agglo-detail-panel').classList.add('hidden');
    currentLine = null;
    selectedStopId = null;
    clearLineStopMarkers();
    if (aggloMap && aggloMap.clearMarkers) {
        aggloMap.clearMarkers();
    }
    if (passageRefreshInterval) clearInterval(passageRefreshInterval);
    document.querySelectorAll('.line-card-v3').forEach(c => c.classList.remove('active'));
    updateBuses(); 
}

function switchTab(tabId, el) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.tab-pane').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tabId).classList.add('active');
}

function toggleDirection() {
    currentDirection = currentDirection === "0" ? "1" : "0";
    const dirIcon = document.querySelector('.switch-dir i');
    if (dirIcon) {
        dirIcon.style.transition = 'transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
        dirIcon.style.transform = (currentDirection === "1") ? 'rotate(180deg)' : 'rotate(0deg)';
    }
    loadLineStops();
}

/**
 * SEARCH & NAVIGATION
 */
function getFilteredLines(query = '') {
    const q = (query || (document.getElementById('agglo-search-input') ? document.getElementById('agglo-search-input').value : '')).toLowerCase().trim();
    if (!q) return allLines;
    return allLines.filter(l => 
        (l.short_name && String(l.short_name).toLowerCase().includes(q)) || 
        (l.long_name && String(l.long_name).toLowerCase().includes(q))
    );
}

let searchDebounceTimer = null;

function handleSearch(val) {
    const query = (val || '').trim();
    isSearching = query.length > 0;
    updateSidebarContext(isSearching, isSearching ? "Résultats de recherche" : "Lignes en service");

    if (!isSearching) {
        renderLinesList(allLines);
        return;
    }

    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);

    // 1. Instant local line filtering
    const matchingLines = getFilteredLines(query);

    // 2. Debounce stop search
    searchDebounceTimer = setTimeout(async () => {
        let matchingStops = [];
        if (query.length >= 2) {
            try {
                const res = await fetch(`/agglobus/api/search?q=${encodeURIComponent(query)}`);
                matchingStops = await safeJson(res);
                if (!Array.isArray(matchingStops)) matchingStops = [];
            } catch (e) {
                console.warn("Stop search error:", e);
            }
        }
        renderCombinedSearchResults(matchingLines, matchingStops, query);
    }, 200);

    // Initial render with matching lines while stops are fetching
    renderCombinedSearchResults(matchingLines, [], query);
}

function renderCombinedSearchResults(lines, stops, query) {
    const container = document.getElementById('lines-list-container');
    if (!container) return;

    if (lines.length === 0 && stops.length === 0) {
        container.innerHTML = `
            <div style="padding: 24px 18px; text-align: center; color: var(--t-muted); font-size: 13px;">
                <i class="fa-solid fa-magnifying-glass" style="font-size: 20px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                Aucun résultat pour "<strong>${query}</strong>".
            </div>
        `;
        return;
    }

    let html = '';

    if (lines.length > 0) {
        html += `<div class="search-category-title"><i class="fa-solid fa-bus"></i> Lignes (${lines.length})</div>`;
        html += lines.map(line => {
            const isActive = activeRoutes.has(line.id);
            return `
                <div class="line-card-v3 ${isActive ? 'active' : ''}" style="--lc: ${line.color};" onclick="handleLineClick('${line.id}')">
                    <div class="line-badge-v3">${line.short_name}</div>
                    <div class="l-info">
                        <div class="l-name">${line.long_name}</div>
                        <div class="l-meta">Ligne régulière</div>
                    </div>
                    <div class="multi-toggle">
                        ${isActive ? '<i class="fa-solid fa-check"></i>' : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    if (stops.length > 0) {
        html += `<div class="search-category-title"><i class="fa-solid fa-map-pin"></i> Arrêts (${stops.length})</div>`;
        html += stops.map(s => `
            <div class="search-stop-card" onclick="selectStop('${s.stop_id}', '${(s.stop_name || '').replace(/'/g, "\\'")}', ${s.lat}, ${s.lon}, true)">
                <div class="search-stop-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div class="l-info">
                    <div class="l-name">${s.stop_name}</div>
                    <div class="l-meta">Arrêt AggloBus Bourges</div>
                </div>
                <i class="fa-solid fa-chevron-right" style="color: #cbd5e1; font-size: 11px;"></i>
            </div>
        `).join('');
    }

    container.innerHTML = html;
}

function updateSidebarContext(showBack, title) {
    document.getElementById('clear-search').classList.toggle('hidden', !showBack);
    document.getElementById('back-to-lines').classList.toggle('hidden', !showBack);
    document.getElementById('sidebar-context-title').textContent = title;
}

function clearSearch() {
    document.getElementById('agglo-search-input').value = '';
    isSearching = false;
    updateSidebarContext(false, "Lignes en service");
    renderLinesList(allLines);
}

/**
 * BUS TRACKING (SIMULATED)
 */

async function startBusTracking() {
    updateBuses();
    setInterval(updateBuses, 5000); // 5s for smoother motion
}

async function updateBuses() {
    try {
        const res = await fetch('/agglobus/api/proxy?type=vehicles');
        const vehicles = await safeJson(res);
        if (!Array.isArray(vehicles)) return;
        
        console.log(`Bus Tracking: Received ${vehicles.length} vehicles`);
        
        const currentIds = new Set();
        
        vehicles.forEach(bus => {
            const tid = String(bus.trip_id).trim();
            
            // FILTERING LOGIC
            let show = true;
            if (currentLine) {
                // If a line is selected, only show buses of that line
                if (String(bus.route_id) !== String(currentLine.id)) {
                    show = false;
                }
                
                // If a stop is selected, only show the bus approaching that stop
                if (show && selectedStopId) {
                    if (String(bus.next_stop_id) !== String(selectedStopId)) {
                        show = false;
                    }
                }
            }
            
            if (!show) {
                if (busMarkers[tid]) {
                    aggloMap.map.removeLayer(busMarkers[tid]);
                    delete busMarkers[tid];
                }
                return;
            }

            currentIds.add(tid);
            
            if (busMarkers[tid]) {
                busMarkers[tid].setLatLng([bus.lat, bus.lon]);
                busMarkers[tid].setTooltipContent(`<b>Ligne ${bus.short_name}</b><br>Vers: ${bus.next_stop}`);
            } else {
                console.log(`Adding new bus marker: ${bus.short_name} (Trip: ${tid})`);
                const icon = L.divIcon({
                    className: 'bus-marker-icon',
                    html: `<div class="bus-dot" style="background:${bus.color}; border-color:white">${bus.short_name}</div>`,
                    iconSize: [26, 26],
                    iconAnchor: [13, 13]
                });
                
                busMarkers[tid] = L.marker([bus.lat, bus.lon], { icon, zIndexOffset: 1000 })
                    .addTo(aggloMap.map)
                    .bindTooltip(`<b>Ligne ${bus.short_name}</b><br>Vers: ${bus.next_stop}`, { direction: 'top' });
            }
        });
        
        // Cleanup old
        const markerIds = Object.keys(busMarkers);
        markerIds.forEach(id => {
            const sid = String(id).trim();
            if (!currentIds.has(sid)) {
                console.log(`Removing inactive bus: ${sid} (Not in Set of ${currentIds.size} IDs)`);
                aggloMap.map.removeLayer(busMarkers[id]);
                delete busMarkers[id];
            }
        });
        
        const syncTime = document.getElementById('sync-time');
        if (syncTime) syncTime.textContent = "à l'instant";
    } catch (e) {
        console.warn("Tracking update failed", e);
    }
}

/**
 * AROUND ME
 */
document.getElementById('agglo-locate-btn').addEventListener('click', function() {
    if (navigator.geolocation) {
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ...';
        navigator.geolocation.getCurrentPosition(async (pos) => {
            updateSidebarContext(true, "À proximité");
            
            const lat = pos.coords.latitude;
            const lon = pos.coords.longitude;

            // Add User Position Marker (Blue dot)
            if (aggloMap.userMarker) aggloMap.map.removeLayer(aggloMap.userMarker);
            aggloMap.userMarker = L.circleMarker([lat, lon], {
                radius: 8, fillOpacity: 0.8, color: '#fff', weight: 2, fillColor: '#3b82f6'
            }).addTo(aggloMap.map).bindTooltip("Vous êtes ici");

            const res = await fetch(`/agglobus/api/near?lat=${lat}&lon=${lon}`);
            const stops = await safeJson(res);
            renderNearStops(stops);
            this.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Autour de moi';
            
            aggloMap.map.setView([lat, lon], 14);
        }, async (err) => {
            console.warn("Erreur de géolocalisation:", err.message);
            // Fallback gracefully to Bourges Centre (Planchat)
            const fallbackLat = 47.0854;
            const fallbackLon = 2.3929;
            updateSidebarContext(true, "Arrêts Bourges Centre");
            const res = await fetch(`/agglobus/api/near?lat=${fallbackLat}&lon=${fallbackLon}`);
            const stops = await safeJson(res);
            renderNearStops(stops);
            if (aggloMap && aggloMap.map) aggloMap.map.setView([fallbackLat, fallbackLon], 14);
            this.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Autour de moi';
        });
    } else {
        alert("La géolocalisation n'est pas supportée par votre navigateur.");
    }
});

function renderNearStops(stops) {
    const container = document.getElementById('lines-list-container');
    if (!container) return;
    if (!Array.isArray(stops) || stops.length === 0) {
        container.innerHTML = '<div class="p-3 text-muted">Aucun arrêt trouvé à proximité.</div>';
        return;
    }
    container.innerHTML = stops.map(s => `
        <div class="line-card-v3" onclick="selectStop('${s.stop_id}', '${(s.stop_name || '').replace(/'/g, "\\'")}', ${s.lat}, ${s.lon}, true)" style="--lc: var(--s-color)">
            <div class="line-badge-v3" style="border-radius:50%"><i class="fa-solid fa-location-dot"></i></div>
            <div class="l-info">
                <div class="l-name">${s.stop_name}</div>
                <div class="l-meta">Distance: ${Math.round((s.distance || 0)*1000)}m</div>
            </div>
        </div>
    `).join('');
    if (aggloMap) {
        aggloMap.clearMarkers();
        stops.forEach(s => {
            aggloMap.addMarker(s.lat, s.lon, `<strong>${s.stop_name}</strong>`);
        });
        aggloMap.fitMarkers();
    }
}
</script>
