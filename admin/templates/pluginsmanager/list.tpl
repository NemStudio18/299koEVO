<section class="module-card">
    <header style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-plug" style="color:var(--evo-accent, #3b82f6);"></i>
            <span>{{Lang.pluginsmanager.plugins-list}}</span>
        </div>
        <div class="pm-actions-group">
            <a class="button success small" href="{ ROUTER.generate("marketplace-plugins") }">
                <i class="fa-solid fa-store"></i> {{Lang.pluginsmanager.open-marketplace}}
            </a>
            <a class="button small" href="{ ROUTER.generate("pluginsmanager-inspect-all") }">
                <i class="fa-solid fa-shield-halved"></i> Audit de sécurité
            </a>
        </div>
    </header>

    <div class="pm-container" id="pmContainer" style="padding:16px;">
        <!-- Top Toolbar with Real-Time Search, Filters & Sorting -->
        <div class="pm-toolbar">
            <div class="pm-toolbar-top">
                <div class="pm-search-wrap">
                    <i class="fa-solid fa-magnifying-glass pm-search-icon"></i>
                    <input type="search" id="pmSearchInput" class="pm-search-input" placeholder="Rechercher par nom, slug, mot-clé..." autocomplete="off">
                    <button type="button" id="pmSearchClear" class="pm-search-clear" title="Effacer" style="display:none;"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="pm-sort-wrap">
                    <label for="pmSortSelect"><i class="fa-solid fa-arrow-down-a-z"></i> Trier par :</label>
                    <select id="pmSortSelect" class="pm-sort-select">
                        <option value="name-asc">Nom (A - Z)</option>
                        <option value="name-desc">Nom (Z - A)</option>
                        <option value="priority-asc">Priorité (1 - 9)</option>
                        <option value="priority-desc">Priorité (9 - 1)</option>
                    </select>
                </div>
            </div>

            <!-- Filter Pills Row with KPI counts -->
            <div class="pm-filters-bar">
                <button type="button" class="pm-filter-pill is-active" data-filter="all">
                    <i class="fa-solid fa-layer-group"></i> Tous <span class="pm-pill-badge">{{pluginStats.total}}</span>
                </button>
                <button type="button" class="pm-filter-pill" data-filter="active">
                    <i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Actifs <span class="pm-pill-badge">{{pluginStats.active}}</span>
                </button>
                <button type="button" class="pm-filter-pill" data-filter="inactive">
                    <i class="fa-solid fa-circle-xmark" style="color:#94a3b8;"></i> Inactifs <span class="pm-pill-badge">{{pluginStats.inactive}}</span>
                </button>
                <button type="button" class="pm-filter-pill" data-filter="settings">
                    <i class="fa-solid fa-sliders" style="color:#3b82f6;"></i> Configurables <span class="pm-pill-badge">{{pluginStats.configurable}}</span>
                </button>
                {% if pluginStats.maintenance > 0 %}
                <button type="button" class="pm-filter-pill" data-filter="maintenance">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;"></i> Maintenance <span class="pm-pill-badge">{{pluginStats.maintenance}}</span>
                </button>
                {% endif %}

                <span class="pm-count-indicator" id="pmCountIndicator">{{pluginStats.total}} extensions</span>
            </div>
        </div>

        {% if legacyPluginsCount > 0 %}
            <div class="pm-legacy-alert">
                <div>
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    {{Lang.pluginsmanager.legacy-warning}} : <strong>{{legacyPluginsList}}</strong>
                </div>
                <a class="button small warning" href="{ ROUTER.generate("admin-marketplace") }">
                    <i class="fa-solid fa-arrow-right"></i> {{Lang.pluginsmanager.legacy-button}}
                </a>
            </div>
        {% endif %}

        <!-- Plugins Form & Table -->
        <form method="post" action="{ ROUTER.generate("pluginsmanager-save") }" id="pluginsmanagerForm">
            {{SHOW.tokenField}}
            <div class="pm-table-card">
                <table class="pm-table">
                    <thead>
                        <tr>
                            <th style="min-width:280px;">Extension</th>
                            <th style="width:140px;">Statut</th>
                            <th style="width:110px;">Priorité</th>
                            <th style="width:90px;text-align:center;">Actif</th>
                            <th style="min-width:180px;text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pmTableBody">
                        {% FOR plugin IN plugins %}
                            <tr class="pm-row {% IF plugin.getConfigVal("activate") %}is-active{% ELSE %}is-inactive{% ENDIF %}"
                                data-slug="{{plugin.getName()}}"
                                data-name="{{plugin.getTranslatedName()}}"
                                data-desc="{{plugin.getTranslatedDesc()}}"
                                data-cat="{{plugin.getInfoVal('adminCategory')}}"
                                data-priority="{{plugin.getConfigVal('priority')}}"
                                data-active="{% IF plugin.getConfigVal('activate') %}1{% ELSE %}0{% ENDIF %}"
                                data-settings="{% IF plugin.getConfigVal('activate') && plugin.getIsCallableOnAdmin() %}1{% ELSE %}0{% ENDIF %}"
                                data-maintenance="{% IF plugin.getConfigVal('activate') && plugin.isInstalled() == false %}1{% ELSE %}0{% ENDIF %}">
                                
                                <!-- Extension Identity -->
                                <td>
                                    <div class="pm-plugin-cell">
                                        <div class="pm-plugin-icon-wrap">
                                            <i class="{% IF plugin.getInfoVal('icon') %}{{plugin.getInfoVal('icon')}}{% ELSE %}fa-solid fa-puzzle-piece{% ENDIF %}" aria-hidden="true"></i>
                                        </div>
                                        <div class="pm-plugin-info">
                                            <div class="pm-plugin-title-row">
                                                <span class="pm-plugin-name">{{plugin.getTranslatedName()}}</span>
                                                <span class="pm-badge-version">v{{plugin.getInfoVal("version")}}</span>
                                                <span class="pm-badge-slug">{{plugin.getName()}}</span>
                                                {% IF plugin.getInfoVal('adminCategory') %}
                                                    <span class="pm-badge-cat">{{plugin.getInfoVal('adminCategory')}}</span>
                                                {% ENDIF %}
                                            </div>
                                            <div class="pm-plugin-desc">{{plugin.getTranslatedDesc()}}</div>
                                            {% IF plugin.getConfigVal("activate") && plugin.isInstalled() == false %}
                                                <div style="margin-top:6px;">
                                                    <a class="button warning small" href="{ ROUTER.generate("pluginsmanager-maintenance", ["plugin" => plugin.getName(), "token" => token]) }">
                                                        <i class="fa-solid fa-wrench"></i> {{Lang.pluginsmanager.maintenance-required}}
                                                    </a>
                                                </div>
                                            {% ENDIF %}
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td>
                                    {% IF plugin.getConfigVal("activate") %}
                                        {% IF plugin.isInstalled() == false %}
                                            <span class="pm-status-badge status-warning">
                                                <span class="pm-dot"></span> Maintenance
                                            </span>
                                        {% ELSE %}
                                            <span class="pm-status-badge status-active">
                                                <span class="pm-dot"></span> Actif
                                            </span>
                                        {% ENDIF %}
                                    {% ELSE %}
                                        <span class="pm-status-badge status-inactive">
                                            <span class="pm-dot"></span> Inactif
                                        </span>
                                    {% ENDIF %}
                                </td>

                                <!-- Priority Dropdown -->
                                <td>
                                    <div class="pm-priority-wrap">
                                        <select class="pm-priority-select" name="priority[{{plugin.getName()}}]" title="Ordre d'exécution du plugin (1 = haute priorité)" onchange="document.getElementById('pluginsmanagerForm').submit();">
                                            {% FOR k, v IN priority %}
                                                <option {% IF plugin.getConfigVal("priority") == v %}selected{% ENDIF %} value="{{v}}">{{v}}</option>
                                            {% ENDFOR %}
                                        </select>
                                    </div>
                                </td>

                                <!-- Switch Toggle (Activation) -->
                                <td style="text-align:center;">
                                    <label class="pm-switch" title="Activer / Désactiver {{plugin.getTranslatedName()}}">
                                        <input onchange="document.getElementById('pluginsmanagerForm').submit();" id="activate[{{plugin.getName()}}]" type="checkbox" name="activate[{{plugin.getName()}}]" {% IF plugin.getConfigVal("activate") %}checked{% ENDIF %} />
                                        <span class="pm-slider"></span>
                                    </label>
                                </td>

                                <!-- Action Buttons -->
                                <td style="text-align:right;">
                                    <div class="pm-actions-cell">
                                        {% IF plugin.getConfigVal("activate") && plugin.getIsCallableOnAdmin() %}
                                            <a class="pm-btn-action pm-btn-settings" href="/admin/{{plugin.getName()}}" title="Accéder aux réglages de {{plugin.getTranslatedName()}}">
                                                <i class="fa-solid fa-sliders" aria-hidden="true"></i> Paramètres
                                            </a>
                                        {% ENDIF %}
                                        <a class="pm-btn-action" href="/admin/pluginsmanager/inspect/{{plugin.getName()}}" title="Inspecter la sécurité et l'architecture">
                                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Inspecter
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        {% ENDFOR %}
                    </tbody>
                </table>

                <!-- Empty State -->
                <div class="pm-empty-state" id="pmEmptyState" style="display:none;">
                    <i class="fa-solid fa-filter-circle-xmark pm-empty-icon" aria-hidden="true"></i>
                    <div class="pm-empty-title">Aucune extension trouvée</div>
                    <div class="pm-empty-desc">Aucun plugin ne correspond à vos critères de recherche actuels.</div>
                    <button type="button" class="button small" id="pmResetBtn">
                        <i class="fa-solid fa-rotate-left"></i> Réinitialiser les filtres
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>
