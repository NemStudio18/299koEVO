<section class="agenda-event-detail">
    <div class="header-actions">
        <a href="{{ ROUTER.generate('agenda-index') }}" class="button small">
            <i class="fa-solid fa-arrow-left"></i> Retour à l'agenda
        </a>
    </div>

    <div class="agenda-event-card detailed">

        {% IF has_image %}
        <div class="event-image">
            <img src="{{event_image_url}}" alt="{{event.getTitle()}}" loading="lazy" referrerpolicy="no-referrer">
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
        <div class="cgr-programming-section">
            <div class="cgr-programming-head">
                <div>
                    <span class="cgr-programming-badge">
                        <i class="fa-solid fa-film"></i> Programmation Cinéma en Direct
                    </span>
                    <h2 class="cgr-programming-title">
                        Films à l'affiche ({{cgrCount}} séances)
                    </h2>
                </div>
                <div class="cgr-search-wrap">
                    <input type="text" id="cgrDetailSearch" class="cgr-search-input" placeholder="Rechercher un film..." oninput="filterCgrDetailMovies(this.value)">
                </div>
            </div>

            <div id="cgrDetailContainer" class="cgr-detail-container">
                {% IF cgrMovies %}
                {% FOR movie in cgrMovies %}
                <article class="cgr-movie-detail-card" data-title="{{movie.title}}">
                    <div class="cgr-movie-poster">
                        <img src="{{movie.image}}" alt="{{movie.title}}" loading="lazy" referrerpolicy="no-referrer">
                        {% IF movie.tag %}
                        <span class="cgr-movie-tag">{{movie.tag}}</span>
                        {% ENDIF %}
                    </div>
                    <div class="cgr-movie-body">
                        <h3 class="cgr-movie-title">{{movie.title}}</h3>
                        {% IF movie.synopsis %}
                        <p class="cgr-movie-synopsis">{{movie.synopsis}}</p>
                        {% ENDIF %}
                        
                        <div class="cgr-movie-showtimes">
                            <span class="cgr-showtimes-label">Séances aujourd'hui :</span>
                            <div class="cgr-showtimes-grid">
                                {% IF movie.hours %}
                                {% FOR h in movie.hours %}
                                <a href="{{movie.url}}" target="_blank" rel="noopener" class="cgr-showtime-chip">{{h}}</a>
                                {% ENDFOR %}
                                {% ELSE %}
                                <span class="cgr-showtimes-fallback">Consulter les horaires sur le site</span>
                                {% ENDIF %}
                            </div>
                        </div>

                        <div class="cgr-movie-cta-wrap">
                            <a href="{{movie.url}}" target="_blank" rel="noopener" class="cgr-movie-cta">
                                <span>Réserver e-billet</span>
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </div>
                </article>
                {% ENDFOR %}
                {% ELSE %}
                <p class="cgr-empty-note">Programmation en cours de synchronisation avec le cinéma.</p>
                {% ENDIF %}
            </div>
        </div>
        <script>
        function filterCgrDetailMovies(query) {
            var q = (query || '').toLowerCase().trim();
            var cards = document.querySelectorAll('#cgrDetailContainer .cgr-movie-detail-card');
            cards.forEach(function(card) {
                var title = (card.getAttribute('data-title') || '').toLowerCase();
                card.classList.toggle('is-hidden', Boolean(q && title.indexOf(q) === -1));
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

            <div class="event-share-action">
                <a href="{{ fb_share_url }}" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="btn-facebook-share"
                   aria-label="Partager cet événement sur Facebook">
                    <i class="fa-brands fa-facebook"></i> Partager sur Facebook
                </a>
                <button type="button" 
                        onclick="vbCopyLink(window.location.href, this)"
                        class="btn-copy-link"
                        title="Copier le lien direct vers cet événement">
                    <i class="fa-solid fa-link"></i> <span>Copier le lien</span>
                </button>
            </div>

            {% IF has_external_url %}
            <div class="event-external-link">
                <a href="{{event_external_url}}" target="_blank" rel="noopener" class="button primary">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Plus d'informations / Billetterie
                </a>
            </div>
            {% ENDIF %}

            {% IF hasMap %}
            <div class="event-map-container">
                {{mapHtml}}
            </div>
            {% ENDIF %}
        </div>
    </div>
</section>
