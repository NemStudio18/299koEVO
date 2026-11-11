<section class="offer-unlock">
    <div style="max-width: 600px; margin: 0 auto; text-align: center;">
        <header style="margin-bottom: 2rem;">
            <h1><i class="fa-solid fa-gift"></i> Votre code</h1>
            <p style="color: #6c757d;">{{ offer.title }}</p>
        </header>

        <div class="code-display" style="background: white; border: 2px solid #667eea; border-radius: 8px; padding: 2rem; margin-bottom: 2rem;">
            <div style="margin-bottom: 1.5rem;">
                <p style="color: #6c757d; margin-bottom: 0.5rem;">Scannez le QR code ou montrez le code en caisse :</p>
                <img src="{{ offer.qrCodeUrl }}" alt="QR Code" style="max-width: 200px; margin: 0 auto; display: block; border: 1px solid #dee2e6; border-radius: 4px; padding: 0.5rem; background: white;">
            </div>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
                <div style="font-size: 0.9rem; color: #6c757d; margin-bottom: 0.5rem;">Code :</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #667eea; font-family: monospace; letter-spacing: 2px;">
                    {{ offer.code }}
                </div>
            </div>
        </div>

        <div style="padding: 1rem; background: #d1ecf1; border-left: 4px solid #0c5460; border-radius: 4px; margin-bottom: 2rem; text-align: left;">
            <p style="margin: 0; color: #0c5460;">
                <strong><i class="fa-solid fa-info-circle"></i> Comment utiliser :</strong><br>
                Présentez ce code ou le QR code en caisse pour bénéficier de l'offre. Aucun téléchargement nécessaire.
            </p>
        </div>

        <div>
            <a href="/offers" class="button secondary">
                <i class="fa-solid fa-arrow-left"></i> Retour aux offres
            </a>
        </div>
    </div>
</section>



