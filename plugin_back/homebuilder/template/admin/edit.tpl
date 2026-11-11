{# Administration - Modifier un bloc #}

<div class="admin-form">
    <div class="admin-header">
        <h1>Modifier le bloc</h1>
        <a href="{{ ROUTER.generate("admin-homebuilder") }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Retour
        </a>
    </div>

    <form method="POST" action="{{ edit_send_link }}" class="block-form">
        <div class="form-section">
            <h2>Informations générales</h2>
            
            <div class="form-group">
                <label for="title">Titre du bloc *</label>
                <input type="text" id="title" name="title" value="{{ title }}" required>
            </div>
            
            <div class="form-group">
                <label for="type">Type de bloc *</label>
                <select id="type" name="type" required>
                    {% FOR key, label IN blockTypes %}
                        <option value="{{ key }}" {% IF key == type %}selected{% ENDIF %}>{{ label }}</option>
                    {% ENDFOR %}
                </select>
            </div>
            
            <div class="form-group">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" rows="5">{{ content }}</textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="active" {% IF active %}checked{% ENDIF %}>
                    Bloc actif
                </label>
            </div>
        </div>

        <!-- Options spécifiques selon le type -->
        <div id="button-options" class="form-section" style="display: none;">
            <h2>Options du bouton</h2>
            <p class="form-description">Créez un bouton cliquable avec un lien vers une URL spécifique.</p>
            
            <div class="form-group">
                <label for="button_url">URL du bouton</label>
                <input type="url" id="button_url" name="button_url" value="{{ button_url }}">
            </div>
            
            <div class="form-group">
                <label for="button_text">Texte du bouton</label>
                <input type="text" id="button_text" name="button_text" value="{{ button_text }}">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="button_color">Couleur</label>
                    <select id="button_color" name="button_color">
                        {% FOR key, label IN colors %}
                            <option value="{{ key }}" {% IF key == button_color %}selected{% ENDIF %}>{{ label }}</option>
                        {% ENDFOR %}
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="button_size">Taille</label>
                    <select id="button_size" name="button_size">
                        {% FOR key, label IN sizes %}
                            <option value="{{ key }}" {% IF key == button_size %}selected{% ENDIF %}>{{ label }}</option>
                        {% ENDFOR %}
                    </select>
                </div>
            </div>
        </div>

        <div id="table-options" class="form-section" style="display: none;">
            <h2>Options du tableau</h2>
            <p class="form-description">Affichez des données structurées dans un tableau avec en-têtes et lignes personnalisables.</p>
            
            <div class="form-group">
                <label for="table_headers">En-têtes (séparés par des virgules)</label>
                <input type="text" id="table_headers" name="table_headers" value="{{ table_headers }}" placeholder="Nom, Email, Téléphone">
            </div>
            
            <div class="form-group">
                <label for="table_rows">Lignes (JSON)</label>
                <textarea id="table_rows" name="table_rows" rows="5" placeholder="[['John', 'john@example.com', '0123456789'], ['Jane', 'jane@example.com', '0987654321']]">{{ table_rows }}</textarea>
            </div>
        </div>

        <div id="form-options" class="form-section" style="display: none;">
            <h2>Options du formulaire</h2>
            <p class="form-description">Créez un formulaire personnalisable avec des champs de différents types (texte, email, etc.).</p>
            
            <div class="form-group">
                <label for="form_action">Action du formulaire</label>
                <input type="url" id="form_action" name="form_action" value="{{ form_action }}">
            </div>
            
            <div class="form-group">
                <label for="form_method">Méthode</label>
                <select id="form_method" name="form_method">
                    <option value="POST" {% IF is_post %}selected{% ENDIF %}>POST</option>
                    <option value="GET" {% IF is_get %}selected{% ENDIF %}>GET</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="form_fields">Champs (JSON)</label>
                <textarea id="form_fields" name="form_fields" rows="5" placeholder="[{'type': 'text', 'name': 'name', 'label': 'Nom', 'required': true}, {'type': 'email', 'name': 'email', 'label': 'Email', 'required': true}]">{{ form_fields }}</textarea>
            </div>
        </div>

        <div id="image-options" class="form-section" style="display: none;">
            <h2>Options de l'image</h2>
            <p class="form-description">Affichez une image avec des dimensions et un texte alternatif personnalisables.</p>
            
            <div class="form-group">
                <label for="image_src">URL de l'image</label>
                <input type="url" id="image_src" name="image_src" value="{{ image_src }}">
            </div>
            
            <div class="form-group">
                <label for="image_alt">Texte alternatif</label>
                <input type="text" id="image_alt" name="image_alt" value="{{ image_alt }}">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="image_width">Largeur (px)</label>
                    <input type="number" id="image_width" name="image_width" value="{{ image_width }}">
                </div>
                
                <div class="form-group">
                    <label for="image_height">Hauteur (px)</label>
                    <input type="number" id="image_height" name="image_height" value="{{ image_height }}">
                </div>
            </div>
        </div>

        <div id="latest-articles-options" class="form-section" style="display: none;">
            <h2>Derniers articles</h2>
            <p class="form-description">Affiche automatiquement les derniers articles du plugin Blog.</p>
            <div class="form-group">
                <label for="latest_articles_count">Nombre d'articles</label>
                <input type="number" min="1" max="6" id="latest_articles_count" name="latest_articles_count" value="{{ latest_articles_count }}">
            </div>
        </div>

        <div id="latest-sondages-options" class="form-section" style="display: none;">
            <h2>Sondages</h2>
            <p class="form-description">Liste les sondages actifs si le plugin est installé.</p>
            <div class="form-group">
                <label for="latest_sondages_count">Nombre de sondages</label>
                <input type="number" min="1" max="4" id="latest_sondages_count" name="latest_sondages_count" value="{{ latest_sondages_count }}">
            </div>
        </div>

        <div id="guestbook-options" class="form-section" style="display: none;">
            <h2>Livre d'or</h2>
            <p class="form-description">Affiche un bouton vers le plugin Livre d'or.</p>
            <div class="form-group">
                <label for="guestbook_button_text">Texte du bouton</label>
                <input type="text" id="guestbook_button_text" name="guestbook_button_text" value="{{ guestbook_button_text }}">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Modifier le bloc
            </button>
            <a href="{{ ROUTER.generate("admin-homebuilder") }}" class="btn btn-secondary">
                Annuler
            </a>
        </div>
    </form>
</div>

<style>
.admin-form {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.admin-header h1 {
    margin: 0;
    color: #333;
}

.form-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.form-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    font-size: 1.3em;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: flex;
    gap: 20px;
}

.form-row .form-group {
    flex: 1;
}

label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

input[type="text"],
input[type="url"],
input[type="number"],
select,
textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1em;
    transition: border-color 0.3s ease;
}

input[type="text"]:focus,
input[type="url"]:focus,
input[type="number"]:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

textarea {
    resize: vertical;
    min-height: 100px;
}

input[type="checkbox"] {
    margin-right: 8px;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
    font-size: 1em;
    font-weight: bold;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }

.btn:hover {
    opacity: 0.8;
    transform: translateY(-1px);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Afficher/masquer les options selon le type de bloc
    document.getElementById('type').addEventListener('change', function() {
        var type = this.value;
        
        // Masquer toutes les options
        document.querySelectorAll('.form-section[id$="-options"]').forEach(function(el) {
            el.style.display = 'none';
        });
        
        // Afficher les options correspondantes
        if (type === "button") {
            document.getElementById('button-options').style.display = 'block';
        } else if (type === "table") {
            document.getElementById('table-options').style.display = 'block';
        } else if (type === "form") {
            document.getElementById('form-options').style.display = 'block';
        } else if (type === "image") {
            document.getElementById('image-options').style.display = 'block';
        } else if (type === "latest_articles") {
            var el = document.getElementById('latest-articles-options');
            if (el) { el.style.display = 'block'; }
        } else if (type === "latest_sondages") {
            var pollEl = document.getElementById('latest-sondages-options');
            if (pollEl) { pollEl.style.display = 'block'; }
        } else if (type === "guestbook_cta") {
            var guestEl = document.getElementById('guestbook-options');
            if (guestEl) { guestEl.style.display = 'block'; }
        }
    });
    
    // Déclencher le changement au chargement
    document.getElementById('type').dispatchEvent(new Event('change'));
});
</script> 
