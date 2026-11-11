<form method="post" action="{ ROUTER.generate("admin-blog-save-config") }">
    {{SHOW.tokenField}}
    <script>
        function onCheckAuthor() {
            if (document.getElementById("displayAuthor").checked) {
                document.getElementById("author-fields").style.display = 'block';
            } else {
                document.getElementById("author-fields").style.display = 'none';
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            onCheckAuthor();
            document.getElementById("displayAuthor").addEventListener("click", function () {
                onCheckAuthor();
            });
        });
    </script>
    <div class='form'>
        <input {% if runPlugin.getConfigVal("hideContent") %}checked{% endif %} type="checkbox" name="hideContent" id="hideContent" aria-describedby='hideContentDesc' />
        <label for="hideContent">{{Lang.blog-hide-content}}</label>
        <div class='tooltip'>
            <span id='hideContentDesc'>{{Lang.blog-hide-content-tooltip}}</span>
        </div>
    </div>
    <div class='form'>
        <input {% if runPlugin.getConfigVal("comments") %}checked{% endif %} type="checkbox" name="comments" id="comments" />
        <label for="comments">{{Lang.blog-allow-comments}}</label>
    </div>
    <div class='form'>
        <label for="label">{{Lang.blog-page-title}}</label><br>
        <input type="text" name="label" id="label" value="{{ runPlugin.getConfigVal("label") }}" />
    </div>
    <div class='form'>
        <label for="items_per_page">{{Lang.blog-entries-per-page}}</label><br>
        <input type="number" name="items_per_page" id="items_per_page" value="{{ runPlugin.getConfigVal("items_per_page") ?: runPlugin.getConfigVal("itemsByPage") ?: 10 }}" />
    </div>
    <div class='form'>
        <label for="notify_admin_email">{{Lang.blog-notify-admin-email}}</label><br>
        <input type="email" name="notify_admin_email" id="notify_admin_email" value="{{ runPlugin.getConfigVal("notify_admin_email") }}" placeholder="admin@example.com" />
        <small>{{Lang.blog-notify-admin-help}}</small>
    </div>
    <div class='form'>
        <label for="displayTOC">{{Lang.blog-display-toc}}</label><br>
        <select id="displayTOC" name="displayTOC">
            <option value="no" {% if runPlugin.getConfigVal("displayTOC") == "no" %}selected{% endif %}>{{Lang.blog-toc-no}}</option>
            <option value="content" {% if runPlugin.getConfigVal("displayTOC") == "content" %}selected{% endif %}>{{Lang.blog-toc-in-content}}</option>
            <option value="sidebar" {% if runPlugin.getConfigVal("displayTOC") == "sidebar" %}selected{% endif %}>{{Lang.blog-toc-in-sidebar}}</option>
        </select>
    </div>
    <div class='form'>
        <input {% if runPlugin.getConfigVal("displayAuthor") %}checked{% endif %} type="checkbox" name="displayAuthor" id="displayAuthor" />
        <label for="displayAuthor">{{Lang.blog-display-author-block}}</label>
    </div>
    <div id="author-fields">
        <div class='form'>
            <label for="authorName">{{Lang.blog-author-name}}</label><br>
            <input type="text" name="authorName" id="authorName" value="{{ runPlugin.getConfigVal("authorName") }}" />
        </div>
        <div class='form'>
            <label for="authorAvatar">{{Lang.blog-author-image}}</label><br>
            <input type="url" name="authorAvatar" id="authorAvatar" value="{{ runPlugin.getConfigVal("authorAvatar") }}" />
            {{filemanagerDisplayManagerButton()}}
        </div>
        <div class='form'>
            <label for="authorBio">{{Lang.blog-author-bio}}</label><br>
            <textarea name="authorBio" id="authorBio" class="editor">{% HOOK.beforeEditEditor(runPlugin.getConfigVal("authorBio")) %}</textarea><br>
        </div>
    </div>

    <div class='form'><button type="submit" class="button primary"><i class="fa-solid fa-save"></i> {{Lang.submit}}</button></div>
</form>