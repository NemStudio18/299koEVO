<nav class="forum-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb-list">
        {% FOR item IN breadcrumb %}
        <li class="breadcrumb-item">
            {% IF item.is_active %}
            <span class="breadcrumb-current">{{item.label}}</span>
            {% ELSE %}
            <a href="{{item.url}}" class="breadcrumb-link">
                {{item.label}}
            </a>
            <i class="fa-solid fa-chevron-right breadcrumb-separator"></i>
            {% ENDIF %}
        </li>
        {% ENDFOR %}
    </ol>
</nav>

