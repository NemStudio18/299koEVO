{% INCLUDE 'forum/breadcrumb.tpl' %}

<section class="forum-category">
    <header class="forum-category-header">
        <div class="forum-header-row">
            <div>
                <h1>
                    {% IF category.icon %}
                    <i class="{{category.icon}} forum-cat-icon"></i>
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
                            <a href="{{topic.url}}" class="{% IF topic.is_pinned %}topic-link-pinned{% ENDIF %}">
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
                            <div class="avatar-xs-initials forum-avatar-tone-{{topic.author.avatar_tone}}">
                                {{topic.author.avatar_initials}}
                            </div>
                            {% ENDIF %}
                            <span>{{topic.author.username}}</span>
                        </div>
                    </td>
                    <td class="col-stats-cell">{{topic.post_count}}</td>
                    <td class="col-stats-cell">{{topic.views}}</td>
                    <td class="col-last-cell">
                        <span class="forum-last-meta">{{topic.last_post_at_formatted}}</span>
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
    <div class="forum-empty-box">
        <i class="fa-solid fa-comments"></i>
        <p>Aucun sujet dans cette catégorie pour le moment.</p>
        {% IF token AND canCreateTopic %}
        <a href="{{createTopicFormUrl}}" class="button primary">
            <i class="fa-solid fa-plus"></i> Créer le premier sujet
        </a>
        {% ENDIF %}
    </div>
    {% ENDIF %}
    {% ENDIF %}
</section>
