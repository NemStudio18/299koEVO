{% INCLUDE 'forum/breadcrumb.tpl' %}

<section class="forum-post-edit">
    <header class="mb-large">
        <h1><i class="fa-solid fa-edit"></i> Modifier le message</h1>
        <a href="{{topicUrl}}" class="button secondary mt-medium">
            <i class="fa-solid fa-arrow-left"></i> Retour au sujet
        </a>
    </header>
    
    <form method="POST" action="{{editPostUrl}}" class="forum-edit-form" novalidate>
        <input type="hidden" name="_csrf" value="{{csrfToken}}">
        
        <div class="form-group mb-large">
            <label class="form-label-bold">Contenu du message</label>
            <textarea name="content" class="editor forum-editor-input" rows="12" required>{{post.content}}</textarea>
        </div>
        
        <div class="form-actions form-actions-row justify-end">
            <a href="{{topicUrl}}" class="button secondary">Annuler</a>
            <button type="submit" class="button primary">
                <i class="fa-solid fa-save"></i> Enregistrer les modifications
            </button>
        </div>
    </form>
</section>

<script>
// Corriger aria-hidden sur #content pour éviter les warnings d'accessibilité
document.addEventListener('DOMContentLoaded', function() {
    const content = document.getElementById('content');
    if (content && content.classList.contains('forum')) {
        if (content.getAttribute('aria-hidden') === 'true') {
            content.removeAttribute('aria-hidden');
        }
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

