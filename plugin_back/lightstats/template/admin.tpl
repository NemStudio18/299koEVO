<section class="module-card">
    <header><i class="fa-solid fa-chart-simple"></i> {{Lang.lightstats.name}}</header>
    
    <div class="lightstats-filters">
        <form method="post" action="{{linkToHome}}" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            {{SHOW.tokenField}}
            <div>
                <label for="dateStart">{{Lang.lightstats.begin-date}}</label>
                <input id="dateStart" name="dateStart" type="date" value="{{browserDateStart}}">
            </div>
            <div>
                <label for="dateEnd">{{Lang.lightstats.end-date}}</label>
                <input id="dateEnd" name="dateEnd" type="date" value="{{browserDateEnd}}">
            </div>
            <div>
                <button type="submit" class="button primary"><i class="fa-solid fa-filter"></i> {{Lang.lightstats.see-statistics}}</button>
            </div>
        </form>
    </div>
</section>

{% IF logsManager.hasLogs %}
<!-- Onglets de navigation -->
<section class="module-card">
    <div class="stats-tabs" style="display: flex; gap: 0.5rem; border-bottom: 2px solid #dee2e6; margin-bottom: 1.5rem;">
        <button class="tab-button active" data-tab="public" style="padding: 0.75rem 1.5rem; background: none; border: none; border-bottom: 3px solid #667eea; color: #667eea; font-weight: 600; cursor: pointer;">
            <i class="fa-solid fa-chart-line"></i> Stats publiques
        </button>
        <button class="tab-button" data-tab="technical" style="padding: 0.75rem 1.5rem; background: none; border: none; color: #6c757d; cursor: pointer;">
            <i class="fa-solid fa-cog"></i> Stats techniques
        </button>
        <button class="tab-button" data-tab="raw" style="padding: 0.75rem 1.5rem; background: none; border: none; color: #6c757d; cursor: pointer;">
            <i class="fa-solid fa-table"></i> Détails bruts
        </button>
    </div>
</section>

<!-- Stats publiques (par défaut) -->
<div id="tab-public" class="tab-content active">
    <section class="module-card">
        <header>
            <i class="fa-solid fa-chart-line"></i> Indicateurs clés (visiteurs humains uniquement)
            <small style="color: #6c757d; font-weight: normal; margin-left: 1rem;">
                Du {{inDateStart}} au {{inDateEnd}}
            </small>
        </header>
        <div class="kpi-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <div class="kpi-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 8px; position: relative;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-users"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold;">{{kpis.unique_visitors}}</div>
                <div class="kpi-label" style="opacity: 0.9;">Visiteurs uniques</div>
                <div class="kpi-tooltip" style="position: absolute; top: 0.5rem; right: 0.5rem; cursor: help;">
                    <i class="fa-solid fa-circle-info" style="opacity: 0.7;"></i>
                    <span class="tooltip-text" style="display: none; position: absolute; right: 0; top: 1.5rem; background: rgba(0,0,0,0.9); color: white; padding: 0.5rem; border-radius: 4px; white-space: nowrap; z-index: 1000; font-size: 0.85rem;">
                        Nombre d'IP uniques ayant visité le site (humains uniquement, excluant bots, admin et test)
                    </span>
                </div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 8px; position: relative;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-sync"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold;">{{kpis.total_sessions}}</div>
                <div class="kpi-label" style="opacity: 0.9;">Sessions</div>
                <div class="kpi-tooltip" style="position: absolute; top: 0.5rem; right: 0.5rem; cursor: help;">
                    <i class="fa-solid fa-circle-info" style="opacity: 0.7;"></i>
                    <span class="tooltip-text" style="display: none; position: absolute; right: 0; top: 1.5rem; background: rgba(0,0,0,0.9); color: white; padding: 0.5rem; border-radius: 4px; white-space: nowrap; z-index: 1000; font-size: 0.85rem;">
                        Nombre de sessions (même IP + navigateur, timeout 30 min)
                    </span>
                </div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 8px; position: relative;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-file"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold;">{{kpis.total_pages}}</div>
                <div class="kpi-label" style="opacity: 0.9;">Pages vues</div>
                <div class="kpi-tooltip" style="position: absolute; top: 0.5rem; right: 0.5rem; cursor: help;">
                    <i class="fa-solid fa-circle-info" style="opacity: 0.7;"></i>
                    <span class="tooltip-text" style="display: none; position: absolute; right: 0; top: 1.5rem; background: rgba(0,0,0,0.9); color: white; padding: 0.5rem; border-radius: 4px; white-space: nowrap; z-index: 1000; font-size: 0.85rem;">
                        Nombre total de pages vues par les visiteurs humains (excluant bots, admin et test)
                    </span>
                </div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 1.5rem; border-radius: 8px; position: relative;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-file-lines"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold;">{{kpis.pages_per_session}}</div>
                <div class="kpi-label" style="opacity: 0.9;">Pages / session</div>
                <div class="kpi-tooltip" style="position: absolute; top: 0.5rem; right: 0.5rem; cursor: help;">
                    <i class="fa-solid fa-circle-info" style="opacity: 0.7;"></i>
                    <span class="tooltip-text" style="display: none; position: absolute; right: 0; top: 1.5rem; background: rgba(0,0,0,0.9); color: white; padding: 0.5rem; border-radius: 4px; white-space: nowrap; z-index: 1000; font-size: 0.85rem;">
                        Nombre moyen de pages consultées par session
                    </span>
                </div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; padding: 1.5rem; border-radius: 8px; position: relative;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-clock"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold;">{{kpis.avg_session_duration_formatted}}</div>
                <div class="kpi-label" style="opacity: 0.8;">Durée moyenne</div>
                <div class="kpi-tooltip" style="position: absolute; top: 0.5rem; right: 0.5rem; cursor: help;">
                    <i class="fa-solid fa-circle-info" style="opacity: 0.7;"></i>
                    <span class="tooltip-text" style="display: none; position: absolute; right: 0; top: 1.5rem; background: rgba(0,0,0,0.9); color: white; padding: 0.5rem; border-radius: 4px; white-space: nowrap; z-index: 1000; font-size: 0.85rem;">
                        Durée moyenne d'une session (temps entre première et dernière page vue)
                    </span>
                </div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 1.5rem; border-radius: 8px; position: relative;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-arrow-trend-down"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold;">{{kpis.bounce_rate}}%</div>
                <div class="kpi-label" style="opacity: 0.9;">Taux de rebond</div>
                <div class="kpi-tooltip" style="position: absolute; top: 0.5rem; right: 0.5rem; cursor: help;">
                    <i class="fa-solid fa-circle-info" style="opacity: 0.7;"></i>
                    <span class="tooltip-text" style="display: none; position: absolute; right: 0; top: 1.5rem; background: rgba(0,0,0,0.9); color: white; padding: 0.5rem; border-radius: 4px; white-space: nowrap; z-index: 1000; font-size: 0.85rem;">
                        Pourcentage de sessions avec une seule page vue (visiteurs humains uniquement)
                    </span>
                </div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #333; padding: 1.5rem; border-radius: 8px; position: relative;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-user-plus"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold;">{{kpis.new_visitors}}</div>
                <div class="kpi-label" style="opacity: 0.8;">Nouveaux visiteurs</div>
                <div class="kpi-tooltip" style="position: absolute; top: 0.5rem; right: 0.5rem; cursor: help;">
                    <i class="fa-solid fa-circle-info" style="opacity: 0.7;"></i>
                    <span class="tooltip-text" style="display: none; position: absolute; right: 0; top: 1.5rem; background: rgba(0,0,0,0.9); color: white; padding: 0.5rem; border-radius: 4px; white-space: nowrap; z-index: 1000; font-size: 0.85rem;">
                        Nombre de visiteurs visitant pour la première fois (basé sur IP)
                    </span>
                </div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%); color: #333; padding: 1.5rem; border-radius: 8px; position: relative;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-user-check"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold;">{{kpis.recurrent_visitors}}</div>
                <div class="kpi-label" style="opacity: 0.8;">Visiteurs récurrents</div>
                <div class="kpi-tooltip" style="position: absolute; top: 0.5rem; right: 0.5rem; cursor: help;">
                    <i class="fa-solid fa-circle-info" style="opacity: 0.7;"></i>
                    <span class="tooltip-text" style="display: none; position: absolute; right: 0; top: 1.5rem; background: rgba(0,0,0,0.9); color: white; padding: 0.5rem; border-radius: 4px; white-space: nowrap; z-index: 1000; font-size: 0.85rem;">
                        Nombre de visiteurs ayant visité plusieurs fois (basé sur IP)
                    </span>
                </div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; padding: 1.5rem; border-radius: 8px; position: relative;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold;">{{kpis.avg_pages_per_day}}</div>
                <div class="kpi-label" style="opacity: 0.8;">Pages vues / jour</div>
                <div class="kpi-tooltip" style="position: absolute; top: 0.5rem; right: 0.5rem; cursor: help;">
                    <i class="fa-solid fa-circle-info" style="opacity: 0.7;"></i>
                    <span class="tooltip-text" style="display: none; position: absolute; right: 0; top: 1.5rem; background: rgba(0,0,0,0.9); color: white; padding: 0.5rem; border-radius: 4px; white-space: nowrap; z-index: 1000; font-size: 0.85rem;">
                        Nombre moyen de pages vues par jour (visiteurs humains uniquement)
                    </span>
                </div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; padding: 1.5rem; border-radius: 8px; position: relative;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-robot"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold;">{{kpis.bot_visits}}</div>
                <div class="kpi-label" style="opacity: 0.8;">Visites bots (exclues)</div>
                <div class="kpi-tooltip" style="position: absolute; top: 0.5rem; right: 0.5rem; cursor: help;">
                    <i class="fa-solid fa-circle-info" style="opacity: 0.7;"></i>
                    <span class="tooltip-text" style="display: none; position: absolute; right: 0; top: 1.5rem; background: rgba(0,0,0,0.9); color: white; padding: 0.5rem; border-radius: 4px; white-space: nowrap; z-index: 1000; font-size: 0.85rem;">
                        Nombre de visites détectées comme bots (Googlebot, Facebook, etc.) - exclues des stats publiques
                    </span>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Stats techniques -->
<div id="tab-technical" class="tab-content" style="display: none;">
    <section class="module-card">
        <header><i class="fa-solid fa-cog"></i> Statistiques techniques</header>
        <div class="kpi-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <div class="kpi-card" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 1.5rem; border-radius: 8px;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem; color: #667eea;"><i class="fa-solid fa-robot"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold; color: #333;">{{kpis.bot_visits}}</div>
                <div class="kpi-label" style="color: #6c757d;">Visites bots</div>
            </div>
            <div class="kpi-card" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 1.5rem; border-radius: 8px;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem; color: #dc3545;"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold; color: #333;">{{kpis.admin_visits}}</div>
                <div class="kpi-label" style="color: #6c757d;">Visites admin</div>
            </div>
            <div class="kpi-card" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 1.5rem; border-radius: 8px;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem; color: #ffc107;"><i class="fa-solid fa-flask"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold; color: #333;">{{kpis.test_visits}}</div>
                <div class="kpi-label" style="color: #6c757d;">Visites test</div>
            </div>
            <div class="kpi-card" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 1.5rem; border-radius: 8px;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem; color: #28a745;"><i class="fa-solid fa-user"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold; color: #333;">{{kpis.human_visits}}</div>
                <div class="kpi-label" style="color: #6c757d;">Visites humaines</div>
            </div>
            <div class="kpi-card" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 1.5rem; border-radius: 8px;">
                <div class="kpi-icon" style="font-size: 2rem; margin-bottom: 0.5rem; color: #17a2b8;"><i class="fa-solid fa-list"></i></div>
                <div class="kpi-value" style="font-size: 2rem; font-weight: bold; color: #333;">{{kpis.total_visits}}</div>
                <div class="kpi-label" style="color: #6c757d;">Total visites</div>
            </div>
        </div>
    </section>
</div>

    <!-- Graphiques (dans l'onglet public) -->
    <section class="module-card">
        <header><i class="fa-solid fa-chart-bar"></i> Évolution quotidienne</header>
        <div style="position: relative; height: 400px; margin: 1rem 0;">
            <canvas id="chartDaily"></canvas>
        </div>
    </section>

    <section class="module-card">
        <header><i class="fa-solid fa-chart-pie"></i> Répartitions</header>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1rem;">
            <div>
                <h4>Navigateurs</h4>
                <canvas id="chartBrowsers" style="max-height: 300px;"></canvas>
            </div>
            <div>
                <h4>Systèmes d'exploitation</h4>
                <canvas id="chartPlatforms" style="max-height: 300px;"></canvas>
            </div>
            <div>
                <h4>Distribution horaire</h4>
                <canvas id="chartHours" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </section>

    <!-- Pages populaires et Référents (dans l'onglet public) -->
    <section class="module-card">
        <header><i class="fa-solid fa-list"></i> Détails</header>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-top: 1rem;">
            <div>
                <h4><i class="fa-solid fa-file"></i> Pages les plus visitées</h4>
                <table class="lightstats-top-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 0.5rem; text-align: left;">Page</th>
                            <th style="padding: 0.5rem; text-align: right;">Vues</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for page, count in kpis.top_pages %}
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 0.5rem;"><a href="{{util.urlBuild(page)}}" target="_blank">{{page}}</a></td>
                            <td style="padding: 0.5rem; text-align: right; font-weight: bold;">{{count}}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            <div>
                <h4><i class="fa-solid fa-link"></i> Sources de trafic</h4>
                <table class="lightstats-top-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 0.5rem; text-align: left;">Source</th>
                            <th style="padding: 0.5rem; text-align: right;">Visites</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for referer, data in kpis.top_referers %}
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 0.5rem;">
                                {% if data.is_direct %}
                                <i class="fa-solid fa-arrow-right"></i> Direct
                                {% else %}
                                <a href="{{data.url}}" target="_blank" rel="nofollow">{{data.label}}</a>
                                {% endif %}
                            </td>
                            <td style="padding: 0.5rem; text-align: right; font-weight: bold;">{{data.count}}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- Détails bruts -->
<div id="tab-raw" class="tab-content" style="display: none;">

<!-- Tableau des logs avec pagination -->
<section class="module-card">
    <header><i class="fa-solid fa-table"></i> Détails des visites ({{totalItems}} au total)</header>
    
    {% IF totalPages > 1 %}
    <div class="pagination-info" style="margin: 1rem 0; padding: 0.75rem; background: #f5f5f5; border-radius: 4px;">
        Page {{currentPage}} sur {{totalPages}} | 
        Affichage de {{paginationStart}} à {{paginationEnd}} sur {{totalItems}} visites
    </div>
    {% ENDIF %}
    
    <div style="overflow-x: auto;">
        <table id="lightStatsTable" style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
            <thead>
                <tr style="background: #f5f5f5;">
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">{{Lang.date}}</th>
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">{{Lang.lightstats.page}}</th>
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">{{Lang.lightstats.referer}}</th>
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">{{Lang.lightstats.browser}}</th>
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">{{Lang.lightstats.os}}</th>
                    <th style="padding: 0.75rem; text-align: center; border-bottom: 2px solid #ddd;">{{Lang.lightstats.robot}}</th>
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">{{Lang.lightstats.ip}}</th>
                </tr>
            </thead>
            <tbody>
            {% for stat in logs %}
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 0.75rem;">{{stat.date}}</td>
                    <td style="padding: 0.75rem;"><a href="{{util.urlBuild(stat.page)}}" target="_blank">{{stat.page}}</a></td>
                    <td style="padding: 0.75rem;">{% if stat.referer %}<a href="{{stat.referer}}" target="_blank" rel="nofollow">{{stat.referer}}</a>{% else %}<span style="color: #999;">Direct</span>{% endif %}</td>
                    <td style="padding: 0.75rem;">{{stat.browser}}{% if stat.browserVersion %} {{stat.browserVersion}}{% endif %}</td>
                    <td style="padding: 0.75rem;">{{stat.platform}}</td>
                    <td style="padding: 0.75rem; text-align: center;">{% if stat.isBot %}<span style="color: #f5576c;"><i class="fa-solid fa-robot"></i> Oui</span>{% else %}<span style="color: #30cfd0;"><i class="fa-solid fa-user"></i> Non</span>{% endif %}</td>
                    <td style="padding: 0.75rem; font-family: monospace; font-size: 0.9em;">{{stat.ip}}</td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    </div>
    
    {% IF totalPages > 1 %}
    <div class="pagination" style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
        {% IF prevPageUrl %}
        <a href="{{prevPageUrl}}" class="button" style="padding: 0.5rem 1rem;"><i class="fa-solid fa-chevron-left"></i> Précédent</a>
        {% ENDIF %}
        
        {% for page in paginationPages %}
            {% IF page == currentPage %}
            <span class="button" style="background: #667eea; color: white; padding: 0.5rem 1rem;">{{page}}</span>
            {% ELSE %}
            <a href="{{paginationUrls[page]}}" class="button" style="padding: 0.5rem 1rem;">{{page}}</a>
            {% ENDIF %}
        {% endfor %}
        
        {% IF nextPageUrl %}
        <a href="{{nextPageUrl}}" class="button" style="padding: 0.5rem 1rem;">Suivant <i class="fa-solid fa-chevron-right"></i></a>
        {% ENDIF %}
    </div>
    {% ENDIF %}
    </section>
</div>

<script>
// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Désactiver tous les onglets
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.borderBottom = 'none';
                btn.style.color = '#6c757d';
            });
            
            tabContents.forEach(content => {
                content.classList.remove('active');
                content.style.display = 'none';
            });
            
            // Activer l'onglet sélectionné
            this.classList.add('active');
            this.style.borderBottom = '3px solid #667eea';
            this.style.color = '#667eea';
            
            document.getElementById('tab-' + targetTab).classList.add('active');
            document.getElementById('tab-' + targetTab).style.display = 'block';
        });
    });
    
    // Tooltips
    document.querySelectorAll('.kpi-tooltip').forEach(tooltip => {
        const icon = tooltip.querySelector('i');
        const text = tooltip.querySelector('.tooltip-text');
        
        icon.addEventListener('mouseenter', function() {
            text.style.display = 'block';
        });
        
        icon.addEventListener('mouseleave', function() {
            text.style.display = 'none';
        });
    });
});
</script>

<script>
// Graphique évolution quotidienne (ligne)
const ctxDaily = document.getElementById('chartDaily');
if (ctxDaily && typeof Chart !== 'undefined') {
    const chartDaysData = [{{chartDays}}];
    const chartVisitorsData = [{{chartVisitors}}];
    const chartPagesData = [{{chartPages}}];
    
    if (chartDaysData.length > 0) {
        new Chart(ctxDaily, {
            type: 'line',
            data: {
                labels: chartDaysData,
                datasets: [{
                    label: 'Visiteurs uniques',
                    data: chartVisitorsData,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Pages vues',
                    data: chartPagesData,
                    borderColor: 'rgb(245, 87, 108)',
                    backgroundColor: 'rgba(245, 87, 108, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Évolution quotidienne'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// Graphique navigateurs (camembert)
const ctxBrowsers = document.getElementById('chartBrowsers');
if (ctxBrowsers && typeof Chart !== 'undefined') {
    const browsersLabels = [{{kpis.browsers_labels_js}}];
    const browsersData = [{{kpis.browsers_data_js}}];
    
    if (browsersLabels.length > 0 && browsersData.length > 0) {
        new Chart(ctxBrowsers, {
            type: 'doughnut',
            data: {
                labels: browsersLabels,
                datasets: [{
                    data: browsersData,
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(245, 87, 108, 0.8)',
                        'rgba(79, 172, 254, 0.8)',
                        'rgba(250, 112, 154, 0.8)',
                        'rgba(48, 207, 208, 0.8)',
                        'rgba(168, 237, 234, 0.8)',
                        'rgba(255, 206, 84, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(201, 203, 207, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
}

// Graphique OS (camembert)
const ctxPlatforms = document.getElementById('chartPlatforms');
if (ctxPlatforms && typeof Chart !== 'undefined') {
    const platformsLabels = [{{kpis.platforms_labels_js}}];
    const platformsData = [{{kpis.platforms_data_js}}];
    
    if (platformsLabels.length > 0 && platformsData.length > 0) {
        new Chart(ctxPlatforms, {
            type: 'doughnut',
            data: {
                labels: platformsLabels,
                datasets: [{
                    data: platformsData,
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(245, 87, 108, 0.8)',
                        'rgba(79, 172, 254, 0.8)',
                        'rgba(250, 112, 154, 0.8)',
                        'rgba(48, 207, 208, 0.8)',
                        'rgba(168, 237, 234, 0.8)',
                        'rgba(255, 206, 84, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(201, 203, 207, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
}

// Graphique distribution horaire (barres)
const ctxHours = document.getElementById('chartHours');
if (ctxHours && typeof Chart !== 'undefined') {
    const hoursLabels = [{{kpis.hours_labels_js}}];
    const hoursData = [{{kpis.hours_data_js}}];
    
    if (hoursLabels.length > 0 && hoursData.length > 0) {
        new Chart(ctxHours, {
            type: 'bar',
            data: {
                labels: hoursLabels,
                datasets: [{
                    label: 'Visites par heure',
                    data: hoursData,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}
</script>

{% ELSE %}
<section class="module-card">
    <p style="text-align: center; padding: 2rem; color: #999;">
        <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
        {{Lang.lightstats.no-data}}
    </p>
</section>
{% ENDIF %}

<style>
.lightstats-filters {
    margin-bottom: 1.5rem;
}
.kpi-card {
    transition: transform 0.2s;
}
.kpi-card:hover {
    transform: translateY(-5px);
}
.lightstats-top-table {
    margin-top: 0.5rem;
}
.lightstats-top-table tbody tr:hover {
    background: #f9f9f9;
}
#lightStatsTable tbody tr:hover {
    background: #f9f9f9;
}
.pagination .button {
    text-decoration: none;
    min-width: 40px;
    text-align: center;
}
</style>
