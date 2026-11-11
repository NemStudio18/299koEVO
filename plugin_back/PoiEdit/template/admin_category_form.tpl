<section class="module-card">
    <header>
        <i class="fa-solid fa-tag"></i>
        {% if category %}
        Modifier une catégorie
        {% else %}
        Ajouter une catégorie
        {% endif %}
    </header>

    <form method="post" action="{{ ROUTER.generate("admin-poi-edit-save-category") }}" novalidate>
        {% if category %}
        <input type="hidden" name="id" value="{{ category.id }}">
        {% endif %}

        <div class="field">
            <label for="slug">Slug *</label>
            <input type="text" id="slug" name="slug" value="{{ category.slug|default("") }}" required>
            <small>Identifiant unique (ex: "food", "beauty", "auto")</small>
        </div>

        <div class="field">
            <label for="label_fr">Label français *</label>
            <input type="text" id="label_fr" name="label_fr" value="{{ category.label_fr|default("") }}" required>
        </div>

        <div class="field">
            <label for="label_en">Label anglais</label>
            <input type="text" id="label_en" name="label_en" value="{{ category.label_en|default("") }}">
        </div>

        <div class="field">
            <label for="icon">Icône FontAwesome</label>
            <input type="text" id="icon" name="icon" value="{{ category.icon|default("") }}" placeholder="fa-basket-shopping">
            <small>Classe FontAwesome (ex: "fa-basket-shopping", "fa-scissors")</small>
        </div>

        <div class="field">
            <label for="priority">Priorité</label>
            <input type="number" id="priority" name="priority" value="{{ category.priority|default(50) }}" min="0" max="100">
            <small>Ordre d'affichage (0-100, plus élevé = affiché en premier)</small>
        </div>

        <div class="field">
            <label>
                <input type="checkbox" name="active" value="1" {% if not category or category.active %}checked{% endif %}>
                Actif
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="button primary">
                <i class="fa-solid fa-save"></i> Enregistrer
            </button>
            <a href="{{ ROUTER.generate("admin-poi-edit-categories") }}" class="button">
                <i class="fa-solid fa-times"></i> Annuler
            </a>
        </div>
    </form>
</section>

