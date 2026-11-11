<div class="pro-page-wrapper">
    <style>
        .pro-page-wrapper {
            --pro-primary: #ff8c42;
            --pro-primary-dark: #ea580c;
            --pro-primary-light: rgba(255, 140, 66, 0.12);
            --pro-dark: #0f172a;
            --pro-slate: #1e293b;
            --pro-muted: #64748b;
            --pro-card-bg: #ffffff;
            --pro-border: #e2e8f0;
            --pro-radius: 16px;
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 16px 60px 16px;
            font-family: 'Outfit', 'Inter', -apple-system, sans-serif;
            color: var(--pro-slate);
        }

        /* --- HERO BANNER --- */
        .pro-hero {
            text-align: center;
            padding: 30px 16px 40px 16px;
            background: linear-gradient(180deg, rgba(255, 140, 66, 0.08) 0%, rgba(255, 255, 255, 0) 100%);
            border-radius: 24px;
            margin-bottom: 32px;
            border: 1px solid rgba(255, 140, 66, 0.15);
        }

        .pro-badge-pilot {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff3eb;
            color: var(--pro-primary-dark);
            border: 1.5px solid rgba(255, 140, 66, 0.35);
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(255, 140, 66, 0.12);
        }

        .pro-badge-pilot .dot-pulse {
            width: 8px;
            height: 8px;
            background: #ea580c;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 rgba(234, 88, 12, 0.5);
            animation: proPulse 2s infinite;
        }

        @keyframes proPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(234, 88, 12, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(234, 88, 12, 0); }
        }

        .pro-hero-title {
            font-size: clamp(1.8rem, 5vw, 2.7rem);
            font-weight: 800;
            line-height: 1.2;
            color: var(--pro-dark);
            margin: 0 0 16px 0;
            letter-spacing: -0.02em;
        }

        .pro-hero-title span.highlight {
            color: var(--pro-primary);
            background: linear-gradient(135deg, #ff8c42 0%, #ea580c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .pro-hero-subtitle {
            font-size: clamp(1rem, 2.5vw, 1.18rem);
            color: #475569;
            line-height: 1.6;
            max-width: 720px;
            margin: 0 auto 28px auto;
        }

        /* --- CTA BUTTONS --- */
        .pro-cta-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
            justify-content: center;
        }

        @media (min-width: 640px) {
            .pro-cta-group {
                flex-direction: row;
                gap: 16px;
            }
        }

        .btn-pro-search {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #ff8c42 0%, #ea580c 100%);
            color: #ffffff !important;
            font-size: 1.05rem;
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 50px;
            text-decoration: none !important;
            box-shadow: 0 4px 18px rgba(234, 88, 12, 0.35);
            transition: all 0.25s ease;
            width: 100%;
            max-width: 380px;
            min-height: 50px;
            box-sizing: border-box;
        }

        .btn-pro-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(234, 88, 12, 0.45);
        }

        .btn-pro-contact {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #f8fafc;
            color: #334155 !important;
            border: 1.5px solid #cbd5e1;
            font-size: 0.95rem;
            font-weight: 600;
            padding: 13px 22px;
            border-radius: 50px;
            text-decoration: none !important;
            transition: all 0.2s ease;
            width: 100%;
            max-width: 320px;
            min-height: 50px;
            box-sizing: border-box;
        }

        .btn-pro-contact:hover {
            background: #ffffff;
            border-color: #94a3b8;
            color: var(--pro-dark) !important;
        }

        /* --- BLOC JAUGE / ARGUMENT CLÉ --- */
        .pro-score-banner {
            background: #ffffff;
            border-radius: var(--pro-radius);
            border: 1.5px solid var(--pro-border);
            padding: 24px 20px;
            margin-bottom: 35px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        }

        .score-comparison-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        @media (min-width: 680px) {
            .score-comparison-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .score-box {
            border-radius: 12px;
            padding: 16px;
            box-sizing: border-box;
        }

        .score-box-before {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
        }

        .score-box-after {
            background: #fff7ed;
            border: 1.5px solid rgba(255, 140, 66, 0.4);
            box-shadow: 0 4px 12px rgba(255, 140, 66, 0.08);
        }

        .score-box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .score-box-title {
            font-size: 0.95rem;
            font-weight: 700;
            margin: 0;
            color: var(--pro-dark);
        }

        .score-bar-bg {
            height: 10px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin: 10px 0;
        }

        .score-bar-fill-before {
            width: 55%;
            height: 100%;
            background: #f59e0b;
            border-radius: 999px;
        }

        .score-bar-fill-after {
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, #ff8c42, #10b981);
            border-radius: 999px;
        }

        .score-box-desc {
            font-size: 0.85rem;
            color: var(--pro-muted);
            line-height: 1.45;
            margin: 0;
        }

        /* --- CARTE VALEUR PILOTE (4 COLONNES / GRILLE) --- */
        .pro-section-title {
            font-size: clamp(1.3rem, 3.5vw, 1.7rem);
            font-weight: 800;
            color: var(--pro-dark);
            text-align: center;
            margin: 0 0 10px 0;
        }

        .pro-section-subtitle {
            font-size: 0.98rem;
            color: var(--pro-muted);
            text-align: center;
            max-width: 600px;
            margin: 0 auto 28px auto;
        }

        .pro-features-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            margin-bottom: 40px;
        }

        @media (min-width: 640px) {
            .pro-features-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .pro-feature-card {
            background: #ffffff;
            border-radius: var(--pro-radius);
            padding: 22px;
            border: 1px solid var(--pro-border);
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .pro-feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }

        .pro-feature-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 4px;
        }

        .icon-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .icon-orange { background: rgba(255, 140, 66, 0.12); color: #ff8c42; }
        .icon-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .icon-emerald { background: rgba(16, 185, 129, 0.1); color: #10b981; }

        .pro-feature-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--pro-dark);
            margin: 0;
        }

        .pro-feature-text {
            font-size: 0.92rem;
            color: #475569;
            line-height: 1.55;
            margin: 0;
        }

        /* --- BLOC CONTRAT DE CONFIANCE (L'ÉCHANGE) --- */
        .pro-contract-box {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: var(--pro-radius);
            padding: 28px 22px;
            color: #ffffff;
            margin-bottom: 40px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
        }

        .pro-contract-badge {
            display: inline-block;
            background: rgba(255, 140, 66, 0.25);
            color: #ff8c42;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .pro-contract-title {
            font-size: 1.35rem;
            font-weight: 800;
            margin: 0 0 12px 0;
            color: #ffffff;
        }

        .pro-contract-text {
            color: #cbd5e1;
            font-size: 0.98rem;
            line-height: 1.6;
            margin: 0;
        }

        /* --- BLOC COMMENT ÇA MARCHE (3 ÉTAPES) --- */
        .pro-steps-container {
            background: #ffffff;
            border-radius: var(--pro-radius);
            border: 1px solid var(--pro-border);
            padding: 30px 20px;
            margin-bottom: 35px;
        }

        .pro-step-item {
            display: flex;
            gap: 16px;
            margin-bottom: 26px;
            align-items: flex-start;
        }

        .pro-step-item:last-child {
            margin-bottom: 0;
        }

        .pro-step-num {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--pro-dark);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
        }

        .pro-step-content h4 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--pro-dark);
            margin: 0 0 4px 0;
        }

        .pro-step-content p {
            font-size: 0.9rem;
            color: var(--pro-muted);
            line-height: 1.5;
            margin: 0;
        }

        /* --- FOOTER CARD (UNE QUESTION ?) --- */
        .pro-contact-footer-card {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: var(--pro-radius);
            padding: 24px;
            text-align: center;
            margin-top: 30px;
        }

        .pro-contact-footer-card h4 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: var(--pro-dark);
        }

        .pro-contact-footer-card p {
            font-size: 0.92rem;
            color: var(--pro-muted);
            margin: 0 0 16px 0;
        }
    </style>

    <!-- SECTION 1 : HERO & OFFRE FONDATEUR -->
    <header class="pro-hero">
        <div class="pro-badge-pilot">
            <span class="dot-pulse"></span>
            Programme Pilote • 5 Places Disponibles
        </div>
        <h1 class="pro-hero-title">
            Devenez <span class="highlight">Membre Fondateur</span> de Vivre-Bourges
        </h1>
        <p class="pro-hero-subtitle">
            Votre établissement fait déjà partie des <strong>2&nbsp;673 lieux</strong> répertoriés sur notre plateforme territoriale. Nous ouvrons un programme pilote de 3 mois pour <strong>5 professionnels et artisans du Berry</strong>. Prenez le contrôle de votre fiche et façonnez nos outils avant tout le monde.
        </p>

        <div class="pro-cta-group">
            <a href="/SearchUi" class="btn-pro-search">
                <i class="fa-solid fa-magnifying-glass"></i> Rechercher mon établissement
            </a>
            <a href="/contact?subject=Candidature%20Pilote%20Membre%20Fondateur" class="btn-pro-contact">
                <i class="fa-solid fa-comments"></i> Échanger avec l'équipe
            </a>
        </div>
        <p style="font-size: 0.82rem; color: #94a3b8; margin-top: 12px; margin-bottom: 0;">
            Établissement récent non listé ? <a href="/PoiManager/propose" style="color: #ff8c42; text-decoration: underline;">Proposez-le gratuitement ici</a>.
        </p>
    </header>

    <!-- SECTION 2 : L'ARGUMENT DE LA JAUGE (DATASCORE 55% -> 100%) -->
    <section class="pro-score-banner">
        <div style="text-align: center; max-width: 660px; margin: 0 auto 10px auto;">
            <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">
                <i class="fa-solid fa-chart-pie" style="color: #ff8c42; margin-right: 6px;"></i> Ne laissez pas votre fiche incomplète
            </h2>
            <p style="font-size: 0.92rem; color: #64748b; line-height: 1.5; margin: 0;">
                Sur Vivre-Bourges, chaque fiche possède une jauge de complétion visible par les habitants.
            </p>
        </div>

        <div class="score-comparison-grid">
            <!-- État Actuel -->
            <div class="score-box score-box-before">
                <div class="score-box-header">
                    <span class="score-box-title"><i class="fa-solid fa-circle-exclamation" style="color: #f59e0b;"></i> Fiche Actuelle</span>
                    <strong style="color: #f59e0b; font-size: 1rem;">~55% complet</strong>
                </div>
                <div class="score-bar-bg">
                    <div class="score-bar-fill-before"></div>
                </div>
                <p class="score-box-desc">
                    Données publiques brutes issues d'OpenStreetMap. Pas de photos récentes, horaires parfois manquants, pas de contact direct ni d'offres.
                </p>
            </div>

            <!-- Fiche Pilote Optimisée -->
            <div class="score-box score-box-after">
                <div class="score-box-header">
                    <span class="score-box-title"><i class="fa-solid fa-circle-check" style="color: #10b981;"></i> Avec le Programme Pilote</span>
                    <strong style="color: #10b981; font-size: 1rem;">100% Optimisée</strong>
                </div>
                <div class="score-bar-bg">
                    <div class="score-bar-fill-after"></div>
                </div>
                <p class="score-box-desc">
                    Horaires en temps réel, galerie photos, lien vers votre site, publication d'événements et badge officiel de confiance.
                </p>
            </div>
        </div>
    </section>

    <!-- SECTION 3 : CE QUE VOUS OBTENEZ PENDANT LE PILOTE -->
    <section style="margin-bottom: 35px;">
        <h2 class="pro-section-title">Ce que vous obtenez pendant le pilote</h2>
        <p class="pro-section-subtitle">
            Un accès prioritaire et privilégié aux outils de valorisation locale de Vivre-Bourges.
        </p>

        <div class="pro-features-grid">
            <!-- Feature 1 -->
            <div class="pro-feature-card">
                <div class="pro-feature-icon icon-blue">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <h3 class="pro-feature-title">Contrôle total de votre fiche</h3>
                <p class="pro-feature-text">
                    Modifiez vos horaires en direct, ajoutez vos photos en haute résolution, vos coordonnées téléphoniques, votre logo et vos réseaux sociaux.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="pro-feature-card">
                <div class="pro-feature-icon icon-orange">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <h3 class="pro-feature-title">Outils de visibilité & offres</h3>
                <p class="pro-feature-text">
                    Publiez vos offres promotionnelles exclusives, annoncez vos ateliers ou temps forts dans l'agenda et répondez aux avis des usagers.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="pro-feature-card">
                <div class="pro-feature-icon icon-purple">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <h3 class="pro-feature-title">Badge « Membre Fondateur »</h3>
                <p class="pro-feature-text">
                    Bénéficiez d'une mise en avant distinctive sur votre fiche et dans les résultats de recherche pour marquer votre ancrage et votre engagement local.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="pro-feature-card">
                <div class="pro-feature-icon icon-emerald">
                    <i class="fa-solid fa-handshake-simple"></i>
                </div>
                <h3 class="pro-feature-title">Accompagnement direct</h3>
                <p class="pro-feature-text">
                    On travaille ensemble, main dans la main. Vous échangez directement avec les créateurs du projet et participez aux arbitrages des futures fonctionnalités.
                </p>
            </div>
        </div>
    </section>

    <!-- SECTION 4 : LE CONTRAT DE CONFIANCE (L'ÉCHANGE) -->
    <section class="pro-contract-box">
        <span class="pro-contract-badge"><i class="fa-solid fa-gem"></i> Partenariat Gagnant-Gagnant</span>
        <h3 class="pro-contract-title">Ce que nous vous demandons (l'échange)</h3>
        <p class="pro-contract-text">
            Nous ne cherchons pas de simples inscrits anonymes, mais de véritables <strong>partenaires locaux</strong>.<br>
            En échange de l'accès complet et gratuit à tous nos outils pendant les 3 mois du pilote, nous vous demanderons simplement <strong>15 minutes de votre temps</strong> à l'issue de l'expérience pour recueillir vos retours sincères et perfectionner la plateforme avec vous.
        </p>
    </section>

    <!-- SECTION 5 : COMMENT ÇA MARCHE ? -->
    <section class="pro-steps-container">
        <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0 0 20px 0; text-align: center;">
            Comment rejoindre le programme en 3 étapes ?
        </h3>

        <div class="pro-step-item">
            <div class="pro-step-num">1</div>
            <div class="pro-step-content">
                <h4>Recherchez votre établissement</h4>
                <p>Trouvez votre lieu parmi les 2&nbsp;673 fiches existantes grâce à la barre de recherche (nom ou activité).</p>
            </div>
        </div>

        <div class="pro-step-item">
            <div class="pro-step-num">2</div>
            <div class="pro-step-content">
                <h4>Revendiquez votre fiche</h4>
                <p>Cliquez sur le badge « Propriétaire ? » présent sur votre fiche pour nous signaler votre statut.</p>
            </div>
        </div>

        <div class="pro-step-item">
            <div class="pro-step-num">3</div>
            <div class="pro-step-content">
                <h4>Validation & Remise des accès sous 48h</h4>
                <p>Nous vous contactons personnellement par téléphone ou email pour confirmer votre place et vous ouvrir votre espace d'administration.</p>
            </div>
        </div>
    </section>

    <!-- SECTION 6 : PAS D'AUTOMATE, ON DISCUTE -->
    <footer class="pro-contact-footer-card">
        <div style="font-size: 1.8rem; color: #ff8c42; margin-bottom: 8px;">
            <i class="fa-solid fa-headset"></i>
        </div>
        <h4>Une question ? Pas de robot, on préfère discuter.</h4>
        <p>
            Pas d'inscription automatisée ni de démarches complexes. Si vous hésitez ou souhaitez en savoir plus sur le pilote :
        </p>
        <div style="display: flex; flex-direction: column; gap: 10px; align-items: center; justify-content: center;">
            <a href="/contact?subject=Question%20Programme%20Pilote%20Pro" class="btn-pro-contact" style="background: #ffffff; border-color: #ff8c42; color: #ea580c !important;">
                <i class="fa-solid fa-envelope"></i> Contacter directement l'équipe locale
            </a>
            <a href="/SearchUi" style="color: #64748b; font-size: 0.9rem; text-decoration: underline; margin-top: 6px;">
                Ou rechercher directement ma fiche dans l'annuaire &rarr;
            </a>
        </div>
    </footer>
</div>
