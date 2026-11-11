<section class="module-card forum-admin-module">
    <header><i class="fa-solid fa-shield-halved"></i> {{Lang.forum.moderation}}</header>

    <div class="forum-adm-toolbar">
        <p>Messages en attente de validation avant publication sur le forum.</p>
        <div class="forum-adm-toolbar-actions">
            <a class="button" href="{{backUrl}}"><i class="fa-solid fa-arrow-left"></i> {{Lang.forum.dashboard}}</a>
        </div>
    </div>

    {% IF posts %}
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{{Lang.forum.topics}}</th>
                    <th>{{Lang.forum.author}}</th>
                    <th>{{Lang.forum.message}}</th>
                    <th>Date</th>
                    <th>{{Lang.forum.actions}}</th>
                </tr>
            </thead>
            <tbody>
                {% FOR post IN posts %}
                <tr>
                    <td>#{{post.id}}</td>
                    <td>
                        {% IF post.topic_title %}
                            <a href="{{post.topic_url}}" target="_blank"><strong>{{post.topic_title}}</strong></a>
                        {% ELSE %}
                            #{{post.topic_id}}
                        {% ENDIF %}
                    </td>
                    <td>
                        <strong>{{post.author_name}}</strong>
                        {% IF post.author_email %}<br><small>{{post.author_email}}</small>{% ENDIF %}
                    </td>
                    <td>
                        <div class="forum-adm-desc-preview">{{post.content}}</div>
                    </td>
                    <td>{{post.created_at_formatted}}</td>
                    <td>
                        <div class="forum-adm-actions-cell">
                            <form method="POST" action="{{approveUrl}}" class="forum-adm-inline-form">
                                <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                <input type="hidden" name="post_id" value="{{post.id}}">
                                <button type="submit" class="button small success"><i class="fa-solid fa-check"></i> {{Lang.forum.approve}}</button>
                            </form>
                            <form method="POST" action="{{deleteUrl}}" class="forum-adm-inline-form" onsubmit="return confirm('{{Lang.forum.delete-confirm}}');">
                                <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                <input type="hidden" name="post_id" value="{{post.id}}">
                                <button type="submit" class="button small alert"><i class="fa-solid fa-trash"></i> {{Lang.forum.delete}}</button>
                            </form>
                        </div>
                    </td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>
    </div>
    {% ELSE %}
    <div class="evo-empty">
        <i class="fa-solid fa-circle-check"></i> {{Lang.forum.no-posts}}
    </div>
    {% ENDIF %}
</section>
