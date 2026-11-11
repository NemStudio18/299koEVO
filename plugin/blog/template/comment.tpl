<li class="comments-item">
    <article class="comment" id="comment{{comment.getId}}">
        <div class="comment-infos" id="comment{{comment.getId}}Infos" data-author="{{Lang.blog.comments.respond-to(comment.getAuthor)}}">
            <div class="comment-avatar">
                <img src="{{comment.getAuthorAvatar}}" alt="{{comment.getAuthor}}" title="{{comment.getAuthor}}" />
            </div>
            <div class="comment-meta-wrap">
                <div class="comment-author">
                    {% if comment.getAuthorWebsite %}
                        <a href="{{comment.getAuthorWebsite}}" target="_blank">{{comment.getAuthor}}</a>
                    {% else %}
                        {{comment.getAuthor}}
                    {% endif %}
                </div>
                <div class="comment-metadata">
                    <a href="#comment{{comment.getId}}"><i class="fa-regular fa-clock"></i> {{util::getNaturalDate(comment.getDate())}}</a>
                </div>
            </div>
        </div>
        <div class="comment-content">
		{{nl2br(comment.getContent())}}
        </div>
        <div class="comment-reply">
            <button class="btn-add-respond small" data-id="{{comment.getId}}">
                <i class="fa-solid fa-reply"></i> {{Lang.blog.comments.respond}}
            </button>
        </div>
	</article>
    {% if comment.hasReplies %}
    <ul class="comments-list comments-children">
        {% for reply in comment.replies %}
            {{reply.show}}
        {% endfor %}
    </ul>
    {% endif %}
</li>