<section class="forum-category-form">
    <h1>{{title}}</h1>

    <form method="POST" action="{{actionUrl}}" class="form-vertical">
        <input type="hidden" name="_csrf" value="{{csrfToken}}">

        <div class="form-group">
            <label>{{Lang.forum.category.name}}</label>
            {% IF category %}
                <input type="text" name="name" value="{{category.getName()}}" required>
            {% ELSE %}
                <input type="text" name="name" value="" required>
            {% ENDIF %}
        </div>

        <div class="form-group">
            <label>{{Lang.forum.category.slug}}</label>
            {% IF category %}
                <input type="text" name="slug" value="{{category.getSlug()}}" placeholder="{{Lang.forum.category.slug}}">
            {% ELSE %}
                <input type="text" name="slug" value="" placeholder="{{Lang.forum.category.slug}}">
            {% ENDIF %}
            <small>{{Lang.forum.category.slug-help}}</small>
        </div>

        <div class="form-group">
            <label>{{Lang.forum.category.description}}</label>
            {% IF category %}
                <textarea name="description" rows="4">{{category.getDescription()}}</textarea>
            {% ELSE %}
                <textarea name="description" rows="4"></textarea>
            {% ENDIF %}
        </div>

        <div class="form-group">
            <label>{{Lang.forum.category.parent}}</label>
            <select name="parent_id">
                <option value="0">-- Catégorie principale (pas de parent) --</option>
                {% FOR parentCat IN parentCategories %}
                <option value="{{parentCat.id}}" {% IF category AND category.getParentId() == parentCat.id %}selected{% ENDIF %}>
                    {{parentCat.name}}
                </option>
                {% ENDFOR %}
            </select>
            <small>Laissez "Catégorie principale" pour créer une catégorie de niveau 1, ou sélectionnez une catégorie pour créer un sous-forum.</small>
        </div>

        <div class="form-group">
            <label>{{Lang.forum.category.icon}}</label>
            {% IF category %}
                <input type="text" name="icon" value="{{category.getIcon()}}" placeholder="fa-solid fa-comments">
            {% ELSE %}
                <input type="text" name="icon" value="" placeholder="fa-solid fa-comments">
            {% ENDIF %}
            <small>Icône FontAwesome (ex: fa-solid fa-comments)</small>
        </div>

        <div class="form-group">
            <label>{{Lang.forum.category.color}}</label>
            {% IF category %}
                <input type="color" name="color" value="{% IF category.getColor() %}{{category.getColor()}}{% ELSE %}#667eea{% ENDIF %}">
            {% ELSE %}
                <input type="color" name="color" value="#667eea">
            {% ENDIF %}
            <small>Couleur de l'icône et des bordures</small>
        </div>

        <div class="form-group">
            <label>{{Lang.forum.category.order}}</label>
            {% IF category %}
                <input type="number" name="order_index" value="{{category.getOrderIndex()}}">
            {% ELSE %}
                <input type="number" name="order_index" value="0">
            {% ENDIF %}
        </div>

        <div class="form-actions">
            <button type="submit" class="button primary">{{Lang.forum.category.save}}</button>
            <a class="button secondary" href="{{cancelUrl}}">{{Lang.forum.category.cancel}}</a>
        </div>
    </form>
</section>

