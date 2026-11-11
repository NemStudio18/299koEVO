<section class="module-card forum-admin-module">
    <header><i class="fa-solid fa-folder-plus"></i> {{title}}</header>

    <form method="POST" action="{{actionUrl}}">
        <input type="hidden" name="_csrf" value="{{csrfToken}}">

        <div class="forum-adm-form-grid">
            <p>
                <label for="cat_name">{{Lang.forum.category.name}} *</label>
                <input type="text" id="cat_name" name="name" value="{% IF category %}{{category.getName()}}{% ENDIF %}" required>
            </p>
            <p>
                <label for="cat_slug">{{Lang.forum.category.slug}}</label>
                <input type="text" id="cat_slug" name="slug" value="{% IF category %}{{category.getSlug()}}{% ENDIF %}" placeholder="auto">
                <small>{{Lang.forum.category.slug-help}}</small>
            </p>
        </div>

        <div class="forum-adm-form-grid">
            {% IF parentCategories %}
            <p>
                <label for="cat_parent">{{Lang.forum.category.parent}}</label>
                <select id="cat_parent" name="parent_id">
                    <option value="">Aucune (catégorie principale)</option>
                    {% FOR parentCat IN parentCategories %}
                    <option value="{{parentCat.id}}" {% IF category %}{% IF category.getParentId() == parentCat.id %}selected{% ENDIF %}{% ENDIF %}>
                        {{parentCat.name}}
                    </option>
                    {% ENDFOR %}
                </select>
                <small>Sélectionnez une catégorie parente pour créer une sous-catégorie ou un salon.</small>
            </p>
            {% ENDIF %}

            <p>
                <label for="cat_order">{{Lang.forum.category.order}}</label>
                <input type="number" id="cat_order" name="order_index" value="{% IF category %}{{category.getOrderIndex()}}{% ELSE %}0{% ENDIF %}">
            </p>

            <p>
                <label for="cat_icon">{{Lang.forum.category.icon}} (FontAwesome)</label>
                <input type="text" id="cat_icon" name="icon" value="{% IF category %}{{category.getIcon()}}{% ENDIF %}" placeholder="fa-solid fa-comments">
            </p>
        </div>

        <p>
            <label for="cat_description">{{Lang.forum.category.description}}</label>
            <textarea id="cat_description" name="description" rows="4">{% IF category %}{{category.getDescription()}}{% ENDIF %}</textarea>
        </p>

        <div class="forum-adm-form-actions">
            <button type="submit" class="button primary"><i class="fa-solid fa-save"></i> {{Lang.forum.category.save}}</button>
            <a class="button secondary" href="{{cancelUrl}}">{{Lang.forum.category.cancel}}</a>
        </div>
    </form>
</section>
