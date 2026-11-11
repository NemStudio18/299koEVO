<section class="agenda-event-detail">
    <div class="header-actions">
        <a href="{{ ROUTER.generate('agenda-index') }}" class="button small">
            <i class="fa-solid fa-arrow-left"></i> Retour à l'agenda
        </a>
    </div>

    <div class="agenda-event-card detailed">

        {% IF has_image %}
        <div class="event-image" style="width: 100%; max-height: 440px; overflow: hidden; border-radius: 14px; margin-bottom: 22px; background: #0f172a; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 18px rgba(0,0,0,0.08);">
            <img src="{{event_image_url}}" alt="{{event.getTitle()}}" loading="lazy" referrerpolicy="no-referrer" style="max-width: 100%; max-height: 440px; object-fit: contain; display: block;">
        </div>
        {% ENDIF %}

        <h1 class="event-title">{{event.getTitle()}}</h1>

        <div class="event-meta">
            <div class="meta-item">
                <i class="fa-solid fa-calendar"></i> 
                <strong>Du :</strong> {{start_date_display}} 
                <strong>Au :</strong> {{end_date_display}}
            </div>
            {% IF event.getLocation() %}
            <div class="meta-item">
                <i class="fa-solid fa-location-dot"></i> 
                <strong>Lieu :</strong> {{event.getLocation()}}
            </div>
            {% ENDIF %}
        </div>

        {% IF event.getDescription() %}
        <div class="event-description">
            {{event.getDescription()}}
        </div>
        {% ENDIF %}

        {% IF isCgrMaster %}
        <div class="cgr-programming-section" style="margin-top: 32px; padding-top: 28px; border-top: 2px dashed var(--border-color, #e2e8f0);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
                <div>
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: var(--dc-accent-soft, rgba(29, 78, 216, 0.12)); color: var(--primary-color, #1d4ed8); border: 1px solid var(--primary-color, #1d4ed8); padding: 4px 12px; border-radius: 999px; font-size: 0.82rem; font-weight: 700; margin-bottom: 6px;">
                        <i class="fa-solid fa-film"></i> Programmation Cinéma en Direct
                    </span>
                    <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800; color: var(--font-color, #0f172a);">
                        Films à l'affiche ({{cgrCount}} séances)
                    </h2>
                </div>
                <div style="flex: 1; max-width: 320px; min-width: 220px;">
                    <input type="text" id="cgrDetailSearch" placeholder="Rechercher un film..." oninput="filterCgrDetailMovies(this.value)" style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1.5px solid var(--form-border-color, #cbd5e1); font-size: 0.9rem;">
                </div>
            </div>

            <div id="cgrDetailContainer" style="display: flex; flex-direction: column; gap: 18px;">
                {% IF cgrMovies %}
                {% FOR movie in cgrMovies %}
                <article class="cgr-movie-detail-card" data-title="{{movie.title}}" style="display: flex; gap: 20px; background: var(--section-bg-color, #ffffff); border: 1.5px solid var(--border-color, #e2e8f0); border-radius: 16px; padding: 18px; box-shadow: 0 4px 14px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s;">
                    <div style="width: 105px; min-width: 105px; height: 148px; border-radius: 10px; overflow: hidden; position: relative; background: var(--section-head-bg-color, #e2e8f0); flex-shrink: 0;">
                        <img src="{{movie.image}}" alt="{{movie.title}}" loading="lazy" referrerpolicy="no-referrer" style="width: 100%; height: 100%; object-fit: cover;">
                        {% IF movie.tag %}
                        <span style="position: absolute; bottom: 4px; left: 4px; right: 4px; background: rgba(15, 23, 42, 0.88); color: #fef08a; font-size: 0.65rem; font-weight: 700; padding: 2px 4px; border-radius: 4px; text-align: center;">{{movie.tag}}</span>
                        {% ENDIF %}
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                        <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--font-color, #0f172a);">{{movie.title}}</h3>
                        {% IF movie.synopsis %}
                        <p style="margin: 0; font-size: 0.87rem; color: var(--font-secondary, #475569); line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">{{movie.synopsis}}</p>
                        {% ENDIF %}
                        
                        <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 6px;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--font-secondary, #334155);">Séances aujourd'hui :</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                {% IF movie.hours %}
                                {% FOR h in movie.hours %}
                                <a href="{{movie.url}}" target="_blank" rel="noopener" style="background: var(--section-head-bg-color, #f1f5f9); color: var(--font-color, #0f172a); border: 1px solid var(--form-border-color, #cbd5e1); padding: 4px 10px; border-radius: 8px; font-size: 0.85rem; font-weight: 700; text-decoration: none; transition: all 0.2s;">{{h}}</a>
                                {% ENDFOR %}
                                {% ELSE %}
                                <span style="font-size: 0.82rem; color: var(--font-muted, #94a3b8); font-style: italic;">Consulter les horaires sur le site</span>
                                {% ENDIF %}
                            </div>
                        </div>

                        <div style="margin-top: auto; padding-top: 10px; display: flex; justify-content: flex-end;">
                            <a href="{{movie.url}}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.88rem; font-weight: 700; color: var(--primary-color, #1d4ed8); text-decoration: none;">
                                <span>Réserver e-billet</span>
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </div>
                </article>
                {% ENDFOR %}
                {% ELSE %}
                <p style="text-align: center; padding: 30px; color: var(--font-secondary, #64748b);">Programmation en cours de synchronisation avec le cinéma.</p>
                {% ENDIF %}
            </div>
        </div>
        <script>
        function filterCgrDetailMovies(query) {
            var q = (query || '').toLowerCase().trim();
            var cards = document.querySelectorAll('#cgrDetailContainer .cgr-movie-detail-card');
            cards.forEach(function(card) {
                var title = (card.getAttribute('data-title') || '').toLowerCase();
                card.style.display = (!q || title.indexOf(q) !== -1) ? 'flex' : 'none';
            });
        }
        </script>
        {% ENDIF %}

        {% IF linkedOffers %}
        <div class="event-linked-offers">
            <h3><i class="fa-solid fa-gift"></i> Offres spéciales pour cet événement</h3>
            <div class="offers-mini-list">
                {% FOR offer in linkedOffers %}
                <div class="offer-mini-item">
                    <div class="offer-mini-title">{{ offer.title }}</div>
                    <div class="offer-mini-advantage">{{ offer.advantage }}</div>
                    <a href="/offers/view/{{ offer.id }}" class="button small">Voir l'offre</a>
                </div>
                {% ENDFOR %}
            </div>
        </div>
        {% ENDIF %}
        
        <div class="event-footer">
            <div class="event-badges">
                <span class="badge" style="background: {{event.getColor()}};">
                    <i class="fa-solid fa-calendar-check"></i> Événement à venir
                </span>
            </div>

            <div class="event-share-action" style="display: inline-flex; align-items: center; gap: 10px; margin-left: auto; flex-wrap: wrap;">
                <a href="{{ fb_share_url }}" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="btn-facebook-share"
                   style="display: inline-flex; align-items: center; gap: 8px; background: #1877F2; color: #ffffff; padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 8px rgba(24,119,242,0.25);"
                   aria-label="Partager cet événement sur Facebook">
                    <i class="fa-brands fa-facebook" style="font-size: 1.2rem;"></i> Partager sur Facebook
                </a>
                <button type="button" 
                        onclick="vbCopyLink(window.location.href, this)"
                        class="btn-copy-link"
                        style="display: inline-flex; align-items: center; gap: 8px; background: var(--section-bg-color, #ffffff); color: var(--font-color, #475569); border: 1.5px solid var(--form-border-color, #cbd5e1); padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.2s;"
                        title="Copier le lien direct vers cet événement">
                    <i class="fa-solid fa-link"></i> <span>Copier le lien</span>
                </button>
            </div>

            {% IF has_external_url %}
            <div class="event-external-link" style="margin-top: 15px;">
                <a href="{{event_external_url}}" target="_blank" rel="noopener" class="button primary">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Plus d'informations / Billetterie
                </a>
            </div>
            {% ENDIF %}

            {% IF hasMap %}
            <div class="event-map-container" style="margin-top: 24px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
                {{mapHtml}}
            </div>
            {% ENDIF %}
        </div>
    </div>
</section>
