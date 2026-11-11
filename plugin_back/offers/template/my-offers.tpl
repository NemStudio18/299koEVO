<section class="offers-my-offers">
    <header style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1><i class="fa-solid fa-briefcase"></i> {{ title }}</h1>
            <p style="color: #6c757d;">Gérez vos offres et consultez leurs performances.</p>
        </div>
        <a href="/offers/add" class="button primary">
            <i class="fa-solid fa-plus"></i> Créer une offre
        </a>
    </header>

    {% IF offers %}
    <div class="offers-table-container" style="background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <tr>
                    <th style="padding: 1rem; text-align: left;">Offre</th>
                    <th style="padding: 1rem; text-align: left;">POI</th>
                    <th style="padding: 1rem; text-align: center;">Statut</th>
                    <th style="padding: 1rem; text-align: center;">Vues</th>
                    <th style="padding: 1rem; text-align: center;">Utilisations</th>
                    <th style="padding: 1rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                {% FOR offer IN offers %}
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 1rem;">
                        <div style="font-weight: bold; color: #667eea;">{{ offer.title }}</div>
                        <div style="font-size: 0.85rem; color: #6c757d;">{{ offer.advantage }}</div>
                    </td>
                    <td style="padding: 1rem;">
                        <span style="font-size: 0.9rem;">{{ offer.poi_name }}</span>
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        {% IF offer.status == 'approved' %}
                            <span style="padding: 0.25rem 0.5rem; background: #d4edda; color: #155724; border-radius: 4px; font-size: 0.8rem;">Validée</span>
                        {% ELSEIF offer.status == 'pending' %}
                            <span style="padding: 0.25rem 0.5rem; background: #fff3cd; color: #856404; border-radius: 4px; font-size: 0.8rem;">En attente</span>
                        {% ELSE %}
                            <span style="padding: 0.25rem 0.5rem; background: #f8d7da; color: #721c24; border-radius: 4px; font-size: 0.8rem;">Refusée</span>
                        {% ENDIF %}
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        {{ offer.stats.views }}
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        {{ offer.stats.unlocks }}
                    </td>
                    <td style="padding: 1rem; text-align: right;">
                        <a href="/offers/edit/{{ offer.id }}" class="button small" title="Modifier l'offre">
                            <i class="fa-solid fa-pencil"></i>
                        </a>
                        <a href="{{ offer.viewUrl }}" class="button small" title="Voir l'offre publique">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>
    </div>
    {% ELSE %}
    <div style="text-align: center; padding: 3rem; background: white; border: 1px solid #dee2e6; border-radius: 8px;">
        <i class="fa-solid fa-inbox" style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;"></i>
        <p style="color: #6c757d;">Vous n'avez pas encore créé d'offre.</p>
        <a href="/offers/add" class="button primary" style="margin-top: 1rem;">Créer ma première offre</a>
    </div>
    {% ENDIF %}

    <div style="margin-top: 2rem;">
        <a href="/claim/my-claims" class="button">
            <i class="fa-solid fa-arrow-left"></i> Retour à mes fiches
        </a>
    </div>
</section>

<style>
.offers-table-container th {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
}
.button.small {
    padding: 0.4rem 0.6rem;
    font-size: 0.85rem;
}
</style>
