<section>
    <a href="{{ pluginsPageUrl }}" class="button">
        <i title="Plugins" class="fa-solid fa-puzzle-piece"></i> {{ Lang.marketplace.plugins }}
    </a>
    <a href="{{ themesPageUrl }}" class="button">
        <i class="fa-solid fa-panorama"></i> {{ Lang.marketplace.themes }}
    </a>
</section>
<div class="home-list">
    <section>
        <header>
            <h2>{{ Lang.marketplace.featured_plugins }}</h2>
        </header>
        {{ PLUGINS_TPL }}
        {% if havePlugins %}
            <p>
                <a href="{{ pluginsPageUrl }}">
                    {{ Lang.marketplace.view_all_plugins }}
                </a>
            </p>
        {% else %}
            <p>{{ Lang.marketplace.no_plugins }}</p>
        {% endif %}
    </section>

    <section>
        <header>
            <h2>{{ Lang.marketplace.featured_themes }}</h2>
        </header>
        {{ THEMES_TPL }}
        {% if haveThemes %}
            <p>
                <a href="{{ themesPageUrl }}">
                    {{ Lang.marketplace.view_all_themes }}
                </a>
            </p>
        {% else %}
            <p>{{ Lang.marketplace.no_themes }}</p>
        {% endif %}
    </section>
</div>