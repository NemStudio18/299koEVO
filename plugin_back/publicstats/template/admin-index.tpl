<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-chart-pie"></i> Configuration des Statistiques Publiques</h2>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fa-solid fa-info-circle"></i> Cette page vous permet de choisir quelles statistiques sont
            affichées sur la page publique.
        </div>

        <form action="{{saveUrl}}" method="post">
            <input type="hidden" name="csrf_token" value="{{csrfToken}}">

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="show_general" name="show_general" {% if config.show_general %}checked{% endif %}>
                    <label class="form-check-label" for="show_general">Afficher les informations générales (Mise à jour,
                        Plugins)</label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="show_users" name="show_users" {% if config.show_users %}checked{% endif %}>
                    <label class="form-check-label" for="show_users">Afficher les statistiques utilisateurs (Âge,
                        Sexe)</label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="show_districts" name="show_districts" {% if config.show_districts %}checked{% endif %}>
                    <label class="form-check-label" for="show_districts">Afficher la répartition par quartiers et
                        villes</label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="show_polls" name="show_polls" {% if config.show_polls %}checked{% endif %}>
                    <label class="form-check-label" for="show_polls">Afficher les statistiques des sondages</label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="show_forum" name="show_forum" {% if config.show_forum %}checked{% endif %}>
                    <label class="form-check-label" for="show_forum">Afficher les statistiques du forum</label>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <a href="{{publicUrl}}" target="_blank" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-external-link-alt"></i> Voir la page publique
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Enregistrer la configuration
                </button>
            </div>
        </form>
    </div>
</div>