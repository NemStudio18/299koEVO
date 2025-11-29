<section class="module-card">
    <header>{{ Lang.pluginsmanager.plugins-list }}</header>
    <div class="tabs-container">
        <ul class="tabs-header">
            <li class="default-tab"><i class="fa-solid fa-chart-pie"></i> {{ Lang.core-overview }}</li>
            <li><i class="fa-solid fa-list-check"></i> {{ Lang.pluginsmanager.plugins-list }}</li>
        </ul>
        <ul class="tabs">
            <li class="tab">
        <div class="module-actions">
            <a class="button success" href="{{ ROUTER.generate("marketplace-plugins") }}">
                <i class="fa-solid fa-store"></i> {{ Lang.pluginsmanager.open-marketplace }}
            </a>
        </div>
        <div class="module-grid">
            <div class="stat-card">
                <span class="stat-value">{{ pluginStats.total }}</span>
                <span class="stat-label">{{ Lang.pluginsmanager.summary-total }}</span>
            </div>
            <div class="stat-card">
                <span class="stat-value">{{ pluginStats.active }}</span>
                <span class="stat-label">{{ Lang.pluginsmanager.summary-active }}</span>
            </div>
            <div class="stat-card">
                <span class="stat-value">{{ pluginStats.inactive }}</span>
                <span class="stat-label">{{ Lang.pluginsmanager.summary-inactive }}</span>
            </div>
            <div class="stat-card">
                <span class="stat-value">{{ pluginStats.maintenance }}</span>
                <span class="stat-label">{{ Lang.pluginsmanager.summary-maintenance }}</span>
            </div>
        </div>

        {% if legacyPluginsCount > 0 %}
            <div class="info-message warning legacy-message">
                <p>{{ Lang.pluginsmanager.legacy-warning }}<br><strong>{{ legacyPluginsList }}</strong></p>
                <a class="button" href="{{ ROUTER.generate("admin-marketplace") }}">
                    <i class="fa-solid fa-arrow-right"></i> {{ Lang.pluginsmanager.legacy-button }}
                </a>
            </div>
        {% endif %}
            </li>
            <li class="tab">
        <form method="post" action="{{ ROUTER.generate("pluginsmanager-save") }}" id="pluginsmanagerForm">
            {{ SHOW.tokenField }}
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>{{ Lang.pluginsmanager.plugin-name }}</th>
                            <th>{{ Lang.pluginsmanager.plugin-version }}</th>
                            <th>{{ Lang.pluginsmanager.priority }}</th>
                            <th>{{ Lang.pluginsmanager.activate }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% FOR plugin IN plugins %}
                            <tr>
                                <td>
                                    <strong>{{ plugin.getTranslatedName() }}</strong>
                                    <div class="small">{{ plugin.getTranslatedDesc() }}</div>
                                    {% IF plugin.getConfigVal("activate") && plugin.isInstalled() == false %}
                                        <p>
                                            <a class="button warning" href="{{ ROUTER.generate("pluginsmanager-maintenance", ["plugin" => plugin.getName(), "token" => token]) }}">{{ Lang.pluginsmanager.maintenance-required }}</a>
                                        </p>
                                    {% ENDIF %}
                                </td>
                                <td>{{ plugin.getInfoVal("version") }}</td>
                                <td>
                                    <select name="priority[{{ plugin.getName() }}]" onchange="document.getElementById('pluginsmanagerForm').submit();">
                                        {% FOR k, v IN priority %}
                                            <option {% IF plugin.getconfigVal("priority") == v %}selected{% ENDIF %} value="{{ v }}">{{ v }}</option>
                                        {% ENDFOR %}
                                    </select>
                                </td>
                                <td>
                                    <input onchange="document.getElementById('pluginsmanagerForm').submit();" id="activate[{{ plugin.getName() }}]" type="checkbox" name="activate[{{ plugin.getName() }}]" {% IF plugin.getConfigVal("activate") %}checked{% ENDIF %} />
                                </td>
                            </tr>
                        {% ENDFOR %}
                    </tbody>
                </table>
            </div>
        </form>
            </li>
        </ul>
    </div>
</section>
