<section class="module-card pages-editorial-wrap">
    <header class="pages-editorial-header">
        <div>
            <h1 class="pages-editorial-title"><i class="fa-regular fa-file-lines"></i> Pages CMS</h1>
            <p class="pages-editorial-sub">Gérez le contenu, le SEO et le statut de vos pages.</p>
        </div>
        <div class="pages-editorial-actions">
            <a href="/admin/menus" class="button secondary">
                <i class="fa-solid fa-bars-staggered"></i> Gérer les Menus
            </a>
            <a href="{{ROUTER.generate("page-admin-new")}}" class="button success">
                <i class="fa-solid fa-square-plus"></i> Nouvelle page
            </a>
        </div>
    </header>

    <div class="pages-info-banner">
        <i class="fa-solid fa-circle-info"></i>
        <span>La disposition du menu principal, du menu avatar et du pied de page se configure désormais séparément dans <a href="/admin/menus"><strong>Menus</strong></a>.</span>
    </div>

    <div class="pages-stats-grid">
        <div class="pages-stat-card stat-published">
            <span class="stat-val">{{editorialStats.publishedPages|default(0)}}</span>
            <span class="stat-lbl">Pages publiées</span>
        </div>
        <div class="pages-stat-card stat-draft">
            <span class="stat-val">{{editorialStats.draftPages|default(0)}}</span>
            <span class="stat-lbl">Brouillons</span>
        </div>
        <div class="pages-stat-card stat-total">
            <span class="stat-val">{{editorialStats.totalPages|default(0)}}</span>
            <span class="stat-lbl">Total pages CMS</span>
        </div>
        <div class="pages-stat-card stat-protected">
            <span class="stat-val">{{editorialStats.protectedPages|default(0)}}</span>
            <span class="stat-lbl">Protégées / NoIndex</span>
        </div>
    </div>

    <div class="table-responsive">
        {% if cmsPages %}
        <table class="pages-editorial-table">
            <thead>
                <tr>
                    <th>Titre de la page</th>
                    <th>Adresse (URL)</th>
                    <th>Statut</th>
                    <th>Présence dans les menus</th>
                    <th style="width: 150px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                {% for p in cmsPages %}
                <tr>
                    <td>
                        <div class="page-row-title">
                            <strong>{{p.name}}</strong>
                            {% if p.isHomepage %}
                                <span class="page-pill pill-home"><i class="fa-solid fa-house"></i> Accueil</span>
                            {% endif %}
                            {% if p.hasPassword %}
                                <span class="page-pill pill-lock"><i class="fa-solid fa-lock"></i> Protégée</span>
                            {% endif %}
                            {% if p.noIndex %}
                                <span class="page-pill pill-noindex">NoIndex</span>
                            {% endif %}
                        </div>
                        {% if p.mainTitle %}
                            <small class="page-row-sub">{{p.mainTitle}}</small>
                        {% endif %}
                    </td>
                    <td>
                        <code class="page-url-code">{{p.url}}</code>
                    </td>
                    <td>
                        {% if p.isHidden %}
                            <span class="page-pill pill-draft">Brouillon</span>
                        {% else %}
                            <span class="page-pill pill-pub">Publiée</span>
                        {% endif %}
                    </td>
                    <td>
                        {% if p.menuLocations %}
                            {% for loc in p.menuLocations %}
                                <span class="page-pill pill-menu">{{loc}}</span>
                            {% endfor %}
                        {% else %}
                            <span class="page-muted">Non placée</span>
                        {% endif %}
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a class="button tiny" href="{{ROUTER.generate("page-admin-edit", ["id" => p.id])}}" title="Modifier le contenu">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        {% if not p.isHidden %}
                        <a class="button tiny secondary" href="{{p.url}}" target="_blank" title="Voir la page">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                        {% endif %}
                        {% if not p.isHomepage %}
                        <a class="button tiny alert"
                           href="{{ROUTER.generate("page-admin-delete", ["id" => p.id, "token" => token])}}"
                           onclick="return confirm('Supprimer définitivement cette page et ses liens de menu associés ?')"
                           title="Supprimer">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                        {% endif %}
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
        {% else %}
        <div class="pages-empty">
            <p>Aucune page CMS créée pour le moment.</p>
            <a href="{{ROUTER.generate("page-admin-new")}}" class="button success">Créer la première page</a>
        </div>
        {% endif %}
    </div>
</section>

<style>
.pages-editorial-wrap {
    max-width: 1280px;
    margin: 0 auto;
}
.pages-editorial-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.pages-editorial-title {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
}
.pages-editorial-sub {
    margin: 4px 0 0;
    color: #64748b;
    font-size: 0.9rem;
}
.pages-editorial-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.pages-info-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e3a8a;
    padding: 10px 14px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 0.9rem;
}
.pages-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}
.pages-stat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #1f62a9;
    border-radius: 10px;
    padding: 14px 16px;
}
.pages-stat-card.stat-published { border-left-color: #16a34a; }
.pages-stat-card.stat-draft { border-left-color: #d97706; }
.pages-stat-card.stat-protected { border-left-color: #7c3aed; }
.pages-stat-card .stat-val {
    display: block;
    font-size: 1.7rem;
    font-weight: 800;
    color: #0f172a;
}
.pages-stat-card .stat-lbl {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 600;
}
.pages-editorial-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}
.pages-editorial-table th,
.pages-editorial-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}
.page-row-title {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.page-row-sub {
    display: block;
    color: #64748b;
    font-size: 0.8rem;
}
.page-url-code {
    font-size: 0.82rem;
    color: #334155;
    background: #f8fafc;
    padding: 2px 6px;
    border-radius: 4px;
}
.page-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
}
.pill-pub { background: #dcfce7; color: #166534; }
.pill-draft { background: #fef3c7; color: #92400e; }
.pill-home { background: #dbeafe; color: #1e40af; }
.pill-lock { background: #f3e8ff; color: #6b21a8; }
.pill-noindex { background: #f1f5f9; color: #475569; }
.pill-menu { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.page-muted { color: #94a3b8; font-size: 0.82rem; }
.pages-empty { text-align: center; padding: 30px; color: #64748b; }
</style>
