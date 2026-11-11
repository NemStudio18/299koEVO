<form method="post" id="mainForm" action="{ ROUTER.generate("admin-blog-save-post") }" enctype="multipart/form-data">
    {{SHOW.tokenField}}
    <input type="hidden" name="id" value="{{news.getId()}}" />
    <input type="hidden" name="imgId" value="{{news.getImg()}}" />

    <div class='tabs-container'>
        <ul class="tabs-header">
            <li class="default-tab"><i class="fa-solid fa-file-pen"></i> {{Lang.blog-content}}</li>
            <li><i class="fa-regular fa-newspaper"></i> {{Lang.blog-intro}}</li>
            <li><i class="fa-regular fa-thumbs-up"></i> {{Lang.blog-seo}}</li>
            <li><i class="fa-solid fa-heading"></i> {{Lang.blog-title}}</li>
            <li><i class="fa-solid fa-sliders"></i> {{Lang.blog-settings}}</li>
            <li><i class="fa-regular fa-image"></i> {{Lang.blog-featured-img}}</li>
        </ul>
        <ul class="tabs">
            <li class="tab">
                {{contentEditor}}
            </li>
            <li class="tab">
                <label for="intro">{{Lang.blog-intro-content}}</label><br>
                <textarea name="intro" id="intro" class="editor">{%HOOK.beforeEditEditor(news.getIntro())%}</textarea><br>
                {{filemanagerDisplayManagerButton()}}
            </li>
            <li class="tab">
                <div class='form'>
                    <label for="seoDesc">{{Lang.blog-seo-content}}</label>
                    <div class='tooltip'>
                        <span id='seoDescDesc'>{{Lang.blog-seo-content-tooltip}}</span>
                    </div>
                    <textarea name="seoDesc" id="seoDesc" aria-describedby="seoDescDesc">{{news.getSEODesc()}}</textarea>
                    <div id='seoDescProgress'></div>
                    <div id='seoDescCounter'></div>
                    <script>
                        function refreshSEODescCounter() {
                            var length = document.getElementById('seoDesc').value.length;
                            var progress = document.getElementById('seoDescProgress');
                            document.getElementById('seoDescCounter').innerHTML = length + ' caractère(s)';
                            if (length <= 100 || length > 250) {
                                progress.classList.remove("good", "care");
                                progress.classList.add("warning");
                            } else if (length <= 160) {
                                progress.classList.remove("good", "warning");
                                progress.classList.add("care");
                            } else {
                                progress.classList.remove("care", "warning");
                                progress.classList.add("good");
                            }
                            progress.style.width = (100 / 250 * length) + "%";
                        }

                        document.addEventListener("DOMContentLoaded", function () {
                            refreshSEODescCounter();
                        });
                        document.getElementById('seoDesc').addEventListener('keyup', function () {
                            refreshSEODescCounter();
                        });
                        document.getElementById('seoDesc').addEventListener('paste', function () {
                            refreshSEODescCounter();
                        });
                    </script>
                </div>                    
            </li>
            <li class='tab'>
                <label for="name">{{Lang.blog-title}}</label><br>
                <input type="text" name="name" id="name" value="{{news.getName()}}" required="required" />
                <label for="date">{{Lang.blog-date}}</label><br>
                <input placeholder="{{Lang.blog-date-placeh}}" type="date" name="date" id="date" value="{{news.getDate()}}" required="required" />
            </li>
            <li class='tab'>
                <h4>{{Lang.blog-settings-post}}</h4>
                <p>
                    <input {% if news.getdraft() %}checked{% endif %} type="checkbox" name="draft" id="draft"/>
                    <label for="draft">{{Lang.blog-do-not-publish}}</label>
                </p>
                {% IF runPlugin.getConfigVal("comments") %}
                    <p>
                        <input {% IF news.getCommentsOff() %}checked{% ENDIF %} type="checkbox" name="commentsOff" id="commentsOff"/>
                        <label for="commentsOff">{{Lang.blog-disable-comments-once}}</label>
                    </p>
                {% ENDIF %}
                <h4>{{Lang.blog-categories}}</h4>
                {{categoriesManager.outputAsCheckbox(news.getId())}}

                <h4>{{Lang.blog-affect-new-category}}</h4>
                <div class="input-field">
                    <label class="active" for="category-add-label">{{Lang.blog.categories.categoryName}}</label>
                    <input type="text" name="category-add-label" id="category-add-label"/>
                    <label for="category-add-parentId">{{Lang.blog.categories.categoryParent}}</label>
                    {{categoriesManager.outputAsSelectOne(0, "category-add-parentId")}}
                </div>
            </li>
            <li class='tab'>
                <h4>{{Lang.blog-featured-img}}</h4>
                <div id="news-image-container">
                    <section id="news-image-fields">
                        <header>{{Lang.blog-featured-img-select-title}}</header>
                        <label for="news-image">{{Lang.blog-featured-img-url}}</label>
                        <input type="text" id="news-image" name="news-image" oninput="blogDisplayPreview()" value="{{news.getImg()}}">
                        {{filemanagerDisplayManagerButton("news-image", "depuis les fichiers")}}
                        <a class="button" data-fancybox data-type="ajax" href="{ ROUTER.generate('admin-galerie-selector', {'token': token}) }"><i class="fa-solid fa-images"></i> depuis la gallerie</a>
                    </section>
                    <section id="news-image-preview-container">
                        <header>{{Lang.blog-featured-img-preview}}</header>
                        <div id="news-image-preview"></div>
                    </section>
                </div>
            </li>
        </ul>
    </div>
    <p><button id="mainSubmit" type="submit" class="floating" title='{{Lang.save}}'><i class="fa-regular fa-floppy-disk"></i></button></p>
</form>

<script>
    function blogDisplayPreview() {
        $url = document.getElementById('news-image').value;
        document.getElementById('news-image-preview').innerHTML = '<img src="' + $url + '" alt="' + $url + '" />';
    }

    blogDisplayPreview();
</script>