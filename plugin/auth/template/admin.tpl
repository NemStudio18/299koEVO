<section class="module-card">
    <header><i class="fa-solid fa-key"></i> Configuration Authentification &amp; Profils</header>

    <form method="post" action="{{ ROUTER.generate('auth-admin-save') }}">
        <input type="hidden" name="_csrf" value="{{_csrfToken}}"/>

        <div class="tabs-container" data-tab-key="auth">
            <ul class="tabs-header">
                <li class="default-tab" data-tab="oauth"><i class="fa-solid fa-plug"></i> OAuth (Facebook/Google)</li>
                <li data-tab="fields"><i class="fa-solid fa-id-card"></i> Champs Profil</li>
                <li data-tab="register"><i class="fa-solid fa-user-plus"></i> Inscription</li>
                <li data-tab="help"><i class="fa-solid fa-circle-question"></i> Aide &amp; Spoiler</li>
            </ul>

            <ul class="tabs">
                <!-- TAB: OAUTH -->
                <li class="tab">
            <h4>Facebook</h4>
            <p class="split">
                <span>
                    <label><input type="checkbox" name="facebook_enabled" {% if config.facebook_enabled %}checked{% endif %} /> Activer Facebook</label>
                </span>
            </p>
            <p class="split">
                <span>
                    <label for="facebook_app_id">App ID</label>
                    <input type="text" id="facebook_app_id" name="facebook_app_id" value="{{config.facebook_app_id}}" autocomplete="off" />
                </span>
                <span>
                    <label for="facebook_app_secret">App Secret</label>
                    <input type="password" id="facebook_app_secret" name="facebook_app_secret" value="{{config.facebook_app_secret}}" autocomplete="off" />
                </span>
            </p>
            <p>
                <small><strong>URL de callback Facebook :</strong><br>
                <code>{{ CORE.getConfigVal("siteUrl") }}/auth/facebook/callback</code></small>
            </p>

            <hr>

            <h4>Google</h4>
            <p class="split">
                <span>
                    <label><input type="checkbox" name="google_enabled" {% if config.google_enabled %}checked{% endif %} /> Activer Google</label>
                </span>
            </p>
            <p class="split">
                <span>
                    <label for="google_client_id">Client ID</label>
                    <input type="text" id="google_client_id" name="google_client_id" value="{{config.google_client_id}}" autocomplete="off" />
                </span>
                <span>
                    <label for="google_client_secret">Client Secret</label>
                    <input type="password" id="google_client_secret" name="google_client_secret" value="{{config.google_client_secret}}" autocomplete="off" />
                </span>
            </p>
            <p>
                <small><strong>URL de callback Google :</strong><br>
                <code>{{ CORE.getConfigVal("siteUrl") }}/auth/google/callback</code></small>
            </p>
                </li>
                <!-- TAB: FIELDS -->
                <li class="tab">
            <h4>Gestion des champs de profil</h4>
            <p><small>Activez ou désactivez les champs de profil et gérez leurs sources de données (JSON pour les listes).</small></p>
            
            <div id="dynamic-fields-container">
                {% FOR i, field IN config.dynamic_fields %}
                    <div class="dynamic-field-row">
                        <button type="button" class="button alert small remove-field" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                        <p class="split">
                            <span>
                                <label>ID (ex: age, city)</label>
                                <input type="text" name="dynamic_fields[{{i}}][id]" value="{{field.id}}" />
                            </span>
                            <span>
                                <label>Label (Affiché à l'utilisateur)</label>
                                <input type="text" name="dynamic_fields[{{i}}][label]" value="{{field.label}}" />
                            </span>
                        </p>
                        <p class="split">
                            <span>
                                <label>Type</label>
                                <select name="dynamic_fields[{{i}}][type]">
                                    <option value="text" {% if field.type == 'text' %}selected{% endif %}>Texte</option>
                                    <option value="number" {% if field.type == 'number' %}selected{% endif %}>Nombre</option>
                                    <option value="select" {% if field.type == 'select' %}selected{% endif %}>Sélecteur (Liste)</option>
                                </select>
                            </span>
                            <span>
                                <label><input type="checkbox" name="dynamic_fields[{{i}}][enabled]" {% if field.enabled %}checked{% endif %} /> Activé</label>
                                <label style="margin-left: 1rem;"><input type="checkbox" name="dynamic_fields[{{i}}][required]" {% if field.required %}checked{% endif %} /> Obligatoire</label>
                                <label style="margin-left: 1rem;"><input type="checkbox" name="dynamic_fields[{{i}}][show_on_register]" {% if field.show_on_register %}checked{% endif %} /> Afficher à l'inscription</label>
                            </span>
                        </p>
                        <p class="field-options-box" style="{% if field.type != 'select' %}display:none;{% endif %}">
                            <label>Options (Format: <code>val:Label,val2:Label2</code>) OU Source JSON (Chemin relatif)</label>
                            <input type="text" name="dynamic_fields[{{i}}][options]" value="{{field.options}}" placeholder="M:Homme,F:Femme" />
                            <input type="text" name="dynamic_fields[{{i}}][source]" value="{{field.source}}" placeholder="common/data/agglo.json" style="margin-top:0.5rem;" />
                        </p>
                    </div>
                {% ENDFOR %}
            </div>
            
            <p><button type="button" id="add-field" class="button"><i class="fa-solid fa-plus"></i> Ajouter un champ personnalisé</button></p>
                </li>
                <!-- TAB: REGISTER -->
                <li class="tab">
            <h4>Contenu des pages Login / Register</h4>
            <p>
                <label for="content_position">Position du contenu personnalisé</label>
                <select id="content_position" name="content_position">
                    <option value="top" {% if config.content_position == "top" %}selected{% endif %}>Haut (Au-dessus du formulaire)</option>
                    <option value="bottom" {% if config.content_position == "bottom" %}selected{% endif %}>Bas (En-dessous du formulaire)</option>
                </select>
            </p>
            <p class="split">
                <span>
                    <label for="login_text">Texte Page CONNEXION</label>
                    <textarea id="login_text" name="login_text" class="editor" rows="3">{{config.login_text}}</textarea>
                </span>
                <span>
                    <label for="register_text">Texte Page INSCRIPTION</label>
                    <textarea id="register_text" name="register_text" class="editor" rows="3">{{config.register_text}}</textarea>
                </span>
            </p>

            <hr>
            <h4>Cases à cocher (Consentement)</h4>
            <div id="checkboxes-container">
                {% FOR i, cb IN config.checkboxes %}
                    <div class="checkbox-row split auth-checkbox-row" style="background: var(--bg-light); padding: 0.5rem; margin-bottom: 0.5rem; border-radius: 4px;">
                        <span>
                            <label>Label</label>
                            <input type="text" name="checkboxes[{{i}}][label]" value="{{cb.label}}" />
                        </span>
                        <span>
                            <label><input type="checkbox" name="checkboxes[{{i}}][required]" {% if cb.required %}checked{% endif %} /> Obligatoire</label>
                        </span>
                        <span>
                            <button type="button" class="button alert small remove-cb" style="margin-top: 1.5rem;"><i class="fa-solid fa-trash"></i></button>
                        </span>
                    </div>
                {% ENDFOR %}
            </div>
            <p><button type="button" id="add-checkbox" class="button small"><i class="fa-solid fa-plus"></i> Ajouter une case</button></p>
                </li>
                <!-- TAB: HELP -->
                <li class="tab">
            <h4>Bloc Spoiler / Aide</h4>
            <p>
                <label><input type="checkbox" name="show_spoiler" {% if config.show_spoiler %}checked{% endif %} /> Activer le bloc d'aide (spoiler)</label>
            </p>
            <p class="split">
                <span>
                    <label for="spoiler_title">Titre du bouton</label>
                    <input type="text" id="spoiler_title" name="spoiler_title" value="{{config.spoiler_title}}" />
                </span>
                <span>
                    <label for="spoiler_type">Type d'affichage</label>
                    <select id="spoiler_type" name="spoiler_type">
                        <option value="accordion" {% if config.spoiler_type == "accordion" %}selected{% endif %}>Accordéon</option>
                        <option value="modal" {% if config.spoiler_type == "modal" %}selected{% endif %}>Modale</option>
                    </select>
                </span>
            </p>
            <p>
                <label for="spoiler_content">Contenu HTML</label>
                <textarea id="spoiler_content" name="spoiler_content" class="editor" rows="8">{{config.spoiler_content}}</textarea>
            </p>
                </li>
            </ul>
        </div>

        <p style="margin-top: 16px;">
            <button class="button success" type="submit"><i class="fa-solid fa-save"></i> Sauvegarder toute la configuration</button>
        </p>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // Add Checkbox Logic
    document.getElementById('add-checkbox').addEventListener('click', function() {
        const container = document.getElementById('checkboxes-container');
        const index = 'new_' + Date.now();
        const row = document.createElement('div');
        row.className = 'checkbox-row split auth-checkbox-row';
        row.style = 'background: var(--bg-light); padding: 0.5rem; margin-bottom: 0.5rem; border-radius: 4px;';
        row.innerHTML = `
            <span>
                <label>Label</label>
                <input type="text" name="checkboxes[` + index + `][label]" value="" />
            </span>
            <span>
                <label><input type="checkbox" name="checkboxes[` + index + `][required]" /> Obligatoire</label>
            </span>
            <span>
                <button type="button" class="button alert small remove-cb" style="margin-top: 1.5rem;"><i class="fa-solid fa-trash"></i></button>
            </span>
        `;
        container.appendChild(row);
    });

    // Add Field Logic
    document.getElementById('add-field').addEventListener('click', function() {
        const container = document.getElementById('dynamic-fields-container');
        const index = 'new_' + Date.now();
        const row = document.createElement('div');
        row.className = 'dynamic-field-row';
        row.innerHTML = `
            <button type="button" class="button alert small remove-field" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
            <p class="split">
                <span>
                    <label>ID (ex: age, city)</label>
                    <input type="text" name="dynamic_fields[` + index + `][id]" value="" />
                </span>
                <span>
                    <label>Label (Affiché)</label>
                    <input type="text" name="dynamic_fields[` + index + `][label]" value="" />
                </span>
            </p>
            <p class="split">
                <span>
                    <label>Type</label>
                    <select name="dynamic_fields[` + index + `][type]" class="field-type-select">
                        <option value="text">Texte</option>
                        <option value="number">Nombre</option>
                        <option value="select">Sélecteur (Liste)</option>
                    </select>
                </span>
                <span>
                    <label><input type="checkbox" name="dynamic_fields[` + index + `][enabled]" checked /> Activé</label>
                    <label style="margin-left: 1rem;"><input type="checkbox" name="dynamic_fields[` + index + `][required]" /> Obligatoire</label>
                    <label style="margin-left: 1rem;"><input type="checkbox" name="dynamic_fields[` + index + `][show_on_register]" /> Afficher à l'inscription</label>
                </span>
            </p>
            <p class="field-options-box" style="display:none;">
                <label>Options (val:Label) OU Source JSON</label>
                <input type="text" name="dynamic_fields[` + index + `][options]" placeholder="M:Homme,F:Femme" />
                <input type="text" name="dynamic_fields[` + index + `][source]" placeholder="common/data/agglo.json" style="margin-top:0.5rem;" />
            </p>
        `;
        container.appendChild(row);
    });

    // Delegate Events
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-cb')) {
            e.target.closest('.checkbox-row').remove();
        }
        if (e.target.closest('.remove-field')) {
            e.target.closest('.dynamic-field-row').remove();
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.name && e.target.name.includes('[type]')) {
            const row = e.target.closest('.dynamic-field-row');
            const optionsBox = row.querySelector('.field-options-box');
            if (e.target.value === 'select') {
                optionsBox.style.display = 'block';
            } else {
                optionsBox.style.display = 'none';
            }
        }
    });
});
</script>
