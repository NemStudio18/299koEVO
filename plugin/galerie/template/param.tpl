<form method="post" action="{ ROUTER.generate("admin-galerie-save-config") }">
    {{ SHOW.tokenField }}

    <p>
        <input {% if runPlugin.getConfigVal("showTitles") %} checked {% endif %} type="checkbox" name="showTitles" id="showTitles" />
        <label for="showTitles">{{ Lang.galerie.show-image-titles }}</label>
    </p>

    <p>
        <label for="label">{{ Lang.galerie.page-title }}</label><br>
        <input type="text" name="label" id="label" value="{{ runPlugin.getConfigVal("label") }}" />
    </p>
    <p>
        <label for="order">{{ Lang.galerie.image-order }}</label><br>
        <select name="order" id="order">
            <option value="natural" {% if runPlugin.getConfigVal("order") == "natural" %} selected {% endif %}>{{ Lang.galerie.natural-order }}</option>
            <option value="byName" {% if runPlugin.getConfigVal("order") == "byName" %} selected {% endif %}>{{ Lang.galerie.order-by-name }}</option>
            <option value="byDate" {% if runPlugin.getConfigVal("order") == "byDate" %} selected {% endif %}>{{ Lang.galerie.order-by-date }}</option>
        </select>
    </p>
    <p>
        <label for="size">{{ Lang.galerie.image-size }}</label><br>
        <select name="size" id="size">
            <option value="800" {% if runPlugin.getConfigVal("size") == "800" %} selected {% endif %}>{{ Lang.galerie.small }}</option>
            <option value="1024" {% if runPlugin.getConfigVal("size") == "1024" %} selected {% endif %}>{{ Lang.galerie.large }}</option>
            <option value="1280" {% if runPlugin.getConfigVal("size") == "1280" %} selected {% endif %}>{{ Lang.galerie.extra-large }}</option>
        </select>
    </p>
    <p>
        {{ galerieGenerateEditor() }}
    </p>

    <p><button type="submit" class="button">{{ Lang.submit }}</button></p>
</form>