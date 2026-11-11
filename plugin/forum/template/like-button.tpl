<form hx-post="{{post.like_url}}" hx-target="this" hx-swap="outerHTML" method="POST" action="{{post.like_url}}" class="inline-form">
    <input type="hidden" name="_csrf" value="{{csrfToken}}">
    <button type="submit" class="button {% IF post.is_liked_by_current_user %}primary{% ELSE %}secondary{% ENDIF %} small">
        <i class="fa-solid fa-thumbs-up"></i> {{post.like_count}}
    </button>
</form>
