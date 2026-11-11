<section class="module-card">
    <header>
        <i class="fa-solid fa-tags"></i>
        Sous-catégories : {{ category.label_fr }}
    </header>

    <div style="margin-bottom: 20px;">
        <a href="{{ ROUTER.generate("admin-poi-edit-categories") }}" class="button">
            <i class="fa-solid fa-arrow-left"></i> Retour aux catégories
        </a>
        <a href="{{ ROUTER.generate("admin-poi-edit-add-subcategory", ["categoryId" => category.id]) }}" class="button primary" style="margin-left: 10px;">
            <i class="fa-solid fa-plus"></i> Ajouter une sous-catégorie
        </a>
    </div>

    <p style="margin-bottom: 20px; color: #666;">
        Les sous-catégories permettent de mapper les clés OSM vers des labels uniformisés.
        Exemple : "bakery" (OSM) → "Boulangerie" (label français).
        <br><strong>Note :</strong> Ces sous-catégories sont liées à la catégorie "<strong>{{ category.label_fr }}</strong>".
    </p>

    {% if subcategories %}
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Clé OSM</th>
                <th>Label FR</th>
                <th>Label EN</th>
                <th>Priorité</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% for subcat in subcategories %}
            <tr>
                <td>{{ subcat.id }}</td>
                <td><code>{{ subcat.osm_key }}</code></td>
                <td><strong>{{ subcat.label_fr }}</strong></td>
                <td>{{ subcat.label_en|default("-") }}</td>
                <td>{{ subcat.priority }}</td>
                <td>
                    {% if subcat.active %}
                    <span style="color: #28a745;">✓ Actif</span>
                    {% else %}
                    <span style="color: #dc3545;">✗ Inactif</span>
                    {% endif %}
                </td>
                <td>
                    <a href="{{ ROUTER.generate("admin-poi-edit-edit-subcategory", ["id" => subcat.id]) }}" class="button small">
                        <i class="fa-solid fa-edit"></i> Modifier
                    </a>
                    <a href="{{ ROUTER.generate("admin-poi-edit-delete-subcategory", ["id" => subcat.id]) }}" 
                       class="button small alert" 
                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette sous-catégorie ?');">
                        <i class="fa-solid fa-trash"></i> Supprimer
                    </a>
                </td>
            </tr>
            {% endfor %}
        </tbody>
    </table>
    {% else %}
    <p style="text-align: center; color: #999; padding: 40px;">
        Aucune sous-catégorie définie pour cette catégorie. 
        <a href="{{ ROUTER.generate("admin-poi-edit-add-subcategory", ["categoryId" => category.id]) }}">Créer la première</a>
    </p>
    {% endif %}
</section>

