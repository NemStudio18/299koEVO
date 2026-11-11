<section>
    {{ runPlugin.getConfigVal("content1") }}

    <form method="post" action="{{sendUrl}}">
        {{SHOW.tokenField}}
        {% if !isLogged %}
        <p>
            <label for="name">{{Lang.contact.form_name}}</label><br>
            <input class="honeypot" type="text" name="_name" value="" />
            <input required="required" type="text" name="name" id="name" value="{{name}}" />
        </p>	
        <p>
            <label for="firstname">{{Lang.contact.form_firstname}}</label><br>
            <input required="required" type="text" name="firstname" id="firstname" value="{{firstname}}" />
        </p>
        <p>
            <label for="email">{{Lang.contact.form_email}}</label><br>
            <input required="email" type="email" name="email" id="email" value="{{email}}" />
        </p>
        {% endif %}
        {% if topics %}
        <p>
            <label for="topic">Sujet de votre message</label><br>
            <select name="topic" id="topic" required="required">
                <option value="">-- Sélectionnez un sujet --</option>
                {% for t in topics %}
                <option value="{{t}}">{{t}}</option>
                {% endfor %}
            </select>
        </p>
        {% endif %}
        <p>
            <label for="message">{{Lang.contact.form_message}}</label><br>
            <textarea required="required" name="message" id="message" class="editor">{{message}}</textarea>
        </p>
        {% if acceptation && !isLogged %}
            <p class="acceptation">
                <label for="acceptation"><input type="checkbox" required="required" name="acceptation" id="acceptation"/>{{ runPlugin.getConfigVal("acceptation") }}</label>
            </p>
        {% endif %}
        {% if antispam %}
            {{antispamField}}
        {% endif %}
        <p>
            <input type="submit" value="{{Lang.contact.form_send}}" />
        </p>
    </form>
    {{ runPlugin.getConfigVal("content2") }}
</section>