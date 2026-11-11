<?php
/**
 * Template Name: Page Qui Sommes-Nous
 */
?>
<div class="about-container">
    
    <!-- 1. Titre & Introduction -->
    <header class="about-header">
        <h1>À propos de L’Atelier Berruyer</h1>
        <p class="about-lead">L’Atelier Berruyer est un projet citoyen apolitique né à Bourges.</p>
        <div class="about-intro-block">
            <p>Il a été créé pour offrir un espace d’expression, de dialogue et de construction collective, ouvert à toutes et tous, sans étiquette ni condition.</p>
        </div>
    </header>

    <!-- 2. Pourquoi ce projet ? -->
    <section class="about-section about-why">
        <h2>Pourquoi L’Atelier Berruyer ?</h2>
        <div class="why-content">
            <p>De nombreux habitants ont des idées, des constats et des propositions pour améliorer leur quotidien. Pourtant, ces voix restent souvent dispersées, peu entendues ou isolées.</p>
            <p class="highlight-text">L’Atelier Berruyer est né de cette envie simple : créer un lieu où le dialogue est possible, où les idées peuvent se confronter sereinement et où le collectif encadre la réflexion.</p>
        </div>
    </section>

    <!-- 3. Notre démarche -->
    <section class="about-section about-approach">
        <h2>Une démarche citoyenne et apolitique</h2>
        <div class="approach-content">
            <p>L’Atelier Berruyer n’est ni un parti, ni un mouvement politique.</p>
            <p>C’est un espace de participation citoyenne, basé sur :</p>
            <div class="values-badges">
                <span class="badge"><i class="fa-solid fa-eye"></i> La transparence</span>
                <span class="badge"><i class="fa-solid fa-ear-listen"></i> L’écoute</span>
                <span class="badge"><i class="fa-regular fa-comments"></i> Le respect des opinions</span>
                <span class="badge"><i class="fa-solid fa-brain"></i> L’intelligence collective</span>
            </div>
            <p>Chacun peut participer librement, proposer, questionner et contribuer, sans pression ni récupération.</p>
        </div>
    </section>

    <!-- 4. Comment ça fonctionne concrètement -->
    <section class="about-section about-how">
        <h2>Comment fonctionne l’Atelier ?</h2>
        <ul class="how-list">
            <li><i class="fa-solid fa-laptop"></i> Un site participatif avec forums, sondages et articles</li>
            <li><i class="fa-solid fa-comments"></i> Des échanges ouverts entre habitants</li>
            <li><i class="fa-solid fa-people-roof"></i> Des ateliers et réunions ponctuelles dans les quartiers</li>
            <li><i class="fa-solid fa-bullhorn"></i> Une restitution publique des idées et propositions</li>
        </ul>
        <p class="note"><i class="fa-solid fa-info-circle"></i> Certaines actions sont ouvertes à tous, d’autres sont réservées aux membres de l’association afin de garantir un cadre de travail clair et organisé.</p>
    </section>

    <!-- 5. L’association -->
    <section class="about-section about-association">
        <h2>L’association L’Atelier Berruyer</h2>
        <div class="association-content">
            <p>L’Atelier Berruyer est également une association loi 1901.</p>
            <p>L’association permet :</p>
            <ul class="association-list">
                <li><i class="fa-solid fa-calendar-check"></i> d’organiser des événements</li>
                <li><i class="fa-solid fa-building"></i> de louer ou utiliser des salles</li>
                <li><i class="fa-solid fa-bullhorn"></i> de financer la communication et les outils</li>
                <li><i class="fa-solid fa-scale-balanced"></i> de garantir un cadre légal et transparent</li>
            </ul>
            <p class="association-footer">L’adhésion est facultative. Le site reste accessible et participatif pour tous.</p>
        </div>
    </section>

    <!-- 6. Nos valeurs -->
    <section class="about-section about-values-list">
        <h2>Nos valeurs</h2>
        <div class="values-grid-simple">
            <div class="value-item">
                <i class="fa-solid fa-handshake-angle"></i>
                <strong>Collectif</strong> : les idées se construisent ensemble
            </div>
            <div class="value-item">
                <i class="fa-solid fa-magnifying-glass"></i>
                <strong>Transparence</strong> : pas de décisions cachées
            </div>
            <div class="value-item">
                <i class="fa-regular fa-comments"></i>
                <strong>Dialogue</strong> : la diversité des opinions est une richesse
            </div>
            <div class="value-item">
                <i class="fa-solid fa-door-open"></i>
                <strong>Ouverture</strong> : chacun participe à son rythme
            </div>
            <div class="value-item">
                <i class="fa-solid fa-location-dot"></i>
                <strong>Ancrage local</strong> : Bourges et ses quartiers avant tout
            </div>
        </div>
    </section>

    <!-- 7. Invitation finale -->
    <section class="about-section about-join">
        <h2>Rejoignez l’Atelier</h2>
        <p>Que vous soyez simplement curieux, porteur d’idées ou désireux de vous engager davantage, vous êtes les bienvenus.</p>
        <p>L’Atelier Berruyer est un espace en construction permanente, porté par celles et ceux qui y participent.</p>
        <div class="join-actions">
            <a href="{{ ROUTER.generate('forum-index') }}" class="btn-hero">Participer au forum</a>
            <a href="{{ ROUTER.generate('register') }}" class="btn-hero btn-alt">S’inscrire</a>
        </div>
    </section>

</div>
