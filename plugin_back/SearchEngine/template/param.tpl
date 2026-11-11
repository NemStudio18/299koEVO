<div class="plugin-config-premium">
    <div class="header-section">
        <h2><i class="fa-solid fa-magnifying-glass"></i> Configuration du Moteur de Recherche</h2>
        <p class="subtitle">Optimisez la pertinence et les performances de votre moteur de recherche.</p>
    </div>

    <form action="{ ROUTER.generate('admin-SearchEngine-save') }" method="post" class="config-form-grid">
        <div class="config-card">
            <div class="card-header">
                <h3><i class="fa-solid fa-location-crosshairs"></i> Géolocalisation</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="radius_default">Rayon par défaut (km) <i class="fa-solid fa-circle-info help-icon" title="Rayon de recherche utilisé pour les fonctions 'autour de moi' s'il n'est pas spécifié."></i></label>
                    <input type="number" step="0.5" min="0.5" max="100" name="radius_default" id="radius_default" value="{{config.radius_default|default('5.0')}}" required>
                </div>
            </div>
        </div>

        <div class="config-card">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-line"></i> Pertinence & Scoring</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="weight_name">Poids du Nom <i class="fa-solid fa-circle-info help-icon" title="Importance accordée au nom dans le score de pertinence."></i></label>
                    <input type="number" min="1" max="100" name="weight_name" id="weight_name" value="{{config.weight_name|default('10')}}" required>
                </div>
                <div class="form-group">
                    <label for="weight_description">Poids de la Description <i class="fa-solid fa-circle-info help-icon" title="Importance accordée à la description dans le score de pertinence."></i></label>
                    <input type="number" min="1" max="100" name="weight_description" id="weight_description" value="{{config.weight_description|default('5')}}" required>
                </div>
                <div class="form-group">
                    <label for="weight_brand">Poids de la Marque <i class="fa-solid fa-circle-info help-icon" title="Importance accordée à la marque/enseigne dans le score de pertinence."></i></label>
                    <input type="number" min="1" max="100" name="weight_brand" id="weight_brand" value="{{config.weight_brand|default('2')}}" required>
                </div>
            </div>
        </div>

        <div class="config-card">
            <div class="card-header">
                <h3><i class="fa-solid fa-list-ol"></i> Affichage</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="items_per_page">Résultats par page <i class="fa-solid fa-circle-info help-icon" title="Nombre de résultats affichés par page dans la liste de recherche."></i></label>
                    <input type="number" min="5" max="100" name="items_per_page" id="items_per_page" value="{{config.items_per_page|default('20')}}" required>
                </div>
            </div>
        </div>

        <div class="config-card">
            <div class="card-header">
                <h3><i class="fa-solid fa-gears"></i> Avancé</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="priority">Priorité d'exécution</label>
                    <input type="number" name="priority" id="priority" value="{{config.priority|default('10')}}">
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="debug" id="debug" {% if config.debug %}checked{% endif %}>
                    <label for="debug">Mode Débogage</label>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Enregistrer la configuration</button>
        </div>
    </form>

    <div class="danger-zone">
        <div class="card-header">
            <h3><i class="fa-solid fa-bolt"></i> Maintenance du Cache</h3>
        </div>
        <div class="card-body">
            <p>Le moteur de recherche utilise un cache JSON pour les agrégations (catégories, villes) afin d'améliorer les performances.</p>
            <form action="{ ROUTER.generate('admin-SearchEngine-clear-cache') }" method="post" onsubmit="return confirm('Voulez-vous vraiment vider le cache de recherche ?');">
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i> Vider le cache de recherche</button>
            </form>
        </div>
    </div>
</div>

<style>
.plugin-config-premium { max-width: 1000px; margin: 0 auto; padding: 20px; font-family: 'Inter', system-ui, sans-serif; }
.header-section { margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
.header-section h2 { color: #2c3e50; font-size: 1.8rem; display: flex; align-items: center; gap: 12px; }
.header-section h2 i { color: #3498db; }
.subtitle { color: #7f8c8d; margin-top: 5px; }

.config-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 25px; margin-bottom: 30px; }
.config-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eef2f7; overflow: hidden; display: flex; flex-direction: column; }
.card-header { background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #eef2f7; }
.card-header h3 { margin: 0; font-size: 1.1rem; color: #334155; display: flex; align-items: center; gap: 10px; }
.card-header h3 i { color: #64748b; font-size: 0.9em; }

.card-body { padding: 20px; flex-grow: 1; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #475569; font-size: 0.95rem; }
.form-group input[type="number"], .form-group input[type="text"] { width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; transition: border-color 0.2s, box-shadow 0.2s; }
.form-group input:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15); }

.help-icon { font-size: 0.8em; color: #94a3b8; cursor: help; margin-left: 4px; }
.checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 20px; }
.checkbox-group input { width: 18px; height: 18px; cursor: pointer; }

.form-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; padding: 20px 0; border-top: 1px solid #eee; }
.btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 10px; border: none; transition: transform 0.1s, filter 0.2s; }
.btn:hover { filter: brightness(1.1); }
.btn:active { transform: scale(0.98); }
.btn-primary { background: #3498db; color: white; }
.btn-danger { background: #e74c3c; color: white; }

.danger-zone { background: #fff5f5; border: 1px solid #feb2b2; border-radius: 12px; padding: 20px; margin-top: 20px; }
.danger-zone h3 { color: #c53030; }
.danger-zone p { color: #742a2a; font-size: 0.9rem; margin-bottom: 15px; }
</style>
