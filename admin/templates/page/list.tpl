<section class="module-card">
    <header>{{Lang.page.page-list}}</header>
    <div class="tabs-container">
        <ul class="tabs-header">
            <li class="default-tab"><i class="fa-solid fa-chart-simple"></i> {{Lang.core-overview}}</li>
            <li><i class="fa-solid fa-lock"></i> Accessibilité</li>
            <li><i class="fa-solid fa-sort"></i> Tri Navigation</li>
        </ul>
        <ul class="tabs">
            <!-- Onglet 1 : Aperçu -->
            <li class="tab default-tab">
                <div class="tabs-container" style="border: none; box-shadow: none; padding: 0;">
                    <ul class="tabs-header sub-tabs" style="justify-content: flex-start; gap: 20px; border-bottom: 2px solid #eee; margin-bottom: 25px;">
                        <li class="default-tab" style="padding: 10px 0; border: none; background: transparent; color: #666; font-weight: 600; cursor: pointer;">
                            <i class="fa-solid fa-chart-simple"></i> Statistiques détaillées
                        </li>
                        <li style="padding: 10px 0; border: none; background: transparent; color: #666; font-weight: 600; cursor: pointer;">
                            <i class="fa-solid fa-list-check"></i> Gestion par type
                        </li>
                    </ul>
                    <ul class="tabs">
                        <!-- Sous-onglet 1.1 : Stats -->
                        <li class="tab default-tab">
                            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
                                <div class="stat-card" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; border-radius: 8px;">
                                    <span class="stat-value" style="display: block; font-size: 2em; font-weight: bold; color: #1976d2;">{{pageStats.realPages}}</span>
                                    <span class="stat-label" style="color: #546e7a; font-size: 0.9em; font-weight: 600;">Vraies Pages CMS</span>
                                </div>
                                <div class="stat-card" style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 20px; border-radius: 8px;">
                                    <span class="stat-value" style="display: block; font-size: 2em; font-weight: bold; color: #e65100;">{{pageStats.links}}</span>
                                    <span class="stat-label" style="color: #546e7a; font-size: 0.9em; font-weight: 600;">Liens externes/URL</span>
                                </div>
                                <div class="stat-card" style="background: #f3e5f5; border-left: 4px solid #9c27b0; padding: 20px; border-radius: 8px;">
                                    <span class="stat-value" style="display: block; font-size: 2em; font-weight: bold; color: #7b1fa2;">{{pageStats.plugins}}</span>
                                    <span class="stat-label" style="color: #546e7a; font-size: 0.9em; font-weight: 600;">Items de Plugins</span>
                                </div>
                                <div class="stat-card" style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; border-radius: 8px;">
                                    <span class="stat-value" style="display: block; font-size: 2em; font-weight: bold; color: #2e7d32;">{{pageStats.totalItems}}</span>
                                    <span class="stat-label" style="color: #546e7a; font-size: 0.9em; font-weight: 600;">Nombre total d'items</span>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                <div class="stat-section" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #eee;">
                                    <h4 style="margin-top: 0; border-bottom: 2px solid #f8f9fa; padding-bottom: 10px;"><i class="fa-solid fa-sitemap"></i> Structure & Navigation</h4>
                                    <ul style="list-style: none; padding: 0; line-height: 2.2;">
                                        <li style="display: flex; justify-content: space-between;"><span>Menu Principal (MP)</span> <strong>{{pageStats.menus.main}}</strong></li>
                                        <li style="display: flex; justify-content: space-between;"><span>Menu Avatar (MA)</span> <strong>{{pageStats.menus.avatar}}</strong></li>
                                        <li style="display: flex; justify-content: space-between;"><span>Pied de page (PP)</span> <strong>{{pageStats.menus.footer}}</strong></li>
                                        <li style="border-top: 1px dashed #eee; margin: 10px 0;"></li>
                                        <li style="display: flex; justify-content: space-between;"><span>Niveau Principal</span> <strong>{{pageStats.mainLevel}}</strong></li>
                                        <li style="display: flex; justify-content: space-between;"><span>Sous-pages / Enfants</span> <strong>{{pageStats.subItems}}</strong></li>
                                    </ul>
                                </div>
                                <div class="stat-section" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #eee;">
                                    <h4 style="margin-top: 0; border-bottom: 2px solid #f8f9fa; padding-bottom: 10px;"><i class="fa-solid fa-triangle-exclamation"></i> États & Alertes</h4>
                                    <ul style="list-style: none; padding: 0; line-height: 2.2;">
                                        <li style="display: flex; justify-content: space-between;"><span>Items masqués/inactifs</span> <strong style="color: #d32f2f;">{{pageStats.hidden}}</strong></li>
                                        <li style="display: flex; justify-content: space-between;"><span>Pages orphelines (Ghost)</span> <strong style="color: #f57c00;">{{pageStats.orphans}}</strong></li>
                                        {% if lostCount > 0 %}
                                            <li style="margin-top: 15px;">
                                                <div class="info-message warning tiny" style="margin: 0;">
                                                    <p style="font-size: 0.85em;">{{Lang.page.ghost-pages-found}} <a href="{{ROUTER.generate("page-admin-maintenance", ["id" => lost, "token" => token])}}">{{Lang.page.click-here}}</a></p>
                                                </div>
                                            </li>
                                        {% endif %}
                                    </ul>
                                </div>
                            </div>
                        </li>

                        <!-- Sous-onglet 1.2 : Gestion -->
                        <li class="tab">
                            <div class="table-responsive">
                                <div class="module-actions" style="margin-bottom: 20px;">
                                    <a class="button success" href="{{ROUTER.generate("page-admin-new")}}">
                                        <i class="fa-solid fa-square-plus"></i> {{Lang.page.add-element}}
                                    </a>
                                </div>
                                {% for group in managedGroups %}
                                    {% set items = group.items %}
                                    <div class="table-container" style="margin-bottom: 30px;">
                                        <h3 style="font-size: 1em; background: #fafafa; border: 1px solid #eee; padding: 10px; border-radius: 4px 4px 0 0; margin-bottom: 0;">
                                            <i class="{{group.icon}}"></i> {{group.label}}
                                            <span class="count-badge" style="background: #eee; font-size: 0.8em; padding: 2px 8px; border-radius: 10px; margin-left: 10px;">
                                                {{group.count}}
                                            </span>
                                        </h3>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Nom</th>
                                                    <th>Adresse / Cible</th>
                                                    <th style="width: 120px; text-align: right;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {% for wrapper in items %}
                                                    {% set item = wrapper.item %}
                                                    <tr>
                                                        <td>
                                                            <i class="{{wrapper.icon}}" style="margin-right: 8px; color: #555;"></i>
                                                            <strong>{% if item.getName() %}{{item.getName()}}{% else %}{{item.getLabel()}}{% endif %}</strong>
                                                        </td>
                                                        <td><small style="color: #666;">{% if wrapper.type == 'page' %}{{page.makeUrl(item)}}{% else %}{{item.getTarget()}}{% endif %}</small></td>
                                                        <td style="text-align: right;">
                                                            {% if wrapper.type == 'page' %}
                                                                <a class="button tiny primary" href="{{ROUTER.generate("page-admin-edit", ["id" => item.getId()])}}">
                                                                    <i class="fa-solid fa-pen"></i> Modifier
                                                                </a>
                                                            {% else %}
                                                                <span style="color: #ccc; font-size: 0.8em; font-style: italic;">Plugin</span>
                                                            {% endif %}
                                                        </td>
                                                    </tr>
                                                {% endfor %}
                                            </tbody>
                                        </table>
                                    </div>
                                {% endfor %}
                            </div>
                        </li>
                    </ul>
                </div>
            </li>
            <!-- Onglet 2 : Accessibilité -->
            <li class="tab">
                <style>
                    .table-container { margin-bottom: 40px; }
                    .table-container h3 { 
                        background: #f8f9fa; padding: 10px 15px; border-radius: 8px 8px 0 0; 
                        margin: 0; border: 1px solid #eee; border-bottom: none;
                        font-size: 1.1em; display: flex; align-items: center; gap: 10px;
                    }
                    .type-badge {
                        font-size: 0.75em; padding: 2px 8px; border-radius: 12px; font-weight: 600;
                        text-transform: uppercase; letter-spacing: 0.5px;
                    }
                    .badge-page { background: #e3f2fd; color: #1976d2; }
                    .badge-plugin { background: #f3e5f5; color: #7b1fa2; }
                    .badge-url { background: #fff3e0; color: #ef6c00; }
                    .badge-parent { background: #e8f5e9; color: #2e7d32; }
                </style>

                <div class="table-responsive" id="accessibility-tables-container">
                    {# Éléments Actifs #}
                    <div class="table-container" id="container-active">
                        <h3>
                            <i class="fa-solid fa-circle-check" style="color: #28a745;"></i>
                            Éléments Actifs
                            <span class="count-badge" style="background: #e8f5e9; font-size: 0.7em; padding: 2px 8px; border-radius: 10px; color: #2e7d32;">{{accessibilityGroups.activeCount}}</span>
                        </h3>
                        <table>
                            <thead>
                                <tr>
                                    <th colspan="2" style="background: transparent; border: none;"></th>
                                    <th colspan="3" style="text-align: center; background: #e3f2fd; color: #1565c0; border-bottom: 2px solid #bbdefb; font-size: 0.85em; text-transform: uppercase; font-weight: bold; padding: 4px 0;">Affichage</th>
                                    <th colspan="1" style="text-align: center; background: #f3e5f5; color: #7b1fa2; border-bottom: 2px solid #e1bee7; font-size: 0.85em; text-transform: uppercase; font-weight: bold; padding: 4px 0;">Mode</th>
                                    <th colspan="3" style="text-align: center; background: #fff8e1; color: #ff8f00; border-bottom: 2px solid #ffecb3; font-size: 0.85em; text-transform: uppercase; font-weight: bold; padding: 4px 0;">Accès (Sécurité)</th>
                                </tr>
                                <tr>
                                    <th>{{Lang.page.page-name}}</th>
                                    <th>Type</th>
                                    <th style="text-align: center; width: 40px; background: #e3f2fd; border-top: none;" title="Menu Principal">MP</th>
                                    <th style="text-align: center; width: 40px; background: #e3f2fd; border-top: none;" title="Menu Avatar">MA</th>
                                    <th style="text-align: center; width: 40px; background: #e3f2fd; border-top: none;" title="Pied de Page">PP</th>
                                    <th style="text-align: center; width: 40px; background: #f3e5f5; border-top: none;" title="Auto (MP ↔ MA)">AUTO</th>
                                    <th style="text-align: center; width: 50px; background: #fff8e1; border-top: none;" title="Public (Tous)">PUB</th>
                                    <th style="text-align: center; width: 50px; background: #fff8e1; border-top: none;" title="Membres (Connectés)">MEM</th>
                                    <th style="text-align: center; width: 50px; background: #fff8e1; border-top: none;" title="Admin (Seulement)">ADM</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-active">
                                {% for wrapper in accessibilityGroups.active %}
                                    {% set item = wrapper.item %}
                                    <tr data-id="{{item.getId()}}" data-type="{{wrapper.type}}" 
                                        data-name="{% if item.getName() %}{{item.getName()}}{% else %}{{item.getLabel()}}{% endif %}" 
                                        data-icon="{{wrapper.icon}}"
                                        data-url="{% if wrapper.type == 'page' %}{{page.makeUrl(item)}}{% else %}{{item.getTarget()}}{% endif %}">
                                        <td><strong>{{item.getName()|default(item.getLabel())}}</strong></td>
                                        <td>
                                            {% if wrapper.type == 'plugin' %}
                                                <span class="type-badge badge-plugin">Plugin</span>
                                            {% else %}
                                                {% if item.targetIs() == 'page' %}<span class="type-badge badge-page">Page</span>
                                                {% elseif item.targetIs() == 'url' %}<span class="type-badge badge-url">Lien</span>
                                                {% elseif item.targetIs() == 'parent' %}<span class="type-badge badge-parent">Dossier</span>
                                                {% elseif item.targetIs() == 'plugin' %}<span class="type-badge badge-plugin">Plugin</span>
                                                {% endif %}
                                            {% endif %}
                                        </td>
                                        <td style="text-align: center; background: #e3f2fd; border-left: 1px solid #e1f5fe;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="MP" {% if item.getVisibilityFlag("MP") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #e3f2fd;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="MA" {% if item.getVisibilityFlag("MA") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #e3f2fd; border-right: 1px solid #e1f5fe;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="PP" {% if item.getVisibilityFlag("PP") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #f3e5f5; border-left: 1px solid #f3e5f5; border-right: 1px solid #f3e5f5;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="AUTO" {% if item.getVisibilityFlag("AUTO") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #fff8e1; border-left: 1px solid #fff9c4;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="PUB" {% if item.getVisibilityFlag("PUB") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #fff8e1;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="MEM" {% if item.getVisibilityFlag("MEM") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #fff8e1; border-right: 1px solid #fff9c4;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="ADM" {% if item.getVisibilityFlag("ADM") %}checked{% endif %}></td>
                                    </tr>
                                {% endfor %}
                            </tbody>
                        </table>
                    </div>

                    {# Éléments Inactifs #}
                    <div class="table-container" id="container-inactive">
                        <h3 style="background: #fff0f0; border-color: #ffcdd2; color: #c62828;">
                            <i class="fa-solid fa-eye-slash"></i>
                            Éléments Inactifs
                            <span class="count-badge" style="background: #ffcdd2; color: #c62828; font-size: 0.7em; padding: 2px 8px; border-radius: 10px;">{{accessibilityGroups.inactiveCount}}</span>
                        </h3>
                        <table>
                            <thead>
                                <tr>
                                    <th colspan="2" style="background: transparent; border: none;"></th>
                                    <th colspan="3" style="text-align: center; background: #e3f2fd; color: #1565c0; border-bottom: 2px solid #bbdefb; font-size: 0.85em; text-transform: uppercase; font-weight: bold; padding: 4px 0;">Affichage</th>
                                    <th colspan="1" style="text-align: center; background: #f3e5f5; color: #7b1fa2; border-bottom: 2px solid #e1bee7; font-size: 0.85em; text-transform: uppercase; font-weight: bold; padding: 4px 0;">Mode</th>
                                    <th colspan="3" style="text-align: center; background: #fff8e1; color: #ff8f00; border-bottom: 2px solid #ffecb3; font-size: 0.85em; text-transform: uppercase; font-weight: bold; padding: 4px 0;">Accès (Sécurité)</th>
                                </tr>
                                <tr>
                                    <th>{{Lang.page.page-name}}</th>
                                    <th>Type</th>
                                    <th style="text-align: center; width: 40px; background: #e3f2fd; border-top: none;" title="Menu Principal">MP</th>
                                    <th style="text-align: center; width: 40px; background: #e3f2fd; border-top: none;" title="Menu Avatar">MA</th>
                                    <th style="text-align: center; width: 40px; background: #e3f2fd; border-top: none;" title="Pied de Page">PP</th>
                                    <th style="text-align: center; width: 40px; background: #f3e5f5; border-top: none;" title="Auto (MP ↔ MA)">AUTO</th>
                                    <th style="text-align: center; width: 50px; background: #fff8e1; border-top: none;" title="Public (Tous)">PUB</th>
                                    <th style="text-align: center; width: 50px; background: #fff8e1; border-top: none;" title="Membres (Connectés)">MEM</th>
                                    <th style="text-align: center; width: 50px; background: #fff8e1; border-top: none;" title="Admin (Seulement)">ADM</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-inactive">
                                {% for wrapper in accessibilityGroups.inactive %}
                                    {% set item = wrapper.item %}
                                    <tr data-id="{{item.getId()}}" data-type="{{wrapper.type}}"
                                        data-name="{% if item.getName() %}{{item.getName()}}{% else %}{{item.getLabel()}}{% endif %}" 
                                        data-icon="{% if item.getIcon() %}{{item.getIcon()}}{% else %}fa-solid fa-file{% endif %}"
                                        data-url="{% if wrapper.type == 'page' %}{{page.makeUrl(item)}}{% else %}{{item.getTarget()}}{% endif %}">
                                        <td style="color: #999;">{% if item.getName() %}{{item.getName()}}{% else %}{{item.getLabel()}}{% endif %}</td>
                                        <td style="opacity: 0.6;">
                                            {% if item.targetIs() == 'page' %}<span class="type-badge badge-page">Page</span>
                                            {% elseif item.targetIs() == 'url' %}<span class="type-badge badge-url">Lien</span>
                                            {% elseif item.targetIs() == 'parent' %}<span class="type-badge badge-parent">Dossier</span>
                                            {% elseif item.targetIs() == 'plugin' %}<span class="type-badge badge-plugin">Plugin</span>
                                            {% endif %}
                                        </td>
                                        <td style="text-align: center; background: #e3f2fd; border-left: 1px solid #e1f5fe;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="MP" {% if item.getVisibilityFlag("MP") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #e3f2fd;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="MA" {% if item.getVisibilityFlag("MA") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #e3f2fd; border-right: 1px solid #e1f5fe;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="PP" {% if item.getVisibilityFlag("PP") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #f3e5f5; border-left: 1px solid #f3e5f5; border-right: 1px solid #f3e5f5;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="AUTO" {% if item.getVisibilityFlag("AUTO") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #fff8e1; border-left: 1px solid #fff9c4;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="PUB" {% if item.getVisibilityFlag("PUB") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #fff8e1;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="MEM" {% if item.getVisibilityFlag("MEM") %}checked{% endif %}></td>
                                        <td style="text-align: center; background: #fff8e1; border-right: 1px solid #fff9c4;"><input type="checkbox" class="visibility-flag" data-id="{{item.getId()}}" data-type="{{wrapper.type}}" data-flag="ADM" {% if item.getVisibilityFlag("ADM") %}checked{% endif %}></td>
                                    </tr>
                                {% endfor %}
                            </tbody>
                        </table>
                    </div>
                </div>
            </li>

            <!-- Onglet 3 : Tri Navigation -->
            <li class="tab">
                <style>
                    .nested-sortable { list-style: none; padding: 0; margin: 0; min-height: 10px; }
                    .nested-sortable .sortable-item { 
                        background: #fff; border: 1px solid #eee; margin-bottom: 5px; border-radius: 4px;
                    }
                    .nested-sortable .item-content {
                        display: flex; align-items: center; padding: 10px; cursor: move;
                    }
                    .nested-sortable .item-content:hover { background: #f9f9f9; }
                    .nested-sortable .drag-handle { margin-right: 10px; color: #ccc; cursor: move; }
                    .nested-sortable .item-icon { width: 30px; text-align: center; color: #555; margin-right: 10px; }
                    .nested-sortable .item-type { font-size: 0.75em; padding: 2px 6px; border-radius: 4px; margin-left: auto; margin-right: 10px; background: #eee; color: #666; }
                    .nested-sortable .item-url { font-size: 0.85em; color: #999; margin-right: 15px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                    
                    /* Sous-listes */
                    .nested-sortable .children-container {
                        padding-left: 30px; border-top: 1px solid #f0f0f0; background: #fcfcfc;
                        min-height: 5px; /* Zone de drop minime */
                    }
                    /* Empty placeholder style */
                    .nested-sortable .children-container:empty { padding: 5px 0 5px 30px; }
                    
                    .sortable-ghost { opacity: 0.4; background: #e3f2fd; border: 1px dashed #2196f3; }
                    
                    /* Badges */
                    .badge-page { background: #e3f2fd; color: #1976d2; }
                    .badge-folder { background: #e8f5e9; color: #2e7d32; font-weight: bold; }
                    .badge-link { background: #fff3e0; color: #ef6c00; }
                    .badge-plugin { background: #f3e5f5; color: #7b1fa2; }
                </style>

                <div class="list-controls" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <p class="sortable-hint" style="margin: 0; padding: 8px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px; font-size: 0.9em; width: 100%;">
                        <i class="fa-solid fa-info-circle"></i> {{Lang.page.drag-to-reorder}} - Glissez les pages dans les dossiers pour les organiser.
                    </p>
                </div>

                <div id="sorting-tables-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    {% FOR group IN sortingGroups %}
                        <div class="table-container" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding-bottom: 10px;">
                            <h3 style="background: #f8f9fa; padding: 10px 15px; margin: 0 0 10px 0; border-bottom: 1px solid #eee; border-radius: 8px 8px 0 0;">
                                <i class="fa-solid {{ group.icon }}"></i> {{ group.label }}
                                <span class="count-badge" style="float: right; background: #eee; font-size: 0.7em; padding: 2px 8px; border-radius: 10px;">{{ group.count }}</span>
                            </h3>
                            <div class="root-list" data-context="{{ group.key }}" style="padding: 0 10px;">
                                {{ group.treeHtml }}
                            </div>
                        </div>
                    {% ENDFOR %}
                </div>
            </li>
        </ul>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function() {
    const translations = JSON.parse('{{translationsJson}}');
    let currentCsrfToken = '{{_csrfToken}}';
    
    // 1. Initialiser Nested Sortable
    // On cible toutes les listes .nested-sortable (racines et enfants)
    function initSortables() {
        const nestedSortables = [].slice.call(document.querySelectorAll('.nested-sortable'));
        for (let i = 0; i < nestedSortables.length; i++) {
            new Sortable(nestedSortables[i], {
                group: 'nested', // Permet le drag entre listes (si besoin) ou 'shared'
                handle: '.item-content', // Toute la barre est draggable sinon on peut limiter au .drag-handle
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                    // Sauvegarder uniquement si l'item a bougé
                    if (evt.newIndex !== evt.oldIndex || evt.to !== evt.from) {
                        saveOrder();
                    }
                }
            });
        }
    }
    
    initSortables();

    // 2. Fonction récursive pour extraire l'ordre
    function getStructure(container) {
        const items = [];
        // Trouver les <li> directs de ce container (.nested-sortable)
        const lis = Array.from(container.children).filter(el => el.tagName === 'LI');
        
        lis.forEach((li, index) => {
            const id = li.getAttribute('data-id');
            const type = li.getAttribute('data-type');
            
            // Chercher le conteneur d'enfants
            const childContainer = li.querySelector('.children-container .nested-sortable');
            let children = [];
            if (childContainer) {
                children = getStructure(childContainer);
            }
            
            items.push({
                id: id,
                type: type,
                position: index + 1,
                children: children
            });
        });
        
        return items;
    }

    function saveOrder() {
        const fullOrder = {};
        
        // Pour chaque contexte racine (Main, Avatar, Footer)
        document.querySelectorAll('.root-list[data-context]').forEach(root => {
            const context = root.getAttribute('data-context');
            // Trouver la première liste UL dans ce contexte
            const ul = root.querySelector('.nested-sortable');
            if (ul) {
                fullOrder[context] = getStructure(ul);
            }
        });

        const hint = document.querySelector('.sortable-hint');
        const originalText = hint.innerHTML;
        hint.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + translations.savingOrder;
        hint.style.background = '#e3f2fd';
        
        const formData = new FormData();
        formData.append('fullOrder', JSON.stringify(fullOrder));
        formData.append('_csrf', currentCsrfToken);

        const url = '{{ROUTER.generate("page-admin-save-order")}}';
        console.log('Saving order to:', url);

        fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.csrfToken) currentCsrfToken = data.csrfToken;
                hint.innerHTML = '<i class="fa-solid fa-check-circle"></i> ' + translations.orderSaved;
                hint.style.background = '#d4edda';
                setTimeout(() => {
                    hint.innerHTML = originalText;
                    hint.style.background = '#e3f2fd';
                }, 2000);
            } else {
                 hint.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + data.error;
                 hint.style.background = '#ffcdd2';
            }
        })
        .catch(err => {
            console.error('Error saving order:', err);
            hint.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Erreur réseau';
            hint.style.background = '#ffcdd2';
        });
    }

    // 3. Gestion de l'Accessibilité (Reste globalement inchangé, mais mise à jour UI tri si besoin)
    // ... (Le code précédent de gestion des checks accessibilité reste valide pour les onglets 1 et 2)
    // On garde le code existant s'il est hors de la zone remplacée, 
    // Sinon on doit le remettre. 
    // ATTENTION: Le remplacment coupe à la ligne 375, donc on assume que le reste du script (updateVisibilityFlags) 
    // était après ou doit être conservé.
    // D'après le `view_file` précédent, le script se termine plus bas.
    // Je vais réinclure le bloc accessibilité pour être sûr.
    
    const accessibilityContainer = document.getElementById('accessibility-tables-container');
    if (accessibilityContainer) {
        accessibilityContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('visibility-flag')) {
                const checkbox = e.target;
                const row = checkbox.closest('tr');
                const id = checkbox.getAttribute('data-id');
                const type = checkbox.getAttribute('data-type');
                const flag = checkbox.getAttribute('data-flag');
                const value = checkbox.checked ? 1 : 0;
                
                const formData = new FormData();
                formData.append('id', id);
                formData.append(flag, value);
                formData.append('_csrf', currentCsrfToken);

                const url = '{{ROUTER.generate("page-admin-update-visibility-flags")}}';
                console.log('Sending visibility update to:', url);
                console.log('Data:', Object.fromEntries(formData));

                fetch(url, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.csrfToken) currentCsrfToken = data.csrfToken;
                        
                        // Sync flags
                        Object.keys(data.flags).forEach(f => {
                            const cb = row.querySelector(`[data-flag="${f}"]`);
                            if (cb) cb.checked = data.flags[f];
                        });

                        // Gérer mouvement Actif/Inactif
                        if (data.isHidden) {
                            const inactiveBody = document.getElementById('tbody-inactive');
                            if(inactiveBody) inactiveBody.appendChild(row);
                        } else {
                            const activeBody = document.getElementById('tbody-active');
                            if(activeBody) activeBody.appendChild(row);
                        }
                        
                        // NOTE: Mettre à jour l'arbre de tri dynamiquement est complexe (structure récursive).
                        // Le plus simple est de recharger la page, ou d'ignorer pour l'instant.
                        // L'utilisateur rafraichira pour voir les changements dans l'onglet Tri.
                    }
                });
            }
        });
    }

    // CSS Switch Style (remis car supprimé par le replace)
    const style = document.createElement('style');
    style.textContent = `
        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; }
        input:checked + .slider { background-color: #28a745; }
        input:checked + .slider:before { transform: translateX(20px); }
        .slider.round { border-radius: 20px; }
        .slider.round:before { border-radius: 50%; }
        .item-content { transition: background 0.2s; }
    `;
    document.head.appendChild(style);

})();
</script>

