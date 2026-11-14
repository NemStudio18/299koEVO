<div class="market-toolbar" data-market-toolbar>
    <div class="market-search-wrapper">
        <div class="market-search-box">
            <i class="fa-solid fa-magnifying-glass market-search-icon"></i>
            <input type="text" class="market-search-input" placeholder="Rechercher par mot-clé, nom, description..." autocomplete="off">
            <button type="button" class="market-search-clear" title="Effacer la recherche" style="display:none;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="market-counter-bar">
            <span class="market-counter-text"><strong class="market-count-num">{{ ressourcesCount }}</strong> ressource(s)</span>
        </div>
    </div>
    
    <div class="market-categories-bar">
        <button type="button" class="market-cat-pill active" data-cat="all"><i class="fa-solid fa-layer-group"></i> <span>Tous</span> <span class="pill-count"></span></button>
        <button type="button" class="market-cat-pill" data-cat="content"><i class="fa-solid fa-feather-pointed"></i> <span>Contenu</span> <span class="pill-count"></span></button>
        <button type="button" class="market-cat-pill" data-cat="commerce"><i class="fa-solid fa-credit-card"></i> <span>Commerce & Dons</span> <span class="pill-count"></span></button>
        <button type="button" class="market-cat-pill" data-cat="events"><i class="fa-solid fa-calendar-days"></i> <span>Événements & RDV</span> <span class="pill-count"></span></button>
        <button type="button" class="market-cat-pill" data-cat="social"><i class="fa-solid fa-comments"></i> <span>Social & Contact</span> <span class="pill-count"></span></button>
        <button type="button" class="market-cat-pill" data-cat="media"><i class="fa-solid fa-photo-film"></i> <span>Médias & Outils</span> <span class="pill-count"></span></button>
        <button type="button" class="market-cat-pill" data-cat="system"><i class="fa-solid fa-shield-halved"></i> <span>Système</span> <span class="pill-count"></span></button>
        <button type="button" class="market-cat-pill market-cat-updates" data-cat="updates"><i class="fa-solid fa-rotate"></i> <span>Mises à jour</span> <span class="pill-count"></span></button>
    </div>
</div>

<div class="market-list">
    {% if ressources %}
        {% for ressource in ressources %}
            <div class="market-card market-item {% if ressource.updateNeeded() %}has-update{% endif %}"
                 data-slug="{{ressource.slug}}"
                 data-type="{{ressource.type}}"
                 data-category="{{ressource.getCategory()}}"
                 data-name="{{ressource.name}}"
                 data-desc="{{ressource.description}}">
                
                <div class="market-card-top">
                    <span class="market-cat-badge cat-{{ressource.getCategory()}}">
                        <i class="{{ressource.getCategoryIcon()}}"></i> {{ressource.getCategoryLabel()}}
                    </span>
                    {% if ressource.updateNeeded() %}
                        {% if ressource.isSecurityPatch() %}
                            <span class="market-badge-patch" title="Correctif / Patch de sécurité disponible">
                                <i class="fa-solid fa-shield-halved"></i> Correctif
                            </span>
                        {% else %}
                            <span class="market-badge-update" title="Nouvelle version v{{ressource.lastVersion}} disponible">
                                <i class="fa-solid fa-arrows-rotate"></i> v{{ressource.lastVersion}}
                            </span>
                        {% endif %}
                    {% endif %}
                </div>

                {% if ressource.getPreviewUrl() %}
                    <div class="market-card-preview">
                        <a data-fancybox="ress-{{ressource.slug}}" href="{{ressource.getPreviewUrl()}}">
                            <img src="{{ressource.getPreviewUrl()}}" alt="{{ressource.name}}" />
                        </a>
                        {% if ressource.getOthersPreviewsUrl() %}
                            <div class="market-others-previews">
                                {% for url in ressource.getOthersPreviewsUrl() %}
                                    <a data-fancybox="ress-{{ressource.slug}}" href="{{url}}">
                                        <img src="{{url}}" />
                                    </a>
                                {% endfor %}
                            </div>
                        {% endif %}
                    </div>
                {% endif %}

                <div class="market-card-title">
                    {% if ressource.icon %}
                        <i class="{{ressource.icon}} market-title-icon"></i>
                    {% endif %}
                    <h3 class="market-item-title">{{ressource.name}}</h3>
                </div>

                <div class="market-card-body">
                    <p class="market-card-desc">{{ressource.description}}</p>
                    
                    <div class="market-card-meta">
                        <div class="market-meta-row">
                            <i class="fa-solid fa-code-commit"></i>
                            <span class="meta-label">{{Lang.marketplace.version}} :</span>
                            {% if ressource.isInstalled %}
                                {% if ressource.updateNeeded() %}
                                    <span class="version-update-box">
                                        <span class="ver-current">v{{ressource.version}}</span>
                                        <i class="fa-solid fa-arrow-right ver-arrow"></i>
                                        <span class="ver-latest">v{{ressource.lastVersion}}</span>
                                    </span>
                                {% else %}
                                    <span class="ver-normal">v{{ressource.version}}</span>
                                    <span class="badge-uptodate"><i class="fa-solid fa-check"></i> À jour</span>
                                {% endif %}
                            {% else %}
                                <span class="ver-available">v{{ressource.lastVersion}}</span>
                            {% endif %}
                        </div>

                        {% if ressource.authorEmail %}
                            <div class="market-meta-row">
                                <i class="fa-solid fa-user-pen"></i>
                                <span class="meta-label">{{Lang.marketplace.author}} :</span>
                                <a href="mailto:{{ressource.authorEmail}}" class="meta-link">{{ressource.authorEmail}}</a>
                            </div>
                        {% endif %}

                        {% if ressource.authorWebsite %}
                            <div class="market-meta-row">
                                <i class="fa-solid fa-globe"></i>
                                <span class="meta-label">{{Lang.marketplace.website}} :</span>
                                <a href="{{ressource.authorWebsite}}" target="_blank" rel="noopener" class="meta-link">{{ressource.authorWebsite}}</a>
                            </div>
                        {% endif %}
                    </div>
                </div>

                <div class="market-card-footer">
                    <div class="market-footer-primary">
                        {% if ressource.isInstallable %}
                            {% if ressource.isInstalled %}
                                {% if ressource.updateNeeded() %}
                                    <a href='{ ROUTER.generate("marketplace-install-release", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }' class="button success market-btn-update">
                                        <i class="fa-solid fa-cloud-arrow-down"></i> {{Lang.marketplace.update}} (v{{ressource.lastVersion}})
                                    </a>
                                {% else %}
                                    <span class="market-status-installed" title="Ressource installée et à jour">
                                        <i class="fa-solid fa-circle-check"></i> {{Lang.marketplace.installed}}
                                    </span>
                                {% endif %}
                            {% else %}
                                <a href='{ ROUTER.generate("marketplace-install-release", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }' class="button market-btn-install">
                                    <i class="fa-solid fa-download"></i> {{Lang.marketplace.install}}
                                </a>
                            {% endif %}
                        {% else %}
                            <button disabled class="button market-btn-disabled">{{Lang.marketplace.not-installable}}</button>
                        {% endif %}
                    </div>

                    {% if ressource.isInstalled %}
                        <div class="market-footer-actions">
                            {% if ressource.type == 'plugin' %}
                                <a href="{ ROUTER.generate("marketplace-uninstall-ressource", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }" onclick="if (!confirm('{{Lang.marketplace.uninstall_keep_data_confirm}}')) return false;" class="market-btn-uninstall" title="Désinstaller le code en conservant les données">
                                    <i class="fa-solid fa-power-off"></i> {{Lang.marketplace.uninstall_keep_data}}
                                </a>
                                <a href="{ ROUTER.generate("marketplace-uninstall-ressource", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }?purge=1" onclick="if (!confirm('{{Lang.marketplace.uninstall_purge_data_confirm}}')) return false;" class="market-btn-purge" title="Supprimer le code et purger définitivement les données">
                                    <i class="fa-solid fa-trash-can"></i> {{Lang.marketplace.uninstall_purge_data}}
                                </a>
                            {% else %}
                                <a href="{ ROUTER.generate("marketplace-uninstall-ressource", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }" onclick="if (!confirm('{{Lang.marketplace.uninstall_confirm}}')) return false;" class="market-btn-purge" title="Supprimer le thème">
                                    <i class="fa-solid fa-trash-can"></i> {{Lang.delete}}
                                </a>
                            {% endif %}
                        </div>
                    {% endif %}
                </div>
            </div>
        {% endfor %}
    {% endif %}
</div>

<div class="market-empty-state" style="display:none;">
    <div class="market-empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
    <h3>Aucun résultat trouvé</h3>
    <p>Aucune ressource ne correspond à votre recherche ou catégorie sélectionnée.</p>
    <button type="button" class="button small market-reset-btn"><i class="fa-solid fa-rotate-left"></i> Réinitialiser les filtres</button>
</div>
