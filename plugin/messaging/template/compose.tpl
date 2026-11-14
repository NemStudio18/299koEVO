<section class="messaging-compose">
    <header>
        <h1><i class="fa-solid fa-pen"></i> Nouveau message</h1>
        <a href="{{inboxLink}}" class="button button-margin-top">
            <i class="fa-solid fa-arrow-left"></i> Retour à la messagerie
        </a>
    </header>
    
    <form method="post" action="{{sendLink}}" class="messaging-form-container" novalidate>
        <input type="hidden" name="_csrf" value="{{_csrfToken}}">
        
        <div class="messaging-form-group">
            <label for="to_user_id">Destinataire <span class="required-asterisk">*</span></label>
            {% IF toUser %}
            <input type="hidden" name="to_user_id" value="{{toUser.id}}">
            <input type="text" value="{{toUser.username}} ({{toUser.email}})" disabled class="messaging-input-disabled">
            {% ELSE %}
            <select id="to_user_id" name="to_user_id" required>
                <option value="">-- Sélectionner un destinataire --</option>
                {% for user in usersList %}
                <option value="{{user.id}}">{{user.username}} ({{user.email}})</option>
                {% endfor %}
            </select>
            {% ENDIF %}
        </div>
        
        <div class="messaging-form-group">
            <label for="subject">Sujet <span class="required-asterisk">*</span></label>
            <input id="subject" type="text" name="subject" required maxlength="200" placeholder="Entrez le sujet de votre message">
        </div>
        
        <div class="messaging-form-group">
            <label for="content">Message <span style="color: var(--alert-color);">*</span></label>
            <textarea id="content" name="content" class="editor" required rows="10"></textarea>
            <small style="color: var(--font-secondary); display: block; margin-top: 0.25rem;">
                Longueur maximale : 5000 caractères
            </small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="button success">
                <i class="fa-solid fa-paper-plane"></i> Envoyer
            </button>
            <a href="{{inboxLink}}" class="button button-margin-left">
                Annuler
            </a>
        </div>
    </form>
</section>

