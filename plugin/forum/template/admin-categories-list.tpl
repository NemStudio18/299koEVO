<section class="module-card forum-admin-module">
    <header><i class="fa-solid fa-folder-tree"></i> {{Lang.forum.categories}}</header>

    <div class="forum-adm-toolbar">
        <p>{{Lang.forum.categories-help}}</p>
        <div class="forum-adm-toolbar-actions">
            <a class="button" href="/admin/forum"><i class="fa-solid fa-arrow-left"></i> {{Lang.forum.dashboard}}</a>
            <a class="button success" href="{{addUrl}}"><i class="fa-solid fa-plus"></i> {{Lang.forum.category.add}}</a>
        </div>
    </div>

    {% IF categories %}
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{Lang.forum.category.name}}</th>
                    <th>{{Lang.forum.category.slug}}</th>
                    <th>{{Lang.forum.category.order}}</th>
                    <th>{{Lang.forum.stats.topics}}</th>
                    <th>{{Lang.forum.stats.posts}}</th>
                    <th>{{Lang.forum.actions}}</th>
                </tr>
            </thead>
            <tbody>
                {% FOR category IN categories %}
                <tr class="forum-adm-row-lvl1">
                    <td>{{category.id}}</td>
                    <td>
                        <div class="forum-adm-cat-cell">
                            {% IF category.icon %}
                            <i class="{{category.icon}} forum-adm-cat-icon"></i>
                            {% ELSE %}
                            <i class="fa-solid fa-folder forum-adm-cat-icon"></i>
                            {% ENDIF %}
                            <strong>{{category.name}}</strong>
                        </div>
                    </td>
                    <td><code>{{category.slug}}</code></td>
                    <td>{{category.order_index}}</td>
                    <td>{{category.topic_count}}</td>
                    <td>{{category.post_count}}</td>
                    <td>
                        <div class="forum-adm-actions-cell">
                            <a class="button small primary" href="{{category.editUrl}}"><i class="fa-solid fa-pen"></i> {{Lang.edit}}</a>
                            <form method="POST" action="{{category.deleteUrl}}" class="forum-adm-inline-form" onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');">
                                <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                <button type="submit" class="button small alert"><i class="fa-solid fa-trash"></i> {{Lang.delete}}</button>
                            </form>
                        </div>
                    </td>
                </tr>
                {% IF category.subcategories %}
                    {% FOR subcategory IN category.subcategories %}
                    <tr class="forum-adm-row-lvl2">
                        <td>{{subcategory.id}}</td>
                        <td>
                            <div class="forum-adm-cat-cell forum-adm-indent-lvl2">
                                <span>↳</span>
                                {% IF subcategory.icon %}
                                <i class="{{subcategory.icon}} forum-adm-cat-icon-muted"></i>
                                {% ENDIF %}
                                <span>{{subcategory.name}}</span>
                            </div>
                        </td>
                        <td><code>{{subcategory.slug}}</code></td>
                        <td>{{subcategory.order_index}}</td>
                        <td>{{subcategory.topic_count}}</td>
                        <td>{{subcategory.post_count}}</td>
                        <td>
                            <div class="forum-adm-actions-cell">
                                <a class="button small primary" href="{{subcategory.editUrl}}"><i class="fa-solid fa-pen"></i> {{Lang.edit}}</a>
                                <form method="POST" action="{{subcategory.deleteUrl}}" class="forum-adm-inline-form" onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');">
                                    <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                    <button type="submit" class="button small alert"><i class="fa-solid fa-trash"></i> {{Lang.delete}}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    {% IF subcategory.subcategories %}
                        {% FOR forum IN subcategory.subcategories %}
                        <tr class="forum-adm-row-lvl3">
                            <td>{{forum.id}}</td>
                            <td>
                                <div class="forum-adm-cat-cell forum-adm-indent-lvl3">
                                    <span>↳↳</span>
                                    {% IF forum.icon %}
                                    <i class="{{forum.icon}} forum-adm-cat-icon"></i>
                                    {% ENDIF %}
                                    <strong>{{forum.name}}</strong>
                                    <span class="evo-badge evo-badge-info">Niveau 3</span>
                                </div>
                            </td>
                            <td><code>{{forum.slug}}</code></td>
                            <td>{{forum.order_index}}</td>
                            <td>{{forum.topic_count}}</td>
                            <td>{{forum.post_count}}</td>
                            <td>
                                <div class="forum-adm-actions-cell">
                                    <a class="button small primary" href="{{forum.editUrl}}"><i class="fa-solid fa-pen"></i> {{Lang.edit}}</a>
                                    <form method="POST" action="{{forum.deleteUrl}}" class="forum-adm-inline-form" onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');">
                                        <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                        <button type="submit" class="button small alert"><i class="fa-solid fa-trash"></i> {{Lang.delete}}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        {% ENDFOR %}
                    {% ENDIF %}
                    {% ENDFOR %}
                {% ENDIF %}
                {% ENDFOR %}
            </tbody>
        </table>
    </div>
    {% ELSE %}
    <div class="evo-empty">
        <p>{{Lang.forum.categories-empty}}</p>
    </div>
    {% ENDIF %}
</section>
