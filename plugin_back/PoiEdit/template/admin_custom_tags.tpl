<section class="module-card">
    <header>
        <i class="fa-solid fa-tags"></i>
        Gestion des tags personnalisés
    </header>

    <div style="margin-bottom: 20px;">
        <a href="{{ addUrl }}" class="button primary">
            <i class="fa-solid fa-plus"></i> Ajouter un tag personnalisé
        </a>
    </div>

    {% if tags %}
    <table class="table">
        <thead>
            <tr>
                <th>Emoji</th>
                <th>Clé</th>
                <th>Label</th>
                <th>Valeur par défaut</th>
                <th>Description</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% for tag in tags %}
            <tr>
                <td style="font-size: 1.5em;">
                    {% if tag.has_emoji %}
                    {{ tag.emoji }}
                    {% endif %}
                </td>
                <td><code>{{ tag.key_name }}</code></td>
                <td><strong>{{ tag.label }}</strong></td>
                <td>
                    {% if tag.has_default_value %}
                    {{ tag.default_value }}
                    {% else %}
                    -
                    {% endif %}
                </td>
                <td>
                    {% if tag.has_description %}
                    {{ tag.description }}
                    {% else %}
                    -
                    {% endif %}
                </td>
                <td>
                    {% if tag.is_active %}
                    <span class="badge success">Actif</span>
                    {% else %}
                    <span class="badge alert">Inactif</span>
                    {% endif %}
                </td>
                <td>
                    <a href="{{ ROUTER.generate("admin-poi-edit-edit-custom-tag", ["id" => tag.id]) }}" class="button small">
                        <i class="fa-solid fa-edit"></i> Modifier
                    </a>
                    <a href="{{ ROUTER.generate("admin-poi-edit-delete-custom-tag", ["id" => tag.id]) }}" 
                       class="button small alert" 
                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce tag ?');">
                        <i class="fa-solid fa-trash"></i> Supprimer
                    </a>
                </td>
            </tr>
            {% endfor %}
        </tbody>
    </table>
    {% else %}
    <p style="padding: 20px; text-align: center; color: #999;">
        Aucun tag personnalisé créé. <a href="{{ addUrl }}">Créer le premier tag</a>
    </p>
    {% endif %}
</section>

