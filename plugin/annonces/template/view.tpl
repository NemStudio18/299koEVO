<section class="annonces-wrap">
    <p class="annonce-back-row">
        <a href="{{backUrl}}" class="annonce-back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour aux petites annonces
        </a>
    </p>

    <div class="annonce-detail-grid">
        <div class="annonce-detail-box">
            {% if annonce.image %}
                <div class="annonce-gallery">
                    <a id="annonceMainPhotoLink" href="{{annonce.image}}" data-fancybox="annonce-gallery" data-caption="{{annonce.title}}" class="annonce-main-photo-wrap">
                        <img id="annonceMainPhotoImg" src="{{annonce.image}}" alt="{{annonce.title}}" class="annonce-detail-img" />
                        <span class="annonce-zoom-hint"><i class="fa-solid fa-magnifying-glass-plus"></i> Agrandir</span>
                    </a>
                    {% if count(annonce.images) > 1 %}
                        <div class="annonce-thumbs-strip">
                            {% for img in annonce.images %}
                                <a href="{{img}}" data-fancybox="annonce-gallery" data-caption="{{annonce.title}}" class="annonce-thumb-item{% if img == annonce.image %} active{% endif %}" onclick="annonceSwitchPhoto('{{img}}', this, event)">
                                    <img src="{{img}}" alt="{{annonce.title}}" loading="lazy" />
                                </a>
                            {% endfor %}
                        </div>
                    {% endif %}
                </div>
            {% endif %}

            <div class="annonce-detail-badges">
                <span class="annonce-badge annonce-badge-{{annonce.status}}">{{annonce.status_label}}</span>
                {% if annonce.category %}
                    <span class="annonce-detail-meta"><i class="fa-solid fa-folder"></i> {{annonce.category}}</span>
                {% endif %}
                {% if annonce.item_condition %}
                    <span class="annonce-detail-meta"><i class="fa-solid fa-circle-check"></i> {{annonce.item_condition}}</span>
                {% endif %}
                {% if annonce.location %}
                    <span class="annonce-detail-meta"><i class="fa-solid fa-location-dot"></i> {{annonce.location}}</span>
                {% endif %}
            </div>

            <div class="annonce-card-price annonce-detail-price">{{annonce.price_label}}</div>

            <div class="annonce-description">
                {{annonce.description}}
            </div>

            {% if annonce.contact_info %}
                <div class="annonce-detail-contact">
                    <strong><i class="fa-solid fa-address-card"></i> Informations complémentaires :</strong> {{annonce.contact_info}}
                </div>
            {% endif %}
        </div>

        <aside class="annonce-reserve-box">
            <h3><i class="fa-solid fa-bookmark"></i> {{Lang.annonces.reserve-btn}}</h3>

            {% if annonce.is_available %}
                {% if reservationNotice %}
                    <div class="annonce-notice">
                        <i class="fa-solid fa-circle-info"></i> {{reservationNotice}}
                    </div>
                {% endif %}

                <form method="post" action="{{reserveUrl}}">
                    <input type="hidden" name="_csrf" value="{{_csrfToken}}" />
                    <input type="text" name="_hp_website" value="" class="annonce-hp-field" tabindex="-1" autocomplete="off" />

                    <p>
                        <label for="res_name">Nom ou pseudo *</label>
                        <input type="text" id="res_name" name="requester_name" required maxlength="150" />
                    </p>
                    <p>
                        <label for="res_email">Adresse email *</label>
                        <input type="email" id="res_email" name="requester_email" required maxlength="180" />
                    </p>
                    <p>
                        <label for="res_phone">Téléphone (optionnel)</label>
                        <input type="text" id="res_phone" name="requester_phone" maxlength="50" />
                    </p>
                    <p>
                        <label for="res_msg">Message / disponibilités *</label>
                        <textarea id="res_msg" name="message" rows="4" required placeholder="Précisez vos disponibilités ou questions…"></textarea>
                    </p>

                    {% if antispam %}
                        <div class="annonce-antispam-wrap">
                            {{antispamField}}
                        </div>
                    {% endif %}

                    <button type="submit" class="annonce-card-btn annonce-submit-btn">
                        <i class="fa-solid fa-paper-plane"></i> Envoyer ma demande de réservation
                    </button>
                </form>
            {% elseif annonce.is_reserved %}
                <div class="annonce-notice annonce-notice-reserved">
                    <i class="fa-solid fa-lock"></i> Cette annonce est actuellement <strong>réservée</strong>. Aucune nouvelle demande ne peut être envoyée pour le moment.
                </div>
            {% else %}
                <div class="annonce-notice">
                    <i class="fa-solid fa-box-archive"></i> Cette annonce est <strong>clôturée</strong>.
                </div>
            {% endif %}
        </aside>
    </div>
</section>

<script>
function annonceSwitchPhoto(url, el, ev) {
    if (ev) {
        ev.preventDefault();
    }
    const mainImg = document.getElementById('annonceMainPhotoImg');
    const mainLink = document.getElementById('annonceMainPhotoLink');
    if (mainImg) mainImg.src = url;
    if (mainLink) mainLink.href = url;
    document.querySelectorAll('.annonce-thumb-item').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');
}
</script>

