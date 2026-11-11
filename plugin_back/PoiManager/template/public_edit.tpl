<section class="module-card">
	<header>
		<i class="fa-solid fa-pen"></i>
		Modifier ma fiche : <strong>{{ poi.name }}</strong>
	</header>

	<!-- Quotas -->
	<div class="form-section">
		<h3>Quotas de votre abonnement <span class="pm-plan-badge">{{ quota.plan|upper }}</span></h3>
		<div class="pm-quota-group">
			<span>Photos : {{ quota.photo_current }} / {{ quota.photo_max }}</span>
			<div class="pm-quota-bar">
				<div class="pm-quota-bar-fill {% if not quota.photo_can_add %}full{% endif %}"
				     style="width: {{ (quota.photo_current / quota.photo_max * 100)|round }}%;"></div>
			</div>
		</div>
		<div class="pm-quota-group">
			<span>Liens : {{ quota.link_current }} / {{ quota.link_max }}</span>
			<div class="pm-quota-bar">
				<div class="pm-quota-bar-fill {% if not quota.link_can_add %}full{% endif %}"
				     style="width: {{ (quota.link_current / quota.link_max * 100)|round }}%;"></div>
			</div>
		</div>
	</div>

	<form method="post" action="{{ ROUTER.generate('poimanager-owner-save') }}" class="pm-form-public">
		<input type="hidden" name="_csrf" value="{{ _csrfToken }}">
		<input type="hidden" name="poi_id" value="{{ poi.id }}">

		<div class="tabs-container">
			<ul class="tabs-header">
				<li class="active" data-tab="contact"><i class="fa-solid fa-phone"></i> Contact & Horaires</li>
				<li data-tab="links"><i class="fa-solid fa-link"></i> Liens</li>
				<li data-tab="photos"><i class="fa-solid fa-images"></i> Photos</li>
			</ul>

			<div class="tabs">
				<!-- CONTACT -->
				<div class="tab active" id="tab-contact">
					<div class="form-section">
						<h3>Identité & Coordonnées</h3>
						<!-- Micro-description (Liste) -->
						<div class="field">
							<label for="owner-desc-list">Micro-description (Liste) <small>(160 caractères max)</small></label>
							<input type="text" id="owner-desc-list" name="desc_list" value="{{ poi.desc_list }}" maxlength="160" placeholder="Brève phrase d'accroche pour les résultats de recherche...">
						</div>
						<div class="field">
							<label for="owner-description">Description détaillée {% if quota.plan == 'free' %}<small>(800 caractères max)</small>{% endif %}</label>
							<textarea id="owner-description" name="description" {% if quota.plan == 'free' %}maxlength="800"{% endif %} rows="5" placeholder="Présentez votre établissement en détails...">{{ description }}</textarea>
						</div>
						<div class="field-row">
							<div class="field">
								<label for="owner-phone">Téléphone</label>
								<input type="text" id="owner-phone" name="contacts[phone]" value="{{ contacts.phone }}" placeholder="+33 2 48 ...">
							</div>
							<div class="field">
								<label for="owner-email">Email</label>
								<input type="email" id="owner-email" name="contacts[email]" value="{{ contacts.email }}">
							</div>
						</div>
						<div class="field">
							<label for="owner-website">Site web</label>
							<input type="url" id="owner-website" name="contacts[website]" value="{{ contacts.website }}" placeholder="https://...">
						</div>
						<div class="field">
							<label>Horaires d'ouverture précis</label>
							{# Nouveau moteur d'horaires JS (Synchronisé avec Admin) #}
							<div id="poi-hours-editor" class="pm-hours-editor">
								<!-- Injecté par JS -->
							</div>
							<script>
								window.__poiHoursInitData = {{ poiOpeningHoursJson|raw }};
							</script>
						</div>
					</div>
					<div class="form-section">
						<h3>Équipements</h3>
						<div class="field-row">
							<div class="field">
								<label for="owner-wheelchair">Accès PMR</label>
								<select id="owner-wheelchair" name="features[wheelchair]">
									<option value="">—</option>
									<option value="yes" {% if features.wheelchair == 'yes' %}selected{% endif %}>Oui</option>
									<option value="no" {% if features.wheelchair == 'no' %}selected{% endif %}>Non</option>
									<option value="limited" {% if features.wheelchair == 'limited' %}selected{% endif %}>Limité</option>
								</select>
							</div>
							<div class="field">
								<label for="owner-wifi">Wi-Fi</label>
								<select id="owner-wifi" name="features[internet_access]">
									<option value="">—</option>
									<option value="wlan" {% if features.internet_access == 'wlan' %}selected{% endif %}>Oui (Wi-Fi)</option>
									<option value="no" {% if features.internet_access == 'no' %}selected{% endif %}>Non</option>
								</select>
							</div>
							<div class="field">
								<label for="owner-parking">Parking</label>
								<select id="owner-parking" name="features[parking]">
									<option value="">—</option>
									<option value="yes" {% if features.parking == 'yes' %}selected{% endif %}>Oui</option>
									<option value="no" {% if features.parking == 'no' %}selected{% endif %}>Non</option>
								</select>
							</div>
						</div>
					</div>
				</div>

				<!-- LIENS -->
				<div class="tab" id="tab-links">
					<div class="form-section">
						<h3>Réseaux sociaux & liens</h3>
						{% if not quota.link_can_add %}
						<p><em>Quota de liens atteint ({{ quota.link_max }} max). <a href="#">Passer à un plan supérieur</a> pour en ajouter plus.</em></p>
						{% endif %}
						<div id="links-container">
							{% for link in links %}
							<div class="pm-link-row" data-index="{{ loop.index0 }}">
								<span class="link-icon"><i class="fa-solid fa-link"></i></span>
								<input type="text" name="links[{{ loop.index0 }}][label]" value="{{ link.label }}" placeholder="Label" class="pm-link-label">
								<input type="url"  name="links[{{ loop.index0 }}][url]"   value="{{ link.url }}"   placeholder="https://..." class="pm-link-url">
								<button type="button" class="button button-small alert btn-remove-link"><i class="fa-solid fa-times"></i></button>
							</div>
							{% endfor %}
						</div>
						{% if quota.link_can_add %}
						<button type="button" id="btn-add-link" class="button">
							<i class="fa-solid fa-plus"></i> Ajouter un lien
						</button>
						{% endif %}
					</div>
				</div>

				<!-- PHOTOS -->
				<div class="tab" id="tab-photos">
					<div class="form-section">
						<h3>Photos</h3>
						{% if quota.photo_can_add %}
						<div class="pm-drop-zone" id="photo-drop-zone">
							<i class="fa-solid fa-cloud-upload-alt pm-drop-icon"></i>
							<p>Glissez vos photos ici ou</p>
							<label class="button">
								<input type="file" id="photo-upload-input" accept="image/jpeg,image/png,image/webp" style="display:none;" multiple>
								Choisir
							</label>
							<p class="pm-drop-hint">JPG, PNG, WEBP — Max 5 Mo</p>
						</div>
						{% else %}
						<p><em>Quota de photos atteint ({{ quota.photo_max }} max).</em></p>
						{% endif %}

						<div class="pm-photo-gallery" id="photo-gallery">
							{% for photo in photos %}
							<div class="pm-photo-item {% if photo.is_cover %}is-cover{% endif %}" data-photo-id="{{ photo.id }}">
								<img src="{{ SITEURL }}/{% if 'data/' not in photo.url %}data/{% endif %}{{ photo.url }}" alt="{{ photo.caption }}">
								<div class="pm-photo-meta">
									<input type="text" value="{{ photo.caption }}" placeholder="Légende..."
									       class="photo-caption-input" data-photo-id="{{ photo.id }}" data-poi-id="{{ poi.id }}">
								</div>
								<div class="pm-photo-actions">
									<button type="button" class="btn-set-cover {% if photo.is_cover %}active{% endif %}" 
									        data-photo-id="{{ photo.id }}" data-poi-id="{{ poi.id }}" title="Utiliser comme couverture">
										<i class="fa-solid fa-star"></i>
									</button>
									<button type="button" class="pm-photo-delete btn-delete-photo"
									        data-photo-id="{{ photo.id }}" data-poi-id="{{ poi.id }}" title="Supprimer">
										<i class="fa-solid fa-times"></i>
									</button>
								</div>
							</div>
							{% else %}
							<p>Aucune photo pour l'instant.</p>
							{% endfor %}
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="form-actions">
			<button class="button success" type="submit"><i class="fa-solid fa-save"></i> Sauvegarder</button>
			<a class="button" href="{{ ROUTER.generate('poimanager-home') }}">Retour</a>
		</div>
	</form>
</section>

<style>
.pm-link-row {
	display: flex;
	gap: 10px;
	margin-bottom: 10px;
	align-items: center;
	background: #f9f9f9;
	padding: 10px;
	border-radius: 4px;
	border: 1px solid #eee;
}
.link-icon {
	width: 30px;
	text-align: center;
	font-size: 1.2em;
	color: #666;
}
.pm-link-label { flex: 1; }
.pm-link-url { flex: 2; }
.pm-photo-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 20px; }
.pm-photo-item { position: relative; border: 2px solid transparent; transition: all 0.3s; border-radius: 8px; overflow: hidden; background: #eee; }
.pm-photo-item img { width: 100%; height: 120px; object-fit: cover; display: block; }
.pm-photo-item.is-cover { border-color: #f1c40f; box-shadow: 0 0 10px rgba(241, 196, 15, 0.3); }
.pm-photo-actions { position: absolute; top: 5px; right: 5px; display: flex; gap: 5px; opacity: 0.8; transition: 0.2s; }
.pm-photo-item:hover .pm-photo-actions { opacity: 1; }
.pm-photo-actions button { background: rgba(0,0,0,0.6); color: #fff; border: none; width: 26px; height: 26px; border-radius: 50%; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; font-size: 12px; }
.pm-photo-actions button:hover { background: rgba(0,0,0,0.9); transform: scale(1.1); }
.pm-photo-actions button.btn-set-cover.active { color: #f1c40f; background: rgba(0,0,0,0.9); }
.pm-photo-actions button.pm-photo-delete:hover { background: #e74c3c; }
.pm-photo-meta { padding: 5px; background: #fff; }
.pm-photo-meta input { width: 100%; border: none; font-size: 11px; padding: 2px; background: transparent; }

/* Styles Heures (Synchronisés Admin) */
.pm-hours-editor {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
}
.pm-day-row {
    display: flex;
    align-items: flex-start;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}
.pm-day-row:last-child { border-bottom: none; }
.pm-day-label {
    width: 100px;
    font-weight: 600;
    color: #1e293b;
    padding-top: 8px;
}
.pm-day-controls {
    flex: 1;
}
.pm-slots-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}
.pm-time-slot {
    display: flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    padding: 4px 8px;
    border-radius: 6px;
    gap: 5px;
}
.pm-time-slot input[type="time"] {
    border: none;
    background: transparent;
    font-family: inherit;
    font-size: 13px;
    color: #334155;
    padding: 2px;
}
.pm-time-slot .sep { color: #94a3b8; font-size: 12px; }
.btn-remove-slot {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0 4px;
    font-size: 14px;
}
.btn-remove-slot:hover { color: #ef4444; }
.btn-add-slot {
    background: #fff;
    border: 1px dashed #ff8c42;
    color: #ff8c42;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: 0.2s;
}
.btn-add-slot:hover { background: #fffaf0; border-style: solid; }
.pm-day-status { width: 120px; text-align: right; }
.status-switch {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
}
.status-switch input { display: none; }
.switch-slider {
    width: 34px;
    height: 18px;
    background: #ff8c42;
    border-radius: 10px;
    position: relative;
    transition: 0.3s;
}
.switch-slider:before {
    content: "";
    position: absolute;
    width: 14px;
    height: 14px;
    background: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: 0.3s;
}
input:checked + .switch-slider { background: #cbd5e1; }
input:checked + .switch-slider:before { transform: translateX(16px); }
.status-text { color: #ff8c42; min-width: 45px; text-align: left; }
input:checked ~ .status-text { color: #94a3b8; }
.pm-copy-tools {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px dashed #f1f5f9;
    display: flex;
    gap: 10px;
    align-items: center;
}
.pm-copy-tools span { font-size: 12px; color: #64748b; font-weight: 600; }
.btn-copy {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11px;
    cursor: pointer;
}
.btn-copy:hover { background: #e2e8f0; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
	const siteUrl   = '{{ SITEURL }}';
	const poiId     = '{{ poi.id }}';
	const csrfToken = '{{ _csrfToken }}';

	// Upload photo
	document.getElementById('photo-upload-input')?.addEventListener('change', function() {
		Array.from(this.files).forEach(file => {
			const fd = new FormData();
			fd.append('photo', file);
			fd.append('poi_id', poiId);
			fd.append('_csrf', csrfToken);
			fetch(siteUrl + '/PoiManager/uploadPhoto', { method: 'POST', body: fd })
				.then(r => r.json())
				.then(d => { if (d.success) location.reload(); else alert(d.message); });
		});
		this.value = '';
	});

	// Liens dynamiques
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
				<input type="text" name="links[${linkIndex}][label]" placeholder="Label" class="pm-link-label">
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

			if (label && !labelInput.value) labelInput.value = label;
			if (iconEl) {
				iconEl.className = 'fa-solid ' + icon;
				if (icon.includes('fa-brands')) iconEl.className = icon;
			}
		});

		if (urlInput.value) urlInput.dispatchEvent(new Event('input'));
	}
	// --- NOUVEL ÉDITEUR D'HORAIRES (Sync Admin) ---
	const hoursContainer = document.getElementById('poi-hours-editor');
	const initData = window.__poiHoursInitData || [];
	const days = [
		{ id: 1, label: 'Lundi' },
		{ id: 2, label: 'Mardi' },
		{ id: 3, label: 'Mercredi' },
		{ id: 4, label: 'Jeudi' },
		{ id: 5, label: 'Vendredi' },
		{ id: 6, label: 'Samedi' },
		{ id: 7, label: 'Dimanche' }
	];

	function render() {
		if (!hoursContainer) return;
		let html = '';
		days.forEach(day => {
			const daySlots = initData.filter(h => parseInt(h.day_of_week) === day.id);
			const isClosed = daySlots.some(h => h.is_closed) || daySlots.length === 0;

			html += `
                <div class="pm-day-row" data-day="${day.id}">
                    <div class="pm-day-label">${day.label}</div>
                    <div class="pm-day-controls">
                        <div class="pm-slots-container" style="${isClosed ? 'display:none' : ''}">
                            ${daySlots.filter(h => !h.is_closed).map((h, i) => renderSlot(day.id, i, h)).join('')}
                            <button type="button" class="btn-add-slot" data-day="${day.id}">+ plage</button>
                        </div>
                    </div>
                    <div class="pm-day-status">
                        <label class="status-switch">
                            <input type="checkbox" class="day-closed-toggle" ${isClosed ? 'checked' : ''}>
                            <div class="switch-slider"></div>
                            <span class="status-text">${isClosed ? 'FERMÉ' : 'OUVERT'}</span>
                        </label>
                    </div>
                </div>
            `;
		});

		html += `
            <div class="pm-copy-tools">
                <span>Reporter le lundi sur :</span>
                <button type="button" class="btn-copy" data-target="week">Semaine</button>
                <button type="button" class="btn-copy" data-target="weekend">Week-end</button>
                <button type="button" class="btn-copy" data-target="all">Tous les jours</button>
            </div>
            <div id="hidden-hours-fields" style="display:none"></div>
        `;

		hoursContainer.innerHTML = html;
		attachHoursEvents();
		writeHiddenFields();
	}

	function renderSlot(dayId, index, data = {}) {
		const open = data.open_time ? data.open_time.substring(0, 5) : '09:00';
		const close = data.close_time ? data.close_time.substring(0, 5) : '18:00';
		const uuid = data.uuid || 'new_' + Date.now() + Math.random();
		return `
            <div class="pm-time-slot" data-uuid="${uuid}">
                <input type="time" class="time-open" value="${open}">
                <span class="sep">à</span>
                <input type="time" class="time-close" value="${close}">
                <button type="button" class="btn-remove-slot"><i class="fa-solid fa-times"></i></button>
            </div>
        `;
	}

	function attachHoursEvents() {
		hoursContainer.querySelectorAll('.day-closed-toggle').forEach(chk => {
			chk.onchange = (e) => {
				const row = e.target.closest('.pm-day-row');
				const container = row.querySelector('.pm-slots-container');
				const dayId = parseInt(row.dataset.day);

				if (e.target.checked) {
					container.style.display = 'none';
				} else {
					container.style.display = 'flex';
					const daySlots = initData.filter(h => parseInt(h.day_of_week) === dayId && !h.is_closed);
					if (daySlots.length === 0) {
						// Ajouter une plage par défaut si on ouvre un jour vide
						const newUuid = 'new_' + Date.now();
						initData.push({ day_of_week: dayId, open_time: '09:00', close_time: '18:00', is_closed: 0, uuid: newUuid });
						render();
						return;
					}
				}
				updateInitDataFromUI();
				render();
			};
		});

		hoursContainer.querySelectorAll('.btn-add-slot').forEach(btn => {
			btn.onclick = () => {
				const dayId = parseInt(btn.dataset.day);
				const slots = initData.filter(h => h.day_of_week == dayId && !h.is_closed);
				if (slots.length >= 4) return;
				initData.push({ day_of_week: dayId, open_time: '09:00', close_time: '18:00', is_closed: 0, uuid: 'new_' + Date.now() });
				render();
			};
		});

		hoursContainer.querySelectorAll('.btn-remove-slot').forEach(btn => {
			btn.onclick = () => {
				const uuid = btn.closest('.pm-time-slot').dataset.uuid;
				const idx = initData.findIndex(h => h.uuid == uuid);
				if (idx !== -1) initData.splice(idx, 1);
				render();
			};
		});

		hoursContainer.querySelectorAll('.btn-copy').forEach(btn => {
			btn.onclick = () => {
				const target = btn.dataset.target;
				const mondaySlots = initData.filter(h => h.day_of_week == 1);
				let targetDays = [];
				if (target === 'week') targetDays = [2, 3, 4, 5];
				if (target === 'weekend') targetDays = [6, 7];
				if (target === 'all') targetDays = [2, 3, 4, 5, 6, 7];

				targetDays.forEach(d => {
					// Supprimer anciens slots
					for (let i = initData.length - 1; i >= 0; i--) {
						if (initData[i].day_of_week == d) initData.splice(i, 1);
					}
					// Copier lundi
					mondaySlots.forEach(s => {
						initData.push({ ...s, day_of_week: d, uuid: 'copy_' + d + '_' + Math.random() });
					});
				});
				render();
			};
		});

		hoursContainer.addEventListener('change', (e) => {
			if (e.target.matches('input[type="time"]')) writeHiddenFields();
		});
	}

	function updateInitDataFromUI() {
		hoursContainer.querySelectorAll('.pm-day-row').forEach(row => {
			const dayId = parseInt(row.dataset.day);
			const isClosed = row.querySelector('.day-closed-toggle').checked;

			if (isClosed) {
				// Supprimer les plages ouvertes et garder une seule ligne fermée
				for (let i = initData.length - 1; i >= 0; i--) {
					if (initData[i].day_of_week == dayId) initData.splice(i, 1);
				}
				initData.push({ day_of_week: dayId, is_closed: 1, uuid: 'closed_' + dayId });
			}
		});
	}

	function writeHiddenFields() {
		const container = document.getElementById('hidden-hours-fields');
		if (!container) return;
		container.innerHTML = '';

		days.forEach(day => {
			const row = hoursContainer.querySelector(`.pm-day-row[data-day="${day.id}"]`);
			const isClosed = row ? row.querySelector('.day-closed-toggle').checked : true;

			if (isClosed) {
				addHidden(container, `hours[${day.id}][0][is_closed]`, '1');
			} else {
				const slots = row.querySelectorAll('.pm-time-slot');
				slots.forEach((slot, i) => {
					const open = slot.querySelector('.time-open').value;
					const close = slot.querySelector('.time-close').value;
					addHidden(container, `hours[${day.id}][${i}][open_time]`, open);
					addHidden(container, `hours[${day.id}][${i}][close_time]`, close);
					addHidden(container, `hours[${day.id}][${i}][is_closed]`, '0');
				});
			}
		});
	}

	function addHidden(container, name, value) {
		const input = document.createElement('input');
		input.type = 'hidden';
		input.name = name;
		input.value = value;
		container.appendChild(input);
	}

	render();

	// --- FIN ÉDITEUR ---

	// Gestion Photos : Légende, Suppression, Couverture
	const gallery = document.getElementById('photo-gallery');
	if (gallery) {
		gallery.addEventListener('click', function(e) {
			// Action Couverture
			const btnCover = e.target.closest('.btn-set-cover');
			if (btnCover) {
				const photoId = btnCover.dataset.photoId;
				const fd = new FormData();
				fd.append('photo_id', photoId);
				fd.append('poi_id', poiId);
				fd.append('_csrf', csrfToken);

				fetch(siteUrl + '/PoiManager/setPhotoAsCover', { method: 'POST', body: fd })
					.then(r => r.json())
					.then(d => {
						if (d.success) {
							document.querySelectorAll('.pm-photo-item').forEach(item => {
								item.classList.remove('is-cover');
								item.querySelector('.btn-set-cover').classList.remove('active');
							});
							btnCover.closest('.pm-photo-item').classList.add('is-cover');
							btnCover.classList.add('active');
						} else alert(d.message || 'Erreur');
					});
			}

			// Action Suppression
			const btnDelete = e.target.closest('.btn-delete-photo');
			if (btnDelete) {
				if (!confirm('Supprimer cette photo ?')) return;
				const photoId = btnDelete.dataset.photoId;
				const fd = new FormData();
				fd.append('photo_id', photoId);
				fd.append('poi_id', poiId);
				fd.append('_csrf', csrfToken);

				fetch(siteUrl + '/PoiManager/ownerDeletePhoto', { method: 'POST', body: fd })
					.then(r => r.json())
					.then(d => {
						if (d.success) btnDelete.closest('.pm-photo-item').remove();
						else alert(d.message || 'Erreur');
					});
			}
		});

		gallery.addEventListener('change', function(e) {
			const inputCaption = e.target.closest('.photo-caption-input');
			if (inputCaption) {
				const photoId = inputCaption.dataset.photoId;
				const fd = new FormData();
				fd.append('photo_id', photoId);
				fd.append('poi_id', poiId);
				fd.append('caption', inputCaption.value);
				fd.append('_csrf', csrfToken);

				fetch(siteUrl + '/PoiManager/updatePhotoCaption', { method: 'POST', body: fd });
			}
		});
	}
});
</script>
