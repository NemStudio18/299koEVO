<div class="plugin-config">
    <h2>Configuration Search UI</h2>
    <p>Ce plugin gère l'affichage public du moteur de recherche.</p>
    
    <form action="{{ ROUTER.generate('admin-SearchUi-home') }}" method="post">
        <div class="form-group">
            <label>
                <input type="checkbox" name="enableSimulation" value="1" {% if enableSimulation == '1' %}checked{% endif %}>
                Activer le mode simulation (barre d'outils admin)
            </label>
            <p class="help-text">Décochez cette option pour masquer la barre "TEST MODE" sur le site public.</p>
        </div>
        
        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit" class="button button-primary">Enregistrer la configuration</button>
        </div>
    </form>
</div>
