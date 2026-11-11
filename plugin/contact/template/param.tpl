<form method="post" action="{{ ROUTER.generate("contact-saveParams")}}">
    {{ SHOW.tokenField }}
    <p>
        <label for="copy">{{ Lang.contact.copy_recipient }}</label><br>
        <input type="email" name="copy" id="copy" value="{{ runPlugin.getConfigVal("copy") }}" />
    </p>
    <p>
        <label for="label">{{ Lang.contact.page_title }}</label><br>
        <input type="text" name="label" id="label" value="{{ runPlugin.getConfigVal("label") }}" required />
    </p>
    <p>
        <label for="selectedUser">{{ Lang.contact.select-user }}</label><br>
        <select name="selectedUser" id="selectedUser">
        {% for user in contactUsers %}
             <option value='{{user.id}}'
                {% if contactSelected == user.id %} selected {% endif %}
            >{{ user.email }}</option>
        {% endfor %}
        </select>
    </p>
    <p>
        <label for="acceptation">{{ Lang.contact.form_acceptance_text }}</label><br>
        <textarea name="acceptation" id="acceptation">{{ runPlugin.getConfigVal("acceptation") }}</textarea>
    </p>
    <p><button type="submit" class="button">{{ Lang.save }}</button></p>
</form>