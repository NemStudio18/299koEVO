<section class="overflow-auto">
    <header><i class="fa-solid fa-layer-group"></i> Modifier le bloc</header>

    <div style="margin-bottom: 16px;">
        <a href="{{ ROUTER.generate('admin-homebuilder') }}" class="button secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <form method="POST" action="{{ edit_send_link }}">
        <div style="background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 10px; padding: 20px; margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Informations générales</h3>

            <label for="title">Titre du bloc *</label>
            <input type="text" id="title" name="title" value="{{ title }}" required>

            <label for="type">Type de bloc *</label>
            <select id="type" name="type" required>
                {% FOR key, label IN blockTypes %}
                    <option value="{{ key }}" {% IF key == type %}selected{% ENDIF %}>{{ label }}</option>
                {% ENDFOR %}
            </select>

            <label for="content">Contenu</label>
            <textarea id="content" name="content" rows="5">{{ content }}</textarea>

            <label>
                <input type="checkbox" name="active" {% IF active %}checked{% ENDIF %}>
                Bloc actif (visible sur la page d'accueil)
            </label>
        </div>

        <!-- Options bouton -->
        <div id="button-options" class="form-section-panel" style="display: none; background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 10px; padding: 20px; margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Options du bouton</h3>
            <p style="color: var(--adm-text-muted);">Créez un bouton cliquable avec un lien vers une URL spécifique.</p>
            <label for="button_url">URL du bouton</label>
            <input type="url" id="button_url" name="button_url" value="{{ button_url }}">
            <label for="button_text">Texte du bouton</label>
            <input type="text" id="button_text" name="button_text" value="{{ button_text }}">
            <div style="display: flex; gap: 16px;">
                <div style="flex: 1;">
                    <label for="button_color">Couleur</label>
                    <select id="button_color" name="button_color">
                        {% FOR key, label IN colors %}
                            <option value="{{ key }}" {% IF key == button_color %}selected{% ENDIF %}>{{ label }}</option>
                        {% ENDFOR %}
                    </select>
                </div>
                <div style="flex: 1;">
                    <label for="button_size">Taille</label>
                    <select id="button_size" name="button_size">
                        {% FOR key, label IN sizes %}
                            <option value="{{ key }}" {% IF key == button_size %}selected{% ENDIF %}>{{ label }}</option>
                        {% ENDFOR %}
                    </select>
                </div>
            </div>
        </div>

        <!-- Options tableau -->
        <div id="table-options" class="form-section-panel" style="display: none; background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 10px; padding: 20px; margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Options du tableau</h3>
            <p style="color: var(--adm-text-muted);">Affichez des données structurées dans un tableau.</p>
            <label for="table_headers">En-têtes (séparés par des virgules)</label>
            <input type="text" id="table_headers" name="table_headers" value="{{ table_headers }}" placeholder="Nom, Email, Téléphone">
            <label for="table_rows">Lignes (JSON)</label>
            <textarea id="table_rows" name="table_rows" rows="5" placeholder="[['John', 'john@example.com', '0123456789']]">{{ table_rows }}</textarea>
        </div>

        <!-- Options formulaire -->
        <div id="form-options" class="form-section-panel" style="display: none; background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 10px; padding: 20px; margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Options du formulaire</h3>
            <label for="form_action">Action du formulaire</label>
            <input type="url" id="form_action" name="form_action" value="{{ form_action }}">
            <label for="form_method">Méthode</label>
            <select id="form_method" name="form_method">
                <option value="POST" {% IF is_post %}selected{% ENDIF %}>POST</option>
                <option value="GET" {% IF is_get %}selected{% ENDIF %}>GET</option>
            </select>
            <label for="form_fields">Champs (JSON)</label>
            <textarea id="form_fields" name="form_fields" rows="5">{{ form_fields }}</textarea>
        </div>

        <!-- Options image -->
        <div id="image-options" class="form-section-panel" style="display: none; background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 10px; padding: 20px; margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Options de l'image</h3>
            <label for="image_src">URL de l'image</label>
            <input type="url" id="image_src" name="image_src" value="{{ image_src }}">
            <label for="image_alt">Texte alternatif</label>
            <input type="text" id="image_alt" name="image_alt" value="{{ image_alt }}">
            <div style="display: flex; gap: 16px;">
                <div style="flex: 1;">
                    <label for="image_width">Largeur (px)</label>
                    <input type="number" id="image_width" name="image_width" value="{{ image_width }}">
                </div>
                <div style="flex: 1;">
                    <label for="image_height">Hauteur (px)</label>
                    <input type="number" id="image_height" name="image_height" value="{{ image_height }}">
                </div>
            </div>
        </div>

        <!-- Options derniers articles -->
        <div id="latest-articles-options" class="form-section-panel" style="display: none; background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 10px; padding: 20px; margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Derniers articles</h3>
            <p style="color: var(--adm-text-muted);">Affiche automatiquement les derniers articles du plugin Blog.</p>
            <label for="latest_articles_count">Nombre d'articles à afficher</label>
            <input type="number" min="1" max="6" id="latest_articles_count" name="latest_articles_count" value="{{ latest_articles_count }}">
        </div>

        <!-- Options sondages -->
        <div id="latest-sondages-options" class="form-section-panel" style="display: none; background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 10px; padding: 20px; margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Sondages</h3>
            <p style="color: var(--adm-text-muted);">Liste les sondages actifs si le plugin est installé.</p>
            <label for="latest_sondages_count">Nombre de sondages</label>
            <input type="number" min="1" max="4" id="latest_sondages_count" name="latest_sondages_count" value="{{ latest_sondages_count }}">
        </div>

        <!-- Options livre d'or -->
        <div id="guestbook-options" class="form-section-panel" style="display: none; background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 10px; padding: 20px; margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Livre d'or</h3>
            <label for="guestbook_button_text">Texte du bouton</label>
            <input type="text" id="guestbook_button_text" name="guestbook_button_text" value="{{ guestbook_button_text }}">
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="button">
                <i class="fa-regular fa-floppy-disk"></i> Enregistrer les modifications
            </button>
            <a href="{{ ROUTER.generate('admin-homebuilder') }}" class="button secondary">Annuler</a>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateOptions() {
        var type = document.getElementById('type').value;
        document.querySelectorAll('.form-section-panel').forEach(function(el) { el.style.display = 'none'; });
        var panels = {
            'button': 'button-options',
            'table': 'table-options',
            'form': 'form-options',
            'image': 'image-options',
            'latest_articles': 'latest-articles-options',
            'latest_sondages': 'latest-sondages-options',
            'guestbook_cta': 'guestbook-options'
        };
        if (panels[type]) {
            var el = document.getElementById(panels[type]);
            if (el) el.style.display = 'block';
        }
    }
    document.getElementById('type').addEventListener('change', updateOptions);
    updateOptions();
});
</script>
