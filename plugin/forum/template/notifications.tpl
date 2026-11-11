{% INCLUDE 'forum/breadcrumb.tpl' %}

<section class="forum-notifications">
    <header>
        <h1><i class="fa-solid fa-bell"></i> Mes notifications</h1>
        {% IF unreadCount > 0 %}
        <span class="badge warning">
            {{unreadCount}} non lu{% IF unreadCount > 1 %}s{% ENDIF %}
        </span>
        {% ENDIF %}
    </header>
    
    {% IF notifications %}
    <div class="forum-notifications-list forum-posts-list">
        {% FOR notification IN notifications %}
        <article class="{% IF NOT notification.is_read %}unread{% ENDIF %}">
            <div class="forum-header-row">
                <div class="flex-1">
                    <p>
                        {{notification.message}}
                    </p>
                    <small>
                        <i class="fa-solid fa-calendar"></i> {{notification.created_at_formatted}}
                    </small>
                </div>
                <div class="notification-actions form-actions-row">
                    {% IF NOT notification.is_read %}
                    <form method="POST" action="{{notification.readUrl}}">
                        <input type="hidden" name="token" value="{{csrfToken}}">
                        <button type="submit" class="button success small">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    </form>
                    {% ENDIF %}
                    {% IF notification.topic_url %}
                    <a href="{{notification.topic_url}}" class="button primary small">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    {% ENDIF %}
                </div>
            </div>
        </article>
        {% ENDFOR %}
    </div>
    {% ELSE %}
    <div class="empty-state-forum">
        <i class="fa-solid fa-bell-slash"></i>
        <p>Aucune notification pour le moment.</p>
    </div>
    {% ENDIF %}
</section>

