

{% if CORE.callHook('map:is-enabled') %}
    {{ CORE.callHook('map:load-assets') }}
{% endif %}

<section class="poi-contribute-form">
    <h1>Proposer un nouveau POI</h1>

    <form method="post" action="{{ ROUTER.generate("poi-edit-contribute-submit") }}" enctype="multipart/form-data" id="contribute-form" novalidate>
        <style>
            .opening-hours-grid {
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #fff;
                margin-top: 10px;
            }
            .opening-hours-header {
                display: grid;
                grid-template-columns: 2fr 1fr 1fr 1fr 40px;
                gap: 10px;
                padding: 10px;
                background: #f5f5f5;
                font-weight: bold;
                border-bottom: 2px solid #ddd;
            }
            .opening-hour-row {
                display: grid;
                grid-template-columns: 2fr 1fr 1fr 1fr 40px;
                gap: 10px;
                padding: 10px;
                border-bottom: 1px solid #eee;
                align-items: center;
            }
            .opening-hour-row:last-child {
                border-bottom: none;
            }
            .opening-hour-row select {
                width: 100% !important;
                padding: 5px !important;
                height: auto !important;
            }
            .checkbox-cell {
                text-align: center;
            }
            .remove-hour-row {
                padding: 5px !important;
                margin: 0 !important;
            }
            .mt-2 { margin-top: 0.5rem; }
        </style>
        <div class="form-section">
            <h2>Informations de base</h2>
            
            <div class="field">
                <label for="name">Nom</label>
                <input type="text" id="name" name="name">
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
                <label for="subcategory_select">Spécialité</label>
                <select id="subcategory_select" name="subcategory">
                    <option value="">-- Sélectionner une spécialité --</option>
                </select>
                <div id="custom_subcategory_wrapper" style="display:none; margin-top:8px;">
                    <input type="text" id="custom_subcategory" name="subcategory_custom" placeholder="Saisissez la spécialité…" style="width:100%;">
                    <small style="color:#888;">Votre proposition sera prise en compte directement.</small>
                </div>
                <small>Sélectionnez d'abord une famille pour voir les spécialités disponibles</small>
            </div>
        </div>

        <div class="form-section">
            <h2>Localisation</h2>
            
            <div class="field">
                <label>Position sur la carte *</label>
                <div id="contribute-map" class="poi-contribute-map"></div>
                <small>Cliquez sur la carte pour positionner le lieu, ou utilisez le bouton GPS ci-dessous</small>
                <button type="button" id="use-gps" class="button small mt-2">
                    <i class="fa-solid fa-location-crosshairs"></i> Utiliser ma position GPS
                </button>
                <input type="hidden" id="lat" name="lat">
                <input type="hidden" id="lon" name="lon">
            </div>
        </div>

        <div class="form-section">
            <h2>Adresse</h2>
            
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
            <h2>Contact</h2>
            
            <div class="field">
                <label for="phone">Téléphone</label>
                <input type="text" id="phone" name="phone">
            </div>

            <div class="field">
                <label for="website">Site web</label>
                <input type="url" id="website" name="website">
            </div>

            <div class="field">
                <label>Horaires d'ouverture</label>
                <div id="opening-hours-container" class="opening-hours-grid">
                    <div class="opening-hours-header">
                        <span>Jour</span>
                        <span>Ouverture</span>
                        <span>Fermeture</span>
                        <span>Fermé</span>
                        <span></span>
                    </div>
                    <div id="opening-hours-rows">
                        <!-- Empty at start -->
                    </div>
                </div>
                <button type="button" id="add-hour-row" class="button small mt-2">+ Ajouter une plage horaire</button>
            </div>

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
            <h2>Propriétaire</h2>
            
            <div class="field checkbox">
                <input type="checkbox" id="is_owner" name="is_owner" value="1">
                <label for="is_owner">Je suis propriétaire de cet établissement</label>
            </div>

            <div id="owner-fields" style="display:none;">
                <div class="field">
                    <label for="note">Note / Informations complémentaires</label>
                    <textarea id="note" name="note" rows="4" placeholder="Précisez votre relation avec l'établissement..."></textarea>
                </div>

                <div class="field">
                    <label for="proof">Justificatif (KBIS, facture, etc.) *</label>
                    <input type="file" id="proof" name="proof" accept=".pdf,.jpg,.jpeg,.png">
                    <small>Format accepté: PDF, JPG, PNG. Taille max: 5 Mo</small>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="button success">Envoyer la proposition</button>
            <a href="{{ ROUTER.generate("home") }}" class="button">Annuler</a>
        </div>
    </form>
</section>

<script>
(function() {
    const specialtiesByFamily = {{ specialtiesByFamilyJson }};

    const familySelect = document.getElementById('family_select');
    const subcategorySelect = document.getElementById('subcategory_select');
    const customSubcategoryWrapper = document.getElementById('custom_subcategory_wrapper');
    const customSubcategoryInput = document.getElementById('custom_subcategory');

    function loadSpecialties(familyKey, selectedValue) {
        subcategorySelect.innerHTML = '<option value="">-- Sélectionner une spécialité --</option>';
        const specs = specialtiesByFamily[familyKey] || [];
        specs.forEach(function(spec) {
            const opt = document.createElement('option');
            opt.value = spec.key;
            opt.textContent = spec.label;
            if (selectedValue && spec.key === selectedValue) opt.selected = true;
            subcategorySelect.appendChild(opt);
        });
        const otherOpt = document.createElement('option');
        otherOpt.value = '__custom__';
        otherOpt.textContent = '-- Autre (précisez) --';
        subcategorySelect.appendChild(otherOpt);
        toggleCustomField();
    }

    function toggleCustomField() {
        if (customSubcategoryWrapper) {
            customSubcategoryWrapper.style.display = subcategorySelect.value === '__custom__' ? 'block' : 'none';
            if (subcategorySelect.value !== '__custom__' && customSubcategoryInput) customSubcategoryInput.value = '';
        }
    }

    if (familySelect) {
        familySelect.addEventListener('change', function() { loadSpecialties(this.value, ''); });
    }
    if (subcategorySelect) {
        subcategorySelect.addEventListener('change', toggleCustomField);
    }
})();
</script>

    // --- 2. MAP LOGIC (Safeguarded) ---
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof L !== 'undefined' && document.getElementById('contribute-map')) {
        let map, marker;
        let currentLat = 46.2276; // Centre de la France par défaut
        let currentLon = 2.2137;

        // Initialiser la carte
        map = L.map('contribute-map').setView([currentLat, currentLon], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Function updateMarker
        function updateMarker(lat, lon) {
            currentLat = lat;
            currentLon = lon;
            const latInput = document.getElementById('lat');
            const lonInput = document.getElementById('lon');
            if (latInput) latInput.value = lat;
            if (lonInput) lonInput.value = lon;
            
            if (marker) {
                marker.setLatLng([lat, lon]);
            } else {
                // Icône personnalisée pour le marker
                // Use a standard marker or try to find a theme one safely
                var customIcon = L.icon({
                    iconUrl: 'theme/Lumiere/leaflet-marker.png', // Relative legacy path
                    iconSize: [24, 24],
                    iconAnchor: [12, 24],
                    popupAnchor: [0, -24]
                });
                
                // Fallback mechanism: if custom icon image fails, Leaflet might show broken image.
                // We'll just define it. If it 404s, it 404s.
                marker = L.marker([lat, lon], { icon: customIcon, draggable: true }).addTo(map);

                marker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    // Limit precision
                    const lat = pos.lat.toFixed(6);
                    const lon = pos.lng.toFixed(6);
                    if (latInput) latInput.value = lat;
                    if (lonInput) lonInput.value = lon;
                });
            }
            map.setView([lat, lon], Math.max(map.getZoom(), 15));
        }

        // Gérer les clics sur la carte
        map.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(6);
            const lon = e.latlng.lng.toFixed(6);
            updateMarker(lat, lon);
        });

        // Écouter les mises à jour globales
        window.addEventListener('user-location-updated', function(evt) {
            if (evt.detail && evt.detail.lat) {
                updateMarker(parseFloat(evt.detail.lat).toFixed(6), parseFloat(evt.detail.lon).toFixed(6));
            }
        });

        // Bouton GPS
        const gpsBtn = document.getElementById('use-gps');
        if (gpsBtn) {
            gpsBtn.addEventListener('click', function() {
                if (window.globalGeolocation) {
                    window.globalGeolocation.requestPosition();
                } else if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        updateMarker(position.coords.latitude.toFixed(6), position.coords.longitude.toFixed(6));
                    }, function(error) {
                        alert('Impossible d\'obtenir votre position. Veuillez cliquer sur la carte.');
                    });
                } else {
                    alert('La géolocalisation n\'est pas supportée par votre navigateur.');
                }
            });
        }
    } else {
        console.warn('Leaflet (L) not defined or map container missing. Map disabled.');
        const mapContainer = document.getElementById('contribute-map');
        if (mapContainer) {
            mapContainer.innerHTML = '<div style="padding:20px;text-align:center;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:4px;">La carte ne peut pas être chargée. Veuillez vérifier votre connexion ou réessayer plus tard.</div>';
        }
    }

    // --- 3. OTHER UI LOGIC ---
    // Afficher/masquer les champs propriétaire
    const ownerCheckbox = document.getElementById('is_owner');
    if (ownerCheckbox) {
        ownerCheckbox.addEventListener('change', function() {
            const ownerFields = document.getElementById('owner-fields');
            if (this.checked) {
                ownerFields.style.display = 'block';
            } else {
                ownerFields.style.display = 'none';
            }
        });
    }

    // Validation du formulaire
    document.getElementById('contribute-form').addEventListener('submit', function(e) {
        const lat = document.getElementById('lat').value;
        const lon = document.getElementById('lon').value;
        const isOwner = document.getElementById('is_owner').checked;
        
        // Only validate map if map is supposedly active or values are empty?
        // If map failed to load, user can't pick location.
        // We should warn them but maybe allow submit if they manually entered it? (There are no manual inputs for lat/lon hidden field)
        if (!lat || !lon) {
            e.preventDefault();
            alert('Veuillez positionner le lieu sur la carte.');
            return false;
        }
        
        if (isOwner && !document.getElementById('proof').files.length) {
            e.preventDefault();
            alert('Veuillez fournir un justificatif si vous êtes propriétaire.');
            return false;
        }
    });

    // --- 4. STRUCTURED OPENING HOURS LOGIC ---
    const hoursContainer = document.getElementById('opening-hours-rows');
    const addHourBtn = document.getElementById('add-hour-row');
    let hourIndex = 0;

    if (addHourBtn && hoursContainer) {
        addHourBtn.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'opening-hour-row';
            row.dataset.index = hourIndex;
            
            let timeOptions = '<option value="">--:--</option>';
            for (let h = 0; h < 24; h++) {
                for (let m = 0; m < 60; m += 15) {
                    const t = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                    timeOptions += `<option value="${t}">${t}</option>`;
                }
            }
            timeOptions += '<option value="24:00">24:00</option>';

            row.innerHTML = `
                <select name="hours[${hourIndex}][day_of_week]">
                    <option value="1">Lundi</option>
                    <option value="2">Mardi</option>
                    <option value="3">Mercredi</option>
                    <option value="4">Jeudi</option>
                    <option value="5">Vendredi</option>
                    <option value="6">Samedi</option>
                    <option value="7">Dimanche</option>
                </select>
                <select name="hours[${hourIndex}][open_time]" class="time-select">
                    ${timeOptions}
                </select>
                <select name="hours[${hourIndex}][close_time]" class="time-select">
                    ${timeOptions}
                </select>
                <div class="checkbox-cell">
                    <input type="checkbox" name="hours[${hourIndex}][is_closed]" value="1">
                </div>
                <button type="button" class="button small alert remove-hour-row"><i class="fa-solid fa-trash"></i></button>
            `;
            
            hoursContainer.appendChild(row);
            hourIndex++;
        });

        hoursContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-hour-row')) {
                e.target.closest('.opening-hour-row').remove();
            }
        });

        // Désactiver les selects d'heure si "Fermé" est coché
        hoursContainer.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox' && e.target.name.includes('[is_closed]')) {
                const row = e.target.closest('.opening-hour-row');
                const timeSelects = row.querySelectorAll('.time-select');
                timeSelects.forEach(select => {
                    select.disabled = e.target.checked;
                    if (e.target.checked) select.value = '';
                });
            }
        });
        
        // Add first row by default
        addHourBtn.click();
    }
});
</script>

