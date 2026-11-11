{% INCLUDE PLUGINS ~ 'forum/template/breadcrumb.tpl' %}

<section class="forum-search">
    <header>
        <h1><i class="fa-solid fa-search"></i> Recherche dans le forum</h1>
        <form method="GET" action="{{searchUrl}}" class="search-form mt-medium form-actions-row">
            <input type="text" name="q" value="{{query}}" placeholder="Rechercher..." required class="form-input flex-1 forum-editor-input">
            <button type="submit" class="button primary">
                <i class="fa-solid fa-search"></i>
            </button>
        </form>
    </header>
    
    {% IF results %}
    <div class="results-container">
        <p class="results-summary">
            <strong>{{resultsCount}}</strong> résultat{% IF resultsCount > 1 %}s{% ENDIF %} trouvé{% IF resultsCount > 1 %}s{% ENDIF %} pour "<strong>{{query}}</strong>"
        </p>
        
        <div class="results-list forum-posts-list">
            {% FOR result IN results %}
            <article class="search-result">
                <div class="result-header">
                    <a href="{{result.post.topic_url}}">
                        <i class="fa-solid fa-comments"></i> {{result.topic_title}}
                    </a>
                    <span class="category-tag">
                        dans {{result.category_name}}
                    </span>
                </div>
                <div class="result-content">
                    {{result.post.content|truncate:200}}
                </div>
                <div class="result-meta">
                    Par <strong>{{result.post.author_name}}</strong> le {{result.post.created_at_formatted}}
                </div>
            </article>
            {% ENDFOR %}
        </div>
    </div>
    {% ELSE %}
    <div class="empty-state-forum">
        <i class="fa-solid fa-search"></i>
        <p>Aucun résultat trouvé pour "<strong>{{query}}</strong>"</p>
    </div>
    {% ENDIF %}
</section>

