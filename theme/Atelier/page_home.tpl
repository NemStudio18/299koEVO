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

    <!-- 4. Feature Cards (En ce moment) -->
    <section class="landing-section landing-features">
        <h2>En ce moment à l'Atelier</h2>
        <div class="features-grid">
            
            <!-- Card 1: Blog -->
            <article class="feature-card blog-card rotate-container" id="blog-rotator">
                {% FOR news IN lastNews %}
                <div class="rotate-item {% IF news.active %}active{% ENDIF %}">
                    <div class="card-icon"><i class="fa-solid fa-newspaper"></i></div>
                    <div class="card-content">
                        <span class="card-label">Dernière Actualité</span>
                        <h3><a href="{{ news.url }}">{{ news.name }}</a></h3>
                        <p>{{ news.intro }}</p>
                        <div class="card-actions-row">
                            <a href="{{ news.url }}" class="card-link">{% IF news.fallback %}Voir le blog{% ELSE %}Lire l'article{% ENDIF %}</a>
                            <a href="{{ contactUrl }}" class="card-link secondary-link"><i class="fa-solid fa-pen-nib"></i> Proposer un article</a>
                        </div>
                    </div>
                </div>
                {% ENDFOR %}
                <div class="rotate-indicators">
                    {% FOR news IN lastNews %}
                    <span class="dot {% IF news.active %}active{% ENDIF %}" data-index="{{ news.index }}"></span>
                    {% ENDFOR %}
                </div>
            </article>

            <!-- Card 2: Forum -->
            <article class="feature-card forum-card rotate-container" id="forum-rotator">
                {% FOR topic IN lastTopics %}
                <div class="rotate-item {% IF topic.active %}active{% ENDIF %}">
                    <div class="card-icon"><i class="fa-solid fa-comments"></i></div>
                    <div class="card-content">
                        <span class="card-label">Discussion Récente</span>
                        <h3><a href="{{ topic.url }}">{{ topic.title }}</a></h3>
                        <div class="card-meta">
                            <span><i class="fa-solid fa-user"></i> {{ topic.authorName }}</span>
                            <span><i class="fa-solid fa-reply"></i> {{ topic.postCount }} rép.</span>
                        </div>
                        <a href="{{ topic.url }}" class="card-link">{% IF topic.fallback %}Lancer le débat{% ELSE %}Rejoindre la discussion{% ENDIF %}</a>
                    </div>
                </div>
                {% ENDFOR %}
                <div class="rotate-indicators">
                    {% FOR topic IN lastTopics %}
                    <span class="dot {% IF topic.active %}active{% ENDIF %}" data-index="{{ topic.index }}"></span>
                    {% ENDFOR %}
                </div>
            </article>

            <!-- Card 3: Agenda (Rotating) -->
            <article class="feature-card agenda-card rotate-container" id="agenda-rotator">
                {% FOR event IN nextEvents %}
                <div class="rotate-item {% IF event.active %}active{% ENDIF %}">
                    <div class="card-icon"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="card-content">
                        <span class="card-label">Prochain Rendez-vous</span>
                        <h3><a href="{{ event.url }}">{{ event.title }}</a></h3>
                        <p class="event-date">
                            <i class="fa-regular fa-clock"></i> {{ event.start_date }}
                            {% IF event.location %}<br><small>{{ event.location }}</small>{% ENDIF %}
                        </p>
                        <a href="{{ event.url }}" class="card-link">{% IF event.fallback %}Proposer un évènement{% ELSE %}Voir les détails{% ENDIF %}</a>
                    </div>
                </div>
                {% ENDFOR %}
                <div class="rotate-indicators">
                    {% FOR event IN nextEvents %}
                    <span class="dot {% IF event.active %}active{% ENDIF %}" data-index="{{ event.index }}"></span>
                    {% ENDFOR %}
                </div>
            </article>
            
        </div>
    </section>

    <!-- 5. Barometer & Poll -->
    <section class="landing-section landing-barometer">
        <div class="barometer-container">
            <!-- Dynamic Stats Barometer -->
            <div class="barometer-info">
                <h2>Le Baromètre de la Cité</h2>
                <div class="barometer-stats" id="barometer-rotator">
                    <div class="stat-item active">
                        <span class="stat-number">{{ barometerStats.users }}</span>
                        <span class="stat-label">Citoyens Engagés</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">{{ barometerStats.ideas }}</span>
                        <span class="stat-label">Idées Proposées</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">{{ barometerStats.votes }}</span>
                        <span class="stat-label">Votes Exprimés</span>
                    </div>
                </div>
            </div>

            <!-- Active Poll -->
            {% IF activePolls %}
            <div class="barometer-poll rotate-container" id="poll-rotator">
                {% FOR poll IN activePolls %}
                <div class="rotate-item {% IF poll.active %}active{% ENDIF %}">
                    <div class="poll-header">
                        <span class="poll-tag">Votre Avis</span>
                        <h3>{{ poll.title }}</h3>
                    </div>
                    <div class="poll-actions">
                        <a href="{{ poll.url }}" class="btn-poll">{% IF poll.fallback %}Suggérer un thème{% ELSE %}Participer au sondage{% ENDIF %}</a>
                        <a href="{{ contactUrl }}" class="btn-poll secondary-poll"><i class="fa-solid fa-lightbulb"></i> Proposer un sondage</a>
                    </div>
                </div>
                {% ENDFOR %}
                <div class="rotate-indicators">
                    {% FOR poll IN activePolls %}
                    <span class="dot {% IF poll.active %}active{% ENDIF %}" data-index="{{ poll.index }}"></span>
                    {% ENDFOR %}
                </div>
            </div>
            {% ENDIF %}
        </div>
    </section>

    <!-- 6. Soutenir -->
    <section class="landing-section landing-support">
        <h2>Soutenir l’Atelier</h2>
        <p>Vous pouvez soutenir l’Atelier Berruyer pour financer nos actions et événements. Chacun participe selon ses moyens.</p>
        <div class="support-actions">
            {% IF isDonsActive %}
            <a href="{{ ROUTER.generate('dons-home') }}" class="btn-cta">Faire un don au collectif</a>
            {% ELSE %}
            <a href="{{ ROUTER.generate('contact-home') }}" class="btn-cta">Nous contacter pour un don / une adhésion</a>
            {% ENDIF %}
        </div>
    </section>
    
    <!-- Content from editor -->
    {% if pageItem.getContent %}
    <section class="landing-section landing-extra-content">
        {{ pageItem.getContent }}
    </section>
    {% endif %}
</div>
