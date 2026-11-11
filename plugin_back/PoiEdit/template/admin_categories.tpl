<section class="module-card">
    <header>
        <i class="fa-solid fa-tags"></i>
        Catégories éditoriales
    </header>

    <div style="margin-bottom: 20px;">
        <a href="{{ ROUTER.generate("admin-poi-edit-add-category") }}" class="button primary">
            <i class="fa-solid fa-plus"></i> Ajouter une catégorie
        </a>
    </div>

    <p style="margin-bottom: 20px; color: #666;">
        Les catégories éditoriales permettent de surcharger les catégories OSM pour uniformiser l'affichage.
        Si un POI a un override, il utilisera la catégorie éditoriale au lieu de la catégorie OSM brute.
        <br><strong>Note :</strong> Vous devez créer au moins une catégorie avant de pouvoir créer des sous-catégories.
    </p>

    {% if categories %}
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Slug</th>
                <th>Label FR</th>
                <th>Label EN</th>
                <th>Icône</th>
                <th>Priorité</th>
                <th>Sous-catégories</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% for category in categories %}
            <tr>
                <td>{{ category.id }}</td>
                <td><code>{{ category.slug }}</code></td>
                <td><strong>{{ category.label_fr }}</strong></td>
                <td>{{ category.label_en|default("-") }}</td>
                <td>
                    {% if category.icon %}
                    <i class="{{ category.icon }}"></i>
                    {% else %}
                    -
                    {% endif %}
                </td>
                <td>{{ category.priority }}</td>
                <td>
                    <a href="{{ ROUTER.generate("admin-poi-edit-subcategories", ["categoryId" => category.id]) }}" class="button small">
                        {{ category.subcategories_count }} sous-catégorie(s)
                    </a>
                </td>
                <td>
                    {% if category.active %}
                    <span style="color: #28a745;">✓ Actif</span>
                    {% else %}
                    <span style="color: #dc3545;">✗ Inactif</span>
                    {% endif %}
                </td>
                <td>
                    <a href="{{ ROUTER.generate("admin-poi-edit-edit-category", ["id" => category.id]) }}" class="button small">
                        <i class="fa-solid fa-edit"></i> Modifier
                    </a>
                    <a href="{{ ROUTER.generate("admin-poi-edit-delete-category", ["id" => category.id]) }}" 
                       class="button small alert" 
                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                        <i class="fa-solid fa-trash"></i> Supprimer
                    </a>
                </td>
            </tr>
            {% endfor %}
        </tbody>
    </table>
    {% else %}
    <p style="text-align: center; color: #999; padding: 40px;">
        Aucune catégorie éditoriale définie. <a href="{{ ROUTER.generate("admin-poi-edit-add-category") }}">Créer la première</a>
    </p>
    {% endif %}
</section>

