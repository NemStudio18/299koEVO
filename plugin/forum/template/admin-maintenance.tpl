<section class="module-card forum-admin-module">
    <header><i class="fa-solid fa-screwdriver-wrench"></i> Maintenance du Forum</header>

    <div class="forum-adm-toolbar">
        <p>Détecter et réparer les éléments orphelins (sous-catégories, sujets, messages sans parent valide).</p>
        <div class="forum-adm-toolbar-actions">
            <a class="button" href="/admin/forum"><i class="fa-solid fa-arrow-left"></i> {{Lang.forum.dashboard}}</a>
        </div>
    </div>

    <div class="forum-adm-kpi-grid">
        <div class="forum-adm-kpi-card {% IF orphanSubcatsCount > 0 %}is-warning{% ELSE %}is-success{% ENDIF %}">
            <span class="forum-adm-kpi-label"><i class="fa-solid fa-folder-open"></i> Sous-catégories orphelines</span>
            <span class="forum-adm-kpi-value">{{orphanSubcatsCount}}</span>
        </div>
        <div class="forum-adm-kpi-card {% IF orphanTopicsCount > 0 %}is-warning{% ELSE %}is-success{% ENDIF %}">
            <span class="forum-adm-kpi-label"><i class="fa-solid fa-comments"></i> Sujets orphelins</span>
            <span class="forum-adm-kpi-value">{{orphanTopicsCount}}</span>
        </div>
        <div class="forum-adm-kpi-card {% IF orphanPostsCount > 0 %}is-warning{% ELSE %}is-success{% ENDIF %}">
            <span class="forum-adm-kpi-label"><i class="fa-solid fa-message"></i> Messages orphelins</span>
            <span class="forum-adm-kpi-value">{{orphanPostsCount}}</span>
        </div>
    </div>

    {% IF orphanSubcatsCount > 0 || orphanTopicsCount > 0 || orphanPostsCount > 0 %}
    <div class="forum-adm-center-actions">
        <form action="{{actionUrl}}" method="POST">
            <input type="hidden" name="_csrf" value="{{csrfToken}}">
            <button type="submit" class="button alert">
                <i class="fa-solid fa-recycle"></i> Récupérer les éléments orphelins
            </button>
            <p><small>Les éléments seront déplacés dans une catégorie « Corbeille / Récupération ».</small></p>
        </form>
    </div>
    {% ELSE %}
    <div class="evo-notice evo-notice-success">
        <i class="fa-solid fa-check-circle"></i> Tout est en ordre ! Aucun élément orphelin détecté.
    </div>
    {% ENDIF %}
</section>
