{% INCLUDE PLUGINS ~ 'forum/template/breadcrumb.tpl' %}

<section class="forum-index">
    {% IF categories %}
        {% FOR category IN categories %}
        <div class="forum-category-row">
            <header class="forum-category-header">
                <h2>
                    {% IF category.icon %}
                    <i class="{{category.icon}} forum-cat-icon"></i>
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
                                <span class="forum-stats-val">{{subcategory.post_count}}</span>
                                <span class="forum-stats-lbl">Messages</span>
                            </td>
                            <td class="forum-col-last">
                                {% IF subcategory.last_post_at_formatted %}
                                <span class="forum-last-meta">Dernière activité :</span>
                                <span class="forum-last-meta">Le {{subcategory.last_post_at_formatted}}</span>
                                {% ELSE %}
                                <span class="forum-last-meta">Aucun message</span>
                                {% ENDIF %}
                            </td>
                        </tr>
                        {% ENDFOR %}
                    {% ELSE %}
                        <tr class="forum-row">
                            <td colspan="4" class="forum-empty-cell">
                                Aucune sous-catégorie ou salon dans cette section.
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
