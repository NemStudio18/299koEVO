<section class="contact-page-container">
    <header class="contact-header">
        <div class="contact-header-badge">
            <i class="fa-solid fa-envelope"></i>
        </div>
        <h1 class="contact-title">Contactez l'équipe</h1>
    </header>

    {{ runPlugin.getConfigVal("content1") }}

    <form method="post" action="{{sendUrl}}" class="contact-form">
            {{SHOW.tokenField}}
            {% if !isLogged %}
            <div class="contact-form-row">
                <div class="form-group">
                    <label for="name">{{Lang.contact.form_name}} <span class="required">*</span></label>
                    <input class="honeypot" type="text" name="_name" value="" />
                    <input required="required" type="text" name="name" id="name" value="{{name}}" placeholder="Votre nom" />
                </div>	
                <div class="form-group">
                    <label for="firstname">{{Lang.contact.form_firstname}} <span class="required">*</span></label>
                    <input required="required" type="text" name="firstname" id="firstname" value="{{firstname}}" placeholder="Votre prénom" />
                </div>
            </div>
            <div class="form-group">
                <label for="email">{{Lang.contact.form_email}} <span class="required">*</span></label>
                <input required="email" type="email" name="email" id="email" value="{{email}}" placeholder="nom@exemple.fr" />
            </div>
            {% endif %}
            {% if topics %}
            <div class="form-group">
                <label for="topic">Sujet de votre message <span class="required">*</span></label>
                <select name="topic" id="topic" required="required">
                    <option value="">-- Sélectionnez un sujet --</option>
                    {% for t in topics %}
                    <option value="{{t}}">{{t}}</option>
                    {% endfor %}
                </select>
            </div>
            {% endif %}
            <div class="form-group">
                <label for="message">{{Lang.contact.form_message}} <span class="required">*</span></label>
                <textarea required="required" name="message" id="message" rows="6" placeholder="Écrivez votre message ici...">{{message}}</textarea>
            </div>
            {% if acceptation && !isLogged %}
                <div class="form-group acceptation">
                    <label for="acceptation" class="form-checkbox-label">
                        <input type="checkbox" required="required" name="acceptation" id="acceptation"/>
                        <span>{{ runPlugin.getConfigVal("acceptation") }}</span>
                    </label>
                </div>
            {% endif %}
            {% if antispam %}
                <div class="form-group antispam-wrapper">
                    {{antispamField}}
                </div>
            {% endif %}
            <div class="contact-submit-wrap">
                <button type="submit" class="btn-primary contact-submit-btn">
                    <i class="fa-solid fa-paper-plane"></i> {{Lang.contact.form_send}}
                </button>
            </div>
        </form>
        {{ runPlugin.getConfigVal("content2") }}
</section>