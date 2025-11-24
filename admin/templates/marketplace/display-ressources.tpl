<div class="market-list">
    {% if ressources %}
        {% for ressource in ressources %}
            <section class="market-item">
                <header>
                    <h2>
                        {% if ressource.getPreviewUrl() %}
                            <a data-fancybox="ress-{{ ressource.slug }}" href="{{ ressource.getPreviewUrl() }}">
                                <img src="{{ ressource.getPreviewUrl() }}" />
                            </a>
                            {% if ressource.getOthersPreviewsUrl() %}
                                <div class="market-others-previews">
                                    {% for url in ressource.getOthersPreviewsUrl() %}
                                        <a data-fancybox="ress-{{ ressource.slug }}" href="{{ url }}">
                                            <img src="{{ url }}" />
                                        </a>
                                    {% endfor %}
                                </div>
                            {% endif %}
                            
                        {% endif %}
                        {% if ressource.icon %}
                            <i class="{{ ressource.icon }}"></i>
                        {% endif %}
                        {{ ressource.name }}
                    </h2>
                </header>
                <div>
                <div>
                    <strong>{{ Lang.marketplace.list_desc }} :</strong> {{ ressource.description }}
                </div>
                <div>
                    <strong>{{ Lang.marketplace.version }} :</strong> {{ ressource.version }}
                </div>
                {% if ressource.authorEmail %}
                    <div>
                        <strong>{{ Lang.marketplace.author }} :</strong> <a href="mailto:{{ ressource.authorEmail }}">{{ ressource.authorEmail }}</a>
                    </div>
                {% endif %}
                {% if ressource.authorWebsite %}
                    <div>
                        <strong>{{ Lang.marketplace.website }} :</strong>
                        <a href="{{ ressource.authorWebsite }}" target="_blank">
                            {{ ressource.authorWebsite }}
                        </a>
                    </div>
                {% endif %}
                </div>
                <footer>
                    {% if ressource.isInstallable %}
                    {% if ressource.isInstalled %}
                        {% if ressource.updateNeeded() %}
                            <a href='{{ ROUTER.generate("marketplace-install-release", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }}' class="button market-update">
                                {{ Lang.marketplace.update }}
                            </a>
                        {% else %}
                            <span class="up-to-date-icon" title="ressource à jour / ressource is up-to-date">&#x2714;</span>
                        {% endif %}
                        <a href="{{ ROUTER.generate("marketplace-uninstall-ressource", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }}" onclick="if (!confirm('{{ Lang.marketplace.uninstall_confirm }}')) return false;" class="button alert">{{ Lang.delete }}</a>
                    {% else %}
                        <a href='{{ ROUTER.generate("marketplace-install-release", ["type" => ressource.type, "slug" => ressource.slug, "token" => token]) }}' class="button market-install">
                            {{ Lang.marketplace.install }}
                        </a>
                    {% endif %}
                    {% else %}
                        <button disabled class="button market-install">{{ Lang.marketplace.not-installable }}</button>
                    {% endif %}
                </footer>
            </section>
        {% endfor %}
    {% endif %}
</div>