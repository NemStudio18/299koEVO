<section class="module-card">
    <header>
        <i class="fa-solid fa-plus-circle"></i>
        Créer un nouveau POI (admin direct)
    </header>

    <div style="background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 4px; padding: 12px; margin-bottom: 20px; font-size: 0.9em;">
        <i class="fa-solid fa-shield-halved" style="color: #388e3c;"></i>
        <strong>Mode admin :</strong> Ce POI sera créé directement en base, sans passer par le workflow de proposition et sans déclencher de points de gamification.
    </div>

    <form method="post" action="{{ ROUTER.generate('admin-poi-edit-create-save') }}" enctype="multipart/form-data" novalidate>

        <div class="tabs-container">
            <ul class="tabs-header">
                <li class="active" data-tab="general">
                    <i class="fa-solid fa-info-circle"></i> Informations générales
                </li>
                <li data-tab="services">
                    <i class="fa-solid fa-cogs"></i> Services &amp; Équipements
                </li>
                {% if CORE.callHook('map:is-enabled') %}
                <li data-tab="location">
                    <i class="fa-solid fa-map-marker-alt"></i> Localisation
                </li>
                {% endif %}
                <li data-tab="content">
                    <i class="fa-solid fa-align-left"></i> Description
                </li>
                <li data-tab="links">
                    <i class="fa-solid fa-link"></i> Liens
                </li>
                <li data-tab="tags">
                    <i class="fa-solid fa-tags"></i> Tags
                </li>
                <li data-tab="photo">
                    <i class="fa-solid fa-image"></i> Photo
                </li>
            </ul>

            <div class="tabs">
                <!-- Onglet 1 : Informations générales -->
                <div class="tab active" id="tab-general">
                    <div class="form-section">
                        <h3>Informations de base</h3>

                        <div class="field">
                            <label for="name">Nom <span style="color:red">*</span></label>
                            <input type="text" id="name" name="name" required autofocus placeholder="Nom du lieu…">
                        </div>

                        <div class="field">
                            <label for="family_select">Famille</label>
                            <select id="family_select" name="category">
                                <option value="">-- Sélectionner une famille --</option>
                                {% if familiesForSelect %}
                                    {% for fam in familiesForSelect %}
                                        <option value="{{ fam.slug }}">{{ fam.label }}</option>
                                    {% endfor %}
                                {% endif %}
                            </select>
                        </div>

                        <div class="field">
                            <label>Spécialités</label>
                            <div id="subcategories_container" class="checkbox-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px; max-height: 300px; overflow-y: auto; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                                <p style="grid-column: 1 / -1; color: #666; font-style: italic; margin: 0;">Sélectionnez d'abord une famille pour voir les spécialités disponibles</p>
                            </div>
                            <div id="custom_subcategory_wrapper" style="display:none; margin-top:8px;">
                                <label for="custom_subcategory" style="font-size: 0.9em; font-weight: normal;">Nom de la spécialité personnalisée :</label>
                                <input type="text" id="custom_subcategory" name="subcategory_custom" placeholder="Saisissez la spécialité…" style="width:100%;">
                                <small style="color:#888;">Votre proposition sera prise en compte directement comme spécialité du POI.</small>
                            </div>
                        </div>

                        <div class="field">
                            <label for="brand">Marque</label>
                            <input type="text" id="brand" name="brand" placeholder="Marque / enseigne…">
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Adresse</h3>
                        <div class="field">
                            <label for="street">Rue</label>
                            <input type="text" id="street" name="street">
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <label for="housenumber">Numéro</label>
                                <input type="text" id="housenumber" name="housenumber">
                            </div>
                            <div class="field">
                                <label for="postcode">Code postal</label>
                                <input type="text" id="postcode" name="postcode">
                            </div>
                            <div class="field">
                                <label for="city">Ville</label>
                                <input type="text" id="city" name="city">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Contact</h3>
                        <div class="field">
                            <label for="phone">Téléphone</label>
                            <input type="text" id="phone" name="phone">
                        </div>
                        <div class="field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>
                        <div class="field">
                            <label for="website">Site web</label>
                            <input type="url" id="website" name="website" placeholder="https://…">
                        </div>
                    </div>
                </div>

                <!-- Onglet 2 : Services & Équipements -->
                <div class="tab" id="tab-services">
                    <div class="form-section">
                        <h3>Accessibilité PMR</h3>
                        <div class="field">
                            <label for="wheelchair">Accessibilité PMR</label>
                            <select id="wheelchair" name="wheelchair">
                                <option value="">Non renseigné</option>
                                <option value="yes">Oui</option>
                                <option value="limited">Partiel</option>
                                <option value="no">Non</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-section">
                        <h3>Équipements</h3>
                        <div class="field">
                            <label for="internet_access">Accès Internet / WiFi</label>
                            <select id="internet_access" name="internet_access">
                                <option value="">Non renseigné</option>
                                <option value="yes">Oui</option>
                                <option value="no">Non</option>
                                <option value="wlan">WiFi</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="parking">Parking</label>
                            <select id="parking" name="parking">
                                <option value="">Non renseigné</option>
                                <option value="yes">Oui</option>
                                <option value="no">Non</option>
                                <option value="private">Privé</option>
                                <option value="public">Public</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="fee">Tarif</label>
                            <select id="fee" name="fee">
                                <option value="">Non renseigné</option>
                                <option value="yes">Payant</option>
                                <option value="no">Gratuit</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="indoor">Intérieur</label>
                            <select id="indoor" name="indoor">
                                <option value="">Non renseigné</option>
                                <option value="yes">Oui</option>
                                <option value="no">Non</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="vending">Distributeur automatique</label>
                            <select id="vending" name="vending">
                                <option value="">Non renseigné</option>
                                <option value="yes">Oui</option>
                                <option value="no">Non</option>
                            </select>
                        </div>
                    </div>
                </div>

                {% if CORE.callHook('map:is-enabled') %}
                <!-- Onglet 3 : Localisation -->
                <div class="tab" id="tab-location">
                    <div class="form-section">
                        <h3>Position géographique</h3>
                        <p class="help-text">Cliquez sur la carte pour définir la position du POI</p>
                        <div class="field-row">
                            <div class="field">
                                <label for="lat">Latitude</label>
                                <input type="number" id="lat" name="lat" step="0.000001" value="47.0810" placeholder="47.0810">
                            </div>
                            <div class="field">
                                <label for="lon">Longitude</label>
                                <input type="number" id="lon" name="lon" step="0.000001" value="2.3988" placeholder="2.3988">
                            </div>
                        </div>
                        <div class="field" style="margin-top: 15px;">
                            <div id="poi-map" style="height: 400px; width: 100%; border: 1px solid #ddd; border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>
                {% endif %}

                <!-- Onglet 4 : Description -->
                <div class="tab" id="tab-content">
                    <div class="form-section">
                        <h3>Description</h3>
                        <div class="field">
                            <label for="description">Description détaillée</label>
                            <textarea id="description" name="description" rows="8" class="editor" placeholder="Description du lieu…"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Onglet 5 : Liens / Réseaux sociaux -->
                <div class="tab" id="tab-links">
                    <div class="form-section">
                        <h3>Liens &amp; Réseaux sociaux</h3>
                        <p class="help-text">Ajoutez des liens vers les réseaux sociaux ou d'autres sites.</p>
                        <div id="links-container">
                            <div class="link-row" style="display:flex; gap:8px; margin-bottom:8px; align-items:center;">
                                <input type="url" name="links[0][url]" placeholder="https://…" style="flex:2;">
                                <input type="text" name="links[0][label]" placeholder="Libellé (ex: Facebook)" style="flex:1;">
                                <button type="button" class="button small danger remove-link-btn" style="flex-shrink:0;"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                        <button type="button" id="add-link-btn" class="button small">
                            <i class="fa-solid fa-plus"></i> Ajouter un lien
                        </button>
                    </div>
                </div>

                <!-- Onglet 6 : Tags -->
                <div class="tab" id="tab-tags">
                    <div class="form-section">
                        <h3>Tags</h3>
                        <p class="help-text">Mots-clés séparés par des virgules (ex : bio, local, artisan).</p>
                        <div class="field">
                            <label for="tags">Tags</label>
                            <input type="text" id="tags" name="tags" value="{{ tags|default('') }}" placeholder="tag1, tag2, tag3…">
                        </div>
                    </div>
                </div>

                <!-- Onglet 7 : Photo -->
                <div class="tab" id="tab-photo">
                    <div class="form-section">
                        <h3>Photo principale</h3>
                        <p class="help-text">Vous pourrez ajouter plusieurs photos depuis la page d'édition après la création.</p>
                        <div class="field">
                            <label for="photo">Uploader une photo</label>
                            <input type="file" id="photo" name="photo" accept="image/*">
                        </div>
                        <div class="field">
                            <label for="photo_caption">Légende</label>
                            <input type="text" id="photo_caption" name="photo_caption" placeholder="Légende de la photo…">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="form-actions" style="margin-top: 30px;">
            <button type="submit" class="button success">
                <i class="fa-solid fa-save"></i> Créer le POI
            </button>
            <a href="{{ ROUTER.generate('admin-poi-edit') }}" class="button">Annuler</a>
        </div>
    </form>
</section>

{% if CORE.callHook('map:is-enabled') %}
{{ CORE.callHook('map:load-assets')|raw }}
{% endif %}

<script>
// Données catégories
var typesCategories = {% if typesCategoriesJson %}{{ typesCategoriesJson|raw }}{% else %}null{% endif %};
var specialtiesByFamily = {% if specialtiesByFamilyJson %}{{ specialtiesByFamilyJson|raw }}{% else %}null{% endif %};

// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    var tabHeaders = document.querySelectorAll('.tabs-header > li');
    var tabs = document.querySelectorAll('.tab');
    tabHeaders.forEach(function(header) {
        header.addEventListener('click', function() {
            var targetTab = this.getAttribute('data-tab');
            tabHeaders.forEach(function(h) { h.classList.remove('active'); });
            tabs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
            var el = document.getElementById('tab-' + targetTab);
            if (el) el.classList.add('active');
            
            // Invalidate map size if switching to location tab
            if (targetTab === 'location' && window.poiMap) {
                setTimeout(function() {
                    window.poiMap.refresh();
                }, 100);
            }
        });
    });
});

// Mise à jour des spécialités (checkboxes) par famille
function updateSpecialties() {
    var familySelect = document.getElementById('family_select');
    var container = document.getElementById('subcategories_container');
    var customWrapper = document.getElementById('custom_subcategory_wrapper');
    
    if (!familySelect || !container) return;
    
    var familyKey = familySelect.value;
    container.innerHTML = '';
    
    if (!familyKey || !specialtiesByFamily || !specialtiesByFamily[familyKey]) {
        container.innerHTML = '<p style="grid-column: 1 / -1; color: #666; font-style: italic; margin: 0;">Sélectionnez d\'abord une famille pour voir les spécialités disponibles</p>';
        if (customWrapper) customWrapper.style.display = 'none';
        return;
    }
    
    var specs = specialtiesByFamily[familyKey];
    if (specs.length === 0) {
        container.innerHTML = '<p style="grid-column: 1 / -1; color: #666; font-style: italic; margin: 0;">Aucune spécialité disponible pour cette famille</p>';
    } else {
        specs.forEach(function(spec) {
            var label = document.createElement('label');
            label.style.cssText = 'display: flex; align-items: center; cursor: pointer; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #fff;';
            
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = 'subcategories[]';
            cb.value = spec.key;
            cb.style.marginRight = '8px';
            
            var span = document.createElement('span');
            span.textContent = spec.label;
            
            label.appendChild(cb);
            label.appendChild(span);
            container.appendChild(label);
        });
    }
    
    // Always show custom field for admin
    if (customWrapper) customWrapper.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    var familySelect = document.getElementById('family_select');
    if (familySelect) {
        familySelect.addEventListener('change', updateSpecialties);
    }
});

// Initialisation Leaflet UniversalMap
document.addEventListener('DOMContentLoaded', function() {
    var latInput = document.getElementById('lat');
    var lonInput = document.getElementById('lon');
    var mapEl = document.getElementById('poi-map');
    
    if (!mapEl || typeof UniversalMap === 'undefined') return;
    
    window.poiMap = new UniversalMap('poi-map', {
        lat: parseFloat(latInput.value) || 47.0810,
        lon: parseFloat(lonInput.value) || 2.3988,
        zoom: 13,
        editable: true,
        onChange: function(pos) {
            latInput.value = pos.lat.toFixed(6);
            lonInput.value = pos.lon.toFixed(6);
        }
    });

    latInput.addEventListener('change', function() {
        if (window.poiMap) window.poiMap.setCenter(parseFloat(this.value), parseFloat(lonInput.value));
    });
    lonInput.addEventListener('change', function() {
        if (window.poiMap) window.poiMap.setCenter(parseFloat(latInput.value), parseFloat(this.value));
    });
});

// Gestion dynamique des liens
document.addEventListener('DOMContentLoaded', function() {
    var linksContainer = document.getElementById('links-container');
    var addLinkBtn     = document.getElementById('add-link-btn');
    var linkIndex      = 1;

    if (addLinkBtn && linksContainer) {
        addLinkBtn.addEventListener('click', function() {
            var row = document.createElement('div');
            row.className = 'link-row';
            row.style.cssText = 'display:flex; gap:8px; margin-bottom:8px; align-items:center;';
            row.innerHTML =
                '<input type="url" name="links[' + linkIndex + '][url]" placeholder="https://…" style="flex:2;">' +
                '<input type="text" name="links[' + linkIndex + '][label]" placeholder="Libellé (ex: Instagram)" style="flex:1;">' +
                '<button type="button" class="button small danger remove-link-btn" style="flex-shrink:0;"><i class="fa-solid fa-trash"></i></button>';
            linksContainer.appendChild(row);
            linkIndex++;
        });

        linksContainer.addEventListener('click', function(e) {
            var btn = e.target.closest('.remove-link-btn');
            if (btn) {
                var row = btn.closest('.link-row');
                if (row && linksContainer.querySelectorAll('.link-row').length > 1) {
                    row.remove();
                } else if (row) {
                    row.querySelectorAll('input').forEach(function(i) { i.value = ''; });
                }
            }
        });
    }
});
</script>

