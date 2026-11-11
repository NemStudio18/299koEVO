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

<section class="forum-index">
    {% IF categories %}
        {% FOR category IN categories %}
        <div class="forum-category-row">
            <header class="forum-category-header">
                <h2>
                    {% IF category.icon %}
                    <i class="{{category.icon}}" style="color: {% IF category.color %}{{category.color}}{% ELSE %}var(--primary-color){% ENDIF %};"></i>
                    {% ENDIF %}
                    <a href="{{category.url}}">{{category.name}}</a>
                </h2>
            </header>
            
            <table class="forum-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{forumTableHeaderLabel}}</th>
                        <th class="col-stats">Stats</th>
                        <th class="col-last-post">Dernier message</th>
                    </tr>
                </thead>
                <tbody>
                    {% IF category.subcategories %}
                        {% FOR subcategory IN category.subcategories %}
                        <tr class="forum-row">
                            <td class="forum-col-icon">
                                <div class="forum-icon-circle">
                                    <i class="{% IF subcategory.icon %}{{subcategory.icon}}{% ELSE %}fa-solid fa-comments{% ENDIF %}"></i>
                                </div>
                            </td>
                            <td class="forum-col-main">
                                <div class="forum-title">
                                    <a href="{{subcategory.url}}">{{subcategory.name}}</a>
                                </div>
                                {% IF subcategory.description %}
                                <p class="forum-desc">{{subcategory.description}}</p>
                                {% ENDIF %}
                            </td>
                            <td class="forum-col-stats">
                                <span class="forum-stats-val">{{subcategory.topic_count}}</span>
                                <span class="forum-stats-lbl">Sujets</span>
                                <br>
                                <span class="forum-stats-val">{{subcategory.post_count}}</span>
                                <span class="forum-stats-lbl">Messages</span>
                            </td>
                            <td class="forum-col-last">
                                <span class="forum-last-meta">Dernière activité :</span>
                                <span class="forum-last-meta">Le {{subcategory.last_post_at_formatted}}</span>
                            </td>
                        </tr>
                        {% ENDFOR %}
                    {% ELSE %}
                        <tr class="forum-row">
                            <td colspan="4" style="padding: 1.5rem; text-align: center; color: var(--font-secondary);">
                                Aucune sous-catégorie ou forum dans cette section.
                            </td>
                        </tr>
                    {% ENDIF %}
                </tbody>
            </table>
        </div>
        {% ENDFOR %}
    {% ELSE %}
    <div class="empty-state-forum">
        <i class="fa-solid fa-inbox"></i>
        <p>Aucune catégorie disponible pour le moment.</p>
    </div>
    {% ENDIF %}
</section>

