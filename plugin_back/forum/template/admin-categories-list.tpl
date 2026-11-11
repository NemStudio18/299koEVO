<section class="forum-admin-categories">
    <header class="admin-actions-header">
        <div>
            <h1>{{Lang.forum.categories}}</h1>
            <p>{{Lang.forum.categories-help}}</p>
        </div>
        <a href="{{addUrl}}" class="button primary">
            {{Lang.forum.category.add}}
        </a>
    </header>

    {% IF categories %}
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>{{Lang.forum.category.name}}</th>
                <th>{{Lang.forum.category.slug}}</th>
                <th>Type</th>
                <th>{{Lang.forum.category.order}}</th>
                <th>{{Lang.forum.topics}}</th>
                <th>{{Lang.forum.posts}}</th>
                <th>{{Lang.forum.actions}}</th>
            </tr>
        </thead>
        <tbody>
            {% FOR category IN categories %}
            <tr class="{% IF category.is_subcategory %}bg-muted{% ENDIF %}">
                <td>{{category.id}}</td>
                <td>
                    {% IF category.is_subcategory %}
                    <span class="subcategory-arrow-large">
                        <i class="fa-solid fa-arrow-turn-down-right"></i> 
                    </span>
                    {% ENDIF %}
                    {% IF category.icon %}
                    <i class="{{category.icon}} mr-small" style="color: {% IF category.color %}{{category.color}}{% ELSE %}#667eea{% ENDIF %};"></i>
                    {% ENDIF %}
                    <strong>{{category.name}}</strong>
                    {% IF category.is_subcategory %}
                    <span class="color-muted font-small ml-small">(Sous-forum)</span>
                    {% ENDIF %}
                </td>
                <td>{{category.slug}}</td>
                <td>
                    {% IF category.is_subcategory %}
                    <span class="color-muted">Sous-forum</span>
                    {% ELSE %}
                    <span class="color-success">Catégorie</span>
                    {% ENDIF %}
                </td>
                <td>{{category.order_index}}</td>
                <td>{{category.topic_count}}</td>
                <td>{{category.post_count}}</td>
                <td class="table-actions">
                    <a class="button" href="{{category.editUrl}}">
                        {{Lang.edit}}
                    </a>
                    <form method="POST"
                          action="{{category.deleteUrl}}"
                          onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');"
                          class="inline-form">
                        <input type="hidden" name="_csrf" value="{{csrfToken}}">
                        <button type="submit" class="button alert">{{Lang.delete}}</button>
                    </form>
                </td>
            </tr>
            {% IF category.subcategories %}
                {% FOR subcat IN category.subcategories %}
                <tr class="bg-muted">
                    <td>{{subcat.id}}</td>
                    <td>
                        <span style="margin-left: 2rem; color: #6c757d;">
                            <i class="fa-solid fa-arrow-turn-down-right"></i> 
                        </span>
                        <strong>{{subcat.name}}</strong>
                        <span class="color-muted font-small ml-small">(Sous-catégorie)</span>
                    </td>
                    <td>{{subcat.slug}}</td>
                    <td><span style="color: #6c757d;">Sous-catégorie</span></td>
                    <td>{{subcat.order_index}}</td>
                    <td>{{subcat.topic_count}}</td>
                    <td>{{subcat.post_count}}</td>
                    <td class="table-actions">
                        <a class="button" href="{{subcat.editUrl}}">
                            {{Lang.edit}}
                        </a>
                        <form method="POST"
                              action="{{subcat.deleteUrl}}"
                              onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');"
                              class="inline-form">
                            <input type="hidden" name="_csrf" value="{{csrfToken}}">
                            <button type="submit" class="button alert">{{Lang.delete}}</button>
                        </form>
                    </td>
                </tr>
                {% IF subcat.subcategories %}
                    {% FOR forum IN subcat.subcategories %}
                    <tr style="background: rgba(102, 126, 234, 0.05);">
                        <td>{{forum.id}}</td>
                        <td>
                            <span style="margin-left: 4rem; color: var(--primary-color);">
                                <i class="fa-solid fa-comments"></i> 
                            </span>
                            <strong style="color: var(--primary-color);">{{forum.name}}</strong>
                            <span class="color-primary-admin font-small ml-small">(Forum / Salon)</span>
                        </td>
                        <td>{{forum.slug}}</td>
                        <td><span class="color-primary-admin">Forum</span></td>
                        <td>{{forum.order_index}}</td>
                        <td>{{forum.topic_count}}</td>
                        <td>{{forum.post_count}}</td>
                        <td class="table-actions">
                            <a class="button" href="{{this.router.generate('admin-forum-categories-edit', ['id' => forum.id])}}">{{Lang.edit}}</a>
                            <form method="POST" action="{{this.router.generate('admin-forum-categories-delete', ['id' => forum.id])}}" onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');" class="inline-form">
                                <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                <button type="submit" class="button alert">{{Lang.delete}}</button>
                            </form>
                        </td>
                    </tr>
                    {% ENDFOR %}
                {% ENDIF %}
                {% ENDFOR %}
            {% ENDIF %}
            {% ENDFOR %}
        </tbody>
    </table>
    {% ELSE %}
    <p>{{Lang.forum.categories-empty}}</p>
    {% ENDIF %}
</section>

