<section class="forum-admin-maintenance">
    <header class="admin-actions-header">
        <div>
            <h1><i class="fa-solid fa-screwdriver-wrench"></i> Maintenance du Forum</h1>
            <p>Détecter et réparer les éléments orphelins (catégories, sujets, messages sans parents valides).</p>
        </div>
    </header>

    <div class="grid-3 has-gutter" style="margin-top: 20px;">
        <div class="col">
            <div class="panel">
                <div class="panel-body text-center">
                    <h4 class="panel-title">Sous-catégories</h4>
                    <p class="h1 {% IF orphanSubcatsCount > 0 %}color-primary{% ELSE %}color-success{% ENDIF %}">{{ orphanSubcatsCount }}</p>
                    <p class="color-muted">Orphelines</p>
                </div>
            </div>
        </div>
        <div class="col">
             <div class="panel">
                <div class="panel-body text-center">
                    <h4 class="panel-title">Sujets</h4>
                    <p class="h1 {% IF orphanTopicsCount > 0 %}color-primary{% ELSE %}color-success{% ENDIF %}">{{ orphanTopicsCount }}</p>
                    <p class="color-muted">Orphelins</p>
                </div>
            </div>
        </div>
        <div class="col">
             <div class="panel">
                <div class="panel-body text-center">
                    <h4 class="panel-title">Messages</h4>
                    <p class="h1 {% IF orphanPostsCount > 0 %}color-primary{% ELSE %}color-success{% ENDIF %}">{{ orphanPostsCount }}</p>
                    <p class="color-muted">Orphelins</p>
                </div>
            </div>
        </div>
    </div>

    {% IF orphanSubcatsCount > 0 || orphanTopicsCount > 0 || orphanPostsCount > 0 %}
    <div style="margin-top: 40px; text-align: center;">
        <form action="{{ actionUrl }}" method="POST">
            <input type="hidden" name="_csrf" value="{{ csrfToken }}">
            <button type="submit" class="button alert large">
                <i class="fa-solid fa-recycle"></i> Récupérer les éléments orphelins
            </button>
            <p class="color-muted mt-2">
               <small>Les éléments seront déplacés dans une catégorie "Corbeille / Récupération".</small>
            </p>
        </form>
    </div>
    {% ELSE %}
    <div class="msg success" style="margin-top: 40px;">
        <i class="fa-solid fa-check-circle"></i> Tout semble en ordre ! Aucun élément orphelin détecté.
    </div>
    {% ENDIF %}
</section>
