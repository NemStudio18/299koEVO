<section class="module-card">
    <header>
        {{ Lang.marketplace.description }}
        <a href="{{ refreshCacheUrl }}" class="button small" style="float: right; margin-top: -5px;" title="{{ Lang.marketplace.refresh_cache }}">
            <i class="fa-solid fa-rotate"></i> {{ Lang.marketplace.refresh_cache }}
        </a>
    </header>
    <div class="tabs-container">
        <ul class="tabs-header">
            <li class="default-tab"><i class="fa-solid fa-chart-pie"></i> {{ Lang.marketplace.tab-overview }}</li>
            <li><i class="fa-solid fa-puzzle-piece"></i> {{ Lang.marketplace.plugins }}</li>
            <li><i class="fa-solid fa-panorama"></i> {{ Lang.marketplace.themes }}</li>
        </ul>
        <ul class="tabs">
            <li class="tab">
                <div class="module-grid">
                    <div class="stat-card">
                        <span class="stat-value">{{ stats.plugins_total }}</span>
                        <span class="stat-label">{{ Lang.marketplace.stats-plugins-total }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ stats.plugins_installed }}</span>
                        <span class="stat-label">{{ Lang.marketplace.stats-plugins-installed }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ stats.plugins_updates }}</span>
                        <span class="stat-label">{{ Lang.marketplace.stats-plugins-updates }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ stats.themes_total }}</span>
                        <span class="stat-label">{{ Lang.marketplace.stats-themes-total }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ stats.themes_installed }}</span>
                        <span class="stat-label">{{ Lang.marketplace.stats-themes-installed }}</span>
                    </div>
                </div>
                {% if pendingLegacyCount > 0 %}
                <div class="info-message legacy-message">
                    <p>
                        <i class="fa-solid fa-plug-circle-exclamation"></i>
                        {{ Lang.marketplace.legacy_plugins_pending }} :
                        <strong>{{ pendingLegacyList }}</strong>
                    </p>
                    <form method="post" action="{{ legacyMigrationUrl }}">
                        <input type="hidden" name="_csrf" value="{{ _csrfToken }}">
                        <button type="submit" class="button success">
                            <i class="fa-solid fa-rotate"></i> {{ Lang.marketplace.legacy_plugins_button }}
                        </button>
                    </form>
                </div>
                {% endif %}
                
                <div class="home-list" style="margin-top: 30px;">
                    <section>
                        <header>
                            <h2>{{ Lang.marketplace.featured_plugins }}</h2>
                        </header>
                        {{ FEATURED_PLUGINS_TPL }}
                    </section>

                    <section>
                        <header>
                            <h2>{{ Lang.marketplace.featured_themes }}</h2>
                        </header>
                        {{ FEATURED_THEMES_TPL }}
                    </section>
                </div>
            </li>
            <li class="tab">
                <div class="home-list">
                    {{ ALL_PLUGINS_TPL }}
                </div>
            </li>
            <li class="tab">
                <div class="home-list">
                    {{ ALL_THEMES_TPL }}
                </div>
            </li>
        </ul>
    </div>
</section>
