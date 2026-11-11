<!-- Bouton flottant Newsletter -->
{% if not isSubscribed %}
<button id="newsletter-floating-btn" class="newsletter-floating-btn button" onclick="{% if isLogged %}document.getElementById('quick-subscribe-form').submit();{% else %}openNewsletterModal(){% endif %}" aria-label="{{ Lang.newsletter.subscribe }}">
    <i class="fa-regular fa-envelope"></i>
    <span>{{ Lang.newsletter.subscribe }}</span>
</button>

<!-- Formulaire caché pour quick subscribe -->
{% if isLogged %}
<form id="quick-subscribe-form" method="post" action="{{ quickSubscribeUrl }}" class="newsletter-hidden">
    {{ SHOW.tokenField }}
</form>
{% endif %}
{% endif %}

    <!-- Modal Newsletter -->
    <div id="newsletter-modal" class="newsletter-modal newsletter-hidden">
        <div class="newsletter-modal-overlay" onclick="closeNewsletterModal()"></div>
        <div class="newsletter-modal-content">
            <div class="newsletter-modal-header">
                <h3>{{ Lang.newsletter.name }}</h3>
                <button class="newsletter-modal-close" onclick="closeNewsletterModal()" aria-label="{{ Lang.cancel }}">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="newsletter-modal-body">
                {{ SHOW.displayMsg }}
                <form method="post" action="{{ subscribeUrl }}" id="newsletter-form">
                    {{ SHOW.tokenField }}
                    <label for="newsletter-email">{{ Lang.newsletter.email }}</label>
                    <input type="email" name="email" id="newsletter-email" placeholder="{{ Lang.newsletter.email-placeholder }}" required />
                    
                    <div class="newsletter-rgpd">
                        <label class="newsletter-rgpd-label">
                            <input type="checkbox" name="rgpd_accept" id="newsletter-rgpd" value="1" required />
                            <span>{{ Lang.newsletter.rgpd-accept }}</span>
                        </label>
                        <small class="newsletter-rgpd-text">{{ Lang.newsletter.rgpd-text }}</small>
                    </div>
                    
                    <button type="submit" class="button">{{ Lang.newsletter.subscribe }}</button>
                </form>
            </div>
        </div>
    </div>



    <script>
    function openNewsletterModal() {
        var modal = document.getElementById('newsletter-modal');
        modal.classList.remove('newsletter-hidden');
        modal.style.display = 'flex'; // Keep flex for layout, but rely on removing hidden for initial state if needed
        document.getElementById('newsletter-email').focus();
        document.body.style.overflow = 'hidden';
    }

    function closeNewsletterModal() {
        var modal = document.getElementById('newsletter-modal');
        modal.classList.add('newsletter-hidden');
        modal.style.display = ''; // Clear inline style
        document.body.style.overflow = '';
    }

    // Fermer la modal en cliquant sur l'overlay
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('newsletter-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal.querySelector('.newsletter-modal-overlay')) {
                    closeNewsletterModal();
                }
            });
        }

        // Fermer avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
                closeNewsletterModal();
            }
        });
    });
    </script>

