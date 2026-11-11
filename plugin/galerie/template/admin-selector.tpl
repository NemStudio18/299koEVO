<div id="galerie-selector-container">
    <header>{{Lang.galerie.select-image}}</header>
    <div class="galerie-selector-grid">
        {% for item in items %}
            <div class="selector-item" data-rel-url="{{item.getRelUrl()}}">
                <img src="{{item.getUrl()}}" alt="{{item.getTitle()}}" title="{{item.getTitle()}}" loading="lazy">
                <p>{{item.getTitle()}}</p>
            </div>
        {% endfor %}
    </div>
</div>

<style>
.galerie-selector-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}
.selector-item {
    width: 110px;
    cursor: pointer;
    text-align: center;
    background: var(--evo-card-bg);
    border: 1px solid var(--evo-border);
    border-radius: var(--evo-radius-sm);
    padding: 6px;
    transition: transform 0.16s ease, border-color 0.16s ease, background-color 0.16s ease;
}
.selector-item:hover {
    transform: translateY(-2px);
    background: var(--evo-accent-soft);
    border-color: var(--evo-accent);
}
.selector-item img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}
.selector-item p {
    font-size: 11px;
    margin: 5px 0 0;
    color: var(--evo-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<script>
document.getElementById('galerie-selector-container').addEventListener('click', function (e) {
    var card = e.target.closest('.selector-item');
    if (!card) return;
    var relUrl = card.dataset.relUrl || '';
    var target = document.getElementById('news-image');
    if (target) {
        target.value = relUrl;
        if (typeof blogDisplayPreview === 'function') {
            blogDisplayPreview();
        }
        target.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (typeof Fancybox !== 'undefined') {
        Fancybox.close(false);
    }
});
</script>
