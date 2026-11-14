<div class="market-list">
    {% if ressources %}
        {% for ressource in ressources %}
            <section class="market-item {% if ressource.updateNeeded() %}has-update{% endif %}">
                {% if ressource.updateNeeded() %}
                    <div class="market-update-badge-bar">
                        {% if ressource.isSecurityPatch() %}
                            <span class="market-update-pill security">
                                <i class="fa-solid fa-shield-halved"></i> Correctif / Patch disponible
                            </span>
                        {% else %}
                            <span class="market-update-pill">
                                <i class="fa-solid fa-arrows-rotate"></i> Mise à jour disponible : v{{ressource.lastVersion}}
                            </span>
                        {% endif %}
                    </div>
                {% endif %}

                <header>
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
                        {{ressource.name}}
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
                            <a href="{ ROUTER.generate("marketplace-uninstall-ressource", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }" onclick="if (!confirm('{{Lang.marketplace.uninstall_confirm}}')) return false;" class="button alert">{{Lang.delete}}</a>
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
