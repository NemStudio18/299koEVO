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

<section class="forum-category">
    <header class="forum-category-header">
        <div class="forum-header-row">
            <div>
                <h1 class="mb-small">
                    {% IF category.icon %}
                    <i class="{{category.icon}} mr-small" style="color: {% IF category.color %}{{category.color}}{% ELSE %}var(--primary-color){% ENDIF %};"></i>
                    {% ENDIF %}
                    {{category.name}}
                </h1>
                {% IF category.description %}
                <p class="forum-description">{{category.description}}</p>
                {% ENDIF %}
            </div>
            {% IF token AND canCreateTopic %}
            <a href="{{createTopicFormUrl}}" class="button primary">
                <i class="fa-solid fa-plus"></i> Nouveau sujet
            </a>
            {% ENDIF %}
        </div>
    </header>

    {% IF hasSubcategories %}
    <div class="forum-category-row">
        <table class="forum-table">
            <thead>
                <tr>
                    <th></th>
                    <th>{{forumTableHeaderLabel}} / Salon</th>
                    <th class="col-stats">Stats</th>
                    <th class="col-last-post">Dernier message</th>
                </tr>
            </thead>
            <tbody>
                {% FOR subcat IN subcategories %}
                <tr class="forum-row">
                    <td class="forum-col-icon">
                        <div class="forum-icon-circle">
                            <i class="{% IF subcat.icon %}{{subcat.icon}}{% ELSE %}fa-solid fa-comments{% ENDIF %}"></i>
                        </div>
                    </td>
                    <td class="forum-col-main">
                        <div class="forum-title">
                            <a href="{{subcat.url}}">{{subcat.name}}</a>
                        </div>
                        {% IF subcat.description %}
                        <p class="forum-desc">{{subcat.description}}</p>
                        {% ENDIF %}
                    </td>
                    <td class="forum-col-stats">
                        <span class="forum-stats-val">{{subcat.topic_count}}</span>
                        <span class="forum-stats-lbl">Sujets</span>
                        <br>
                        <span class="forum-stats-val">{{subcat.post_count}}</span>
                        <span class="forum-stats-lbl">Messages</span>
                    </td>
                    <td class="forum-col-last">
                        <span class="forum-last-meta">Activé le {{subcat.created_at_formatted}}</span>
                    </td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>
    </div>
    {% ENDIF %}

    {% IF topics %}
    <div class="forum-topic-list-container">
        <table class="forum-topics-table">
            <thead>
                <tr>
                    <th>Sujet</th>
                    <th class="col-author">Auteur</th>
                    <th class="col-stats">Réponses</th>
                    <th class="col-stats">Vues</th>
                    <th class="col-last-post">Dernier message</th>
                </tr>
            </thead>
            <tbody>
                {% FOR topic IN topics %}
                <tr {% IF topic.is_pinned %}class="forum-topic-pinned-row"{% ENDIF %}>
                    <td>
                        <div class="form-actions-row">
                            <a href="{{topic.url}}" class="{% IF topic.is_pinned %}topic-link-pinned{% ENDIF %} text-decoration-none color-font">
                                {{topic.title}}
                            </a>
                            {% IF topic.is_pinned %}
                            <span class="forum-badge-pinned">
                                <i class="fa-solid fa-thumbtack"></i> Épinglé
                            </span>
                            {% ENDIF %}
                            {% IF topic.is_locked %}
                            <span class="forum-badge-locked">
                                <i class="fa-solid fa-lock"></i> Verrouillé
                            </span>
                            {% ENDIF %}
                        </div>
                    </td>
                    <td>
                        <div class="form-actions-row">
                            {% IF topic.author.has_valid_avatar %}
                            <img src="{{topic.author.avatar}}" alt="{{topic.author.username}}" class="avatar-xs">
                            {% ELSE %}
                            <div class="avatar-xs-initials" style="background: {{topic.author.avatar_color}}; color: var(--primary-inverse-color);">
                                {{topic.author.avatar_initials}}
                            </div>
                            {% ENDIF %}
                            <span>{{topic.author.username}}</span>
                        </div>
                    </td>
                    <td class="text-center">{{topic.post_count}}</td>
                    <td class="text-center">{{topic.views}}</td>
                    <td style="color: var(--font-secondary); font-size: 0.85rem;">
                        {{topic.last_post_at_formatted}}
                    </td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>
    </div>
    
    {% IF hasPagination %}
    <div class="forum-pagination-wrapper">
        {% FOR pageItem IN paginationPages %}
        <a href="{{pageItem.url}}" class="button {% IF pageItem.is_current %}primary{% ELSE %}secondary{% ENDIF %} pagination-btn">
            {{pageItem.number}}
        </a>
        {% ENDFOR %}
    </div>
    {% ENDIF %}
    {% ELSE %}
    {% IF NOT hasSubcategories %}
    <div class="forum-empty-box extra-padding">
        <i class="fa-solid fa-comments icon-extra-large mb-medium"></i>
        <p>Aucun sujet dans cette catégorie pour le moment.</p>
        {% IF token AND canCreateTopic %}
        <a href="{{createTopicFormUrl}}" class="button primary mt-medium">
            <i class="fa-solid fa-plus"></i> Créer le premier sujet
        </a>
        {% ENDIF %}
    </div>
    {% ENDIF %}
    {% ENDIF %}
</section>

