<section class="module-card">
    <header><i class="fa-solid fa-chart-bar"></i> {{ Lang.sondage-admin-list }}</header>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="tabs-container" data-tab-key="sondage">
        <ul class="tabs-header">
            <li class="default-tab" data-tab="list"><i class="fa-solid fa-list"></i> {{ Lang.sondage-admin-list }}</li>
            <li data-tab="create"><i class="fa-solid fa-plus"></i> {{ Lang.sondage-admin-add }}</li>
            <li data-tab="stats"><i class="fa-solid fa-chart-pie"></i> {{ Lang.sondage.stats-title }}</li>
        </ul>

        <ul class="tabs">
            <li class="tab">
                <div class="module-actions">
                    <a class="button" href="{{ addUrl }}"><i class="fa-solid fa-plus"></i> {{ Lang.sondage-admin-add }}</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ Lang.sondage.title }}</th>
                                <th>{{ Lang.sondage.status }}</th>
                                <th>{{ Lang.sondage.votes }}</th>
                                <th>{{ Lang.sondage.date }}</th>
                                <th>{{ Lang.sondage.actions }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        {% for sondage in sondageManager.getItems() %}
                            <tr>
                                <td>
                                    <a title="{{ Lang.edit }}" href="{ ROUTER.generate("sondage-admin-edit-id", ["id" => sondage.getId()]) }"><strong>{{ sondage.getTitle() }}</strong></a>
                                </td>
                                <td>
                                    {% if sondage.getActive() %}{{ Lang.core-active }}{% else %}{{ Lang.core-inactive }}{% endif %}
                                </td>
                                <td>{{ sondage.getTotalVotes() }}</td>
                                <td>{{ util.getDate(sondage.getDate()) }}</td>
                                <td style="white-space: nowrap;">
                                    <a title="{{ Lang.edit }}" href="{ ROUTER.generate("sondage-admin-edit-id", ["id" => sondage.getId()]) }" class="button small">{{ Lang.edit }}</a>
                                    <a title="{{ Lang.delete }}" href="{ ROUTER.generate("sondage-admin-delete", ["id" => sondage.getId(), "token" => token]) }" class="button alert small" onclick="return confirm('{{ Lang.confirm.deleteItem }}')">{{ Lang.delete }}</a>
                                </td>
                            </tr>
                        {% endfor %}
                        </tbody>
                    </table>
                </div>
            </li>

            <li class="tab">
        <form method="post" action="{{ saveUrl }}">
            {{ SHOW.tokenField }}
            <input type="hidden" name="id" value="" />
            
            <div class="form-section">
                <label for="title-new">{{ Lang.sondage.title }}</label>
                <input type="text" name="title" id="title-new" value="" required placeholder="{{ Lang.sondage.title-placeholder }}" />
            </div>
            
            <div class="form-section">
                <label>{{ Lang.sondage.options }}</label>
                <div id="options-container-new" class="options-container">
                    <div class="option-row">
                        <input type="text" name="options[]" required placeholder="{{ Lang.sondage.option-placeholder }}" />
                        <button type="button" class="button alert small remove-option">{{ Lang.sondage.delete-option }}</button>
                    </div>
                    <div class="option-row">
                        <input type="text" name="options[]" required placeholder="{{ Lang.sondage.option-placeholder }}" />
                        <button type="button" class="button alert small remove-option">{{ Lang.sondage.delete-option }}</button>
                    </div>
                </div>
                    <button type="button" id="add-option-new" class="button secondary">{{ Lang.sondage.add-option }}</button>
                </div>
                
                <div class="form-section">
                    <p class="checkbox-field">
                        <input type="checkbox" name="isMultipleChoice" id="multiple-new" value="1" />
                        <label for="multiple-new">{{ Lang.sondage.multiple-choice }}</label>
                    </p>
                    <p class="checkbox-field">
                        <input type="checkbox" name="allowOther" id="other-new" value="1" />
                        <label for="other-new">{{ Lang.sondage.allow-other }}</label>
                    </p>
                </div>
            
            <div class="form-section">
                <p class="checkbox-field">
                    <input type="checkbox" name="active" id="active-new" checked />
                    <label for="active-new">{{ Lang.sondage.active }}</label>
                    <small>{{ Lang.sondage.active-help }}</small>
                </p>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button primary">{{ Lang.save }}</button>
            </div>
        </form>
            </li>

            <li class="tab">
        {% if count(allSondagesStats) == 0 %}
            <p>{{ Lang.sondage.no-sondages }}</p>
        {% else %}
            {% for sondageStats in allSondagesStats %}
                <div class="sondage-stats" style="margin-bottom: 2rem; padding: 1.5rem; background: #f9f9f9; border-radius: 8px;">
                    <h3>{{ sondageStats.title }}</h3>
                    <div class="stats-summary" style="display: flex; gap: 2rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e0e0e0; align-items: center;">
                        <div class="stat-item">
                            <span class="stat-label">{{ Lang.sondage.totalVotes }}:</span>
                            <span class="stat-value">{{ sondageStats.totalVotes }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Taux de participation:</span>
                            <span class="stat-value">{{ sondageStats.participationRate }}%</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">{{ Lang.sondage.status }}:</span>
                            <span class="stat-value">{% if sondageStats.active %}{{ Lang.core-active }}{% else %}{{ Lang.core-inactive }}{% endif %}</span>
                        </div>
                    </div>
                
                    {% if sondageStats.totalVotes > 0 %}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                        <!-- Graphique Résultats Globaux -->
                        <div class="chart-container" style="position: relative; height: 300px;">
                            <h4 style="text-align: center; margin-bottom: 1rem; color: #4e73df;">Répartition des Votes</h4>
                            <canvas id="chart-results-{{sondageStats.id}}"></canvas>
                        </div>
                        
                        <!-- Détail des votes (Liste existante) -->
                        <div class="options-stats">
                            <h4>Détails</h4>
                            {% for stat in sondageStats.optionsStats %}
                                <div class="option-stat-row" style="margin-bottom: 1rem;">
                                    <div class="option-stat-header" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span class="option-stat-text">{{ stat.text }}</span>
                                        <span class="option-stat-count">{{ stat.votes }} ({{ stat.percentage }}%)</span>
                                    </div>
                                    <div class="option-stat-bar" style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden;">
                                        <div class="option-stat-progress" style="background: #007bff; height: 100%; width: {{ stat.percentage }}%; transition: width 0.3s ease;"></div>
                                    </div>
                                </div>
                            {% endfor %}
                        </div>
                        
                        <!-- Graphiques Démographiques -->
                        <div class="demographics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px dashed #e0e0e0;">
                            <div class="demo-chart-container">
                                <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem; text-align: center; color: #666;">Répartition par Sexe</h4>
                                <canvas id="chart-sex-{{sondageStats.id}}" style="max-height: 200px;"></canvas>
                            </div>
                            <div class="demo-chart-container">
                                <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem; text-align: center; color: #666;">Répartition par Âge</h4>
                                <canvas id="chart-age-{{sondageStats.id}}" style="max-height: 200px;"></canvas>
                            </div>
                            <div class="demo-chart-container">
                                <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem; text-align: center; color: #666;">Top Villes</h4>
                                <canvas id="chart-city-{{sondageStats.id}}" style="max-height: 200px;"></canvas>
                            </div>
                            <div class="demo-chart-container">
                                <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem; text-align: center; color: #666;">Top Quartiers</h4>
                                <canvas id="chart-district-{{sondageStats.id}}" style="max-height: 200px;"></canvas>
                            </div>
                        </div>
                    </div>
                    {% else %}
                        <p style="color: #666; font-style: italic;">{{ Lang.sondage.no-votes-yet }}</p>
                    {% endif %}
                </div>
            {% endfor %}
        {% endif %}
            </li>
        </ul>
    </div>
</section>

<style>
.sondage-stats {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
}
.sondage-stats h3 {
    margin: 0 0 1rem 0;
    color: #0f172a;
}
.stats-summary {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}
.stat-item {
    display: flex;
    flex-direction: column;
}
.stat-label {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}
.stat-value {
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
}
.options-stats {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.option-stat-row {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.option-stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.option-stat-text {
    font-weight: 600;
    color: #334155;
}
.option-stat-count {
    font-size: 0.9rem;
    color: #64748b;
}
.option-stat-bar {
    height: 20px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}
.option-stat-progress {
    height: 100%;
    background: var(--primary-color, #1f62a9);
    border-radius: 10px;
    transition: width 0.6s ease;
}
.form-section {
    margin-bottom: 1.5rem;
}
.form-section label {
    display: block;
    margin-bottom: 0.4rem;
    font-weight: 600;
    color: #0f172a;
}
.options-container {
    margin-bottom: 1rem;
}
.option-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    align-items: center;
}
.option-row input[type="text"] {
    flex: 1;
    margin: 0;
}
.checkbox-field {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.checkbox-field label {
    margin: 0;
    font-weight: 500;
}
.checkbox-field small {
    display: block;
    width: 100%;
    color: #64748b;
    font-size: 0.85rem;
}
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const sondageId = parseInt(urlParams.get('sondage_id') || '0', 10);
    if (sondageId > 0) {
        const statsLi = document.querySelector('.tabs-container[data-tab-key="sondage"] > .tabs-header > li[data-tab="stats"]');
        if (statsLi) statsLi.click();
    }
    
    // Gestion des options dans le formulaire de création
    document.getElementById('add-option-new')?.addEventListener('click', function() {
        const container = document.getElementById('options-container-new');
        const row = document.createElement('div');
        row.className = 'option-row';
        row.innerHTML = '<input type="text" name="options[]" required placeholder="{{ Lang.sondage.option-placeholder }}" /> <button type="button" class="button alert small remove-option">Supprimer</button>';
        container.appendChild(row);
    });
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-option')) {
            const container = e.target.closest('.options-container');
            if (container && container.children.length > 1) {
                e.target.closest('.option-row').remove();
            }
        }
    });

    // Initialisation des graphiques admin
    {% for sondageStats in allSondagesStats %}
        {% if sondageStats.totalVotes > 0 %}
            (function() {
                const sondageId = {{sondageStats.id}};
                
                // Graphique Résultats Globaux
                const labelsGlobal = [];
                const dataGlobal = [];
                {% for label in sondageStats.optionsLabels %}
                    labelsGlobal.push("{{label}}");
                {% endfor %}
                {% for rv in sondageStats.optionsVotes %}
                    dataGlobal.push({{rv}});
                {% endfor %}

                new Chart(document.getElementById('chart-results-' + sondageId), {
                    type: 'pie',
                    data: {
                        labels: labelsGlobal,
                        datasets: [{
                            data: dataGlobal,
                            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69']
                        }]
                    },
                    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
                });
                
                // Graphique Sexe
                new Chart(document.getElementById('chart-sex-' + sondageId), {
                    type: 'doughnut',
                    data: {
                        labels: ['Homme', 'Femme', 'Autre', 'Inconnu'],
                        datasets: [{
                            data: [
                                {{ sondageStats.participation.sexes.M }},
                                {{ sondageStats.participation.sexes.F }},
                                {{ sondageStats.participation.sexes.O }},
                                {{ sondageStats.participation.sexes.unknown }}
                            ],
                            backgroundColor: ['#4e73df', '#e74a3b', '#f6c23e', '#858796']
                        }]
                    },
                    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
                });

                // Graphique Age
                new Chart(document.getElementById('chart-age-' + sondageId), {
                    type: 'bar',
                    data: {
                        labels: ['-18', '18-25', '26-35', '36-50', '51-65', '65+', '?'],
                        datasets: [{
                            label: 'Votants',
                            data: [
                                {{ sondageStats.participation.ages.age_minus_18 }},
                                {{ sondageStats.participation.ages.age_18_25 }},
                                {{ sondageStats.participation.ages.age_26_35 }},
                                {{ sondageStats.participation.ages.age_36_50 }},
                                {{ sondageStats.participation.ages.age_51_65 }},
                                {{ sondageStats.participation.ages.age_65_plus }},
                                {{ sondageStats.participation.ages.age_unknown }}
                            ],
                            backgroundColor: '#36b9cc'
                        }]
                    },
                    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                });

                // Graphique Villes (Top 5)
                const cityLabels = [];
                const cityData = [];
                
                {% for city, votes in sondageStats.top5Cities %}
                    cityLabels.push("{{city}}");
                    cityData.push({{votes}});
                {% endfor %}
                
                new Chart(document.getElementById('chart-city-' + sondageId), {
                    type: 'bar',
                    data: {
                        labels: cityLabels,
                        datasets: [{
                            data: cityData,
                            backgroundColor: '#1cc88a'
                        }]
                    },
                    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
                });

                // Graphique Quartiers (Top 5)
                const districtLabels = [];
                const districtData = [];

                {% for district, votes in sondageStats.top5Districts %}
                    districtLabels.push("{{district}}");
                    districtData.push({{votes}});
                {% endfor %}

                new Chart(document.getElementById('chart-district-' + sondageId), {
                    type: 'bar',
                    data: {
                        labels: districtLabels,
                        datasets: [{
                            data: districtData,
                            backgroundColor: '#f6c23e'
                        }]
                    },
                    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
                });
            })();
        {% endif %}
    {% endfor %}
});
</script>
