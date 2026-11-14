<section class="messaging-inbox">
    <header>
        <h1><i class="fa-solid fa-inbox"></i> Messagerie</h1>
        <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1rem;">
            <a href="{{composeLink}}" class="button success">
                <i class="fa-solid fa-pen"></i> Nouveau message
            </a>
            {% IF unreadCount > 0 %}
            <span class="badge" style="background: var(--alert-color); color: var(--primary-inverse-color); padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold;">
                {{unreadCount}} non lu{% IF unreadCount > 1 %}s{% ENDIF %}
            </span>
            {% ENDIF %}
        </div>
    </header>
    
    <div class="messaging-inbox__grid">
        <!-- Boîte de réception -->
        <div class="inbox-section">
            <h2 class="messaging-section-title">
                <i class="fa-solid fa-inbox"></i> Boîte de réception
            </h2>
            {% IF inboxMessages %}
            <div class="messaging-list">
                {% for message in inboxMessages %}
                <a href="{{message.read_url}}" class="messaging-item {% IF NOT message.is_read %}messaging-item--unread{% ENDIF %}">
                    <div class="message-header-row">
                        <div>
                            <strong>{{message.from_user.username}}</strong>
                            {% IF NOT message.is_read %}
                            <span class="messaging-badge messaging-badge--new">Nouveau</span>
                            {% ENDIF %}
                        </div>
                        <small style="color: var(--font-secondary);">{{message.created_at_formatted}}</small>
                    </div>
                    <div style="color: var(--font-color-secondary); font-weight: inherit;">
                        {{message.subject}}
                    </div>
                    <div style="color: var(--font-secondary); font-size: 0.875rem; margin-top: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{message.content}}
                    </div>
                </a>
                {% endfor %}
            </div>
            {% ELSE %}
            <div class="messaging-empty-state">
                <i class="fa-solid fa-inbox"></i>
                Aucun message reçu
            </div>
            {% ENDIF %}
        </div>
        
        <!-- Messages envoyés -->
        <div class="sent-section">
            <h2 class="messaging-section-title messaging-section-title--success">
                <i class="fa-solid fa-paper-plane"></i> Messages envoyés
            </h2>
            {% IF sentMessages %}
            <div class="messaging-list">
                {% for message in sentMessages %}
                <a href="{{message.read_url}}" class="messaging-item">
                    <div class="message-header-row">
                        <div>
                            <strong>À : {{message.to_user.username}}</strong>
                            {% IF message.is_read %}
                            <span class="messaging-badge messaging-badge--read">Lu</span>
                            {% ELSE %}
                            <span class="messaging-badge messaging-badge--pending">Non lu</span>
                            {% ENDIF %}
                        </div>
                        <small style="color: var(--font-secondary);">{{message.created_at_formatted}}</small>
                    </div>
                    <div style="color: var(--font-color-secondary);">
                        {{message.subject}}
                    </div>
                    <div style="color: var(--font-secondary); font-size: 0.875rem; margin-top: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{message.content}}
                    </div>
                </a>
                {% endfor %}
            </div>
            {% ELSE %}
            <div class="messaging-empty-state">
                <i class="fa-solid fa-paper-plane"></i>
                Aucun message envoyé
            </div>
            {% ENDIF %}
        </div>
    </div>
</section>

