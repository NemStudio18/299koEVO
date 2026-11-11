{% IF CORE.callHook('map:is-enabled') %}
{{ CORE.callHook('map:load-assets') }}
{% ENDIF %}
<section class="agenda-admin-edit">
    <h1>{% IF isEdit %}Modifier un évènement{% ELSE %}Créer un évènement{% ENDIF %}</h1>

    <form method="POST" action="{{actionUrl}}">
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

