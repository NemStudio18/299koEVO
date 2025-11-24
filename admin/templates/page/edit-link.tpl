<section>
    <header>{{ Lang.page.edit-link }}</header>
    <form method="post" action="{{ ROUTER.generate("page-admin-save") }}" enctype="multipart/form-data">
        {{ show::tokenField() }}
        <input type="hidden" name="id" value="{{ pageItem.getId() }}" />
        <p>
            <input {% if pageItem.getIsHidden() %}checked{% endif %} type="checkbox" name="isHidden" id="isHidden" />
            <label for="isHidden">{{ Lang.page.hide-from-menu }}</label>
        </p>
        <p>
            <label for="parent">{{ Lang.page.parent-item }}</label><br>
            <select name="parent" id="parent">
                <option value="">{{ Lang.page.no-parent-option }}</option>
                {% for v in page.getItems() %}
                    {% if v.targetIs() == "parent" %}
                        <option {% if v.getId() == pageItem.getParent() %}selected{% endif %} value="{{ v.getId() }}">{{ v.getName() }}</option>
                    {% endif %}
                {% endfor %}
            </select>
        </p>
        <p>
            <label for="name">{{ Lang.page.name-label }}</label><br>
            <input type="text" name="name" id="name" value="{{ pageItem.getName() }}" required="required" />
        </p>
        {% if pageItem.targetIs() == "plugin" %}
            <p>
                <label for="target">{{ Lang.page.target }} : {{ pageItem.getTarget() }}</label>
                <input style="display:none;" type="text" name="target" id="target" value="{{ pageItem.getTarget() }}" />
            </p>
        {% else %}
            <p>
                <label for="target">{{ Lang.page.target }}</label><br>
                <input placeholder="{{ Lang.page.example }} : https://299ko.ovh" {% if pageItem.targetIs() == "plugin" %}readonly{% endif %} type="url" name="target" id="target" value="{{ pageItem.getTarget() }}" required="required" />
            </p>
        {% endif %}
        <p>
            <label for="targetAttr">{{ Lang.page.open }}</label><br>
            <select name="targetAttr" id="targetAttr">
                <option value="_self" {% if pageItem.getTargetAttr() == "_self" %}selected{% endif %}>{{ Lang.page.open-same-window }}</option>
                <option value="_blank" {% if pageItem.getTargetAttr() == "_blank" %}selected{% endif %}>{{ Lang.page.open-new-window }}</option>
            </select>
        </p>
        <p>
            <label for="cssClass">{{ Lang.page.css-class }}</label>
            <input type="text" name="cssClass" id="cssClass" value="{{ pageItem.getCssClass() }}" />
        </p>
        <p>
            <label for="position">{{ Lang.page.position }}</label>
            <input type="number" name="position" id="position" value="{{ pageItem.getPosition() }}" />
        </p>
        <p>
            <button type="submit" class="button success radius">{{ Lang.submit }}</button>
        </p>
    </form>
</section>
