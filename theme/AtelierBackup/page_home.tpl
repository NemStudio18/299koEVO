<?php
/**
 * Template Name: Page d'Accueil (Landing)
 */
?>
<div class="landing-container">
    
    <!-- 1. Hero Section -->
    <header class="landing-hero">
        <h1 class="landing-title">Faites entendre votre voix à Bourges.</h1>
        <p class="landing-subtitle">L’Atelier Berruyer, c’est votre espace citoyen.</p>
        
        <div class="hero-actions">
            <!-- Link to registration or forum/polls -->
            <a href="{{ ROUTER.generate('register') }}" class="btn-hero">Participer dès maintenant</a>
        </div>
        
        <div class="landing-slogan-small">
            <p>Apolitique, inclusif et ouvert à tous, pour partager vos idées et construire ensemble.</p>
        </div>
    </header>

    <!-- 2. Notre Mission -->
    <section class="landing-section landing-mission-v2">
        <h2>Pourquoi l’Atelier Berruyer ?</h2>
        <div class="mission-text">
            <p>L’Atelier Berruyer est un collectif citoyen apolitique basé à Bourges.</p>
            <p>Notre objectif : créer un lieu où chaque habitant peut s’exprimer, proposer et participer à la vie locale.</p>
            <p><strong>Ici, vos idées ont un impact concret et le collectif encadre la décision, pas l’inverse.</strong></p>
        </div>
        <div class="mission-icons">
            <div class="icon-box"><i class="fa-regular fa-comments"></i><span>Dialogue</span></div>
            <div class="icon-box"><i class="fa-regular fa-lightbulb"></i><span>Idées</span></div>
            <div class="icon-box"><i class="fa-solid fa-hands-holding-circle"></i><span>Actions</span></div>
        </div>
    </section>

    <!-- 3. Comment Participer -->
    <section class="landing-section landing-participate">
        <h2>Rejoignez le collectif</h2>
        <div class="participate-grid">
            <div class="participate-item">
                <i class="fa-regular fa-comments"></i>
                <p>Commentez et échangez sur nos forums</p>
            </div>
            <div class="participate-item">
                <i class="fa-solid fa-square-poll-vertical"></i>
                <p>Répondez aux sondages</p>
            </div>
            <div class="participate-item">
                <i class="fa-solid fa-map-location-dot"></i>
                <p>Proposez des idées pour votre quartier</p>
            </div>
            <div class="participate-item">
                <i class="fa-solid fa-people-group"></i>
                <p>Participez à des ateliers citoyens</p>
            </div>
        </div>
        <div class="participate-footer">
            <p>L’inscription est libre et ouverte à tous.</p>
            <a href="{{ ROUTER.generate('register') }}" class="btn-cta">S’inscrire / Créer un compte</a>
        </div>
    </section>

    <!-- 4. Actualités (Blog) -->
    {% IF atelier_news %}
    <section class="landing-section landing-news">
        <h2>Actualités et Blog</h2>
        <div class="news-grid">
            {% FOR news IN atelier_news %}
            <article class="news-card">
                <h3>{{ news.getName }}</h3>
                <div class="news-excerpt">
                     <!-- Truncate content or use intro if available (assuming getIntro exists or standard substr) -->
                     {{ Util.cutString(news.getContent, 150) }}
                </div>
                <a href="{{ news.getUrl }}" class="read-more">Lire la suite &rarr;</a>
            </article>
            {% ENDFOR %}
        </div>
    </section>
    {% ENDIF %}

    <!-- 5. Prochains Événements (Agenda) -->
    {% IF atelier_events %}
    <section class="landing-section landing-events">
        <h2>Prochains événements / ateliers</h2>
        <div class="events-list">
            {% FOR event IN atelier_events %}
            <div class="event-row">
                <div class="event-date">
                    <span class="day">{{ Util.getFormattedDate(event.getStartDate, 'd') }}</span>
                    <span class="month">{{ Util.getFormattedDate(event.getStartDate, 'M') }}</span>
                </div>
                <div class="event-details">
                    <h4>{{ event.getTitle }}</h4>
                    <p>{{ event.getDescription }}</p>
                </div>
                <div class="event-action">
                    <a href="{{ event.getUrl }}" class="btn-small">Voir</a>
                </div>
            </div>
            {% ENDFOR %}
        </div>
    </section>
    {% ENDIF %}

    <!-- 6. Soutenir -->
    <section class="landing-section landing-support">
        <h2>Soutenir l’Atelier</h2>
        <p>Vous pouvez soutenir l’Atelier Berruyer pour financer nos actions et événements. Chacun participe selon ses moyens.</p>
        <div class="support-actions">
            <!-- Redirect to Contact for now as Donation plugin is not active -->
            <a href="{{ ROUTER.generate('contact-home') }}" class="btn-cta">Nous contacter pour un don / une adhésion</a>
        </div>
    </section>
    
    <!-- Content from editor -->
    {% if pageItem.getContent %}
    <section class="landing-section landing-extra-content">
        {{ pageItem.getContent }}
    </section>
    {% endif %}
</div>
