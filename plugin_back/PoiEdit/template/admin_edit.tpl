<section class="module-card">
    <header>
        <i class="fa-solid fa-pen-to-square"></i>
        Édition admin : {{ poi.name }}
    </header>

    <form method="post" action="{{ ROUTER.generate("admin-poi-edit-save") }}" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="poi_id" value="{{ poi.id }}">

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
        </style>

        <div class="tabs-container">
            <ul class="tabs-header">
                <li class="active" data-tab="general">
                    <i class="fa-solid fa-info-circle"></i> Informations générales
                </li>
                <li data-tab="services">
                    <i class="fa-solid fa-cogs"></i> Services & Équipements
                </li>
                {% if CORE.callHook('map:is-enabled') %}
                <li data-tab="location">
                    <i class="fa-solid fa-map-marker-alt"></i> Localisation
                </li>
                {% endif %}
                <li data-tab="personalization">
                    <i class="fa-solid fa-palette"></i> Personnalisation
                </li>
                <li data-tab="content">
                    <i class="fa-solid fa-images"></i> Contenu
                </li>
            </ul>

            <div class="tabs">
                <!-- Onglet 1: Informations générales -->
                <div class="tab active" id="tab-general">
        <div class="form-section">
            <h3>Informations de base</h3>
            
            <div class="field">
                <label for="name">Nom</label>
                <input type="text" id="name" name="name" value="{{ poi.name }}">
            </div>

            <div class="field">
                <label for="family_select">Famille</label>
                <select id="family_select" name="category">
                    <option value="">-- Sélectionner une famille --</option>
                    {% if familiesForSelect %}
                        {% for fam in familiesForSelect %}
                            <option value="{{ fam.slug }}" {% if selectedCategory == fam.slug %}selected{% endif %}>{{ fam.label }}</option>
                        {% endfor %}
                    {% endif %}
                </select>
            </div>

            <div class="field">
                <label>Spécialités</label>
                <div id="subcategories_container" class="checkbox-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px; max-height: 300px; overflow-y: auto; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                    <!-- Les checkboxes seront générées par JS -->
                </div>
                <div id="custom_subcategory_wrapper" style="display:none; margin-top:8px;">
                    <label for="custom_subcategory" style="font-size: 0.9em; font-weight: normal;">Nom de la spécialité personnalisée :</label>
                    <input type="text" id="custom_subcategory" name="subcategory_custom" value="{{ poi.subcategory_custom|default('') }}" placeholder="Saisissez la spécialité…" style="width:100%;">
                    <small style="color:#888;">Votre proposition sera prise en compte directement comme spécialité du POI.</small>
                </div>
                <small id="subcat_help" style="color: #666; font-style: italic;">Sélectionnez d'abord une famille pour voir les spécialités disponibles</small>
            </div>

            <div class="field">
                <label for="brand">Marque</label>
                <input type="text" id="brand" name="brand" value="{{ poi.brand }}">
            </div>
        </div>

        <div class="form-section">
            <h3>Adresse</h3>
            
            <div class="field">
                <label for="street">Rue</label>
                <input type="text" id="street" name="street" value="{{ poi.addr_street }}">
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="housenumber">Numéro</label>
                    <input type="text" id="housenumber" name="housenumber" value="{{ poi.addr_housenumber }}">
                </div>

                <div class="field">
                    <label for="postcode">Code postal</label>
                    <input type="text" id="postcode" name="postcode" value="{{ poi.addr_postcode }}">
                </div>

                <div class="field">
                    <label for="city">Ville</label>
                    <input type="text" id="city" name="city" value="{{ poi.addr_city }}">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Contact</h3>
            
            <div class="field">
                <label for="phone">Téléphone</label>
                <input type="text" id="phone" name="phone" value="{{ poi.contact_phone }}">
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="text" id="email" name="email" value="{{ poi.contact_email }}">
            </div>

            <div class="field">
                <label for="website">Site web</label>
                <input type="url" id="website" name="website" value="{{ poi.website }}">
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
                        {% if poiOpeningHours %}
                            {% for hour in poiOpeningHours %}
                                <div class="opening-hour-row" data-index="{{ loop.index0 }}">
                                    <select name="hours[{{ loop.index0 }}][day_of_week]">
                                        <option value="0" {% if hour.day_of_week == 0 %}selected{% endif %}>Lundi</option>
                                        <option value="1" {% if hour.day_of_week == 1 %}selected{% endif %}>Mardi</option>
                                        <option value="2" {% if hour.day_of_week == 2 %}selected{% endif %}>Mercredi</option>
                                        <option value="3" {% if hour.day_of_week == 3 %}selected{% endif %}>Jeudi</option>
                                        <option value="4" {% if hour.day_of_week == 4 %}selected{% endif %}>Vendredi</option>
                                        <option value="5" {% if hour.day_of_week == 5 %}selected{% endif %}>Samedi</option>
                                        <option value="6" {% if hour.day_of_week == 6 %}selected{% endif %}>Dimanche</option>
                                    </select>
                                    <select name="hours[{{ loop.index0 }}][open_time]" class="time-select">
                                        <option value="">--:--</option>
                                        {% for h in 0..23 %}
                                            {% for m in [0, 15, 30, 45] %}
                                                {% set t = "%02d:%02d"|format(h, m) %}
                                                <option value="{{ t }}" {% if hour.open_time|slice(0, 5) == t %}selected{% endif %}>{{ t }}</option>
                                            {% endfor %}
                                        {% endfor %}
                                    </select>
                                    <select name="hours[{{ loop.index0 }}][close_time]" class="time-select">
                                        <option value="">--:--</option>
                                        {% for h in 0..23 %}
                                            {% for m in [0, 15, 30, 45] %}
                                                {% set t = "%02d:%02d"|format(h, m) %}
                                                <option value="{{ t }}" {% if hour.close_time|slice(0, 5) == t %}selected{% endif %}>{{ t }}</option>
                                            {% endfor %}
                                        {% endfor %}
                                        <option value="24:00" {% if hour.close_time|slice(0, 5) == "24:00" %}selected{% endif %}>24:00</option>
                                    </select>
                                    <div class="checkbox-cell">
                                        <input type="checkbox" name="hours[{{ loop.index0 }}][is_closed]" value="1" {% if hour.is_closed %}checked{% endif %}>
                                    </div>
                                    <button type="button" class="button small alert remove-hour-row"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            {% endfor %}
                        {% endif %}
                    </div>
                </div>
                <button type="button" id="add-hour-row" class="button small mt-2">+ Ajouter une plage horaire</button>
                <input type="hidden" name="opening_hours" value="{{ poi.opening_hours }}"> <!-- Keep for legacy, but structured is priority -->
            </div>
                    </div>
            </div>

                <!-- Onglet 2: Services & Équipements -->
                <div class="tab" id="tab-services">
                    <div class="form-section">
                        <h3>Accessibilité PMR</h3>
            <div class="field">
                <label for="wheelchair">Accessibilité PMR</label>
                <select id="wheelchair" name="wheelchair">
                    <option value="">Non renseigné</option>
                    <option value="yes" {% if poi.wheelchair == "yes" %}selected{% endif %}>Oui</option>
                    <option value="limited" {% if poi.wheelchair == "limited" %}selected{% endif %}>Partiel</option>
                    <option value="no" {% if poi.wheelchair == "no" %}selected{% endif %}>Non</option>
                </select>
            </div>
        </div>

        <div class="form-section">
            <h3>Équipements et services</h3>
            
            <div class="field">
                <label for="internet_access">Accès Internet / WiFi</label>
                <select id="internet_access" name="internet_access">
                    <option value="">Non renseigné</option>
                    <option value="yes" {% if poi.internet_access == "yes" %}selected{% endif %}>Oui</option>
                    <option value="no" {% if poi.internet_access == "no" %}selected{% endif %}>Non</option>
                    <option value="wlan" {% if poi.internet_access == "wlan" %}selected{% endif %}>WiFi</option>
                    <option value="wifi" {% if poi.internet_access == "wifi" %}selected{% endif %}>WiFi (alternatif)</option>
                </select>
            </div>

            <div class="field">
                <label for="parking">Parking</label>
                <select id="parking" name="parking">
                    <option value="">Non renseigné</option>
                    <option value="yes" {% if poi.parking == "yes" %}selected{% endif %}>Oui</option>
                    <option value="no" {% if poi.parking == "no" %}selected{% endif %}>Non</option>
                    <option value="private" {% if poi.parking == "private" %}selected{% endif %}>Privé</option>
                    <option value="public" {% if poi.parking == "public" %}selected{% endif %}>Public</option>
                </select>
            </div>

            <div class="field">
                <label for="fee">Tarif</label>
                <select id="fee" name="fee">
                    <option value="">Non renseigné</option>
                    <option value="yes" {% if poi.fee == "yes" %}selected{% endif %}>Payant</option>
                    <option value="no" {% if poi.fee == "no" %}selected{% endif %}>Gratuit</option>
                </select>
            </div>

            <div class="field">
                <label for="indoor">Intérieur</label>
                <select id="indoor" name="indoor">
                    <option value="">Non renseigné</option>
                    <option value="yes" {% if poi.indoor == "yes" %}selected{% endif %}>Oui</option>
                    <option value="no" {% if poi.indoor == "no" %}selected{% endif %}>Non</option>
                </select>
            </div>

            <div class="field">
                <label for="vending">Distributeur automatique</label>
                <select id="vending" name="vending">
                    <option value="">Non renseigné</option>
                    <option value="yes" {% if poi.vending == "yes" %}selected{% endif %}>Oui</option>
                    <option value="no" {% if poi.vending == "no" %}selected{% endif %}>Non</option>
                </select>
                        </div>
            </div>
        </div>

                {% if CORE.callHook('map:is-enabled') %}
                <!-- Onglet 3: Localisation -->
                <div class="tab" id="tab-location">
        <div class="form-section">
            <h3>Position géographique</h3>
            <p class="help-text">Cliquez sur la carte pour définir la position du POI</p>
            
            <div class="field-row">
                <div class="field">
                    <label for="lat">Latitude</label>
                    <input type="number" id="lat" name="lat" step="0.000001" value="{{ poi.lat }}">
                </div>

                <div class="field">
                    <label for="lon">Longitude</label>
                    <input type="number" id="lon" name="lon" step="0.000001" value="{{ poi.lon }}">
                </div>
            </div>

            <div class="field" style="margin-top: 15px;">
                <div id="poi-map" style="height: 400px; width: 100%; border: 1px solid #ddd; border-radius: 4px;"></div>
                        </div>
            </div>
        </div>
        {% endif %}

                <!-- Onglet 4: Personnalisation -->
                <div class="tab" id="tab-personalization">
        <div class="form-section">
                        <h3>Apparence</h3>
            
            <div class="field">
                            <label for="logo_file">Logo</label>
                {% if logo %}
                            <div style="margin-bottom: 10px;">
                                <img src="{{ logo }}" alt="Logo actuel" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; border-radius: 4px;">
                                <br><small>Logo actuel</small>
                            </div>
                            <input type="hidden" id="logo" name="logo" value="{{ logo }}">
                {% endif %}
                            <input type="file" id="logo_file" name="logo_file" accept="image/*">
                            <small>Télécharger une image de logo (JPG, PNG, GIF, WebP, SVG)</small>
            </div>

            <div class="field">
                            <label>Couleur de fond</label>
                            <div class="color-picker-container" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                                <label class="color-option" style="display: flex; align-items: center; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="radio" name="background_color" value="" {% if not backgroundColor %}checked{% endif %} style="margin-right: 8px;">
                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; background: #fafafa; border: 1px solid #ddd; margin-right: 8px;"></span>
                                    <span>Par défaut (style)</span>
                                </label>
                                <label class="color-option" style="display: flex; align-items: center; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="radio" name="background_color" value="#ffffff" {% if backgroundColor == "#ffffff" %}checked{% endif %} style="margin-right: 8px;">
                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; background: #ffffff; border: 1px solid #ddd; margin-right: 8px;"></span>
                                    <span>Blanc</span>
                                </label>
                                <label class="color-option" style="display: flex; align-items: center; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="radio" name="background_color" value="#f0f0f0" {% if backgroundColor == "#f0f0f0" %}checked{% endif %} style="margin-right: 8px;">
                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; background: #f0f0f0; border: 1px solid #ddd; margin-right: 8px;"></span>
                                    <span>Gris clair</span>
                                </label>
                                <label class="color-option" style="display: flex; align-items: center; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="radio" name="background_color" value="#fff8e1" {% if backgroundColor == "#fff8e1" %}checked{% endif %} style="margin-right: 8px;">
                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; background: #fff8e1; border: 1px solid #ddd; margin-right: 8px;"></span>
                                    <span>Jaune pâle</span>
                                </label>
                                <label class="color-option" style="display: flex; align-items: center; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="radio" name="background_color" value="#e3f2fd" {% if backgroundColor == "#e3f2fd" %}checked{% endif %} style="margin-right: 8px;">
                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; background: #e3f2fd; border: 1px solid #ddd; margin-right: 8px;"></span>
                                    <span>Bleu pâle</span>
                                </label>
                                <label class="color-option" style="display: flex; align-items: center; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="radio" name="background_color" value="#f3e5f5" {% if backgroundColor == "#f3e5f5" %}checked{% endif %} style="margin-right: 8px;">
                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; background: #f3e5f5; border: 1px solid #ddd; margin-right: 8px;"></span>
                                    <span>Violet pâle</span>
                                </label>
                                <label class="color-option" style="display: flex; align-items: center; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="radio" name="background_color" value="#e8f5e9" {% if backgroundColor == "#e8f5e9" %}checked{% endif %} style="margin-right: 8px;">
                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; background: #e8f5e9; border: 1px solid #ddd; margin-right: 8px;"></span>
                                    <span>Vert pâle</span>
                                </label>
                                <label class="color-option" style="display: flex; align-items: center; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="radio" name="background_color" value="#fff3e0" {% if backgroundColor == "#fff3e0" %}checked{% endif %} style="margin-right: 8px;">
                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; background: #fff3e0; border: 1px solid #ddd; margin-right: 8px;"></span>
                                    <span>Orange pâle</span>
                                </label>
                                <label class="color-option" style="display: flex; align-items: center; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="radio" name="background_color" value="#fce4ec" {% if backgroundColor == "#fce4ec" %}checked{% endif %} style="margin-right: 8px;">
                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; background: #fce4ec; border: 1px solid #ddd; margin-right: 8px;"></span>
                                    <span>Rose pâle</span>
                                </label>
                                <label class="color-option" style="display: flex; align-items: center; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="radio" name="background_color" value="#e0f2f1" {% if backgroundColor == "#e0f2f1" %}checked{% endif %} style="margin-right: 8px;">
                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; background: #e0f2f1; border: 1px solid #ddd; margin-right: 8px;"></span>
                                    <span>Turquoise pâle</span>
                                </label>
                            </div>
                            <small style="display: block; margin-top: 10px; color: #666;">Sélectionnez une couleur ou laissez "Par défaut" pour utiliser la couleur du style</small>
            </div>

            <div class="field">
                <label for="stars">Nombre d'étoiles (hôtels, restaurants)</label>
                <select id="stars" name="stars">
                    <option value="">Non renseigné</option>
                    <option value="1" {% if stars == "1" %}selected{% endif %}>1 étoile</option>
                    <option value="2" {% if stars == "2" %}selected{% endif %}>2 étoiles</option>
                    <option value="3" {% if stars == "3" %}selected{% endif %}>3 étoiles</option>
                    <option value="4" {% if stars == "4" %}selected{% endif %}>4 étoiles</option>
                    <option value="5" {% if stars == "5" %}selected{% endif %}>5 étoiles</option>
                </select>
            </div>
        </div>

        <div class="form-section">
            <h3>Tags et Mots-clés</h3>
            <p><small>Saisissez des tags séparés par des virgules pour faciliter la recherche (ex: restaurant, terrasse, wifi).</small></p>
            
            <div class="field">
                <label for="tags">Tags</label>
                <input type="text" id="tags" name="tags" value="{{ poi.tags }}" placeholder="ex: tag1, tag2, superlieu">
            </div>
        </div>

                <!-- Onglet 5: Contenu -->
                <div class="tab" id="tab-content">
        <div class="form-section">
            <h3>Réseaux sociaux et liens</h3>
            <div id="links-container">
                {% if links %}
                    {% for link in links %}
                    <div class="link-item" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <input type="hidden" name="links[{{ loop.index0 }}][id]" value="{{ link.id }}">
                        <div class="field">
                            <label>URL</label>
                            <input type="url" name="links[{{ loop.index0 }}][url]" value="{{ link.url }}" placeholder="https://...">
                        </div>
                        <div class="field">
                            <label>Label (optionnel)</label>
                            <input type="text" name="links[{{ loop.index0 }}][label]" value="{{ link.label }}" placeholder="Facebook, Instagram, etc.">
                        </div>
                        <button type="button" class="button small alert remove-link" style="margin-top: 5px;">Supprimer</button>
                    </div>
                    {% endfor %}
                {% endif %}
            </div>
            <button type="button" id="add-link-btn" class="button small">+ Ajouter un lien</button>
        </div>

        <div class="form-section">
            <h3>Photos</h3>
                        <div id="photos-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
                {% if photos %}
                    {% for photo in photos %}
                                <div class="photo-gallery-item" data-photo-id="{{ photo.id }}" style="position: relative; border: 1px solid #ddd; border-radius: 4px; padding: 10px; background: #fff;">
                                    <div style="position: relative; width: 100%; padding-top: 75%; overflow: hidden; border-radius: 4px; background: #f5f5f5;">
                                        <img src="{{ photo.url }}" alt="{{ photo.caption }}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; cursor: pointer;" onclick="openPhotoModal('{{ photo.url }}', '{{ photo.caption_escaped }}')">
                                    </div>
                                    <div class="photo-actions" style="margin-top: 10px;">
                                        <input type="text" class="photo-caption-input" value="{{ photo.caption }}" data-photo-id="{{ photo.id }}" placeholder="Légende..." style="width: 100%; padding: 5px; margin-bottom: 5px; border: 1px solid #ddd; border-radius: 3px;">
                                        <div style="display: flex; gap: 5px;">
                                            <button type="button" class="button small update-photo-caption" data-photo-id="{{ photo.id }}" style="flex: 1;">Renommer</button>
                                            <button type="button" class="button small alert delete-photo" data-photo-id="{{ photo.id }}" style="flex: 1;">Supprimer</button>
                                        </div>
                                    </div>
                    </div>
                    {% endfor %}
                            {% else %}
                                <p style="grid-column: 1 / -1; text-align: center; color: #999; font-style: italic;">Aucune photo pour le moment</p>
                {% endif %}
            </div>
            <div class="field">
                <label for="photo">Ajouter une photo</label>
                <input type="file" id="photo" accept="image/*">
                <input type="text" id="photo_caption" placeholder="Légende (optionnel)">
                <div id="photo-upload-status" style="margin-top: 5px; font-size: 0.9em; display: none;"></div>
            </div>
        </div>

        <div class="form-section">
            <h3>Description</h3>
            <div class="field">
                <label for="description">Description détaillée</label>
                            <textarea id="description" name="description" rows="6" class="editor">{{ description }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 30px;">
            <button type="submit" class="button success">Enregistrer</button>
            <a href="{{ ROUTER.generate("admin-poi-edit") }}" class="button">Retour</a>
        </div>
    </form>
</section>

{# Les assets de la carte sont chargés plus bas avec |raw #}
<script>
// Données métier
var typesCategories = {% if typesCategoriesJson %}{{ typesCategoriesJson|raw }}{% else %}null{% endif %};
var specialtiesByFamily = {% if specialtiesByFamilyJson %}{{ specialtiesByFamilyJson|raw }}{% else %}null{% endif %};
var hourIndex = {{ poiOpeningHours|length|default(0) }};
var linkIndex = {% if linksCount %}{{ linksCount }}{% else %}0{% endif %};
var poiId = "{{ poi.id }}";

document.addEventListener('DOMContentLoaded', function() {
    // 1. GESTION DES ONGLETS
    const tabHeaders = document.querySelectorAll('.tabs-header > li');
    const tabs = document.querySelectorAll('.tab');
    
    tabHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            tabHeaders.forEach(h => h.classList.remove('active'));
            tabs.forEach(t => t.classList.remove('active'));
            
            this.classList.add('active');
            const targetTabElement = document.getElementById('tab-' + targetTab);
            if (targetTabElement) {
                targetTabElement.classList.add('active');
            }
            
            // Rafraîchir la carte si on passe à l'onglet location
            if (targetTab === 'location' && window.poiMap) {
                setTimeout(function() {
                    window.poiMap.refresh();
                }, 100);
            }
        });
    });

    // 2. GESTION DES HORAIRES
    const hoursContainer = document.getElementById('opening-hours-rows');
    const addHourBtn = document.getElementById('add-hour-row');

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
                    <option value="0">Lundi</option>
                    <option value="1">Mardi</option>
                    <option value="2">Mercredi</option>
                    <option value="3">Jeudi</option>
                    <option value="4">Vendredi</option>
                    <option value="5">Samedi</option>
                    <option value="6">Dimanche</option>
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
    }

    // 3. INITIALISATION CARTE
    const latInput = document.getElementById('lat');
    const lonInput = document.getElementById('lon');
    const mapEl = document.getElementById('poi-map');
    
    if (mapEl && typeof UniversalMap !== 'undefined') {
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
    }
});
// Management of subcategories is handled by the script at the bottom of the file

// Gestion des liens (réseaux sociaux)
var linkIndex = {% if linksCount %}{{ linksCount }}{% else %}0{% endif %};
document.getElementById('add-link-btn')?.addEventListener('click', function() {
    const container = document.getElementById('links-container');
    const div = document.createElement('div');
    div.className = 'link-item';
    div.style.cssText = 'margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;';
    div.innerHTML = `
        <div class="field">
            <label>URL</label>
            <input type="url" name="links[${linkIndex}][url]" placeholder="https://...">
        </div>
        <div class="field">
            <label>Label (optionnel)</label>
            <input type="text" name="links[${linkIndex}][label]" placeholder="Facebook, Instagram, etc.">
        </div>
        <button type="button" class="button small alert remove-link" style="margin-top: 5px;">Supprimer</button>
    `;
    container.appendChild(div);
    linkIndex++;
    
    // Ajouter l'événement de suppression
    div.querySelector('.remove-link').addEventListener('click', function() {
        div.remove();
    });
});

// Supprimer les liens existants
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('remove-link')) {
        e.preventDefault();
        e.stopPropagation();
        var linkItem = e.target.closest('.link-item');
        if (linkItem) {
            // Marquer l'input hidden comme supprimé en vidant l'URL
            var urlInput = linkItem.querySelector('input[name*="[url]"]');
            if (urlInput) {
                urlInput.value = '';
            }
            // Cacher l'élément au lieu de le supprimer pour qu'il soit toujours envoyé
            linkItem.style.display = 'none';
        }
    }
});

// Gestion des tags supprimée et simplifiée en un seul champ input text.

// 4. GESTION DES PHOTOS
document.addEventListener('DOMContentLoaded', function() {
    if (!poiId) return;

    // Supprimer une photo
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('delete-photo')) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette photo ?')) {
                return;
            }
            
            const photoId = e.target.getAttribute('data-photo-id');
            const photoItem = e.target.closest('.photo-gallery-item');
            
            if (!photoId) {
                console.error('Photo ID manquant');
                alert('Erreur : ID de photo manquant');
                return;
            }
            
            if (!poiId) {
                console.error('POI ID manquant');
                alert('Erreur : ID de POI manquant');
                return;
            }
            
            const deleteUrl = '{{ ROUTER.generate("admin-poi-edit-delete-photo", ["photoId" => 0]) }}'.replace('/0', '/' + photoId);
            console.log('Suppression photo:', { photoId, poiId, url: deleteUrl });
            
            fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'poi_id=' + poiId
            })
            .then(response => {
                console.log('Response status:', response.status, response.statusText);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('HTTP error response:', text);
                        throw new Error('HTTP error! status: ' + response.status + ' - ' + text);
                    });
                }
                const contentType = response.headers.get('content-type');
                console.log('Content-Type:', contentType);
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Expected JSON but got:', text);
                        throw new Error('Réponse non-JSON reçue: ' + text.substring(0, 200));
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Delete response data:', data);
                if (data.success) {
                    photoItem.remove();
                    // Si plus de photos, afficher un message
                    const container = document.getElementById('photos-container');
                    if (container && container.querySelectorAll('.photo-gallery-item').length === 0) {
                        container.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #999; font-style: italic;">Aucune photo pour le moment</p>';
                    }
                } else {
                    console.error('Delete failed:', data.message);
                    alert('Erreur : ' + (data.message || 'Erreur lors de la suppression'));
                }
            })
            .catch(error => {
                console.error('Error lors de la suppression:', error);
                alert('Erreur lors de la suppression de la photo : ' + error.message);
            });
        }
    });

    // Mettre à jour la légende d'une photo
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('update-photo-caption')) {
            e.preventDefault();
            e.stopPropagation();
            
            const photoId = e.target.getAttribute('data-photo-id');
            const photoItem = e.target.closest('.photo-gallery-item');
            const captionInput = photoItem.querySelector('.photo-caption-input');
            const caption = captionInput.value.trim();
            
            fetch('{{ ROUTER.generate("admin-poi-edit-update-photo-caption") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'photo_id=' + photoId + '&poi_id=' + poiId + '&caption=' + encodeURIComponent(caption)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Expected JSON but got:', text);
                        throw new Error('Réponse non-JSON reçue');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    e.target.textContent = '✓ Enregistré';
                    e.target.style.background = 'var(--success-color, #7cb342)';
                    setTimeout(() => {
                        e.target.textContent = 'Renommer';
                        e.target.style.background = '';
                    }, 2000);
                } else {
                    alert(data.message || 'Erreur lors de la mise à jour');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors de la mise à jour de la légende');
            });
        }
    });

    // Ouvrir une photo en modal (agrandissement)
    window.openPhotoModal = function(url, caption) {
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; display: flex; align-items: center; justify-content: center; cursor: pointer;';
        modal.innerHTML = '<div style="max-width: 90%; max-height: 90%; position: relative;"><img src="' + url + '" style="max-width: 100%; max-height: 100%; object-fit: contain;" alt="' + (caption || '') + '"><button style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.8); border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 20px; cursor: pointer;">×</button></div>';
        document.body.appendChild(modal);
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal || e.target.tagName === 'BUTTON') {
                document.body.removeChild(modal);
            }
        });
    };

    // Upload automatique de photo lors de la sélection
    const photoInput = document.getElementById('photo');
    const photoCaptionInput = document.getElementById('photo_caption');
    const photoUploadStatus = document.getElementById('photo-upload-status');
    
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Vérifier le type de fichier
            if (!file.type.match('image.*')) {
                photoUploadStatus.textContent = 'Erreur : Veuillez sélectionner une image';
                photoUploadStatus.style.color = '#d32f2f';
                photoUploadStatus.style.display = 'block';
                return;
            }
            
            // Afficher le statut de chargement
            photoUploadStatus.textContent = 'Upload en cours...';
            photoUploadStatus.style.color = '#1976d2';
            photoUploadStatus.style.display = 'block';
            
            // Préparer FormData
            const formData = new FormData();
            formData.append('photo', file);
            formData.append('poi_id', poiId);
            if (photoCaptionInput && photoCaptionInput.value.trim()) {
                formData.append('caption', photoCaptionInput.value.trim());
            }
            
            // Upload AJAX
            fetch('{{ ROUTER.generate("admin-poi-edit-upload-photo") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Expected JSON but got:', text);
                        throw new Error('Réponse non-JSON reçue');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    photoUploadStatus.textContent = '✓ Photo uploadée avec succès';
                    photoUploadStatus.style.color = '#388e3c';
                    
                    // Ajouter la photo à la galerie
                    const container = document.getElementById('photos-container');
                    if (container) {
                        // Supprimer le message "Aucune photo" s'il existe
                        const emptyMsg = container.querySelector('p');
                        if (emptyMsg) {
                            emptyMsg.remove();
                        }
                        
                        // Créer l'élément photo
                        const photoItem = document.createElement('div');
                        photoItem.className = 'photo-gallery-item';
                        photoItem.setAttribute('data-photo-id', data.photo_id);
                        photoItem.style.cssText = 'position: relative; border: 1px solid #ddd; border-radius: 4px; padding: 10px; background: #fff;';
                        photoItem.innerHTML = `
                            <div style="position: relative; width: 100%; padding-top: 75%; overflow: hidden; border-radius: 4px; background: #f5f5f5;">
                                <img src="${data.url}" alt="${data.caption || ''}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; cursor: pointer;" onclick="openPhotoModal('${data.url}', '${(data.caption || '').replace(/'/g, "\\'")}')">
                            </div>
                            <div class="photo-actions" style="margin-top: 10px;">
                                <input type="text" class="photo-caption-input" value="${data.caption || ''}" data-photo-id="${data.photo_id}" placeholder="Légende..." style="width: 100%; padding: 5px; margin-bottom: 5px; border: 1px solid #ddd; border-radius: 3px;">
                                <div style="display: flex; gap: 5px;">
                                    <button type="button" class="button small update-photo-caption" data-photo-id="${data.photo_id}" style="flex: 1;">Renommer</button>
                                    <button type="button" class="button small alert delete-photo" data-photo-id="${data.photo_id}" style="flex: 1;">Supprimer</button>
                                </div>
                            </div>
                        `;
                        container.appendChild(photoItem);
                    }
                    
                    // Réinitialiser les champs
                    photoInput.value = '';
                    if (photoCaptionInput) {
                        photoCaptionInput.value = '';
                    }
                    
                    // Masquer le statut après 3 secondes
                    setTimeout(() => {
                        photoUploadStatus.style.display = 'none';
                    }, 3000);
                } else {
                    photoUploadStatus.textContent = 'Erreur : ' + (data.message || 'Échec de l\'upload');
                    photoUploadStatus.style.color = '#d32f2f';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                photoUploadStatus.textContent = 'Erreur : Échec de l\'upload';
                photoUploadStatus.style.color = '#d32f2f';
            });
        });
    }
});
</script>

<script>
(function() {
    const specialtiesByFamily = {{ specialtiesByFamilyJson|raw }};
    const selectedSubcategories = {{ selectedSubcategoriesJson|raw }};
    const poiSubcategory = '{{ poi.subcategory|default("") }}';

    const familySelect = document.getElementById('family_select');
    const subcategoriesContainer = document.getElementById('subcategories_container');
    const customSubcategoryWrapper = document.getElementById('custom_subcategory_wrapper');
    const customSubcategoryInput = document.getElementById('custom_subcategory');
    const subcatHelp = document.getElementById('subcat_help');

    function loadSpecialties(familyKey, selectedValues) {
        subcategoriesContainer.innerHTML = '';
        const specs = specialtiesByFamily[familyKey] || [];
        
        if (specs.length === 0 && !familyKey) {
            subcatHelp.style.display = 'block';
            subcategoriesContainer.style.display = 'none';
            return;
        }
        
        subcatHelp.style.display = 'none';
        subcategoriesContainer.style.display = 'grid';

        // Checkbox pour chaque spécialité mappée
        specs.forEach(function(spec) {
            const label = document.createElement('label');
            label.style.cssText = 'display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer; padding: 4px; border-radius: 3px;';
            label.onmouseover = function() { this.style.background = '#eee'; };
            label.onmouseout = function() { this.style.background = 'transparent'; };
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'subcategories[]';
            checkbox.value = spec.key;
            if (selectedValues && selectedValues.includes(spec.key)) checkbox.checked = true;
            
            label.appendChild(checkbox);
            label.appendChild(document.createTextNode(spec.label));
            subcategoriesContainer.appendChild(label);
        });

        // Case "Autre"
        const otherLabel = document.createElement('label');
        otherLabel.style.cssText = 'display: flex; align-items: center; gap: 8px; font-weight: bold; cursor: pointer; color: #1976d2; padding: 4px; border-top: 1px solid #ddd; margin-top: 5px;';
        
        const otherCheckbox = document.createElement('input');
        otherCheckbox.type = 'checkbox';
        otherCheckbox.id = 'other_subcat_checkbox';
        otherCheckbox.value = '__custom__';
        
        // Déterminer si "Autre" doit être coché : 
        // soit il y a une valeur dans subcategory_custom, 
        // soit la subcategory principale n'est pas dans le mapping de cette famille
        const isMapped = specs.some(s => s.key === poiSubcategory);
        const hasCustom = customSubcategoryInput && customSubcategoryInput.value.trim() !== '';
        
        if (hasCustom || (poiSubcategory && !isMapped && poiSubcategory !== '')) {
            otherCheckbox.checked = true;
        }

        otherCheckbox.addEventListener('change', toggleCustomField);
        
        otherLabel.appendChild(otherCheckbox);
        otherLabel.appendChild(document.createTextNode('-- Autre (précisez) --'));
        subcategoriesContainer.appendChild(otherLabel);
        
        toggleCustomField();
    }

    function toggleCustomField() {
        const otherCheckbox = document.getElementById('other_subcat_checkbox');
        if (otherCheckbox && otherCheckbox.checked) {
            customSubcategoryWrapper.style.display = 'block';
        } else {
            customSubcategoryWrapper.style.display = 'none';
            // On ne vide pas forcément l'input immédiatement pour éviter les pertes accidentelles, 
            // mais le contrôleur l'ignorera si non coché ou si vide.
        }
    }

    if (familySelect) {
        if (familySelect.value) {
            loadSpecialties(familySelect.value, selectedSubcategories);
        }
        familySelect.addEventListener('change', function() {
            loadSpecialties(this.value, []);
        });
    }
})();
</script>
