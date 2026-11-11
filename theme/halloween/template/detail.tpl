<section class="search-detail{% if poi.has_background_color %} padding-20 border-radius-8{% endif %}" {% if poi.has_background_color %}style="background-color: {{poi.background_color}};"{% endif %}>
{# Photo de couverture déplacée dans l'onglet Infos #}

{% if hasLocalFeed %}
<link rel="stylesheet" href="/plugin/LocalFeed/public/css/localfeed.css?v=3">
<script src="/plugin/LocalFeed/public/js/localfeed.js?v=3" defer></script>
{% endif %}

    <header class="detail-header">
        <div class="detail-header-top">
        {% if isEmbed %}
        <button type="button" class="back-link detail-back-button" onclick="document.getElementById('poi-detail-container').style.display='none'; document.getElementById('pois-results-container').style.display='block'; window.history.back();">
                <i class="fa-solid fa-arrow-left"></i> <span class="back-link-text">Retour aux résultats</span>
        </button>
        {% else %}
        <a href="{{ ROUTER.generate("SearchUi-home") }}" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> <span class="back-link-text">Retour à la recherche</span>
        </a>
        {% endif %}
            <div class="detail-header-actions">
                {# DEBUG: Roles context for header buttons (v2) #}
                <!-- DEBUG: IS_ADMIN={{ IS_ADMIN }}, IS_OWNER={{ IS_OWNER }}, IS_LOGGED={{ IS_LOGGED }} -->

                {# 
                   Centralised Header Actions via ConversionOrchestrator.
                   This hook determines which buttons to show (Signaler, S'abonner, Corriger, etc.)
                   depending on whether the POI is claimed and what the user's role is.
                   Admins and Owners see nothing here as they have their own section below.
                #}
                {% HOOK.poi:detail-actions(['poi' => poi, 'is_admin' => IS_ADMIN, 'is_owner' => IS_OWNER, 'is_logged' => IS_LOGGED]) %}
            </div>
        </div>
        <div class="detail-header-row">
            {% if poi.has_logo %}
            <div class="detail-logo">
                <img src="{{poi.logo}}" alt="Logo {{poi.name}}" class="detail-logo img">
            </div>
            {% endif %}
            <div class="detail-header-content">
                <!-- Badges de catégorie & statuts (Mobile First) -->
                <div class="detail-category-strip">
                    {% if poi.display_category_name %}
                    <span class="poi-category-pill">
                        <i class="{{poi.display_category_icon}}"></i>
                        <span>{{poi.display_category_name}}</span>
                    </span>
                    {% endif %}
                    {% if poi.display_subcategory_name %}
                    <span class="poi-subcategory-pill">
                        <span>{{poi.display_subcategory_name}}</span>
                    </span>
                    {% endif %}
                    {% if poi.claimed_by %}
                    <span class="certified-badge detail-certified-badge" title="Établissement géré par son propriétaire">
                        <i class="fa-solid fa-circle-check"></i> Certifié
                    </span>
                    {% endif %}
                    {% if poi.is_active_this_month %}
                    <span class="active-badge detail-active-badge">
                        <i class="fa-solid fa-fire"></i> Actif ce mois-ci
                    </span>
                    {% endif %}
                </div>

                <!-- Nom / Titre principal du POI -->
                <h1 class="poi-main-title">
                    {{poi.name}}
                    {% if poi.stars_html %}
                    <span class="detail-stars">{{poi.stars_html|raw}}</span>
                    {% endif %}
                </h1>

                <!-- Avis sous le titre si existants -->
                {% if isReviewsActive and averageRating > 0 %}
                <div class="poi-header-rating-row">
                    <a href="#poi-reviews-section" class="header-rating-link detail-rating-link">
                        <span class="detail-stars-wrapper">
                            {% for star in averageStars %}
                                {% if star.isFull %}
                                    <i class="fa-solid fa-star detail-star-icon"></i>
                                {% elseif star.isHalf %}
                                    <i class="fa-solid fa-star-half-stroke detail-star-icon"></i>
                                {% else %}
                                    <i class="fa-regular fa-star detail-star-icon"></i>
                                {% endif %}
                            {% endfor %}
                        </span>
                        <span class="detail-rating-text">{{averageRating}}/5</span>
                        <span class="detail-rating-count">({{ratingCount}} avis)</span>
                    </a>
                </div>
                {% endif %}

                <!-- Adresse synthétique avec icône géolocalisation -->
                {% if poi.address %}
                <div class="poi-header-location-row">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>{{poi.address}}</span>
                </div>
                {% elseif poi.addr_street %}
                <div class="poi-header-location-row">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>{{poi.addr_street}}{% if poi.addr_city %}, {{poi.addr_city}}{% endif %}</span>
                </div>
                {% endif %}

                <!-- Barre d'Actions Tactiles Rapides (Quick Actions Bar) -->
                <div class="poi-quick-actions-bar">
                    {% if poi.phone %}
                    <a href="tel:{{poi.phone}}" class="quick-action-btn quick-action-call">
                        <i class="fa-solid fa-phone"></i>
                        <span>Appeler</span>
                    </a>
                    {% endif %}
                    {% if poi.lat and poi.lon %}
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{poi.lat}},{{poi.lon}}" target="_blank" rel="noopener" class="quick-action-btn quick-action-gps">
                        <i class="fa-solid fa-diamond-turn-right"></i>
                        <span>Itinéraire</span>
                    </a>
                    {% elseif poi.address or poi.addr_street %}
                    <a href="https://www.google.com/maps/search/?api=1&query={{poi.name|url_encode}}+{{poi.addr_city|default('Bourges')|url_encode}}" target="_blank" rel="noopener" class="quick-action-btn quick-action-gps">
                        <i class="fa-solid fa-diamond-turn-right"></i>
                        <span>Itinéraire</span>
                    </a>
                    {% endif %}
                    {% if poi.website %}
                    <a href="{{poi.website}}" target="_blank" rel="noopener" class="quick-action-btn quick-action-web">
                        <i class="fa-solid fa-globe"></i>
                        <span>Site web</span>
                    </a>
                    {% endif %}
                    <button type="button" class="quick-action-btn quick-action-share" onclick="if(navigator.share){navigator.share({title:'{{poi.name|escape('js')}}',url:window.location.href});}else{navigator.clipboard.writeText(window.location.href);alert('Lien copié dans le presse-papier !');}">
                        <i class="fa-solid fa-share-nodes"></i>
                        <span>Partager</span>
                    </button>
                    <button type="button" class="quick-action-btn quick-action-copy" onclick="vbCopyLink(window.location.href, this)" title="Copier le lien de cet établissement">
                        <i class="fa-solid fa-link"></i>
                        <span>Copier</span>
                    </button>
                    <a href="{{ fb_share_url }}" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="quick-action-btn quick-action-fb" 
                       title="Partager sur Facebook"
                       aria-label="Partager cet établissement sur Facebook">
                        <i class="fa-brands fa-facebook" style="color: #1877F2;"></i>
                        <span>Facebook</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    {% if IS_ADMIN or IS_OWNER %}
        <!-- Manager Action Section: rôle-séparée -->
        <section class="detail-section owner-section detail-manager-section">
            <div class="detail-owner-wrapper">
                <div>
                    <span class="owner-badge detail-owner-badge">
                        <i class="fa-solid fa-crown"></i>
                        {% if IS_SIMULATING_OWNER %}
                            Simulation Propriétaire
                        {% elseif IS_ADMIN %}
                            Administration
                        {% else %}
                            Espace Propriétaire
                        {% endif %}
                    </span>
                    <h3 class="owner-title">Outils de gestion disponibles</h3>
                </div>
                <div class="owner-actions detail-owner-actions">

                    {# === BLOC ADMIN (non-simulation) : routes admin, (A) préfixés === #}
                    {% if IS_ADMIN and not IS_SIMULATING_OWNER %}
                        {# Bouton de test pour vérifier la portée #}
                        <!-- DEBUG: IS_ADMIN is TRUE, Admin block reached -->

                        {% if poi.admin_edit_url %}
                        <a href="{{ poi.admin_edit_url }}" class="button primary small owner-admin-btn">
                            <i class="fa-solid fa-wrench"></i> (A) Éditer la fiche
                        </a>
                        {% endif %}

                        <a href="{{ adminEventsUrl|default('/admin/agenda/events/add') }}?poi_id={{ poi.id }}" class="button secondary small owner-admin-btn">
                            <i class="fa-solid fa-calendar-plus"></i> (A) Créer Événement
                        </a>

                        <a href="{{ adminOffersUrl|default('/admin/offers/add') }}?poi_id={{ poi.id }}" class="button secondary small owner-admin-btn">
                            <i class="fa-solid fa-tag"></i> (A) Créer Offre
                        </a>

                        {% if isQrPoiActive %}
                        <a href="{{ adminQrUrl|default('/admin/qr-poi/generate/' ~ poi.id) }}" class="button secondary small owner-admin-btn">
                            <i class="fa-solid fa-qrcode"></i> (A) QR Code
                        </a>
                        {% endif %}

                        <a href="{{ poi.detail_url_short }}?sim_owner=1" class="button small btn-sim-owner" style="background-color: #17a2b8; color: white;">
                            <i class="fa-solid fa-eye"></i> Simuler la vue Propriétaire
                        </a>
                    {% endif %}

                    {# === BLOC PROPRIÉTAIRE (owner réel ou admin en simulation) : routes publiques === #}
                    {% if IS_OWNER or IS_SIMULATING_OWNER %}
                        {# Badge Établissement Certifié #}
                        {% if poi.claimed_by %}
                        <span class="button small" style="background: #2ecc71; color: white; cursor: default;">
                            <i class="fa-solid fa-circle-check"></i> Établissement certifié
                        </span>
                        {% endif %}

                        {% if poi.edit_url %}
                        <a href="{{ poi.edit_url }}" class="button primary small">
                            <i class="fa-solid fa-edit"></i> Modifier ma fiche
                        </a>
                        {% endif %}

                        <a href="{{ ownerEventsUrl }}?poi_id={{ poi.id }}" class="button secondary small">
                            <i class="fa-solid fa-calendar-plus"></i> Créer Événement
                        </a>

                        <a href="{{ ownerOffersUrl }}?poi_id={{ poi.id }}" class="button secondary small">
                            <i class="fa-solid fa-tag"></i> Créer Offre
                        </a>

                        {% if isQrPoiActive %}
                        <a href="{{ ownerQrUrl }}" class="button secondary small">
                            <i class="fa-solid fa-qrcode"></i> Gérer le QR Code
                        </a>
                        {% endif %}

                        {% if hasLocalFeed %}
                        <button type="button" class="button secondary small" onclick="const t = document.querySelector('[data-tab=\'actualites\']'); if(t){t.click(); t.scrollIntoView({behavior:'smooth'});}">
                            <i class="fa-solid fa-bullhorn" style="color: #ff8c42;"></i> Publier sur mon Mur
                        </button>
                        {% endif %}

                        {# Bouton quitter simulation (visible uniquement si admin simule) #}
                        {% if IS_SIMULATING_OWNER %}
                        <a href="{{ poi.detail_url_short }}?sim_owner=0" class="button small" style="background-color: #ff9800; color: white;">
                            <i class="fa-solid fa-eye-slash"></i> Quitter la simulation
                        </a>
                        {% endif %}
                    {% endif %}

                </div>
            </div>
        </section>
    {% endif %}

    <!-- Tabs Navigation -->
    <div class="detail-tabs-nav" role="tablist">
        <button type="button" class="detail-tab-button active" role="tab" aria-selected="true" data-tab="info">
            <i class="fa-solid fa-circle-info"></i> <span>Infos</span>
        </button>
        
        {% if hasDescription %}
        <button type="button" class="detail-tab-button" role="tab" aria-selected="false" data-tab="description">
            <i class="fa-solid fa-file-lines"></i> <span>Description</span>
        </button>
        {% endif %}

        {% if hasPhotos %}
        <button type="button" class="detail-tab-button" role="tab" aria-selected="false" data-tab="galerie">
            <i class="fa-solid fa-images"></i> <span>Galerie</span>
        </button>
        {% endif %}

        {# Agenda tab - always visible if plugin is active #}
        {% if isAgendaActive %}
        <button type="button" class="detail-tab-button" role="tab" aria-selected="false" data-tab="agenda">
            <i class="fa-solid fa-calendar-days"></i> <span>Agenda</span>
            {% if poiEvents %}
            <span class="tab-badge">{{poiEvents|length}}</span>
            {% endif %}
        </button>
        {% endif %}

        {# Offres tab - always visible if plugin is active #}
        {% if isOffersActive %}
        <button type="button" class="detail-tab-button" role="tab" aria-selected="false" data-tab="offres">
            <i class="fa-solid fa-tag"></i> <span>Offres</span>
        </button>
        {% endif %}

        {% if isReviewsActive or hasReviews %}
        <button type="button" class="detail-tab-button" role="tab" aria-selected="false" data-tab="avis">
            <i class="fa-solid fa-star"></i> <span>Avis</span>
        </button>
        {% endif %}

        {# Tab: Les Bons Plans LBP (Anti-Gaspi & Click & Collect) #}
        {% if hasGoodDeals %}
        <button type="button" class="detail-tab-button" role="tab" aria-selected="false" data-tab="bons-plans">
            <i class="fa-solid fa-basket-shopping" style="color: #f59e0b;"></i> <span>Bons Plans (LBP)</span>
            {% if goodDealsCount > 0 %}
            <span class="tab-badge" style="background: #f59e0b; color: #1e1b2e;">{{goodDealsCount}}</span>
            {% endif %}
        </button>
        {% endif %}

        {# Tab: Actualités & Mur Social #}
        {% if hasLocalFeed %}
        <button type="button" class="detail-tab-button" role="tab" aria-selected="false" data-tab="actualites">
            <i class="fa-solid fa-bullhorn"></i> <span>Actualités &amp; Mur</span>
            {% if poiPostsCount > 0 %}
            <span class="tab-badge">{{poiPostsCount}}</span>
            {% endif %}
        </button>
        {% endif %}
    </div>

    <div class="detail-content">
        <!-- Tab: Info Pratique -->
        <div class="detail-tab-pane active" id="tab-info" role="tabpanel">
            <div class="detail-info-grid">
                <div class="detail-info-left">
                    <section class="detail-section">
                        <div class="header-section-detail">
                            <h2>Informations pratiques</h2>
                            <span id="poi-distance-header" class="poi-distance-display">
                                <i class="fa-solid fa-route"></i> <span class="poi-distance-value"></span>
                            </span>
                        </div>
                        <dl class="detail-info-list">
                            {% if poi.has_address %}
                            <div class="info-item">
                                <dt><i class="fa-solid fa-location-dot"></i></dt>
                                <dd>
                                    {% if poi.address %}
                                        {{poi.address}}
                                    {% else %}
                                        <span class="info-placeholder">Non renseigné</span>
                                    {% endif %}
                                </dd>
                            </div>
                            {% endif %}

                            {% if poi.has_phone %}
                            <div class="info-item">
                                <dt><i class="fa-solid fa-phone"></i></dt>
                                <dd><a href="tel:{{poi.phone}}" class="phone-link">{{poi.phone}}</a></dd>
                            </div>
                            {% endif %}

                            {% if poi.has_website %}
                            <div class="info-item">
                                <dt><i class="fa-solid fa-globe"></i></dt>
                                <dd><a href="{{poi.website}}" target="_blank" rel="noopener" class="website-link" title="Visiter le site internet">{{poi.website}}</a></dd>
                            </div>
                            {% endif %}

                            {% if poi.has_email %}
                            <div class="info-item">
                                <dt><i class="fa-solid fa-envelope"></i></dt>
                                <dd><a href="mailto:{{poi.email}}" class="email-link">{{poi.email}}</a></dd>
                            </div>
                            {% endif %}

                            {% if poi.has_opening_hours or poi.has_opening_hours_human %}
                            <div class="info-item">
                                <dt><i class="fa-solid fa-clock"></i></dt>
                                <dd>
                                    <div class="opening-status-wrapper">
                                        {{ poi.is_open_now_html|raw }}
                                    </div>
                                    {% if poi.has_opening_hours_human %}
                                        <div class="opening-hours-formatted">
                                            {% for hour in poi.opening_hours_human %}
                                                <div class="hour-line">{{hour}}</div>
                                            {% endfor %}
                                        </div>
                                    {% else %}
                                        {{poi.opening_hours}}
                                    {% endif %}
                                </dd>
                            </div>
                            {% endif %}
                            
                            <!-- Additional info ... -->
                            {% if poi.wheelchair == "yes" or poi.wheelchair == "limited" %}
                            <div class="info-item">
                                <dt><i class="fa-solid fa-wheelchair"></i></dt>
                                <dd>Accessible PMR{% if poi.wheelchair == "limited" %} (Limité){% endif %}</dd>
                            </div>
                            {% endif %}

                            {% if poi.parking == "yes" %}
                            <div class="info-item">
                                <dt><i class="fa-solid fa-square-p"></i></dt>
                                <dd>Parking disponible</dd>
                            </div>
                            {% endif %}

                            {% if poi.has_internet_access %}
                            <div class="info-item">
                                <dt><i class="fa-solid fa-wifi"></i></dt>
                                <dd>{% if poi.internet_access == "wlan" or poi.internet_access == "wifi" %}WiFi{% else %}Internet{% endif %}</dd>
                            </div>
                            {% endif %}
                        </dl>
                        
                        {% if poi.has_links %}
                        <div class="detail-social-links">
                            {% for link in poi.links %}
                            <a href="{{link.url}}" target="_blank" rel="noopener" class="social-link-button" title="{{ link.label|default("Lien") }}">
                                <i class="{{link.icon_class}}"></i>
                            </a>
                            {% endfor %}
                        </div>
                        {% endif %}

                        {% if poi.has_cover %}
                        <div class="detail-cover-infos-section" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                            <img src="{{poi.cover_url}}" alt="{{poi.name}}" class="detail-info-photo" style="cursor: pointer; max-width: 100%; border-radius: 8px;" onclick="window.openLightbox(this.src, '{{poi.name|escape('js')}}')">
                        </div>
                        {% endif %}
                    </section>
                </div>
                
                <div class="detail-info-right">
                    {% if CORE.callHook('map:is-enabled') and poi.lat and poi.lon %}
                    <section class="detail-section no-margin">
                        <div class="detail-map-wrapper">
                            <div class="detail-map" id="poi-map" 
                                 data-lat="{{poi.lat}}" 
                                 data-lon="{{poi.lon}}" 
                                 data-name="{{poi.name_escaped}}"
                                 data-color="{{poi.background_color}}"
                                 data-icon="{{poi.category_icon|default('location-dot')}}"></div>
                            <div class="map-overlay-actions">
                                <a href="https://www.google.com/maps/dir/?api=1&destination={{poi.lat}},{{poi.lon}}" target="_blank" class="button primary small">
                                    <i class="fa-solid fa-location-arrow"></i> Itinéraire
                                </a>
                            </div>
                        </div>
                    </section>
                    {% endif %}

                    {% if isQrPoiActive and showQrCode and qrCodeUrl %}
                    <section class="detail-section poi-qr-code-section">
                        <div class="qr-container-mini">
                            <img src="{{qrCodeUrl}}" alt="QR Code" class="qr-image">
                            <p class="qr-text-mini">Accès mobile rapide</p>
                        </div>
                    </section>
                    {% endif %}
                </div>
            </div>
        </div>

        <!-- Tab: Description -->
        {% if hasDescription %}
        <div class="detail-tab-pane" id="tab-description" role="tabpanel">
            <section class="detail-section">
                <h2>Description</h2>
                <div class="detail-description">{{poi.description|raw}}</div>
                
                {% if poi.custom_tags %}
                <div class="detail-tags" style="margin-top: 20px;">
                    {% for customTag in poi.custom_tags %}
                    <span class="tag">
                        {% if customTag.has_emoji %}<span class="tag-emoji">{{customTag.emoji}}</span>{% endif %}
                        <strong>{{customTag.label}}</strong>
                    </span>
                    {% endfor %}
                </div>
                {% endif %}
            </section>
        </div>
        {% endif %}

        <!-- Tab: Galerie -->
        {% if hasPhotos %}
        <div class="detail-tab-pane" id="tab-galerie" role="tabpanel">
            <section class="detail-section">
                <h2>Photos</h2>
                <div class="detail-photos-gallery-grid">
                    {% for photo in poi.photos %}
                    <div class="photo-thumb-card" onclick="window.openLightbox('{{photo.url}}', '{{photo.caption|escape('js')}}')" style="position: relative;">
                        <img src="{{photo.url}}" alt="{{photo.caption}}" loading="lazy">
                        {% if photo.is_verified %}
                        <div class="verified-photo-badge" title="Photo vérifiée par EXIF (Date & GPS)" style="position: absolute; top: 8px; left: 8px; background: rgba(46, 204, 113, 0.9); color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: bold; display: flex; align-items: center; gap: 4px; z-index: 2; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            <i class="fa-solid fa-camera" style="font-size: 0.6rem;"></i> Vérifiée
                        </div>
                        {% endif %}
                        {% if photo.caption %}<div class="photo-thumb-overlay"><i class="fa-solid fa-magnifying-glass-plus"></i></div>{% endif %}
                    </div>
                    {% endfor %}
                </div>
            </section>
        </div>
        {% endif %}

        <!-- Lightbox Modal -->
        <div id="poi-lightbox" class="poi-lightbox-modal" onclick="this.style.display='none'">
            <span class="lightbox-close">&times;</span>
            <img class="lightbox-content" id="lightbox-img">
            <div id="lightbox-caption"></div>
        </div>

        <script>
        window.openLightbox = function(src, caption) {
            const modal = document.getElementById('poi-lightbox');
            const modalImg = document.getElementById('lightbox-img');
            const captionText = document.getElementById('lightbox-caption');
            modal.style.display = "flex";
            modalImg.src = src;
            captionText.innerHTML = caption || "";
        };
        </script>

        <!-- Tab: Agenda -->
        {% if isAgendaActive %}
        <div class="detail-tab-pane" id="tab-agenda" role="tabpanel">
            <section class="detail-section">
                <div class="section-title-wrap" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 10px;">
                    <h2 style="margin: 0;"><i class="fa-solid fa-calendar-days" style="color: #ff8c42;"></i> Agenda &amp; Événements</h2>
                    {% if isOwner or isAdmin %}
                    <a href="{{ownerEventsUrl}}?poi_id={{poi.id}}" class="button-search button btn-sm" style="font-size: 0.85rem;"><i class="fa-solid fa-plus"></i> <span class="button-text">Ajouter un événement</span></a>
                    {% endif %}
                </div>
                {% if poiEvents %}
                    <div class="poi-agenda-grid">
                        {% for event in poiEvents %}
                        <div class="poi-agenda-card" style="border-left: 4px solid {{event.color}};">
                            <div class="poi-agenda-dates">
                                <span class="date-badge"><i class="fa-solid fa-calendar"></i> {{event.start_date_display}}{% if event.end_date_display != event.start_date_display %} &rarr; {{event.end_date_display}}{% endif %}</span>
                            </div>
                            <h3 class="poi-agenda-title"><a href="{{event.url}}">{{event.title}}</a></h3>
                            {% if event.location %}
                            <p class="poi-agenda-location"><i class="fa-solid fa-location-dot"></i> {{event.location}}</p>
                            {% endif %}
                            {% if event.description %}
                            <p class="poi-agenda-desc">{{event.description|slice(0, 160)}}...</p>
                            {% endif %}
                            <a href="{{event.url}}" class="poi-agenda-link">Voir la fiche événement <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        {% endfor %}
                    </div>
                {% else %}
                    <div class="empty-tab-state">
                        <i class="fa-solid fa-calendar-alt empty-icon"></i>
                        <p>Aucun événement prévu prochainement pour cet établissement.</p>
                    </div>
                {% endif %}
            </section>
        </div>
        {% endif %}

        <!-- Tab: Offres -->
        {% if isOffersActive %}
        <div class="detail-tab-pane" id="tab-offres" role="tabpanel">
            <section class="detail-section">
                <h2>Offres et bons plans</h2>
                {% if activeOffers %}
                    <div class="offers-list">
                        {% for offer in activeOffers %}
                        <div class="offer-card">
                            <div class="offer-header">
                                <h3 class="offer-title">{{offer.title}}</h3>
                                <span class="offer-badge">{{offer.advantage}}</span>
                            </div>
                            {% if offer.description %}<p class="offer-desc">{{offer.description}}</p>{% endif %}
                            <div class="offer-footer">
                                <div class="offer-meta">
                                    {% if offer.is_date_type %}
                                        <i class="fa-solid fa-calendar"></i> Jusqu'au {{offer.end_date_formatted}}
                                    {% elseif offer.is_count_type %}
                                        <i class="fa-solid fa-hashtag"></i> {{offer.max_codes}} codes max
                                    {% endif %}
                                </div>
                                {% if IS_LOGGED %}
                                    <a href="{{offer.viewUrl}}" class="button primary small offer-btn">Voir l'offre</a>
                                {% else %}
                                    <div class="offer-guest-friction">
                                        <a href="/users/login?msg=subscribe&poi_name={{ poi.name|url_encode }}&return_url={{ REQUEST_URI|url_encode }}" class="button primary small offer-btn ghost">Connectez-vous pour voir le code</a>
                                    </div>
                                {% endif %}
                            </div>
                        </div>
                        {% endfor %}
                    </div>
                {% else %}
                    <div class="empty-tab-state">
                        <i class="fa-solid fa-tags empty-icon"></i>
                        <p>Il n'y a pas d'offres promotionnelles en cours pour le moment.</p>
                    </div>
                {% endif %}
            </section>
        </div>
        {% endif %}

        <!-- Tab: Les Bons Plans LBP (Anti-Gaspi) -->
        {% if hasGoodDeals %}
        <div class="detail-tab-pane" id="tab-bons-plans" role="tabpanel">
            <section class="detail-section">
                <div class="header-section-detail" style="margin-bottom: 16px;">
                    <h2><i class="fa-solid fa-basket-shopping" style="color: #f59e0b;"></i> Les Bons Plans &amp; Déstockage</h2>
                    {% if isOwner or IS_ADMIN %}
                    <a href="/dashboard-lbp" class="btn btn-sm" style="background: #fef3c7; color: #b45309; font-weight: 700; text-decoration: none; padding: 5px 12px; border-radius: 6px; font-size: 0.85rem;">
                        <i class="fa-solid fa-gear"></i> Gérer mes bons plans
                    </a>
                    {% endif %}
                </div>
                {{ goodDealsHtml|raw }}
            </section>
        </div>
        {% endif %}

        <!-- Tab: Actualités & Mur Social -->
        {% if hasLocalFeed %}
        <div class="detail-tab-pane" id="tab-actualites" role="tabpanel">
            <section class="detail-section">
                {{ localFeedHtml|raw }}
            </section>
        </div>
        {% endif %}

        <!-- Tab: Avis -->
        {% if isReviewsActive or hasReviews %}
        <div class="detail-tab-pane" id="tab-avis" role="tabpanel">
            <section class="detail-section" id="poi-reviews-section">
                <h2>Avis et commentaires</h2>
                <div class="reviews-container">
                    <!-- Formulaire -->
                    <div class="reviews-form-wrapper">
                        {% if IS_LOGGED %}
                            {% if userReview == null or not userReview %}
                            <div class="poi-reviews-form-embed">
                                <h3 class="review-form-title">Votre avis nous intéresse</h3>
                                <form method="POST" action="{{ addReviewUrl }}">
                                    <input type="hidden" name="token" value="{{ csrfToken }}">
                                    <input type="hidden" name="poi_id" value="{{ poi.id }}">
                                    <div class="form-group">
                                        <div class="poi-reviews-rating-input">
                                            {% for i in range(5, 1, -1) %}
                                            <input type="radio" id="rating{{i}}" name="rating" value="{{i}}" {% if i == 5 %}required{% endif %}>
                                            <label for="rating{{i}}"><i class="fa-solid fa-star"></i></label>
                                            {% endfor %}
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <textarea name="comment" class="form-control" placeholder="Racontez-nous votre visite..."></textarea>
                                    </div>
                                    <button type="submit" class="button primary w-100">Publier mon avis</button>
                                </form>
                            </div>
                            {% else %}
                            <div class="review-status success">
                                <i class="fa-solid fa-check-circle"></i> Merci pour votre contribution !
                            </div>
                            {% endif %}
                        {% else %}
                        <div class="review-status info">
                            <p>Vous avez visité ce lieu ? <a href="{{ actionUrls.favorite }}">Connectez-vous</a> pour laisser un avis.</p>
                        </div>
                        {% endif %}
                    </div>

                    <div class="reviews-list">
                        {% if reviews and reviews|length > 0 %}
                            {% for review in reviews %}
                            <div class="review-card">
                                <div class="review-card-header">
                                    <div class="review-author">
                                        <div class="author-avatar" style="background-color: {{ review.avatar_color }}">{{ review.avatar_initials }}</div>
                                        <div class="author-meta">
                                            <span class="author-name">{{ review.author_name }}</span>
                                            <span class="review-date">{{ review.created_at_formatted }}</span>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        {% for star in review.stars %}
                                            <i class="fa-{% if star.isFull %}solid{% else %}regular{% endif %} fa-star"></i>
                                        {% endfor %}
                                    </div>
                                </div>
                                {% if review.comment %}
                                    <div class="review-comment">{{ review.comment|nl2br }}</div>
                                {% endif %}
                            </div>
                            {% endfor %}
                        {% else %}
                            <p class="empty-msg">Soyez le premier à donner votre avis sur {{poi.name}} !</p>
                        {% endif %}
                    </div>
                </div>
            </section>
        </div>
        {% endif %}
    </div>
</section>

{% if showAd and topPois %}
<section class="detail-section poi-ad-section" style="margin-top: 2rem;">
    <div style="background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 12px; padding: 20px; color: white; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top:0; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-crown" style="color: #ff8c42;"></i> Les Pépites Incontournables
            <span style="font-size:0.75rem; background:rgba(255,255,255,0.1); padding:2px 8px; border-radius:10px; margin-left:auto;">Sponsorisé</span>
        </h3>
        <p style="font-size: 0.95rem; color: #cbd5e1; margin-bottom: 20px;">Découvrez les établissements les plus dynamiques de la région :</p>
        
        <div style="display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: thin; scrollbar-color: #ff8c42 rgba(255,255,255,0.1); justify-content: center;">
            {% for topPoi in topPois %}
            <a href="{{topPoi.detail_url_final}}" class="ad-poi-card" style="flex: 0 0 180px; text-decoration: none; background: rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); transition: transform 0.2s, border-color 0.2s;">
                <div class="ad-poi-image" style="height: 120px; position: relative; overflow: hidden; background-color: #f8fafc;">
                    {% if topPoi.cover_url %}
                        <div style="height: 100%; width: 100%; background: url('{{ topPoi.cover_url }}') center/cover no-repeat;"></div>
                    {% elseif topPoi.lat and topPoi.lon %}
                        <div id="minimap-sponsor-{{topPoi.id}}" class="poi-minimap" data-lat="{{topPoi.lat}}" data-lon="{{topPoi.lon}}" style="height: 100%; width: 100%;"></div>
                    {% elseif topPoi.logo %}
                        <div style="height: 100%; width: 100%; background: url('{{ topPoi.logo }}') center/contain no-repeat;"></div>
                    {% else %}
                        <div style="height: 100%; width: 100%; background: url('{{SITE_URL}}/theme/LumiereV2/header.jpg') center/cover no-repeat;"></div>
                    {% endif %}
                </div>
                <div style="padding: 12px; background: rgba(15, 23, 42, 0.8);">
                    <div style="color: white; font-weight: bold; font-size: 0.95rem; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{topPoi.name}}</div>
                    <div style="color: #ff8c42; font-size: 0.8rem;"><i class="fa-solid fa-map-pin"></i> {{topPoi.city}}</div>
                </div>
            </a>
            {% endfor %}
        </div>
        
        <style>
            .ad-poi-card:hover { transform: translateY(-5px); border-color: #ff8c42; }
        </style>
        
        {% if IS_OWNER %}
        <div style="margin-top: 20px; font-size: 0.85rem; color: #94a3b8; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
            <i class="fa-solid fa-info-circle"></i> Vous êtes le propriétaire ? <a href="/BusinessDashboard/premium" style="color: #ff8c42; text-decoration: underline;">Passez Premium</a> pour masquer ces suggestions et mettre en avant vos offres.
        </div>
        {% elseif not poi.claimed_by %}
        <div style="margin-top: 20px; font-size: 0.85rem; color: #94a3b8; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
            <i class="fa-solid fa-info-circle"></i> Propriétaire de ce lieu ? <a href="{{ actionUrls.claim }}" style="color: #ff8c42; text-decoration: underline;">Revendiquez cette fiche</a> pour la développer.
        </div>
        {% endif %}
    </div>
</section>
{% endif %}


{% if CORE.callHook('map:is-enabled') %}
{{ CORE.callHook('map:load-assets') }}
<script src="{{SHOW.siteUrl}}/plugin/SearchUi/assets/js/home-map-minimal.js?v=2"></script>
<script>
(function() {
    /**
     * Initialise la carte de détail
     */
    function initDetailMap() {
        const mapEl = document.getElementById('poi-map');
        if (!mapEl) return;
        
        // Robust safeguard: Check DOM property AND window-level flag
        if (mapEl._leaflet_id || window.detailMapInitialized) {
            console.log('Map already initialized or initializing...');
            return;
        }
        window.detailMapInitialized = true;

        const lat = parseFloat(mapEl.getAttribute('data-lat'));
        const lon = parseFloat(mapEl.getAttribute('data-lon'));
        const name = mapEl.getAttribute('data-name') || 'POI';
        const color = mapEl.getAttribute('data-color') || '#007bff';
        const icon = mapEl.getAttribute('data-icon') || 'location-dot';
        
        if (!isNaN(lat) && !isNaN(lon)) {
            // Créer la carte
            const map = L.map('poi-map', {
                zoomControl: false,
                dragging: false,
                touchZoom: false,
                doubleClickZoom: false,
                scrollWheelZoom: false,
                boxZoom: false,
                keyboard: false,
                attributionControl: false
            }).setView([lat, lon], 16);
            
            // Stocker dans window pour accès global (ex: invalidateSize)
            window.poiMap = map;

            // Ajouter les tuiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(map);

            // Add mapping from color to family (simplistic fallback since JS doesn't have it here)
            let familyClass = 'marker-other';
            if (color === '#ff8c42') familyClass = 'marker-shops';
            else if (color === '#e53935') familyClass = 'marker-food';
            else if (color === '#43a047') familyClass = 'marker-health';
            else if (color === '#1e88e5') familyClass = 'marker-services';
            else if (color === '#8e24aa') familyClass = 'marker-leisure';
            else if (color === '#6d4c41') familyClass = 'marker-heritage';
            else if (color === '#2e7d32') familyClass = 'marker-nature';
            else if (color === '#d32f2f') familyClass = 'marker-emergency';
            
            // Ajouter un marqueur personnalisé (identique à la home page)
            const customIcon = L.divIcon({
                className: 'custom-poi-marker',
                html: `<div class="marker-pin ${familyClass}">
                        <i class="fa-solid fa-${icon}"></i>
                       </div>`,
                iconSize: [30, 42],
                iconAnchor: [15, 42]
            });

            L.marker([lat, lon], { icon: customIcon }).addTo(map);
            
            // Calculer et afficher la distance si la géolocalisation est disponible
            calculateDistance(lat, lon);
        }
    }
    
    /**
     * Calcule la distance entre la position de l'utilisateur et le POI
     */
    function calculateDistance(poiLat, poiLon) {
        // Essayer d'abord la position en mémoire
        if (window.userLocation && window.userLocation.lat) {
            doCalculateDistance(window.userLocation.lat, window.userLocation.lon, poiLat, poiLon);
        }

        // Écouter les mises à jour globales
        window.addEventListener('user-location-updated', function(evt) {
            if (evt.detail && evt.detail.lat) {
                doCalculateDistance(evt.detail.lat, evt.detail.lon, poiLat, poiLon);
            }
        });
    }
    
    /**
     * Effectue le calcul de distance et l'affichage
     */
    function doCalculateDistance(lat1, lon1, lat2, lon2) {
        if (isNaN(lat1) || isNaN(lon1) || isNaN(lat2) || isNaN(lon2)) {
            return;
        }
        
        var R = 6371; // Rayon de la Terre en km
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        var distance = R * c;
        
        var displays = document.querySelectorAll('.poi-distance-display');
        displays.forEach(function(el) {
            var valueEl = el.querySelector('.poi-distance-value');
            if (valueEl) {
                if (distance < 1) {
                    valueEl.textContent = Math.round(distance * 1000) + ' m';
                } else {
                    valueEl.textContent = distance.toFixed(1) + ' km';
                }
                el.style.display = 'inline';
            }
        });
    }
    
    // Initialiser dès que possible
    if (document.readyState === 'complete') {
        initDetailMap();
    } else {
        window.addEventListener('load', initDetailMap);
    }

    // Mini-maps pour les sponsors
    function initSponsorMaps() {
        if (typeof L === 'undefined') {
            setTimeout(initSponsorMaps, 500);
            return;
        }
        const mapEls = document.querySelectorAll('.poi-minimap');
        mapEls.forEach(function(el) {
            if (el.dataset.initialized) return;
            const lat = parseFloat(el.getAttribute('data-lat'));
            const lon = parseFloat(el.getAttribute('data-lon'));
            if (isNaN(lat) || isNaN(lon)) return;

            const map = L.map(el.id, {
                center: [lat, lon],
                zoom: 14,
                zoomControl: false,
                dragging: false,
                scrollWheelZoom: false,
                touchZoom: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            L.marker([lat, lon]).addTo(map);
            el.dataset.initialized = "true";
        });
    }

    if (document.readyState === 'complete') {
        initSponsorMaps();
    } else {
        window.addEventListener('load', initSponsorMaps);
    }
})();
</script>
{% endif %}

    <!-- Modal de signalement (accessible à tous) -->
    <div id="report-poi-modal" class="report-modal-overlay">
        <div class="report-modal-content">
            <button type="button" id="report-poi-close" class="report-close-btn"><i class="fa-solid fa-times"></i></button>
            <h3 class="report-title"><i class="fa-solid fa-flag"></i> Signaler ce POI</h3>
            <form id="report-poi-form" class="report-form">
                <input type="hidden" name="poi_id" value="{{poi.id}}">
                <div>
                    <label class="report-label">Type de signalement :</label>
                    <select name="report_type" required class="report-select">
                        <option value="closed">Fermé définitivement</option>
                        <option value="deleted">Supprimé / N'existe plus</option>
                        <option value="duplicate">Doublon</option>
                        <option value="other">Autre</option>
                    </select>
                </div>
                <div>
                    <label class="report-label">Raison (optionnel) :</label>
                    <textarea name="reason" rows="4" class="report-textarea" placeholder="Détails du signalement..."></textarea>
                </div>
                <div class="report-actions">
                    <button type="button" id="report-poi-cancel" class="button report-cancel-btn">Annuler</button>
                    <button type="submit" class="button report-submit-btn"><i class="fa-solid fa-paper-plane"></i> Envoyer</button>
                </div>
            </form>
            <div id="report-poi-message" class="report-message"></div>
        </div>
    </div>

    <script>
    (function() {
        // --- 1. Gestion des Favoris ---
        // Use event delegation for all favorite buttons (orchestrator + local)
        document.addEventListener('click', function(e) {
            const favBtn = e.target.closest('.favorite-button');
            if (!favBtn) return;
            
            // If it's a link with a URL (guest friction), let it be
            if (favBtn.tagName === 'A' && favBtn.getAttribute('href') !== '#') return;
            
            e.preventDefault();
            
            if (!{% if IS_LOGGED %}true{% else %}false{% endif %}) {
                window.location.href = '{{ actionUrls.favorite }}';
                return;
            }

            const poiId = favBtn.dataset.poiId || '{{ poi.id }}';
            fetch('{{ toggleFavoriteUrl }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ token: '{{csrfToken}}', poi_id: poiId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Update all favorite buttons for this POI on the page
                    document.querySelectorAll('.favorite-button[data-poi-id="' + poiId + '"], .favorite-button:not([data-poi-id])').forEach(btn => {
                        btn.dataset.isFavorite = data.is_favorite ? '1' : '0';
                        btn.classList.toggle('active', data.is_favorite);
                        const textEl = btn.querySelector('.button-text');
                        if (textEl) {
                            textEl.textContent = data.is_favorite ? 'Retirer des favoris' : 'S\'abonner à ce POI';
                        }
                    });
                }
            });
        });

        // --- 2. Gestion du Signalement ---
        var reportModal = document.getElementById('report-poi-modal');
        var reportForm = document.getElementById('report-poi-form');
        
        // delegated listener for all report buttons (orchestrator + local)
        document.addEventListener('click', function(e) {
            if (e.target.closest('[data-report-modal="true"]') || e.target.closest('.report-poi-button')) {
                if (reportModal) {
                    e.preventDefault();
                    reportModal.style.display = 'flex';
                }
            }
        });

        if (reportModal) {
            [document.getElementById('report-poi-close'), document.getElementById('report-poi-cancel')].forEach(function(b) {
                if(b) b.addEventListener('click', function() { reportModal.style.display = 'none'; });
            });
            
            if (reportForm) {
                reportForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var msg = document.getElementById('report-poi-message');
                    msg.style.display = 'block';
                    msg.className = 'report-message info';
                    msg.textContent = 'Envoi en cours...';
                    
                    fetch('{{reportPoiUrl|default("/SearchUi/api/report-poi")}}', {
                        method: 'POST',
                        body: new URLSearchParams(new FormData(reportForm))
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.success) {
                            msg.className = 'report-message success';
                            msg.textContent = 'Merci ! Votre signalement a bien été envoyé.';
                            setTimeout(function() { 
                                reportModal.style.display = 'none';
                                reportForm.reset();
                                msg.style.display = 'none';
                            }, 2500);
                        } else {
                            msg.className = 'report-message error';
                            msg.textContent = res.error || 'Une erreur est survenue lors de l\'envoi.';
                        }
                    })
                    .catch(function(err) {
                        msg.className = 'report-message error';
                        msg.textContent = 'Erreur de connexion.';
                    });
                });
            }
        }

        // --- 3. Gestion des Onglets ---
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.detail-tab-button');
            if (!btn) return;
            
            const container = btn.closest('.search-detail');
            if (!container) return;
            
            e.preventDefault();
            const target = btn.dataset.tab;
            const buttons = container.querySelectorAll('.detail-tab-button');
            const panes = container.querySelectorAll('.detail-tab-pane');
            
            buttons.forEach(b => b.classList.toggle('active', b === btn));
            panes.forEach(p => p.classList.toggle('active', p.id === 'tab-' + target));
            
            // Forcer le redraw de la carte si on active l'onglet info
            if (target === 'info' && window.poiMap) {
                setTimeout(() => window.poiMap.invalidateSize(), 150);
            }
        });

        // Activation automatique de l'onglet via URL Hash (ex: #tab-actualites)
        if (window.location.hash) {
            const hashTab = window.location.hash.replace('#tab-', '').replace('#', '');
            const targetBtn = document.querySelector(`.detail-tab-button[data-tab="${hashTab}"]`);
            if (targetBtn) {
                setTimeout(() => {
                    targetBtn.click();
                    targetBtn.scrollIntoView({ behavior: 'smooth' });
                }, 100);
            }
        }
        
        // --- 4. Administration: Reset Ownership ---
        window.adminResetOwnership = function(poiId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer le propriétaire de ce lieu ?')) return;
            
            const formData = new FormData();
            formData.append('poi_id', poiId);
            
            fetch('/ConversionOrchestrator/admin/reset-ownership', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Propriété supprimée avec succès.');
                    window.location.reload();
                } else {
                    alert('Erreur: ' + (data.error || 'Inconnue'));
                }
            })
            .catch(error => {
                console.error('Error resetting ownership:', error);
                alert('Une erreur est survenue lors de la suppression.');
            });
        };

    })();
    </script>
    <script>
    (function() {
        var poiSlug = '{{poi.slug}}';
        if (!poiSlug) return;
        function trackPoi(action, label) {
            if (typeof _paq !== 'undefined') {
                _paq.push(['trackEvent', 'POI_Action', action, poiSlug + (label ? ' | ' + label : '')]);
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('a[href^="tel:"]').forEach(function(el) {
                el.addEventListener('click', function() { trackPoi('phone_call', el.getAttribute('href').replace('tel:', '')); });
            });
            document.querySelectorAll('.website-link').forEach(function(el) {
                el.addEventListener('click', function() { trackPoi('website_click', el.getAttribute('href')); });
            });
            document.querySelectorAll('a[href*="google.com/maps"], a[href*="maps.google"]').forEach(function(el) {
                el.addEventListener('click', function() { trackPoi('directions_click', 'google_maps'); });
            });
            document.querySelectorAll('a[href^="mailto:"]').forEach(function(el) {
                el.addEventListener('click', function() { trackPoi('email_click', el.getAttribute('href').replace('mailto:', '')); });
            });
        });
    })();
    </script>
    <style>
        .detail-hero-cover { display: none; } {# Caché car déplacé dans Infos #}
        .detail-info-photo { max-width: 100%; max-height: 400px; object-fit: contain; border-radius: 12px; display: block; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); background: #f8fafc; }
        
        /* Galerie Thumbnails */
        .detail-photos-gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; }
        .photo-thumb-card { position: relative; aspect-ratio: 1/1; cursor: pointer; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
        .photo-thumb-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .photo-thumb-card:hover img { transform: scale(1.1); }
        .photo-thumb-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.3); opacity: 0; display: flex; align-items: center; justify-content: center; color: white; transition: 0.2s; }
        .photo-thumb-card:hover .photo-thumb-overlay { opacity: 1; }

        /* Lightbox */
        .poi-lightbox-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); align-items: center; justify-content: center; flex-direction: column; }
        .lightbox-content { max-width: 90%; max-height: 80%; border-radius: 4px; object-fit: contain; }
        #lightbox-caption { margin: 15px; color: #ccc; font-weight: bold; }
        .lightbox-close { position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer; }
        
        .result-feature-icon { margin-left: 8px; color: #7f8c8d; font-size: 0.9em; }
        .result-feature-icon.pmr { color: #2980b9; }
        .result-feature-icon.parking { color: #27ae60; }
        .info-item dt i { vertical-align: middle; }
    </style>
