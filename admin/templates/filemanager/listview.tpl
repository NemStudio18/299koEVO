<section class="module-card"
    id="fm-listview-container"
    data-dir="{{dir}}"
    data-editor="{% if hasEditor %}{{editor}}{% endif %}"
    data-ajax-view="{% if ajaxView %}1{% else %}0{% endif %}"
    data-token="{{token}}"
    data-upload-url="{{uploadUrl}}"
    data-delete-url="{{deleteUrl}}"
    data-create-url="{{createUrl}}"
    data-redirect-ajax-url="{{redirectAjaxUrl}}"
    data-view-url="{{redirectUrl}}"
    data-msg-folder-empty="{{Lang.filemanager.folder-name-empty}}"
    data-msg-folder-created="{{Lang.filemanager.folder-created}}"
    data-msg-folder-failed="{{Lang.filemanager.folder-creation-failed}}"
    data-msg-no-file="{{Lang.filemanager.no-file-selected}}"
    data-msg-upload-failed="{{Lang.filemanager.upload-failed}}"
    data-msg-copied="{{Lang.filemanager.copied-to-clipboard}}"
    data-msg-copy-failed="{{Lang.filemanager.copy-failed}}"
    data-msg-deleted="{{Lang.core-item-deleted}}"
    data-msg-confirm-delete="{{Lang.confirm.deleteItem}}">

    {% if ajaxView %}
        <link rel="stylesheet" href="{{fmCssUrl}}" data-fm-asset="css">
    {% endif %}

    <header class="fm-header-bar">
        <span class="fm-header-title">
            <i class="fa-solid fa-folder-tree"></i> {{Lang.filemanager.name}}
        </span>
        <div class="fm-header-badges">
            <span class="fm-stat-pill"><i class="fa-regular fa-folder"></i> <strong>{{stats.folders}}</strong> {{Lang.filemanager.stats-total-folders}}</span>
            <span class="fm-stat-pill"><i class="fa-regular fa-file"></i> <strong>{{stats.files}}</strong> {{Lang.filemanager.stats-total-files}}</span>
            <span class="fm-stat-pill"><i class="fa-regular fa-image"></i> <strong>{{stats.pictures}}</strong> {{Lang.filemanager.stats-total-pictures}}</span>
            <span class="fm-stat-pill"><i class="fa-solid fa-hard-drive"></i> <strong>{{stats.size_formatted}}</strong></span>
        </div>
    </header>

    <div class="fm-dropzone">
        <input type="file" name="image_file" multiple class="fm-file-input">
        <div class="fm-dropzone-info">
            <div class="fm-dropzone-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <div class="fm-dropzone-text">
                <strong>Déposez vos fichiers ici ou cliquez pour envoyer</strong>
                <span>Images converties automatiquement en WebP · Max 10 Mo par fichier</span>
            </div>
        </div>
        <div class="fm-dropzone-actions">
            <button type="button" class="button primary small" data-fm-action="pick-files">
                <i class="fa-solid fa-file-circle-plus"></i> {{Lang.filemanager.add-file}}
            </button>
            <button type="button" class="button success small" data-fm-action="toggle-new-folder">
                <i class="fa-solid fa-folder-plus"></i> {{Lang.filemanager.add-folder}}
            </button>
        </div>
    </div>

    <progress value="0" max="100" class="fm-progress"></progress>

    <div class="fm-new-folder-bar">
        <input type="text" class="input fm-new-folder-input" placeholder="{{Lang.filemanager.folder-name}}...">
        <button type="button" class="button success small" data-fm-action="create-folder">
            <i class="fa-solid fa-check"></i> {{Lang.validate}}
        </button>
        <button type="button" class="button secondary small" data-fm-action="cancel-new-folder">
            {{Lang.cancel}}
        </button>
    </div>

    <div id="fm-breadcrumb-container">
        <div id="fm-breadcrumb">
            <a data-fm-action="open-dir" data-dir=""><i class="fa-solid fa-house"></i> {{Lang.filemanager.root}}</a>
            {% for crumb in breadcrumbs %}
                <a data-fm-action="open-dir" data-dir="{{crumb.dir}}">{{crumb.label}}</a>
            {% endfor %}
        </div>
        <div class="fm-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" class="fm-search-input" placeholder="Filtrer ce dossier...">
        </div>
    </div>

    <div id="fm-listview">
        <div id="fm-view-files">
            {% if dir != "" %}
                <div class="fm-thumb fm-thumb-parent" data-fm-action="open-dir" data-dir="{{dir}}/..">
                    <div class="fm-folder parent-dir">
                        <i class="fa-solid fa-level-up-alt"></i>
                    </div>
                    <div class="fm-title-wrap">
                        <p class="fm-title">..</p>
                        <div class="fm-meta">{{Lang.filemanager.parent-folder}}</div>
                    </div>
                </div>
            {% endif %}

            {% for item in folders %}
                <div class="fm-thumb" data-filter-name="{{item.name}}">
                    <div class="fm-folder" data-fm-action="open-dir" data-dir="{{item.path}}">
                        <i class="fa-solid fa-folder"></i>
                    </div>
                    <div class="fm-title-wrap">
                        <p class="fm-title" title="{{item.name}}">{{item.name}}</p>
                        <div class="fm-meta">Dossier</div>
                    </div>
                    <div class="fm-actions">
                        <button type="button" class="fm-link" data-fm-action="open-dir" data-dir="{{item.path}}" title="Ouvrir">
                            <i class="fa-regular fa-folder-open"></i>
                        </button>
                        <button type="button" class="fm-link alert" data-fm-action="delete-folder" data-name="{{item.name}}" title="{{Lang.delete}}">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            {% endfor %}

            {% for item in files %}
                <div class="fm-thumb" data-filter-name="{{item.name}}">
                    {% if item.isPicture %}
                        <div class="fm-picture">
                            {% if hasEditor %}
                                <button type="button" class="fm-picture-picker-trigger" data-fm-action="insert" data-rel-url="{{item.relUrl}}" data-url="{{item.url}}" data-is-picture="1" title="Cliquer pour sélectionner">
                                    <img src="{{item.url}}" alt="{{item.name}}" loading="lazy">
                                </button>
                            {% else %}
                                <a href="{{item.url}}" data-fancybox="fm-gallery" data-caption="{{item.name}}">
                                    <img src="{{item.url}}" alt="{{item.name}}" loading="lazy">
                                </a>
                            {% endif %}
                        </div>
                    {% else %}
                        <div class="fm-file">
                            <i class="{{item.iconClass}}"></i>
                        </div>
                    {% endif %}
                    <div class="fm-title-wrap">
                        <p class="fm-title" title="{{item.name}}">{{item.name}}</p>
                        <div class="fm-meta">{{item.sizeFormatted}}</div>
                    </div>
                    <div class="fm-actions">
                        {% if hasEditor %}
                            <button type="button" class="fm-link fm-insert-btn" data-fm-action="insert" data-rel-url="{{item.relUrl}}" data-url="{{item.url}}" data-is-picture="{{item.isPictureFlag}}" title="Insérer">
                                <i class="fa-solid fa-plus"></i> Insérer
                            </button>
                        {% endif %}
                        <button type="button" class="fm-link" data-fm-action="copy" data-url="{{item.relUrl}}" title="{{Lang.copy}}">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                        <button type="button" class="fm-link alert" data-fm-action="delete-file" data-name="{{item.name}}" title="{{Lang.delete}}">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            {% endfor %}

            {% if isEmpty %}
                <div class="fm-empty">
                    <i class="fa-regular fa-folder-open"></i>
                    Ce dossier est vide. Glissez un fichier ci-dessus pour commencer.
                </div>
            {% endif %}
        </div>
    </div>

    {% if ajaxView %}
        <script>
            (function () {
                if (typeof window.EvoFileManager === 'undefined') {
                    var s = document.createElement('script');
                    s.src = '{{fmJsUrl}}';
                    s.onload = function () {
                        if (window.EvoFileManager) window.EvoFileManager.initAll();
                    };
                    document.body.appendChild(s);
                } else {
                    window.EvoFileManager.initAll();
                }
            })();
        </script>
    {% endif %}
</section>
