<section class="module-card">
    <header>
        <h1>Revendiquer : {{ poi.name }}</h1>
    </header>
    
    {% if hasExistingClaim %}
    <div class="alert alert-info">
        <p>Vous avez déjà une demande de revendication en cours pour cette fiche.</p>
        {% if existingClaim.claim_status == "pending" %}
        <p><strong>Statut :</strong> En attente de validation</p>
        {% if existingClaim.requested_at_formatted %}
        <p><strong>Demandé le :</strong> {{ existingClaim.requested_at_formatted }}</p>
        {% endif %}
        {% elseif existingClaim.claim_status == "approved" %}
        <p><strong>Statut :</strong> Validé</p>
        {% elseif existingClaim.claim_status == "rejected" %}
        <p><strong>Statut :</strong> Rejeté</p>
        {% endif %}
    </div>
    {% else %}
    <form method="post" action="{{ ROUTER.generate("claim-submit") }}" enctype="multipart/form-data">
        <input type="hidden" name="poi_id" value="{{ poi_id }}">
        <input type="hidden" name="id" value="{{ id }}">
        <input type="hidden" name="type" value="{{ type }}">
        
        <div class="form-group">
            <label for="note">Message (optionnel)</label>
            <textarea id="note" name="note" rows="4" placeholder="Expliquez pourquoi vous revendiquez cette fiche..."></textarea>
        </div>
        
        <div class="form-group">
            <label for="proof">Justificatif <span style="color: red;">*</span></label>
            <input type="file" id="proof" name="proof" accept="image/*,.pdf" required>
            <small>Vous devez joindre une photo ou un document justifiant votre revendication (obligatoire).</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="button primary">Envoyer la demande</button>
            <a href="{{ ROUTER.generate("SearchUi-home") }}" class="button">Annuler</a>
        </div>
    </form>
    {% endif %}
</section>
