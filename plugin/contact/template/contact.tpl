<style>
.contact-page-container {
    max-width: 680px;
    margin: 12px auto 24px auto;
    padding: 0;
    box-sizing: border-box;
}
.contact-header-badge {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    background: var(--dc-accent-soft, rgba(29, 78, 216, 0.1));
    color: var(--primary-color, #1d4ed8);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 12px;
}
.contact-title {
    font-size: 1.6rem;
    color: var(--font-color, #1e293b);
    margin: 0 0 8px 0;
    font-weight: 700;
}
.contact-subtitle {
    color: var(--font-secondary, #64748b);
    font-size: 0.95rem;
    margin: 0;
}
@media (max-width: 600px) {
    .contact-page-container textarea {
        min-height: 140px !important;
        font-size: 16px !important;
    }
}
</style>
<section class="contact-page-container">
    <header class="contact-header" style="margin-bottom: 24px; text-align: center;">
        <div class="contact-header-badge">
            <i class="fa-solid fa-envelope"></i>
        </div>
        <h1 class="contact-title">Contactez l'équipe</h1>
        <p class="contact-subtitle">Une question, une remarque ou un projet ? Écrivez-nous directement.</p>
    </header>

    {{ runPlugin.getConfigVal("content1") }}

    <form method="post" action="{{sendUrl}}" class="contact-form">
            {{SHOW.tokenField}}
            {% if !isLogged %}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="name">{{Lang.contact.form_name}} <span class="required">*</span></label>
                    <input class="honeypot" type="text" name="_name" value="" style="display:none;" />
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
            <div style="margin-top: 24px;">
                <button type="submit" class="btn-primary" style="width: 100%; font-size: 1rem; padding: 13px;">
                    <i class="fa-solid fa-paper-plane"></i> {{Lang.contact.form_send}}
                </button>
            </div>
        </form>
        {{ runPlugin.getConfigVal("content2") }}
</section>