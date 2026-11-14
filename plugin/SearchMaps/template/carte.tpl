<section class="carte-interactive-page">
    <header class="carte-header">
        <h1><i class="fa-solid fa-map-location-dot"></i> {{ pageTitle }}</h1>
        <p class="carte-subtitle">Explorez les lieux, patrimoines et commerces partenaires à proximité.</p>
    </header>

    <div class="carte-controls-bar">
        <div class="carte-filter-group">
            <label for="carte-cat-select"><i class="fa-solid fa-filter"></i> Catégorie :</label>
            <select id="carte-cat-select" onchange="filterPois()">
                <option value="all">Toutes les catégories ({{ poisCount }})</option>
                {% for cat in categories %}
                    <option value="{{ cat }}">{{ cat }}</option>
                {% endfor %}
            </select>
        </div>

        <div class="carte-filter-group">
            <label for="carte-radius-select"><i class="fa-solid fa-location-crosshairs"></i> Rayon :</label>
            <select id="carte-radius-select" onchange="filterPois()">
                <option value="0">Tous les points</option>
                <option value="2">Moins de 2 km</option>
                <option value="5">Moins de 5 km</option>
                <option value="10">Moins de 10 km</option>
                <option value="25">Moins de 25 km</option>
            </select>
            <button type="button" class="button small secondary" id="btn-geoloc-me" onclick="locateUser()" title="Utiliser ma position GPS">
                <i class="fa-solid fa-location-arrow"></i> Me localiser
            </button>
        </div>
    </div>

    <div class="carte-layout">
        <div id="main-interactive-map" class="carte-map-container"></div>

        <aside class="carte-sidebar">
            <div class="carte-sidebar-header">
                <h3><i class="fa-solid fa-list"></i> Points trouvés (<span id="poi-count-display">{{ poisCount }}</span>)</h3>
            </div>
            <div id="poi-cards-list" class="carte-cards-list">
                <!-- Dynamically populated -->
            </div>
        </aside>
    </div>
</section>

<style>
.carte-interactive-page { max-width: 1200px; margin: 0 auto; padding: 1rem 0; }
.carte-header { margin-bottom: 1.5rem; text-align: center; }
.carte-header h1 { font-size: 2rem; margin: 0 0 0.5rem; }
.carte-subtitle { color: var(--evo-text-muted, #64748b); margin: 0; font-size: 1.05rem; }
.carte-controls-bar { display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between; background: var(--evo-surface, #f8fafc); border: 1px solid var(--evo-border, #e2e8f0); border-radius: 10px; padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; }
.carte-filter-group { display: flex; align-items: center; gap: 0.5rem; }
.carte-filter-group label { font-weight: 600; font-size: 0.9rem; }
.carte-filter-group select { padding: 6px 10px; border-radius: 6px; border: 1px solid var(--evo-border, #cbd5e1); background: var(--evo-surface, #fff); color: var(--evo-text, inherit); }
.carte-layout { display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; min-height: 560px; }
@media (max-width: 900px) { .carte-layout { grid-template-columns: 1fr; } }
.carte-map-container { height: 560px; border-radius: 12px; border: 1px solid var(--evo-border, #cbd5e1); overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.carte-sidebar { background: var(--evo-surface, #ffffff); border: 1px solid var(--evo-border, #e2e8f0); border-radius: 12px; display: flex; flex-direction: column; max-height: 560px; overflow: hidden; }
.carte-sidebar-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--evo-border, #e2e8f0); background: var(--evo-surface-alt, #f8fafc); }
.carte-sidebar-header h3 { margin: 0; font-size: 1.05rem; }
.carte-cards-list { overflow-y: auto; padding: 0.75rem; flex: 1; display: flex; flex-direction: column; gap: 0.75rem; }
.poi-card-item { padding: 0.85rem; border-radius: 8px; border: 1px solid var(--evo-border, #e2e8f0); background: var(--evo-surface, #fff); cursor: pointer; transition: all 0.18s ease; }
.poi-card-item:hover { border-color: var(--evo-primary, #0284c7); transform: translateY(-2px); box-shadow: 0 3px 8px rgba(0,0,0,0.05); }
.poi-card-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
.poi-card-meta { font-size: 0.8rem; color: var(--evo-text-muted, #64748b); margin-bottom: 4px; }
.poi-card-dist { display: inline-block; padding: 2px 6px; border-radius: 4px; background: rgba(2, 132, 199, 0.1); color: #0284c7; font-weight: 600; font-size: 0.75rem; margin-top: 4px; }
</style>

<script>
const allPois = {{ poisJson|raw }};
let carteMap = null;
let markersLayer = null;
let userPos = null;

function haversineDist(lat1, lon1, lat2, lon2) {
    const R = 6371; // Rayon Terre en km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function initMap() {
    if (typeof L === 'undefined') return;
    
    carteMap = L.map('main-interactive-map').setView([{{ centerLat }}, {{ centerLon }}], {{ zoomDefault }});
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(carteMap);

    markersLayer = L.layerGroup().addTo(carteMap);
    renderPois(allPois);
}

function renderPois(pois) {
    if (!carteMap || !markersLayer) return;
    markersLayer.clearLayers();

    const listContainer = document.getElementById('poi-cards-list');
    listContainer.innerHTML = '';
    document.getElementById('poi-count-display').textContent = pois.length;

    if (pois.length === 0) {
        listContainer.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: var(--evo-text-muted, #64748b);">Aucun point ne correspond à vos critères.</div>';
        return;
    }

    const bounds = L.latLngBounds();

    pois.forEach(poi => {
        const lat = parseFloat(poi.lat);
        const lon = parseFloat(poi.lon);
        bounds.extend([lat, lon]);

        // Custom icon
        const iconHtml = `<div style="background: ${poi.color || '#0284c7'}; color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.3); border: 2px solid #fff;"><i class="fa-solid ${poi.icon || 'fa-map-pin'}" style="font-size: 14px;"></i></div>`;
        const customIcon = L.divIcon({
            html: iconHtml,
            className: 'custom-poi-marker',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        let distText = '';
        if (userPos) {
            const d = haversineDist(userPos.lat, userPos.lng, lat, lon);
            distText = `<div class="poi-card-dist"><i class="fa-solid fa-person-walking"></i> à ${d < 1 ? Math.round(d * 1000) + ' m' : d.toFixed(1) + ' km'}</div>`;
        }

        const popupContent = `
            <div style="font-family: inherit; min-width: 200px;">
                <h4 style="margin: 0 0 4px; font-size: 1rem; color: #0f172a;">${poi.title}</h4>
                <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 6px;"><span class="badge" style="background: #e2e8f0; color: #334155; padding: 2px 6px; border-radius: 4px;">${poi.category}</span></div>
                ${poi.address ? `<div style="font-size: 0.82rem; margin-bottom: 4px;"><i class="fa-solid fa-location-dot"></i> ${poi.address}</div>` : ''}
                ${poi.phone ? `<div style="font-size: 0.82rem; margin-bottom: 4px;"><i class="fa-solid fa-phone"></i> ${poi.phone}</div>` : ''}
                ${poi.description ? `<p style="font-size: 0.85rem; margin: 6px 0; color: #334155;">${poi.description}</p>` : ''}
                <div style="margin-top: 8px; display: flex; gap: 8px;">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lon}" target="_blank" rel="noopener" class="button small primary" style="font-size: 0.78rem; text-decoration: none; padding: 4px 8px; border-radius: 4px; background: #0284c7; color: #fff;">
                        <i class="fa-solid fa-diamond-turn-right"></i> Itinéraire
                    </a>
                    ${poi.website ? `<a href="${poi.website}" target="_blank" rel="noopener" class="button small secondary" style="font-size: 0.78rem; text-decoration: none; padding: 4px 8px; border-radius: 4px; border: 1px solid #cbd5e1; color: #334155;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Site</a>` : ''}
                </div>
            </div>
        `;

        const marker = L.marker([lat, lon], { icon: customIcon }).addTo(markersLayer);
        marker.bindPopup(popupContent);

        // Sidebar card
        const card = document.createElement('div');
        card.className = 'poi-card-item';
        card.innerHTML = `
            <div class="poi-card-title">
                <span style="color: ${poi.color || '#0284c7'};"><i class="fa-solid ${poi.icon || 'fa-map-pin'}"></i></span>
                ${poi.title}
            </div>
            <div class="poi-card-meta">${poi.category}</div>
            ${poi.address ? `<div class="poi-card-meta"><i class="fa-solid fa-location-dot"></i> ${poi.address}</div>` : ''}
            ${distText}
        `;
        card.addEventListener('click', () => {
            carteMap.setView([lat, lon], 16);
            marker.openPopup();
        });
        listContainer.appendChild(card);
    });

    if (pois.length > 0 && !userPos) {
        carteMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
    }
}

function filterPois() {
    const selectedCat = document.getElementById('carte-cat-select').value;
    const maxRadius = parseFloat(document.getElementById('carte-radius-select').value) || 0;

    let filtered = allPois.filter(poi => {
        if (selectedCat !== 'all' && poi.category !== selectedCat) {
            return false;
        }
        if (maxRadius > 0 && userPos) {
            const dist = haversineDist(userPos.lat, userPos.lng, parseFloat(poi.lat), parseFloat(poi.lon));
            if (dist > maxRadius) {
                return false;
            }
        }
        return true;
    });

    renderPois(filtered);
}

function locateUser() {
    if (!navigator.geolocation) {
        alert('La géolocalisation n\'est pas supportée par votre navigateur.');
        return;
    }
    const btn = document.getElementById('btn-geoloc-me');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Localisation...';

    navigator.geolocation.getCurrentPosition(
        pos => {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Vous êtes ici';
            btn.classList.add('success');
            userPos = { lat: pos.coords.latitude, lng: pos.coords.longitude };

            L.circleMarker([userPos.lat, userPos.lng], {
                radius: 8,
                fillColor: '#3b82f6',
                color: '#ffffff',
                weight: 3,
                opacity: 1,
                fillOpacity: 0.9
            }).addTo(carteMap).bindPopup('<strong>Votre position</strong>').openPopup();

            carteMap.setView([userPos.lat, userPos.lng], 14);
            filterPois();
        },
        err => {
            btn.innerHTML = '<i class="fa-solid fa-location-arrow"></i> Me localiser';
            alert('Impossible d\'obtenir votre position GPS.');
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

document.addEventListener('DOMContentLoaded', initMap);
</script>

