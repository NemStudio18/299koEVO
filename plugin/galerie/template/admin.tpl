{% if mode == "list" %}
    <div class="tabs-container" data-tab-key="galerie">
        <ul class="tabs-header">
            <li data-tab="images" class="{% if activeTab == 'images' %}default-tab{% endif %}">
                <i class="fa-solid fa-images"></i> {{ Lang.galerie.images-list }}
            </li>
            <li data-tab="categories" class="{% if activeTab == 'categories' %}default-tab{% endif %}">
                <i class="fa-solid fa-folder-tree"></i> {{ Lang.galerie.categories }}
            </li>
            <li data-tab="carousel" class="{% if activeTab == 'carousel' %}default-tab{% endif %}">
                <i class="fa-solid fa-play"></i> {{ Lang.galerie.carousel-settings }}
            </li>
            <li data-tab="settings" class="{% if activeTab == 'settings' %}default-tab{% endif %}">
                <i class="fa-solid fa-cog"></i> {{ Lang.core-settings }}
            </li>
        </ul>

        <ul class="tabs">
            <!-- Tab: Images -->
            <li class="tab">
                <div class="admin-actions-bar" style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem;">
                    <a class="button primary" href="{ ROUTER.generate("admin-galerie-edit") }">
                        <i class="fa-solid fa-plus"></i> {{ Lang.add }}
                    </a>
                    <button class="button showall" data-state="hidden">
                        <i class="fa-solid fa-eye-slash"></i> {{ Lang.galerie.toggle-hidden }}
                    </button>
                </div>

                <div class="gallery-sections">
                    {% if galerie.getGroupedItems() %}
                        {% for groupName, items in galerie.getGroupedItems() %}
                            <div class="gallery-category-group">
                                <h3 class="gallery-category-header">
                                    <i class="fa-solid fa-folder-open"></i> {{groupName}}
                                    <span class="count-badge">{{count(items)}}</span>
                                </h3>
                                <div class="gallery-grid">
                                    {% for item in items %}
                                        <div class="gallery-card {% if item.getHidden() %}hidden-item{% else %}visible-item{% endif %}" id="img-{{ item.getId() }}">
                                            <div class="gallery-preview">
                                                <img src="{{ item.getUrl() }}" alt="{{ item.getImg() }}" loading="lazy" />
                                            </div>
                                            <div class="gallery-card-content">
                                                <div class="gallery-card-title" title="{{ item.getTitle() }}">{{ item.getTitle() }}</div>
                                            </div>
                                            <div class="gallery-card-actions">
                                                <a title="{{ Lang.edit }}" href="{ ROUTER.generate("admin-galerie-edit-id", ["id" => item.getId()]) }" class="button primary small">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a title="{{ Lang.delete }}" onclick="if(!confirm('{{ Lang.confirm.deleteItem }}')) return false;" href="{ ROUTER.generate("admin-galerie-delete", ["id" => item.getId(), "token" => token]) }" class="button alert small">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    {% endfor %}
                                </div>
                            </div>
                        {% endfor %}
                    {% else %}
                        <div class="empty-state" style="text-align: center; padding: 3rem; color: var(--font-color); opacity: 0.5;">
                            <i class="fa-solid fa-images" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                            <p>{{ Lang.galerie.no-images-yet }}</p>
                        </div>
                    {% endif %}
                </div>
            </li>

            <!-- Tab: Categories -->
            <li class="tab">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                    <section class="panel">
                        <header>{{ Lang.add }} {{ Lang.galerie.category }}</header>
                        <form method="post" action="{ ROUTER.generate("admin-galerie-category-add") }">
                            {{ SHOW.tokenField }}
                            <input type="hidden" name="activeTab" value="categories" />
                            <p>
                                <label for="cat-name">{{ Lang.galerie.title }}</label><br>
                                <input type="text" name="name" id="cat-name" required="required" class="width-100" />
                            </p>
                            <p>
                                <label for="cat-parent">{{ Lang.category }} {{ Lang.parent }}</label><br>
                                <select name="parent" id="cat-parent" class="width-100">
                                    <option value="">-- {{ Lang.none }} --</option>
                                    {% for cat in galerie.getCategories() %}
                                        {% if not cat.parent %}
                                            <option value="{{ cat.id }}">{{ cat.name }}</option>
                                        {% endif %}
                                    {% endfor %}
                                </select>
                            </p>
                            <button type="submit" class="button primary">{{ Lang.save }}</button>
                        </form>
                    </section>

                    <section class="panel">
                        <header>{{ Lang.galerie.existing-categories }}</header>
                        {% if galerie.getCategories() %}
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ Lang.galerie.category }}</th>
                                        <th class="text-right">{{ Lang.actions }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {% for cat in galerie.getCategories() %}
                                        {% if not cat.parent %}
                                            <tr>
                                                <td><strong>{{ cat.name }}</strong></td>
                                                <td class="text-right">
                                                    <a href="{ ROUTER.generate("admin-galerie-category-delete", ["id" => cat.id]) }" 
                                                       onclick="return confirm('{{ Lang.confirm.deleteItem }}')" 
                                                       class="button alert small">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            {% for sub in galerie.getCategories() %}
                                                {% if sub.parent == cat.id %}
                                                    <tr>
                                                        <td style="padding-left: 2rem;"><i class="fa-solid fa-turn-up fa-rotate-90 color-muted"></i> {{ sub.name }}</td>
                                                        <td class="text-right">
                                                            <a href="{ ROUTER.generate("admin-galerie-category-delete", ["id" => sub.id]) }" 
                                                               onclick="return confirm('{{ Lang.confirm.deleteItem }}')" 
                                                               class="button alert small">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                {% endif %}
                                            {% endfor %}
                                        {% endif %}
                                    {% endfor %}
                                </tbody>
                            </table>
                        {% else %}
                            <p class="color-muted italic">{{ Lang.galerie.no-categories-yet }}</p>
                        {% endif %}
                    </section>
                </div>
            </li>

            <!-- Tab: Carousel -->
            <li class="tab">
                <form method="post" action="{ ROUTER.generate("admin-galerie-save-config") }">
                    {{ SHOW.tokenField }}
                    <input type="hidden" name="activeTab" value="carousel" />
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Gallery Top Carousel -->
                        <section class="panel">
                            <header>{{ Lang.galerie.carousel-gallery }}</header>
                            
                            <p>
                                <input {% if runPlugin.getConfigVal("carouselGalleryEnabled") %} checked {% endif %} type="checkbox" name="carouselGalleryEnabled" id="carouselGalleryEnabled" />
                                <label for="carouselGalleryEnabled">{{ Lang.galerie.carousel-enabled }}</label>
                            </p>

                            <p>
                                <label for="carouselGalleryType">{{ Lang.galerie.carousel-type }}</label><br>
                                <select name="carouselGalleryType" id="carouselGalleryType" class="width-100">
                                    <option value="slide" {% if runPlugin.getConfigVal("carouselGalleryType") == "slide" %} selected {% endif %}>{{ Lang.galerie.carousel-type-standard }}</option>
                                    <option value="fade" {% if runPlugin.getConfigVal("carouselGalleryType") == "fade" %} selected {% endif %}>{{ Lang.galerie.carousel-type-fade }}</option>
                                    <option value="coverflow" {% if runPlugin.getConfigVal("carouselGalleryType") == "coverflow" %} selected {% endif %}>{{ Lang.galerie.carousel-type-coverflow }}</option>
                                </select>
                            </p>

                            <p>
                                <label for="carouselGallerySource">{{ Lang.galerie.carousel-source }}</label><br>
                                <select name="carouselGallerySource" id="carouselGallerySource" class="width-100 carousel-source-toggle" data-target="carouselGalleryCategoryWrap">
                                    <option value="all" {% if runPlugin.getConfigVal("carouselGallerySource") == "all" %} selected {% endif %}>{{ Lang.galerie.carousel-source-all }}</option>
                                    <option value="category" {% if runPlugin.getConfigVal("carouselGallerySource") == "category" %} selected {% endif %}>{{ Lang.galerie.carousel-source-category }}</option>
                                </select>
                            </p>

                            <p id="carouselGalleryCategoryWrap" {% if runPlugin.getConfigVal("carouselGallerySource") != "category" %} style="display:none;" {% endif %}>
                                <label for="carouselGalleryCategory">{{ Lang.galerie.carousel-category }}</label><br>
                                <select name="carouselGalleryCategory" id="carouselGalleryCategory" class="width-100">
                                    {% for cat in galerie.getCategories() %}
                                        <option value="{{ cat.name }}" {% if runPlugin.getConfigVal("carouselGalleryCategory") == cat.name %} selected {% endif %}>{{ cat.name }}</option>
                                    {% endfor %}
                                </select>
                            </p>
                        </section>

                        <!-- External Hook Carousel -->
                        <section class="panel">
                            <header>{{ Lang.galerie.carousel-hook }}</header>
                            
                            <p>
                                <input {% if runPlugin.getConfigVal("carouselHookEnabled") %} checked {% endif %} type="checkbox" name="carouselHookEnabled" id="carouselHookEnabled" />
                                <label for="carouselHookEnabled">{{ Lang.galerie.carousel-enabled }}</label>
                            </p>

                            <p>
                                <label for="carouselHookType">{{ Lang.galerie.carousel-type }}</label><br>
                                <select name="carouselHookType" id="carouselHookType" class="width-100">
                                    <option value="slide" {% if runPlugin.getConfigVal("carouselHookType") == "slide" %} selected {% endif %}>{{ Lang.galerie.carousel-type-standard }}</option>
                                    <option value="fade" {% if runPlugin.getConfigVal("carouselHookType") == "fade" %} selected {% endif %}>{{ Lang.galerie.carousel-type-fade }}</option>
                                    <option value="coverflow" {% if runPlugin.getConfigVal("carouselHookType") == "coverflow" %} selected {% endif %}>{{ Lang.galerie.carousel-type-coverflow }}</option>
                                </select>
                            </p>

                            <p>
                                <label for="carouselHookSource">{{ Lang.galerie.carousel-source }}</label><br>
                                <select name="carouselHookSource" id="carouselHookSource" class="width-100 carousel-source-toggle" data-target="carouselHookCategoryWrap">
                                    <option value="all" {% if runPlugin.getConfigVal("carouselHookSource") == "all" %} selected {% endif %}>{{ Lang.galerie.carousel-source-all }}</option>
                                    <option value="category" {% if runPlugin.getConfigVal("carouselHookSource") == "category" %} selected {% endif %}>{{ Lang.galerie.carousel-source-category }}</option>
                                </select>
                            </p>

                            <p id="carouselHookCategoryWrap" {% if runPlugin.getConfigVal("carouselHookSource") != "category" %} style="display:none;" {% endif %}>
                                <label for="carouselHookCategory">{{ Lang.galerie.carousel-category }}</label><br>
                                <select name="carouselHookCategory" id="carouselHookCategory" class="width-100">
                                    {% for cat in galerie.getCategories() %}
                                        <option value="{{ cat.name }}" {% if runPlugin.getConfigVal("carouselHookCategory") == cat.name %} selected {% endif %}>{{ cat.name }}</option>
                                    {% endfor %}
                                </select>
                            </p>
                        </section>
                    </div>

                    <div class="mt-medium text-center">
                        <button type="submit" class="button primary">
                            <i class="fa-solid fa-save"></i> {{ Lang.save }}
                        </button>
                    </div>
                </form>
            </li>

            <!-- Tab: Settings -->
            <li class="tab">
                {% INCLUDE PLUGINS ~ "galerie/template/_settings.tpl" %}
            </li>
        </ul>
    </div>
{% endif %}

{% if mode == "edit" %}
    <div class="admin-edit-container">
        <header class="mb-medium">
            <h3>{% if item.getId() %}{{ Lang.edit }}{% else %}{{ Lang.add }}{% endif %} {{ Lang.galerie.image }}</h3>
            <a href="{ ROUTER.generate("admin-galerie-home") }" class="button small">
                <i class="fa-solid fa-arrow-left"></i> {{ Lang.galerie.back }}
            </a>
        </header>

        <form method="post" action="{ ROUTER.generate("admin-galerie-save") }" enctype="multipart/form-data">
            {{ SHOW.tokenField }}
            <input type="hidden" name="id" value="{{ item.getId() }}" />

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <div class="edit-main">
                    <section class="panel">
                        <p>
                            <label for="title">{{ Lang.galerie.title }}</label><br>
                            <input type="text" name="title" id="title" value="{{ item.getTitle() }}" required="required" class="width-100" />
                        </p>
                        <p>
                            {{ contentEditor }}
                        </p>
                    </section>

                    <section class="panel mt-medium">
                        <header>{{ Lang.galerie.image }}</header>
                        <div class="file-upload-zone" style="border: 2px dashed #ddd; padding: 2rem; text-align: center; border-radius: 8px;">
                            <input type="file" name="file" id="file" accept="image/*" {% if item.getImg() == "" %} required="required" {% endif %} />
                            <p class="font-small color-muted mt-small">Format: png, jpg, jpeg, gif</p>
                            
                            {% if item.getImg() != "" %}
                                <div class="mt-medium">
                                    <p class="font-bold">{{ Lang.galerie.current-image }} :</p>
                                    <img src="{{ item.getUrl() }}" alt="{{ item.getImg() }}" style="max-width: 100%; border-radius: 8px; margin-top: 0.5rem;" />
                                </div>
                            {% endif %}
                        </div>
                    </section>
                </div>

                <div class="edit-sidebar">
                    <section class="panel">
                        <header>{{ Lang.core-settings }}</header>
                        <p>
                            <input {% if item.getHidden() %} checked {% endif %} type="checkbox" name="hidden" id="hidden"/>
                            <label for="hidden">{{ Lang.galerie.make-invisible }}</label>
                        </p>
                        <p>
                            <label for="date">{{ Lang.galerie.date }}</label><br>
                            <input type="date" name="date" id="date" value="{{ item.getDate() }}" class="width-100" /> 
                        </p>
                        <p>
                            <label for="category">{{ Lang.galerie.category }}</label><br>
                            <select name="category" id="category" class="width-100">
                                <option value="">-- {{ Lang.none }} --</option>
                                {% for cat in galerie.getCategories() %}
                                    {% if not cat.parent %}
                                        <option value="{{ cat.name }}" {% if item.getCategory() == cat.name %} selected {% endif %}>{{ cat.name }}</option>
                                        {% for sub in galerie.getCategories() %}
                                            {% if sub.parent == cat.id %}
                                                <option value="{{ sub.name }}" {% if item.getCategory() == sub.name %} selected {% endif %}>&nbsp;&nbsp;&nbsp;{{ sub.name }}</option>
                                            {% endif %}
                                        {% endfor %}
                                    {% endif %}
                                {% endfor %}
                            </select>
                        </p>
                    </section>

                    <div class="mt-medium">
                        <button type="submit" class="button primary width-100">{{ Lang.save }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
{% endif %}

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Hidden items toggle
    const showAllBtn = document.querySelector('.showall');
    if (showAllBtn) {
        showAllBtn.addEventListener('click', function () {
            const isHiddenState = this.dataset.state === 'hidden';
            const hiddenItems = document.querySelectorAll('.gallery-card.hidden-item');
            const visibleItems = document.querySelectorAll('.gallery-card.visible-item');

            if (isHiddenState) {
                this.dataset.state = 'displayed';
                this.innerHTML = '<i class="fa-solid fa-eye"></i> {{ Lang.galerie.hide-hidden }}';
                hiddenItems.forEach(item => item.style.display = 'flex');
                visibleItems.forEach(item => item.style.display = 'none');
            } else {
                this.dataset.state = 'hidden';
                this.innerHTML = '<i class="fa-solid fa-eye-slash"></i> {{ Lang.galerie.toggle-hidden }}';
                hiddenItems.forEach(item => item.style.display = 'none');
                visibleItems.forEach(item => item.style.display = 'flex');
            }
        });
        
        // Initial state
        document.querySelectorAll('.gallery-card.hidden-item').forEach(item => item.style.display = 'none');
    }



    // Carousel source toggle
    document.querySelectorAll('.carousel-source-toggle').forEach(select => {
        select.addEventListener('change', function() {
            const targetId = this.dataset.target;
            const catWrap = document.getElementById(targetId);
            if (this.value === 'category') {
                catWrap.style.display = 'block';
            } else {
                catWrap.style.display = 'none';
            }
        });
    });
});
</script>