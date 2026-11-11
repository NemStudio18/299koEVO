<section class="qr-poi-admin-generate">
    <div style="max-width: 600px; margin: 0 auto;">
        <header style="margin-bottom: 2rem;">
            <a href="{{ backUrl }}" style="color: #667eea; text-decoration: none; margin-bottom: 1rem; display: inline-block;">
                <i class="fa-solid fa-arrow-left"></i> Retour à la liste
            </a>
            <h1><i class="fa-solid fa-qrcode"></i> QR Code</h1>
            <p style="color: #6c757d;">{{ poi.name }}</p>
        </header>

        <div class="qr-code-display" style="background: white; border: 2px solid #667eea; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; text-align: center;">
            <img src="{{ qrCodeUrl }}" alt="QR Code" style="max-width: 100%; border: 1px solid #dee2e6; border-radius: 4px; padding: 1rem; background: white;">
        </div>

        <div style="padding: 1rem; background: #d1ecf1; border-left: 4px solid #0c5460; border-radius: 4px; margin-bottom: 2rem;">
            <p style="margin: 0; color: #0c5460;">
                <strong><i class="fa-solid fa-info-circle"></i> Comment utiliser :</strong><br>
                Scannez ce QR code avec un smartphone pour accéder directement à la fiche de détails de votre POI.
            </p>
            <p style="margin: 0.5rem 0 0 0; color: #0c5460; font-size: 0.9rem;">
                <strong>URL de redirection :</strong><br>
                <code style="background: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">{{ redirectUrl }}</code>
            </p>
            <div style="margin-top: 1rem; border-top: 1px solid rgba(12, 84, 96, 0.1); padding-top: 1rem;">
                <a href="/admin/qr-poi/customize/{{ poi.id }}" style="color: #0c5460; text-decoration: none; font-weight: bold;">
                    <i class="fa-solid fa-palette"></i> Personnaliser ce QR Code (Couleur, Logo, Texte...)
                </a>
            </div>
        </div>

        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
            <h3 style="margin-top: 0; font-size: 1rem;"><i class="fa-solid fa-eye"></i> Visibilité sur la fiche publique</h3>
            
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 4px; background: {% if isDisplayEnabled %}#d4edda{% else %}#f8d7da{% endif %}; margin-bottom: 1rem;">
                <i class="fa-solid fa-{% if isDisplayEnabled %}check-circle{% else %}times-circle{% endif %}" style="font-size: 1.2rem; color: {% if isDisplayEnabled %}#28a745{% else %}#dc3545{% endif %};"></i>
                <div>
                    <strong style="color: {% if isDisplayEnabled %}#155724{% else %}#721c24{% endif %};">
                        {% if isDisplayEnabled %}QR Code visible{% else %}QR Code masqué{% endif %}
                    </strong>
                </div>
            </div>

            <form method="POST" action="{{ toggleUrl }}">
                <input type="hidden" name="token" value="{{ csrfToken }}">
                <input type="hidden" name="show_qr_code" value="{% if isDisplayEnabled %}0{% else %}1{% endif %}">
                <button type="submit" class="button {% if isDisplayEnabled %}warning{% else %}primary{% endif %}" style="width: 100%; border-radius: 4px; padding: 0.75rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: {% if isDisplayEnabled %}#ff9800{% else %}#28a745{% endif %}; color: white;">
                    <i class="fa-solid fa-{% if isDisplayEnabled %}eye-slash{% else %}eye{% endif %}"></i>
                    {% if isDisplayEnabled %}Masquer le QR Code sur la fiche publique{% else %}Afficher le QR Code sur la fiche publique{% endif %}
                </button>
            </form>
        </div>

        <div style="text-align: center; display: flex; gap: 1rem; justify-content: center;">
            <a href="{{ downloadUrl }}" class="button primary" download>
                <i class="fa-solid fa-download"></i> Télécharger (PNG)
            </a>
            <a href="{{ customizeUrl }}" class="button" style="background: #6c757d; color: white;">
                <i class="fa-solid fa-palette"></i> Personnaliser
            </a>
        </div>
    </div>
</section>



