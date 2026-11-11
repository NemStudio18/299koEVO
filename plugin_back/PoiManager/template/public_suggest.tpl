<section class="module-card">
	<header>
		<i class="fa-solid fa-lightbulb"></i>
		Proposer une correction pour : <strong>{{ poi.name }}</strong>
	</header>

	<p>Vous avez repéré une information incorrecte ou obsolète ? Indiquez les corrections ci-dessous.
	   Un administrateur les examinera avant de les appliquer.</p>

	<form method="post" action="{{ ROUTER.generate('poimanager-suggest-save') }}" class="pm-form-public">
		<input type="hidden" name="_csrf" value="{{ _csrfToken }}">
		<input type="hidden" name="poi_id" value="{{ poi.id }}">

		<div class="form-section">
			<h3>Informations actuelles → vos corrections</h3>
			<p><small>Laissez un champ vide pour ne pas modifier cette information.</small></p>

			<!-- Nom -->
			<div class="field">
				<label for="s-name">Nom</label>
				<div class="field-row">
					<input type="text" value="{{ poi.name }}" readonly class="pm-old-field">
					<input type="text" id="s-name" name="field_name" placeholder="Correction..." class="pm-new-field">
				</div>
			</div>

			<!-- Téléphone -->
			<div class="field">
				<label for="s-phone">Téléphone</label>
				<div class="field-row">
					<input type="text" value="{{ poi.contact_phone|default('—') }}" readonly class="pm-old-field">
					<input type="text" id="s-phone" name="field_contact_phone" placeholder="Correction..." class="pm-new-field">
				</div>
			</div>

			<!-- Email -->
			<div class="field">
				<label for="s-email">Email</label>
				<div class="field-row">
					<input type="text" value="{{ poi.contact_email|default('—') }}" readonly class="pm-old-field">
					<input type="email" id="s-email" name="field_contact_email" placeholder="Correction..." class="pm-new-field">
				</div>
			</div>

			<!-- Site web -->
			<div class="field">
				<label for="s-website">Site web</label>
				<div class="field-row">
					<input type="text" value="{{ poi.website|default('—') }}" readonly class="pm-old-field">
					<input type="url" id="s-website" name="field_website" placeholder="https://..." class="pm-new-field">
				</div>
			</div>

			<!-- Horaires -->
			<div class="field">
				<label for="s-opening">Horaires d'ouverture précis</label>
				
				<div class="field-row" style="margin-bottom: 10px; background: #f8f9fa; padding: 10px; border-radius: 8px; border: 1px solid #eee;">
					<div style="flex: 1;">
						<small style="display: block; color: #666; margin-bottom: 5px;">Horaires actuels (lecture seule) :</small>
						<input type="text" value="{{ poi.opening_hours|default('—') }}" readonly class="pm-old-field" style="width: 100%;">
					</div>
				</div>

				<!-- Données PHP → JS -->
				<script>
				window.__poiHoursInitData = {{ poiOpeningHoursJson|raw }};
				</script>

				<!-- Conteneur de l'éditeur -->
				<div id="lv2-hours-editor" class="lv2-hours-editor"></div>

				<!-- Champs cachés pour la soumission du formulaire (générés par JS) -->
				<div id="lv2-hours-hidden-fields"></div>
			</div>

			<!-- Adresse -->
			<div class="field">
				<label for="s-street">Rue</label>
				<div class="field-row">
					<input type="text" value="{{ poi.addr_street|default('—') }}" readonly class="pm-old-field">
					<input type="text" id="s-street" name="field_addr_street" placeholder="Correction..." class="pm-new-field">
				</div>
			</div>
			<!-- Micro-description (Liste) -->
			<div class="field">
				<label for="s-desc-list">Description courte (255 car. max)</label>
				<div class="field-row">
					<input type="text" value="{{ poi.desc_list|default('—') }}" readonly class="pm-old-field">
					<input type="text" id="s-desc-list" name="field_desc_list" placeholder="Une brève description pour les listes..." class="pm-new-field" maxlength="255">
				</div>
			</div>

			<div class="field">
				<label for="s-city">Ville</label>
				<div class="field-row">
					<input type="text" value="{{ poi.addr_city|default('—') }}" readonly class="pm-old-field">
					<input type="text" id="s-city" name="field_addr_city" placeholder="Correction..." class="pm-new-field">
				</div>
			</div>
		</div>

		<div class="form-actions">
			<button class="button success" type="submit">
				<i class="fa-solid fa-paper-plane"></i> Envoyer mes corrections
			</button>
			<a class="button" href="javascript:history.back()">Annuler</a>
		</div>
	</form>

    <hr>

    {% if photoLimit > 0 %}
    <div class="form-section photo-suggestion-section">
        <h3>Contribuer avec une photo</h3>
        <p>Aidez la communauté en ajoutant une photo de cet établissement. (Votre niveau actuel vous permet d'ajouter jusqu'à <strong>{{ photoLimit }} photo(s)</strong> par établissement).</p>
        
        <form id="photo-suggest-form" enctype="multipart/form-data">
            <input type="hidden" name="poi_id" value="{{ poi.id }}">
            <div class="field">
                <input type="file" name="photo" id="suggest-photo-file" accept="image/*" class="pm-new-field">
            </div>
            <div class="field">
                <input type="text" name="caption" placeholder="Légende (facultative...)" class="pm-new-field" style="width: 100%; margin-top: 10px;">
            </div>
            <div class="form-actions" style="margin-top: 15px;">
                <button type="submit" class="button primary" id="btn-suggest-photo">
                    <i class="fa-solid fa-camera"></i> Envoyer la photo
                </button>
            </div>
            <div id="photo-suggest-msg" style="margin-top: 10px; font-weight: 600;"></div>
        </form>
    </div>
    {% else %}
    <div class="form-section photo-suggestion-section" style="opacity: 0.7;">
        <h3>Contribuer avec une photo</h3>
        <div style="background: #f8f9fa; border: 1.5px dashed #e2e8f0; padding: 20px; border-radius: 12px; text-align: center;">
            <i class="fa-solid fa-lock" style="font-size: 1.5rem; color: #94a3b8; margin-bottom: 10px;"></i>
            <p style="margin: 0; color: #64748b;">Gagnez 250 points (Palier <strong>Contributeur</strong>) pour débloquer l'ajout de photos !</p>
        </div>
    </div>
    {% endif %}

</section>

<style>
/* === LV2 Hours Editor Styles (Suggestions) === */
.lv2-hours-editor { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.04); margin-top: 10px; }
.lv2-day-row { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.lv2-day-row:last-child { border-bottom: none; }
.lv2-day-header { display: flex; align-items: center; gap: 10px; padding: 11px 15px; }
.lv2-day-label { font-weight: 800; font-size: .85rem; letter-spacing: .06em; text-transform: uppercase; width: 36px; color: #1e293b; flex-shrink: 0; }
.lv2-day-label.weekend { color: #7c3aed; }
.lv2-day-row.is-day-closed { background: #f8fafc; }
.lv2-day-row.is-day-closed .lv2-day-label { color: #94a3b8; }
.lv2-closed-badge { background: #fef2f2; color: #ef4444; font-size: .7rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; display: none; }
.lv2-day-row.is-day-closed .lv2-closed-badge { display: inline-block; }
.lv2-day-actions { margin-left: auto; display: flex; align-items: center; gap: 8px; }

.lv2-toggle { position: relative; width: 38px; height: 21px; flex-shrink: 0; }
.lv2-toggle input { display: none; }
.lv2-toggle-track { position: absolute; inset: 0; background: #e2e8f0; border-radius: 10px; cursor: pointer; transition: background .2s; }
.lv2-toggle input:checked + .lv2-toggle-track { background: #ff8c42; }
.lv2-toggle-thumb { position: absolute; top: 3px; left: 3px; width: 15px; height: 15px; background: #fff; border-radius: 50%; pointer-events: none; transition: left .18s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.lv2-toggle input:checked ~ .lv2-toggle-thumb { left: 20px; }
.lv2-toggle-lbl { font-size: .72rem; font-weight: 600; color: #64748b; cursor: pointer; user-select: none; letter-spacing: .04em; }

.lv2-btn-add { font-size: .72rem; font-weight: 700; color: #ff8c42; background: none; border: 1.5px dashed #ffcfaa; border-radius: 5px; padding: 3px 10px; cursor: pointer; transition: all .15s; display: none; }
.lv2-btn-add:hover { background: #fff5ee; border-color: #ff8c42; }
.lv2-day-row:not(.is-day-closed) .lv2-btn-add { display: inline-block; }

.lv2-copy-wrap { position: relative; }
.lv2-btn-copy { background: none; border: 1px solid #e2e8f0; color: #94a3b8; border-radius: 5px; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: .85rem; transition: all .12s; }
.lv2-btn-copy:hover { color: #ff8c42; border-color: #ff8c42; background: #fff5ee; }
.lv2-copy-menu { display: none; position: absolute; right: 0; top: calc(100% + 5px); background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 4px; z-index: 300; min-width: 155px; box-shadow: 0 8px 24px rgba(0,0,0,.1); }
.lv2-copy-menu.open { display: block; }
.lv2-copy-menu button { display: block; width: 100%; background: none; border: none; color: #475569; font-size: .78rem; padding: 7px 10px; text-align: left; cursor: pointer; border-radius: 5px; transition: background .1s; }
.lv2-copy-menu button:hover { background: #f8fafc; color: #ff8c42; }

.lv2-slots { padding: 0 15px 12px; display: flex; flex-direction: column; gap: 7px; }
.lv2-day-row.is-day-closed .lv2-slots { display: none; }
.lv2-slot { display: flex; align-items: center; gap: 8px; animation: lv2SlideIn .15s ease; }
@keyframes lv2SlideIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
.lv2-slot-idx { font-size: .7rem; color: #cbd5e0; width: 14px; flex-shrink: 0; text-align: right; font-weight: 700; }
.lv2-sep { font-size: .75rem; color: #94a3b8; flex-shrink: 0; }
.lv2-slot input[type="time"] { background: #f8fafc; border: 1px solid #e2e8f0; color: #1e293b; border-radius: 7px; padding: 6px 9px; font-size: .85rem; font-weight: 600; width: 92px; outline: none; transition: border-color .15s; }
.lv2-slot input[type="time"]:focus { border-color: #ff8c42; box-shadow: 0 0 0 3px rgba(255,140,66,.1); }
.lv2-btn-rm { background: none; border: none; color: #cbd5e0; cursor: pointer; font-size: 1rem; line-height: 1; padding: 3px 5px; border-radius: 4px; margin-left: auto; transition: color .12s, background .12s; }
.lv2-btn-rm:hover { color: #ef4444; background: #fef2f2; }
.lv2-slot:first-child:last-child .lv2-btn-rm { visibility: hidden; }

.lv2-hours-footer { padding: 11px 15px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #fafafa; font-size: .75rem; color: #94a3b8; font-weight: 600; }

.pm-form-public .field { margin-bottom: 20px; }
.pm-form-public .field label { display: block; font-weight: 700; margin-bottom: 8px; color: #334155; }
.pm-old-field { background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b; font-style: italic; }
.pm-new-field { border: 1px solid #ff8c42 !important; box-shadow: 0 0 0 3px rgba(255,140,66,0.05); }
</style>

<script>
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

    const raw = window.__poiHoursInitData || [];
    let state = DAYS.map((d, i) => {
        const dayRows = raw.filter(r => parseInt(r.day_of_week) === i);
        const openRows = dayRows.filter(r => !r.is_closed || r.is_closed == 0);
        const isClosed = dayRows.length > 0 && openRows.length === 0;
        const slots = openRows.length > 0
            ? openRows.map(r => ({ open_time: (r.open_time || '09:00').substring(0, 5), close_time: (r.close_time || '18:00').substring(0, 5) }))
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
            
            const btnAdd = document.createElement('button');
            btnAdd.type = 'button';
            btnAdd.className = 'lv2-btn-add';
            btnAdd.textContent = '+ plage';
            btnAdd.onclick = () => { if (day.slots.length < 4) { day.slots.push({ open_time: '14:00', close_time: '19:00' }); render(); writeHiddenFields(); } };
            
            const copyWrap = document.createElement('div');
            copyWrap.className = 'lv2-copy-wrap';
            const btnCopy = document.createElement('button');
            btnCopy.type = 'button'; btnCopy.className = 'lv2-btn-copy'; btnCopy.innerHTML = '<i class="fa-solid fa-copy"></i>';
            const menu = document.createElement('div');
            menu.className = 'lv2-copy-menu';
            [['→ Lun–Ven', 'weekdays'], ['→ Week-end', 'weekend'], ['→ Tous les jours', 'all']].forEach(([label, target]) => {
                const btn = document.createElement('button');
                btn.type = 'button'; btn.textContent = label;
                btn.onclick = (e) => { e.stopPropagation(); copyTo(di, target); menu.classList.remove('open'); };
                menu.appendChild(btn);
            });
            btnCopy.onclick = (e) => { e.stopPropagation(); document.querySelectorAll('.lv2-copy-menu.open').forEach(m => m !== menu && m.classList.remove('open')); menu.classList.toggle('open'); };
            copyWrap.appendChild(btnCopy); copyWrap.appendChild(menu);
            
            const toggleWrap = document.createElement('label');
            toggleWrap.className = 'lv2-toggle';
            const toggleInput = document.createElement('input');
            toggleInput.type = 'checkbox';
            toggleInput.checked = day.is_closed;
            toggleInput.onchange = () => { day.is_closed = toggleInput.checked; render(); writeHiddenFields(); };
            const track = document.createElement('div'); track.className = 'lv2-toggle-track';
            const thumb = document.createElement('div'); thumb.className = 'lv2-toggle-thumb';
            toggleWrap.appendChild(toggleInput); toggleWrap.appendChild(track); toggleWrap.appendChild(thumb);
            
            const toggleLbl = document.createElement('span');
            toggleLbl.className = 'lv2-toggle-lbl'; toggleLbl.textContent = 'FERMÉ';
            toggleLbl.onclick = () => toggleInput.click();
            
            actions.appendChild(btnAdd); actions.appendChild(copyWrap); actions.appendChild(toggleWrap); actions.appendChild(toggleLbl);
            hdr.appendChild(lbl); hdr.appendChild(badge); hdr.appendChild(actions);
            row.appendChild(hdr);
            
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
                const dash = document.createElement('span'); dash.className = 'lv2-sep'; dash.textContent = '–';
                const closeIn = makeTimeInput(slot.close_time, v => { slot.close_time = v; writeHiddenFields(); });
                
                const btnRm = document.createElement('button');
                btnRm.type = 'button'; btnRm.className = 'lv2-btn-rm'; btnRm.innerHTML = '&times;';
                btnRm.onclick = () => { if (day.slots.length > 1) { day.slots.splice(si, 1); render(); writeHiddenFields(); } };
                
                slotEl.appendChild(idx); slotEl.appendChild(arrow);
                slotEl.appendChild(openIn); slotEl.appendChild(dash); slotEl.appendChild(closeIn);
                slotEl.appendChild(btnRm);
                slotsDiv.appendChild(slotEl);
            });
            row.appendChild(slotsDiv);
            editor.appendChild(row);
        });
        const footer = document.createElement('div');
        footer.className = 'lv2-hours-footer';
        footer.textContent = state.filter(d => !d.is_closed).length + ' j / 7 ouverts';
        editor.appendChild(footer);
        document.onclick = () => document.querySelectorAll('.lv2-copy-menu.open').forEach(m => m.classList.remove('open'));
    }

    function makeTimeInput(value, onChange) {
        const input = document.createElement('input');
        input.type = 'time'; input.value = value;
        input.onchange = () => onChange(input.value);
        return input;
    }

    function copyTo(fromIdx, target) {
        const src = state[fromIdx];
        const targets = target === 'all' ? [0,1,2,3,4,5,6] : target === 'weekdays' ? [0,1,2,3,4] : [5,6];
        targets.forEach(i => { if (i !== fromIdx) { state[i].is_closed = src.is_closed; state[i].slots = src.slots.map(s => ({ ...s })); } });
        render(); writeHiddenFields();
    }

    function writeHiddenFields() {
        const container = document.getElementById('lv2-hours-hidden-fields');
        if (!container) return;
        container.innerHTML = '';
        state.forEach((day, di) => {
            const dayId = di + 1;
            if (day.is_closed) {
                makeInput(container, `field_opening_hours_struct[${dayId}][0][is_closed]`, '1');
            } else {
                day.slots.forEach((slot, si) => {
                    makeInput(container, `field_opening_hours_struct[${dayId}][${si}][open_time]`, slot.open_time);
                    makeInput(container, `field_opening_hours_struct[${dayId}][${si}][close_time]`, slot.close_time);
                    makeInput(container, `field_opening_hours_struct[${dayId}][${si}][is_closed]`, '0');
                });
            }
        });
    }

    function makeInput(container, name, value) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = name; input.value = value;
        container.appendChild(input);
    }

    render();
    writeHiddenFields();

    // Photo Suggestion Logic
    document.getElementById('photo-suggest-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('btn-suggest-photo');
        const msg = document.getElementById('photo-suggest-msg');
        const formData = new FormData(form);

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Envoi...';
        msg.innerHTML = '';

        fetch('{{ ROUTER.generate('poimanager-suggest-photo-upload') }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.body && data.body.success) {
                msg.style.color = 'green';
                msg.innerHTML = data.body.message;
                form.reset();
            } else {
                msg.style.color = 'red';
                msg.innerHTML = (data.body && data.body.message) ? data.body.message : 'Une erreur est survenue.';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            msg.style.color = 'red';
            msg.innerHTML = 'Erreur de connexion.';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-camera"></i> Envoyer la photo';
        });
    });
})();
</script>
