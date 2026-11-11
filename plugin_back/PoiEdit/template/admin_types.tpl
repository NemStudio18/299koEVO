<section class="poi-edit-admin-types">
    <header class="admin-actions-header">
        <div>
            <h1>Gestion des types et catégories</h1>
            <p>Gérez les types métier et leurs catégories. Les types personnalisés sont marqués d'un badge.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ ROUTER.generate("admin-poi-edit-add-type") }}" class="button primary">
                <i class="fa-solid fa-plus"></i> Ajouter un type
            </a>
            <a href="{{ ROUTER.generate("admin-poi-edit-add-category") }}" class="button secondary">
                <i class="fa-solid fa-plus"></i> Ajouter une catégorie
            </a>
        </div>
    </header>

    <div class="types-list">
        {% FOR typeKey, typeData IN types %}
            <div class="type-card">
                <div class="type-header">
                    <h2>
                        {{ typeData.label }}
                        {% if typeData.is_custom %}
                            <span class="badge badge-info" style="margin-left: 10px;">Personnalisé</span>
                        {% endif %}
                    </h2>
                    <span class="type-key" style="color: #666; font-size: 0.9em;">({{ typeKey }})</span>
                </div>
                
                <div class="categories-list">
                    {% if typeData.categories and typeData.categories|length > 0 %}
                        <h3 style="font-size: 0.95em; margin-top: 15px; margin-bottom: 10px; color: #555;">Catégories :</h3>
                        <ul class="categories-grid">
                            {% FOR catKey, catData IN typeData.categories %}
                                <li class="category-item">
                                    <strong>{{ catData.label|default:catKey }}</strong>
                                    {% if catData.is_custom %}
                                        <span class="badge badge-info" style="font-size: 0.75em; margin-left: 5px;">Perso</span>
                                    {% else %}
                                        <span class="badge badge-secondary" style="font-size: 0.75em; margin-left: 5px;">BDD</span>
                                    {% endif %}
                                    {% if catData.count %}
                                        <span style="color: #666; font-size: 0.8em; display: block; margin-top: 2px;">{{ catData.count }} POI</span>
                                    {% endif %}
                                    <span class="category-key" style="color: #999; font-size: 0.85em; display: block; margin-top: 2px;">{{ catKey }}</span>
                                </li>
                            {% ENDFOR %}
                        </ul>
                    {% else %}
                        <p style="color: #999; font-style: italic; margin-top: 10px;">Aucune catégorie définie</p>
                    {% endif %}
                </div>
            </div>
        {% ENDFOR %}
    </div>

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
        <a href="{{ ROUTER.generate("admin-poi-edit-type-proposals") }}" class="button">
            <i class="fa-solid fa-lightbulb"></i> Voir les propositions en attente
        </a>
    </div>
</section>

<style>
.poi-edit-admin-types {
    padding: 20px;
}

.types-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.type-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.type-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #eee;
}

.type-header h2 {
    margin: 0;
    font-size: 1.2em;
    color: #333;
}

.type-key {
    font-family: monospace;
    background: #f5f5f5;
    padding: 2px 8px;
    border-radius: 4px;
}

.categories-grid {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.category-item {
    background: #f9f9f9;
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #eee;
    font-size: 0.9em;
}

.category-item strong {
    display: block;
    color: #333;
}

.category-key {
    font-family: monospace;
    font-size: 0.85em;
}

.badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.75em;
    font-weight: normal;
}

.badge-info {
    background: #3b82f6;
    color: white;
}

.badge-secondary {
    background: #6b7280;
    color: white;
}
</style>

