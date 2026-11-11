<div class="admin-config">
    <h3>Configuration SearchMaps</h3>
    <form action="{{ router.generate('admin-SearchMaps-save') }}" method="POST">
        <input type="hidden" name="_csrf" value="{{ csrfToken }}">
        
        <div class="field">
            <label>Zoom par défaut</label>
            <input type="number" name="zoom_default" value="{{ config.zoom_default|default(13) }}" min="1" max="21">
        </div>

        <div class="field">
            <label>Centre de la carte (Latitude)</label>
            <input type="text" name="center_lat" value="{{ config.center_lat|default('47.0833') }}">
        </div>

        <div class="field">
            <label>Centre de la carte (Longitude)</label>
            <input type="text" name="center_lon" value="{{ config.center_lon|default('2.4') }}">
        </div>

        <div class="field">
            <label>Priorité d'affichage</label>
            <input type="number" name="priority" value="{{ config.priority|default(1) }}">
        </div>

        <div class="field">
            <label>
                <input type="checkbox" name="debug" {{ config.debug ? 'checked' : '' }}> Activer le mode debug
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="button primary">Enregistrer la configuration</button>
        </div>
    </form>
</div>

<style>
.admin-config { padding: 1rem; background: var(--bg-card); border-radius: 8px; }
.field { margin-bottom: 1rem; }
.field label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
.field input[type="number"], .field input[type="text"] { width: 100%; max-width: 400px; }
.form-actions { margin-top: 2rem; }
</style>
