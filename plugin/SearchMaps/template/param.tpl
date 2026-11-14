<section class="module-card">
    <header><i class="fa-solid fa-map-location-dot"></i> Cartographie &amp; Points d'Intérêt (SearchMaps)</header>

    <div class="tabs-container" data-tab-key="searchmaps">
        <ul class="tabs-header">
            <li class="default-tab" data-tab="pois">
                <i class="fa-solid fa-location-dot"></i> Points d'Intérêt ({{ poisCount }})
            </li>
            <li data-tab="config">
                <i class="fa-solid fa-sliders"></i> Paramètres généraux
            </li>
            <li data-tab="help">
                <i class="fa-solid fa-circle-question"></i> Intégration &amp; Shortcodes
            </li>
        </ul>

        <ul class="tabs">
            <!-- Tab 1: Points d'Intérêt (POI) -->
            <li class="tab">
                <div class="poi-admin-header">
                    <p class="description">Gérez vos lieux remarquables, commerces partenaires ou points d'intérêt. Ils s'affichent automatiquement sur la carte interactive avec filtres par catégorie et recherche par rayon.</p>
                    <button type="button" class="button primary" id="btn-open-poi-modal" onclick="openPoiModal()">
                        <i class="fa-solid fa-plus"></i> Ajouter un point d'intérêt
                    </button>
                </div>

                <div class="poi-table-wrapper">
                    <table class="table-striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Titre</th>
                                <th>Catégorie</th>
                                <th>Coordonnées</th>
                                <th>Adresse</th>
                                <th>Contact / Web</th>
                                <th style="width: 140px; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% if pois is empty %}
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--evo-text-muted, #64748b);">
                                        Aucun point d'intérêt enregistré. Cliquez sur « Ajouter un point d'intérêt » ci-dessus.
                                    </td>
                                </tr>
                            {% else %}
                                {% for poi in pois %}
                                    <tr>
                                        <td style="text-align: center;">
                                            <span class="poi-badge-icon" style="background: {{ poi.color|default('#0284c7') }}; color: #fff;">
                                                <i class="fa-solid {{ poi.icon|default('fa-map-pin') }}"></i>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ poi.title }}</strong>
                                            {% if poi.description %}
                                                <div class="poi-desc-preview">{{ poi.description }}</div>
                                            {% endif %}
                                        </td>
                                        <td><span class="badge">{{ poi.category }}</span></td>
                                        <td><code>{{ poi.lat }}, {{ poi.lon }}</code></td>
                                        <td><small>{{ poi.address|default('-') }}</small></td>
                                        <td>
                                            {% if poi.phone %}<small><i class="fa-solid fa-phone"></i> {{ poi.phone }}</small><br>{% endif %}
                                            {% if poi.website %}<small><a href="{{ poi.website }}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Site web</a></small>{% endif %}
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <button type="button" class="button small secondary" onclick='editPoi({{ poi|json_encode }})' title="Modifier">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <form action="{{ router.generate('admin-SearchMaps-poi-delete', ['id': poi.id]) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Supprimer ce point d\'intérêt ?');">
                                                <input type="hidden" name="_csrf" value="{{ csrfToken }}">
                                                <button type="submit" class="button small danger" title="Supprimer">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                {% endfor %}
                            {% endif %}
                        </tbody>
                    </table>
                </div>

                <!-- Modal / Drawer d'ajout/modification de POI -->
                <div id="poi-modal" class="poi-modal-overlay" style="display: none;">
                    <div class="poi-modal-card">
                        <div class="poi-modal-header">
                            <h3 id="poi-modal-title"><i class="fa-solid fa-location-dot"></i> Ajouter un point d'intérêt</h3>
                            <button type="button" class="poi-modal-close" onclick="closePoiModal()">&times;</button>
                        </div>
                        <form action="{{ router.generate('admin-SearchMaps-poi-save') }}" method="POST" id="poi-form">
                            <input type="hidden" name="_csrf" value="{{ csrfToken }}">
                            <input type="hidden" name="poi_id" id="poi_id" value="">

                            <div class="poi-grid-2">
                                <div class="field">
                                    <label for="poi_title">Titre du lieu / commerce *</label>
                                    <input type="text" id="poi_title" name="title" required placeholder="Ex: Cathédrale Saint-Étienne">
                                </div>
                                <div class="field">
                                    <label for="poi_category">Catégorie *</label>
                                    <input type="text" id="poi_category" name="category" list="categories-list" required placeholder="Ex: Patrimoine & Culture">
                                    <datalist id="categories-list">
                                        {% for cat in categories %}
                                            <option value="{{ cat }}">
                                        {% endfor %}
                                    </datalist>
                                </div>
                            </div>

                            <div class="field">
                                <label><i class="fa-solid fa-map-pin"></i> Cliquez sur la carte pour définir les coordonnées :</label>
                                <div id="admin-poi-picker-map" style="height: 240px; border-radius: 8px; border: 1px solid var(--evo-border, #cbd5e1); margin-bottom: 0.5rem;"></div>
                            </div>

                            <div class="poi-grid-2">
                                <div class="field">
                                    <label for="poi_lat">Latitude</label>
                                    <input type="text" id="poi_lat" name="lat" value="{{ centerLat }}" required>
                                </div>
                                <div class="field">
                                    <label for="poi_lon">Longitude</label>
                                    <input type="text" id="poi_lon" name="lon" value="{{ centerLon }}" required>
                                </div>
                            </div>

                            <div class="field">
                                <label for="poi_address">Adresse postale</label>
                                <input type="text" id="poi_address" name="address" placeholder="Ex: Place Étienne Dolet, 18000 Bourges">
                            </div>

                            <div class="poi-grid-2">
                                <div class="field">
                                    <label for="poi_phone">Téléphone</label>
                                    <input type="text" id="poi_phone" name="phone" placeholder="02 48 ...">
                                </div>
                                <div class="field">
                                    <label for="poi_website">Site web / Lien</label>
                                    <input type="url" id="poi_website" name="website" placeholder="https://...">
                                </div>
                            </div>

                            <div class="poi-grid-2">
                                <div class="field">
                                    <label for="poi_icon">Icône FontAwesome</label>
                                    <input type="text" id="poi_icon" name="icon" value="fa-map-pin" placeholder="fa-monument, fa-store, fa-utensils...">
                                </div>
                                <div class="field">
                                    <label for="poi_color">Couleur du repère</label>
                                    <input type="color" id="poi_color" name="color" value="#0284c7" style="height: 38px; width: 100%; cursor: pointer;">
                                </div>
                            </div>

                            <div class="field">
                                <label for="poi_description">Description courte</label>
                                <textarea id="poi_description" name="description" rows="3" placeholder="Présentation succincte..."></textarea>
                            </div>

                            <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 8px;">
                                <button type="button" class="button secondary" onclick="closePoiModal()">Annuler</button>
                                <button type="submit" class="button primary"><i class="fa-solid fa-save"></i> Enregistrer le point</button>
                            </div>
                        </form>
                    </div>
                </div>
            </li>

            <!-- Tab 2: Paramètres -->
            <li class="tab">
                <form action="{{ router.generate('admin-SearchMaps-save') }}" method="POST">
                    <input type="hidden" name="_csrf" value="{{ csrfToken }}">

                    <div class="field">
                        <label for="zoom_default">Zoom par défaut (1 à 21)</label>
                        <input type="number" id="zoom_default" name="zoom_default" value="{{ zoomDefault }}" min="1" max="21">
                    </div>

                    <div class="field">
                        <label for="center_lat">Centre de la carte par défaut (Latitude)</label>
                        <input type="text" id="center_lat" name="center_lat" value="{{ centerLat }}">
                    </div>

                    <div class="field">
                        <label for="center_lon">Centre de la carte par défaut (Longitude)</label>
                        <input type="text" id="center_lon" name="center_lon" value="{{ centerLon }}">
                    </div>

                    <div class="field">
                        <label for="priority">Priorité d'affichage</label>
                        <input type="number" id="priority" name="priority" value="{{ priorityVal }}">
                    </div>

                    <div class="field">
                        <label>
                            <input type="checkbox" name="debug" {% if debugChecked %}checked{% endif %}> Activer le mode debug (logs navigateur <code>console.debug</code>)
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button primary"><i class="fa-solid fa-save"></i> Enregistrer la configuration</button>
                    </div>
                </form>
            </li>

            <!-- Tab 3: Fonctionnement & Intégration -->
            <li class="tab">
                <section>
                    <header><i class="fa-solid fa-map"></i> 1. Page de Carte Interactive dédiée</header>
                    <p>Vos visiteurs peuvent consulter la carte complète avec filtres et recherche par rayon sur l'adresse : <a href="/carte" target="_blank"><strong>/carte</strong></a>.</p>
                </section>

                <section>
                    <header><i class="fa-solid fa-code"></i> 2. Shortcodes dans vos Articles et Pages</header>
                    <p>Insérez des cartes riches n'importe où dans votre contenu :</p>
                    <ul>
                        <li><strong>Carte avec tous vos points d'intérêt :</strong> <code>[map pois="all"]</code></li>
                        <li><strong>Carte filtrée par catégorie :</strong> <code>[map category="Patrimoine & Culture"]</code></li>
                        <li><strong>Carte centrée sur un lieu précis :</strong> <code>[map lat="47.0810" lon="2.3986" zoom="15" title="Point de rendez-vous"]</code></li>
                    </ul>
                </section>
            </li>
        </ul>
    </div>
</section>

<style>
.poi-admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.poi-admin-header .description { margin: 0; max-width: 700px; color: var(--evo-text-muted, #64748b); }
.poi-badge-icon { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; font-size: 0.8rem; }
.poi-desc-preview { font-size: 0.8rem; color: var(--evo-text-muted, #64748b); margin-top: 2px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.poi-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 640px) { .poi-grid-2 { grid-template-columns: 1fr; } }
.poi-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.poi-modal-card { background: var(--evo-surface, #ffffff); border-radius: 12px; width: 100%; max-width: 650px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.25); padding: 1.5rem; }
.poi-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--evo-border, #e2e8f0); padding-bottom: 0.75rem; }
.poi-modal-header h3 { margin: 0; font-size: 1.15rem; }
.poi-modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--evo-text-muted, #64748b); line-height: 1; }
.field { margin-bottom: 1rem; }
.field label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; }
.field input[type="number"], .field input[type="text"], .field input[type="url"], .field textarea { width: 100%; box-sizing: border-box; }
.form-actions { margin-top: 1.5rem; }
</style>

<script>
let pickerMap = null;
let pickerMarker = null;

function initPickerMap() {
    if (pickerMap) return;
    if (typeof L === 'undefined') return;
    
    const lat = parseFloat(document.getElementById('poi_lat').value) || {{ centerLat }};
    const lon = parseFloat(document.getElementById('poi_lon').value) || {{ centerLon }};

    pickerMap = L.map('admin-poi-picker-map').setView([lat, lon], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(pickerMap);

    pickerMarker = L.marker([lat, lon], { draggable: true }).addTo(pickerMap);

    pickerMarker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        document.getElementById('poi_lat').value = pos.lat.toFixed(6);
        document.getElementById('poi_lon').value = pos.lng.toFixed(6);
    });

    pickerMap.on('click', function(e) {
        pickerMarker.setLatLng(e.latlng);
        document.getElementById('poi_lat').value = e.latlng.lat.toFixed(6);
        document.getElementById('poi_lon').value = e.latlng.lng.toFixed(6);
    });
}

function openPoiModal() {
    document.getElementById('poi-modal').style.display = 'flex';
    document.getElementById('poi-form').reset();
    document.getElementById('poi_id').value = '';
    document.getElementById('poi-modal-title').innerHTML = '<i class="fa-solid fa-location-dot"></i> Ajouter un point d\'intérêt';
    setTimeout(() => {
        initPickerMap();
        if (pickerMap) pickerMap.invalidateSize();
    }, 150);
}

function closePoiModal() {
    document.getElementById('poi-modal').style.display = 'none';
}

function editPoi(poi) {
    openPoiModal();
    document.getElementById('poi-modal-title').innerHTML = '<i class="fa-solid fa-pen"></i> Modifier le point d\'intérêt';
    document.getElementById('poi_id').value = poi.id || '';
    document.getElementById('poi_title').value = poi.title || '';
    document.getElementById('poi_category').value = poi.category || 'Général';
    document.getElementById('poi_lat').value = poi.lat || {{ centerLat }};
    document.getElementById('poi_lon').value = poi.lon || {{ centerLon }};
    document.getElementById('poi_address').value = poi.address || '';
    document.getElementById('poi_phone').value = poi.phone || '';
    document.getElementById('poi_website').value = poi.website || '';
    document.getElementById('poi_icon').value = poi.icon || 'fa-map-pin';
    document.getElementById('poi_color').value = poi.color || '#0284c7';
    document.getElementById('poi_description').value = poi.description || '';

    if (pickerMarker && pickerMap && poi.lat && poi.lon) {
        pickerMarker.setLatLng([poi.lat, poi.lon]);
        pickerMap.setView([poi.lat, poi.lon], 15);
    }
}
</script>
