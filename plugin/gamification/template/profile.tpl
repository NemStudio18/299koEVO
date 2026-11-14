<section class="module-card progression-profile">
    <header>
        <i class="fa-solid fa-trophy"></i> Ma Progression
    </header>
    
    <!-- Stats principales -->
    <div class="progression-stats-main">
        <div class="stat-card stat-card-primary">
            <div class="stat-value">{{stats.total_points}}</div>
            <div class="stat-label"><i class="fa-solid fa-coins"></i> Points totaux</div>
        </div>
        
        {% if stats.has_current_palier %}
        <div class="stat-card stat-card-palier">
            <div class="stat-palier-name">
                <i class="fa-solid {{stats.current_palier.badge_icon}}"></i>
                {{stats.current_palier.name}}
            </div>
            <div class="stat-label">Palier actuel</div>
        </div>
        {% endif %}
        
        <div class="stat-card stat-card-actions">
            <div class="stat-value">{{stats.total_transactions}}</div>
            <div class="stat-label"><i class="fa-solid fa-list"></i> Actions</div>
        </div>
        
        {% if stats.has_next_palier %}
        <div class="stat-card stat-card-next">
            <div class="stat-palier-name">
                <i class="fa-solid {{stats.next_palier.badge_icon}}"></i>
                {{stats.next_palier.name}}
            </div>
            <div class="stat-label">
                {{stats.points_remaining}} points restants
            </div>
        </div>
        {% endif %}
    </div>
    
    <!-- Métriques du jour -->
    <div class="progression-today-metrics">
        <h3>
            <i class="fa-solid fa-calendar-day"></i> Aujourd'hui
        </h3>
        <div class="today-metrics-flex">
            <div class="today-metric-card">
                <div class="today-metric-value">{% IF stats.today_metrics.points_today %}{{stats.today_metrics.points_today}}{% ELSE %}0{% ENDIF %}</div>
                <div class="today-metric-label">Points gagnés</div>
            </div>
            <div class="today-metric-card">
                <div class="today-metric-value">{% IF stats.today_metrics.actions_today %}{{stats.today_metrics.actions_today}}{% ELSE %}0{% ENDIF %}</div>
                <div class="today-metric-label">Actions réalisées</div>
            </div>
            <div class="today-metric-card">
                <div class="today-metric-value">{% IF stats.today_metrics.proposals_today %}{{stats.today_metrics.proposals_today}}{% ELSE %}0{% ENDIF %}</div>
                <div class="today-metric-label">POI proposés</div>
            </div>
            <div class="today-metric-card">
                <div class="today-metric-value">{% IF stats.today_metrics.contributions_today %}{{stats.today_metrics.contributions_today}}{% ELSE %}0{% ENDIF %}</div>
                <div class="today-metric-label">Contributions acceptées</div>
            </div>
            <div class="today-metric-card">
                <div class="today-metric-value">{% IF stats.today_metrics.photos_today %}{{stats.today_metrics.photos_today}}{% ELSE %}0{% ENDIF %}</div>
                <div class="today-metric-label">Photos ajoutées</div>
            </div>
        </div>
        <!-- Mini graphique de motivation -->
        <div class="today-motivation-chart">
            <div class="chart-bars">
                <div class="chart-bar" style="height: {{ stats.chart_heights.points }}%;" title="Points"></div>
                <div class="chart-bar" style="height: {{ stats.chart_heights.actions }}%;" title="Actions"></div>
                <div class="chart-bar" style="height: {{ stats.chart_heights.proposals }}%;" title="POI proposés"></div>
                <div class="chart-bar" style="height: {{ stats.chart_heights.contributions }}%;" title="Contributions"></div>
                <div class="chart-bar" style="height: {{ stats.chart_heights.photos }}%;" title="Photos"></div>
            </div>
            <p class="chart-encouragement">
                {% IF stats.has_activity_today %}
                <i class="fa-solid fa-fire"></i> Continuez comme ça !
                {% ELSE %}
                <i class="fa-solid fa-rocket"></i> Lancez-vous aujourd'hui !
                {% ENDIF %}
            </p>
        </div>
    </div>
    
    <!-- Badge actuel -->
    {% if stats.has_current_palier %}
    <div class="current-badge">
        <h3>Mon badge actuel</h3>
        <div class="badge-circle" style="background: {{stats.current_palier.badge_color}};">
            <i class="fa-solid {{stats.current_palier.badge_icon}}"></i>
            <div class="badge-name">{{stats.current_palier.badge_name}}</div>
        </div>
        <p class="badge-palier-name">{{stats.current_palier.name}}</p>
    </div>
    {% endif %}
    
    <!-- Progression vers le prochain palier -->
    {% if stats.has_next_palier %}
    <div class="progress-section">
        <h3>Progression vers {{stats.next_palier.name}}</h3>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" style="background: linear-gradient(90deg, {{stats.current_color}}, {{stats.next_palier.badge_color}}); width: {{stats.progress_percent}}%;">
                {% if stats.show_progress_text %}{{stats.progress_percent_rounded}}%{% endif %}
            </div>
        </div>
        <p class="progress-text">
            {{stats.total_points}} / {{stats.next_palier.points_required}} points
        </p>
    </div>
    {% endif %}
    
    <!-- Tous les paliers -->
    <div class="all-paliers">
        <h3><i class="fa-solid fa-layer-group"></i> Tous les paliers</h3>
        <div class="paliers-grid">
            {% for palier in paliers %}
            <div class="palier-card" style="border-color: {{palier.border_color}}; background: {{palier.bg_color}};">
                <div class="palier-icon" style="color: {{palier.badge_color}};">
                    <i class="fa-solid {{palier.badge_icon}}"></i>
                </div>
                <div class="palier-name">{{palier.name}}</div>
                <div class="palier-points">{{palier.points_required}} pts</div>
                {% if palier.is_current %}
                <div class="palier-status palier-status-current" style="color: {{palier.badge_color}};">
                    <i class="fa-solid fa-check-circle"></i> Actuel
                </div>
                {% elseif palier.is_reached %}
                <div class="palier-status palier-status-reached">
                    <i class="fa-solid fa-check"></i> Atteint
                </div>
                {% endif %}
            </div>
            {% endfor %}
        </div>
    </div>
    
    <!-- Historique des transactions -->
    <div class="transactions-history">
        <h3><i class="fa-solid fa-history"></i> Historique des actions</h3>
        {% if transactions %}
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                {% for transaction in transactions %}
                <tr>
                    <td>{{transaction.date}}</td>
                    <td>
                        <i class="fa-solid {{transaction.icon}}"></i> {{transaction.label}}
                    </td>
                    <td class="points-positive">+{{transaction.points}}</td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
        {% else %}
        <p class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            Aucune action enregistrée pour le moment.
        </p>
        {% endif %}
    </div>
    
    <!-- Statistiques par type d'action -->
    {% IF stats.actions_count %}
    <div class="actions-stats">
        <h3><i class="fa-solid fa-chart-pie"></i> Répartition des actions</h3>
        <div class="actions-flex">
            {% FOR actionData IN stats.actions_count %}
            <div class="action-stat-card">
                <div class="action-stat-value">{{actionData.count}}</div>
                <div class="action-stat-label">{{actionData.label}}</div>
            </div>
            {% ENDFOR %}
        </div>
    </div>
    {% ENDIF %}
</section>

<style>
.progression-profile {
    max-width: 1200px;
    margin: 0 auto;
}

/* Stats principales avec couleurs du thème Lumiere */
.progression-stats-main {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin: 1.5rem 0;
}

.stat-card {
    flex: 1;
    min-width: 200px;
    padding: 1.5rem;
    border-radius: var(--panel-radius);
    text-align: center;
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: var(--panel-shadow);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.stat-card-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover-color) 100%);
    color: white;
}

.stat-card-actions {
    background: linear-gradient(135deg, var(--info-color) 0%, var(--info-hover-color) 100%);
    color: white;
}

.stat-card-palier {
    background: var(--panel-bg);
    border: 2px solid var(--primary-color);
}

.stat-card-next {
    background: var(--panel-bg);
    border: 2px solid var(--success-color);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-label {
    opacity: 0.9;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.stat-palier-name {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: var(--font-color);
}

.stat-card-primary .stat-label,
.stat-card-actions .stat-label {
    color: white;
}

/* Métriques du jour */
.progression-today-metrics {
    margin: 2rem 0;
}

.progression-today-metrics h3 {
    margin-bottom: 1rem;
    color: var(--font-color);
}

.today-metrics-flex {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.today-metric-card {
    flex: 1;
    min-width: 150px;
    padding: 1rem;
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--panel-radius);
    text-align: center;
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.today-metric-card:hover {
    transform: translateY(-3px);
    border-color: var(--primary-color);
}

.today-metric-value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.today-metric-label {
    font-size: 0.85rem;
    color: var(--font-color);
    opacity: 0.8;
}

/* Graphique de motivation */
.today-motivation-chart {
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--panel-radius);
}

.chart-bars {
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    gap: 0.5rem;
    height: 120px;
    margin-bottom: 1rem;
    padding: 0 1rem;
}

.chart-bar {
    flex: 1;
    min-width: 30px;
    max-width: 60px;
    background: linear-gradient(to top, var(--primary-color), var(--primary-hover-color));
    border-radius: 4px 4px 0 0;
    transition: all 0.3s ease;
    position: relative;
}

.chart-bar:hover {
    opacity: 0.8;
    transform: scaleY(1.05);
}

.chart-bar::after {
    content: attr(title);
    position: absolute;
    bottom: -20px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.7rem;
    color: var(--font-color);
    opacity: 0.6;
    white-space: nowrap;
}

.chart-encouragement {
    text-align: center;
    margin: 0;
    font-size: 1rem;
    color: var(--font-color);
    font-weight: 500;
}

.chart-encouragement i {
    margin-right: 0.5rem;
    color: var(--primary-color);
}

/* Badge actuel */
.current-badge {
    text-align: center;
    padding: 2rem;
    background: var(--panel-bg);
    border-radius: var(--panel-radius);
    border: 1px solid var(--panel-border);
    margin: 1.5rem 0;
}

/* Progression */
.progress-section {
    margin: 1.5rem 0;
}

.progress-section h3 {
    color: var(--font-color);
    margin-bottom: 0.5rem;
}

/* Paliers */
.palier-card {
    text-align: center;
    padding: 1rem;
    border: 2px solid var(--panel-border);
    border-radius: var(--panel-radius);
    background: var(--panel-bg);
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.palier-card:hover {
    transform: scale(1.05);
    border-color: var(--primary-color);
}

/* Historique */
.transactions-history {
    margin: 1.5rem 0;
}

.transactions-history h3 {
    color: var(--font-color);
    margin-bottom: 1rem;
}

.transactions-history table {
    width: 100%;
    border-collapse: collapse;
    background: var(--panel-bg);
    border-radius: var(--panel-radius);
    overflow: hidden;
}

.transactions-history thead {
    background: var(--primary-color);
    color: white;
}

.transactions-history th {
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
}

.transactions-history td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--panel-border);
}

.transactions-history tbody tr:hover {
    background: rgba(0, 0, 0, 0.02);
}

/* Badge actuel */
.current-badge h3 {
    margin-bottom: 1rem;
    color: var(--font-color);
}

.badge-circle {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: white;
    border-radius: 50%;
    width: 150px;
    height: 150px;
    margin: 0 auto;
}

.badge-circle i {
    font-size: 4rem;
    margin-bottom: 0.5rem;
}

.badge-name {
    font-weight: bold;
    font-size: 1.2rem;
}

.badge-palier-name {
    margin-top: 1rem;
    color: var(--font-color);
    opacity: 0.8;
}

/* Progression */
.progress-bar-container {
    background: var(--panel-border);
    border-radius: 10px;
    height: 30px;
    position: relative;
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-bar-fill {
    height: 100%;
    transition: width 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    border-radius: 10px;
}

.progress-text {
    margin-top: 0.5rem;
    color: var(--font-color);
    opacity: 0.8;
    text-align: center;
}

/* Paliers */
.all-paliers {
    margin: 1.5rem 0;
}

.all-paliers h3 {
    color: var(--font-color);
    margin-bottom: 1rem;
}

.paliers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.palier-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.palier-name {
    font-weight: bold;
    margin-bottom: 0.25rem;
    color: var(--font-color);
}

.palier-points {
    font-size: 0.875rem;
    color: var(--font-color);
    opacity: 0.7;
}

.palier-status {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    font-weight: bold;
}

.palier-status-current {
    color: var(--primary-color);
}

.palier-status-reached {
    color: var(--success-color);
}

/* Historique */
.points-positive {
    color: var(--success-color);
    font-weight: bold;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--font-color);
    opacity: 0.6;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

/* Actions stats */
.actions-stats {
    margin: 1.5rem 0;
}

.actions-stats h3 {
    color: var(--font-color);
    margin-bottom: 1rem;
}

.actions-flex {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
}

.action-stat-card {
    flex: 1;
    min-width: 200px;
    padding: 1rem;
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--panel-radius);
    text-align: center;
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.action-stat-card:hover {
    transform: translateY(-3px);
    border-color: var(--primary-color);
}

.action-stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.action-stat-label {
    color: var(--font-color);
    opacity: 0.8;
}

/* Responsive */
@media (max-width: 768px) {
    .progression-stats-main {
        flex-direction: column;
    }
    
    .stat-card {
        min-width: 100%;
    }
    
    .today-metrics-flex {
        flex-direction: column;
    }
    
    .today-metric-card {
        min-width: 100%;
    }
    
    .chart-bars {
        height: 100px;
        gap: 0.25rem;
    }
    
    .chart-bar {
        min-width: 25px;
        max-width: 40px;
    }
    
    .chart-bar::after {
        font-size: 0.6rem;
        bottom: -18px;
    }
    
    .stat-value {
        font-size: 2rem;
    }
    
    .stat-palier-name {
        font-size: 1.25rem;
    }
    
    .transactions-history table {
        font-size: 0.9rem;
    }
    
    .transactions-history th,
    .transactions-history td {
        padding: 0.5rem;
    }
    
    .current-badge {
        padding: 1.5rem;
    }
    
    .badge-circle {
        width: 120px;
        height: 120px;
        padding: 1.5rem;
    }
    
    .badge-circle i {
        font-size: 3rem;
    }
    
    .paliers-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .actions-flex {
        flex-direction: column;
    }
    
    .action-stat-card {
        min-width: 100%;
    }
}
</style>

