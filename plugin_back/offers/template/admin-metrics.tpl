<section class="offers-admin-metrics">
    <header>
        <h1><i class="fa-solid fa-chart-line"></i> Métriques des offres</h1>
        <p>Statistiques d'utilisation des offres promotionnelles</p>
    </header>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #667eea;">{{ totalOffers }}</div>
            <div style="color: #6c757d; margin-top: 0.5rem;">Total des offres</div>
        </div>
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #28a745;">{{ activeOffers }}</div>
            <div style="color: #6c757d; margin-top: 0.5rem;">Offres actives</div>
        </div>
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #17a2b8;">{{ totalViews }}</div>
            <div style="color: #6c757d; margin-top: 0.5rem;">Total des vues</div>
        </div>
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #ffc107;">{{ totalUnlocks }}</div>
            <div style="color: #6c757d; margin-top: 0.5rem;">Total des déblocages</div>
        </div>
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #dc3545;">{{ globalConversionRate }}%</div>
            <div style="color: #6c757d; margin-top: 0.5rem;">Taux de conversion</div>
        </div>
    </div>

    <div style="margin-top: 2rem;">
        <h2>Détail par offre</h2>
        {% IF offers %}
        <table class="table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>POI ID</th>
                    <th>Période</th>
                    <th>Statut</th>
                    <th>Vues</th>
                    <th>Déblocages</th>
                    <th>Taux de conversion</th>
                </tr>
            </thead>
            <tbody>
                {% FOR offer IN offers %}
                <tr>
                    <td><strong>{{ offer.title }}</strong></td>
                    <td>{{ offer.poi_id }}</td>
                    <td>
                        <small>{{ offer.start_date_formatted }} - {{ offer.end_date_formatted }}</small>
                    </td>
                    <td>
                        {% IF offer.status == "approved" %}
                            <span style="color: #28a745;">Approuvée</span>
                        {% ELSEIF offer.status == "pending" %}
                            <span style="color: #ffc107;">En attente</span>
                        {% ELSE %}
                            <span style="color: #dc3545;">Rejetée</span>
                        {% ENDIF %}
                        {% IF offer.isCurrentlyValid %}
                            <br><small style="color: #28a745;">Active</small>
                        {% ENDIF %}
                    </td>
                    <td>{{ offer.views_count }}</td>
                    <td>{{ offer.unlocks_count }}</td>
                    <td>{{ offer.conversion_rate }}%</td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>
        {% ELSE %}
        <p style="text-align: center; padding: 2rem; color: #6c757d;">Aucune offre pour le moment.</p>
        {% ENDIF %}
    </div>
</section>

<style>
.offers-admin-metrics {
    padding: 1rem;
}

.offers-admin-metrics header {
    margin-bottom: 2rem;
}

.offers-admin-metrics header h1 {
    margin: 0 0 0.5rem 0;
    color: #667eea;
}

.offers-admin-metrics header p {
    margin: 0;
    color: #6c757d;
}

.offers-admin-metrics .table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.offers-admin-metrics .table thead {
    background: #f8f9fa;
}

.offers-admin-metrics .table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.offers-admin-metrics .table td {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.offers-admin-metrics .table tbody tr:hover {
    background: #f8f9fa;
}
</style>


