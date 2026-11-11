{% INCLUDE 'forum/breadcrumb.tpl' %}

<section class="forum-create-topic">
    <header class="mb-large">
        <h1><i class="fa-solid fa-plus-circle"></i> Créer un nouveau sujet</h1>
        <p class="forum-topic__meta mt-small">
            Dans la catégorie : 
            <a href="{{category.url}}">
                <i class="{{category.icon}}"></i> {{category.name}}
            </a>
        </p>
    </header>

    <form method="POST" action="{{createTopicUrl}}" class="forum-create-form" novalidate>
        <input type="hidden" name="_csrf" value="{{csrfToken}}">
        <input type="hidden" name="category_id" value="{{category.id}}">
        
        <div class="form-group mb-large">
            <label for="title" class="form-label-bold">Titre du sujet *</label>
            <input type="text" id="title" name="title" required 
            <input type="text" id="title" name="title" required class="form-input forum-editor-input" placeholder="Entrez le titre du sujet">
                   placeholder="Entrez le titre de votre sujet">
        </div>
        
        <div class="form-group mb-large">
            <label for="content" class="form-label-bold">Message *</label>
            <textarea id="content" name="content" class="editor forum-editor-input" rows="15" required 
                      placeholder="Rédigez votre premier message..."></textarea>
        </div>
        
        <div class="form-actions form-actions-row justify-end mt-large">
            <a href="{{cancelUrl}}" class="button secondary text-decoration-none">Annuler</a>
            <button type="submit" class="button primary">
                <i class="fa-solid fa-paper-plane"></i> Créer le sujet
            </button>
        </div>
    </form>
</section>

<script>
// Corriger aria-hidden sur #content pour éviter les warnings d'accessibilité
document.addEventListener('DOMContentLoaded', function() {
    const content = document.getElementById('content');
    if (content && content.classList.contains('forum')) {
        // Retirer aria-hidden si présent
        if (content.getAttribute('aria-hidden') === 'true') {
            content.removeAttribute('aria-hidden');
        }
        
        // Observer les changements pour éviter qu'il soit réinjecté
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'aria-hidden' && 
                    content.getAttribute('aria-hidden') === 'true') {
                    content.removeAttribute('aria-hidden');
                }
            });
        });
        observer.observe(content, { attributes: true, attributeFilter: ['aria-hidden'] });
    }
});
</script>
