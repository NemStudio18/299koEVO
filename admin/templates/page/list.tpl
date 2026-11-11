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
                    <th class="pages-col-actions">Actions</th>
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
                    <td class="pages-cell-actions">
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
