{% INCLUDE 'forum/breadcrumb.tpl' %}

<section class="forum-reply-topic">
    <header class="mb-large">
        <h1><i class="fa-solid fa-reply"></i> Répondre au sujet</h1>
        <p class="forum-topic__meta mt-small">
            Sujet : 
            <a href="{{topic.url}}">
                {{topic.title}}
            </a>
        </p>
    </header>

    <form method="POST" action="{{createPostUrl}}" class="forum-reply-form" novalidate>
        <input type="hidden" name="_csrf" value="{{csrfToken}}">
        <input type="hidden" name="topic_id" value="{{topic.id}}">
        
        <div class="form-group mb-large">
            <label for="content" class="form-label-bold">Votre message *</label>
            <textarea id="content" name="content" class="editor forum-editor-input" rows="15" required 
                      placeholder="Rédigez votre réponse..."></textarea>
        </div>
        
        <div class="form-actions form-actions-row justify-end mt-large">
            <a href="{{cancelUrl}}" class="button secondary text-decoration-none">Annuler</a>
            <button type="submit" class="button primary">
                <i class="fa-solid fa-paper-plane"></i> Envoyer la réponse
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



