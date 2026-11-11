<section class="qr-poi-admin-list">
    <header>
        <h1><i class="fa-solid fa-qrcode"></i> QR Codes de mes fiches</h1>
        <p>Générez et téléchargez les QR codes pour vos fiches POI réclamées</p>
    </header>

    {% IF pois %}
    <div class="pois-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        {% FOR poi IN pois %}
        <div class="poi-card" style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <h3 style="margin: 0 0 1rem 0; color: #667eea;">{{ poi.name }}</h3>
            
            {% IF poi.addr_street %}
            <p style="color: #6c757d; font-size: 0.9rem; margin: 0 0 1rem 0;">
                <i class="fa-solid fa-location-dot"></i> {{ poi.addr_street }}
                {% IF poi.addr_city %}, {{ poi.addr_city }}{% ENDIF %}
            </p>
            {% ENDIF %}
            
            <div style="margin: 1rem 0;">
                <img src="{{ poi.qrCodeUrl }}" alt="QR Code" style="max-width: 200px; border: 1px solid #dee2e6; border-radius: 4px; padding: 0.5rem; background: white;">
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1rem;">
                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                    <a href="{{ poi.downloadUrl }}" class="button primary small" download>
                        <i class="fa-solid fa-download"></i> Télécharger
                    </a>
                </div>
                
                <div style="padding: 0.75rem; background: #f8f9fa; border-radius: 6px; border: 1px solid #dee2e6;">
                    <form method="POST" action="{{ poi.toggleUrl }}" style="margin: 0;">
                        <input type="hidden" name="token" value="{{ csrfToken }}">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                            <input type="checkbox" 
                                   name="show_qr_code" 
                                   value="1" 
                                   {% IF poi.showQrCode %}checked{% ENDIF %}
                                   onchange="this.form.submit()"
                                   style="cursor: pointer;">
                            <span style="color: #495057;">
                                <i class="fa-solid fa-qrcode"></i> Afficher sur la page publique
                            </span>
                        </label>
                    </form>
                </div>
            </div>
        </div>
        {% ENDFOR %}
    </div>
    {% ELSE %}
    <div style="text-align: center; padding: 3rem; background: white; border: 1px solid #dee2e6; border-radius: 8px; margin-top: 2rem;">
        <i class="fa-solid fa-inbox" style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;"></i>
        <p style="color: #6c757d;">Vous n'avez aucune fiche POI réclamée.</p>
        <p style="color: #6c757d; font-size: 0.9rem; margin-top: 0.5rem;">Réclamez une fiche pour pouvoir générer son QR code.</p>
    </div>
    {% ENDIF %}
</section>

<style>
.poi-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.poi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>



