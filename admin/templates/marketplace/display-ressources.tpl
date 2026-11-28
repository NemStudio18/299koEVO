{% if showToolbar %}
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
        <button type="button" class="market-cat-pill market-cat-official" data-cat="official"><i class="fa-solid fa-shield-halved"></i> <span>Officiels</span> <span class="pill-count"></span></button>
        <button type="button" class="market-cat-pill market-cat-community" data-cat="community"><i class="fa-solid fa-users"></i> <span>Communauté</span> <span class="pill-count"></span></button>
        <button type="button" class="market-cat-pill market-cat-updates" data-cat="updates"><i class="fa-solid fa-rotate"></i> <span>Mises à jour</span> <span class="pill-count"></span></button>
        <button type="button" class="market-cat-pill market-cat-exclusive" data-cat="exclusive" style="display:none;"><i class="fa-solid fa-crown"></i> <span>Mes exclusivités</span> <span class="pill-count"></span></button>
    </div>
</div>
{% endif %}

<div class="market-list">
    {% if ressources %}
        {% for ressource in ressources %}
            <div class="market-card market-item {% if ressource.updateNeeded() %}has-update{% endif %} {% if ressource.isExclusive %}is-exclusive{% endif %} {% if ressource.isCommunity() %}is-community{% else %}is-official{% endif %}"
                 data-slug="{{ressource.slug}}"
                 data-type="{{ressource.type}}"
                 data-category="{{ressource.getCategory()}}"
                 data-name="{{ressource.name}}"
                 data-desc="{{ressource.description}}"
                 data-channel="{% if ressource.isExclusive %}exclusive{% elseif ressource.isCommunity() %}community{% else %}official{% endif %}"
                 data-exclusive="{% if ressource.isExclusive %}1{% else %}0{% endif %}">
                
                <!-- En-tête de carte : Catégorie + Statut d'installation -->
                <div class="market-card-top">
                    <span class="market-cat-badge cat-{{ressource.getCategory()}}">
                        <i class="{{ressource.getCategoryIcon()}}"></i> {{ressource.getCategoryLabel()}}
                    </span>
                    <div class="market-card-badges">
                        {% if ressource.isExclusive %}
                            <span class="market-badge-exclusive" title="{{ressource.exclusiveLabel|default('Exclusivité privée associée à votre jeton')}}">
                                <i class="fa-solid fa-crown"></i> Exclusif
                            </span>
                        {% elseif ressource.isCommunity() %}
                            <span class="market-badge-community" title="Extension issue de la communauté 299ko - Revue par PluginInspector">
                                <i class="fa-solid fa-users"></i> Communauté
                            </span>
                        {% else %}
                            <span class="market-badge-official" title="Extension officielle certifiée 299ko">
                                <i class="fa-solid fa-shield-halved"></i> Officiel
                            </span>
                        {% endif %}
                        {% if ressource.updateNeeded() %}
                            {% if ressource.isSecurityPatch() %}
                                <span class="market-badge-patch" title="Correctif de sécurité disponible">
                                    <i class="fa-solid fa-shield-halved"></i> Correctif
                                </span>
                            {% else %}
                                <span class="market-badge-update" title="Nouvelle version v{{ressource.lastVersion}} disponible">
                                    <i class="fa-solid fa-arrows-rotate"></i> v{{ressource.lastVersion}}
                                </span>
                            {% endif %}
                        {% else %}
                            {% if ressource.isInstalled %}
                                <span class="market-badge-installed" title="Ressource installée">
                                    <i class="fa-solid fa-check"></i> Installé
                                </span>
                            {% endif %}
                        {% endif %}
                    </div>
                </div>

                <!-- Aperçu visuel si disponible (Thèmes ou Plugins avec captures) -->
                {% if ressource.getPreviewUrl() %}
                    <div class="market-card-preview">
                        <a data-fancybox="ress-{{ressource.slug}}" href="{{ressource.getPreviewUrl()}}">
                            <img src="{{ressource.getPreviewUrl()}}" alt="{{ressource.name}}" onerror="this.closest('.market-card-preview').style.display='none'" />
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

                <!-- En-tête principal : Icône carrée stylée + Nom & Auteur -->
                <div class="market-card-header">
                    <div class="market-app-icon icon-{{ressource.getCategory()}}">
                        {% if ressource.hasIconImage() %}
                            <img src="{{ressource.getIconUrl()}}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;" />
                        {% else %}
                            <i class="{{ressource.getIconClass()}}"></i>
                        {% endif %}
                    </div>
                    <div class="market-header-meta">
                        <h3 class="market-item-title">{{ressource.name}}</h3>
                        {% if ressource.authorEmail %}
                            <span class="market-author-text">par <a href="mailto:{{ressource.authorEmail}}">{{ressource.authorEmail}}</a></span>
                        {% endif %}
                    </div>
                </div>

                <!-- Description & Métadonnées -->
                <div class="market-card-body">
                    <p class="market-card-desc">{{ressource.description}}</p>
                    
                    <div class="market-card-tech">
                        <div class="market-tech-item">
                            <i class="fa-solid fa-code-commit"></i>
                            {% if ressource.isInstalled %}
                                {% if ressource.updateNeeded() %}
                                    <span class="version-update-box">
                                        <span class="ver-current">v{{ressource.version}}</span>
                                        <i class="fa-solid fa-arrow-right ver-arrow"></i>
                                        <span class="ver-latest">v{{ressource.lastVersion}}</span>
                                    </span>
                                {% else %}
                                    <span class="ver-pill">v{{ressource.version}}</span>
                                    <span class="badge-uptodate"><i class="fa-solid fa-check"></i> À jour</span>
                                {% endif %}
                            {% else %}
                                <span class="ver-pill">v{{ressource.lastVersion}}</span>
                            {% endif %}
                        </div>

                        {% if ressource.authorWebsite %}
                            <div class="market-tech-item">
                                <i class="fa-solid fa-globe"></i>
                                <a href="{{ressource.authorWebsite}}" target="_blank" rel="noopener" class="tech-link">Site officiel</a>
                            </div>
                        {% endif %}
                    </div>
                </div>

                <!-- Footer d'actions -->
                <div class="market-card-footer">
                    <div class="market-footer-main">
                        {% if ressource.isInstallable %}
                            {% if ressource.isInstalled %}
                                {% if ressource.updateNeeded() %}
                                    <a href='{ ROUTER.generate("marketplace-install-release", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }' class="button success market-btn-update market-btn-block">
                                        <i class="fa-solid fa-cloud-arrow-down"></i> {{Lang.marketplace.update}} (v{{ressource.lastVersion}})
                                    </a>
                                {% else %}
                                    <div class="market-status-installed market-status-block">
                                        <i class="fa-solid fa-circle-check"></i> {{Lang.marketplace.installed}}
                                    </div>
                                {% endif %}
                            {% elseif ressource.getMissingRequiredPlugins() %}
                                <button type="button" disabled class="button market-btn-disabled market-btn-block" style="background:#f59e0b;color:#1e293b;border-color:#d97706;cursor:not-allowed;font-weight:600;" title="Nécessite l'installation préalable de : {{ressource.getMissingRequiredPluginsString()}}">
                                    <i class="fa-solid fa-lock"></i> Requis : {{ressource.getMissingRequiredPluginsString()}}
                                </button>
                            {% else %}
                                {% if ressource.isCommunity() %}
                                    <a href='{ ROUTER.generate("marketplace-install-release", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }' 
                                       class="button market-btn-install market-btn-block market-btn-community-install"
                                       data-community="1"
                                       data-name="{{ressource.name}}"
                                       data-type="{{ressource.type}}"
                                       data-author="{{ressource.authorEmail|default('Contributeur communautaire')}}">
                                        <i class="fa-solid fa-download"></i> {{Lang.marketplace.install}}
                                    </a>
                                {% else %}
                                    <a href='{ ROUTER.generate("marketplace-install-release", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }' class="button market-btn-install market-btn-block">
                                        <i class="fa-solid fa-download"></i> {{Lang.marketplace.install}}
                                    </a>
                                {% endif %}
                            {% endif %}
                        {% else %}
                            <button disabled class="button market-btn-disabled market-btn-block">{{Lang.marketplace.not-installable}}</button>
                        {% endif %}
                    </div>

                    {% if ressource.isInstalled %}
                        <div class="market-footer-actions">
                            {% if ressource.type == 'plugin' %}
                                <a href="{ ROUTER.generate("marketplace-uninstall-ressource", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }" onclick="if (!confirm('{{Lang.marketplace.uninstall_keep_data_confirm}}')) return false;" class="market-btn-uninstall" title="Désinstaller le code en conservant les données">
                                    <i class="fa-solid fa-power-off"></i> {{Lang.marketplace.uninstall_keep_data}}
                                </a>
                                <a href="{ ROUTER.generate("marketplace-uninstall-ressource", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }?purge=1" onclick="if (!confirm('{{Lang.marketplace.uninstall_purge_data_confirm}}')) return false;" class="market-btn-purge" title="Supprimer définitivement le code et les données">
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

{% if showToolbar %}
<div class="market-empty-state" style="display:none;">
    <div class="market-empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
    <h3>Aucun résultat trouvé</h3>
    <p>Aucune ressource ne correspond à votre recherche ou catégorie sélectionnée.</p>
    <button type="button" class="button small market-reset-btn"><i class="fa-solid fa-rotate-left"></i> Réinitialiser les filtres</button>
</div>
{% endif %}

<!-- Modale d'avertissement transparent d'extension communautaire -->
<div id="marketCommunityModal" class="market-modal-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="commModalTitle">
    <div class="market-modal-box">
        <div class="market-modal-icon">
            <i class="fa-solid fa-users"></i>
        </div>
        <h3 id="commModalTitle" class="market-modal-title">Extension de la Communauté</h3>
        <p class="market-modal-desc">
            Vous vous apprêtez à installer <strong id="commModalName">l'extension</strong> (<span id="commModalType">module</span>), développée par <strong id="commModalAuthor">un tiers</strong>.
        </p>
        <div class="market-modal-notice">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Cette extension a été analysée avec succès par <strong>PluginInspector</strong> et respecte les normes d'intégrité de 299ko EVO.</span>
        </div>
        <div class="market-modal-actions">
            <button type="button" id="commModalCancel" class="button">Annuler</button>
            <a href="#" id="commModalConfirm" class="button primary">
                <i class="fa-solid fa-download"></i> Confirmer l'installation
            </a>
        </div>
    </div>
</div>
