<section class="forum-admin-moderation">
    <header>
        <h1>Modération</h1>
        <p>Messages en attente de validation.</p>
    </header>

    {% IF posts %}
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Sujet</th>
                <th>Auteur</th>
                <th>Contenu</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% FOR post IN posts %}
            <tr>
                <td>{{post.id}}</td>
                <td>
                    {% IF post.topic_title %}
                        <strong>{{post.topic_title}}</strong><br>
                    {% ELSE %}
                        <strong>Topic #{{post.topic_id}}</strong><br>
                    {% ENDIF %}
                    <small>ID {{post.topic_id}}</small>
                </td>
                <td>{{post.author_name}}</td>
                <td>{{post.content}}</td>
                <td>{{post.created_at_formatted}}</td>
                <td>
                    <form method="POST" action="{{approveUrl}}">
                        <input type="hidden" name="_csrf" value="{{csrfToken}}">
                        <input type="hidden" name="post_id" value="{{post.id}}">
                        <button type="submit" class="button success small">{{Lang.forum.approve}}</button>
                    </form>
                    <form method="POST" action="{{deleteUrl}}" onsubmit="return confirm('{{Lang.forum.delete-confirm}}');">
                        <input type="hidden" name="_csrf" value="{{csrfToken}}">
                        <input type="hidden" name="post_id" value="{{post.id}}">
                        <button type="submit" class="button danger small">{{Lang.forum.delete}}</button>
                    </form>
                </td>
            </tr>
            {% ENDFOR %}
        </tbody>
    </table>
    {% ELSE %}
    <p>Aucun message à modérer.</p>
    {% ENDIF %}
</section>

