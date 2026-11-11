{% INCLUDE PLUGINS ~ 'forum/template/breadcrumb.tpl' %}

<section class="forum-reply-topic">
    <header>
        <h1><i class="fa-solid fa-reply"></i> Répondre au sujet</h1>
        <p class="forum-topic__meta">
            Sujet :
            <a href="{{topic.url}}">{{topic.title}}</a>
        </p>
    </header>

    <form method="POST" action="{{createPostUrl}}" class="forum-reply-form" novalidate>
        <input type="hidden" name="_csrf" value="{{csrfToken}}">
        <input type="hidden" name="topic_id" value="{{topic.id}}">

        <div class="form-group">
            <label for="content" class="form-label-bold">Votre message *</label>
            <textarea id="content" name="content" class="editor forum-editor-input" rows="12" required placeholder="Rédigez votre réponse..."></textarea>
        </div>

        <div class="form-actions form-actions-row">
            <a href="{{cancelUrl}}" class="button secondary">Annuler</a>
            <button type="submit" class="button primary">
                <i class="fa-solid fa-paper-plane"></i> Envoyer la réponse
            </button>
        </div>
    </form>
</section>
