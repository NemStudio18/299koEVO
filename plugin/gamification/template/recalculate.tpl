<section class="module-card">
    <header><i class="fa-solid fa-calculator"></i> Recalcul des points de progression</header>
    
    <div style="margin-top: 1.5rem;">
        <p>
            Cette fonction permet de recalculer les points de progression pour tous les utilisateurs en fonction de leurs contributions historiques.
        </p>
        
        <div style="margin: 1.5rem 0; padding: 1rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
            <h4 style="margin-top: 0;">Types de contributions pris en compte :</h4>
            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                <li><strong>POIs possédés</strong> (claims approuvés) : {% IF config.points_claim_poi_validated %}{{config.points_claim_poi_validated}}{% ELSE %}200{% ENDIF %} points par POI</li>
                <li><strong>Propositions de POI approuvées</strong> : {% IF config.points_contribution_accepted %}{{config.points_contribution_accepted}}{% ELSE %}50{% ENDIF %} points par proposition</li>
                <li><strong>Photos uploadées</strong> : {% IF config.points_contribution %}{{config.points_contribution}}{% ELSE %}10{% ENDIF %} points par photo</li>
                <li><strong>Liens ajoutés</strong> : {% IF config.points_contribution %}{{config.points_contribution}}{% ELSE %}10{% ENDIF %} points par lien</li>
                <li><strong>Tags personnalisés</strong> : {% IF config.points_contribution %}{{config.points_contribution}}{% ELSE %}10{% ENDIF %} points par tag</li>
                <li><strong>Descriptions ajoutées</strong> : {% IF config.points_contribution_accepted %}{{config.points_contribution_accepted}}{% ELSE %}50{% ENDIF %} points par description</li>
            </ul>
        </div>
        
        <div style="margin: 1.5rem 0; padding: 1rem; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px;">
            <p style="margin: 0; color: #856404;">
                <strong>⚠️ Attention :</strong> Cette opération va parcourir tous les utilisateurs et ajouter des points basés sur leurs stats historiques. 
                Les points seront ajoutés avec la raison "migration_initiale" pour éviter les doublons si vous relancez le recalcul.
            </p>
        </div>
        
        <form method="post" action="{{linkRecalculateRun}}" onsubmit="return confirm('Êtes-vous sûr de vouloir lancer le recalcul des points pour tous les utilisateurs ? Cette opération peut prendre du temps.');">
            <input type="hidden" name="_csrf" value="{{csrfToken}}">
            <p style="margin-top: 1.5rem;">
                <button type="submit" class="button primary" style="background: #28a745; color: white; border: none;">
                    <i class="fa-solid fa-play"></i> Lancer le recalcul
                </button>
                <a href="{{linkToHome}}" class="button" style="margin-left: 0.5rem;">
                    <i class="fa-solid fa-arrow-left"></i> Retour
                </a>
            </p>
        </form>
    </div>
</section>

