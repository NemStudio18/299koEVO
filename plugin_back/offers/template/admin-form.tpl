<section class="offers-admin-form">
    <h1>{{ title }}</h1>

    <form method="POST" action="{{ actionUrl }}" class="form-vertical">
        <input type="hidden" name="_csrf" value="{{ csrfToken }}">

        <div class="form-group">
            <label>ID du POI (fiche réclamée) *</label>
            {% IF poiIdReadonly %}
                <input type="hidden" name="poi_id" value="{{ offer.poi_id }}">
                <div style="padding: 0.75rem; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
                    <strong>{% IF poiName %}{{ poiName }}{% ELSE %}POI #{{ offer.poi_id }}{% ENDIF %}</strong>
                    <small style="display: block; color: #666; margin-top: 0.25rem;">ID: {{ offer.poi_id }}</small>
                </div>
            {% ELSE %}
                <input type="number" name="poi_id" value="{% IF offer %}{{ offer.poi_id }}{% ENDIF %}" required min="1">
            {% ENDIF %}
            <small>L'ID de la fiche POI réclamée pour laquelle cette offre est valable.</small>
        </div>

        <div class="form-group">
            <label>Titre de l'offre *</label>
            <input type="text" name="title" value="{% IF offer %}{{ offer.title }}{% ENDIF %}" required>
            <small>Ex: "Réduction de 10% sur toutes les boissons"</small>
        </div>

        <div class="form-group">
            <label>Avantage *</label>
            <input type="text" name="advantage" value="{% IF offer %}{{ offer.advantage }}{% ENDIF %}" required placeholder="-10% / 1 acheté = 1 offert / Cadeau">
            <small>L'avantage clair et visible (ex: "-10%", "1 acheté = 1 offert")</small>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3">{% IF offer %}{{ offer.description }}{% ENDIF %}</textarea>
            <small>Description détaillée de l'offre (optionnel)</small>
        </div>

        <div class="form-group">
            <label>Conditions</label>
            <input type="text" name="condition_text" value="{% IF offer %}{{ offer.condition_text }}{% ENDIF %}" placeholder="Sur présentation du code / Mention 'Vivre Bourges'">
            <small>Conditions d'utilisation (optionnel)</small>
        </div>

        <div class="form-group">
            <label>Type d'offre *</label>
            <select name="offer_type" id="offer_type" required onchange="toggleOfferTypeFields()">
                <option value="date" {% IF offer AND offer.is_date_type %}selected{% ENDIF %}>Par dates (date de début et date de fin)</option>
                <option value="count" {% IF offer AND offer.is_count_type %}selected{% ENDIF %}>Par décompte (nombre de codes maximum)</option>
            </select>
            <small>Choisissez si l'offre expire à une date ou après un nombre de codes générés</small>
        </div>

        <div class="form-group">
            <label>Date de début *</label>
            <input type="date" name="start_date" id="start_date" value="{% IF offer %}{{ offer.start_date }}{% ENDIF %}" required>
        </div>

        <div class="form-group" id="date_end_group">
            <label>Date de fin *</label>
            <input type="date" name="end_date" id="end_date" value="{% IF offer %}{{ offer.end_date }}{% ENDIF %}">
            <small>Durée maximale : 30 jours</small>
        </div>

        <div class="form-group" id="max_codes_group" style="display: none;">
            <label>Nombre maximum de codes *</label>
            <input type="number" name="max_codes" id="max_codes" value="{% IF offer %}{{ offer.max_codes }}{% ENDIF %}" min="1" placeholder="Ex: 100">
            <small>L'offre sera désactivée automatiquement une fois ce nombre de codes générés</small>
        </div>

        <div class="form-group">
            <label>Lier à un événement</label>
            <select name="event_id">
                <option value="">-- Aucun événement --</option>
                {% FOR event in poiEvents %}
                <option value="{{ event.id }}" {% IF offer AND offer.event_id == event.id %}selected{% ENDIF %}>{{ event.title }}</option>
                {% ENDFOR %}
            </select>
            <small>L'offre sera mise en avant sur la page de cet événement.</small>
        </div>

        <div class="form-group">
            <label>POIs Partenaires (Offre Groupée)</label>
            {% IF isSubscriber %}
                <select name="partner_poi_ids[]" multiple size="5" class="form-control">
                    {% FOR p in allPois %}
                        {% IF p.id != offer.poi_id %}
                        <option value="{{ p.id }}" {% IF offer AND p.id IN offer.partner_poi_ids %}selected{% ENDIF %}>{{ p.name }} (ID: {{ p.id }})</option>
                        {% ENDIF %}
                    {% ENDFOR %}
                </select>
                <small>Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs partenaires. Réservé aux abonnés.</small>
            {% ELSE %}
                <div style="padding: 1rem; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; color: #856404;">
                    <i class="fa-solid fa-star"></i> <strong>Fonctionnalité Premium</strong><br>
                    L'association de plusieurs commerces (offres groupées) est réservée aux abonnés. 
                    <a href="/subscription" style="text-decoration: underline;">En savoir plus sur l'abonnement</a>.
                </div>
            {% ENDIF %}
        </div>

        <script>
        function toggleOfferTypeFields() {
            const offerType = document.getElementById('offer_type').value;
            const dateEndGroup = document.getElementById('date_end_group');
            const maxCodesGroup = document.getElementById('max_codes_group');
            const endDateInput = document.getElementById('end_date');
            const maxCodesInput = document.getElementById('max_codes');
            
            if (offerType === 'date') {
                dateEndGroup.style.display = 'block';
                maxCodesGroup.style.display = 'none';
                endDateInput.required = true;
                maxCodesInput.required = false;
            } else {
                dateEndGroup.style.display = 'none';
                maxCodesGroup.style.display = 'block';
                endDateInput.required = false;
                maxCodesInput.required = true;
            }
        }
        
        // Initialiser au chargement
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', toggleOfferTypeFields);
        } else {
            toggleOfferTypeFields();
        }
        </script>

        <div class="form-actions">
            <button type="submit" class="button primary">Enregistrer</button>
            <a href="{{ cancelUrl }}" class="button secondary">Annuler</a>
        </div>
    </form>
</section>

