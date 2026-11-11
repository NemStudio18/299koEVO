{% IF CORE.callHook('map:is-enabled') %}
{{ CORE.callHook('map:load-assets') }}
{% ENDIF %}
<section class="agenda-admin-edit">
    <h1>{% IF isEdit %}Modifier un évènement{% ELSE %}Créer un évènement{% ENDIF %}</h1>

    <form method="POST" action="{{actionUrl}}" enctype="multipart/form-data">
        {{SHOW.tokenField()}}
        <input type="hidden" name="id" value="{{formEvent.id}}">

        <div class="form-group">
            <label>{{Lang.agenda.title}}</label>
            <input type="text" name="title" value="{{formEvent.title}}" required>
        </div>

        <div class="form-group">
            <label>{{Lang.agenda.event-description}}</label>
            <textarea name="description" class="editor" rows="4">{{formEvent.description}}</textarea>
        </div>

        <div class="form-group">
            <label>Image d'illustration (affiche / photo)</label>
            {% IF formEvent.image_url %}
            <div style="margin-bottom: 10px; display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <img src="{{formEvent.image_url}}" alt="Aperçu" style="max-height: 90px; max-width: 140px; border-radius: 6px; object-fit: cover; border: 1px solid #cbd5e1;">
                <span style="font-size: 0.85rem; color: #64748b; word-break: break-all;">Actuelle : {{formEvent.image_url}}</span>
            </div>
            {% ENDIF %}
            <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
            <small style="display:block; margin-top:4px; color:#64748b;">Téléversez une affiche/photo depuis votre appareil, ou saisissez une URL ci-dessous (laissez vide pour extraire automatiquement une image de la description si présente) :</small>
            <input type="text" name="image_url" value="{{formEvent.image_url}}" placeholder="https://... ou /data/upload/...">
        </div>

        <div class="form-group">
            <label>Lien externe (Billetterie, site officiel, réservation)</label>
            <input type="url" name="external_url" value="{{formEvent.external_url}}" placeholder="https://...">
            <small style="display:block; margin-top:4px; color:#64748b;">Ajoute un bouton d'action directe sur la fiche de l'événement.</small>
        </div>

        <div class="form-group">
            <label>Lieu / Commerce partenaire associé (POI ID)</label>
            <input type="number" name="poi_id" value="{{formEvent.poi_id}}" placeholder="Ex: 42 (optionnel)">
            <small style="display:block; margin-top:4px; color:#64748b;">Relie automatiquement l'événement à la fiche commerce/lieu de Bourges.</small>
        </div>

        <div class="form-group">
            <label>{{Lang.agenda.start-date}}</label>
            <input type="datetime-local" name="start_date" value="{{formEvent.start_date}}" required>
        </div>

        <div class="form-group">
            <label>{{Lang.agenda.end-date}}</label>
            <input type="datetime-local" name="end_date" value="{{formEvent.end_date}}">
        </div>

        <div class="form-group">
            <label>{{Lang.agenda.location}}</label>
            <input type="text" name="location" value="{{formEvent.location}}">
        </div>

        {% IF CORE.callHook('map:is-enabled') %}
        <div class="form-group">
            <label>Position sur la carte (cliquez pour modifier)</label>
            <div id="event-map-picker" style="height: 300px; border-radius: 8px; border: 1px solid var(--form-border-color); margin-bottom: 10px; background: var(--section-head-bg-color);"></div>
            <input type="hidden" name="latitude" value="{% IF formEvent.latitude %}{{formEvent.latitude}}{% ENDIF %}">
            <input type="hidden" name="longitude" value="{% IF formEvent.longitude %}{{formEvent.longitude}}{% ENDIF %}">
        </div>
        {% ENDIF %}

        <div class="form-group">
            <label>{{Lang.agenda.color}}</label>
            <input type="color" name="color" value="{{formEvent.color}}">
        </div>

        <button type="submit" class="button primary">{% IF isEdit %}Mettre à jour{% ELSE %}Enregistrer{% ENDIF %}</button>
        <a class="button secondary" href="{ ROUTER.generate("admin-agenda-events") }">Retour à la liste</a>
    </form>
</section>

