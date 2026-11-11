{% include 'theme/LumiereV2/header.tpl' %}

<div class="lumiere-landing-wrapper" style="background-color: #f8fafc;">

    <!-- HERO SECTION PRO -->
    <section class="landing-hero parallax-section" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(30, 41, 59, 0.7)), url('{{ SHOW.siteUrl }}/theme/LumiereV2/header.jpg') center/cover no-repeat; padding: 100px 20px 60px; text-align: center; color: white !important;">
        <div class="landing-hero-content" style="max-width: 800px; margin: 0 auto;">
            <span style="background: rgba(255, 140, 66, 0.2); color: #ff8c42; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; margin-bottom: 20px; display: inline-block;">
                <i class="fa-solid fa-store"></i> Espace Professionnel
            </span>
            <h1 style="font-size: 3.2rem; font-weight: 800; margin-bottom: 20px; text-shadow: 0 4px 10px rgba(0,0,0,0.5); color: #ffffff !important;">
                Boostez votre visibilité locale à Bourges
            </h1>
            <p style="font-size: 1.25rem; color: #f1f5f9; margin-bottom: 40px; line-height: 1.6; opacity: 0.95;">
                Démarquez-vous, attirez de nouveaux clients et développez votre activité grâce à notre plateforme dédiée aux acteurs locaux.
            </p>
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                <a href="/page/comment-revendiquer-19" class="button btn-interactive" style="background-color: #ff8c42; color: white; padding: 15px 35px; font-size: 1.1rem; border-radius: 30px; border: none; font-weight: bold; text-decoration: none; box-shadow: 0 4px 15px rgba(255, 140, 66, 0.4); transition: transform 0.3s ease;"><i class="fa-solid fa-graduation-cap"></i> Voir le Tutoriel</a>
                <a href="#freemium-details" class="button btn-interactive" style="background-color: rgba(255,255,255,0.1); color: white !important; padding: 15px 35px; font-size: 1.1rem; border-radius: 30px; border: 2px solid rgba(255,255,255,0.3); font-weight: bold; text-decoration: none; transition: background 0.3s ease;"><i class="fa-solid fa-magnifying-glass"></i> En savoir plus</a>
            </div>
        </div>
    </section>

    <!-- SECTION 1: FONCTIONNEMENT DE BASE (GRATUIT) -->
    <section id="freemium-details" style="padding: 60px 20px; background-color: #fff;">
        <div style="max-width: 1000px; margin: 0 auto; text-align: center;">
            <h2 style="font-size: 2.2rem; color: #1e293b; margin-bottom: 15px;">Une présence digitale essentielle, <span style="color: #ff8c42;">100% gratuite</span></h2>
            <p style="font-size: 1.1rem; color: #64748b; margin-bottom: 40px;">Revendiquer votre fiche est un service citoyen gratuit. Voici ce qui change pour vous :</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; text-align: left;">
                <div class="interactive-pro-card" style="background: #f8fafc; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden;">
                    <div style="width: 50px; height: 50px; background: #e0f2fe; color: #0284c7; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 20px;">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <h3 style="font-size: 1.3rem; color: #0f172a; margin-bottom: 10px;">Vitrine complète</h3>
                    <p style="color: #475569; line-height: 1.6; margin: 0;">Horaires, logo, photos, site web... Prenez le contrôle total de vos infos. <span style="color:#ff8c42; font-size:0.8rem; display:block; margin-top:10px;"><i class="fa-solid fa-plus"></i> Cliquez pour en savoir plus</span></p>
                    <div class="card-details" style="max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s ease; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px; color: #64748b; font-size: 0.95rem;">
                        Une page enrichie rassure les clients (score de confiance boosté) et améliore votre référencement sur les moteurs de recherche externes.
                    </div>
                </div>

                <div class="interactive-pro-card" style="background: #f8fafc; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden;">
                    <div style="width: 50px; height: 50px; background: #fef3c7; color: #d97706; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 20px;">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <h3 style="font-size: 1.3rem; color: #0f172a; margin-bottom: 10px;">Le statut "Actif"</h3>
                    <p style="color: #475569; line-height: 1.6; margin: 0;">Obtenez le badge <strong>"Actif ce mois-ci"</strong> et remontez dans les résultats. <span style="color:#ff8c42; font-size:0.8rem; display:block; margin-top:10px;"><i class="fa-solid fa-plus"></i> Cliquez pour en savoir plus</span></p>
                    <div class="card-details" style="max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s ease; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px; color: #64748b; font-size: 0.95rem;">
                        Chaque mise à jour de vos horaires ou publication d'offre réactive votre badge. Les établissements actifs sont 3x plus consultés que les autres.
                    </div>
                </div>

                <div class="interactive-pro-card" style="background: #f8fafc; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden;">
                    <div style="width: 50px; height: 50px; background: #fce7f3; color: #db2777; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 20px;">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <h3 style="font-size: 1.3rem; color: #0f172a; margin-bottom: 10px;">Données protégées</h3>
                    <p style="color: #475569; line-height: 1.6; margin: 0;">Vous devenez le seul validateur des modifications sur votre fiche. <span style="color:#ff8c42; font-size:0.8rem; display:block; margin-top:10px;"><i class="fa-solid fa-plus"></i> Cliquez pour en savoir plus</span></p>
                    <div class="card-details" style="max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s ease; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px; color: #64748b; font-size: 0.95rem;">
                        Une fois propriétaire, les suggestions des utilisateurs ne sont plus appliquées automatiquement. Vous gardez la main sur votre communication.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: TRANSPARENCE ET LIMITATIONS -->
    <section style="padding: 60px 20px; background: #f1f5f9;">
        <div style="max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="font-size: 1.8rem; color: #1e293b; margin-bottom: 10px;">Une plateforme transparente</h2>
                <p style="color: #64748b;">Parce qu'un projet local repose sur la confiance mutuelle.</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; border-top: 1px solid #e5e7eb; padding-top: 30px;">
                <div>
                    <h4 style="color: #0f172a; margin-bottom: 15px;"><i class="fa-solid fa-check" style="color:#10b981;"></i> Ce qui est gratuit</h4>
                    <ul class="freemium-list" style="list-style: none; padding: 0; margin-bottom: 30px;">
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 10px;"></i> Revendication & Protection de votre fiche</li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 10px;"></i> Gestion (4 photos, 1 lien social, 3 tags)</li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 10px;"></i> Description <strong>150 mots max</strong> (Éditeur WYSIWYG complet)</li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 10px;"></i> 1 Offre active simultanée</li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 10px;"></i> Réponses aux avis clients</li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 10px;"></i> Messagerie directe avec les habitants</li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 10px;"></i> Stats de base (vues & impressions)</li>
            </ul>
        </div>

        {# Colonne Premium #}
        <div class="pricing-card premium reveal-on-scroll" style="flex: 1; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); border: 2px solid #ff8c42; position: relative;">
            <div class="premium-badge-popular" style="position: absolute; top: -15px; right: 20px; background: #ff8c42; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.8rem;">RECOMMANDÉ</div>
            <h3 style="font-size: 1.8rem; color: #0f172a; margin-bottom: 10px;">Artisan (Premium)</h3>
            <p style="color: #64748b; margin-bottom: 25px;">Pour les pros qui souhaitent devenir incontournables.</p>
            
            <ul class="premium-list" style="list-style: none; padding: 0; margin-bottom: 30px;">
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-crown" style="color: #ffb800; margin-right: 10px;"></i> <strong>Top priorité</strong> dans les recherches</li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-image" style="color: #ffb800; margin-right: 10px;"></i> Photos & Médias <strong>illimités</strong></li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-pen-nib" style="color: #ffb800; margin-right: 10px;"></i> Description <strong>illimitée</strong> (Éditeur WYSIWYG complet)</li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-qrcode" style="color: #ffb800; margin-right: 10px;"></i> Personnalisation complète du <strong>QR Code</strong></li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-eye-slash" style="color: #ffb800; margin-right: 10px;"></i> Masquage des concurrents sur votre fiche</li>
                <li style="margin-bottom: 12px;"><i class="fa-solid fa-bullhorn" style="color: #ffb800; margin-right: 10px;"></i> Actus illimitées & Mise en avant VIP</li>
            </ul>
            <li><i class="fa-solid fa-chart-line" style="color:#ff8c42;"></i> Statistiques de performance détaillées</li>
                    </ul>
                    <a href="/page/offres-premium-18" style="color:#ff8c42; font-weight:bold; text-decoration:none; font-size:0.9rem; margin-top:10px; display:inline-block;">Découvrir les avantages du Premium &rarr;</a>
                </div>
            </div>
        </div>
    </section>

    <style>
        .interactive-pro-card:hover { border-color: #ff8c42 !important; transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .interactive-pro-card.expanded { background: #fff !important; border-color: #ff8c42 !important; box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
        .interactive-pro-card.expanded .card-details { max-height: 500px !important; opacity: 1 !important; }
        .interactive-pro-card.expanded .fa-plus { transform: rotate(45deg); }
        .btn-interactive:hover { transform: scale(1.05); }
        .parallax-section { background-attachment: fixed; }
        @media (max-width: 768px) { .parallax-section { background-attachment: scroll; } }
    </style>

    <!-- SECTION CTA FINAL -->
    <section style="padding: 80px 20px; text-align: center; background-color: #f1f5f9;">
        <div style="max-width: 600px; margin: 0 auto;">
            <i class="fa-solid fa-rocket" style="font-size: 3rem; color: #ff8c42; margin-bottom: 20px;"></i>
            <h2 style="font-size: 2rem; color: #1e293b; margin-bottom: 20px;">Prêt à rejoindre les pros du coin ?</h2>
            <p style="font-size: 1.1rem; color: #64748b; margin-bottom: 40px;">L'inscription est simple, rapide et sans engagement. Commencez par revendiquer gratuitement votre établissement.</p>
            <a href="/page/comment-revendiquer-19" class="button" style="background-color: #0f172a; color: white; padding: 15px 40px; font-size: 1.2rem; border-radius: 30px; border: none; font-weight: bold; text-decoration: none; display: inline-block;">
                Voir comment faire &rarr;
            </a>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.interactive-pro-card');
        cards.forEach(card => {
            card.addEventListener('click', function() {
                this.classList.toggle('expanded');
            });
        });
    });
    </script>
</div>

{% include 'theme/LumiereV2/footer.tpl' %}
