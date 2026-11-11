<nav class="forum-breadcrumb mb-medium padding-y-small border-bottom">
    <ol class="breadcrumb-list">
        {% FOR item IN breadcrumb %}
        <li class="breadcrumb-item">
            {% IF item.is_active %}
            <span class="color-secondary font-medium">{{item.label}}</span>
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

