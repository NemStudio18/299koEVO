<div class="chips-container">
    {% if subcategories %}
    {% for subKey, subData in subcategories %}
    <button type="button" 
            class="chip subcategory-chip" 
            data-category="{{category}}" 
            data-subcategory="{{subKey}}"
            data-label="{{subData.label_safe}}"
            data-icon="{{subData.icon_safe}}"
            hx-get="{{subData.filter_url}}"
            hx-target="#SearchUi-home-wrapper"
            hx-select="#SearchUi-home-wrapper"
            hx-swap="outerHTML"
            hx-push-url="true"
            hx-trigger="click"
            hx-indicator=".pois-loading">
        {% if subData.icon %}
        <i class="fa-solid fa-{{subData.icon}}"></i>
        {% else %}
        <i class="fa-solid fa-map-marker-alt"></i>
        {% endif %}
        <span>{{subData.label}}</span>
        <span class="chip-count">({{subData.count_formatted}})</span>
    </button>
    {% endfor %}
    {% else %}
    <p>Aucune sous-catégorie disponible.</p>
    {% endif %}
</div>

