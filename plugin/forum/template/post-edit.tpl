{% INCLUDE 'forum/breadcrumb.tpl' %}

<section class="forum-post-edit">
    <header>
        <h1><i class="fa-solid fa-edit"></i> Modifier le message</h1>
    </header>

    <form method="POST" action="{{editPostUrl}}" class="forum-edit-form" novalidate>
        <input type="hidden" name="_csrf" value="{{csrfToken}}">

        <div class="form-group">
            <label class="form-label-bold">Contenu du message</label>
            <textarea name="content" class="editor forum-editor-input" rows="12" required>{{post.content}}</textarea>
        </div>

        <div class="form-actions form-actions-row">
            <a href="{{topicUrl}}" class="button secondary">Annuler</a>
            <button type="submit" class="button primary">
                <i class="fa-solid fa-save"></i> Enregistrer les modifications
            </button>
        </div>
    </form>
</section>
