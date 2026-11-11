<section class="module-card">
    <header>
        <i class="fa-solid fa-database"></i> {{Lang.configmanager-storage-title|default('Gestion du Stockage Agnostique')}}
    </header>

    <div class="storage-intro">
        <p>Gérez ici la flexibilité de vos données et vos infrastructures de bases de données.</p>
    </div>


    <hr class="section-divider">

    <!-- Gestion des Connexions -->
    <div class="storage-section">
        <h3><i class="fa-solid fa-server"></i> Connexions SQL Disponibles</h3>
        <p class="section-desc">Configurez vos accès aux bases de données externes (MySQL) ou locales (SQLite).</p>

        <div class="connection-tabs">
            <div class="tab-btn active" data-target="default">
                Défaut ({% if dbConfig.driver %}{{dbConfig.driver}}{% else %}Non configuré{% endif %})
                {% if connectionStatuses.default %}
                    <span class="status-dot {{ connectionStatuses.default.success ? 'status-online' : 'status-offline' }}" title="{{ connectionStatuses.default.message }}"></span>
                {% endif %}
            </div>
            {% for name, db in databases %}
            <div class="tab-btn" data-target="{{name|replace(':', '-')}}">
                {{name}} ({{db.driver}})
                {% if connectionStatuses[name] %}
                    <span class="status-dot {{ connectionStatuses[name].success ? 'status-online' : 'status-offline' }}" title="{{ connectionStatuses[name].message }}"></span>
                {% endif %}
            </div>
            {% endfor %}
            <div class="tab-btn btn-add-conn" data-target="add"><i class="fa-solid fa-plus"></i> Ajouter</div>
        </div>

        <!-- Panel Default -->
        <div id="panel-default" class="connection-panel active">
            {% if connectionStatuses.default and not connectionStatuses.default.success %}
            <div class="msg error" style="margin-bottom: 1rem;">
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Erreur de connexion :</strong> {{ connectionStatuses.default.message }}
            </div>
            {% elseif connectionStatuses.default %}
            <div class="msg success" style="margin-bottom: 1rem;">
                <i class="fa-solid fa-circle-check"></i> <strong>Connexion établie avec succès.</strong>
            </div>
            {% endif %}
            <form method="post" action="{{saveDatabaseUrl}}">
                {{SHOW.tokenField}}
                <input type="hidden" name="db_name" value="default">
                <div class="db-config-grid">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Driver</label>
                            <select name="database[driver]" class="db-driver-selector">
                                <option value="mysql" {% if dbConfig.driver == 'mysql' %}selected{% endif %}>MySQL</option>
                                <option value="sqlite" {% if dbConfig.driver == 'sqlite' %}selected{% endif %}>SQLite</option>
                                <option value="pgsql" {% if dbConfig.driver == 'pgsql' %}selected{% endif %}>PostgreSQL</option>
                            </select>
                        </div>
                        <div class="form-group db-fields-mysql" style="{% if dbConfig.driver != 'mysql' %}display:none{% endif %}">
                            <label>Hôte</label>
                            <input type="text" name="database[host]" value="{{dbConfig.host|default('localhost')}}">
                        </div>
                    </div>
                    <div class="db-fields-mysql" style="{% if dbConfig.driver != 'mysql' %}display:none{% endif %}">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Base de données</label>
                                <input type="text" name="database[dbname]" value="{{dbConfig.dbname}}">
                            </div>
                            <div class="form-group">
                                <label>Utilisateur</label>
                                <input type="text" name="database[user]" value="{{dbConfig.user}}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mot de passe</label>
                            <input type="password" name="database[password]" placeholder="Laisser vide pour ne pas modifier">
                        </div>
                    </div>
                    <div class="db-fields-sqlite" style="{% if dbConfig.driver != 'sqlite' %}display:none{% endif %}">
                        <div class="form-group">
                            <label>Chemin du fichier (.sqlite)</label>
                            <input type="text" name="database[path]" value="{{dbConfig.path}}" placeholder="ex: data/storage/custom.sqlite">
                        </div>
                    </div>
                    <button type="submit" class="button primary">Mettre à jour la connexion par défaut</button>
                </div>
            </form>
        </div>

        <!-- Panel Add -->
        <div id="panel-add" class="connection-panel">
            <form method="post" action="{{saveDatabaseUrl}}">
                {{SHOW.tokenField}}
                <div class="db-config-grid">
                    <div class="form-group">
                        <label>Identifiant unique de la connexion (ex: <strong>secondary</strong>)</label>
                        <input type="text" name="db_name" placeholder="Nom interne sans espaces" required>
                    </div>
                        <div class="form-group">
                            <label>Driver</label>
                            <select name="database[driver]" class="db-driver-selector">
                                <option value="mysql">MySQL</option>
                                <option value="pgsql">PostgreSQL</option>
                            </select>
                        </div>
                        <div class="form-group db-fields-mysql">
                            <label>Hôte</label>
                            <input type="text" name="database[host]" value="localhost">
                        </div>
                    </div>
                    <div class="db-fields-mysql">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Base de données</label>
                                <input type="text" name="database[dbname]">
                            </div>
                            <div class="form-group">
                                <label>Utilisateur</label>
                                <input type="text" name="database[user]">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mot de passe</label>
                            <input type="password" name="database[password]">
                        </div>
                    </div>
                    <!-- SQLite est géré automatiquement par plugin, pas besoin de le créer manuellement ici -->
                    <button type="submit" class="button success">Ajouter cette connexion SQL</button>
                </div>
            </form>
        </div>

        {% for name, db in databases %}
        <div id="panel-{{name|replace(':', '-')}}" class="connection-panel">
            {% if connectionStatuses[name] and not connectionStatuses[name].success %}
            <div class="msg error" style="margin-bottom: 1rem;">
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Erreur de connexion ({{name}}) :</strong> {{ connectionStatuses[name].message }}
            </div>
            {% elseif connectionStatuses[name] %}
            <div class="msg success" style="margin-bottom: 1rem;">
                <i class="fa-solid fa-circle-check"></i> <strong>Connexion '{{name}}' établie avec succès.</strong>
            </div>
            {% endif %}
            <form method="post" action="{{saveDatabaseUrl}}">
                {{SHOW.tokenField}}
                <input type="hidden" name="db_name" value="{{name}}">
                <div class="db-config-grid">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Driver</label>
                            <select name="database[driver]" class="db-driver-selector">
                                <option value="mysql" {% if db.driver == 'mysql' %}selected{% endif %}>MySQL</option>
                                <option value="sqlite" {% if db.driver == 'sqlite' %}selected{% endif %}>SQLite</option>
                                <option value="pgsql" {% if db.driver == 'pgsql' %}selected{% endif %}>PostgreSQL</option>
                            </select>
                        </div>
                        <div class="form-group db-fields-mysql" style="{% if db.driver != 'mysql' %}display:none{% endif %}">
                            <label>Hôte</label>
                            <input type="text" name="database[host]" value="{{db.host|default('localhost')}}">
                        </div>
                    </div>
                    <div class="db-fields-mysql" style="{% if db.driver != 'mysql' %}display:none{% endif %}">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Base de données</label>
                                <input type="text" name="database[dbname]" value="{{db.dbname}}">
                            </div>
                            <div class="form-group">
                                <label>Utilisateur</label>
                                <input type="text" name="database[user]" value="{{db.user}}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mot de passe</label>
                            <input type="password" name="database[password]" placeholder="Laisser vide pour ne pas modifier">
                        </div>
                    </div>
                    <div class="db-fields-sqlite" style="{% if db.driver != 'sqlite' %}display:none{% endif %}">
                        <div class="form-group">
                            <label>Chemin du fichier (.sqlite)</label>
                            <input type="text" name="database[path]" value="{{db.path}}">
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="button primary">Mettre à jour {{name}}</button>
                        <button type="submit" name="delete_conn" value="1" class="button outline danger" onclick="return confirm('Supprimer cette connexion ?')">Supprimer</button>
                    </div>
                </div>
            </form>
        </div>
        {% endfor %}
    </div>

    <hr class="section-divider">

    <!-- Gestion par Composant -->
    <div class="storage-section">
        <h3><i class="fa-solid fa-layer-group"></i> Gestion par Composant</h3>
        <p class="section-desc">Définissez le type de stockage et la base de données cible pour chaque module.</p>

        <form method="post" action="{{saveUrl}}">
            {{SHOW.tokenField}}
            
            <div class="table-container">
                <table class="storage-table">
                    <thead>
                        <tr>
                            <th>Composant</th>
                            <th>État Actuel</th>
                            <th>Cible de Stockage</th>
                            <th>Actions de Migration</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for key, model in models %}
                        {% set current = storageConfig[key]|default(storageConfig.default)|default('json') %}
                        <tr data-model="{{key}}">
                            <td>
                                <div class="model-info">
                                    <span class="model-label">{{model.label}}</span>
                                    <code class="model-class">{{model.class}}</code>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-{{current == 'json' ? 'json' : 'sql'}}" data-connection="{{current}}">
                                    {{current|upper}}
                                </span>
                            </td>
                            <td>
                                <select name="storage[{{key}}]" class="storage-selector">
                                    {% if model.supports_json %}
                                    <optgroup label="Fichiers">
                                        <option value="json" {% if current == 'json' %}selected{% endif %}>JSON (local)</option>
                                    </optgroup>
                                    {% endif %}

                                    {% if model.valid_connections %}
                                    <optgroup label="Bases de Données">
                                        {% for connId, connLabel in model.valid_connections %}
                                            <option value="{{connId}}" {% if current == connId %}selected{% endif %}>{{connLabel}}</option>
                                        {% endfor %}
                                    </optgroup>
                                    {% endif %}
                                </select>
                            </td>
                            <td class="migration-cell">
                                <button type="button" class="button outline btn-migrate" style="display:none">
                                    <i class="fa-solid fa-arrows-rotate"></i> Migrer
                                </button>
                                <div class="migration-status"></div>
                            </td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>

            <div class="config-actions">
                <button type="submit" class="button success large">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer les affectations de stockage
                </button>
            </div>
        </form>
    </div>

    <div class="storage-help">
        <h3><i class="fa-solid fa-circle-info"></i> Guide Multi-Driver</h3>
        <div class="help-grid">
            <div class="help-item">
                <strong>Granularité</strong>
                <p>Vous pouvez faire cohabiter MySQL pour vos utilisateurs et SQLite pour vos sondages.</p>
            </div>
            <div class="help-item">
                <strong>Connexions</strong>
                <p>Créez d'abord vos connexions SQL, puis assignez-les aux composants souhaités.</p>
            </div>
        </div>
        <p class="help-warning"><strong>Attention :</strong> Assurez-vous que le driver SQL cible est bien configuré (testez la connexion) avant de migrer des données sensibles.</p>
    </div>
</section>

<style>
.storage-section { margin-bottom: 2.5rem; }
.storage-section h3 { margin-bottom: 0.5rem; font-size: 1.25em; display: flex; align-items: center; gap: 10px; }
.section-desc { color: #666; margin-bottom: 1.5rem; font-size: 0.95em; }
.section-divider { border: 0; border-top: 1px solid #eee; margin: 2rem 0; }

/* Connections Manager */
.connection-tabs { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 1rem; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
.tab-btn { padding: 7px 14px; border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; border-radius: 8px; cursor: pointer; font-size: 0.88rem; font-weight: 600; }
.tab-btn.active { background: var(--primary-color, #1f62a9); color: #ffffff; border-color: var(--primary-color, #1f62a9); }
.tab-btn:hover:not(.active) { background: #f1f5f9; color: #0f172a; }
.btn-add-conn { background: #ecfdf5; color: #15803d; border-color: #bbf7d0; }

.connection-panel { display: none; }
.connection-panel.active { display: block; animation: fadeIn 0.25s ease; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

/* DB Config Form */
.db-config-grid { background: #f8fafc; padding: 1.5rem; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 820px; }
.form-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
.form-group { flex: 1; display: flex; flex-direction: column; gap: 5px; margin-bottom: 1rem; }
.form-group label { font-weight: 600; font-size: 0.85rem; color: #334155; }
.form-group input, .form-group select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.92rem; }

/* Model Table */
.badge { padding: 4px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
.badge-json { background: #475569; color: white; }
.badge-sql { background: var(--primary-color, #1f62a9); color: white; }

.storage-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.storage-table th { background: #f8fafc; padding: 12px; font-size: 0.85rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.storage-table td { padding: 14px 12px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }

.button.outline { background: transparent; border: 1px solid var(--primary-color, #1f62a9); color: var(--primary-color, #1f62a9); font-size: 0.82rem; }
.button.outline:hover { background: var(--primary-color, #1f62a9); color: white; }

.migration-status { font-size: 0.8em; margin-top: 5px; font-weight: 500; }
.migration-status.success { color: #16a34a; }
.migration-status.error { color: #dc2626; }

.storage-help { background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid var(--success-color, #16a34a); border-radius: 12px; padding: 1.5rem; }
.help-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1rem; }
.help-item strong { color: #15803d; }

.status-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-left: 5px; }
.status-online { background-color: #16a34a; box-shadow: 0 0 5px rgba(22, 163, 74, 0.5); }
.status-offline { background-color: #dc2626; box-shadow: 0 0 5px rgba(220, 38, 38, 0.5); }

.migration-loading::after { content: "..."; animation: dots 1.5s infinite; }
@keyframes dots { 0% { content: "."; } 33% { content: ".."; } 66% { content: "..."; } }
</style>

<script>
// Tabs Management avec mémorisation de l'onglet actif
(function () {
    const STORAGE_KEY = 'adm_tab:' + window.location.pathname + ':storage_conn';
    const tabBtns = document.querySelectorAll('.connection-tabs .tab-btn');
    const panels = document.querySelectorAll('.connection-panel');

    function activateConnTab(target, persist) {
        const panel = document.getElementById('panel-' + target);
        if (!panel) return false;
        tabBtns.forEach(b => b.classList.toggle('active', b.dataset.target === target));
        panels.forEach(p => p.classList.remove('active'));
        panel.classList.add('active');
        if (persist) {
            try { sessionStorage.setItem(STORAGE_KEY, target); } catch (e) {}
        }
        return true;
    }

    try {
        const saved = sessionStorage.getItem(STORAGE_KEY);
        if (saved) activateConnTab(saved, false);
    } catch (e) {}

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const target = this.dataset.target || (this.classList.contains('btn-add-conn') ? 'add' : '');
            if (target) activateConnTab(target, true);
        });
    });
})();

// DB Driver Fields Toggle
document.querySelectorAll('.db-driver-selector').forEach(select => {
    select.addEventListener('change', function() {
        const panel = this.closest('.connection-panel');
        panel.querySelector('.db-fields-mysql').style.display = (this.value === 'mysql') ? 'block' : 'none';
        panel.querySelector('.db-fields-sqlite').style.display = (this.value === 'sqlite') ? 'block' : 'none';
    });
});

// Migration Sync
document.querySelectorAll('.storage-selector').forEach(select => {
    select.addEventListener('change', function() {
        const row = this.closest('tr');
        const btn = row.querySelector('.btn-migrate');
        const badge = row.querySelector('.badge');
        const currentId = badge.dataset.connection; // "json", "default", or custom conn name
        const targetId = this.value; // same
        
        const currentDriver = currentId === 'json' ? 'json' : 'sql';
        const targetDriver = (targetId === 'json') ? 'json' : 'sql';

        if (targetId === currentId) {
            btn.style.display = 'none';
        } else {
            btn.style.display = 'inline-block';
            btn.dataset.targetId = targetId;
            btn.dataset.sourceId = currentId;
            
            // On simplifie pour l'UI de migration (json <-> sql)
            // Mais le code PHP saura utiliser le bon nom de connexion
            const targetLabel = targetId.toUpperCase();
            btn.innerHTML = `<i class="fa-solid fa-arrows-rotate"></i> Vers ${targetLabel}`;
        }
    });
    // Init
    select.dispatchEvent(new Event('change'));
});

// Migration Async
document.querySelectorAll('.btn-migrate').forEach(button => {
    button.addEventListener('click', async function() {
        const row = this.closest('tr');
        const model = row.dataset.model;
        const targetId = this.dataset.targetId;
        const sourceId = this.dataset.sourceId;
        const statusDiv = row.querySelector('.migration-status');
        
        if (!confirm(`Migrer "${model}" de ${sourceId.toUpperCase()} vers ${targetId.toUpperCase()} ?`)) return;

        this.disabled = true;
        this.classList.add('migration-loading');
        statusDiv.textContent = 'En cours...';
        statusDiv.className = 'migration-status';

        try {
            const formData = new FormData();
            formData.append('model', model);
            formData.append('target', targetId === 'json' ? 'json' : 'sql');
            formData.append('target_connection', targetId === 'json' ? '' : targetId);
            formData.append('source', sourceId === 'json' ? 'json' : 'sql');
            formData.append('source_connection', sourceId === 'json' ? '' : sourceId);
            formData.append('token', '{{token}}');

            const response = await fetch('{{migrateUrl}}', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                statusDiv.textContent = `Terminé (${result.count} entrées)`;
                statusDiv.classList.add('success');
                // Fake refresh
                const badge = row.querySelector('.badge');
                badge.textContent = targetId.toUpperCase();
                badge.dataset.connection = targetId;
                badge.className = `badge badge-${targetId === 'json' ? 'json' : 'sql'}`;
                row.querySelector('.storage-selector').dispatchEvent(new Event('change'));
            } else {
                statusDiv.textContent = 'Erreur migration';
                statusDiv.classList.add('error');
            }
        } catch (e) {
            statusDiv.textContent = 'Erreur critique';
            statusDiv.classList.add('error');
        } finally {
            this.disabled = false;
            this.classList.remove('migration-loading');
        }
    });
});
</script>
