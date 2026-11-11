<link rel="stylesheet" href="/plugin/lightstats/template/admin.css?v=20261103_mobile_v3">
<style id="ls-mobile-shield">
/* Bouclier prioritaire immédiat anti-écrasement 299Ko Admin */
.lightstats-dashboard button,
.lightstats-dashboard .button,
.lightstats-dashboard input[type="button"],
.lightstats-dashboard input[type="submit"] {
    clear: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: auto !important;
    margin: 0 !important;
    text-align: center !important;
    box-sizing: border-box !important;
}
.lightstats-dashboard .ls-tabs-nav {
    display: flex !important;
    flex-wrap: nowrap !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
    width: 100% !important;
    gap: 0.5rem !important;
    padding-bottom: 6px !important;
}
.lightstats-dashboard .ls-tab-button {
    display: inline-flex !important;
    flex: 0 0 auto !important;
    width: auto !important;
    white-space: nowrap !important;
    background: #ffffff !important;
    color: var(--ls-slate-600) !important;
    border: 1px solid var(--ls-slate-200) !important;
    border-radius: 10px !important;
    padding: 0.65rem 1rem !important;
}
.lightstats-dashboard .ls-tab-button.active {
    background: var(--ls-primary, #6366f1) !important;
    color: #ffffff !important;
    border-color: var(--ls-primary, #6366f1) !important;
}
.lightstats-dashboard .ls-preset-btn {
    display: inline-flex !important;
    width: auto !important;
    background: var(--ls-slate-50, #f8fafc) !important;
    border: 1px solid var(--ls-slate-200, #e2e8f0) !important;
    color: var(--ls-slate-600, #475569) !important;
    border-radius: 8px !important;
    padding: 0.45rem 0.85rem !important;
}
.lightstats-dashboard .ls-preset-btn.active {
    background: var(--ls-primary, #6366f1) !important;
    color: #ffffff !important;
    border-color: var(--ls-primary, #6366f1) !important;
}
@media (max-width: 640px) {
    .lightstats-dashboard .ls-filter-bar {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    .lightstats-dashboard .ls-kpi-grid {
        grid-template-columns: 1fr !important;
    }
    .lightstats-dashboard .ls-submit-btn {
        width: 100% !important;
    }
}
</style>

<div class="lightstats-dashboard">

    <!-- ── EN-TÊTE PRINCIPAL ── -->
    <header class="ls-header">
        <div class="ls-header-brand">
            <div class="ls-header-icon">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="ls-header-titles">
                <h1>LightStats</h1>
                <p>Mesure d'audience locale hébergée sur votre serveur</p>
            </div>
        </div>
        <div class="ls-header-badges">
            <button type="button" id="lsInfoModalBtn" class="ls-preset-btn" style="cursor: pointer;">
                <i class="fa-solid fa-circle-info" style="color: var(--ls-primary);"></i> Fonctionnement, Fiabilité &amp; RGPD
            </button>
        </div>
    </header>

    <!-- ── BARRE DE FILTRAGE DES DATES ── -->
    <section class="ls-filter-bar">
        <div class="ls-presets-group">
            <span style="font-size: 0.8rem; font-weight: 700; color: var(--ls-slate-400); margin-right: 0.25rem;">
                <i class="fa-solid fa-bolt"></i> Périodes :
            </span>
            <button type="button" class="ls-preset-btn {% IF activePreset == 'today' %}active{% ENDIF %}" onclick="applyPreset('today')">Aujourd'hui</button>
            <button type="button" class="ls-preset-btn {% IF activePreset == 'yesterday' %}active{% ENDIF %}" onclick="applyPreset('yesterday')">Hier</button>
            <button type="button" class="ls-preset-btn {% IF activePreset == '7days' %}active{% ENDIF %}" onclick="applyPreset('7days')">7 derniers jours</button>
            <button type="button" class="ls-preset-btn {% IF activePreset == '30days' %}active{% ENDIF %}" onclick="applyPreset('30days')">30 derniers jours</button>
            <button type="button" class="ls-preset-btn {% IF activePreset == 'month' %}active{% ENDIF %}" onclick="applyPreset('month')">Ce mois-ci</button>
        </div>

        <form method="get" action="{{linkToHome}}" id="lightstatsFilterForm" class="ls-custom-dates-form">
            <input type="hidden" name="tab" id="activeTabInput" value="{{activeTab}}">
            <div class="ls-date-field">
                <span>Du</span>
                <input type="date" name="dateStart" id="dateStart" value="{{browserDateStart}}" required>
            </div>
            <div class="ls-date-field">
                <span>Au</span>
                <input type="date" name="dateEnd" id="dateEnd" value="{{browserDateEnd}}" required>
            </div>
            <button type="submit" class="ls-submit-btn">
                <i class="fa-solid fa-filter"></i> Actualiser
            </button>
        </form>
    </section>

    <!-- ── NAVIGATION PAR ONGLETS ── -->
    <nav class="ls-tabs-nav" id="lightstatsTabs">
        <button type="button" class="ls-tab-button {% IF activeTab == 'public' %}active{% ENDIF %}" data-tab="public">
            <i class="fa-solid fa-users"></i> 1. Audience & Visiteurs
        </button>
        <button type="button" class="ls-tab-button {% IF activeTab == 'journeys' %}active{% ENDIF %}" data-tab="journeys">
            <i class="fa-solid fa-route"></i> 2. Parcours Visiteurs
        </button>
        <button type="button" class="ls-tab-button {% IF activeTab == 'technical' %}active{% ENDIF %}" data-tab="technical">
            <i class="fa-solid fa-shield-halved"></i> 3. Crawlers & Sécurité
        </button>
        <button type="button" class="ls-tab-button {% IF activeTab == 'raw' %}active{% ENDIF %}" data-tab="raw">
            <i class="fa-solid fa-list-ul"></i> 4. Journaux Détaillés
            <span class="ls-tab-count">{{totalItems}}</span>
        </button>
    </nav>


    <!-- ===================================================================== -->
    <!-- ONGLET 1 : AUDIENCE & VISITEURS (VUE D'ENSEMBLE)                      -->
    <!-- ===================================================================== -->
    <div id="tab-public" class="tab-pane" style="{% IF activeTab != 'public' %}display: none;{% ENDIF %}">

        <!-- Grille des 4 KPIs -->
        <section class="ls-kpi-grid">
            <!-- KPI 1 : Visiteurs réels & Sessions observées -->
            <article class="ls-kpi-card indigo">
                <div class="ls-kpi-header">
                    <span class="ls-kpi-label">Visiteurs observés</span>
                    <span class="ls-kpi-badge indigo"><i class="fa-solid fa-users" aria-hidden="true"></i> Audience</span>
                </div>
                <div class="ls-kpi-value">{{sovereignUniqueVisitors}}</div>
                <div class="ls-kpi-subtext">{% IF certifiedSessions %}dont <strong>{{certifiedSessions}}</strong> certifiés JS ({{totalDbSessions}} sessions observées){% ELSE %}{{totalDbSessions}} sessions observées sur la période{% ENDIF %}</div>
            </article>

            <!-- KPI 2 : Pages certifiées réconciliées -->
            <article class="ls-kpi-card emerald">
                <div class="ls-kpi-header">
                    <span class="ls-kpi-label">Pages certifiées</span>
                    <span class="ls-kpi-badge emerald"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Réconciliées</span>
                </div>
                <div class="ls-kpi-value">{{certifiedPageviews}}</div>
                <div class="ls-kpi-subtext">Pages confirmées par le navigateur client</div>
            </article>

            <!-- KPI 3 : Taux d'engagement qualifié -->
            <article class="ls-kpi-card emerald">
                <div class="ls-kpi-header">
                    <span class="ls-kpi-label">Taux d'engagement</span>
                    <span class="ls-kpi-badge emerald"><i class="fa-solid fa-fire" aria-hidden="true"></i> Qualifié</span>
                </div>
                <div class="ls-kpi-value">{{engagementRatePct}} %</div>
                <div class="ls-kpi-subtext">Multi-pages ou action utile sur sessions certifiées</div>
            </article>

            <!-- KPI 4 : Durée mesurée -->
            <article class="ls-kpi-card amber">
                <div class="ls-kpi-header">
                    <span class="ls-kpi-label">Durée mesurée</span>
                    <span class="ls-kpi-badge amber"><i class="fa-solid fa-stopwatch" aria-hidden="true"></i> Réelle</span>
                </div>
                <div class="ls-kpi-value">{% IF consolidated.duration.avg_measured_formatted %}{{consolidated.duration.avg_measured_formatted}}{% ELSE %}—{% ENDIF %}</div>
                <div class="ls-kpi-subtext">Sessions mesurables uniquement (≥ 2 actions)</div>
            </article>
        </section>

        <!-- Grand Graphique d'Évolution Journalière (Pages HTTP Admissibles) -->
        <section class="ls-card">
            <div class="ls-card-header">
                <div>
                    <h3 class="ls-card-title">
                        <i class="fa-solid fa-chart-line" style="color: var(--ls-primary);"></i>
                        {% IF isSingleDay %}Évolution heure par heure ({{inDateStart}}){% ELSE %}Rythme quotidien de fréquentation ({{browserDateStart}} au {{browserDateEnd}}){% ENDIF %}
                    </h3>
                    <p style="margin: 0.2rem 0 0; font-size: 0.8rem; color: var(--ls-slate-400);">Fréquentation HTTP admissible sur la période sélectionnée</p>
                </div>
                <span class="ls-badge-pill">
                    <i class="fa-solid fa-circle" style="color: var(--ls-primary); font-size: 0.6rem;"></i> Visiteurs &nbsp;&bull;&nbsp;
                    <i class="fa-solid fa-circle" style="color: var(--ls-emerald); font-size: 0.6rem;"></i> Pages HTTP
                </span>
            </div>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="chartDaily"></canvas>
            </div>
        </section>

        <!-- Répartition Qualifiée des Sessions & Taux de Certification Active View -->
        <div class="ls-grid-2" style="margin-bottom: 1.5rem;">
            <!-- Qualification des Sessions -->
            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-solid fa-chart-pie" style="color: var(--ls-emerald);"></i> Qualification des sessions</h3>
                    <span class="ls-badge-pill">{{totalDbSessions}} sessions</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.875rem; padding: 0.5rem 0;">
                    <!-- Multi-pages -->
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem;">
                            <span><i class="fa-solid fa-layer-group" style="color: var(--ls-emerald);"></i> Multi-pages (≥ 2 pages certifiées)</span>
                            <span style="color: var(--ls-emerald); font-weight: 700;">{{sessionBreakdown.multi_page.count}} ({{sessionBreakdown.multi_page.pct}}%)</span>
                        </div>
                        <div class="ls-progress-track">
                            <div class="ls-progress-fill" style="background: var(--ls-emerald); width: {{sessionBreakdown.multi_page.pct}}%;"></div>
                        </div>
                    </div>

                    <!-- Monopage engagée -->
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem;">
                            <span><i class="fa-solid fa-hand-pointer" style="color: var(--ls-primary);"></i> Monopage engagée (1 page + interaction locale)</span>
                            <span style="color: var(--ls-primary); font-weight: 700;">{{sessionBreakdown.engaged_single_page.count}} ({{sessionBreakdown.engaged_single_page.pct}}%)</span>
                        </div>
                        <div class="ls-progress-track">
                            <div class="ls-progress-fill" style="background: var(--ls-primary); width: {{sessionBreakdown.engaged_single_page.pct}}%;"></div>
                        </div>
                    </div>

                    <!-- Rebond strict -->
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem;">
                            <span><i class="fa-solid fa-arrow-right-from-bracket" style="color: var(--ls-slate-400);"></i> Rebond strict (1 page sans action)</span>
                            <span style="color: var(--ls-slate-600); font-weight: 700;">{{sessionBreakdown.strict_bounce.count}} ({{sessionBreakdown.strict_bounce.pct}}%)</span>
                        </div>
                        <div class="ls-progress-track">
                            <div class="ls-progress-fill" style="background: var(--ls-slate-400); width: {{sessionBreakdown.strict_bounce.pct}}%;"></div>
                        </div>
                    </div>

                    <!-- Sessions HTTP non certifiées -->
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem;">
                            <span><i class="fa-solid fa-globe" style="color: var(--ls-slate-400);"></i> Sessions HTTP non certifiées</span>
                            <span style="color: var(--ls-slate-500); font-weight: 700;">{{sessionBreakdown.unverified.count}} ({{sessionBreakdown.unverified.pct}}%)</span>
                        </div>
                        <div class="ls-progress-track">
                            <div class="ls-progress-fill" style="background: var(--ls-slate-200); width: {{sessionBreakdown.unverified.pct}}%;"></div>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 0.75rem; padding: 0.65rem 0.85rem; background: var(--ls-slate-50); border-radius: 8px; font-size: 0.75rem; color: var(--ls-slate-500);">
                    <i class="fa-solid fa-circle-info"></i> <strong>Règle méthodologique :</strong> Les sessions HTTP non certifiées (ex. JavaScript désactivé, bloqueurs, interruption ou refus de consentement) sont isolées et exclues du dénominateur du taux d'engagement.
                </div>
            </section>

            <!-- Certification Active View & Réconciliation -->
            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-solid fa-shield-halved" style="color: var(--ls-primary);"></i> Fiabilité Active View (Niveau 2)</h3>
                    <span class="ls-badge-pill">Réconciliation</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.85rem; padding: 0.5rem 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--ls-slate-100); padding-bottom: 0.5rem;">
                        <span style="font-size: 0.85rem; color: var(--ls-slate-600);">Requêtes HTTP admissibles</span>
                        <strong style="font-size: 1rem; color: var(--ls-slate-900);">{{admissibleHttpPages}}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--ls-slate-100); padding-bottom: 0.5rem;">
                        <span style="font-size: 0.85rem; color: var(--ls-slate-600);">Pages certifiées réconciliées</span>
                        <strong style="font-size: 1rem; color: var(--ls-emerald);">{{certifiedPageviews}}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--ls-slate-100); padding-bottom: 0.5rem;">
                        <span style="font-size: 0.85rem; color: var(--ls-slate-600);">Taux de certification Active View</span>
                        <strong style="font-size: 1rem; color: var(--ls-primary);">{{consolidated.browser_activity.certification_rate_pct}} %</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.85rem; color: var(--ls-slate-600);">Certifications orphelines rejetées</span>
                        <strong style="font-size: 0.9rem; color: var(--ls-slate-500);">{{consolidated.browser_activity.orphan_certifications_count}}</strong>
                    </div>
                </div>
                <div style="margin-top: 0.75rem; padding: 0.65rem 0.85rem; background: var(--ls-slate-50); border-radius: 8px; font-size: 0.75rem; color: var(--ls-slate-500);">
                    <i class="fa-solid fa-check-double"></i> <strong>Contrôle d'intégrité :</strong> Seules les certifications réconciliées avec une requête HTTP admissible alimentent les métriques d'audience et de durée.
                </div>
            </section>
        </div>

        <!-- Grille 2 Colonnes : Top Pages & Sources -->
        <div class="ls-grid-2">
            <!-- Top Pages -->
            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-solid fa-file-lines"></i> Pages les plus consultées</h3>
                </div>
                <div class="ls-table-responsive">
                    <table class="ls-table">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th style="width: 35%;">Volume relatif</th>
                                <th style="text-align: right;">Vues</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% for page, nb in kpis.top_pages %}
                            <tr>
                                <td style="max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{page}}">
                                    <a href="{{util.urlBuild(page)}}" target="_blank" rel="noopener" style="color: var(--ls-slate-800); text-decoration: none; font-weight: 600;">
                                        {{page}}
                                    </a>
                                </td>
                                <td>
                                    <div class="ls-progress-track">
                                        <div class="ls-progress-fill" style="width: 60%;"></div>
                                    </div>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: var(--ls-primary);">{{nb}}</td>
                            </tr>
                            {% endfor %}
                            {% IF NOT kpis.top_pages %}
                            <tr><td colspan="3" style="text-align: center; color: var(--ls-slate-400); padding: 2rem;">Aucune consultation enregistrée sur cette période.</td></tr>
                            {% ENDIF %}
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Sources de trafic -->
            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-solid fa-compass"></i> Sources de Trafic & Référents</h3>
                </div>
                <div class="ls-table-responsive">
                    <table class="ls-table">
                        <thead>
                            <tr>
                                <th>Origine</th>
                                <th style="text-align: right;">Visites</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% for referer, data in kpis.top_referers %}
                            <tr>
                                <td>
                                    {% if data.is_direct %}
                                        <span class="ls-badge-pill"><i class="fa-solid fa-arrow-right"></i> Accès Direct / Favoris</span>
                                    {% else %}
                                        <a href="{{data.url}}" target="_blank" rel="nofollow noopener" style="color: var(--ls-slate-800); font-weight: 600; text-decoration: none;">
                                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.75rem; color: var(--ls-primary);"></i> {{data.label}}
                                        </a>
                                    {% endif %}
                                </td>
                                <td style="text-align: right; font-weight: 700; color: var(--ls-primary);">{{data.hits}}</td>
                            </tr>
                            {% endfor %}
                            {% IF NOT kpis.top_referers %}
                            <tr><td colspan="2" style="text-align: center; color: var(--ls-slate-400); padding: 2rem;">Aucun référent détecté sur cette période.</td></tr>
                            {% ENDIF %}
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Grille 3 Colonnes : Navigateurs, Systèmes et Heures -->
        <div class="ls-grid-3">
            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-brands fa-chrome"></i> Navigateurs</h3>
                </div>
                <div style="height: 220px; position: relative;">
                    <canvas id="chartBrowsers"></canvas>
                </div>
            </section>

            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-solid fa-laptop"></i> Systèmes (OS)</h3>
                </div>
                <div style="height: 220px; position: relative;">
                    <canvas id="chartPlatforms"></canvas>
                </div>
            </section>

            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-regular fa-clock"></i> Visites par Heure</h3>
                </div>
                <div style="height: 220px; position: relative;">
                    <canvas id="chartHours"></canvas>
                </div>
            </section>
        </div>

    </div>


    <!-- ===================================================================== -->
    <!-- ONGLET 2 : PARCOURS VISITEURS & FIDÉLISATION (AGRÉGÉ)                -->
    <!-- ===================================================================== -->
    <div id="tab-journeys" class="tab-pane" style="{% IF activeTab != 'journeys' %}display: none;{% ENDIF %}">

        <!-- 4 KPIs Répartition des Parcours -->
        <div class="ls-kpi-quad">
            <div class="ls-kpi-card">
                <div class="ls-kpi-icon icon-green"><i class="fa-solid fa-layer-group"></i></div>
                <div class="ls-kpi-body">
                    <div class="ls-kpi-label">Sessions Multi-pages</div>
                    <div class="ls-kpi-num">{{sessionBreakdown.multi_page.count}}</div>
                    <div class="ls-kpi-sub">{{sessionBreakdown.multi_page.pct}}% des sessions observées</div>
                </div>
            </div>

            <div class="ls-kpi-card">
                <div class="ls-kpi-icon icon-blue"><i class="fa-solid fa-hand-pointer"></i></div>
                <div class="ls-kpi-body">
                    <div class="ls-kpi-label">Monopages Engagées</div>
                    <div class="ls-kpi-num">{{sessionBreakdown.engaged_single_page.count}}</div>
                    <div class="ls-kpi-sub">{{sessionBreakdown.engaged_single_page.pct}}% (clic utile sur la page)</div>
                </div>
            </div>

            <div class="ls-kpi-card">
                <div class="ls-kpi-icon icon-purple"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
                <div class="ls-kpi-body">
                    <div class="ls-kpi-label">Rebonds Stricts</div>
                    <div class="ls-kpi-num">{{sessionBreakdown.strict_bounce.count}}</div>
                    <div class="ls-kpi-sub">{{sessionBreakdown.strict_bounce.pct}}% (1 page sans clic)</div>
                </div>
            </div>

            <div class="ls-kpi-card">
                <div class="ls-kpi-icon icon-amber"><i class="fa-solid fa-globe"></i></div>
                <div class="ls-kpi-body">
                    <div class="ls-kpi-label">HTTP Non Certifiées</div>
                    <div class="ls-kpi-num">{{sessionBreakdown.unverified.count}}</div>
                    <div class="ls-kpi-sub">{{sessionBreakdown.unverified.pct}}% (sans JS / isolées)</div>
                </div>
            </div>
        </div>

        <!-- Pages d'entrée et de sortie agrégées -->
        <div class="ls-grid-2" style="margin-top: 1rem;">
            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-solid fa-door-open"></i> Principales Pages d'Entrée (Landing Pages)</h3>
                    <span class="ls-badge-pill">Accueil & Découverte</span>
                </div>
                <div class="ls-table-responsive">
                    <table class="ls-table">
                        <thead>
                            <tr>
                                <th>Page d'arrivée</th>
                                <th style="text-align: right;">Sessions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% FOR lp IN topLandingPages %}
                            <tr>
                                <td><i class="fa-regular fa-file" style="color: var(--ls-slate-400); margin-right: 6px;"></i> <strong>{{lp.page}}</strong></td>
                                <td style="text-align: right; font-weight: 700; color: var(--ls-primary);">{{lp.hits}}</td>
                            </tr>
                            {% ENDFOR %}
                            {% IF NOT topLandingPages %}
                            <tr><td colspan="2" style="text-align: center; color: var(--ls-slate-400); padding: 2rem;">Aucune session certifiée sur la période.</td></tr>
                            {% ENDIF %}
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-solid fa-door-closed"></i> Principales Pages de Sortie (Exit Pages)</h3>
                    <span class="ls-badge-pill">Fin de visite</span>
                </div>
                <div class="ls-table-responsive">
                    <table class="ls-table">
                        <thead>
                            <tr>
                                <th>Dernière page vue</th>
                                <th style="text-align: right;">Sessions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% FOR ep IN topExitPages %}
                            <tr>
                                <td><i class="fa-regular fa-file-lines" style="color: var(--ls-slate-400); margin-right: 6px;"></i> <strong>{{ep.page}}</strong></td>
                                <td style="text-align: right; font-weight: 700; color: var(--ls-slate-600);">{{ep.hits}}</td>
                            </tr>
                            {% ENDFOR %}
                            {% IF NOT topExitPages %}
                            <tr><td colspan="2" style="text-align: center; color: var(--ls-slate-400); padding: 2rem;">Aucune session certifiée sur la période.</td></tr>
                            {% ENDIF %}
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Flux chronologique des parcours observés -->
        <section class="ls-card" style="margin-top: 1rem;">
            <div class="ls-card-header">
                <h3 class="ls-card-title"><i class="fa-solid fa-route"></i> Échantillon de Parcours Observés (Anonymisés)</h3>
                <span class="ls-badge-pill">Flux récent anonyme</span>
            </div>

            {% FOR j IN journeys %}
            <div style="background: var(--ls-slate-50); border: 1px solid var(--ls-slate-200); border-radius: 10px; padding: 1.25rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <strong style="color: var(--ls-slate-900);">Session #{{j.session_id}}</strong>
                        <span style="font-size: 0.8rem; color: var(--ls-slate-500); margin-left: 0.5rem;"><i class="fa-regular fa-clock"></i> {{j.started_at}}</span>
                    </div>
                    <div>
                        <span class="ls-badge-pill">{{j.event_count}} actions</span>
                        <span class="ls-badge-pill"><i class="fa-solid fa-stopwatch"></i> {{j.duration_formatted}}</span>
                    </div>
                </div>

                <div style="margin-top: 0.5rem;">
                    {% FOR ev IN j.events %}
                    <div class="journey-timeline-item">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="journey-step-badge">
                                {% IF ev.event_type == 'pageview' %}<i class="fa-regular fa-eye"></i> Page consultée{% ENDIF %}
                                {% IF ev.event_type == 'qr_scan' %}<i class="fa-solid fa-qrcode" style="color: var(--ls-emerald);"></i> Scan Vitrine{% ENDIF %}
                                {% IF ev.event_type == 'call_click' %}<i class="fa-solid fa-phone" style="color: var(--ls-emerald);"></i> Appel Téléphonique{% ENDIF %}
                                {% IF ev.event_type == 'directions_click' %}<i class="fa-solid fa-location-arrow" style="color: #0284c7;"></i> Itinéraire GPS{% ENDIF %}
                                {% IF ev.event_type == 'website_click' %}<i class="fa-solid fa-arrow-up-right-from-square"></i> Site officiel{% ENDIF %}
                            </span>
                            <span style="font-size: 0.75rem; color: var(--ls-slate-400);">{{ev.created_at}}</span>
                        </div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--ls-slate-800); margin-top: 0.25rem;">
                            {{ev.page}}
                        </div>
                    </div>
                    {% ENDFOR %}
                </div>
            </div>
            {% ENDFOR %}

            {% IF NOT journeys %}
            <div class="ls-empty-card">
                <div class="ls-empty-icon"><i class="fa-solid fa-route"></i></div>
                <h4 class="ls-empty-title">Aucun parcours enregistré sur cette période</h4>
                <p class="ls-empty-text">Les parcours se remplissent automatiquement dès que des visiteurs naviguent avec la balise active.</p>
            </div>
            {% ENDIF %}
        </section>

    </div>


    <!-- ===================================================================== -->
    <!-- ONGLET 4 : CRAWLERS, ROBOTS & SÉCURITÉ                               -->
    <!-- ===================================================================== -->
    <div id="tab-technical" class="tab-pane" style="{% IF activeTab != 'technical' %}display: none;{% ENDIF %}">

        <div class="ls-kpi-quad">
            <div class="ls-kpi-card">
                <div class="ls-kpi-icon icon-blue"><i class="fa-solid fa-server"></i></div>
                <div class="ls-kpi-body">
                    <div class="ls-kpi-label">Total Requêtes Serveur</div>
                    <div class="ls-kpi-num">{{httpKpis.total_http_requests}}</div>
                    <div class="ls-kpi-sub">Ensemble des requêtes HTTP reçues</div>
                </div>
            </div>

            <div class="ls-kpi-card">
                <div class="ls-kpi-icon icon-amber"><i class="fa-solid fa-robot"></i></div>
                <div class="ls-kpi-body">
                    <div class="ls-kpi-label">Robots & Crawlers</div>
                    <div class="ls-kpi-num">{{httpKpis.identified_bots}}</div>
                    <div class="ls-kpi-sub">Googlebot, Bingbot & moteurs officiels</div>
                </div>
            </div>

            <div class="ls-kpi-card">
                <div class="ls-kpi-icon icon-purple"><i class="fa-solid fa-shield-virus"></i></div>
                <div class="ls-kpi-body">
                    <div class="ls-kpi-label">Sondages & Scanners</div>
                    <div class="ls-kpi-num">{{httpKpis.scanners_suspicious}}</div>
                    <div class="ls-kpi-sub">Recherches d'URLs sensibles (.env, wp...)</div>
                </div>
            </div>

            <div class="ls-kpi-card">
                <div class="ls-kpi-icon icon-green"><i class="fa-solid fa-globe"></i></div>
                <div class="ls-kpi-body">
                    <div class="ls-kpi-label">Trafic Public Admissible</div>
                    <div class="ls-kpi-num">{{httpKpis.admissible_public_pages}}</div>
                    <div class="ls-kpi-sub">Pages web publiques (200 OK)</div>
                </div>
            </div>
        </div>

        <!-- Répartition des Réponses Serveur (Codes HTTP) -->
        <div class="ls-grid-2" style="margin-top: 1rem;">
            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-solid fa-chart-pie"></i> Distribution des Réponses HTTP</h3>
                    <span class="ls-badge-pill">Codes d'état serveur</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.85rem; padding: 0.5rem 0;">
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem;">
                            <span><i class="fa-solid fa-circle-check" style="color: var(--ls-emerald);"></i> 2xx - Succès (Pages servies)</span>
                            <span style="font-weight: 700;">{{httpKpis.status_2xx}}</span>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem;">
                            <span><i class="fa-solid fa-share" style="color: #0284c7;"></i> 3xx - Redirections HTTP</span>
                            <span style="font-weight: 700;">{{httpKpis.status_3xx}}</span>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem;">
                            <span><i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i> 4xx - Erreurs Client (404, 403...)</span>
                            <span style="font-weight: 700;">{{httpKpis.status_4xx}}</span>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem;">
                            <span><i class="fa-solid fa-circle-xmark" style="color: #ef4444;"></i> 5xx - Erreurs Serveur (500...)</span>
                            <span style="font-weight: 700;">{{httpKpis.status_5xx}}</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ls-card">
                <div class="ls-card-header">
                    <h3 class="ls-card-title"><i class="fa-solid fa-shield-halved"></i> Règle d'Orthogonalité Sécurité</h3>
                </div>
                <p style="color: var(--ls-slate-600); font-size: 0.875rem; line-height: 1.6; margin: 0 0 0.75rem 0;">
                    LightStats applique une séparation stricte entre les dimensions :
                </p>
                <ul style="color: var(--ls-slate-600); font-size: 0.85rem; line-height: 1.6; margin: 0; padding-left: 1.25rem;">
                    <li><strong>Moteurs officiels :</strong> Crawlers déclarés (Googlebot, Bingbot) authentifiés par reverse DNS.</li>
                    <li><strong>Scanners & Sondages :</strong> Requêtes ciblant des fichiers techniques ou vulnérabilités (.env, wp-login...) isolées au niveau serveur.</li>
                    <li><strong>Sessions HTTP non certifiées :</strong> Visiteurs humains réels avec JavaScript désactivé ou bloqueur, rigoureusement distincts des robots.</li>
                </ul>
            </section>
        </div>

    </div>


    <!-- ===================================================================== -->
    <!-- ONGLET 5 : JOURNAUX DÉTAILLÉS (LOGS BRUTS PAGINÉS)                   -->
    <!-- ===================================================================== -->
    <div id="tab-raw" class="tab-pane" style="{% IF activeTab != 'raw' %}display: none;{% ENDIF %}">

        <section class="ls-card">
            <div class="ls-card-header">
                <h3 class="ls-card-title"><i class="fa-solid fa-list-ul"></i> Journal des Requêtes Serveur</h3>
                <span class="ls-badge-pill">Affichage {{paginationStart}} à {{paginationEnd}} sur {{totalItems}} requêtes</span>
            </div>

            <div class="ls-table-responsive">
                <table class="ls-table">
                    <thead>
                        <tr>
                            <th>Date & Heure</th>
                            <th>Code</th>
                            <th>Chemin de la page</th>
                            <th>Source (Référent)</th>
                            <th>Client / Navigateur</th>
                            <th>Nature du Visiteur</th>
                            <th>IP (RGPD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for stat in logs %}
                        <tr>
                            <td style="white-space: nowrap; font-size: 0.8rem; color: var(--ls-slate-500);">{{stat.date}}</td>
                            <td>
                                {% if stat.status == 200 %}
                                    <span class="status-pill status-200">200</span>
                                {% elseif stat.status == 404 %}
                                    <span class="status-pill status-404">404</span>
                                {% elseif stat.status == 301 || stat.status == 302 %}
                                    <span class="status-pill status-30x">{{stat.status}}</span>
                                {% elseif stat.status >= 500 %}
                                    <span class="status-pill status-500">{{stat.status}}</span>
                                {% else %}
                                    <span class="status-pill status-other">{{stat.status}}</span>
                                {% endif %}
                            </td>
                            <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <a href="{{util.urlBuild(stat.page)}}" target="_blank" rel="noopener" style="color: var(--ls-slate-800); text-decoration: none; font-weight: 600;" title="{{stat.page}}">
                                    {{stat.page}}
                                </a>
                                {% if stat.query %}<small style="color: var(--ls-slate-400);">?{{stat.query}}</small>{% endif %}
                            </td>
                            <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {% if stat.referer %}
                                    <a href="{{stat.referer}}" target="_blank" rel="nofollow noopener" style="color: var(--ls-slate-600); text-decoration: none;" title="{{stat.referer}}">{{stat.referer}}</a>
                                {% else %}
                                    <span style="color: var(--ls-slate-400); font-style: italic;">Direct</span>
                                {% endif %}
                            </td>
                            <td>
                                <strong style="font-size: 0.82rem;">{{stat.browser}}</strong>
                                <div style="font-size: 0.72rem; color: var(--ls-slate-400);">{{stat.platform}}</div>
                            </td>
                            <td>
                                {% if stat.botCategory == 'verified_bot' %}
                                    <span class="bot-tag bot-verified"><i class="fa-solid fa-circle-check"></i> Bot vérifié</span>
                                {% elseif stat.botCategory == 'declared_bot' %}
                                    <span class="bot-tag bot-declared"><i class="fa-solid fa-robot"></i> Crawler</span>
                                {% elseif stat.botCategory == 'suspected_tool' %}
                                    <span class="bot-tag bot-tool"><i class="fa-solid fa-terminal"></i> Outil</span>
                                {% elseif stat.botCategory == 'undetermined' %}
                                    <span class="bot-tag bot-unknown"><i class="fa-solid fa-question"></i> Inconnu</span>
                                {% else %}
                                    <span class="bot-tag bot-human"><i class="fa-solid fa-user"></i> Humain</span>
                                {% endif %}
                            </td>
                            <td style="font-family: monospace; font-size: 0.8rem; color: var(--ls-slate-500);">{{stat.ip}}</td>
                        </tr>
                        {% endfor %}
                        {% IF NOT logs %}
                        <tr><td colspan="7" style="text-align: center; color: var(--ls-slate-400); padding: 2.5rem;">Aucun log disponible pour cette sélection.</td></tr>
                        {% ENDIF %}
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            {% IF totalPages > 1 %}
            <div class="ls-pagination">
                <span style="font-size: 0.85rem; color: var(--ls-slate-500);">
                    Page <strong>{{currentPage}}</strong> sur <strong>{{totalPages}}</strong>
                </span>
                <div class="ls-page-links">
                    {% IF prevPageUrl %}
                    <a href="{{prevPageUrl}}" class="ls-page-btn" title="Précédent"><i class="fa-solid fa-chevron-left"></i></a>
                    {% ENDIF %}

                    {% for item in paginationElements %}
                        {% if item.type == 'ellipsis' %}
                            <span style="padding: 0 0.25rem; color: var(--ls-slate-400);">&hellip;</span>
                        {% elseif item.isCurrent %}
                            <span class="ls-page-btn active">{{item.num}}</span>
                        {% else %}
                            <a href="{{item.url}}" class="ls-page-btn">{{item.num}}</a>
                        {% endif %}
                    {% endfor %}

                    {% IF nextPageUrl %}
                    <a href="{{nextPageUrl}}" class="ls-page-btn" title="Suivant"><i class="fa-solid fa-chevron-right"></i></a>
                    {% ENDIF %}
                </div>
            </div>
            {% ENDIF %}
        </section>

    </div>

</div>

<!-- ── MODAL EXPLICATIONS, FIABILITÉ & RGPD LIGHTSTATS ── -->
<div id="lsInfoModal" style="display: none; position: fixed; inset: 0; z-index: 1200; background: rgba(15, 23, 42, 0.58); padding: 20px; overflow-y: auto;">
    <div style="max-width: 780px; margin: 24px auto; background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 24px 48px rgba(15, 23, 42, 0.22); overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <h3 style="margin: 0; font-size: 1.05rem; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-info" style="color: var(--ls-primary, #1d4ed8);"></i>
                LightStats : fonctionnement, fiabilité &amp; conformité RGPD
            </h3>
            <button type="button" id="lsInfoModalClose" aria-label="Fermer" style="background: none; border: none; font-size: 1.15rem; color: #64748b; cursor: pointer; padding: 4px 8px;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div style="padding: 20px 22px; display: flex; flex-direction: column; gap: 16px; color: #334155; font-size: 0.9rem; line-height: 1.6;">
            <div>
                <h4 style="margin: 0 0 6px; color: #0f172a; font-size: 0.95rem;"><i class="fa-solid fa-gears" style="color: #1d4ed8;"></i> 1. À quoi sert LightStats et comment fonctionne-t-il ?</h4>
                <p style="margin: 0;">LightStats mesure la fréquentation et les parcours du site directement sur votre serveur (sans service externe), en combinant deux signaux :</p>
                <ul style="margin: 6px 0 0 18px;">
                    <li><strong>Collecte serveur (PHP) :</strong> chaque requête HTTP publique enregistre la page vue (sans paramètres marketing <code>utm_*</code>, <code>fbclid</code>, <code>gclid</code>…), le référent nettoyé, la catégorie du client (navigateur humain, robot vérifié par DNS ou scanner suspect) et un journal quotidien sur 30 jours glissants.</li>
                    <li><strong>Certification navigateur (« Active View JS ») :</strong> sur les pages HTML, un script léger (<code>privacy-tracker.js</code>) envoie un signal de confirmation lié à la requête pour distinguer un vrai navigateur d'un robot ou préchargement.</li>
                </ul>
            </div>

            <div>
                <h4 style="margin: 0 0 6px; color: #0f172a; font-size: 0.95rem;"><i class="fa-solid fa-gauge-high" style="color: #059669;"></i> 2. Quel est son taux de fiabilité ?</h4>
                <p style="margin: 0;">La fiabilité est directement visible dans l'indicateur <strong>Taux de certification Active View</strong> (onglet Audience). En pratique :</p>
                <ul style="margin: 6px 0 0 18px;">
                    <li>Les faux positifs (bots, crawlers SEO, scanners <code>.env</code>/<code>wp-config</code>) sont largement écartés des statistiques humaines.</li>
                    <li>Les visiteurs bloquant JavaScript ou coupant la page avant la fin du chargement apparaissent en <em>sessions HTTP non certifiées</em> (écart habituel de ~5 à 20 % selon le public). Sans consentement cookie, un visiteur revenant d'un jour sur l'autre est compté comme un nouveau visiteur chaque jour.</li>
                </ul>
            </div>

            <div>
                <h4 style="margin: 0 0 6px; color: #0f172a; font-size: 0.95rem;"><i class="fa-solid fa-user-shield" style="color: #d97706;"></i> 3. Vérification RGPD / Vie privée</h4>
                <ul style="margin: 0 0 0 18px;">
                    <li><strong>Adresses IP minimisées :</strong> le dernier octet IPv4 est remplacé par <code>0</code> (ex. <code>192.168.1.0</code>) et les IPv6 sont tronquées avant stockage ; l'empreinte journalière (<code>sha256</code> salé par la date) change chaque nuit et ne permet pas de remonter à l'IP réelle.</li>
                    <li><strong>Conservation limitée :</strong> purge automatique des journaux et événements au bout de <strong>30 jours</strong> ; aucun partage vers des tiers publicitaires.</li>
                    <li><strong>Cookies utilisés :</strong> un cookie technique de session de 30 min (<code>vb_sid</code>, <code>HttpOnly</code>) est posé côté serveur pour regrouper les pages d'une même visite, et un cookie pseudonyme de 7 jours (<code>vb_vid</code>) n'est créé <strong>qu'après clic sur « Tout accepter »</strong> dans le bandeau de consentement (jamais en cas de refus).</li>
                </ul>
            </div>

            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                    <h4 style="margin: 0; color: #0f172a; font-size: 0.92rem;"><i class="fa-solid fa-file-contract" style="color: #1d4ed8;"></i> 4. Exemple à intégrer dans vos Mentions légales / Politique de confidentialité</h4>
                    <button type="button" id="lsCopyLegalBtn" style="padding: 5px 10px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #0f172a; font-size: 0.78rem; font-weight: 600; cursor: pointer;">
                        <i class="fa-regular fa-copy"></i> Copier le texte
                    </button>
                </div>
                <textarea id="lsLegalTemplate" readonly rows="8" style="width: 100%; font-size: 0.82rem; line-height: 1.5; color: #1e293b; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; resize: vertical;">Mesure d'audience (LightStats)
Ce site utilise LightStats, une solution de mesure d'audience hébergée directement sur notre serveur, sans recours à des régies publicitaires ni transfert de données vers des tiers.
- Données collectées : pages consultées (sans paramètres marketing), provenance HTTP, type de navigateur/système, identifiant technique de session (30 minutes) et adresse IP immédiatement anonymisée (masquage du dernier octet).
- Suivi récurrent : un identifiant visiteur pseudonyme (cookie « vb_vid », conservé 7 jours) n'est déposé qu'avec votre accord explicite via le bandeau de consentement. Vous pouvez refuser ou modifier votre choix à tout moment.
- Durée de conservation : les journaux techniques bruts sont automatiquement supprimés après 30 jours.</textarea>
            </div>
        </div>
    </div>
</div>

<!-- ── SCRIPTS CHART.JS & NAVIGATION DES ONGLETS ── -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Navigation sans rechargement entre onglets (avec mémorisation sessionStorage)
    const LS_TAB_KEY = 'adm_tab:' + window.location.pathname + ':lightstats';
    const tabBtns = document.querySelectorAll('.ls-tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');
    const activeTabInput = document.getElementById('activeTabInput');

    function activateLightStatsTab(target, persist) {
        const btn = document.querySelector(`.ls-tab-button[data-tab="${target}"]`);
        const pane = document.getElementById('tab-' + target);
        if (!btn || !pane) return;

        tabBtns.forEach(b => b.classList.remove('active'));
        tabPanes.forEach(p => p.style.display = 'none');
        btn.classList.add('active');
        pane.style.display = 'block';
        if (activeTabInput) activeTabInput.value = target;

        if (persist) {
            try { sessionStorage.setItem(LS_TAB_KEY, target); } catch (e) {}
            const url = new URL(window.location);
            url.searchParams.set('tab', target);
            window.history.replaceState({}, '', url);
        }
        window.dispatchEvent(new Event('resize'));
    }

    const urlTabParam = new URLSearchParams(window.location.search).get('tab');
    if (urlTabParam) {
        try { sessionStorage.setItem(LS_TAB_KEY, urlTabParam); } catch (e) {}
    } else {
        try {
            const savedLsTab = sessionStorage.getItem(LS_TAB_KEY);
            if (savedLsTab) activateLightStatsTab(savedLsTab, false);
        } catch (e) {}
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            activateLightStatsTab(this.getAttribute('data-tab'), true);
        });
    });

    // 2. Initialisation du Grand Graphique Quotidien
    const ctxDaily = document.getElementById('chartDaily');
    if (ctxDaily && typeof Chart !== 'undefined') {
        const isSingleDay = {% IF isSingleDay %}true{% ELSE %}false{% ENDIF %};
        let labels = [];
        let visitorsData = [];
        let pagesData = [];

        if (isSingleDay) {
            labels = [{{hourlyChartLabels}}];
            visitorsData = [{{hourlyChartVisitors}}];
            pagesData = [{{hourlyChartPages}}];
        } else {
            labels = [{{chartDays}}];
            visitorsData = [{{chartVisitors}}];
            pagesData = [{{chartPages}}];
        }

        if (labels.length > 0) {
            new Chart(ctxDaily, {
                type: isSingleDay ? 'bar' : 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Visiteurs Uniques',
                            data: visitorsData,
                            borderColor: '#6366f1',
                            backgroundColor: isSingleDay ? 'rgba(99, 102, 241, 0.75)' : 'rgba(99, 102, 241, 0.08)',
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2.5,
                            borderRadius: isSingleDay ? 4 : 0,
                            pointRadius: isSingleDay ? 0 : 3.5,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Pages Vues',
                            data: pagesData,
                            borderColor: '#10b981',
                            backgroundColor: isSingleDay ? 'rgba(16, 185, 129, 0.65)' : 'rgba(16, 185, 129, 0.05)',
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2.5,
                            borderRadius: isSingleDay ? 4 : 0,
                            pointRadius: isSingleDay ? 0 : 3.5,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { weight: 600 } } }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }

    // 3. Camembert Navigateurs
    const ctxBrowsers = document.getElementById('chartBrowsers');
    if (ctxBrowsers && typeof Chart !== 'undefined') {
        const bLabels = [{{kpis.browsers_labels_js}}];
        const bData = [{{kpis.browsers_data_js}}];
        if (bLabels.length > 0 && bData.length > 0) {
            new Chart(ctxBrowsers, {
                type: 'doughnut',
                data: {
                    labels: bLabels,
                    datasets: [{
                        data: bData,
                        backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#ec4899']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
                }
            });
        }
    }

    // 4. Camembert Systèmes (OS)
    const ctxPlatforms = document.getElementById('chartPlatforms');
    if (ctxPlatforms && typeof Chart !== 'undefined') {
        const pLabels = [{{kpis.platforms_labels_js}}];
        const pData = [{{kpis.platforms_data_js}}];
        if (pLabels.length > 0 && pData.length > 0) {
            new Chart(ctxPlatforms, {
                type: 'doughnut',
                data: {
                    labels: pLabels,
                    datasets: [{
                        data: pData,
                        backgroundColor: ['#06b6d4', '#6366f1', '#10b981', '#f59e0b', '#64748b']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
                }
            });
        }
    }

    // 5. Barres Heures
    const ctxHours = document.getElementById('chartHours');
    if (ctxHours && typeof Chart !== 'undefined') {
        const hLabels = [{{kpis.hours_labels_js}}];
        const hData = [{{kpis.hours_data_js}}];
        if (hLabels.length > 0 && hData.length > 0) {
            new Chart(ctxHours, {
                type: 'bar',
                data: {
                    labels: hLabels,
                    datasets: [{
                        data: hData,
                        backgroundColor: 'rgba(99, 102, 241, 0.75)',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }

    // 6. Modal Informations / RGPD LightStats
    const lsModal = document.getElementById('lsInfoModal');
    const lsModalBtn = document.getElementById('lsInfoModalBtn');
    const lsModalClose = document.getElementById('lsInfoModalClose');
    const lsCopyBtn = document.getElementById('lsCopyLegalBtn');
    const lsLegalArea = document.getElementById('lsLegalTemplate');

    if (lsModal && lsModalBtn) {
        lsModalBtn.addEventListener('click', () => { lsModal.style.display = 'block'; });
        if (lsModalClose) {
            lsModalClose.addEventListener('click', () => { lsModal.style.display = 'none'; });
        }
        lsModal.addEventListener('click', (e) => {
            if (e.target === lsModal) lsModal.style.display = 'none';
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && lsModal.style.display === 'block') lsModal.style.display = 'none';
        });
    }
    if (lsCopyBtn && lsLegalArea) {
        lsCopyBtn.addEventListener('click', function() {
            const text = lsLegalArea.value;
            const orig = lsCopyBtn.innerHTML;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    lsCopyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copié !';
                    setTimeout(() => { lsCopyBtn.innerHTML = orig; }, 1800);
                });
            } else {
                lsLegalArea.select();
                document.execCommand('copy');
                lsCopyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copié !';
                setTimeout(() => { lsCopyBtn.innerHTML = orig; }, 1800);
            }
        });
    }
});

// Raccourcis de sélection de date
function applyPreset(preset) {
    const startInput = document.getElementById('dateStart');
    const endInput = document.getElementById('dateEnd');
    const form = document.getElementById('lightstatsFilterForm');
    const today = new Date();
    
    function formatDate(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    if (preset === 'today') {
        const str = formatDate(today);
        startInput.value = str;
        endInput.value = str;
    } else if (preset === 'yesterday') {
        const y = new Date(today);
        y.setDate(y.getDate() - 1);
        const str = formatDate(y);
        startInput.value = str;
        endInput.value = str;
    } else if (preset === '7days') {
        const s = new Date(today);
        s.setDate(s.getDate() - 6);
        startInput.value = formatDate(s);
        endInput.value = formatDate(today);
    } else if (preset === '30days') {
        const s = new Date(today);
        s.setDate(s.getDate() - 29);
        startInput.value = formatDate(s);
        endInput.value = formatDate(today);
    } else if (preset === 'month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        startInput.value = formatDate(firstDay);
        endInput.value = formatDate(today);
    }

    form.submit();
}
</script>
