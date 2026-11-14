<section class="messaging-read">
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>{{message.subject}}</h1>
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{inboxLink}}" class="button">
                    <i class="fa-solid fa-arrow-left"></i> Retour
                </a>
                <form method="post" action="{{deleteLink}}" style="display: inline-block;" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                    <input type="hidden" name="_csrf" value="{{_csrfToken}}">
                    <button type="submit" class="button alert">
                        <i class="fa-solid fa-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </header>
    
    <div class="messaging-read__header">
        <div class="messaging-meta-grid">
            <div class="messaging-meta-label">{% IF message.is_sent %}À :{% ELSE %}De :{% ENDIF %}</div>
            <div>
                {% IF message.is_sent %}
                <strong>{{message.to_user.username}}</strong> ({{message.to_user.email}})
                {% ELSE %}
                <strong>{{message.from_user.username}}</strong> ({{message.from_user.email}})
                {% ENDIF %}
            </div>
            
            <div class="messaging-meta-label">Date :</div>
            <div>{{message.created_at_formatted}}</div>
        </div>
    </div>
    
    <div class="messaging-read__content">
        {{message.content}}
    </div>
    
    <div class="messaging-reply-section">
        <a href="{{composeLink}}?to={% IF message.is_sent %}{{message.to_user.id}}{% ELSE %}{{message.from_user.id}}{% ENDIF %}" 
           class="button success">
            <i class="fa-solid fa-reply"></i> Répondre
        </a>
    </div>
</section>



