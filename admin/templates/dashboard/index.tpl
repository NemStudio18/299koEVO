<div class="adm-dash-wrap">
    <section class="adm-dash-hero">
        <div>
            <span class="adm-dash-kicker">Administration • {{siteName|default('Déclic')}}</span>
            <h1 class="adm-dash-title">Tableau de bord général</h1>
            <p class="adm-dash-sub">Vue d'ensemble du site, raccourcis éditoriaux et suivi des modules actifs.</p>
        </div>
        <div class="adm-dash-quick">
            {% for ql in quickLinks %}
                <a href="{{ql.url}}" class="adm-quick-btn">
                    <i class="{{ql.icon}}"></i>
                    <span>{{ql.label}}</span>
                </a>
            {% endfor %}
        </div>
    </section>

    <div class="adm-kpi-grid">
        <a href="/admin/page" class="adm-kpi-card">
            <div class="kpi-icon kpi-blue"><i class="fa-regular fa-file-lines"></i></div>
            <div class="kpi-body">
                <span class="kpi-label">Pages CMS</span>
                <strong class="kpi-val">{{stats.publishedPages|default(0)}}</strong>
                <span class="kpi-sub">{{stats.draftPages|default(0)}} brouillon(s)</span>
            </div>
        </a>

        <a href="/admin/menus" class="adm-kpi-card">
            <div class="kpi-icon kpi-indigo"><i class="fa-solid fa-bars-staggered"></i></div>
            <div class="kpi-body">
                <span class="kpi-label">Éléments de menu</span>
                <strong class="kpi-val">{{stats.totalMenuItems|default(0)}}</strong>
                <span class="kpi-sub">Principal · Avatar · Footer</span>
            </div>
        </a>

        <a href="/admin/users" class="adm-kpi-card">
            <div class="kpi-icon kpi-green"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-body">
                <span class="kpi-label">Utilisateurs</span>
                <strong class="kpi-val">{{stats.usersCount|default(0)}}</strong>
                <span class="kpi-sub">Comptes enregistrés</span>
            </div>
        </a>

        <a href="/admin/pluginsmanager" class="adm-kpi-card">
            <div class="kpi-icon kpi-purple"><i class="fa-solid fa-plug"></i></div>
            <div class="kpi-body">
                <span class="kpi-label">Plugins actifs</span>
                <strong class="kpi-val">{{stats.activePlugins|default(0)}} / {{stats.totalPlugins|default(0)}}</strong>
                <span class="kpi-sub">Modules installés</span>
            </div>
        </a>

        {% for card in pluginCards %}
        <a href="{{card.url}}" class="adm-kpi-card">
            <div class="kpi-icon kpi-{{card.accent|default('blue')}}"><i class="{{card.icon}}"></i></div>
            <div class="kpi-body">
                <span class="kpi-label">{{card.title}}</span>
                <strong class="kpi-val">{{card.value}}</strong>
                <span class="kpi-sub">{{card.sub}}</span>
            </div>
        </a>
        {% endfor %}
    </div>

    {% if extraWidgets %}
    <div class="adm-dash-widgets">
        {% for w in extraWidgets %}
            <div class="adm-dash-widget-box">{{w}}</div>
        {% endfor %}
    </div>
    {% endif %}

    <div class="adm-dash-meta-bar">
        <span><i class="fa-solid fa-code-branch"></i> 299koEVO v{{cmsVersion}}</span>
        <span><i class="fa-brands fa-php"></i> PHP {{phpVersion}}</span>
        <span><i class="fa-solid fa-database"></i> Stockage : {{storageDriver}}</span>
    </div>
</div>

