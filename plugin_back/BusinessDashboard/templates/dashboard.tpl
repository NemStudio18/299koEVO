<div class="business-dashboard">
    <div class="dash-header">
        <h1><i class="fa fa-chart-line"></i> Espace Pro</h1>
        <p>Gérez vos établissements et suivez leur performance.</p>
    </div>

    <!-- KPI Cards -->
    <div class="dash-metrics">
        <div class="metric-card">
            <span class="metric-value">{{totalViews}}</span>
            <span class="metric-label">Vues Totales</span>
            <i class="fa fa-eye metric-icon"></i>
        </div>
        <div class="metric-card">
            <span class="metric-value">{{totalImpressions}}</span>
            <span class="metric-label">Apparitions Recherche</span>
            <i class="fa fa-magnifying-glass metric-icon"></i>
        </div>
        <div class="metric-card promo">
            <span class="metric-value">0</span>
            <span class="metric-label">Offres Actives</span>
            <a href="#" class="btn-micro">Créer une offre</a>
        </div>
    </div>

    <!-- Setup Chart.js -->
    <div class="dash-chart-container">
        <canvas id="statsChart"></canvas>
    </div>

    <div class="dash-pois-list">
        <h2>Vos Établissements</h2>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {% loop pois as poi %}
                <tr>
                    <td>{{poi.name}}</td>
                    <td>
                        <a href="/poi-edit/edit/poi/{{poi.id}}" class="btn-edit"><i class="fa fa-edit"></i> Modifier</a>
                    </td>
                </tr>
                {% endloop %}
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare Data
        const rawData = {{dailyStats}}; // JSON injected
        
        // Simple transformation (Grouping by date)
        // ... (This logic handles converting SQL rows to Chart.js format)
        // For MVP, just basic placeholder logic as SQL processing in JS is tedious
        
        const ctx = document.getElementById('statsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['J-6', 'J-5', 'J-4', 'J-3', 'J-2', 'Hier', 'Aujourd\'hui'],
                datasets: [{
                    label: 'Vues',
                    data: [12, 19, 3, 5, 2, 3, 10], // Mock for now until JS processor
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: 'Performance 7 derniers jours' }
                }
            }
        });
    });
</script>

<style>
.business-dashboard { max-width: 1200px; margin: 0 auto; padding: 20px; }
.dash-metrics { display: flex; gap: 20px; margin-bottom: 40px; }
.metric-card { flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; flex-direction: column; position: relative; }
.metric-value { font-size: 2.5em; font-weight: bold; color: #0056b3; }
.metric-label { color: #666; }
.metric-icon { position: absolute; top: 20px; right: 20px; font-size: 2em; opacity: 0.1; }
.dash-chart-container { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
</style>
