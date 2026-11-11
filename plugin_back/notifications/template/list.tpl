{# Template liste notifications #}

<div class="notifications-container">
    <div class="notifications-header">
        <h1>Mes Notifications</h1>
        <div class="notifications-header-actions">
            {% if unreadCount > 0 %}
                <span class="badge" id="unread-count">{{unreadCount}} non lue(s)</span>
                <form method="POST" action="/notifications/mark-all-read">
                    <input type="hidden" name="token" value="{{csrfToken}}" />
                    <button type="submit" class="btn-mark-all">Tout marquer comme lu</button>
                </form>
            {% endif %}
        </div>
    </div>

    {% if notifications|length == 0 %}
        <p class="no-notifications">Aucune notification pour le moment.</p>
    {% else %}
        <div class="notifications-list">
            {% for notif in notifications %}
            <div class="notification {% if notif.is_read == 0 %}unread{% endif %}" id="notif-{{notif.id}}">
                <div class="notif-source">{{notif.source_plugin}}</div>
                <div class="notif-content">
                    <strong>{{notif.title}}</strong>
                    {% if notif.message %}
                        <p>{{notif.message}}</p>
                    {% endif %}
                    {% if notif.link_url %}
                        <a href="{{notif.link_url}}" class="notif-link">Voir le détail</a>
                    {% endif %}
                </div>
                <div class="notif-meta">
                    <span class="notif-time">{{ notif.created_at|date("d/m/Y H:i") }</span>
                    {% if notif.is_read == 0 %}
                        <button class="mark-read" data-id="{{notif.id}}" data-token="{{csrfToken}}">Marquer comme lu</button>
                    {% endif %}
                </div>
            </div>
            {% endfor %}
        </div>
    {% endif %}
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.mark-read').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const token = this.dataset.token;
            const card = document.getElementById('notif-' + id);
            
            // Optimistic UI update
            if (card) {
                card.classList.remove('unread');
                this.remove(); // Remove button
                // Update badge (simple decrement via text content parsing is fragile, doing reliable ajax update is better but expensive. Let's just create a visual update)
                const badge = document.getElementById('unread-count');
                if(badge) {
                    let count = parseInt(badge.textContent);
                    if(count > 0) { 
                        count--; 
                        badge.textContent = count + ' non lue(s)';
                        if(count === 0) badge.style.display = 'none';
                    }
                }
            }
            
            // AJAX call
            fetch('/notifications/mark-read/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + id + '&token=' + token
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Erreur lors du marquage comme lu:', data.error);
                    // Revert if error?
                }
            })
            .catch(error => console.error('Erreur:', error));
        });
    });
});
</script>
