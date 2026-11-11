{% INCLUDE 'forum/breadcrumb.tpl' %}

<section class="forum-topic">
    <div class="forum-topic-main-card">
        <header class="forum-topic-main-header">
            <div class="forum-header-row">
                <div class="flex-1">
                    <h1 class="forum-topic__title">
                        {% IF topic.is_pinned %}
                        <i class="fa-solid fa-thumbtack"></i>
                        {% ENDIF %}
                        {% IF topic.is_locked %}
                        <i class="fa-solid fa-lock"></i>
                        {% ENDIF %}
                        {{topic.title}}
                    </h1>
                    <div class="forum-stats-box">
                        <div><i class="fa-solid fa-eye"></i> <strong>{{topic.views}}</strong> vues</div>
                        <div><i class="fa-solid fa-comments"></i> <strong>{{topic.reply_count}}</strong> réponses</div>
                    </div>
                </div>
                {% IF isModerator %}
                <div class="forum-moderation-actions">
                    <form method="POST" action="{{topicPinUrl}}" class="inline-form">
                        <input type="hidden" name="_csrf" value="{{csrfToken}}">
                        <button type="submit" class="button {% IF topic.is_pinned %}success{% ELSE %}secondary{% ENDIF %} small">
                            <i class="fa-solid fa-thumbtack"></i> {% IF topic.is_pinned %}Désépingler{% ELSE %}Épingler{% ENDIF %}
                        </button>
                    </form>
                    <form method="POST" action="{{topicLockUrl}}" class="inline-form">
                        <input type="hidden" name="_csrf" value="{{csrfToken}}">
                        <button type="submit" class="button {% IF topic.is_locked %}alert{% ELSE %}secondary{% ENDIF %} small">
                            <i class="fa-solid fa-lock"></i> {% IF topic.is_locked %}Déverrouiller{% ELSE %}Verrouiller{% ENDIF %}
                        </button>
                    </form>
                </div>
                {% ENDIF %}
            </div>
        </header>

        {% IF firstPost %}
        <div class="forum-topic-main-content" id="p{{firstPost.id}}">
            <div class="forum-post__header forum-post__header--first">
                <div class="forum-author-info">
                    {% IF firstPost.author.has_valid_avatar %}
                    <div class="forum-avatar">
                        <img src="{{firstPost.author.avatar}}" alt="{{firstPost.author.username}}">
                    </div>
                    {% ELSE %}
                    <div class="forum-avatar forum-avatar-tone-{{firstPost.author.avatar_tone}}">
                        {{firstPost.author.avatar_initials}}
                    </div>
                    {% ENDIF %}
                    <div>
                        <strong class="forum-post-author-name">{{firstPost.author.username}}</strong>
                        {% IF firstPost.author.has_mysql_data %}
                        <span class="forum-badge-verified">Vérifié</span>
                        {% ENDIF %}
                        {% IF firstPost.author.group_name %}
                        <span class="forum-badge-group">{{firstPost.author.group_name}}</span>
                        {% ENDIF %}
                        <div class="forum-post-meta">
                            <i class="fa-solid fa-calendar"></i> {{firstPost.created_at_formatted}}
                            {% IF firstPost.author.created_at_formatted %}
                            <div class="author-meta-detail">
                                <i class="fa-solid fa-user-clock"></i> Membre depuis le {{firstPost.author.created_at_formatted}}
                            </div>
                            {% ENDIF %}
                            {% IF firstPost.author.post_count > 0 %}
                            <div class="author-meta-detail">
                                <i class="fa-solid fa-message"></i> {{firstPost.author.post_count}} messages
                            </div>
                            {% ENDIF %}
                            {% IF firstPost.is_edited %}
                            <span><i class="fa-solid fa-edit"></i> Modifié le {{firstPost.edited_at_formatted}}</span>
                            {% ENDIF %}
                        </div>
                    </div>
                </div>
                <div class="forum-post-actions">
                    {% IF firstPost.can_edit %}
                    <a href="{{firstPost.edit_url}}" class="button secondary small">
                        <i class="fa-solid fa-edit"></i> Modifier
                    </a>
                    {% ENDIF %}
                    <form hx-post="{{firstPost.like_url}}" hx-target="this" hx-swap="outerHTML" method="POST" action="{{firstPost.like_url}}" class="inline-form">
                        <input type="hidden" name="_csrf" value="{{csrfToken}}">
                        <button type="submit" class="button {% IF firstPost.is_liked_by_current_user %}primary{% ELSE %}secondary{% ENDIF %} small">
                            <i class="fa-solid fa-thumbs-up"></i> {{firstPost.like_count}}
                        </button>
                    </form>
                    {% HOOK.forum:topic-post-actions(firstPost) %}
                    {% IF isModerator %}
                    <form method="POST" action="{{moderationTopicDeleteUrl}}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce sujet ?');" class="inline-form">
                        <input type="hidden" name="_csrf" value="{{csrfToken}}">
                        <input type="hidden" name="topic_id" value="{{topic.id}}">
                        <button type="submit" class="button alert small">
                            <i class="fa-solid fa-trash"></i> Supprimer
                        </button>
                    </form>
                    {% ENDIF %}
                </div>
            </div>
            <div class="forum-post__content forum-post__content--first">
                {{firstPost.content}}
            </div>
        </div>
        {% ENDIF %}
    </div>

    <div class="forum-posts forum-posts-list">
        {% IF replies %}
            {% FOR post IN replies %}
            <article class="forum-post {% IF post.is_pending %}forum-post--pending{% ENDIF %}" id="p{{post.id}}">
                <div class="forum-post__header">
                    <div class="forum-author-info">
                        {% IF post.author.has_valid_avatar %}
                        <div class="forum-avatar forum-avatar-sm">
                            <img src="{{post.author.avatar}}" alt="{{post.author.username}}">
                        </div>
                        {% ELSE %}
                        <div class="forum-avatar forum-avatar-sm forum-avatar-tone-{{post.author.avatar_tone}}">
                            {{post.author.avatar_initials}}
                        </div>
                        {% ENDIF %}
                        <div>
                            <strong class="forum-post-author-name">{{post.author.username}}</strong>
                            {% IF post.author.has_mysql_data %}
                            <span class="forum-badge-verified">Vérifié</span>
                            {% ENDIF %}
                            {% IF post.author.group_name %}
                            <span class="forum-badge-group">{{post.author.group_name}}</span>
                            {% ENDIF %}
                            {% IF post.is_pending %}
                            <span class="forum-badge-pending">
                                <i class="fa-solid fa-clock"></i> En attente de modération
                            </span>
                            {% ENDIF %}
                            <div class="forum-post-meta">
                                <i class="fa-solid fa-calendar"></i> {{post.created_at_formatted}}
                                {% IF post.author.created_at_formatted %}
                                <div class="author-meta-detail">
                                    <i class="fa-solid fa-user-clock"></i> Membre depuis le {{post.author.created_at_formatted}}
                                </div>
                                {% ENDIF %}
                                {% IF post.author.post_count > 0 %}
                                <div class="author-meta-detail">
                                    <i class="fa-solid fa-message"></i> {{post.author.post_count}} messages
                                </div>
                                {% ENDIF %}
                                {% IF post.is_edited %}
                                <span><i class="fa-solid fa-edit"></i> Modifié le {{post.edited_at_formatted}}</span>
                                {% ENDIF %}
                            </div>
                        </div>
                    </div>
                    <div class="forum-post-actions">
                        {% IF post.can_edit %}
                        <a href="{{post.edit_url}}" class="button secondary small">
                            <i class="fa-solid fa-edit"></i> Modifier
                        </a>
                        {% ENDIF %}
                        {% INCLUDE 'forum/like-button.tpl' %}
                        {% HOOK.forum:topic-post-actions(post) %}

                        {% IF isModerator %}
                        {% IF post.is_pending %}
                        <form method="POST" action="{{moderationApproveUrl}}" class="inline-form">
                            <input type="hidden" name="_csrf" value="{{csrfToken}}">
                            <input type="hidden" name="post_id" value="{{post.id}}">
                            <input type="hidden" name="redirect" value="{{topic.url}}">
                            <button type="submit" class="button success small">
                                <i class="fa-solid fa-check"></i> Approuver
                            </button>
                        </form>
                        {% ENDIF %}
                        <form method="POST" action="{{moderationDeleteUrl}}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');" class="inline-form">
                            <input type="hidden" name="_csrf" value="{{csrfToken}}">
                            <input type="hidden" name="post_id" value="{{post.id}}">
                            <input type="hidden" name="redirect" value="{{topic.url}}">
                            <button type="submit" class="button alert small">
                                <i class="fa-solid fa-trash"></i> Supprimer
                            </button>
                        </form>
                        {% ENDIF %}
                    </div>
                </div>
                <div class="forum-post__content">
                    {{post.content}}
                </div>
            </article>
            {% ENDFOR %}
        {% ELSE %}
            {% IF NOT firstPost %}
            <div class="forum-empty-box">
                <i class="fa-solid fa-comments"></i>
                <p>Aucune réponse pour le moment.</p>
            </div>
            {% ENDIF %}
        {% ENDIF %}
    </div>

    {% IF hasPagination %}
    <div class="forum-pagination-wrapper">
        {% FOR pageItem IN paginationPages %}
        <a href="{{pageItem.url}}" class="button {% IF pageItem.is_current %}primary{% ELSE %}secondary{% ENDIF %} pagination-btn">
            {{pageItem.number}}
        </a>
        {% ENDFOR %}
    </div>
    {% ENDIF %}
</section>

<section class="forum-reply-section">
    <h2>
        <i class="fa-solid fa-reply"></i> Répondre au sujet
    </h2>
    {% IF canPost %}
        {% IF topic.is_locked %}
        <div class="forum-reply-lock-msg">
            <i class="fa-solid fa-lock"></i> Ce sujet est verrouillé. Vous ne pouvez plus y répondre.
        </div>
        {% ELSE %}
        <form method="POST" action="{{createPostUrl}}" class="forum-reply__form" novalidate>
            <input type="hidden" name="_csrf" value="{{csrfToken}}">
            <input type="hidden" name="topic_id" value="{{topic.id}}">

            <div class="form-group">
                <label class="form-label-bold">Votre message</label>
                <textarea name="content" class="editor forum-editor-input" rows="8" required></textarea>
            </div>
            <div class="form-actions form-actions-row">
                <button type="submit" class="button primary">
                    <i class="fa-solid fa-paper-plane"></i> Envoyer la réponse
                </button>
                <a href="{{replyPageUrl}}" class="button secondary">
                    <i class="fa-solid fa-edit"></i> Répondre sur une page dédiée
                </a>
            </div>
        </form>
        {% ENDIF %}
    {% ELSE %}
    <div class="forum-login-required-msg">
        <i class="fa-solid fa-right-to-bracket"></i> Vous devez être connecté pour répondre à ce sujet.
        <a href="/users/login" class="button primary small">Se connecter</a>
    </div>
    {% ENDIF %}
</section>
