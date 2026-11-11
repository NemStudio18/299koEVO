<section class="annonces-wrap">
    <p style="margin-bottom: 1rem;">
        <a href="{{backUrl}}" style="text-decoration: none; font-weight: 600;">
            <i class="fa-solid fa-arrow-left"></i> Retour aux petites annonces
        </a>
    </p>

    <div class="annonce-detail-grid">
        <div class="annonce-detail-box">
            {% if annonce.image %}
                <img src="{{annonce.image}}" alt="{{annonce.title}}" class="annonce-detail-img" />
            {% endif %}

            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span class="annonce-badge annonce-badge-{{annonce.status}}">{{annonce.status_label}}</span>
                {% if annonce.category %}
                    <span style="font-size: 0.86rem; color: #475569;"><i class="fa-solid fa-folder"></i> {{annonce.category}}</span>
                {% endif %}
                {% if annonce.item_condition %}
                    <span style="font-size: 0.86rem; color: #475569;"><i class="fa-solid fa-circle-check"></i> {{annonce.item_condition}}</span>
                {% endif %}
                {% if annonce.location %}
                    <span style="font-size: 0.86rem; color: #475569;"><i class="fa-solid fa-location-dot"></i> {{annonce.location}}</span>
                {% endif %}
            </div>

            <div class="annonce-card-price" style="font-size: 1.35rem; margin-bottom: 14px;">{{annonce.price_label}}</div>

            <div class="annonce-description" style="line-height: 1.65;">
                {{annonce.description}}
            </div>

            {% if annonce.contact_info %}
                <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.9rem; color: #334155;">
                    <strong><i class="fa-solid fa-address-card"></i> Informations complémentaires :</strong> {{annonce.contact_info}}
                </div>
            {% endif %}
        </div>

        <aside class="annonce-reserve-box">
            <h3 style="margin-top: 0; margin-bottom: 10px;"><i class="fa-solid fa-bookmark"></i> {{Lang.annonces.reserve-btn}}</h3>

            {% if annonce.is_available %}
                {% if reservationNotice %}
                    <div class="annonce-notice">
                        <i class="fa-solid fa-circle-info"></i> {{reservationNotice}}
                    </div>
                {% endif %}

                <form method="post" action="{{reserveUrl}}">
                    <input type="hidden" name="_csrf" value="{{_csrfToken}}" />
                    <input type="text" name="_hp_website" value="" style="display:none !important;" tabindex="-1" autocomplete="off" />

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
                        <div style="margin-bottom: 1rem;">
                            {{antispamField}}
                        </div>
                    {% endif %}

                    <button type="submit" class="annonce-card-btn" style="width: 100%; border: none; cursor: pointer;">
                        <i class="fa-solid fa-paper-plane"></i> Envoyer ma demande de réservation
                    </button>
                </form>
            {% elseif annonce.is_reserved %}
                <div class="annonce-notice" style="background: #fffbeb; border-color: #fde68a; color: #92400e;">
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
