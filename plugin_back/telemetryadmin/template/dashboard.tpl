<section class="module-card">
    <header>{{ Lang.telemetryadmin.dashboard-title }}</header>
    
    {% if stats.telemetry or stats.reports %}
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
            {% if stats.telemetry %}
            <div class="stat-card">
                <h3>{{ Lang.telemetryadmin.stats-installations }}</h3>
                <div class="stat-value">{{ stats.telemetry.total_installations }}</div>
                <div class="stat-label">{{ Lang.telemetryadmin.stats-total }}</div>
                <div class="stat-value" style="font-size: 1.2em; color: #28a745; margin-top: 0.5rem;">
                    {{ stats.telemetry.active_installations }}
                </div>
                <div class="stat-label">{{ Lang.telemetryadmin.stats-active }}</div>
            </div>
            
            <div class="stat-card">
                <h3>{{ Lang.telemetryadmin.stats-reports }}</h3>
                <div class="stat-value">{{ stats.reports.total }}</div>
                <div class="stat-label">{{ Lang.telemetryadmin.stats-total }}</div>
            </div>
            {% endif %}
        </div>

        {% if stats.telemetry %}
        <div style="margin-top: 2rem; display:grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap:1.5rem;">
            <div>
                <h3>{{ Lang.telemetryadmin.cms-versions }}</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ Lang.telemetryadmin.version }}</th>
                            <th>{{ Lang.telemetryadmin.count }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% FOR version IN stats.telemetry.cms_versions %}
                        <tr>
                            <td>{{ version.cms_version }}</td>
                            <td>{{ version.count }}</td>
                        </tr>
                        {% ENDFOR %}
                    </tbody>
                </table>
            </div>

            <div>
                <h3>{{ Lang.telemetryadmin.php-versions }}</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ Lang.telemetryadmin.version }}</th>
                            <th>{{ Lang.telemetryadmin.count }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% FOR version IN stats.telemetry.php_versions %}
                        <tr>
                            <td>{{ version.php_version }}</td>
                            <td>{{ version.count }}</td>
                        </tr>
                        {% ENDFOR %}
                    </tbody>
                </table>
            </div>

            <div>
                <h3>{{ Lang.telemetryadmin.language_distribution }}</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ Lang.telemetryadmin.lang }}</th>
                            <th>{{ Lang.telemetryadmin.count }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% FOR lang IN stats.telemetry.language_distribution %}
                        <tr>
                            <td>{{ lang.lang }}</td>
                            <td>{{ lang.count }}</td>
                        </tr>
                        {% ENDFOR %}
                    </tbody>
                </table>
            </div>

            <div>
                <h3>{{ Lang.telemetryadmin.popular-themes }}</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ Lang.telemetryadmin.theme }}</th>
                            <th>{{ Lang.telemetryadmin.count }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% FOR t IN stats.telemetry.popular_themes %}
                        <tr>
                            <td>{{ t.theme }}</td>
                            <td>{{ t.count }}</td>
                        </tr>
                        {% ENDFOR %}
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <h3>{{ Lang.telemetryadmin.popular-plugins }}</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ Lang.telemetryadmin.plugin }}</th>
                        <th>{{ Lang.telemetryadmin.installations }}</th>
                    </tr>
                </thead>
                <tbody>
                    {% FOR plugin IN stats.telemetry.popular_plugins %}
                    <tr>
                        <td>{{ plugin.plugin_slug }}</td>
                        <td>{{ plugin.count }}</td>
                    </tr>
                    {% ENDFOR %}
                </tbody>
            </table>
        </div>

        <div style="margin-top:2rem;">
            <h3>{{ Lang.telemetryadmin.active-timeline }}</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ Lang.telemetryadmin.date }}</th>
                        <th>{{ Lang.telemetryadmin.count }}</th>
                    </tr>
                </thead>
                <tbody>
                    {% FOR row IN stats.telemetry.active_timeline %}
                    <tr>
                        <td>{{ row.day }}</td>
                        <td>{{ row.count }}</td>
                    </tr>
                    {% ENDFOR %}
                </tbody>
            </table>
        </div>

        <div style="margin-top:2rem; display:flex; gap:1rem; flex-wrap:wrap;">
            <a href="{{ ROUTER.generate("telemetryadmin-installations") }}" class="button">{{ Lang.telemetryadmin.view-installations }}</a>
            <a href="{{ ROUTER.generate("telemetryadmin-sync-logs") }}" class="button ghost">{{ Lang.telemetryadmin.sync-logs-title }}</a>
        </div>
        {% endif %}

        {% if stats.reports %}
        <div style="margin-top: 2rem;">
            <h3>{{ Lang.telemetryadmin.reports-by-status }}</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ Lang.telemetryadmin.status }}</th>
                        <th>{{ Lang.telemetryadmin.count }}</th>
                    </tr>
                </thead>
                <tbody>
                    {% FOR status IN stats.reports.by_status %}
                    <tr>
                        <td>{{ status.status }}</td>
                        <td>{{ status.count }}</td>
                    </tr>
                    {% ENDFOR %}
                </tbody>
            </table>
        </div>

        <div style="margin-top: 2rem;">
            <h3>{{ Lang.telemetryadmin.reports-by-type }}</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ Lang.telemetryadmin.type }}</th>
                        <th>{{ Lang.telemetryadmin.count }}</th>
                    </tr>
                </thead>
                <tbody>
                    {% FOR type IN stats.reports.by_type %}
                    <tr>
                        <td>{{ type.type }}</td>
                        <td>{{ type.count }}</td>
                    </tr>
                    {% ENDFOR %}
                </tbody>
            </table>
        </div>
        {% endif %}

        <div style="margin-top: 2rem;">
            <a href="{{ ROUTER.generate("telemetryadmin-reports") }}" class="button">
                <i class="fa-solid fa-list"></i> {{ Lang.telemetryadmin.view-reports }}
            </a>
        </div>
    {% endif %}
</section>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.stat-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 1rem 0;
    font-size: 1.1em;
    color: #495057;
}

.stat-value {
    font-size: 2.5em;
    font-weight: bold;
    color: #1a3a5f;
    margin: 0.5rem 0;
}

.stat-label {
    font-size: 0.9em;
    color: #6c757d;
    margin-top: 0.25rem;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.table th,
.table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
}
</style>

