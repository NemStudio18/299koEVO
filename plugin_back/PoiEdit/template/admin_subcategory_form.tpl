<section class="module-card">
    <header>
        <i class="fa-solid fa-tag"></i>
        {% if subcategory %}
        Modifier une sous-catégorie
        {% else %}
        Ajouter une sous-catégorie
        {% endif %}
    </header>

    <form method="post" action="{{ ROUTER.generate("admin-poi-edit-save-subcategory") }}" novalidate>
        {% if subcategory %}
        <input type="hidden" name="id" value="{{ subcategory.id }}">
        {% endif %}
        <input type="hidden" name="category_id" value="{{ category.id }}">

        <div class="field">
            <label>Catégorie parente</label>
            <input type="text" value="{{ category.label_fr }}" disabled style="background: #f5f5f5;">
        </div>


        <div class="field">
            <label for="label_fr">Label français *</label>
            <input type="text" id="label_fr" name="label_fr" value="{{ subcategory.label_fr|default("") }}" required>
        </div>

        <div class="field">
            <label for="label_en">Label anglais</label>
            <input type="text" id="label_en" name="label_en" value="{{ subcategory.label_en|default("") }}">
        </div>

        <div class="field">
            <label for="priority">Priorité</label>
            <input type="number" id="priority" name="priority" value="{{ subcategory.priority|default(50) }}" min="0" max="100">
            <small>Ordre d'affichage (0-100, plus élevé = affiché en premier)</small>
        </div>

        <div class="field">
            <label>
                <input type="checkbox" name="active" value="1" {% if not subcategory or subcategory.active %}checked{% endif %}>
                Actif
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="button primary">
                <i class="fa-solid fa-save"></i> Enregistrer
            </button>
            <a href="{{ ROUTER.generate("admin-poi-edit-subcategories", ["categoryId" => category.id]) }}" class="button">
                <i class="fa-solid fa-times"></i> Annuler
            </a>
        </div>
    </form>
</section>

