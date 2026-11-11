<section class="module-card">
	<header>
		<i class="fa-solid fa-{% if poi.id %}pen-to-square{% else %}plus{% endif %}"></i>
		{% if poi.id %}Éditer : <strong>{{ poi.name }}</strong>{% else %}Nouveau POI{% endif %}
	</header>

	<form method="post" action="{% if poi.id %}{{ ROUTER.generate('admin-poimanager-save') }}{% else %}{{ ROUTER.generate('admin-poimanager-create-save') }}{% endif %}" enctype="multipart/form-data" novalidate>
		<input type="hidden" name="_csrf" value="{{ _csrfToken }}">
		{% if poi.id %}<input type="hidden" name="poi_id" value="{{ poi.id }}">{% endif %}

		<div class="tabs-container">
			<ul class="tabs-header">
				<li class="active" data-tab="general"><i class="fa-solid fa-info-circle"></i> Général</li>
				<li data-tab="location"><i class="fa-solid fa-map-pin"></i> Localisation</li>
				<li data-tab="contact"><i class="fa-solid fa-phone"></i> Contact</li>
				<li data-tab="media"><i class="fa-solid fa-images"></i> Médias{% if photos %} ({{ photos|length }}){% endif %}</li>
				<li data-tab="links"><i class="fa-solid fa-link"></i> Liens</li>
				<li data-tab="description"><i class="fa-solid fa-align-left"></i> Description</li>
				<li data-tab="tags"><i class="fa-solid fa-tags"></i> Tags</li>
			</ul>

			<div class="tabs">

				<!-- =================== GÉNÉRAL =================== -->
				<div class="tab active" id="tab-general">
					<div class="form-section">
						<h3>Identité</h3>
						<div class="field-row">
							<div class="field">
								<label for="poi-name">Nom <span class="required">*</span></label>
								<input type="text" id="poi-name" name="name" value="{{ poi.name }}" required>
							</div>
							<div class="field">
								<label for="poi-brand">Marque / Enseigne</label>
								<input type="text" id="poi-brand" name="brand" value="{{ poi.brand_name|default(poi.brand) }}">
							</div>
							<div class="field">
							<label for="poi-desc-list">Micro-description (Liste) <small>(255 caractères max)</small></label>
							<input type="text" id="poi-desc-list" name="desc_list" value="{{ poi.desc_list }}" maxlength="255" placeholder="Brève phrase d'accroche pour les résultats de recherche...">
						</div>
					</div>
						<div class="field-row">
							<div class="field">
								<label for="family_select">Catégorie (Famille)</label>
								<select id="family_select" name="category">
									<option value="">-- Sélectionner une famille --</option>
									{% if familiesForSelect %}
										{% for fam in familiesForSelect %}
											<option value="{{ fam.slug }}" {% if poi.category == fam.slug %}selected{% endif %}>{{ fam.label }}</option>
										{% endfor %}
									{% endif %}
								</select>
							</div>
							<div class="field">
								<label>Sous-catégories (Spécialités)</label>
								<div id="subcategories_container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px; max-height: 300px; overflow-y: auto; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
									<!-- Les checkboxes seront générées par JS -->
								</div>
								<div id="custom_subcategory_wrapper" style="display:none; margin-top:8px;">
									<label for="custom_subcategory" style="font-size: 0.9em; font-weight: normal;">Spécialité personnalisée :</label>
									<input type="text" id="custom_subcategory" name="subcategory_custom" value="{{ poi.subcategory|default('') }}" placeholder="Saisissez la spécialité..." style="width:100%;">
								</div>
								<small id="subcat_help" style="color: #666; font-style: italic;">Sélectionnez d'abord une famille pour voir les spécialités</small>
							</div>
						</div>
					</div>
					<div class="form-section">
						<h3>Publication</h3>
						<div class="field-row">
							<div class="field">
								<label for="poi-status">Statut</label>
								<select id="poi-status" name="status">
									<option value="public" {% if poi.status == 'public' or not poi.status %}selected{% endif %}>Public</option>
									<option value="draft" {% if poi.status == 'draft' %}selected{% endif %}>Brouillon</option>
									<option value="private" {% if poi.status == 'private' %}selected{% endif %}>Privé</option>
								</select>
							</div>
							<div class="field">
								<label for="poi-datascore">Score données</label>
								<input type="number" id="poi-datascore" name="datascore" value="{{ poi.datascore|default(50) }}" readonly style="background-color: #f5f5f5; color: #777;">
								<small>Calculé automatiquement</small>
							</div>
						</div>
						<div class="field">
							<label>
								<input type="checkbox" name="is_closed" value="1" {% if poi.is_closed %}checked{% endif %} id="poi-is-closed">
								Ce POI est définitivement fermé
							</label>
						</div>
						<div class="field" id="closed-reason-field" {% if not poi.is_closed %}style="display:none"{% endif %}>
							<label for="poi-closed-reason">Raison de la fermeture</label>
							<input type="text" id="poi-closed-reason" name="closed_reason" value="{{ poi.closed_reason }}">
						</div>
					</div>
				</div>

				<!-- =================== LOCALISATION =================== -->
				<div class="tab" id="tab-location">
					<div class="form-section">
						<h3>Coordonnées GPS</h3>
						<div class="field-row">
							<div class="field">
								<label for="poi-lat">Latitude</label>
								<input type="text" id="poi-lat" name="lat" value="{{ poi.lat }}" placeholder="47.0000" readonly style="background-color: #f9f9f9; cursor: not-allowed;">
							</div>
							<div class="field">
								<label for="poi-lon">Longitude</label>
								<input type="text" id="poi-lon" name="lon" value="{{ poi.lon }}" placeholder="2.0000" readonly style="background-color: #f9f9f9; cursor: not-allowed;">
							</div>
						</div>
						<div id="poi-map" class="map-container"></div>
						<p><small>Cliquez sur la carte pour définir la position. Le marqueur est déplaçable.</small></p>
					</div>
					<div class="form-section">
						<h3>Adresse</h3>
						<div class="field-row">
							<div class="field">
								<label for="poi-housenumber">N° de rue</label>
								<input type="text" id="poi-housenumber" name="addr_housenumber" value="{{ poi.addr_housenumber }}">
							</div>
							<div class="field">
								<label for="poi-street">Rue</label>
								<input type="text" id="poi-street" name="addr_street" value="{{ poi.addr_street }}">
							</div>
						</div>
						<div class="field-row">
							<div class="field">
								<label for="poi-postcode">Code postal</label>
								<input type="text" id="poi-postcode" name="addr_postcode" value="{{ poi.addr_postcode }}">
							</div>
							<div class="field">
								<label for="poi-city">Ville</label>
								<input type="text" id="poi-city" name="addr_city" value="{{ poi.addr_city }}">
							</div>
						</div>
					</div>
				</div>

				<!-- =================== CONTACT =================== -->
				<div class="tab" id="tab-contact">
					<div class="form-section">
						<h3>Coordonnées</h3>
						<div class="field-row">
							<div class="field">
								<label for="poi-phone">Téléphone</label>
								<input type="text" id="poi-phone" name="contacts[phone]" value="{{ contacts.phone }}" placeholder="+33 2 48 ...">
							</div>
							<div class="field">
								<label for="poi-email">Email</label>
								<input type="email" id="poi-email" name="contacts[email]" value="{{ contacts.email }}">
							</div>
						</div>
						<div class="field">
							<label for="poi-website">Site web</label>
							<input type="url" id="poi-website" name="contacts[website]" value="{{ contacts.website }}" placeholder="https://...">
						</div>
						<div class="field">
							<label>Horaires d'ouverture précis</label>

				<style>
				/* === HOURS EDITOR — LumièreV2 theme === */
				.lv2-hours-editor {
					border: 1px solid #e2e8f0;
					border-radius: 12px;
					overflow: hidden;
					background: #fff;
					box-shadow: 0 2px 8px rgba(0,0,0,.04);
				}
				.lv2-day-row {
					border-bottom: 1px solid #f1f5f9;
					transition: background .15s;
				}
				.lv2-day-row:last-child { border-bottom: none; }
				.lv2-day-header {
					display: flex;
					align-items: center;
					gap: 10px;
					padding: 11px 15px;
				}
				.lv2-day-label {
					font-weight: 800;
					font-size: .85rem;
					letter-spacing: .06em;
					text-transform: uppercase;
					width: 36px;
					color: #1e293b;
					flex-shrink: 0;
				}
				.lv2-day-label.weekend { color: #7c3aed; }
				.lv2-day-row.is-day-closed .lv2-day-label { color: #94a3b8; }
				.lv2-closed-badge {
					background: #fef2f2;
					color: #ef4444;
					font-size: .7rem;
					font-weight: 700;
					padding: 2px 8px;
					border-radius: 4px;
					display: none;
				}
				.lv2-day-row.is-day-closed .lv2-closed-badge { display: inline-block; }
				.lv2-day-actions { margin-left: auto; display: flex; align-items: center; gap: 8px; }

				/* Toggle */
				.lv2-toggle { position: relative; width: 38px; height: 21px; flex-shrink: 0; }
				.lv2-toggle input { display: none; }
				.lv2-toggle-track {
					position: absolute; inset: 0;
					background: #e2e8f0;
					border-radius: 10px;
					cursor: pointer;
					transition: background .2s;
				}
				.lv2-toggle input:checked + .lv2-toggle-track { background: #ff8c42; }
				.lv2-toggle-thumb {
					position: absolute; top: 3px; left: 3px;
					width: 15px; height: 15px;
					background: #fff;
					border-radius: 50%;
					pointer-events: none;
					transition: left .18s;
					box-shadow: 0 1px 3px rgba(0,0,0,.2);
				}
				.lv2-toggle input:checked ~ .lv2-toggle-thumb { left: 20px; }
				.lv2-toggle-lbl {
					font-size: .72rem; font-weight: 600; color: #64748b;
					cursor: pointer; user-select: none; letter-spacing: .04em;
				}

				/* Add slot */
				.lv2-btn-add {
					font-size: .72rem; font-weight: 700;
					color: #ff8c42;
					background: none;
					border: 1.5px dashed #ffcfaa;
					border-radius: 5px;
					padding: 3px 10px;
					cursor: pointer;
					transition: all .15s;
					display: none;
				}
				.lv2-btn-add:hover { background: #fff5ee; border-color: #ff8c42; }
				.lv2-day-row:not(.is-day-closed) .lv2-btn-add { display: inline-block; }

				/* Copy menu */
				.lv2-copy-wrap { position: relative; }
				.lv2-btn-copy {
					background: none; border: 1px solid #e2e8f0;
					color: #94a3b8; border-radius: 5px;
					width: 26px; height: 26px;
					display: flex; align-items: center; justify-content: center;
					cursor: pointer; font-size: .85rem; transition: all .12s;
				}
				.lv2-btn-copy:hover { color: #ff8c42; border-color: #ff8c42; background: #fff5ee; }
				.lv2-copy-menu {
					display: none; position: absolute; right: 0; top: calc(100% + 5px);
					background: #fff; border: 1px solid #e2e8f0;
					border-radius: 8px; padding: 4px; z-index: 300;
					min-width: 155px; box-shadow: 0 8px 24px rgba(0,0,0,.1);
				}
				.lv2-copy-menu.open { display: block; }
				.lv2-copy-menu button {
					display: block; width: 100%;
					background: none; border: none; color: #475569;
					font-size: .78rem; padding: 7px 10px; text-align: left;
					cursor: pointer; border-radius: 5px; transition: background .1s;
				}
				.lv2-copy-menu button:hover { background: #f8fafc; color: #ff8c42; }

				/* Slots */
				.lv2-slots { padding: 0 15px 12px; display: flex; flex-direction: column; gap: 7px; }
				.lv2-day-row.is-day-closed .lv2-slots { display: none; }
				.lv2-slot {
					display: flex; align-items: center; gap: 8px;
					animation: lv2SlideIn .15s ease;
				}
				@keyframes lv2SlideIn {
					from { opacity: 0; transform: translateY(-4px); }
					to   { opacity: 1; transform: translateY(0); }
				}
				.lv2-slot-idx {
					font-size: .7rem; color: #cbd5e0; width: 14px;
					flex-shrink: 0; text-align: right; font-weight: 700;
				}
				.lv2-sep { font-size: .75rem; color: #94a3b8; flex-shrink: 0; }
				.lv2-slot input[type="time"] {
					background: #f8fafc; border: 1px solid #e2e8f0;
					color: #1e293b; border-radius: 7px; padding: 6px 9px;
					font-size: .85rem; font-weight: 600;
					width: 98px; outline: none; transition: border-color .15s;
				}
				.lv2-slot input[type="time"]:focus { border-color: #ff8c42; box-shadow: 0 0 0 3px rgba(255,140,66,.1); }
				.lv2-btn-rm {
					background: none; border: none; color: #cbd5e0;
					cursor: pointer; font-size: 1rem; line-height: 1;
					padding: 3px 5px; border-radius: 4px;
					margin-left: auto; transition: color .12s, background .12s;
				}
				.lv2-btn-rm:hover { color: #ef4444; background: #fef2f2; }
				.lv2-slot:first-child:last-child .lv2-btn-rm { visibility: hidden; }

				/* Footer */
				.lv2-hours-footer {
					padding: 11px 15px;
					border-top: 1px solid #f1f5f9;
					display: flex; align-items: center; justify-content: space-between;
					background: #fafafa;
					font-size: .75rem; color: #94a3b8; font-weight: 600;
				}
				</style>

				<!-- Données PHP → JS -->
				<script>
				window.__poiHoursInitData = {{ poiOpeningHoursJson|raw }};
				</script>

				<!-- Conteneur de l'éditeur -->
				<div id="lv2-hours-editor" class="lv2-hours-editor"></div>

				<!-- Champs cachés pour la soumission du formulaire (générés par JS) -->
				<div id="lv2-hours-hidden-fields"></div>
						</div>
					</div>
					<div class="form-section">
						<h3>Équipements</h3>
						<div class="field-row">
							<div class="field">
								<label for="poi-wheelchair">Accès PMR</label>
								<select id="poi-wheelchair" name="features[wheelchair]">
									<option value="">—</option>
									<option value="yes" {% if features.wheelchair == 'yes' %}selected{% endif %}>Oui</option>
									<option value="no" {% if features.wheelchair == 'no' %}selected{% endif %}>Non</option>
									<option value="limited" {% if features.wheelchair == 'limited' %}selected{% endif %}>Limité</option>
								</select>
							</div>
							<div class="field">
								<label for="poi-wifi">Wi-Fi</label>
								<select id="poi-wifi" name="features[internet_access]">
									<option value="">—</option>
									<option value="wlan" {% if features.internet_access == 'wlan' %}selected{% endif %}>Oui (Wi-Fi)</option>
									<option value="no" {% if features.internet_access == 'no' %}selected{% endif %}>Non</option>
								</select>
							</div>
							<div class="field">
								<label for="poi-parking">Parking</label>
								<select id="poi-parking" name="features[parking]">
									<option value="">—</option>
									<option value="yes" {% if features.parking == 'yes' %}selected{% endif %}>Oui</option>
									<option value="no" {% if features.parking == 'no' %}selected{% endif %}>Non</option>
								</select>
							</div>
						</div>
					</div>
				</div>

				<!-- =================== MÉDIAS =================== -->
				<div class="tab" id="tab-media">
					<div class="form-section">
						<h3>Photos</h3>
						{% if not poi.id %}
						<p><em>Sauvegardez d'abord le POI pour pouvoir ajouter des photos.</em></p>
						{% else %}
						<div class="pm-drop-zone" id="photo-drop-zone">
							<i class="fa-solid fa-cloud-upload-alt pm-drop-icon"></i>
							<p>Glissez des photos ici ou</p>
							<label class="button">
								<input type="file" id="photo-upload-input" accept="image/jpeg,image/png,image/webp" style="display:none;" multiple>
								Choisir des photos
							</label>
							<p class="pm-drop-hint">JPG, PNG, WEBP — Max 5 Mo</p>
						</div>
						<div class="field">
							<label for="photo-caption">Légende à attribuer</label>
							<input type="text" id="photo-caption" placeholder="Description de la photo...">
						</div>
						<div class="pm-photo-gallery" id="photo-gallery">
							{% if photos %}
							{% for photo in photos %}
							<div class="pm-photo-item {% if photo.is_cover %}is-cover{% endif %}" data-photo-id="{{ photo.id }}">
								<img src="{{ SHOW.siteUrl }}/{% if 'data/' not in photo.url %}data/{% endif %}{{ photo.url }}" alt="{{ photo.caption }}">
								<div class="pm-photo-meta">
									<input type="text" value="{{ photo.caption }}" placeholder="Ajouter une légende..."
									       class="photo-caption-input" data-photo-id="{{ photo.id }}" data-poi-id="{{ poi.id }}">
								</div>
								<div class="pm-photo-actions">
									<button type="button" class="btn-photo-action btn-set-cover {% if photo.is_cover %}active{% endif %}" 
									        data-photo-id="{{ photo.id }}" data-poi-id="{{ poi.id }}" title="Mettre en couverture">
										<i class="fa-solid fa-star"></i> Couverture
									</button>
									<button type="button" class="btn-photo-action btn-delete-photo"
									        data-photo-id="{{ photo.id }}" data-poi-id="{{ poi.id }}" title="Supprimer la photo">
										<i class="fa-solid fa-trash"></i>
									</button>
								</div>
							</div>
							{% endfor %}
							{% else %}
							<p class="pm-empty-state">Aucune photo pour l'instant.</p>
							{% endif %}
						</div>
						{% endif %}
					</div>
				</div>

				<!-- =================== LIENS =================== -->
				<div class="tab" id="tab-links">
					<div class="form-section">
						<h3>Réseaux sociaux & liens</h3>
						<div id="links-container">
							{% for link in links %}
							<div class="pm-link-row" data-index="{{ loop.index0 }}">
								<span class="link-icon"><i class="fa-solid fa-link"></i></span>
								<input type="text" name="links[{{ loop.index0 }}][label]" value="{{ link.label }}" placeholder="Label (ex: Facebook)" class="pm-link-label">
								<input type="url" name="links[{{ loop.index0 }}][url]" value="{{ link.url }}" placeholder="https://..." class="pm-link-url">
								<button type="button" class="button button-small alert btn-remove-link"><i class="fa-solid fa-times"></i></button>
							</div>
							{% endfor %}
						</div>
						<button type="button" id="btn-add-link" class="button">
							<i class="fa-solid fa-plus"></i> Ajouter un lien
						</button>
					</div>
				</div>

				<!-- =================== DESCRIPTION =================== -->
				<div class="tab" id="tab-description">
					<div class="form-section">
						<h3>Description longue</h3>
						<div class="field">
							<textarea name="description_content" rows="12" class="editor">{{ description }}</textarea>
						</div>
					</div>
				</div>

				<!-- =================== TAGS =================== -->
				<div class="tab" id="tab-tags">
					<div class="form-section">
						<h3>Tags personnalisés</h3>
						<p><small>Paires clé/valeur personnalisées. Ex : <code>specialite</code> → <code>Développement web</code></small></p>
						<div id="tags-container">
							{% for tag in tags %}
							<div class="pm-tag-row">
								<input type="text" name="tags[{{ tag.key_name }}]" value="{{ tag.key_name }}" class="pm-tag-key" readonly>
								<input type="text" name="tags_val[{{ tag.key_name }}]" value="{{ tag.value_text }}" class="pm-tag-val">
								<button type="button" class="button button-small alert btn-remove-tag"><i class="fa-solid fa-times"></i></button>
							</div>
							{% endfor %}
						</div>
						<div class="pm-tag-row" id="new-tag-row">
							<input type="text" id="new-tag-key" placeholder="Clé" class="pm-tag-key">
							<input type="text" id="new-tag-val" placeholder="Valeur" class="pm-tag-val">
							<button type="button" id="btn-add-tag" class="button"><i class="fa-solid fa-plus"></i></button>
						</div>
					</div>
				</div>

			</div><!-- /.tabs -->
		</div><!-- /.tabs-container -->

		<div class="form-actions">
			<button class="button success" type="submit"><i class="fa-solid fa-save"></i> Sauvegarder</button>
			<a class="button" href="{{ ROUTER.generate('admin-poimanager-list') }}">Annuler</a>
		</div>
	</form>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
	// ===== TABS SWITCHING =====
	document.querySelectorAll('.tabs-header li').forEach(li => {
		li.addEventListener('click', () => {
			const tabId = li.dataset.tab;
			document.querySelectorAll('.tabs-header li').forEach(l => l.classList.remove('active'));
			document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
			li.classList.add('active');
			const targetTab = document.getElementById('tab-' + tabId);
			if (targetTab) targetTab.classList.add('active');
			
			// Si on va sur l'onglet localisation, on force un resize pour Leaflet
			if (tabId === 'location') {
				setTimeout(() => {
					window.dispatchEvent(new Event('resize'));
					if (window.poiMap) {
						window.poiMap.refresh();
						// Second pass to be absolutely sure after potential animations
						setTimeout(() => window.poiMap.refresh(), 300);
					}
				}, 200);
			}
		});
	});

	const poiId  = '{{ poi.id }}';
	const siteUrl = '{{ SITEURL }}';
	const csrfToken = '{{ _csrfToken }}';

	// ===== FERMETURE TOGGLE =====
	const closedCb = document.getElementById('poi-is-closed');
	const closedField = document.getElementById('closed-reason-field');
	if (closedCb && closedField) {
		closedCb.addEventListener('change', () => {
			closedField.style.display = closedCb.checked ? '' : 'none';
		});
	}

	// ===== CARTE (UniversalMap via SearchMaps) =====
	const latInput = document.getElementById('poi-lat');
	const lonInput = document.getElementById('poi-lon');
	const mapEl    = document.getElementById('poi-map');
	
	if (mapEl && typeof UniversalMap !== 'undefined') {
		const initLat = parseFloat(latInput?.value) || 47.0810;
		const initLon = parseFloat(lonInput?.value) || 2.3988;
		
		window.poiMap = new UniversalMap('poi-map', {
			lat: initLat,
			lon: initLon,
			zoom: 14,
			editable: true,
			onChange: function(lat, lon) {
				if (latInput) latInput.value = lat.toFixed(7);
				if (lonInput) lonInput.value = lon.toFixed(7);
			}
		});

		if (latInput) latInput.addEventListener('change', function() {
			if (window.poiMap) window.poiMap.setMarker(parseFloat(this.value) || initLat, parseFloat(lonInput?.value) || initLon);
		});
		if (lonInput) lonInput.addEventListener('change', function() {
			if (window.poiMap) window.poiMap.setMarker(parseFloat(latInput?.value) || initLat, parseFloat(this.value) || initLon);
		});

		// Trigger initial resize if we start on location tab (unlikely but safe)
		if (document.querySelector('.tabs-header li.active')?.dataset.tab === 'location') {
			setTimeout(() => window.poiMap?.refresh(), 500);
		}
	}

	// ===== UPLOAD PHOTOS =====
	document.getElementById('photo-upload-input')?.addEventListener('change', function() {
		Array.from(this.files).forEach(f => uploadPhoto(f));
		this.value = '';
	});

	function uploadPhoto(file) {
		if (!poiId) return;
		const fd = new FormData();
		fd.append('photo', file);
		fd.append('poi_id', poiId);
		fd.append('caption', document.getElementById('photo-caption')?.value || '');
		fd.append('_csrf', csrfToken);
		fetch(siteUrl + '/admin/PoiManager/uploadPhoto', { method: 'POST', body: fd })
			.then(r => r.json())
			.then(d => { if (d.success) location.reload(); else alert('Erreur : ' + d.message); });
	}

	document.querySelectorAll('.btn-delete-photo').forEach(btn => {
		btn.addEventListener('click', () => {
			if (!confirm('Supprimer cette photo ?')) return;
			const fd = new FormData();
			fd.append('photo_id', btn.dataset.photoId);
			fd.append('poi_id', btn.dataset.poiId);
			fd.append('_csrf', csrfToken);
			fetch(siteUrl + '/admin/PoiManager/deletePhoto/' + btn.dataset.photoId, { method: 'POST', body: fd })
				.then(r => r.json())
				.then(d => { if (d.success) btn.closest('.pm-photo-item').remove(); });
		});
	});

	document.querySelectorAll('.photo-caption-input').forEach(input => {
		input.addEventListener('change', () => {
			const fd = new FormData();
			fd.append('photo_id', input.dataset.photoId);
			fd.append('poi_id', input.dataset.poiId);
			fd.append('caption', input.value);
			fd.append('_csrf', csrfToken);
			fetch(siteUrl + '/admin/PoiManager/updatePhotoCaption', { method: 'POST', body: fd });
		});
	});

	// ===== LIENS =====
	let linkIndex = parseInt("0{{ links|length|default(0) }}", 10);
	const linksContainer = document.getElementById('links-container');
	const btnAddLink = document.getElementById('btn-add-link');

	if (btnAddLink && linksContainer) {
		btnAddLink.addEventListener('click', () => {
			const row = document.createElement('div');
			row.className = 'pm-link-row';
			row.dataset.index = linkIndex;
			row.innerHTML = `
				<span class="link-icon"><i class="fa-solid fa-link"></i></span>
				<input type="text" name="links[${linkIndex}][label]" placeholder="Label (ex: Facebook)" class="pm-link-label">
				<input type="url"  name="links[${linkIndex}][url]"   placeholder="https://..." class="pm-link-url">
				<button type="button" class="button button-small alert btn-remove-link"><i class="fa-solid fa-times"></i></button>
			`;
			linksContainer.appendChild(row);
			attachLinkListeners(row);
			linkIndex++;
		});

		document.querySelectorAll('.pm-link-row').forEach(row => attachLinkListeners(row));
	}

	function attachLinkListeners(row) {
		const urlInput = row.querySelector('.pm-link-url');
		const labelInput = row.querySelector('.pm-link-label');
		const iconEl = row.querySelector('.link-icon i');
		const btnRemove = row.querySelector('.btn-remove-link');

		btnRemove.addEventListener('click', () => row.remove());

		urlInput.addEventListener('input', () => {
			const url = urlInput.value.toLowerCase();
			let label = '';
			let icon = 'fa-link';

			if (url.includes('facebook.com')) { label = 'Facebook'; icon = 'fa-brands fa-facebook'; }
			else if (url.includes('instagram.com')) { label = 'Instagram'; icon = 'fa-brands fa-instagram'; }
			else if (url.includes('linkedin.com')) { label = 'LinkedIn'; icon = 'fa-brands fa-linkedin'; }
			else if (url.includes('twitter.com') || url.includes('x.com')) { label = 'X / Twitter'; icon = 'fa-brands fa-x-twitter'; }
			else if (url.includes('youtube.com')) { label = 'YouTube'; icon = 'fa-brands fa-youtube'; }
			else if (url.includes('tiktok.com')) { label = 'TikTok'; icon = 'fa-brands fa-tiktok'; }

			if (label && !labelInput.value) {
				labelInput.value = label;
			}
			if (iconEl) {
				iconEl.className = 'fa-solid ' + icon;
				if (icon.includes('fa-brands')) iconEl.className = icon;
			}
		});

		// Trigger initial icon check
		if (urlInput.value) urlInput.dispatchEvent(new Event('input'));
	}

	// ===== TAGS =====
	document.getElementById('btn-add-tag')?.addEventListener('click', () => {
		const key = document.getElementById('new-tag-key').value.trim().replace(/\s+/g, '_').toLowerCase();
		const val = document.getElementById('new-tag-val').value.trim();
		if (!key || !val) { alert('Clé et valeur requises.'); return; }
		const row = document.createElement('div');
		row.className = 'pm-tag-row';
		row.innerHTML = `
			<input type="text" name="tags[${key}]" value="${key}" class="pm-tag-key" readonly>
			<input type="text" name="tags_val[${key}]" value="${val}" class="pm-tag-val">
			<button type="button" class="button button-small alert btn-remove-tag"><i class="fa-solid fa-times"></i></button>
		`;
		row.querySelector('.btn-remove-tag').addEventListener('click', () => row.remove());
		document.getElementById('tags-container').appendChild(row);
		document.getElementById('new-tag-key').value = '';
		document.getElementById('new-tag-val').value = '';
	});
	document.querySelectorAll('.btn-remove-tag').forEach(b => b.addEventListener('click', () => b.closest('.pm-tag-row').remove()));
});

// ===== CATEGORIES ET SOUS-CATEGORIES =====
(function() {
    const specialtiesByFamily = {% if specialtiesByFamilyJson %}{{ specialtiesByFamilyJson|raw }}{% else %}{}{% endif %};
    const poiSubcategory = "{{ poi.subcategory|default('')|raw }}";
    const familySelect = document.getElementById('family_select');
    const subcategoriesContainer = document.getElementById('subcategories_container');
    const customSubcategoryWrapper = document.getElementById('custom_subcategory_wrapper');
    const customSubcategoryInput = document.getElementById('custom_subcategory');
    const subcatHelp = document.getElementById('subcat_help');

    function loadSpecialties(familyKey) {
        subcategoriesContainer.innerHTML = '';
        const specs = specialtiesByFamily[familyKey] || [];
        
        if (specs.length === 0 && !familyKey) {
            subcatHelp.style.display = 'block';
            subcategoriesContainer.style.display = 'none';
            return;
        }
        
        subcatHelp.style.display = 'none';
        subcategoriesContainer.style.display = 'grid';

        const activeSubcats = poiSubcategory ? poiSubcategory.split(',').map(s => s.trim()) : [];

        // Checkbox pour chaque spécialité mappée
        specs.forEach(function(spec) {
            const label = document.createElement('label');
            label.style.cssText = 'display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer; padding: 4px; border-radius: 3px;';
            label.onmouseover = function() { this.style.background = '#eee'; };
            label.onmouseout = function() { this.style.background = 'transparent'; };
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'subcategory[]'; 
            checkbox.value = spec.key;
            
            if (activeSubcats.includes(spec.key)) {
                checkbox.checked = true;
            }
            
            label.appendChild(checkbox);
            label.appendChild(document.createTextNode(spec.label));
            subcategoriesContainer.appendChild(label);
        });

        // Case "Autre"
        const otherLabel = document.createElement('label');
        otherLabel.style.cssText = 'display: flex; align-items: center; gap: 8px; font-weight: bold; cursor: pointer; color: #1976d2; padding: 4px; border-top: 1px solid #ddd; margin-top: 5px;';
        
        const otherCheckbox = document.createElement('input');
        otherCheckbox.type = 'checkbox';
        otherCheckbox.name = 'subcategory[]';
        otherCheckbox.id = 'other_subcat_checkbox';
        otherCheckbox.value = '__custom__';
        
        // Find custom keys that aren't mapped in this family's specs
        const unmappedSubcats = activeSubcats.filter(s => !specs.some(spec => spec.key === s) && s !== '');
        
        if (unmappedSubcats.length > 0) {
            otherCheckbox.checked = true;
            if (customSubcategoryInput) {
                customSubcategoryInput.value = unmappedSubcats.join(', ');
            }
        }

        otherCheckbox.addEventListener('change', toggleCustomField);
        
        otherLabel.appendChild(otherCheckbox);
        otherLabel.appendChild(document.createTextNode('-- Autre (précisez) --'));
        subcategoriesContainer.appendChild(otherLabel);
        
        // On attache un listener sur tous les radios pour cacher le custom si un mapped est selectionné
        subcategoriesContainer.querySelectorAll('input[type="radio"]').forEach(el => {
            el.addEventListener('change', toggleCustomField);
        });
        
        toggleCustomField();
    }

    function toggleCustomField() {
        const otherCheckbox = document.getElementById('other_subcat_checkbox');
        if (otherCheckbox && otherCheckbox.checked) {
            customSubcategoryWrapper.style.display = 'block';
        } else {
            customSubcategoryWrapper.style.display = 'none';
        }
    }

    if (familySelect) {
        if (familySelect.value) {
            loadSpecialties(familySelect.value);
        }
        toggleCustomField();
    }
})();

// ===== HORAIRES — new editor engine =====
(function() {
    const DAYS = [
        { label: 'Lun', weekend: false },
        { label: 'Mar', weekend: false },
        { label: 'Mer', weekend: false },
        { label: 'Jeu', weekend: false },
        { label: 'Ven', weekend: false },
        { label: 'Sam', weekend: true  },
        { label: 'Dim', weekend: true  },
    ];

    // Hydrate state from PHP data (array of {day_of_week, open_time, close_time, is_closed})
    const raw = window.__poiHoursInitData || [];

    let state = DAYS.map((d, i) => {
        const dayRows = raw.filter(r => parseInt(r.day_of_week) === i);
        // A day is "fully closed" only if ALL its rows are is_closed, and there are no open rows
        const closedRows  = dayRows.filter(r => r.is_closed == 1 || r.is_closed === true);
        const openRows    = dayRows.filter(r => !r.is_closed || r.is_closed == 0);
        const isClosed    = dayRows.length > 0 && openRows.length === 0;
        const slots = openRows.length > 0
            ? openRows.map(r => ({
                open_time:  (r.open_time  || '09:00').substring(0, 5),
                close_time: (r.close_time || '18:00').substring(0, 5),
            }))
            : [{ open_time: '09:00', close_time: '18:00' }];
        return { day_of_week: i, is_closed: isClosed, slots };
    });

    const editor = document.getElementById('lv2-hours-editor');
    if (!editor) return;

    function render() {
        editor.innerHTML = '';
        state.forEach((day, di) => {
            const row = document.createElement('div');
            row.className = 'lv2-day-row' + (day.is_closed ? ' is-day-closed' : '');

            // Header
            const hdr = document.createElement('div');
            hdr.className = 'lv2-day-header';

            const lbl = document.createElement('span');
            lbl.className = 'lv2-day-label' + (DAYS[di].weekend ? ' weekend' : '');
            lbl.textContent = DAYS[di].label;

            const badge = document.createElement('span');
            badge.className = 'lv2-closed-badge';
            badge.textContent = 'fermé';

            const actions = document.createElement('div');
            actions.className = 'lv2-day-actions';

            // + plage button
            const btnAdd = document.createElement('button');
            btnAdd.type = 'button';
            btnAdd.className = 'lv2-btn-add';
            btnAdd.textContent = '+ plage';
            btnAdd.onclick = () => {
                if (day.slots.length < 4) {
                    day.slots.push({ open_time: '14:00', close_time: '19:00' });
                    render();
                }
            };

            // Copy menu
            const copyWrap = document.createElement('div');
            copyWrap.className = 'lv2-copy-wrap';
            const btnCopy = document.createElement('button');
            btnCopy.type = 'button';
            btnCopy.className = 'lv2-btn-copy';
            btnCopy.title = 'Copier vers…';
            btnCopy.innerHTML = '<i class="fa-solid fa-copy"></i>';
            const menu = document.createElement('div');
            menu.className = 'lv2-copy-menu';
            [['→ Lun–Ven', 'weekdays'], ['→ Week-end', 'weekend'], ['→ Tous les jours', 'all']].forEach(([label, target]) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = label;
                btn.onclick = (e) => { e.stopPropagation(); copyTo(di, target); menu.classList.remove('open'); };
                menu.appendChild(btn);
            });
            btnCopy.onclick = (e) => {
                e.stopPropagation();
                document.querySelectorAll('.lv2-copy-menu.open').forEach(m => m !== menu && m.classList.remove('open'));
                menu.classList.toggle('open');
            };
            copyWrap.appendChild(btnCopy);
            copyWrap.appendChild(menu);

            // Toggle fermé
            const toggleWrap = document.createElement('label');
            toggleWrap.className = 'lv2-toggle';
            const toggleInput = document.createElement('input');
            toggleInput.type = 'checkbox';
            toggleInput.checked = day.is_closed;
            toggleInput.onchange = () => { day.is_closed = toggleInput.checked; render(); writeHiddenFields(); };
            const track = document.createElement('div'); track.className = 'lv2-toggle-track';
            const thumb = document.createElement('div'); thumb.className = 'lv2-toggle-thumb';
            toggleWrap.appendChild(toggleInput);
            toggleWrap.appendChild(track);
            toggleWrap.appendChild(thumb);

            const toggleLbl = document.createElement('span');
            toggleLbl.className = 'lv2-toggle-lbl';
            toggleLbl.textContent = 'FERMÉ';
            toggleLbl.onclick = () => toggleInput.click();

            actions.appendChild(btnAdd);
            actions.appendChild(copyWrap);
            actions.appendChild(toggleWrap);
            actions.appendChild(toggleLbl);
            hdr.appendChild(lbl); hdr.appendChild(badge); hdr.appendChild(actions);
            row.appendChild(hdr);

            // Slots
            const slotsDiv = document.createElement('div');
            slotsDiv.className = 'lv2-slots';
            day.slots.forEach((slot, si) => {
                const slotEl = document.createElement('div');
                slotEl.className = 'lv2-slot';

                const idx = document.createElement('span');
                idx.className = 'lv2-slot-idx';
                idx.textContent = si + 1;

                const arrow = document.createElement('span');
                arrow.className = 'lv2-sep';
                arrow.textContent = '→';

                const openIn = makeTimeInput(slot.open_time, v => { slot.open_time = v; writeHiddenFields(); });
                const dash   = document.createElement('span');
                dash.className = 'lv2-sep'; dash.textContent = '–';
                const closeIn = makeTimeInput(slot.close_time, v => { slot.close_time = v; writeHiddenFields(); });

                const btnRm = document.createElement('button');
                btnRm.type = 'button';
                btnRm.className = 'lv2-btn-rm';
                btnRm.title = 'Supprimer cette plage';
                btnRm.innerHTML = '&times;';
                btnRm.onclick = () => { if (day.slots.length > 1) { day.slots.splice(si, 1); render(); writeHiddenFields(); } };

                slotEl.appendChild(idx); slotEl.appendChild(arrow);
                slotEl.appendChild(openIn); slotEl.appendChild(dash); slotEl.appendChild(closeIn);
                slotEl.appendChild(btnRm);
                slotsDiv.appendChild(slotEl);
            });
            row.appendChild(slotsDiv);
            editor.appendChild(row);
        });

        // Footer (open days count)
        const footer = document.createElement('div');
        footer.className = 'lv2-hours-footer';
        const openCount = state.filter(d => !d.is_closed).length;
        footer.textContent = openCount + ' j / 7 ouverts';
        editor.appendChild(footer);

        // Close menus on outside click
        document.onclick = () => document.querySelectorAll('.lv2-copy-menu.open').forEach(m => m.classList.remove('open'));
    }

    function makeTimeInput(value, onChange) {
        const input = document.createElement('input');
        input.type = 'time';
        input.value = value;
        input.onchange = () => onChange(input.value);
        return input;
    }

    function copyTo(fromIdx, target) {
        const src = state[fromIdx];
        const targets = target === 'all' ? [0,1,2,3,4,5,6]
            : target === 'weekdays' ? [0,1,2,3,4] : [5,6];
        targets.forEach(i => {
            if (i === fromIdx) return;
            state[i].is_closed = src.is_closed;
            state[i].slots = src.slots.map(s => ({ ...s }));
        });
        render();
        writeHiddenFields();
    }

    // Write hidden form fields matching hours[dayId1-7][slotIndex][field]
    function writeHiddenFields() {
        const container = document.getElementById('lv2-hours-hidden-fields');
        if (!container) return;
        container.innerHTML = '';
        state.forEach((day, di) => {
            const dayId = di + 1; // 1=Lundi…7=Dimanche (matches controller)
            if (day.is_closed) {
                // Send is_closed=1 for slot 0 so the backend knows this day is closed
                make(container, `hours[${dayId}][0][is_closed]`, '1');
            } else {
                day.slots.forEach((slot, si) => {
                    make(container, `hours[${dayId}][${si}][open_time]`,  slot.open_time);
                    make(container, `hours[${dayId}][${si}][close_time]`, slot.close_time);
                    make(container, `hours[${dayId}][${si}][is_closed]`,  '0');
                });
            }
        });
    }

    function make(container, name, value) {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = name;
        input.value = value;
        container.appendChild(input);
    }

    render();
    writeHiddenFields();



    /* --- Photo Management --- */
    const gallery = document.getElementById('photo-gallery');
    if (gallery) {
        gallery.addEventListener('click', function(e) {
            const btnCover = e.target.closest('.btn-set-cover');
            if (btnCover) {
                const photoId = btnCover.dataset.photoId;
                const poiId   = btnCover.dataset.poiId;

                fetch('{{ ROUTER.generate('admin-poimanager-photo-set-cover') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `photo_id=${photoId}&poi_id=${poiId}&_csrf={{ _csrfToken }}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.pm-photo-item').forEach(item => {
                            item.classList.remove('is-cover');
                            item.querySelector('.btn-set-cover').classList.remove('active');
                        });
                        btnCover.closest('.pm-photo-item').classList.add('is-cover');
                        btnCover.classList.add('active');
                        showToast("Photo de couverture mise à jour !");
                    } else {
                        showToast(data.message || "Erreur lors de la mise à jour", "error");
                    }
                });
            }
        });

        // Caption Update with feedback
        gallery.addEventListener('change', function(e) {
            if (e.target.classList.contains('photo-caption-input')) {
                const input = e.target;
                const fd = new FormData();
                fd.append('photo_id', input.dataset.photoId);
                fd.append('poi_id', input.dataset.poiId);
                fd.append('caption', input.value);
                fd.append('_csrf', '{{ _csrfToken }}');
                
                fetch('{{ SITEURL }}/admin/PoiManager/updatePhotoCaption', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(d => {
                        if(d.success) showToast("Légende enregistrée");
                        else showToast("Erreur lors de l'enregistrement", "error");
                    });
            }
        });
    }

    // Photo Upload Refresh
    window.addEventListener('photoUploaded', () => { showToast("Photo ajoutée !"); location.reload(); });

})();
</script>

<style>
	/* --- Modern Admin POI Layout --- */
	.module-card { background: #fcfcfc; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #eee; }
	.module-card header { background: #f8f9fa; padding: 1.5rem; border-bottom: 2px solid #eee; font-size: 1.25rem; font-weight: 700; color: #333; }
	
	.tabs-header { background: #fff; padding: 0 1rem; border-bottom: 1px solid #eee; display: flex; gap: 5px; flex-wrap: wrap; }
	.tabs-header li { padding: 12px 20px; font-weight: 600; color: #666; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s; display: flex; align-items: center; gap: 8px; }
	.tabs-header li:hover { background: #f9f9f9; color: #333; }
	.tabs-header li.active { color: #ff8c42; border-bottom-color: #ff8c42; background: #fffaf7; }
	
	.tabs-container .tabs .tab { display: none; }
	.tabs-container .tabs .tab.active { display: block; }
	
	#poi-map, .map-container { height: 450px !important; width: 100%; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 10px; z-index: 1; background: #eee; }

	.form-section { background: #fff; padding: 1.5rem; border-radius: 10px; border: 1px solid #efefef; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
	.form-section h3 { margin-top: 0; font-size: 1.1rem; color: #333; border-bottom: 2px solid #fff3e0; padding-bottom: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
	.form-section h3::before { content: ""; width: 4px; height: 20px; background: #ff8c42; border-radius: 10px; }

	.field label { font-weight: 700; color: #444; margin-bottom: 8px; display: block; font-size: 0.95rem; }
	.field input[type="text"], .field input[type="number"], .field select, .field textarea { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; transition: all 0.2s; background: #fff; }
	.field input:focus, .field select:focus { border-color: #ff8c42; box-shadow: 0 0 0 3px rgba(255,140,66,0.1); outline: none; }

	/* --- Photo Gallery Redesign --- */
	.pm-photo-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
	.pm-photo-item { position: relative; background: #fff; border-radius: 12px; border: 1px solid #eee; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.05); transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); }
	.pm-photo-item:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
	.pm-photo-item.is-cover { border: 3px solid #f1c40f; }
	.pm-photo-item.is-cover::after { content: "\f005"; font-family: "Font Awesome 6 Free"; font-weight: 900; position: absolute; top: 10px; left: 10px; background: #f1c40f; color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 10; }
	
	.pm-photo-item img { width: 100%; height: 160px; object-fit: cover; border-bottom: 1px solid #eee; }
	.pm-photo-meta { padding: 10px; background: #fcfcfc; }
	.pm-photo-meta input { width: 100%; border: none; font-size: 0.85rem; padding: 5px; background: transparent; color: #555; text-align: center; }
	
	.pm-photo-actions { padding: 10px; display: flex; gap: 8px; justify-content: center; background: #fff; border-top: 1px solid #f5f5f5; }
	.btn-photo-action { flex: 1; padding: 8px 5px; font-size: 0.75rem; font-weight: 700; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 5px; background: #fff; }
	.btn-set-cover { color: #f39c12; }
	.btn-set-cover:hover, .btn-set-cover.active { background: #f1c40f; color: #fff; border-color: #f1c40f; }
	.btn-delete-photo { color: #e74c3c; }
	.btn-delete-photo:hover { background: #e74c3c; color: #fff; border-color: #e74c3c; }

	/* --- Opening Hours Cards --- */
	.opening-hours-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
	.day-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: flex; flex-direction: column; overflow: hidden; }
	.day-header { background: #f1f5f9; padding: 15px; border-bottom: 2px solid #e2e8f0; display: flex; align-items: center; }
	.day-label { font-weight: 900; font-size: 1.2rem; color: #0f172a; text-transform: uppercase; letter-spacing: 1.5px; display: flex; align-items: center; gap: 10px; }
	.day-label i { color: #ff8c42; font-size: 1.1em; }

	.day-ranges-container { padding: 15px; flex-grow: 1; min-height: 80px; }
	
	.opening-hour-row { background: #fdfdfd; padding: 12px; border-radius: 10px; margin-bottom: 12px; display: flex; flex-direction: column; gap: 10px; border: 1px solid #f1f5f9; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
	.range-inputs { display: flex; align-items: center; gap: 12px; justify-content: center; }
	.range-actions { display: flex; align-items: center; justify-content: space-between; border-top: 1px dashed #edf2f7; padding-top: 10px; }
	
	.opening-hour-row .time-select { padding: 8px; border-radius: 6px; border: 1px solid #cbd5e0; font-weight: 700; width: 100px; text-align: center; font-size: 1rem; color: #334155; }
	.opening-hour-row .time-select:focus { border-color: #ff8c42; outline: none; box-shadow: 0 0 0 3px rgba(255,140,66,0.1); }
	
	.opening-hour-row.is-closed { background: #fff5f5; border-color: #fed7d7; }
	.opening-hour-row.is-closed .time-select { opacity: 0.4; }
	
	.empty-day-hint { font-style: italic; color: #94a3b8; font-size: 0.9rem; text-align: center; padding: 10px; }

	.btn-add-range { margin: 0 15px 15px; border: 2px dashed #e2e8f0; background: #fff; padding: 10px; border-radius: 8px; color: #64748b; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
	.btn-add-range:hover { background: #f8fafc; border-color: #cbd5e0; color: #475569; }
	.btn-add-range i { color: #ff8c42; }

	/* --- Toast Feedback --- */
	.pm-toast { position: fixed; bottom: 20px; right: 20px; background: #333; color: white; padding: 12px 25px; border-radius: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); z-index: 9999; animation: pm-toast-in 0.3s ease-out; }
	.pm-toast.success { background: #28a745; }
	.pm-toast.error { background: #dc3545; }
	@keyframes pm-toast-in { from { transform: translateY(100px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

	/* PM Drop Zone refinement */
	.pm-drop-zone { border: 3px dashed #e2e8f0; border-radius: 15px; padding: 40px; text-align: center; background: #f8fafc; transition: all 0.3s; margin-bottom: 20px; }
	.pm-drop-zone:hover { border-color: #ff8c42; background: #fffaf7; }
	.pm-drop-icon { font-size: 3rem; color: #cbd5e0; margin-bottom: 15px; }
	.pm-drop-zone p { font-weight: 600; color: #64748b; }
</style>
