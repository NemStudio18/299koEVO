<div class="chips-container">
    {% for categoryKey, categoryData in categories %}
    <button type="button" 
            class="chip category-chip" 
            data-category="{{categoryKey}}"
            data-label="{{categoryData.label_safe}}"
            data-icon="{{categoryData.icon_safe}}"
            hx-get="{{categoryData.filter_url}}"
            hx-target="#SearchUi-home-wrapper"
            hx-select="#SearchUi-home-wrapper"
            hx-swap="outerHTML"
            hx-push-url="true"
            hx-trigger="click"
            hx-indicator=".pois-loading">
        {% if categoryData.icon %}
        <i class="fa-solid fa-{{categoryData.icon}}"></i>
        {% else %}
        <i class="fa-solid fa-map-marker-alt"></i>
        {% endif %}
        <span>{{categoryData.label}}</span>
        <span class="chip-count">({{categoryData.count_formatted}})</span>
    </button>
    {% endfor %}
</div>

