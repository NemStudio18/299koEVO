<section class="agenda-event-form">
    {% IF CORE.callHook('map:is-enabled') %}
    {{ CORE.callHook('map:load-assets') }}
    {% ENDIF %}

    <div class="header-actions">
        <a href="{{cancelUrl}}" class="button secondary small">
            <i class="fa-solid fa-arrow-left"></i> Retour à l'agenda
        </a>
    </div>

    {% IF isProposal %}
    <div class="info-box">
        <p>
            <strong><i class="fa-solid fa-info-circle"></i> Information :</strong><br>
            Votre événement sera soumis à validation par un administrateur avant d'être publié.
        </p>
    </div>
    {% ENDIF %}

    <form method="POST" action="{{actionUrl}}" enctype="multipart/form-data" class="form-vertical" novalidate>
        <input type="hidden" name="_csrf" value="{{csrfToken}}">
        {% IF poiId %}
        <input type="hidden" name="poi_id" value="{{poiId}}">
        {% ENDIF %}
        
        <div class="form-group">
            <label>Titre de l'événement <span class="required">*</span></label>
            <input type="text" name="title" required placeholder="Titre de votre événement">
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="editor" rows="6" placeholder="Décrivez votre événement..."></textarea>
        </div>
        
        {% IF isProposal %}
        <div class="form-group">
            <label>
                Preuve de l'événement <span class="required">*</span>
                <small>(PDF, JPG, PNG, etc. - obligatoire pour valider votre proposition)</small>
            </label>
            <input type="file" name="proof_file" accept=".pdf,.jpg,.jpeg,.png,.gif,.bmp,.doc,.docx" required>
        </div>
        {% ENDIF %}
        
        <div class="date-grid">
            <div class="form-group">
                <label>Date de début <span class="required">*</span></label>
                <input type="date" name="start_date" required>
            </div>
            
            <div class="form-group">
                <label>Date de fin</label>
                <input type="date" name="end_date">
            </div>
        </div>
        
        <div class="form-group">
            <label>Lieu</label>
            <input type="text" name="location" placeholder="Adresse ou lieu de l'événement">
        </div>

        <div class="form-group">
            <label>Position sur la carte (facultatif)</label>
            <p class="help-text">Cliquez sur la carte pour définir précisément le lieu.</p>
            <div id="event-map-picker" style="height: 300px; border-radius: 8px; border: 1px solid var(--form-border-color); margin-bottom: 10px;"></div>
            <input type="hidden" name="latitude" value="">
            <input type="hidden" name="longitude" value="">
        </div>
        
        <div class="form-group">
            <label>Couleur</label>
            <input type="color" name="color" value="#3498db">
        </div>
        
        <div class="form-actions">
            <a href="{{cancelUrl}}" class="button secondary">Annuler</a>
            <button type="submit" class="button primary">
                <i class="fa-solid fa-save"></i> 
                {% IF isProposal %}
                Proposer l'événement
                {% ELSE %}
                Créer l'événement
                {% ENDIF %}
            </button>
        </div>
    </form>
</section>


