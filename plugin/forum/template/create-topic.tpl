{% INCLUDE PLUGINS ~ 'forum/template/breadcrumb.tpl' %}

<section class="forum-create-topic">
    <header>
        <h1><i class="fa-solid fa-plus-circle"></i> Créer un nouveau sujet</h1>
        <p class="forum-topic__meta">
            Dans la catégorie :
            <a href="{{category.url}}">
                {% IF category.icon %}<i class="{{category.icon}}"></i>{% ENDIF %} {{category.name}}
            </a>
        </p>
    </header>

    <form method="POST" action="{{createTopicUrl}}" class="forum-create-form" novalidate>
        <input type="hidden" name="_csrf" value="{{csrfToken}}">
        <input type="hidden" name="category_id" value="{{category.id}}">

        <div class="form-group">
            <label for="title" class="form-label-bold">Titre du sujet *</label>
            <input type="text" id="title" name="title" required class="form-input forum-editor-input" placeholder="Entrez le titre de votre sujet">
        </div>

        <div class="form-group">
            <label for="content" class="form-label-bold">Message *</label>
            <textarea id="content" name="content" class="editor forum-editor-input" rows="12" required placeholder="Rédigez votre premier message..."></textarea>
        </div>

        <div class="form-actions form-actions-row">
            <a href="{{cancelUrl}}" class="button secondary">Annuler</a>
            <button type="submit" class="button primary">
                <i class="fa-solid fa-paper-plane"></i> Créer le sujet
            </button>
        </div>
    </form>
</section>
