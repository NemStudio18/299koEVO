<section>
    <div class="guestbook-tabs">
        <button class="tab-button active" data-tab="messages">{% if messagesTabTitle %}{{ messagesTabTitle }}{% else %}{{ Lang.guestbook.title }}{% endif %}</button>
        <button class="tab-button" data-tab="form">{{ Lang.guestbook.add-message }}</button>
    </div>
    
    <div id="guestbook-messages-tab" class="tab-content active">
        {% if messagesTabContent %}
            <div class="messages-tab-intro">{{ htmlspecialchars_decode(messagesTabContent) }}</div>
        {% endif %}
        {% if count(entries) > 0 %}
            <div class="guestbook-entries">
                {% for entry in entries %}
                    <div class="guestbook-entry">
                        <div class="entry-header">
                            <strong>{{ entry.name }}</strong>
                            <span class="entry-date">{{ entry.date }}</span>
                        </div>
                        <div class="entry-message">{{ htmlspecialchars_decode(entry.message) }}</div>
                        <div class="entry-footer">
                            <a href="{{ entry.likeUrl }}" class="like-button {% if entry.hasLiked %}liked{% endif %}">
                                <i class="fa-solid fa-heart"></i>
                                <span>{{ entry.likesCount }}</span>
                            </a>
                        </div>
                        {% if entry.hasAdminReply %}
                            <div class="admin-reply">
                                <div class="admin-reply-header">
                                    <strong>{% if entry.adminReplyAuthor %}{{ entry.adminReplyAuthor }}{% else %}{{ Lang.guestbook.admin-reply }}{% endif %}</strong>
                                    <span class="admin-reply-date">{{ entry.adminReplyDate }}</span>
                                </div>
                                <div class="admin-reply-message">{{ entry.adminReply }}</div>
                            </div>
                        {% endif %}
                    </div>
                {% endfor %}
            </div>
        {% else %}
            <p>{{ Lang.guestbook.no-messages }}</p>
        {% endif %}
    </div>
    
    <div id="guestbook-form-tab" class="tab-content">
        <form method="post" action="{{ addUrl }}">
            {{ SHOW.tokenField }}
            <input type="hidden" name="_name" value="" />
            
            <label for="name">{{ Lang.guestbook.field-name }}</label>
            <input type="text" name="name" id="name" required />
            
            {% if requireEmail %}
                <label for="email">{{ Lang.guestbook.email }}</label>
                <input type="email" name="email" id="email" required />
            {% else %}
                <label for="email">{{ Lang.guestbook.email }}</label>
                <input type="email" name="email" id="email" />
            {% endif %}
            
            <label for="message">{{ Lang.guestbook.message }}</label>
            <textarea name="message" id="message" required {% if maxLength > 0 %}maxlength="{{ maxLength }}"{% endif %}></textarea>
            {% if maxLength > 0 %}
                <small>{{ Lang.core-remaining }} <span id="chars-remaining">{{ maxLength }}</span> {{ Lang.core-characters }}</small>
            {% endif %}
            
            <div class="rgpd-checkbox">
                <label>
                    <input type="checkbox" name="rgpd_accept" value="1" required />
                    <span>{{ Lang.guestbook.rgpd-accept }}</span>
                </label>
                <small class="rgpd-text">{{ Lang.guestbook.rgpd-text }}</small>
            </div>
            
            <div class="form-submit-row">
                {% if antispam %}
                    <div class="antispam-field">
                        {{ antispam.show() }}
                    </div>
                {% endif %}
                <button type="submit" class="button">{{ Lang.guestbook.submit }}</button>
            </div>
        </form>
    </div>
</section>

{% if maxLength > 0 %}
<script>
document.getElementById('message')?.addEventListener('input', function() {
    const remaining = {{ maxLength }} - this.value.length;
    const span = document.getElementById('chars-remaining');
    if (span) {
        span.textContent = remaining;
        span.style.color = remaining < 0 ? 'red' : '';
    }
});
</script>
{% endif %}

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.guestbook-tabs .tab-button');
    const contents = document.querySelectorAll('.guestbook-tabs + .tab-content, .guestbook-tabs ~ .tab-content');
    
    tabs.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Désactiver tous les onglets
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            
            // Activer l'onglet sélectionné
            this.classList.add('active');
            const targetContent = document.getElementById('guestbook-' + targetTab + '-tab');
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
});
</script>
