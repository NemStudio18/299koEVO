<section class="module-card">
    {% if CORE.callHook('map:is-enabled') %}
    {{ CORE.callHook('map:load-assets') }}
    {% endif %}
    <header>
        <i class="fa-solid fa-pen-to-square"></i>
        Édition de la fiche : {{ poi.name }}
    </header>

    {% if canEdit %}
        <form method="post" action="{{ ROUTER.generate("poi-edit-submit") }}" enctype="multipart/form-data" novalidate>
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
                .mt-2 { margin-top: 0.5rem; }
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

                {% if isAdmin or permission == "edit" %}
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
                    <label for="subcategory_select">Spécialité</label>
                    <select id="subcategory_select" name="subcategory">
                        <option value="">-- Sélectionner une spécialité --</option>
                    </select>
                    <div id="custom_subcategory_wrapper" style="display:none; margin-top:8px;">
                        <input type="text" id="custom_subcategory" name="subcategory_custom" placeholder="Saisissez la spécialité…" style="width:100%;">
                        <small style="color:#888;">Votre proposition sera prise en compte directement.</small>
                    </div>
                    <small style="color: #666; font-style: italic;">Sélectionnez d'abord une famille pour voir les spécialités disponibles</small>
                </div>

                <div class="field">
                    <label for="brand">Marque</label>
                    <input type="text" id="brand" name="brand" {% if poi.brand %}value="{{ poi.brand }}"{% endif %}>
                </div>
                {% endif %}
            </div>

            <div class="form-section">
                            <h3>Adresse</h3>
                
                <div class="field">
                    <label for="street">Rue</label>
                                <input type="text" id="street" name="street" {% if poi.addr_street %}value="{{ poi.addr_street }}"{% endif %}>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="housenumber">Numéro</label>
                                    <input type="text" id="housenumber" name="housenumber" {% if poi.addr_housenumber %}value="{{ poi.addr_housenumber }}"{% endif %}>
                    </div>

                    <div class="field">
                        <label for="postcode">Code postal</label>
                                    <input type="text" id="postcode" name="postcode" {% if poi.addr_postcode %}value="{{ poi.addr_postcode }}"{% endif %}>
                    </div>

                    <div class="field">
                        <label for="city">Ville</label>
                                    <input type="text" id="city" name="city" {% if poi.addr_city %}value="{{ poi.addr_city }}"{% endif %}>
                    </div>
                </div>
            </div>

            {% if isAdmin or permission == "edit" %}
            <div class="form-section">
                            <h3>Contact</h3>
                
                <div class="field">
                    <label for="phone">Téléphone</label>
                    <input type="text" id="phone" name="phone" {% if poi.contact_phone %}value="{{ poi.contact_phone }}"{% endif %} {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}disabled title="Atteignez le niveau 2 pour débloquer ce champ"{% endif %}>
                    {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}
                    <small style="color: #f4511e;"><i class="fa-solid fa-lock"></i> Niveau 2 requis</small>
                    {% endif %}
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="text" id="email" name="email" {% if poi.contact_email %}value="{{ poi.contact_email }}"{% endif %} {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}disabled title="Atteignez le niveau 2 pour débloquer ce champ"{% endif %}>
                    {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}
                    <small style="color: #f4511e;"><i class="fa-solid fa-lock"></i> Niveau 2 requis</small>
                    {% endif %}
                </div>

                <div class="field">
                    <label for="website">Site web</label>
                    <input type="url" id="website" name="website" {% if poi.website %}value="{{ poi.website }}"{% endif %} {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}disabled title="Atteignez le niveau 2 pour débloquer ce champ"{% endif %}>
                    {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}
                    <small style="color: #f4511e;"><i class="fa-solid fa-lock"></i> Niveau 2 requis</small>
                    {% endif %}
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
                                        <select name="hours[{{ loop.index0 }}][day_of_week]" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}disabled title="Atteignez le niveau 2 pour débloquer ce champ"{% endif %}>
                                            <option value="0" {% if hour.day_of_week == 0 %}selected{% endif %}>Lundi</option>
                                            <option value="1" {% if hour.day_of_week == 1 %}selected{% endif %}>Mardi</option>
                                            <option value="2" {% if hour.day_of_week == 2 %}selected{% endif %}>Mercredi</option>
                                            <option value="3" {% if hour.day_of_week == 3 %}selected{% endif %}>Jeudi</option>
                                            <option value="4" {% if hour.day_of_week == 4 %}selected{% endif %}>Vendredi</option>
                                            <option value="5" {% if hour.day_of_week == 5 %}selected{% endif %}>Samedi</option>
                                            <option value="6" {% if hour.day_of_week == 6 %}selected{% endif %}>Dimanche</option>
                                        </select>
                                        <select name="hours[{{ loop.index0 }}][open_time]" class="time-select" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}disabled title="Atteignez le niveau 2 pour débloquer ce champ"{% endif %}>
                                            <option value="">--:--</option>
                                            {% for h in 0..23 %}
                                                {% for m in [0, 15, 30, 45] %}
                                                    {% set t = "%02d:%02d"|format(h, m) %}
                                                    <option value="{{ t }}" {% if hour.open_time|slice(0, 5) == t %}selected{% endif %}>{{ t }}</option>
                                                {% endfor %}
                                            {% endfor %}
                                        </select>
                                        <select name="hours[{{ loop.index0 }}][close_time]" class="time-select" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}disabled title="Atteignez le niveau 2 pour débloquer ce champ"{% endif %}>
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
                                            <input type="checkbox" name="hours[{{ loop.index0 }}][is_closed]" value="1" {% if hour.is_closed %}checked{% endif %} {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}disabled title="Atteignez le niveau 2 pour débloquer ce champ"{% endif %}>
                                        </div>
                                        <button type="button" class="button small alert remove-hour-row" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}disabled title="Atteignez le niveau 2 pour débloquer ce champ"{% endif %}><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                {% endfor %}
                            {% endif %}
                        </div>
                    </div>
                    {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}
                    <small style="color: #f4511e;"><i class="fa-solid fa-lock"></i> Niveau 2 requis</small>
                    {% endif %}
                    <button type="button" id="add-hour-row" class="button small mt-2" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 2 %}disabled title="Atteignez le niveau 2 pour débloquer ce champ"{% endif %}>+ Ajouter une plage horaire</button>
                    <input type="hidden" name="opening_hours" value="{{ poi.opening_hours }}">
                </div>
                        </div>
                        {% endif %}
                </div>

                    {% if isAdmin or permission == "edit" %}
                    <div class="tab" id="tab-services">
                        <div class="form-section">
                            <h3>Accessibilité PMR</h3>
                <div class="field">
                    <label for="wheelchair">Accessibilité PMR</label>
                    <select id="wheelchair" name="wheelchair" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}disabled title="Atteignez le niveau 3 pour débloquer ce champ"{% endif %}>
                        <option value="">Non renseigné</option>
                        <option value="yes" {% if poi.wheelchair == "yes" %}selected{% endif %}>Oui</option>
                        <option value="limited" {% if poi.wheelchair == "limited" %}selected{% endif %}>Partiel</option>
                        <option value="no" {% if poi.wheelchair == "no" %}selected{% endif %}>Non</option>
                    </select>
                    {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}
                    <small style="color: #f4511e;"><i class="fa-solid fa-lock"></i> Niveau 3 requis</small>
                    {% endif %}
                </div>
            </div>

            <div class="form-section">
                            <h3>Équipements et services</h3>
                
                <div class="field">
                    <label for="internet_access">Accès Internet / WiFi</label>
                    <select id="internet_access" name="internet_access" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}disabled title="Atteignez le niveau 3 pour débloquer ce champ"{% endif %}>
                        <option value="">Non renseigné</option>
                        <option value="yes" {% if poi.internet_access == "yes" %}selected{% endif %}>Oui</option>
                        <option value="no" {% if poi.internet_access == "no" %}selected{% endif %}>Non</option>
                        <option value="wlan" {% if poi.internet_access == "wlan" %}selected{% endif %}>WiFi</option>
                        <option value="wifi" {% if poi.internet_access == "wifi" %}selected{% endif %}>WiFi (alternatif)</option>
                    </select>
                    {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}
                    <small style="color: #f4511e;"><i class="fa-solid fa-lock"></i> Niveau 3 requis</small>
                    {% endif %}
                </div>

                <div class="field">
                    <label for="parking">Parking</label>
                    <select id="parking" name="parking" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}disabled title="Atteignez le niveau 3 pour débloquer ce champ"{% endif %}>
                        <option value="">Non renseigné</option>
                        <option value="yes" {% if poi.parking == "yes" %}selected{% endif %}>Oui</option>
                        <option value="no" {% if poi.parking == "no" %}selected{% endif %}>Non</option>
                        <option value="private" {% if poi.parking == "private" %}selected{% endif %}>Privé</option>
                        <option value="public" {% if poi.parking == "public" %}selected{% endif %}>Public</option>
                    </select>
                    {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}
                    <small style="color: #f4511e;"><i class="fa-solid fa-lock"></i> Niveau 3 requis</small>
                    {% endif %}
                </div>

                <div class="field">
                    <label for="fee">Tarif</label>
                    <select id="fee" name="fee" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}disabled title="Atteignez le niveau 3 pour débloquer ce champ"{% endif %}>
                        <option value="">Non renseigné</option>
                        <option value="yes" {% if poi.fee == "yes" %}selected{% endif %}>Payant</option>
                        <option value="no" {% if poi.fee == "no" %}selected{% endif %}>Gratuit</option>
                    </select>
                    {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}
                    <small style="color: #f4511e;"><i class="fa-solid fa-lock"></i> Niveau 3 requis</small>
                    {% endif %}
                </div>

                <div class="field">
                    <label for="indoor">Intérieur</label>
                    <select id="indoor" name="indoor" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}disabled title="Atteignez le niveau 3 pour débloquer ce champ"{% endif %}>
                        <option value="">Non renseigné</option>
                        <option value="yes" {% if poi.indoor == "yes" %}selected{% endif %}>Oui</option>
                        <option value="no" {% if poi.indoor == "no" %}selected{% endif %}>Non</option>
                    </select>
                    {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}
                    <small style="color: #f4511e;"><i class="fa-solid fa-lock"></i> Niveau 3 requis</small>
                    {% endif %}
                </div>

                <div class="field">
                    <label for="vending">Distributeur automatique</label>
                    <select id="vending" name="vending" {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}disabled title="Atteignez le niveau 3 pour débloquer ce champ"{% endif %}>
                        <option value="">Non renseigné</option>
                        <option value="yes" {% if poi.vending == "yes" %}selected{% endif %}>Oui</option>
                        <option value="no" {% if poi.vending == "no" %}selected{% endif %}>Non</option>
                    </select>
                    {% if not isAdmin and permission == "edit" and not hasSubscription and gamificationLevel < 3 %}
                    <small style="color: #f4511e;"><i class="fa-solid fa-lock"></i> Niveau 3 requis</small>
                    {% endif %}
                            </div>
                </div>
            </div>

                    {% if CORE.callHook('map:is-enabled') %}
                <!-- Onglet 3: Localisation -->
                <div class="tab" id="tab-location">
        <div class="form-section">
            <h3>Localisation</h3>
            <p class="help-text">Cliquez sur la carte pour définir la position du POI</p>
            
            <div class="field-row">
                <div class="field">
                    <label for="lat">Latitude</label>
                                <input type="number" id="lat" name="lat" step="0.000001" {% if poi.lat %}value="{{ poi.lat }}"{% endif %}>
                </div>

                <div class="field">
                    <label for="lon">Longitude</label>
                                <input type="number" id="lon" name="lon" step="0.000001" {% if poi.lon %}value="{{ poi.lon }}"{% endif %}>
                </div>
            </div>

            <div class="field" style="margin-top: 15px;">
                {{ CORE.callHook('map:render', {
                    'id': 'poi-map', 
                    'lat': poi.lat, 
                    'lon': poi.lon, 
                    'zoom': 13, 
                    'editable': true,
                    'onChange': 'updatePoiCoordinates'
                }) }}
            </div>
            </div>
        </div>
        {% endif %}

                    <!-- Onglet 4: Personnalisation -->
                    <div class="tab" id="tab-personalization">
            <div class="form-section">
                            <h3>Personnalisation</h3>
                
                <div class="field">
                    <label for="logo">Logo (URL)</label>
                    {% if logo %}
                                <input type="url" id="logo" name="logo" value="{{ logo }}">
                    {% else %}
                                <input type="url" id="logo" name="logo" value="">
                    {% endif %}
                    <small>URL vers l'image du logo</small>
                    {% if not hasSubscription %}
                    <div style="margin-top: 10px; padding: 12px; background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 6px; display: inline-block;">
                        <strong style="font-size: 18px; color: #495057;">Changements de logo : <span style="color: {% if logoChangesThisWeek >= 2 %}#dc3545{% else %}#28a745{% endif %}; font-size: 24px; font-weight: bold;">{{ logoChangesThisWeek }}/2</span></strong>
                        <div style="margin-top: 5px; font-size: 12px; color: #6c757d;">Limite hebdomadaire avec le plan gratuit. Les abonnements arriveront bientôt.</div>
                    </div>
                    {% endif %}
                </div>

                <div class="field">
                    <label>Couleur de fond</label>
                    {% if hasSubscription %}
                        <div class="color-picker-container d-flex flex-wrap gap-10 mt-2">
                            <label class="color-option">
                                <input type="radio" name="background_color" value="" {% if not backgroundColor %}checked{% endif %} class="mr-2">
                                <span class="color-preview bg-light"></span>
                                <span>Par défaut (style)</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="background_color" value="#ffffff" {% if backgroundColor == "#ffffff" %}checked{% endif %} class="mr-2">
                                <span class="color-preview bg-white"></span>
                                <span>Blanc</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="background_color" value="#f0f0f0" {% if backgroundColor == "#f0f0f0" %}checked{% endif %} class="mr-2">
                                <span class="color-preview" style="background: #f0f0f0;"></span>
                                <span>Gris clair</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="background_color" value="#fff8e1" {% if backgroundColor == "#fff8e1" %}checked{% endif %} class="mr-2">
                                <span class="color-preview" style="background: #fff8e1;"></span>
                                <span>Jaune pâle</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="background_color" value="#e3f2fd" {% if backgroundColor == "#e3f2fd" %}checked{% endif %} class="mr-2">
                                <span class="color-preview" style="background: #e3f2fd;"></span>
                                <span>Bleu pâle</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="background_color" value="#f3e5f5" {% if backgroundColor == "#f3e5f5" %}checked{% endif %} class="mr-2">
                                <span class="color-preview" style="background: #f3e5f5;"></span>
                                <span>Violet pâle</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="background_color" value="#e8f5e9" {% if backgroundColor == "#e8f5e9" %}checked{% endif %} class="mr-2">
                                <span class="color-preview" style="background: #e8f5e9;"></span>
                                <span>Vert pâle</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="background_color" value="#fff3e0" {% if backgroundColor == "#fff3e0" %}checked{% endif %} class="mr-2">
                                <span class="color-preview" style="background: #fff3e0;"></span>
                                <span>Orange pâle</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="background_color" value="#fce4ec" {% if backgroundColor == "#fce4ec" %}checked{% endif %} class="mr-2">
                                <span class="color-preview" style="background: #fce4ec;"></span>
                                <span>Rose pâle</span>
                            </label>
                            <label class="color-option">
                                <input type="radio" name="background_color" value="#e0f2f1" {% if backgroundColor == "#e0f2f1" %}checked{% endif %} class="mr-2">
                                <span class="color-preview" style="background: #e0f2f1;"></span>
                                <span>Turquoise pâle</span>
                            </label>
                        </div>
                        <small class="d-block mt-2 text-muted">Sélectionnez une couleur ou laissez "Par défaut" pour utiliser la couleur du style</small>
                    {% else %}
                        <div class="color-picker-container d-flex flex-wrap gap-10 mt-2 opacity-50 pointer-events-none">
                            <label class="color-option cursor-not-allowed">
                                <input type="radio" name="background_color" value="" disabled class="mr-2">
                                <span class="color-preview bg-light"></span>
                                <span>Par défaut (style)</span>
                            </label>
                        </div>
                        <small class="d-block mt-2 text-danger">Non disponible avec le plan gratuit. Les abonnements arriveront bientôt.</small>
                    {% endif %}
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
            </div>

                    <!-- Onglet 5: Contenu -->
                    <div class="tab" id="tab-content">
            <div class="form-section">
                            <h3>Réseaux sociaux et liens</h3>
                {% if not hasSubscription %}
                {% if linksCount >= 1 %}
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                    <strong>Limite atteinte :</strong> Vous avez atteint la limite d'1 lien de réseau social avec le plan gratuit. Les abonnements arriveront bientôt pour lever cette limitation.
                </div>
                {% endif %}
                {% endif %}
                <div id="links-container">
                    {% if links %}
                        {% for link in links %}
                        <div class="link-item" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <input type="hidden" name="links[{{ loop.index0 }}][id]" value="{{ link.id }}">
                            <div class="field">
                                <label>URL</label>
                                            <input type="url" name="links[{{ loop.index0 }}][url]" value="{{ link.url }}">
                            </div>
                            <div class="field">
                                <label>Label (optionnel)</label>
                                            <input type="text" name="links[{{ loop.index0 }}][label]" value="{{ link.label }}">
                            </div>
                            <button type="button" class="button small alert remove-link" style="margin-top: 5px;">Supprimer</button>
                        </div>
                        {% endfor %}
                    {% endif %}
                </div>
                {% if hasSubscription or linksCount < 1 %}
                <button type="button" id="add-link-btn" class="button small">+ Ajouter un lien</button>
                {% else %}
                <button type="button" id="add-link-btn" class="button small" disabled title="Limite atteinte avec le plan gratuit">+ Ajouter un lien</button>
                {% endif %}
                {% if not hasSubscription %}
                <div style="margin-top: 10px; padding: 12px; background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 6px; display: inline-block;">
                    <strong style="font-size: 18px; color: #495057;">Liens : <span style="color: {% if linksCount >= 1 %}#dc3545{% else %}#28a745{% endif %}; font-size: 24px; font-weight: bold;">{{ linksCount }}/1</span></strong>
                    <div style="margin-top: 5px; font-size: 12px; color: #6c757d;">Limite avec le plan gratuit. Les abonnements arriveront bientôt.</div>
                </div>
                {% endif %}
            </div>

            <div class="form-section">
                            <h3>Photos</h3>
                {% if not hasSubscription %}
                {% if photosCount >= 4 %}
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                    <strong>Limite atteinte :</strong> Vous avez atteint la limite de 4 photos avec le plan gratuit. Les abonnements arriveront bientôt pour lever cette limitation.
                </div>
                {% endif %}
                {% endif %}
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
                {% if hasSubscription or photosCount < 4 %}
                <div class="field">
                    <label for="photo">Ajouter une photo</label>
                                <input type="file" id="photo" accept="image/*">
                                <input type="text" id="photo_caption" placeholder="Légende (optionnel)">
                                <div id="photo-upload-status" style="margin-top: 5px; font-size: 0.9em; display: none;"></div>
                </div>
                {% else %}
                <div class="field">
                    <input type="file" id="photo" name="photo" accept="image/*" disabled>
                    <small style="color: #f4511e;">Limite de 4 photos atteinte avec le plan gratuit</small>
                </div>
                {% endif %}
                {% if not hasSubscription %}
                <div style="margin-top: 10px; padding: 12px; background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 6px; display: inline-block;">
                    <div style="margin-bottom: 8px;">
                        <strong style="font-size: 18px; color: #495057;">Photos : <span style="color: {% if photosCount >= 4 %}#dc3545{% else %}#28a745{% endif %}; font-size: 24px; font-weight: bold;">{{ photosCount }}/4</span></strong>
                    </div>
                    <div>
                        <strong style="font-size: 18px; color: #495057;">Uploads/semaine : <span style="color: {% if photoUploadsThisWeek >= 6 %}#dc3545{% else %}#28a745{% endif %}; font-size: 24px; font-weight: bold;">{{ photoUploadsThisWeek }}/6</span></strong>
                    </div>
                    <div style="margin-top: 5px; font-size: 12px; color: #6c757d;">Limites avec le plan gratuit. Les abonnements arriveront bientôt.</div>
                </div>
                {% endif %}
            </div>

            <div class="form-section">
                            <h3>Tags</h3>
                            <p><small>Saisissez des tags séparés par des virgules pour faciliter la recherche (ex: terrasse, wifi, bio).</small></p>
                            <div class="field">
                                <input type="text" id="tags" name="tags" value="{{ poiTags }}" placeholder="ex: tag1, tag2, spécialité">
                            </div>
            </div>

            <div class="form-section">
                            <h3>Description</h3>
                <div class="field">
                    <label for="description">Description détaillée</label>
                                <textarea id="description" name="description" rows="6" class="editor">{% if description %}{{ description }}{% endif %}</textarea>
                    {% if hasSubscription %}
                    <small>Vous pouvez ajouter une description détaillée de votre établissement.</small>
                    {% else %}
                    <div style="margin-top: 10px; padding: 12px; background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 6px; display: inline-block;">
                        <strong style="font-size: 18px; color: #495057;">Mots : <span id="description-count" style="color: {% if descriptionLength >= 150 %}#dc3545{% else %}#28a745{% endif %}; font-size: 24px; font-weight: bold;">0</span>/150</strong>
                        <div style="margin-top: 5px; font-size: 12px; color: #6c757d;">Limite avec le plan gratuit. Les abonnements arriveront bientôt.</div>
                    </div>
                    {% endif %}
                            </div>
                </div>
            </div>
            {% endif %}
                </div>
            </div>

            <div class="form-actions" style="margin-top: 30px;">
                <button type="submit" class="button success">Enregistrer les modifications</button>
                <a href="{{ poiDetailUrl }}" class="button">Annuler</a>
            </div>
        </form>
    {% elseif canSuggest %}
        <div class="suggest-form">
            <p>En tant que membre, vous pouvez proposer des corrections pour cette fiche. Vous pouvez modifier uniquement l'adresse. Vos suggestions seront examinées par un administrateur.</p>
            
            <form method="post" action="{{ ROUTER.generate("poi-edit-suggest") }}">
                <input type="hidden" name="poi_id" value="{{ poi.id }}">

                <div class="form-section">
                    <h2>Adresse</h2>
                    
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
                <h2>Position géographique</h2>
                <p class="help-text">Emplacement actuel du lieu (non modifiable)</p>
                <div class="field" style="margin-top: 15px;">
                    <div id="poi-map-view" style="height: 400px; width: 100%; border: 1px solid #ddd; border-radius: 4px;"></div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button success">Proposer les corrections</button>
                    <a href="{{ poiDetailUrl }}" class="button">Annuler</a>
            </div>
        </form>
    </div>
    {% endif %}
</section>




<script>
(function() {
    const specialtiesByFamily = {{ specialtiesByFamilyJson|raw }};
    const currentSubcategory = '{{ selectedSubcategories|first|default("") }}';

    const familySelect = document.getElementById('family_select');
    const subcategorySelect = document.getElementById('subcategory_select');
    const customSubcategoryWrapper = document.getElementById('custom_subcategory_wrapper');
    const customSubcategoryInput = document.getElementById('custom_subcategory');

    function loadSpecialties(familyKey, selectedValue) {
        subcategorySelect.innerHTML = '<option value="">-- Sélectionner une spécialité --</option>';
        const specs = specialtiesByFamily[familyKey] || [];
        let found = false;
        specs.forEach(function(spec) {
            const opt = document.createElement('option');
            opt.value = spec.key;
            opt.textContent = spec.label;
            if (selectedValue && spec.key === selectedValue) { opt.selected = true; found = true; }
            subcategorySelect.appendChild(opt);
        });
        const otherOpt = document.createElement('option');
        otherOpt.value = '__custom__';
        otherOpt.textContent = '-- Autre (précisez) --';
        if (selectedValue && !found && selectedValue !== '') otherOpt.selected = true;
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
        if (familySelect.value) { loadSpecialties(familySelect.value, currentSubcategory); }
        familySelect.addEventListener('change', function() { loadSpecialties(this.value, ''); });
    }
    if (subcategorySelect) {
        subcategorySelect.addEventListener('change', toggleCustomField);
    }
})();
</script>

{% if CORE.callHook('map:is-enabled') %}
<script>
// Variables globales pour la carte
// Callback pour UniversalMap
function updatePoiCoordinates(lat, lon) {
    const latInput = document.getElementById('lat');
    const lonInput = document.getElementById('lon');
    if (latInput && lonInput) {
        latInput.value = parseFloat(lat).toFixed(6);
        lonInput.value = parseFloat(lon).toFixed(6);
    }
}

// Initialisation via le hook map:render (automatique)
// Plus besoin de initializeMap() manuel

</script>
{% endif %}

<script>
// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    const tabHeaders = document.querySelectorAll('.tabs-header > li');
    const tabs = document.querySelectorAll('.tab');
    
    // S'assurer que seul le premier onglet est actif au chargement
    if (tabHeaders.length > 0 && tabs.length > 0) {
        // Cacher tous les onglets
        tabs.forEach(t => t.classList.remove('active'));
        // Afficher uniquement le premier onglet qui a la classe "active" dans le HTML
        const activeTab = document.querySelector('.tab.active');
        if (!activeTab && tabs.length > 0) {
            // Si aucun onglet n'est actif, activer le premier
            tabs[0].classList.add('active');
            if (tabHeaders[0]) {
                tabHeaders[0].classList.add('active');
            }
        }
        
        {% if CORE.callHook('map:is-enabled') %}
        // Si l'onglet "location" est actif au chargement, initialiser la carte
        const locationTab = document.getElementById('tab-location');
        if (locationTab && locationTab.classList.contains('active')) {
            setTimeout(function() {
                initializeMap();
            }, 200);
        }
        {% endif %}
    }
    
    tabHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Retirer la classe active de tous les headers et tabs
            tabHeaders.forEach(h => h.classList.remove('active'));
            tabs.forEach(t => t.classList.remove('active'));
            
            // Ajouter la classe active au header et tab sélectionnés
            this.classList.add('active');
            const targetTabElement = document.getElementById('tab-' + targetTab);
            if (targetTabElement) {
                targetTabElement.classList.add('active');
                
                {% if CORE.callHook('map:is-enabled') %}
                // Si c'est l'onglet "location", initialiser la carte
                if (targetTab === 'location') {
                    // Attendre un peu que l'onglet soit visible avant d'initialiser
                    setTimeout(function() {
                        initializeMap();
                    }, 100);
                }
                {% endif %}
            }
        });
    });

    // Gestion des horaires structurés
    const hoursContainer = document.getElementById('opening-hours-rows');
    const addHourBtn = document.getElementById('add-hour-row');
    let hourIndex = {{ poiOpeningHours|length|default(0) }};

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
    }

    // Gestion des liens avec limitation pour plan free
    var linkIndex = {% if linksCount %}{{ linksCount }}{% else %}0{% endif %};
    const addLinkBtn = document.getElementById('add-link-btn');
    if (addLinkBtn) {
        addLinkBtn.addEventListener('click', function() {
            const container = document.getElementById('links-container');
            if (!container) return;
            
            const currentLinks = container.querySelectorAll('.link-item').length;
            
            {% if not hasSubscription %}
            if (currentLinks >= 1) {
                alert('Vous ne pouvez ajouter qu\'un seul lien de réseau social avec le plan gratuit. Les abonnements arriveront bientôt pour lever cette limitation.');
                return;
            }
            {% endif %}
            
            const div = document.createElement('div');
            div.className = 'link-item';
            div.style.cssText = 'margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;';
            div.innerHTML = `
                <div class="field">
                    <label>URL</label>
                    <input type="url" name="links[${linkIndex}][url]" value="" placeholder="https://...">
                </div>
                <div class="field">
                    <label>Label (optionnel)</label>
                    <input type="text" name="links[${linkIndex}][label]" value="" placeholder="Facebook, Instagram, etc.">
                </div>
                <button type="button" class="button small alert remove-link" style="margin-top: 5px;">Supprimer</button>
            `;
            container.appendChild(div);
            linkIndex++;
            
            div.querySelector('.remove-link').addEventListener('click', function() {
                div.remove();
            });
        });
    }

    // Supprimer les liens existants
    document.querySelectorAll('.remove-link').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.link-item').remove();
        });
    });

    // Upload automatique des photos
    const photoInput = document.getElementById('photo');
    const photoCaptionInput = document.getElementById('photo_caption');
    const photoUploadStatus = document.getElementById('photo-upload-status');
    const poiId = document.querySelector('input[name="poi_id"]')?.value;
    
    if (photoInput && poiId) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Vérifier le type de fichier
            if (!file.type.match('image.*')) {
                if (photoUploadStatus) {
                    photoUploadStatus.textContent = 'Erreur : Veuillez sélectionner une image';
                    photoUploadStatus.style.color = '#d32f2f';
                    photoUploadStatus.style.display = 'block';
                }
                return;
            }
            
            // Afficher le statut de chargement
            if (photoUploadStatus) {
                photoUploadStatus.textContent = 'Upload en cours...';
                photoUploadStatus.style.color = '#1976d2';
                photoUploadStatus.style.display = 'block';
            }
            
            // Préparer FormData
            const formData = new FormData();
            formData.append('photo', file);
            formData.append('poi_id', poiId);
            if (photoCaptionInput && photoCaptionInput.value.trim()) {
                formData.append('caption', photoCaptionInput.value.trim());
            }
            
            // Upload AJAX
            fetch('{{ ROUTER.generate("poi-edit-upload-photo") }}', {
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
                    if (photoUploadStatus) {
                        photoUploadStatus.textContent = '✓ Photo uploadée avec succès';
                        photoUploadStatus.style.color = '#388e3c';
                    }
                    
                    // Ajouter la photo à la galerie
                    const container = document.getElementById('photos-container');
                    if (container) {
                        // Supprimer le message "Aucune photo" s'il existe
                        const emptyMsg = container.querySelector('p');
                        if (emptyMsg && emptyMsg.textContent.includes('Aucune photo')) {
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
                    if (photoUploadStatus) {
                        setTimeout(() => {
                            photoUploadStatus.style.display = 'none';
                        }, 3000);
                    }
                } else {
                    if (photoUploadStatus) {
                        photoUploadStatus.textContent = 'Erreur : ' + (data.message || 'Échec de l\'upload');
                        photoUploadStatus.style.color = '#d32f2f';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (photoUploadStatus) {
                    photoUploadStatus.textContent = 'Erreur : Échec de l\'upload';
                    photoUploadStatus.style.color = '#d32f2f';
                }
        });
    });
    }
    
    // Gestion de la galerie de photos (suppression et renommage)
    // Définir poiId ici pour qu'il soit accessible dans les event listeners
    const poiIdForPhotos = document.querySelector('input[name="poi_id"]')?.value;
    if (!poiIdForPhotos) {
        console.warn('POI ID non trouvé pour la gestion des photos');
    }
    
    // Supprimer une photo
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('delete-photo')) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!poiIdForPhotos) {
                alert('Erreur : POI ID manquant');
                return;
            }
            
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette photo ?')) {
                return;
            }

            const photoId = e.target.getAttribute('data-photo-id');
            const photoItem = e.target.closest('.photo-gallery-item');
            
            if (!photoId) {
                alert('Erreur : ID de photo manquant');
                return;
            }
                            
                            fetch('{{ ROUTER.generate("poi-edit-delete-photo", ["photoId" => 0]) }}'.replace('/0', '/' + photoId), {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                body: 'poi_id=' + poiIdForPhotos
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
                                    photoItem.remove();
                    // Si plus de photos, afficher un message
                    const container = document.getElementById('photos-container');
                    if (container && container.querySelectorAll('.photo-gallery-item').length === 0) {
                        container.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #999; font-style: italic;">Aucune photo pour le moment</p>';
                    }
                                    {% if not hasSubscription %}
                                    // Réactiver l'upload si on était à la limite
                    const photoInput = document.getElementById('photo');
                                    if (photoInput) photoInput.disabled = false;
                                    {% endif %}
                                } else {
                    alert('Erreur : ' + (data.message || 'Erreur lors de la suppression'));
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Erreur lors de la suppression de la photo');
            });
        }
        
        // Mettre à jour la légende d'une photo
        if (e.target && e.target.classList.contains('update-photo-caption')) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!poiIdForPhotos) {
                alert('Erreur : POI ID manquant');
                return;
            }
            
            const photoId = e.target.getAttribute('data-photo-id');
            const photoItem = e.target.closest('.photo-gallery-item');
            const captionInput = photoItem.querySelector('.photo-caption-input');
            const caption = captionInput.value.trim();
            
            fetch('{{ ROUTER.generate("poi-edit-update-photo-caption") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'photo_id=' + photoId + '&poi_id=' + poiIdForPhotos + '&caption=' + encodeURIComponent(caption)
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
                    alert('Erreur : ' + (data.message || 'Erreur lors de la mise à jour'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors de la mise à jour de la légende');
            });
        }
    });

{% if not hasSubscription %}
document.addEventListener('DOMContentLoaded', function() {
const descriptionTextarea = document.getElementById('description');
const descriptionCount = document.getElementById('description-count');
if (descriptionTextarea && descriptionCount) {
    function updateDescriptionCount() {
        const text = descriptionTextarea.value.trim();
        const words = text ? text.split(/\s+/).length : 0;
        descriptionCount.textContent = words;
        if (words >= 150) {
            descriptionCount.style.color = '#dc3545';
        } else {
            descriptionCount.style.color = '#28a745';
        }
    }
    descriptionTextarea.addEventListener('input', updateDescriptionCount);
    updateDescriptionCount(); // Initial count
}
});
{% endif %}

// Update categories when type changes
function updateCategories() {
    const categorySelect = document.getElementById('category_id');
    if (!categorySelect) return;
    
    const subcategoriesContainer = document.getElementById('subcategories-container');
    if (!subcategoriesContainer) return;
    
    const selectedCategorySlug = categorySelect.options[categorySelect.selectedIndex]?.getAttribute('data-slug') || '';
    const categorySlugInput = document.getElementById('category_slug');
    if (categorySlugInput) {
        categorySlugInput.value = selectedCategorySlug || '';
    }
    subcategoriesContainer.innerHTML = '';
    
    if (!selectedCategorySlug) {
        subcategoriesContainer.innerHTML = '<p style="grid-column: 1 / -1; color: #666; font-style: italic; margin: 0;">Sélectionnez d\'abord une catégorie ci-dessus pour voir les sous-catégories disponibles</p>';
        return;
    }

    // Charger les données de catégories si pas encore fait
    if (!typesCategories) {
        try {
            var dataElement = document.getElementById('types-categories-data');
            if (dataElement) {
                typesCategories = JSON.parse(dataElement.textContent);
            }
        } catch(e) {
            console.error('Failed to parse typesCategories', e);
        }
    }

    const currentSubcategories = {% if selectedSubcategoriesJson %}{{ selectedSubcategoriesJson }}{% else %}[]{% endif %};
    
    if (typesCategories && typesCategories.categories && typesCategories.categories[selectedCategorySlug]) {
        const subcategories = typesCategories.categories[selectedCategorySlug];
        if (subcategories && typeof subcategories === 'object') {
            const subcatKeys = Object.keys(subcategories);
            if (subcatKeys.length === 0) {
                subcategoriesContainer.innerHTML = '<p style="grid-column: 1 / -1; color: #666; font-style: italic; margin: 0;">Aucune sous-catégorie disponible pour cette catégorie</p>';
                return;
            }
            
            subcatKeys.forEach(function(subcatKey) {
                const subcatLabel = subcategories[subcatKey];
                const label = document.createElement('label');
                label.className = 'subcategory-option';
                label.style.cssText = 'display: flex; align-items: center; cursor: pointer; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #fff;';
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = subcatKey;
                checkbox.name = 'subcategories[]';
                checkbox.id = 'subcategory-' + subcatKey;
                checkbox.style.marginRight = '8px';
                
                if (currentSubcategories && Array.isArray(currentSubcategories) && currentSubcategories.indexOf(subcatKey) !== -1) {
                    checkbox.checked = true;
                }
                
                const span = document.createElement('span');
                span.textContent = subcatLabel;
                
                label.appendChild(checkbox);
                label.appendChild(span);
                subcategoriesContainer.appendChild(label);
            });
        }
    } else {
        subcategoriesContainer.innerHTML = '<p style="grid-column: 1 / -1; color: #666; font-style: italic; margin: 0;">Sélectionnez d\'abord une catégorie ci-dessus pour voir les sous-catégories disponibles</p>';
    }
}

// Attach event listener for category selection
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    if (categorySelect) {
        categorySelect.addEventListener('change', updateCategories);
        // Initial call to populate subcategories if a category is already selected
        updateCategories();
    }
});


// Limitation des tags personnalisés pour plan free
{% if not hasSubscription %}
const customTagCheckboxes = document.querySelectorAll('#custom-tags-container input[type="checkbox"]');
if (customTagCheckboxes.length > 0) {
    customTagCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('#custom-tags-container input[type="checkbox"]:checked').length;
            if (checkedCount > 3) {
                alert('Vous ne pouvez sélectionner que 3 tags personnalisés maximum avec le plan gratuit. Les abonnements arriveront bientôt pour lever cette limitation.');
                this.checked = false;
            }
            });
        });
}
{% endif %}

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

// Gestion des couleurs - Mise en évidence de la sélection
const colorOptions = document.querySelectorAll('.color-option');
colorOptions.forEach(option => {
    const radio = option.querySelector('input[type="radio"]');
    if (radio && radio.checked && !radio.disabled) {
        option.style.borderColor = 'var(--primary-color, #00acc1)';
        option.style.backgroundColor = 'rgba(0, 172, 193, 0.05)';
    }
    
    radio.addEventListener('change', function() {
        if (!this.disabled) {
            colorOptions.forEach(opt => {
                const optRadio = opt.querySelector('input[type="radio"]');
                if (!optRadio || !optRadio.disabled) {
                    opt.style.borderColor = '#ddd';
                    opt.style.backgroundColor = '#fff';
                }
            });
            if (this.checked) {
                option.style.borderColor = 'var(--primary-color, #00acc1)';
                option.style.backgroundColor = 'rgba(0, 172, 193, 0.05)';
            }
        }
    });
});

// Gestion des horaires structurés
document.addEventListener('DOMContentLoaded', function() {
    const hoursContainer = document.getElementById('opening-hours-rows');
    const addHourBtn = document.getElementById('add-hour-row');
    let hourIndex = {{ poiOpeningHours|length|default(0) }};

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
    }
});
</script>
{% if CORE.callHook('map:is-enabled') and canSuggest %}
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialize locked map for non-owners (view only)
    if (typeof L === 'undefined') {
        console.error('Leaflet (L) is not defined. Map cannot be initialized.');
        return;
    }

    let viewMap, viewMarker;
    const poiLat = parseFloat({{ poi.lat }}) || 47.0810;
    const poiLon = parseFloat({{ poi.lon }}) || 2.3988;

    viewMap = L.map('poi-map-view', {
        zoomControl: true,
        dragging: false,
        touchZoom: false,
        doubleClickZoom: false,
        scrollWheelZoom: false,
        boxZoom: false,
        keyboard: false
    }).setView([poiLat, poiLon], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(viewMap);

    // Use custom icon from theme if available
    let customIcon;
    try {
        customIcon = L.icon({
            iconUrl: '/theme/Lumiere/leaflet-marker.png',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });
    } catch (e) {
        customIcon = null;
    }

    // Add marker (non-draggable)
    viewMarker = L.marker([poiLat, poiLon], {
        draggable: false,
        icon: customIcon
    }).addTo(viewMap);

    // Add popup with POI name
    var poiName = {% if poiNameJson %}{{ poiNameJson }}{% else %}''{% endif %};
    var poiAddress = {% if poiAddressJson %}{{ poiAddressJson }}{% else %}''{% endif %};
    viewMarker.bindPopup('<strong>' + poiName + '</strong><br>' + poiAddress).openPopup();
});
</script>
{% endif %}
