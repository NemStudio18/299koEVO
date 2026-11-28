<section class="content">
    <div id="content_title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h2><i class="fa-solid fa-chart-line"></i> Monitoring & Télémétrie Souveraine</h2>
            <p style="margin: 4px 0 0 0; color: var(--cb-text-muted, #64748b); font-size: 0.95rem;">
                Tableau de bord technique natif : temps de réponse, mémoire vive PHP, profilage des Hooks et volume d'I/O.
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.88rem; cursor: pointer; color: var(--cb-text, #334155); margin: 0;">
                <input type="checkbox" id="autoRefreshToggle" checked style="cursor: pointer;">
                <span>Rafraîchissement direct (5s)</span>
            </label>
            <button type="button" id="btnManualRefresh" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-arrows-rotate"></i> Actualiser
            </button>
            <form method="post" action="{{ resetUrl }}" style="margin: 0;" onsubmit="return confirm('Confirmez-vous la réinitialisation complète des compteurs et de l\'historique de métriques ?');">
                {{ SHOW.tokenField }}
                <button type="submit" class="button outline" style="display: inline-flex; align-items: center; gap: 6px; color: #dc2626; border-color: #dc2626;">
                    <i class="fa-solid fa-trash-can"></i> Réinitialiser
                </button>
            </form>
        </div>
    </div>

    <!-- KPIs Principaux -->
    <div class="monitor-kpi-grid">
        <!-- Temps de réponse -->
        <div class="monitor-kpi-card">
            <div class="kpi-icon icon-time"><i class="fa-solid fa-stopwatch"></i></div>
            <div class="kpi-content">
                <span class="kpi-label">Latence Moyenne Core</span>
                <div class="kpi-value" id="kpiAvgDuration">
                    {{ metrics.summary.avg_duration_ms|default(0) }} <small>ms</small>
                </div>
                <div class="kpi-footer" id="kpiPeakDuration">
                    Pic max : <strong>{{ metrics.summary.max_duration_ms|default(0) }} ms</strong>
                </div>
            </div>
        </div>

        <!-- Mémoire vive PHP -->
        <div class="monitor-kpi-card">
            <div class="kpi-icon icon-ram"><i class="fa-solid fa-memory"></i></div>
            <div class="kpi-content">
                <span class="kpi-label">Pic Mémoire RAM</span>
                <div class="kpi-value" id="kpiPeakMemory">
                    {{ metrics.summary.max_memory_mb|default(0) }} <small>Mo</small>
                </div>
                <div class="kpi-footer" id="kpiAvgMemory">
                    Moyenne : <strong>{{ metrics.summary.avg_memory_mb|default(0) }} Mo</strong> (Limite : {{ metrics.server_live.memory_limit }})
                </div>
            </div>
        </div>

        <!-- Volume I/O JSON & SQL -->
        <div class="monitor-kpi-card">
            <div class="kpi-icon icon-io"><i class="fa-solid fa-database"></i></div>
            <div class="kpi-content">
                <span class="kpi-label">Opérations I/O Totales</span>
                <div class="kpi-value" id="kpiTotalIo">
                    {{ metrics.summary.total_json_reads + metrics.summary.total_json_writes + metrics.summary.total_sql_queries }}
                </div>
                <div class="kpi-footer" id="kpiIoDetails">
                    JSON : <strong>{{ metrics.summary.total_json_reads }}</strong> lus / <strong>{{ metrics.summary.total_json_writes }}</strong> écrits | SQL : <strong>{{ metrics.summary.total_sql_queries }}</strong> req.
                </div>
            </div>
        </div>

        <!-- Environnement Serveur -->
        <div class="monitor-kpi-card">
            <div class="kpi-icon icon-server"><i class="fa-solid fa-server"></i></div>
            <div class="kpi-content">
                <span class="kpi-label">Environnement PHP</span>
                <div class="kpi-value" style="font-size: 1.4rem;">
                    PHP {{ metrics.server_live.php_version|slice(0, 6) }}
                </div>
                <div class="kpi-footer">
                    OPcache : <strong style="color: {% if metrics.server_live.opcache_enabled %}#10b981{% else %}#f59e0b{% endif %};">{% if metrics.server_live.opcache_enabled %}Actif{% else %}Inactif{% endif %}</strong> | OS : <strong>{{ metrics.server_live.os }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Onglets Profilage & Historique -->
    <div class="tabs-container" style="margin-top: 2rem;">
        <ul class="tabs-header">
            <li class="default-tab">
                <i class="fa-solid fa-stopwatch-20"></i> Profilage des Hooks & Plugins
            </li>
            <li>
                <i class="fa-solid fa-list-ol"></i> Historique des Requêtes ({{ metrics.requests|length }})
            </li>
        </ul>

        <ul class="tabs">
            <!-- TAB 1 : Profilage unitaire des Hooks -->
            <li class="tab default-tab">
                <div class="monitor-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.15rem;"><i class="fa-solid fa-gauge"></i> Chronométrage unitaire des Hooks</h3>
                            <p style="margin: 4px 0 0 0; font-size: 0.88rem; color: var(--cb-text-muted, #64748b);">
                                Détectez instantanément les extensions ou points d'accroche qui ralentissent la génération de vos pages.
                            </p>
                        </div>
                    </div>

                    {% if metrics.hooks_profiler|length > 0 %}
                        <div class="table-responsive">
                            <table class="monitor-table" id="hooksTable">
                                <thead>
                                    <tr>
                                        <th>Point d'Accroche (Hook)</th>
                                        <th style="text-align: center;">Exécutions</th>
                                        <th style="text-align: right;">Temps Cumulé</th>
                                        <th style="text-align: right;">Moyenne / Appel</th>
                                        <th style="text-align: right;">Pic Max</th>
                                        <th>Gestionnaire le plus lent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {% for hookName, h in metrics.hooks_profiler %}
                                        <tr>
                                            <td>
                                                <code style="font-weight: 600; color: var(--primary-color, #1f62a9);">{{ hookName }}</code>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge badge-subtle">{{ h.count }}</span>
                                            </td>
                                            <td style="text-align: right; color: var(--cb-text-muted, #64748b); font-variant-numeric: tabular-nums;">
                                                {{ h.total_ms }} ms
                                            </td>
                                            <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;">
                                                <span class="{% if h.avg_ms > 20 %}text-danger{% elseif h.avg_ms > 5 %}text-warning{% else %}text-success{% endif %}">
                                                    {{ h.avg_ms }} ms
                                                </span>
                                            </td>
                                            <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;">
                                                <span class="{% if h.max_ms > 80 %}text-danger{% elseif h.max_ms > 25 %}text-warning{% else %}text-success{% endif %}">
                                                    {{ h.max_ms }} ms
                                                </span>
                                            </td>
                                            <td style="font-size: 0.82rem; color: var(--cb-text-muted, #64748b);">
                                                <code>{{ h.slowest_handler|default('Fonction interne / Closure') }}</code>
                                            </td>
                                        </tr>
                                    {% endfor %}
                                </tbody>
                            </table>
                        </div>
                    {% else %}
                        <div class="msg info">
                            <i class="fa-solid fa-circle-info"></i> Aucun point d'accroche enregistré pour l'instant. Naviguez sur les pages publiques et administratives pour peupler la télémétrie.
                        </div>
                    {% endif %}
                </div>
            </li>

            <!-- TAB 2 : Historique des requêtes -->
            <li class="tab">
                <div class="monitor-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="margin: 0; font-size: 1.15rem;"><i class="fa-solid fa-clock-rotate-left"></i> Flux des Dernières Requêtes</h3>
                    </div>

                    {% if metrics.requests|length > 0 %}
                        <div class="table-responsive">
                            <table class="monitor-table" id="requestsTable">
                                <thead>
                                    <tr>
                                        <th>Horodatage</th>
                                        <th>Méthode</th>
                                        <th>URL / Route</th>
                                        <th style="text-align: center;">Statut</th>
                                        <th style="text-align: right;">Latence</th>
                                        <th style="text-align: right;">Pic RAM</th>
                                        <th style="text-align: center;">Hooks</th>
                                        <th style="text-align: right;">I/O JSON / SQL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {% for req in metrics.requests %}
                                        <tr>
                                            <td style="font-size: 0.82rem; white-space: nowrap; color: var(--cb-text-muted, #64748b);">
                                                {{ req.timestamp }}
                                            </td>
                                            <td>
                                                <span class="badge {% if req.method == 'POST' %}badge-post{% else %}badge-get{% endif %}">
                                                    {{ req.method }}
                                                </span>
                                            </td>
                                            <td style="font-family: monospace; font-size: 0.86rem; word-break: break-all;">
                                                {{ req.uri }}
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge {% if req.status >= 400 %}badge-error{% elseif req.status >= 300 %}badge-redirect{% else %}badge-ok{% endif %}">
                                                    {{ req.status }}
                                                </span>
                                            </td>
                                            <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;">
                                                <span class="{% if req.duration_ms > 300 %}text-danger{% elseif req.duration_ms > 100 %}text-warning{% else %}text-success{% endif %}">
                                                    {{ req.duration_ms }} ms
                                                </span>
                                            </td>
                                            <td style="text-align: right; font-size: 0.86rem;">
                                                {{ req.peak_memory_mb }} Mo
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge badge-subtle">{{ req.hooks_count }}</span>
                                            </td>
                                            <td style="text-align: right; font-size: 0.82rem; color: var(--cb-text-muted, #64748b);">
                                                {{ req.io.json_read|default(0) }}R / {{ req.io.json_write|default(0) }}W | {{ req.io.sql_query|default(0) }} SQL
                                            </td>
                                        </tr>
                                    {% endfor %}
                                </tbody>
                            </table>
                        </div>
                    {% else %}
                        <div class="msg info">
                            <i class="fa-solid fa-circle-info"></i> Aucune requête enregistrée dans la fenêtre glissante.
                        </div>
                    {% endif %}
                </div>
            </li>
        </ul>
    </div>
</section>

<style>
/* Grid KPIs */
.monitor-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}
.monitor-kpi-card {
    background: var(--cb-card-bg, #ffffff);
    border: 1px solid var(--cb-border, #e2e8f0);
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}
.icon-time { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
.icon-ram { background: rgba(168, 85, 247, 0.12); color: #9333ea; }
.icon-io { background: rgba(16, 185, 129, 0.12); color: #059669; }
.icon-server { background: rgba(245, 158, 11, 0.12); color: #d97706; }

.kpi-content { flex: 1; min-width: 0; }
.kpi-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; color: var(--cb-text-muted, #64748b); }
.kpi-value { font-size: 1.7rem; font-weight: 700; color: var(--cb-text, #0f172a); margin: 2px 0; line-height: 1.2; }
.kpi-value small { font-size: 0.9rem; font-weight: 500; color: var(--cb-text-muted, #64748b); }
.kpi-footer { font-size: 0.78rem; color: var(--cb-text-muted, #64748b); }

/* Tables */
.table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px; border: 1px solid var(--cb-border, #e2e8f0); }
.monitor-table { width: 100%; border-collapse: collapse; text-align: left; }
.monitor-table th { background: var(--cb-bg-alt, #f8fafc); padding: 10px 14px; font-size: 0.82rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--cb-text-muted, #64748b); border-bottom: 1px solid var(--cb-border, #e2e8f0); }
.monitor-table td { padding: 12px 14px; border-bottom: 1px solid var(--cb-border, #e2e8f0); font-size: 0.9rem; vertical-align: middle; }
.monitor-table tr:last-child td { border-bottom: 0; }
.monitor-table tbody tr:hover { background: var(--cb-hover, rgba(0,0,0,0.015)); }

/* Badges */
.badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; line-height: 1.4; }
.badge-subtle { background: var(--cb-border, #e2e8f0); color: var(--cb-text, #334155); }
.badge-get { background: rgba(59, 130, 246, 0.15); color: #1d4ed8; font-weight: 700; }
.badge-post { background: rgba(16, 185, 129, 0.15); color: #047857; font-weight: 700; }
.badge-ok { background: rgba(16, 185, 129, 0.15); color: #047857; }
.badge-redirect { background: rgba(59, 130, 246, 0.15); color: #1d4ed8; }
.badge-error { background: rgba(239, 68, 68, 0.15); color: #b91c1c; }

.text-success { color: #10b981; }
.text-warning { color: #f59e0b; }
.text-danger { color: #ef4444; }

/* Dark mode adjustments */
:root[data-theme="dark"] .monitor-kpi-card,
html[data-theme="dark"] .monitor-kpi-card { background: #1e293b; border-color: #334155; }
:root[data-theme="dark"] .kpi-value,
html[data-theme="dark"] .kpi-value { color: #f8fafc; }
:root[data-theme="dark"] .monitor-table th,
html[data-theme="dark"] .monitor-table th { background: #0f172a; border-color: #334155; color: #94a3b8; }
:root[data-theme="dark"] .monitor-table td,
html[data-theme="dark"] .monitor-table td { border-color: #334155; }
:root[data-theme="dark"] .table-responsive,
html[data-theme="dark"] .table-responsive { border-color: #334155; }
:root[data-theme="dark"] .badge-subtle,
html[data-theme="dark"] .badge-subtle { background: #334155; color: #cbd5e1; }
</style>

<script>
// Auto-refresh télémétrie en arrière-plan sans recharger la page
(function() {
    const apiUrl = '{{ apiUrl }}';
    const toggle = document.getElementById('autoRefreshToggle');
    const btnRefresh = document.getElementById('btnManualRefresh');
    let refreshTimer = null;

    async function fetchTelemetry() {
        try {
            const resp = await fetch(apiUrl);
            if (!resp.ok) return;
            const data = await resp.json();
            updateUI(data);
        } catch (e) {
            console.warn('[SystemMonitor] Erreur lors de la récupération des métriques', e);
        }
    }

    function updateUI(data) {
        if (!data || !data.summary) return;

        // Mettre à jour les KPIs
        const avgDur = document.getElementById('kpiAvgDuration');
        if (avgDur) avgDur.innerHTML = `${data.summary.avg_duration_ms || 0} <small>ms</small>`;

        const peakDur = document.getElementById('kpiPeakDuration');
        if (peakDur) peakDur.innerHTML = `Pic max : <strong>${data.summary.max_duration_ms || 0} ms</strong>`;

        const peakMem = document.getElementById('kpiPeakMemory');
        if (peakMem) peakMem.innerHTML = `${data.summary.max_memory_mb || 0} <small>Mo</small>`;

        const avgMem = document.getElementById('kpiAvgMemory');
        if (avgMem) avgMem.innerHTML = `Moyenne : <strong>${data.summary.avg_memory_mb || 0} Mo</strong> (Limite : ${data.server_live?.memory_limit || 'N/A'})`;

        const totalIo = document.getElementById('kpiTotalIo');
        if (totalIo) {
            const ioSum = (data.summary.total_json_reads || 0) + (data.summary.total_json_writes || 0) + (data.summary.total_sql_queries || 0);
            totalIo.textContent = ioSum;
        }

        const ioDet = document.getElementById('kpiIoDetails');
        if (ioDet) {
            ioDet.innerHTML = `JSON : <strong>${data.summary.total_json_reads || 0}</strong> lus / <strong>${data.summary.total_json_writes || 0}</strong> écrits | SQL : <strong>${data.summary.total_sql_queries || 0}</strong> req.`;
        }

        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        const hooksTbody = document.querySelector('#hooksTable tbody');
        if (hooksTbody && data.hooks_profiler) {
            const entries = Object.entries(data.hooks_profiler);
            hooksTbody.innerHTML = entries.map(([hookName, h]) => {
                const avgCls = h.avg_ms > 20 ? 'text-danger' : (h.avg_ms > 5 ? 'text-warning' : 'text-success');
                const maxCls = h.max_ms > 80 ? 'text-danger' : (h.max_ms > 25 ? 'text-warning' : 'text-success');
                return `<tr>
                    <td><code style="font-weight: 600; color: var(--primary-color, #1f62a9);">${esc(hookName)}</code></td>
                    <td style="text-align: center;"><span class="badge badge-subtle">${Number(h.count || 0)}</span></td>
                    <td style="text-align: right; color: var(--cb-text-muted, #64748b); font-variant-numeric: tabular-nums;">${Number(h.total_ms || 0)} ms</td>
                    <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;"><span class="${avgCls}">${Number(h.avg_ms || 0)} ms</span></td>
                    <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;"><span class="${maxCls}">${Number(h.max_ms || 0)} ms</span></td>
                    <td style="font-size: 0.82rem; color: var(--cb-text-muted, #64748b);"><code>${esc(h.slowest_handler || 'Fonction interne / Closure')}</code></td>
                </tr>`;
            }).join('');
        }

        const reqTbody = document.querySelector('#requestsTable tbody');
        if (reqTbody && Array.isArray(data.requests)) {
            reqTbody.innerHTML = data.requests.map((req) => {
                const methodCls = req.method === 'POST' ? 'badge-post' : 'badge-get';
                const st = Number(req.status || 200);
                const statusCls = st >= 400 ? 'badge-error' : (st >= 300 ? 'badge-redirect' : 'badge-ok');
                const dur = Number(req.duration_ms || 0);
                const durCls = dur > 300 ? 'text-danger' : (dur > 100 ? 'text-warning' : 'text-success');
                const io = req.io || {};
                return `<tr>
                    <td style="font-size: 0.82rem; white-space: nowrap; color: var(--cb-text-muted, #64748b);">${esc(req.timestamp)}</td>
                    <td><span class="badge ${methodCls}">${esc(req.method)}</span></td>
                    <td style="font-family: monospace; font-size: 0.86rem; word-break: break-all;">${esc(req.uri)}</td>
                    <td style="text-align: center;"><span class="badge ${statusCls}">${st}</span></td>
                    <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;"><span class="${durCls}">${dur} ms</span></td>
                    <td style="text-align: right; font-size: 0.86rem;">${Number(req.peak_memory_mb || 0)} Mo</td>
                    <td style="text-align: center;"><span class="badge badge-subtle">${Number(req.hooks_count || 0)}</span></td>
                    <td style="text-align: right; font-size: 0.82rem; color: var(--cb-text-muted, #64748b);">${Number(io.json_read || 0)}R / ${Number(io.json_write || 0)}W | ${Number(io.sql_query || 0)} SQL</td>
                </tr>`;
            }).join('');
        }
    }

    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            fetchTelemetry();
        });
    }

    function setupTimer() {
        if (refreshTimer) clearInterval(refreshTimer);
        if (toggle && toggle.checked) {
            refreshTimer = setInterval(fetchTelemetry, 5000);
        }
    }

    if (toggle) {
        toggle.addEventListener('change', setupTimer);
        setupTimer();
    }
})();
</script>

