<section class="galerie">
    {{ galerieDisplayGalleryCarousel() }}
    <header class="galerie-header">
        <div class="galerie-intro">
            {{ htmlspecialchars_decode(runPlugin.getConfigVal("introduction")) }}
        </div>

        {% if galerie.useCategories() %}
            <div class="galerie-categories">
                <ul class="categories-nav">
                    {% if count(galerie.listCategories(false)) > 0 %}
                        <li><button class="active" rel="category_all">{{ Lang.galerie.display-all-categories }}</button></li>
                    {% endif %}
                    {% for k, v in galerie.listCategories(false) %}
                        <li><button rel="category_{{util.strToUrl(v)}}">{{v}}</button></li>
                    {% endfor %}
                </ul>
            </div>
        {% endif %}
    </header>

    {% if galerie.countItems() == 0 %}
        <div class="no-items">
            <i class="fa-solid fa-image-portrait"></i>
            <p>{{Lang.galerie.no-item-found}}</p>
        </div>
    {% else %}
        <div class="galerie-grid" id="list">
            {% for k, obj in galerie.getItems() %}
                {% if obj.getHidden() == false %}
                    <div class="galerie-item category_{{ util.strToUrl(obj.getCategory()) }} category_all">
                        <div class="galerie-item-inner">
                            <a href="{{ obj.getUrl}}" target="_blank" title="{{ obj.getTitle}}">
                                <div class="galerie-img-container">
                                    <img src="{{ obj.getUrl}}" alt="{{ obj.getTitle}}" loading="lazy" />
                                    {% if runPlugin.getConfigVal("showTitles") %}
                                        <div class="galerie-item-overlay">
                                            <span>{{ obj.getTitle}}</span>
                                        </div>
                                    {% endif %}
                                </div>
                            </a>
                        </div>
                    </div>
                {% endif %}
            {% endfor %}
        </div>
    {% endif %}
</section>