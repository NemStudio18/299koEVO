<section class="module-card">
    <header><i class="fa-solid fa-trophy"></i> Configuration Progression</header>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem;">
        <!-- Configuration des points -->
        <div>
            <h3><i class="fa-solid fa-coins"></i> Points par action</h3>
            <form method="post" action="{{linkSave}}">
                {{ SHOW.tokenField }}
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <label for="points_contribution">Contribution proposée</label>
                        </td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <input type="number" id="points_contribution" name="points_contribution" 
                                   value="{{config.points_contribution}}" min="0" style="width: 100px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <label for="points_contribution_accepted">Contribution acceptée</label>
                        </td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <input type="number" id="points_contribution_accepted" name="points_contribution_accepted" 
                                   value="{{config.points_contribution_accepted}}" min="0" style="width: 100px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <label for="points_edit_poi">Édition POI</label>
                        </td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <input type="number" id="points_edit_poi" name="points_edit_poi" 
                                   value="{{config.points_edit_poi}}" min="0" style="width: 100px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <label for="points_edit_poi_accepted">Édition POI acceptée</label>
                        </td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <input type="number" id="points_edit_poi_accepted" name="points_edit_poi_accepted" 
                                   value="{{config.points_edit_poi_accepted}}" min="0" style="width: 100px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <label for="points_claim_poi">Revendication POI</label>
                        </td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <input type="number" id="points_claim_poi" name="points_claim_poi" 
                                   value="{{config.points_claim_poi}}" min="0" style="width: 100px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <label for="points_claim_poi_validated">Revendication validée</label>
                        </td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <input type="number" id="points_claim_poi_validated" name="points_claim_poi_validated" 
                                   value="{{config.points_claim_poi_validated}}" min="0" style="width: 100px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <label for="points_login_daily">Connexion quotidienne</label>
                        </td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <input type="number" id="points_login_daily" name="points_login_daily" 
                                   value="{{config.points_login_daily}}" min="0" style="width: 100px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <label for="points_first_contribution">Première contribution</label>
                        </td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <input type="number" id="points_first_contribution" name="points_first_contribution" 
                                   value="{{config.points_first_contribution}}" min="0" style="width: 100px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;" colspan="2">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="public_leaderboard" value="1" 
                                       {% IF config.public_leaderboard %}checked{% ENDIF %}>
                                <span>Rendre le leaderboard accessible côté public</span>
                            </label>
                            <small style="display: block; margin-top: 0.25rem; color: #6c757d;">
                                Si activé, le leaderboard sera accessible à l'adresse : <code>/gamification/leaderboard</code>
                            </small>
                        </td>
                    </tr>
                </table>
                <p style="margin-top: 1rem;">
                    <button type="submit" class="button primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
                </p>
            </form>
            
            <!-- Section recalcul historique -->
            <div style="margin-top: 2rem; padding: 1.5rem; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #856404;"><i class="fa-solid fa-calculator"></i> Recalcul des points historiques</h3>
                <p style="color: #856404; margin-bottom: 1rem;">
                    Cette fonction permet de recalculer les points de progression pour tous les utilisateurs en fonction de leurs contributions historiques (POIs possédés, propositions approuvées, photos, liens, tags, descriptions).
                </p>
                <p style="color: #856404; margin-bottom: 1rem;">
                    <strong>Attention :</strong> Cette opération peut prendre du temps selon le nombre d'utilisateurs. Les points seront ajoutés avec la raison "migration_initiale" pour éviter les doublons.
                </p>
                <a href="{{linkRecalculate}}" class="button" style="background: #ffc107; color: #856404; border: none;">
                    <i class="fa-solid fa-calculator"></i> Lancer le recalcul
                </a>
            </div>
        </div>
        
        <!-- Gestion des paliers -->
        <div>
            <h3><i class="fa-solid fa-layer-group"></i> Paliers et badges</h3>
            <div style="max-height: 600px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">Nom</th>
                            <th style="padding: 0.75rem; text-align: right; border-bottom: 2px solid #ddd;">Points</th>
                            <th style="padding: 0.75rem; text-align: center; border-bottom: 2px solid #ddd;">Badge</th>
                            <th style="padding: 0.75rem; text-align: center; border-bottom: 2px solid #ddd;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for palier in paliers %}
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 0.75rem;">
                                <strong>{{palier.name}}</strong>
                            </td>
                            <td style="padding: 0.75rem; text-align: right;">
                                {{palier.points_required}}
                            </td>
                            <td style="padding: 0.75rem; text-align: center;">
                                <span style="display: inline-block; padding: 0.25rem 0.5rem; background: {{palier.badge_color}}; color: white; border-radius: 4px;">
                                    <i class="fa-solid {{palier.badge_icon}}"></i> {{palier.badge_name}}
                                </span>
                            </td>
                            <td style="padding: 0.75rem; text-align: center;">
                                <button type="button" class="button small" onclick="editPalier({{palier.id}}, '{{palier.name}}', {{palier.points_required}}, '{{palier.badge_name}}', '{{palier.badge_icon}}', '{{palier.badge_color}}')">
                                    <i class="fa-solid fa-edit"></i>
                                </button>
                                {% if palier.points_required > 0 %}
                                <a href="{{palier.delete_url}}" class="button small" onclick="return confirm('Supprimer ce palier ?')" style="background: #e74c3c;">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                                {% endif %}
                            </td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            
            <button type="button" class="button" onclick="showAddPalierForm()" style="margin-top: 1rem;">
                <i class="fa-solid fa-plus"></i> Ajouter un palier
            </button>
        </div>
    </div>
</section>

<!-- Formulaire modal pour ajouter/modifier un palier -->
<div id="palierModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h3 id="modalTitle">Ajouter un palier</h3>
        <form method="post" action="{{linkPalierSave}}" id="palierForm">
            {{ SHOW.tokenField }}
            <input type="hidden" id="palier_id" name="id" value="">
            <p>
                <label for="palier_name">Nom du palier</label>
                <input type="text" id="palier_name" name="name" required style="width: 100%;">
            </p>
            <p>
                <label for="palier_points_required">Points requis</label>
                <input type="number" id="palier_points_required" name="points_required" min="0" required style="width: 100%;">
            </p>
            <p>
                <label for="palier_badge_name">Nom du badge</label>
                <input type="text" id="palier_badge_name" name="badge_name" required style="width: 100%;">
            </p>
            <p>
                <label for="palier_badge_icon">Icône FontAwesome (ex: fa-star)</label>
                <input type="text" id="palier_badge_icon" name="badge_icon" value="fa-star" required style="width: 100%;">
            </p>
            <p>
                <label for="palier_badge_color">Couleur du badge (hex)</label>
                <input type="color" id="palier_badge_color" name="badge_color" value="#3498db" required style="width: 100%; height: 40px;">
            </p>
            <p style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <button type="submit" class="button primary" style="flex: 1;"><i class="fa-solid fa-save"></i> Enregistrer</button>
                <button type="button" class="button" onclick="hidePalierForm()" style="flex: 1;">Annuler</button>
            </p>
        </form>
    </div>
</div>

<script>
function showAddPalierForm() {
    document.getElementById('modalTitle').textContent = 'Ajouter un palier';
    document.getElementById('palier_id').value = '';
    document.getElementById('palier_name').value = '';
    document.getElementById('palier_points_required').value = '';
    document.getElementById('palier_badge_name').value = '';
    document.getElementById('palier_badge_icon').value = 'fa-star';
    document.getElementById('palier_badge_color').value = '#3498db';
    document.getElementById('palierModal').style.display = 'flex';
}

function editPalier(id, name, points, badgeName, badgeIcon, badgeColor) {
    document.getElementById('modalTitle').textContent = 'Modifier le palier';
    document.getElementById('palier_id').value = id;
    document.getElementById('palier_name').value = name;
    document.getElementById('palier_points_required').value = points;
    document.getElementById('palier_badge_name').value = badgeName;
    document.getElementById('palier_badge_icon').value = badgeIcon;
    document.getElementById('palier_badge_color').value = badgeColor;
    document.getElementById('palierModal').style.display = 'flex';
}

function hidePalierForm() {
    document.getElementById('palierModal').style.display = 'none';
}

// Fermer le modal en cliquant à l'extérieur
document.getElementById('palierModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hidePalierForm();
    }
});
</script>

<style>
.button.small {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

