<section class="offers-admin-stats">
    <header>
        <h1>Statistiques des offres</h1>
    </header>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #667eea; margin-bottom: 0.5rem;">
                {{ totalOffers }}
            </div>
            <div style="color: #6c757d;">Total des offres</div>
        </div>
        
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #ffc107; margin-bottom: 0.5rem;">
                {{ pendingOffers }}
            </div>
            <div style="color: #6c757d;">En attente</div>
        </div>
        
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #28a745; margin-bottom: 0.5rem;">
                {{ approvedOffers }}
            </div>
            <div style="color: #6c757d;">Approuvées</div>
        </div>
        
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #17a2b8; margin-bottom: 0.5rem;">
                {{ activeOffers }}
            </div>
            <div style="color: #6c757d;">Actives</div>
        </div>
    </div>

    <div>
        <a href="/admin/offers" class="button secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour à la liste
        </a>
    </div>
</section>



