<section class="module-card">
    <header>
        <i class="fa-solid fa-database"></i> {{Lang.configmanager-storage-title|default('Gestion du Stockage Agnostique')}}
    </header>

    <div class="storage-intro">
        <p>Gérez ici la flexibilité de vos données et vos infrastructures de bases de données.</p>
    </div>

    <div class="tabs-container" data-tab-key="storage">
        <ul class="tabs-header">
            <li class="default-tab" data-tab="connections">
                <i class="fa-solid fa-server"></i> Connexions SQL Disponibles
            </li>
            <li data-tab="components">
                <i class="fa-solid fa-layer-group"></i> Gestion par Composant
            </li>
            <li data-tab="info">
                <i class="fa-solid fa-circle-info"></i> Informations
            </li>
        </ul>

        <ul class="tabs">
            <!-- TAB 1 : Connexions SQL -->
            <li class="tab">
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
            <div class="msg error storage-msg-gap">
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Erreur de connexion :</strong> {{ connectionStatuses.default.message }}
            </div>
            {% elseif connectionStatuses.default %}
            <div class="msg success storage-msg-gap">
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
                        <div class="form-group db-fields-mysql {% if dbConfig.driver != 'mysql' %}evo-is-hidden{% endif %}">
                            <label>Hôte</label>
                            <input type="text" name="database[host]" value="{{dbConfig.host|default('localhost')}}">
                        </div>
                    </div>
                    <div class="db-fields-mysql {% if dbConfig.driver != 'mysql' %}evo-is-hidden{% endif %}">
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
                    <div class="db-fields-sqlite {% if dbConfig.driver != 'sqlite' %}evo-is-hidden{% endif %}">
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
                    <div class="form-row">
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
            <div class="msg error storage-msg-gap">
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Erreur de connexion ({{name}}) :</strong> {{ connectionStatuses[name].message }}
            </div>
            {% elseif connectionStatuses[name] %}
            <div class="msg success storage-msg-gap">
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
                        <div class="form-group db-fields-mysql {% if db.driver != 'mysql' %}evo-is-hidden{% endif %}">
                            <label>Hôte</label>
                            <input type="text" name="database[host]" value="{{db.host|default('localhost')}}">
                        </div>
                    </div>
                    <div class="db-fields-mysql {% if db.driver != 'mysql' %}evo-is-hidden{% endif %}">
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
                    <div class="db-fields-sqlite {% if db.driver != 'sqlite' %}evo-is-hidden{% endif %}">
                        <div class="form-group">
                            <label>Chemin du fichier (.sqlite)</label>
                            <input type="text" name="database[path]" value="{{db.path}}">
                        </div>
                    </div>
                    <div class="storage-actions-row">
                        <button type="submit" class="button primary">Mettre à jour {{name}}</button>
                        <button type="submit" name="delete_conn" value="1" class="button outline danger" onclick="return confirm('Supprimer cette connexion ?')">Supprimer</button>
                    </div>
                </div>
            </form>
        </div>
        {% endfor %}
                </div>
            </li>

            <!-- TAB 2 : Gestion par Composant -->
            <li class="tab">
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
                                <button type="button" class="button outline btn-migrate evo-is-hidden">
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
            </li>

            <!-- TAB 3 : Informations -->
            <li class="tab">
                <div class="storage-section storage-section-gap">
                    <h3><i class="fa-solid fa-circle-info"></i> Comment fonctionne le Stockage</h3>
                    <p class="section-desc">Le CMS peut stocker ses entités (Utilisateurs, Pages, Menus, Galerie, Redirections SEO…) soit dans des <strong>fichiers JSON locaux</strong> (<code>data/</code>), soit dans une <strong>base SQL</strong> (<strong>SQLite</strong>, <strong>MySQL</strong> ou <strong>PostgreSQL</strong>) configurée dans <code>data/env.json</code>.</p>

                    <div class="help-grid storage-section-gap">
                        <div class="storage-help">
                            <h4><i class="fa-solid fa-database"></i> 1. Connexions SQL</h4>
                            <p>La connexion <strong>Défaut</strong> est la base principale du site. Vous pouvez aussi déclarer des connexions secondaires nommées si vous voulez isoler un module sur une autre base.</p>
                        </div>
                        <div class="storage-help">
                            <h4><i class="fa-solid fa-layer-group"></i> 2. Affectation par composant</h4>
                            <p>Chaque composant possède sa propre cible : <code>JSON (local)</code> ou <code>Défaut (sql)</code>. C'est ici que vous choisissez quel composant lit/écrit dans JSON ou dans votre base SQL.</p>
                        </div>
                        <div class="storage-help">
                            <h4><i class="fa-solid fa-arrows-rotate"></i> 3. Migration sécurisée</h4>
                            <p>Lorsque vous changez la cible d'un composant dans le tableau, un bouton <strong>Migrer</strong> apparaît pour copier les enregistrements existants vers la nouvelle cible avant d'enregistrer.</p>
                        </div>
                    </div>

                    <h3><i class="fa-solid fa-book"></i> Exemples concrets de paramétrage</h3>
                    <div class="help-grid">
                        <div class="db-config-grid">
                            <strong class="storage-example-title">Exemple A — Site standard autonome (Recommandé)</strong>
                            <ul class="storage-example-list">
                                <li><strong>Connexion Défaut :</strong> Driver <code>SQLite</code>, chemin <code>/home/.../data/database.sqlite</code> (aucun serveur MySQL externe nécessaire).</li>
                                <li><strong>Composants principaux :</strong> <code>User</code>, <code>PageItem</code>, <code>MenuItem</code>, <code>ContactMessage</code>, <code>GalerieItem</code>, <code>Redirect</code> sur <code>Défaut (SQL)</code>.</li>
                                <li><strong>Stockage par défaut :</strong> <code>JSON</code> pour les petits réglages et caches légers.</li>
                            </ul>
                        </div>
                        <div class="db-config-grid">
                            <strong class="storage-example-title">Exemple B — Site à fort trafic (MySQL dédié)</strong>
                            <ul class="storage-example-list">
                                <li><strong>Connexion Défaut :</strong> Driver <code>MySQL</code> (<code>localhost</code>, base dédiée, utilisateur &amp; mot de passe).</li>
                                <li><strong>Procédure :</strong> 1) Enregistrer la connexion MySQL et vérifier le voyant vert, 2) Cliquer sur <em>Migrer</em> pour chaque composant souhaité dans l'onglet <em>Gestion par Composant</em>, 3) Enregistrer les affectations.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</section>

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
        panel.querySelectorAll('.db-fields-mysql').forEach(el => el.classList.toggle('evo-is-hidden', this.value !== 'mysql'));
        panel.querySelectorAll('.db-fields-sqlite').forEach(el => el.classList.toggle('evo-is-hidden', this.value !== 'sqlite'));
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
            btn.classList.add('evo-is-hidden');
        } else {
            btn.classList.remove('evo-is-hidden');
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
