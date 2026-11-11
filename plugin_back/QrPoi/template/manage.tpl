{# Template frontend de gestion du QR Code pour le propriétaire d'un POI #}
{# URL: /qr-poi/manage/{poi_id} — chargé via PublicResponse (thème Lumière) #}

<div class="qr-manage-owner-page" style="max-width: 900px; margin: 2rem auto; padding: 0 1rem;">
    
    {# En-tête #}
    <div style="margin-bottom: 2rem;">
        <a href="{{ backUrl }}" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #667eea; text-decoration: none; margin-bottom: 1.5rem;">
            <i class="fa-solid fa-arrow-left"></i> Retour à la fiche
        </a>
        <h1 style="margin: 0; font-size: 1.75rem; color: #1a1a2e;">
            <i class="fa-solid fa-qrcode" style="color: #667eea;"></i>
            QR Code — {{ poi.name }}
        </h1>
        <p style="color: #6c757d; margin-top: 0.25rem;">
            Gérez l'affichage et la personnalisation du QR Code de votre établissement
        </p>
        {% if isAdmin %}
        <span style="background: #fff3cd; color: #856404; padding: 0.3rem 0.75rem; border-radius: 4px; font-size: 0.85rem; border: 1px solid #ffe69c;">
            <i class="fa-solid fa-crown"></i> Mode Administrateur
        </span>
        {% endif %}
    </div>

    <div style="display: flex; gap: 2rem; flex-wrap: wrap;">

        {# Colonne Gauche : Aperçu QR Code #}
        <div style="flex: 1; min-width: 260px; background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center;">
            <h2 style="font-size: 1.1rem; color: #333; margin-bottom: 1.5rem;">
                <i class="fa-solid fa-eye"></i> Aperçu
            </h2>
            <div style="display: inline-block; background: #f8f9fa; border-radius: 12px; padding: 1.5rem; border: 2px solid #e9ecef; margin-bottom: 1.5rem;">
                <img src="{{ qrCodeUrl }}" alt="QR Code {{ poi.name }}" style="width: 200px; height: 200px; display: block;">
            </div>
            <p style="font-size: 0.85rem; color: #6c757d; margin-bottom: 1.5rem;">Ouvrez l'appareil photo de votre smartphone et visez ce code pour tester la redirection.</p>
            
            <div style="background: #e8f4f8; border-radius: 8px; padding: 1rem; text-align: left; margin-bottom: 1.5rem;">
                <p style="font-size: 0.8rem; color: #0c5460; margin: 0;">
                    <strong>URL de redirection :</strong><br>
                    <code style="font-size: 0.75rem; word-break: break-all;">{{ redirectUrl }}</code>
                </p>
            </div>

            <a href="{{ downloadUrl }}" download class="button primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; border-radius: 8px; padding: 0.75rem; font-size: 0.9rem; text-decoration: none; background: #667eea; color: white; margin-bottom: 0.75rem;">
                <i class="fa-solid fa-download"></i> Télécharger le QR Code (PNG)
            </a>

            <a href="/qr-poi/customize/{{ poi.id }}" class="button secondary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; border-radius: 8px; padding: 0.75rem; font-size: 0.9rem; text-decoration: none; background: #f8f9fa; color: #333; border: 1px solid #ddd;">
                <i class="fa-solid fa-palette"></i> Personnaliser (Logo, Couleur...)
            </a>
        </div>

        {# Colonne Droite : Options #}
        <div style="flex: 2; min-width: 280px; display: flex; flex-direction: column; gap: 1.5rem;">

            {# Toggle affichage #}
            <div style="background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                <h2 style="font-size: 1.1rem; color: #333; margin-top: 0; margin-bottom: 1rem;">
                    <i class="fa-solid fa-toggle-on"></i> Affichage du QR Code
                </h2>
                <p style="color: #6c757d; font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Contrôlez si le QR Code apparaît sur la fiche publique de votre établissement.
                </p>
                
                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 8px; background: {% if isDisplayEnabled %}#d4edda{% else %}#f8d7da{% endif %}; margin-bottom: 1.5rem;">
                    <i class="fa-solid fa-{% if isDisplayEnabled %}check-circle{% else %}times-circle{% endif %}" style="font-size: 1.5rem; color: {% if isDisplayEnabled %}#28a745{% else %}#dc3545{% endif %};"></i>
                    <div>
                        <strong style="color: {% if isDisplayEnabled %}#155724{% else %}#721c24{% endif %};">
                            {% if isDisplayEnabled %}QR Code visible{% else %}QR Code masqué{% endif %}
                        </strong>
                        <p style="margin: 0; font-size: 0.85rem; color: {% if isDisplayEnabled %}#155724{% else %}#721c24{% endif %};">
                            {% if isDisplayEnabled %}Le QR Code est affiché sur votre fiche.{% else %}Le QR Code est masqué du public.{% endif %}
                        </p>
                    </div>
                </div>
                
                <form method="POST" action="{{ toggleUrl }}">
                    <input type="hidden" name="token" value="{{ csrfToken }}">
                    <input type="hidden" name="show_qr_code" value="{% if isDisplayEnabled %}0{% else %}1{% endif %}">
                    <button type="submit" class="button {% if isDisplayEnabled %}warning{% else %}primary{% endif %}" style="width: 100%; border-radius: 8px; padding: 0.75rem; font-size: 0.9rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: {% if isDisplayEnabled %}#ff9800{% else %}#28a745{% endif %}; color: white;">
                        <i class="fa-solid fa-{% if isDisplayEnabled %}eye-slash{% else %}eye{% endif %}"></i>
                        {% if isDisplayEnabled %}Masquer le QR Code{% else %}Afficher le QR Code{% endif %}
                    </button>
                </form>
            </div>

            {# Aide #}
            <div style="background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                <h2 style="font-size: 1.1rem; color: #333; margin-top: 0; margin-bottom: 1rem;">
                    <i class="fa-solid fa-circle-info" style="color: #667eea;"></i> À propos du QR Code
                </h2>
                <ul style="color: #6c757d; font-size: 0.9rem; line-height: 1.7; padding-left: 1.2rem; margin: 0;">
                    <li>Le QR Code pointe directement vers la page de votre établissement.</li>
                    <li>Il peut être imprimé, affiché en vitrine, ou placé sur vos supports de communication.</li>
                    <li>Chaque scan est comptabilisé pour voir l'impact de vos démarches.</li>
                    <li>Vous pouvez le masquer si vous ne souhaitez pas l'afficher sur votre fiche en ligne.</li>
                </ul>
            </div>

        </div>
    </div>
</div>
