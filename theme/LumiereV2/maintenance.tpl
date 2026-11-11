<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance - Vivre-Bourges.fr</title>
    <base href="{{ CORE.makeSiteUrl }}/">
    <link rel="stylesheet" href="theme/LumiereV2/css/structure.css" type="text/css"/>
    <link rel="stylesheet" href="theme/LumiereV2/css/design.css" type="text/css"/>
    <link rel="stylesheet" href="theme/LumiereV2/css/components.css" type="text/css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="d-flex align-center justify-center" style="min-height: 100vh; background: #f0f2f5;">
    <div class="main-container section-narrow">
        <div class="card text-center" style="padding: 40px;">
                        <!-- Logo -->
                        {% if CORE.getConfigVal("siteLogo") %}
                        <img src="{{ CORE.getConfigVal('siteLogo') }}" alt="Vivre-Bourges.fr" style="max-height: 100px; margin-bottom: 2rem;">
                        {% endif %}

                        <h1>Vivre-Bourges.fr est temporairement en maintenance</h1>
                        
                        <p style="font-size: 1.1rem; margin-bottom: 2rem;">
                            <strong>Vivre-Bourges.fr est un outil local ouvert à tous, pensé comme un annuaire 2.0 participatif pour la ville de Bourges et son agglomération.</strong>
                        </p>
                        
                        <style>
                            .beta-box {
                                text-align: left; 
                                margin: 0 0 2rem 0; 
                                padding: 20px; 
                                border: 2px solid var(--primary-color);
                                border-radius: 8px;
                                display: flex;
                                align-items: center;
                                justify-content: space-between;
                                background: rgba(255,140,66,0.05); /* Soft background based on primary */
                                gap: 20px;
                            }
                            .beta-box-btn {
                                flex-shrink: 0;
                            }
                            @media (max-width: 768px) {
                                .beta-box {
                                    flex-direction: column;
                                    text-align: center;
                                }
                                .beta-box i {
                                    display: block;
                                    margin: 0 auto 10px;
                                    font-size: 2rem;
                                }
                                .beta-box-btn {
                                    width: 100%;
                                }
                                .beta-box-btn .button {
                                    width: 100%;
                                    justify-content: center;
                                }
                            }
                        </style>
                        <div class="msg success beta-box">
                            <div>
                                <i class="fa-solid fa-flask" style="color: var(--primary-color); font-size: 1.5rem; margin-right: 10px; vertical-align: middle;"></i>
                                <strong style="font-size: 1.2rem; vertical-align: middle;">Accès Beta disponible</strong>
                                <p style="margin-top: 10px; margin-bottom: 0px; color: var(--text-main);">L'annuaire de recherche est toujours testable sur notre version anticipée. Venez découvrir les fonctionnalités en cours de développement !</p>
                            </div>
                            <div class="beta-box-btn">
                                <a href="https://beta.vivre-bourges.fr" class="button button-primary" style="text-decoration: none;">
                                    Accéder au site Beta <i class="fa-solid fa-arrow-right" style="margin-left: 5px;"></i>
                                </a>
                            </div>
                        </div>

                        <div class="msg info" style="text-align: left; margin-bottom: 2rem;">
                            <i class="fa-solid fa-circle-info"></i>
                            <strong>Un projet participatif et gratuit</strong>
                            <ul style="margin-top: 10px; margin-bottom: 0;">
                                <li>L’inscription est gratuite pour tous</li>
                                <li>Chacun peut proposer de nouveaux points, des corrections ou des améliorations</li>
                                <li>Un système de jeux et de participation valorise les contributions</li>
                            </ul>
                        </div>

                        <div style="text-align: left;">
                            <h2>Pour les professionnels</h2>
                            <p>Chaque professionnel peut récupérer gratuitement sa fiche, en devenir le seul gestionnaire et enrichir sa présentation.</p>
                            
                            <div class="msg success" style="margin: 20px 0;">
                                <i class="fa-solid fa-check-circle"></i>
                                <strong>Tous les outils disposent d’un accès gratuit</strong>
                                <br>L'abonnement optionnel sert uniquement à lever certaines limites (nombre de photos, offres simultanées...) et à soutenir le projet.
                            </div>
                        </div>

                        <div class="msg warning" style="text-align: left; margin: 30px 0;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <strong>Pourquoi cette maintenance ?</strong>
                            <p style="margin-top: 10px;">Je suis actuellement l’unique développeur du projet. Suite à une correction de bug récente, j’ai découvert une erreur de logique dans la base de données qui impacte le cœur du site.</p>
                            <p style="margin-bottom: 0;"><strong>👉 Je mets temporairement le site en maintenance afin de reprendre une partie du noyau technique et garantir une base saine et fiable.</strong></p>
                        </div>

                        <div style="text-align: left; margin-top: 30px;">
                            <h3>Informations importantes</h3>
                            <ul>
                                <li>Aucune action n’est requise de votre part</li>
                                <li>Les comptes déjà inscrits avant la maintenance ne devraient pas être impactés</li>
                                <li>Les données existantes sont conservées</li>
                            </ul>
                        </div>

                        <hr style="margin: 30px 0; border: 0; border-top: 1px solid var(--panel-border);">

                        <p>Je m’excuse pour la gêne occasionnée et vous remercie pour votre compréhension et votre soutien.<br>
                        Le projet est autofinancé et a besoin de sa communauté pour continuer à évoluer.</p>
                        
                        <p class="text-center" style="font-weight: bold; color: var(--primary-color);">À très bientôt,<br>et merci de faire vivre Bourges autrement.</p>
                        
                        {% IF maintenanceMessage %}
                        <div class="msg error" style="margin-top: 30px; text-align: left;">
                             <i class="fa-solid fa-bullhorn"></i>
                             <strong>Message administrateur :</strong>
                             <div style="margin-top: 5px;">{{maintenanceMessage}}</div>
                        </div>
                        {% ENDIF %}
                        
                        <div style="margin-top: 30px;">
                            <a href="mailto:contact@vivre-bourges.fr" class="button">Nous contacter</a>
                        </div>
                    </div>

        </div>
    </div>
</body>
</html>
