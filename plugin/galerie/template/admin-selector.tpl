<div id="galerie-selector-container">
    <header>{{Lang.galerie.select-image}}</header>
    <div class="galerie-selector-grid">
        {% for item in items %}
            <div class="selector-item" onclick="selectGalerieImage('{{item.getImg()}}')">
                <img src="{{item.getUrl()}}" alt="{{item.getTitle()}}" title="{{item.getTitle()}}">
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
    width: 100px;
    cursor: pointer;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
    transition: transform 0.2s;
}
.selector-item:hover {
    transform: scale(1.05);
    background: #f0f0f0;
}
.selector-item img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 2px;
}
.selector-item p {
    font-size: 10px;
    margin: 5px 0 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<script>
function selectGalerieImage(imgFilename) {
    // We assume the target field ID is "news-image" (standard for Blog)
    // Or we could pass it as a parameter if needed.
    const targetId = 'news-image';
    const target = document.getElementById(targetId);
    if (target) {
        target.value = imgFilename;
        // Trigger oninput to refresh preview
        if (typeof blogDisplayPreview === 'function') {
            blogDisplayPreview();
        }
    }
    Fancybox.close();
}
</script>
