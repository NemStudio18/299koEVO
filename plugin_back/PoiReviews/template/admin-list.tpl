<div class="tabs-container">
    <header><h1>Gestion des avis et commentaires</h1></header>
    
    <ul class="tabs-header">
        <li class="{% if currentStatus == "active" or currentStatus == "" %}active{% endif %}" onclick="window.location.href='{{ urlActive }}'">Actifs</li>
        <li class="{% if currentStatus == "moderated" %}active{% endif %}" onclick="window.location.href='{{ urlModerated }}'">Modérés</li>
        <li class="{% if currentStatus == "deleted" %}active{% endif %}" onclick="window.location.href='{{ urlDeleted }}'">Supprimés</li>
        <li class="{% if currentStatus == "all" %}active{% endif %}" onclick="window.location.href='{{ urlAll }}'">Tous</li>
    </ul>

    <div class="tabs">
        <div class="tab">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>POI</th>
                        <th>Utilisateur</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {% for review in reviews %}
                    <tr>
                        <td>{{ review.id }}</td>
                        <td><small>#{{ review.poi_id }}</small></td>
                        <td>{{ review.user_id }}</td>
                        <td>
                            <div style="white-space: nowrap;">
                            {% for star in review.stars %}
                                {% if star.isFull %}
                                    <i class="fa-solid fa-star" style="color: #ffc107;"></i>
                                {% else %}
                                    <i class="fa-regular fa-star" style="color: #ccc;"></i>
                                {% endif %}
                            {% endfor %}
                            </div>
                        </td>
                        <td><small>{{ review.comment_display }}</small></td>
                        <td>
                            {% if review.status == "active" %}
                                <span class="badge success">Actif</span>
                            {% elseif review.status == "moderated" %}
                                <span class="badge warning">Modéré</span>
                            {% else %}
                                <span class="badge alert">Supprimé</span>
                            {% endif %}
                        </td>
                        <td><small>{{ review.created_at_formatted }}</small></td>
                        <td>
                            <div role="group">
                                {% if review.status == "moderated" or review.status == "deleted" %}
                                <form method="POST" action="{{ review.restoreUrl }}">
                                    <input type="hidden" name="token" value="{{ csrfToken }}">
                                    <button type="submit" class="button small" title="Restaurer"><i class="fa-solid fa-undo"></i></button>
                                </form>
                                {% endif %}
                                {% if review.status != "deleted" %}
                                <form method="POST" action="{{ review.deleteUrl }}">
                                    <input type="hidden" name="token" value="{{ csrfToken }}">
                                    <button type="submit" class="button small alert" onclick="return confirm('Supprimer définitivement cet avis ?')" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                                </form>
                                {% endif %}
                            </div>
                        </td>
                    </tr>
                    {% endfor %}
                    {% if reviews|length == 0 %}
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem;">Aucun avis trouvé.</td>
                    </tr>
                    {% endif %}
                </tbody>
            </table>
        </div>
    </div>
</div>



