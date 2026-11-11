<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="public-stats-section">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            {% if stats.general %}
            <small class="text-muted">Mise à jour : {{ stats.general.lastUpdate }}</small>
            {% endif %}
        </div>
    </div>

    <!-- TABS NAVIGATION (Profile Style) -->
    <div class="profile-tabs-nav" role="tablist">
        <button class="profile-tab-button active" role="tab" aria-selected="true" aria-controls="tab-overview" id="tab-button-overview" data-tab="overview">
            <i class="fa-solid fa-chart-pie"></i> <span>Vue d'ensemble</span>
        </button>
        <button class="profile-tab-button" role="tab" aria-selected="false" aria-controls="tab-members" id="tab-button-members" data-tab="members">
            <i class="fa-solid fa-users"></i> <span>Membres</span>
        </button>
        <button class="profile-tab-button" role="tab" aria-selected="false" aria-controls="tab-content" id="tab-button-content" data-tab="content">
            <i class="fa-solid fa-comments"></i> <span>Contenu</span>
        </button>
        <button class="profile-tab-button" role="tab" aria-selected="false" aria-controls="tab-districts" id="tab-button-districts" data-tab="districts">
            <i class="fa-solid fa-map-marked-alt"></i> <span>Quartiers</span>
        </button>
    </div>

    <!-- TABS CONTENT -->
    <div class="profile-tabs mt-4">

        <!-- TAB 1: OVERVIEW -->
        <div class="profile-tab-content active" id="tab-overview" role="tabpanel" aria-labelledby="tab-button-overview">
            <div class="row g-4">
                
                <!-- Activity Cards -->
                {% if stats.activity %}
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded me-3 text-primary">
                                <i class="fa-solid fa-user-plus stat-card-icon"></i>
                            </div>
                            <div>
                                <div class="stat-value text-primary">{{ stats.activity.new_users_7d }}</div>
                                <div class="stat-label">Nouveaux (7j)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 p-3 rounded me-3 text-info">
                                <i class="fa-solid fa-comments stat-card-icon"></i>
                            </div>
                            <div>
                                <div class="stat-value text-info">{{ stats.activity.new_posts_7d }}</div>
                                <div class="stat-label">Messages (7j)</div>
                            </div>
                        </div>
                    </div>
                </div>
                {% endif %}

                {% if stats.polls %}
                <div class="col-md-4">
                     <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 p-3 rounded me-3 text-warning">
                                <i class="fa-solid fa-poll stat-card-icon"></i>
                            </div>
                            <div>
                                <div class="stat-value text-warning">{{ stats.polls.total }}</div>
                                <div class="stat-label">Sondages</div>
                            </div>
                        </div>
                    </div>
                </div>
                {% endif %}

                <!-- Growth Chart -->
                {% if stats.advanced.user_growth %}
                <div class="col-12">
                     <div class="card border-0 shadow-sm">
                        <div class="card-body">
                             <h6 class="card-title text-muted mb-3">Croissance de la communauté (12 mois)</h6>
                             <div class="chart-container">
                                <canvas id="growthChartOverview"></canvas>
                             </div>
                        </div>
                     </div>
                </div>
                {% endif %}

                <!-- Polls Chart Detail -->
                {% if stats.polls %}
                 <div class="col-md-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                             <h6 class="card-title text-muted mb-3">Participation Sondages</h6>
                             <div class="chart-container-sm">
                                <canvas id="pollsChart"></canvas>
                             </div>
                        </div>
                    </div>
                 </div>
                 {% endif %}
            </div>
        </div>

        <!-- TAB 2: MEMBERS -->
        <div class="profile-tab-content" id="tab-members" role="tabpanel" aria-labelledby="tab-button-members">
             {% if stats.users %}
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">Répartition par Âge</h6>
                            <div class="chart-container">
                                <canvas id="usersAgeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                     <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">Répartition par Genre</h6>
                             <div class="row align-items-center h-100">
                                <div class="col-6">
                                     <ul class="list-unstyled">
                                        <li class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                            <span><i class="fa-solid fa-mars text-primary me-2"></i> Hommes</span>
                                            <span class="fw-bold">{{ stats.users.sexDistribution.M }}</span>
                                        </li>
                                        <li class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                            <span><i class="fa-solid fa-venus text-danger me-2"></i> Femmes</span>
                                            <span class="fw-bold">{{ stats.users.sexDistribution.F }}</span>
                                        </li>
                                        <li class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                            <span><i class="fa-solid fa-genderless text-secondary me-2"></i> Autre</span>
                                            <span class="fw-bold">{{ stats.users.sexDistribution.O }}</span>
                                        </li>
                                         <li class="d-flex justify-content-between text-muted small">
                                            <span>Non spécifié</span>
                                            <span>{{ stats.users.sexDistribution.non_specifie }}</span>
                                        </li>
                                     </ul>
                                </div>
                                <div class="col-6 text-center border-start">
                                    <div class="display-4 fw-bold text-primary">{{ stats.users.total }}</div>
                                    <div class="text-muted small">Membres Inscrits</div>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
            {% endif %}
        </div>

        <!-- TAB 3: CONTENT -->
        <div class="profile-tab-content" id="tab-content" role="tabpanel" aria-labelledby="tab-button-content">
            {% if stats.forum %}
            <div class="row g-4">
                 <div class="col-12">
                     <div class="card border-0 shadow-sm bg-primary text-white">
                         <div class="card-body py-4">
                             <div class="row text-center">
                                 <div class="col-md-4 border-end border-white-50">
                                     <div class="h3 fw-bold mb-0">{{ stats.forum.categories }}</div>
                                     <small class="opacity-75">Catégories</small>
                                 </div>
                                 <div class="col-md-4 border-end border-white-50">
                                      <div class="h3 fw-bold mb-0">{{ stats.forum.topics }}</div>
                                     <small class="opacity-75">Sujets</small>
                                 </div>
                                 <div class="col-md-4">
                                      <div class="h3 fw-bold mb-0">{{ stats.forum.posts }}</div>
                                     <small class="opacity-75">Messages</small>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
                 
                 {% if stats.advanced.topic_growth %}
                 <div class="col-12">
                     <div class="card border-0 shadow-sm h-100">
                         <div class="card-body">
                             <h6 class="card-title text-muted mb-3">Nouveaux Sujets (12 derniers mois)</h6>
                              <div class="chart-container">
                                <canvas id="topicGrowthChart"></canvas>
                             </div>
                         </div>
                     </div>
                 </div>
                 {% endif %}
            </div>
            {% endif %}
        </div>

        <!-- TAB 4: DISTRICTS -->
        <div class="profile-tab-content" id="tab-districts" role="tabpanel" aria-labelledby="tab-button-districts">
             {% if stats.districts %}
              <div class="card border-0 shadow-sm">
                  <div class="card-body">
                      <h6 class="card-title text-muted mb-3">Répartition des membres par Quartier</h6>
                      <div class="chart-container-md">
                          <canvas id="districtsChart"></canvas>
                      </div>
                  </div>
              </div>
            {% endif %}
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    // --- CHART HELPER ---
    function createChart(ctxId, type, data, options = {}) {
        const ctx = document.getElementById(ctxId);
        if (ctx) {
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            };
            // Merge options (deep merge would be better but simple spread works for first level)
            const finalOptions = { ...defaultOptions, ...options };
            
            // Nested merge for plugins and scales if provided
            if(options.plugins) { 
                finalOptions.plugins = { ...defaultOptions.plugins, ...options.plugins }; 
            }
            if(options.scales) {
                finalOptions.scales = { ...defaultOptions.scales, ...options.scales };
            }
            // For indexAxis which is top-level
            if(options.indexAxis) {
                finalOptions.indexAxis = options.indexAxis;
            }

            new Chart(ctx, { type, data, options: finalOptions });
        }
    }

    Chart.defaults.font.family = "'Outfit', system-ui, -apple-system, sans-serif";
    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--font-secondary').trim() || '#6c757d';

    // Growth
    {% if stats.advanced.user_growth %}
    createChart('growthChartOverview', 'line', {
        labels: {{ stats.json.growth_labels }},
        datasets: [{
            label: 'Membres',
            data: {{ stats.json.growth_users }},
            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#0d6efd',
            backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--dc-accent-soft').trim() || 'rgba(29, 78, 216, 0.1)',
            tension: 0.4,
            fill: true
        }]
    }, { scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4] }, ticks: { precision: 0 } }, x: { grid: { display: false } } } });
    {% endif %}

    // Polls
    {% if stats.polls %}
    createChart('pollsChart', 'doughnut', {
        labels: ['Actifs', 'Terminés'],
        datasets: [{
             data: [{{ stats.polls.active }}, {{ stats.polls.closed }}],
             backgroundColor: [
                 getComputedStyle(document.documentElement).getPropertyValue('--warning-color').trim() || '#ffc107',
                 getComputedStyle(document.documentElement).getPropertyValue('--font-muted').trim() || '#6c757d'
             ],
             borderWidth: 0
        }]
    }, { plugins: { legend: { position: 'right' } }, cutout: '70%' });
    {% endif %}

    // Age
    {% if stats.users %}
    createChart('usersAgeChart', 'doughnut', {
        labels: {{ stats.json.age_labels }},
        datasets: [{
            data: {{ stats.json.age_data }},
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
            borderWidth: 2
        }]
    }, { plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } }, cutout: '60%' });
    {% endif %}

    // Topics
    {% if stats.advanced.topic_growth %}
    createChart('topicGrowthChart', 'bar', {
        labels: {{ stats.json.growth_labels }},
        datasets: [{
            label: 'Nouveaux Sujets',
            data: {{ stats.json.growth_topics }},
             backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--infos-color').trim() || '#0dcaf0',
             borderRadius: 4
        }]
    }, { scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } });
    {% endif %}

    // Districts
    {% if stats.districts %}
    createChart('districtsChart', 'bar', {
        labels: {{ stats.json.districts_labels }},
        datasets: [{
            label: 'Membres',
            data: {{ stats.json.districts_data }},
            backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--success-color').trim() || '#198754',
            borderRadius: 4
        }]
    }, { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } });
    {% endif %}


    // --- TABS MANAGEMENT ---
    const tabButtons = document.querySelectorAll('.profile-tab-button[data-tab]');
    const tabContents = document.querySelectorAll('.profile-tab-content');
    
    function switchTab(targetTab) {
        // Deactivate all
        tabButtons.forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });
        tabContents.forEach(content => {
            content.classList.remove('active');
        });
        
        // Activate target
        const activeBtn = document.querySelector(`.profile-tab-button[data-tab="${targetTab}"]`);
        const activeContent = document.getElementById('tab-' + targetTab);
        
        if (activeBtn && activeContent) {
            activeBtn.classList.add('active');
            activeBtn.setAttribute('aria-selected', 'true');
            activeContent.classList.add('active');
        }
    }

    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            switchTab(this.getAttribute('data-tab'));
        });
    });

    // Handle hash on load
    if (window.location.hash) {
        const hash = window.location.hash.substring(1);
        if(['overview', 'members', 'content', 'districts'].includes(hash)) {
            switchTab(hash);
        }
    }
});
</script>
