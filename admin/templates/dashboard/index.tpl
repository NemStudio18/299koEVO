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

<style>
.adm-dash-wrap {
    display: flex;
    flex-direction: column;
    gap: 22px;
}
.adm-dash-hero {
    margin: 0 !important;
    padding: 26px 28px !important;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
    border: 1px solid #e2e8f0;
    border-radius: 16px !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04) !important;
}
.adm-dash-kicker {
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #1f62a9;
    margin-bottom: 4px;
}
.adm-dash-title {
    margin: 0;
    font-size: 1.65rem;
    font-weight: 800;
    color: #0f172a;
    text-transform: none;
}
.adm-dash-sub {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 0.95rem;
}
.adm-dash-quick {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.adm-quick-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 14px;
    border-radius: 10px;
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    font-weight: 700;
    font-size: 0.86rem;
    text-decoration: none;
    transition: all 0.15s ease;
}
.adm-quick-btn:hover {
    background: #1f62a9;
    color: #ffffff;
    border-color: #1f62a9;
}
.adm-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
}
.adm-kpi-card {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px 20px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}
.adm-kpi-card:hover {
    transform: translateY(-2px);
    border-color: #93c5fd;
    box-shadow: 0 8px 20px rgba(31, 98, 169, 0.08);
}
.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.kpi-blue   { background: #eff6ff; color: #1d4ed8; }
.kpi-indigo { background: #eef2ff; color: #4338ca; }
.kpi-green  { background: #f0fdf4; color: #15803d; }
.kpi-purple { background: #faf5ff; color: #7e22ce; }
.kpi-warn   { background: #fef2f2; color: #dc2626; }
.kpi-body {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.kpi-label {
    font-size: 0.82rem;
    color: #64748b;
    font-weight: 600;
}
.kpi-val {
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}
.kpi-sub {
    font-size: 0.78rem;
    color: #94a3b8;
}
.adm-dash-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 16px;
}
.adm-dash-widget-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px;
}
.adm-dash-meta-bar {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    padding: 12px 18px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.84rem;
    color: #64748b;
    font-weight: 600;
}
.adm-dash-meta-bar i {
    color: #1f62a9;
    margin-right: 4px;
}
</style>

