<div class="poi-notifications-container">
    <h1>Mes notifications</h1>
    
    <div style="margin-bottom: 1rem;">
        <button type="button" class="button" onclick="markAllAsRead()">Marquer tout comme lu</button>
    </div>
    
    <!-- Debug: unreadCount = {{ unreadCount }}, notifications count = {% if notifications %}{{ notifications|length }}{% else %}0{% endif %} -->
    
    <div class="poi-notifications-list">
        {% if notifications %}
            {% for notification in notifications %}
            <div class="poi-notification-item {% if notification.is_unread %}unread{% else %}read{% endif %}">
                <div class="poi-notification-header">
                    <div>
                        <span class="poi-notification-type">{{ notification.notification_type_label }}</span>
                        <span class="poi-notification-date">{{ notification.created_at_formatted }}</span>
                    </div>
                    {% if notification.is_unread %}
                    <button type="button" class="button small" onclick="markAsRead({{ notification.id }})">Marquer comme lu</button>
                    {% endif %}
                </div>
                <div class="poi-notification-content">
                    <a href="{{ notification.poi_detail_url }}" class="poi-notification-link">
                        {{ notification.poi_name }}
                    </a>
                    {% if notification.message %}
                        <span class="text-muted"> - {{ notification.message }}</span>
                    {% endif %}
                </div>
            </div>
            {% endfor %}
        {% else %}
            <p class="poi-notification-empty">Aucune notification.</p>
        {% endif %}
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch('{{ ROUTER.generate("poi-favorites-mark-read") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            token: '{{ csrfToken }}',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function markAllAsRead() {
    fetch('{{ ROUTER.generate("poi-favorites-mark-read") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            token: '{{ csrfToken }}',
            notification_id: 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

