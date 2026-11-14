<div class="market-toolbar" data-market-toolbar>
    <div class="market-search-wrapper">
        <div class="market-search-box">
            <i class="fa-solid fa-magnifying-glass market-search-icon"></i>
            <input type="text" class="market-search-input" placeholder="Rechercher par mot-clé, nom, description..." autocomplete="off">
            <button type="button" class="market-search-clear" title="Effacer la recherche" style="display:none;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="market-counter-bar">
            <span class="market-counter-text"><strong class="market-count-num">{{ ressources|length }}</strong> affiché(s)</span>
        </div>
    </div>
    
    <div class="market-categories-bar">
        <button type="button" class="market-cat-pill active" data-cat="all"><i class="fa-solid fa-layer-group"></i> Tous</button>
        <button type="button" class="market-cat-pill" data-cat="content"><i class="fa-solid fa-feather-pointed"></i> Contenu</button>
        <button type="button" class="market-cat-pill" data-cat="commerce"><i class="fa-solid fa-credit-card"></i> Commerce & Dons</button>
        <button type="button" class="market-cat-pill" data-cat="events"><i class="fa-solid fa-calendar-days"></i> Événements & RDV</button>
        <button type="button" class="market-cat-pill" data-cat="social"><i class="fa-solid fa-comments"></i> Social & Contact</button>
        <button type="button" class="market-cat-pill" data-cat="media"><i class="fa-solid fa-photo-film"></i> Médias & Outils</button>
        <button type="button" class="market-cat-pill" data-cat="system"><i class="fa-solid fa-shield-halved"></i> Système</button>
        <button type="button" class="market-cat-pill market-cat-updates" data-cat="updates"><i class="fa-solid fa-rotate"></i> Mises à jour</button>
    </div>
</div>

<div class="market-list">
    {% if ressources %}
        {% for ressource in ressources %}
            <section class="market-item {% if ressource.updateNeeded() %}has-update{% endif %}"
                     data-slug="{{ressource.slug}}"
                     data-type="{{ressource.type}}"
                     data-category="{{ressource.getCategory()}}"
                     data-name="{{ressource.name}}"
                     data-desc="{{ressource.description}}">
                <header>
                    <div class="market-item-top-row">
                        <span class="market-cat-badge cat-{{ressource.getCategory()}}">
                            <i class="{{ressource.getCategoryIcon()}}"></i> {{ressource.getCategoryLabel()}}
                        </span>
                        {% if ressource.updateNeeded() %}
                            {% if ressource.isSecurityPatch() %}
                                <span class="market-badge-patch" title="Correctif / Patch de sécurité disponible">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    <span>Correctif</span>
                                </span>
                            {% else %}
                                <span class="market-badge-update" title="Nouvelle version v{{ressource.lastVersion}} disponible">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                    <span>v{{ressource.lastVersion}}</span>
                                </span>
                            {% endif %}
                        {% endif %}
                    </div>
                    <h2>
                        {% if ressource.getPreviewUrl() %}
                            <a data-fancybox="ress-{{ressource.slug}}" href="{{ressource.getPreviewUrl()}}">
                                <img src="{{ressource.getPreviewUrl()}}" />
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
                        {% endif %}
                        {% if ressource.icon %}
                            <i class="{{ressource.icon}}"></i>
                        {% endif %}
                        <span class="market-item-title">{{ressource.name}}</span>
                    </h2>
                </header>
                <div>
                    <div class="market-item-desc">
                        <strong>{{Lang.marketplace.list_desc}} :</strong> {{ressource.description}}
                    </div>
                    <div class="market-item-version">
                        <strong>{{Lang.marketplace.version}} :</strong>
                        {% if ressource.isInstalled %}
                            {% if ressource.updateNeeded() %}
                                <span class="version-update-box">
                                    <span class="ver-current" title="Version actuellement installée">v{{ressource.version}}</span>
                                    <i class="fa-solid fa-arrow-right ver-arrow"></i>
                                    <span class="ver-latest" title="Nouvelle version disponible">v{{ressource.lastVersion}}</span>
                                </span>
                            {% else %}
                                <span class="ver-current">v{{ressource.version}}</span>
                                <span class="badge-uptodate"><i class="fa-solid fa-check"></i> À jour</span>
                            {% endif %}
                        {% else %}
                            <span class="ver-available">v{{ressource.lastVersion}}</span>
                        {% endif %}
                    </div>
                    {% if ressource.authorEmail %}
                        <div>
                            <strong>{{Lang.marketplace.author}} :</strong> <a href="mailto:{{ressource.authorEmail}}">{{ressource.authorEmail}}</a>
                        </div>
                    {% endif %}
                    {% if ressource.authorWebsite %}
                        <div>
                            <strong>{{Lang.marketplace.website}} :</strong>
                            <a href="{{ressource.authorWebsite}}" target="_blank" rel="noopener">
                                {{ressource.authorWebsite}}
                            </a>
                        </div>
                    {% endif %}
                </div>
                <footer>
                    {% if ressource.isInstallable %}
                        {% if ressource.isInstalled %}
                            {% if ressource.updateNeeded() %}
                                <a href='{ ROUTER.generate("marketplace-install-release", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }' class="button success market-update-button-highlight">
                                    <i class="fa-solid fa-cloud-arrow-down"></i> {{Lang.marketplace.update}} (v{{ressource.lastVersion}})
                                </a>
                            {% else %}
                                <span class="up-to-date-icon" title="Ressource à jour">&#x2714; À jour</span>
                            {% endif %}
                            {% if ressource.type == 'plugin' %}
                                <div class="market-actions-group">
                                    <a href="{ ROUTER.generate("marketplace-uninstall-ressource", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }" onclick="if (!confirm('{{Lang.marketplace.uninstall_keep_data_confirm}}')) return false;" class="market-btn-uninstall" title="Désinstaller le code en conservant les données">
                                        <i class="fa-solid fa-power-off"></i> {{Lang.marketplace.uninstall_keep_data}}
                                    </a>
                                    <a href="{ ROUTER.generate("marketplace-uninstall-ressource", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }?purge=1" onclick="if (!confirm('{{Lang.marketplace.uninstall_purge_data_confirm}}')) return false;" class="market-btn-purge" title="Supprimer le code et purger définitivement les données">
                                        <i class="fa-solid fa-trash-can"></i> {{Lang.marketplace.uninstall_purge_data}}
                                    </a>
                                </div>
                            {% else %}
                                <a href="{ ROUTER.generate("marketplace-uninstall-ressource", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }" onclick="if (!confirm('{{Lang.marketplace.uninstall_confirm}}')) return false;" class="button alert">{{Lang.delete}}</a>
                            {% endif %}
                        {% else %}
                            <a href='{ ROUTER.generate("marketplace-install-release", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }' class="button market-install">
                                <i class="fa-solid fa-download"></i> {{Lang.marketplace.install}}
                            </a>
                        {% endif %}
                    {% else %}
                        <button disabled class="button market-install">{{Lang.marketplace.not-installable}}</button>
                    {% endif %}
                </footer>
            </section>
        {% endfor %}
    {% endif %}
</div>

<div class="market-empty-state" style="display:none;">
    <div class="market-empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
    <h3>Aucun résultat trouvé</h3>
    <p>Aucune ressource ne correspond à votre recherche ou catégorie sélectionnée.</p>
    <button type="button" class="button small market-reset-btn"><i class="fa-solid fa-rotate-left"></i> Réinitialiser les filtres</button>
</div>
