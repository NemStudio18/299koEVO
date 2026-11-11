


<div class="business-dashboard">
    <div class="dashboard-header">
        <h1>Tableau de Bord Business</h1>
        <div class="user-info">
            Bonjour, <strong>{{user.username}}</strong>
        </div>
    </div>

    <!-- Getting Started Guide -->
    <div class="getting-started-section dashboard-welcome-card">
        <div class="dashboard-welcome-icon">🏗️</div>
        <div class="dashboard-welcome-content">
            <h3 class="dashboard-welcome-title">Bienvenue dans votre espace pro !</h3>
            <p class="dashboard-welcome-text">Commencez par mettre à jour vos horaires et ajouter des photos pour attirer plus de clients. Votre établissement est désormais sous votre contrôle direct.</p>
        </div>
        <div class="dashboard-welcome-actions">
             <a href="#poi-list" class="button button-primary">Mes établissements</a>
             <a href="/offers/create" class="button button-secondary">Créer une offre</a>
        </div>
    </div>

    <!-- Teaser Stats (Visible to everyone) -->
    <div class="stats-grid teaser-stats">
        <div class="stat-card">
            <div class="stat-value">{{totalViews}}</div>
            <div class="stat-label">Vues Totales</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{totalImpressions}}</div>
            <div class="stat-label">Impressions</div>
        </div>
    </div>

    <!-- Detailed Stats Wrapper -->
    <div class="dashboard-stats-wrapper" style="position: relative; margin-top: 20px;">
        {% if isFree %}
        <div class="upsell-stats-overlay">
            <i class="fa-solid fa-lock" style="font-size: 2rem; color: #ff8c42; margin-bottom: 15px;"></i>
            <h3>Analyses Précises</h3>
            <p>Découvrez d'où viennent vos clients et quels jours ils consultent votre fiche.</p>
            <a href="/subscription/plans" class="button button-primary">Passer au plan Pro</a>
        </div>
        {% endif %}

        <div class="stats-container {% if isFree %}blurred-stats{% endif %}">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">0</div>
                    <div class="stat-label">Clics Itinéraire</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">0%</div>
                    <div class="stat-label">Engagement</div>
                </div>
            </div>

            <div class="dashboard-content">
                <!-- Main Chart -->
                <div class="chart-section">
                    <h2>Performance (30 derniers jours)</h2>
                    <div class="chart-container">
                        <canvas id="businessChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- POI List -->
        <div class="poi-list-section">
            <h2>Mes Établissements</h2>
            {% IF pois %}
                <ul class="poi-list">
                {% FOR poi IN pois %}
                    <li class="poi-item">
                        <span class="poi-name">{{poi.name}}</span>
                        <span class="poi-id">(ID: {{poi.id}})</span>
                        <a href="/poi/edit/{{poi.id}}" class="btn-manage">Gérer</a>
                    </li>
                {% ENDFOR %}
                </ul>
            {% ELSE %}
                <p>Aucun établissement associé.</p>
            {% ENDIF %}
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Parse data from PHP
        // We use a safe fallback if variable is empty
        const dailyDataStr = '{{dailyStats}}' || '[]';
        let dailyData = [];
        try {
             dailyData = JSON.parse(dailyDataStr);
        } catch(e) {
             console.error("Error parsing daily stats", e);
        }
        
        // Prepare arrays
        const labels = [];
        const viewData = [];
        const impressionData = [];
        
        // Helper to format date
        const formatDate = (dateString) => {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
        };

        // Group by date
        const grouped = {};
        dailyData.forEach(item => {
            if (!grouped[item.date]) {
                grouped[item.date] = { view: 0, impression: 0 };
            }
            grouped[item.date][item.event_type] = parseInt(item.count);
        });
        
        // Sort dates
        const sortedDates = Object.keys(grouped).sort();
        
        sortedDates.forEach(date => {
            labels.push(formatDate(date));
            viewData.push(grouped[date].view || 0);
            impressionData.push(grouped[date].impression || 0);
        });

        const ctx = document.getElementById('businessChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Vues',
                        data: viewData,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Impressions',
                        data: impressionData,
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>

