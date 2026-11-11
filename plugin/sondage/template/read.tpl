<section>
    <div class="sondage-back-button">
        <a href="{{ listUrl }}" class="button">
            <i class="fa-solid fa-arrow-left"></i>
            <span>{{ Lang.sondage.back-to-list }}</span>
        </a>
    </div>
    
    {% if isClosed %}
        <div class="sondage-results">
            <h3>{{ Lang.sondage.results }}</h3>
            <div class="sondage-total">{{ totalVotesText }}</div>
            
            <div class="sondage-results-wrapper">
                <canvas id="resultsChart"></canvas>
            </div>

            <div class="sondage-results-list">
                {% for option in optionsData %}
                    <div class="sondage-option-result">
                        <div class="option-header">
                            <span class="option-text">{{ option.text }}</span>
                            <span class="option-stats">{{ option.votes }} vote(s) ({{ option.percentage }}%)</span>
                        </div>
                        <div class="option-bar">
                            <div class="option-progress" style="width: {{ option.percentage }}%;"></div>
                        </div>
                    </div>
                {% endfor %}
            </div>
            
            {% if count(otherVotes) > 0 %}
                <div class="sondage-other-results">
                    <h4>{{ Lang.sondage.other-responses }}</h4>
                    <ul>
                        {% for other in otherVotes %}
                            <li>{{ other }}</li>
                        {% endfor %}
                    </ul>
                </div>
            {% endif %}
        <div class="sondage-results-demographics">
            <h3>Participation par profil</h3>
            
            <div class="demographics-grid">
                <!-- Sexe -->
                <div class="demographics-card">
                    <h4>Répartition par sexe</h4>
                    <div class="chart-container">
                        <canvas id="sexChart"></canvas>
                    </div>
                </div>
                
                <!-- Age -->
                <div class="demographics-card">
                    <h4>Répartition par âge</h4>
                    <div class="chart-container">
                        <canvas id="ageChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="demographics-grid">
                <!-- Ville -->
                <div class="demographics-card">
                    <h4>Top villes</h4>
                    <div class="chart-container-tall">
                        <canvas id="cityChart"></canvas>
                    </div>
                </div>
                
                <!-- Quartier -->
                <div class="demographics-card">
                    <h4>Top quartiers</h4>
                    <div class="chart-container-tall">
                        <canvas id="districtChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const colors = ['#667eea', '#764ba2', '#ff6b6b', '#feca57', '#48dbfb', '#ff9f43', '#1dd1a1', '#54a0ff', '#5f27cd', '#c0392b'];

                // Chart Résultats Main
                (function() {
                    const ctx = document.getElementById('resultsChart').getContext('2d');
                    const labels = {{ jsonChartLabels }};
                    const data = {{ jsonChartData }};
                    
                    Chart.defaults.font.family = "'Outfit', system-ui, -apple-system, sans-serif";
                    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--font-secondary').trim() || '#6c757d';

                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: colors.slice(0, labels.length),
                                borderWidth: 0
                            }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: { 
                                legend: { 
                                    position: 'bottom',
                                    labels: {
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--font-color').trim() || '#2c3e50'
                                    }
                                } 
                            } 
                        }
                    });
                })();

                // Chart Sexes
                (function() {
                    const ctx = document.getElementById('sexChart').getContext('2d');
                    const labels = ['Homme', 'Femme', 'Autre', 'Inconnu'];
                    const data = [
                        {{ participationStats.sexes.M }},
                        {{ participationStats.sexes.F }},
                        {{ participationStats.sexes.O }},
                        {{ participationStats.sexes.unknown }}
                    ];
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: [
                                    getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#54a0ff', 
                                    getComputedStyle(document.documentElement).getPropertyValue('--alert-color').trim() || '#ff6b6b', 
                                    getComputedStyle(document.documentElement).getPropertyValue('--warning-color').trim() || '#feca57', 
                                    getComputedStyle(document.documentElement).getPropertyValue('--font-muted').trim() || '#c8d6e5'
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: { 
                                legend: { 
                                    position: 'bottom',
                                    labels: {
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--font-color').trim() || '#2c3e50'
                                    }
                                } 
                            },
                            cutout: '70%'
                        }
                    });
                })();

                // Chart Ages
                (function() {
                    const ctx = document.getElementById('ageChart').getContext('2d');
                    const labels = ['-18', '18-25', '26-35', '36-50', '51-65', '65+', '?'];
                    const data = [
                        {{ participationStats.ages.age_minus_18 }},
                        {{ participationStats.ages.age_18_25 }},
                        {{ participationStats.ages.age_26_35 }},
                        {{ participationStats.ages.age_36_50 }},
                        {{ participationStats.ages.age_51_65 }},
                        {{ participationStats.ages.age_65_plus }},
                        {{ participationStats.ages.age_unknown }}
                    ];
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Participants',
                                data: data,
                                backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#764ba2',
                                borderRadius: 4
                            }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } }, 
                            scales: { 
                                y: { 
                                    beginAtZero: true, 
                                    ticks: { 
                                        stepSize: 1,
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--font-muted').trim() || '#6c757d'
                                    },
                                    grid: {
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--form-border-color').trim() || 'rgba(0,0,0,0.1)'
                                    }
                                },
                                x: {
                                    ticks: {
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--font-muted').trim() || '#6c757d'
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            } 
                        }
                    });
                })();

                // Chart Villes
                (function() {
                    const ctx = document.getElementById('cityChart').getContext('2d');
                    const rawData = {{ jsonCities }};
                    const sorted = Object.entries(rawData || {}).sort((a,b) => b[1] - a[1]).slice(0, 5);
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: sorted.map(i => i[0]),
                            datasets: [{
                                label: 'Votes',
                                data: sorted.map(i => i[1]),
                                backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--success-color').trim() || '#1dd1a1',
                                borderRadius: 4
                            }]
                        },
                        options: { 
                            indexAxis: 'y', 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } }, 
                            scales: { 
                                x: { 
                                    beginAtZero: true, 
                                    ticks: { 
                                        stepSize: 1,
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--font-muted').trim() || '#6c757d'
                                    },
                                    grid: {
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--form-border-color').trim() || 'rgba(0,0,0,0.1)'
                                    }
                                },
                                y: {
                                    ticks: {
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--font-muted').trim() || '#6c757d'
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            } 
                        }
                    });
                })();

                // Chart Quartiers
                (function() {
                    const ctx = document.getElementById('districtChart').getContext('2d');
                    const rawData = {{ jsonDistricts }};
                    const sorted = Object.entries(rawData || {}).sort((a,b) => b[1] - a[1]).slice(0, 5);
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: sorted.map(i => i[0]),
                            datasets: [{
                                label: 'Votes',
                                data: sorted.map(i => i[1]),
                                backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--warning-color').trim() || '#ff9f43',
                                borderRadius: 4
                            }]
                        },
                        options: { 
                            indexAxis: 'y', 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } }, 
                            scales: { 
                                x: { 
                                    beginAtZero: true, 
                                    ticks: { 
                                        stepSize: 1,
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--font-muted').trim() || '#6c757d'
                                    },
                                    grid: {
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--form-border-color').trim() || 'rgba(0,0,0,0.1)'
                                    }
                                },
                                y: {
                                    ticks: {
                                        color: getComputedStyle(document.documentElement).getPropertyValue('--font-muted').trim() || '#6c757d'
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            } 
                        }
                    });
                })();
            });
        </script>
    {% elseif hasVoted %}
        <div class="sondage-voted-message">
            <i class="fa-solid fa-circle-check"></i>
            <h3>{{ Lang.sondage.thank-you-vote }}</h3>
            <p>{{ Lang.sondage.results-later-help }}</p>
        </div>
    {% else %}
        {% if IS_LOGGED %}
            <form method="post" action="{{ voteUrl }}">
                {{ SHOW.tokenField }}
                
                <div class="sondage-options">
                    <label class="sondage-options-label">{% if sondage.getIsMultipleChoice() %}{{ Lang.sondage.choose-multiple }}{% else %}{{ Lang.sondage.choose-one }}{% endif %}:</label>
                    {% for option in optionsData %}
                        <label class="sondage-option">
                            <input type="{% if sondage.getIsMultipleChoice() %}checkbox{% else %}radio{% endif %}" name="options[]" value="{{ option.index }}" />
                            <span>{{ option.text }}</span>
                        </label>
                    {% endfor %}
                    
                    {% if sondage.getAllowOther() %}
                        <div class="sondage-option-other">
                            <label for="other_text">{{ Lang.sondage.other-option-label }}:</label>
                            <input type="text" name="other_text" id="other_text" placeholder="{{ Lang.sondage.other-placeholder }}" />
                        </div>
                    {% endif %}
                </div>
                
                <button type="submit" class="button sondage-vote-button">{{ Lang.sondage.vote }}</button>
            </form>
        {% else %}
            <div class="sondage-login-required">
                <p>{{ Lang.sondage.must-be-logged-to-vote }}</p>
                <a href="{ ROUTER.generate("auth-login") }" class="button primary">{{ Lang.login }}</a>
                
                <div class="sondage-preview-options">
                    <label class="sondage-options-label">{{ Lang.sondage.options-preview }}:</label>
                    <ul>
                        {% for option in optionsData %}
                            <li>{{ option.text }}</li>
                        {% endfor %}
                    </ul>
                </div>
            </div>
        {% endif %}
    {% endif %}
</section>
