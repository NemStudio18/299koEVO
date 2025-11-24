<form method="post" action="{{ ROUTER.generate("page-admin-save") }}" enctype="multipart/form-data">
    {{ show::tokenField() }}
    <section>
        <input type="hidden" name="id" value="{{ pageItem.getId() }}" />
        {% if pluginsManager.isActivePlugin("galerie") %}
            <input type="hidden" name="imgId" value="{{ pageItem.getImg() }}" />
        {% endif %}

        <header>{{ Lang.core-settings}}</header>
        <p>
            <input {% if pageItem.getIsHomepage() %}checked{% endif %} type="checkbox" name="isHomepage" id="isHomepage" />
            <label for="isHomepage">{{ Lang.page.homepage }}</label>
        </p>
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
            <label for="cssClass">{{ Lang.page.css-class }}</label>
            <input type="text" name="cssClass" id="cssClass" value="{{ pageItem.getCssClass() }}" />
        </p>
        <p>
            <label for="position">{{ Lang.page.position }}</label>
            <input type="number" name="position" id="position" value="{{ pageItem.getPosition() }}" />
        </p>
        <p>
            <label for="_password">{{ Lang.page.restrict-access-password }}</label>
            <input type="password" name="_password" id="_password" value="" />
        </p>
        {% if pageItem.getPassword() != "" %}
            <p>
                <input type="checkbox" name="resetPassword" id="resetPassword" /> 
                <label for="resetPassword">{{ Lang.page.remove-password-restriction }}</label>
            </p>
        {% endif %}
    </section>
    <section>
        <header>{{ Lang.page.seo-heading }}</header>
        <p>
            <input {% if pageItem.getNoIndex() %}checked{% endif %} type="checkbox" name="noIndex" id="noIndex"/>
            <label for="noIndex">{{ Lang.page.no-index-checkbox }}</label>
        </p>
        <p>
            <label for="metaTitleTag">{{ Lang.page.meta-title }}</label>
            <input type="text" name="metaTitleTag" id="metaTitleTag" value="{{ pageItem.getMetaTitleTag() }}" />
        </p>
        <p>
            <label for="metaDescriptionTag">{{ Lang.page.meta-description }}</label>
            <input type="text" name="metaDescriptionTag" id="metaDescriptionTag" value="{{ pageItem.getMetaDescriptionTag() }}" />
        </p>
    </section>
    <section>
        <header>{{ Lang.page.content-heading }}</header>
        <p>
            <label for="name">{{ Lang.page.name-label }}</label><br>
            <input type="text" name="name" id="name" value="{{ pageItem.getName() }}" required="required" />
        </p>
        <p>
            <label for="mainTitle">{{ Lang.page.page-title }}</label><br>
            <input type="text" name="mainTitle" id="mainTitle" value="{{ pageItem.getMainTitle() }}" />
        </p>
        <p>
            <label for="file">{{ Lang.page.include-file }}
                <select name="file" id="file">
                    <option value="">--</option>
                    {% for file in page.listTemplates() %}
                        <option {% if file == pageItem.getFile() %}selected{% endif %} value="{{ file }}">{{ file }}</option>
                    {% endfor %}
                </select>
        </p>
        <p>
            {{ contentEditor }}
        </p>
    </section>

    {% if pluginsManager.isActivePlugin("galerie") %}
        <section>
            <header>{{ Lang.galerie.featured-image }}</header>
            <p>
                {% if pageItem.getImg() %}<input type="checkbox" name="delImg" id="delImg" />
                    <label for="delImg">{{ Lang.galerie.delete-featured-image }}</label><br>
                {% else %}<label for="file">{{ Lang.galerie.file }} (png, jpg, jpeg, gif)</label><br><input type="file" name="file" id="file" accept="image/*" />{% endif %}
                <br><br>
                {% if pageItem.getImg() %}<img src="{{ pageItem.getImgUrl() }}" alt="{{ pageItem.getImg() }}" />{% endif %}
            </p>
        </section>
    {% endif %}
    <p>
        <button type="submit" class="button success">{{ Lang.submit }}</button>
    </p>
</form>
