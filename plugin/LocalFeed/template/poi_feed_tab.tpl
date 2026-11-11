<div class="poi-feed-container">
    <div class="poi-feed-header">
        <div class="poi-feed-title">
            <div class="poi-feed-title-icon">
                <i class="fa-solid fa-newspaper"></i>
            </div>
            <div>
                <h4>Mur d'Actualités &amp; Nouveautés</h4>
                <span class="poi-feed-subtitle">Directement depuis l'établissement</span>
            </div>
        </div>
        <span class="poi-feed-count-badge">
            <?= count($posts) ?> publication(s)
        </span>
    </div>

    <?php if (!empty($canManage)): ?>
        <div class="poi-owner-publish-wrapper">
            <?php include __DIR__ . '/publish_box.tpl'; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <div class="poi-feed-empty">
            <div class="poi-feed-empty-icon">
                <i class="fa-regular fa-comment-dots"></i>
            </div>
            <h5>Aucune publication pour l'instant</h5>
            <p>Cet établissement n'a pas encore partagé d'actualité récente sur son mur.</p>
        </div>
    <?php else: ?>
        <div class="poi-feed-list">
            <?php foreach ($posts as $post): ?>
                <div class="poi-feed-item" id="poi-post-<?= $post['id'] ?>">
                    
                    <!-- En-tête de la publication -->
                    <div class="poi-feed-item-header">
                        <span class="poi-feed-item-badge <?= htmlspecialchars($post['post_type']) ?>">
                            <?php if ($post['post_type'] === 'special_offer'): ?>
                                <i class="fa-solid fa-tag"></i> Bon plan
                            <?php elseif ($post['post_type'] === 'photo'): ?>
                                <i class="fa-solid fa-camera"></i> Photo du jour
                            <?php elseif ($post['post_type'] === 'event'): ?>
                                <i class="fa-solid fa-calendar-day"></i> Événement
                            <?php elseif ($post['post_type'] === 'alert'): ?>
                                <i class="fa-solid fa-triangle-exclamation"></i> Info pratique
                            <?php else: ?>
                                <i class="fa-solid fa-newspaper"></i> Actualité
                            <?php endif; ?>
                        </span>
                        
                        <div class="poi-feed-item-meta">
                            <?php if (!empty($post['pinned'])): ?>
                                <span class="poi-feed-pinned-badge">
                                    <i class="fa-solid fa-thumbtack"></i> Épinglé
                                </span>
                            <?php endif; ?>
                            <span class="poi-feed-item-time">
                                <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($post['time_ago']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Titre du post -->
                    <?php if (!empty($post['title'])): ?>
                        <h5 class="poi-feed-item-title">
                            <?= htmlspecialchars($post['title']) ?>
                        </h5>
                    <?php endif; ?>

                    <!-- Contenu textuel -->
                    <div class="poi-feed-item-content">
                        <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    </div>

                    <!-- Média / Photo attachée -->
                    <?php if (!empty($post['media_url'])): ?>
                        <div class="poi-feed-item-media">
                            <img src="<?= htmlspecialchars($post['media_url']) ?>" alt="Photo de publication" loading="lazy" />
                        </div>
                    <?php endif; ?>

                    <!-- Barre d'actions : Like, Commenter, Partager -->
                    <div class="poi-feed-item-footer">
                        <button type="button" class="feed-action-btn btn-like <?= !empty($post['has_liked']) ? 'active' : '' ?>" 
                                onclick="LocalFeedUI.toggleLike(this, <?= $post['id'] ?>)">
                            <i class="<?= !empty($post['has_liked']) ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                            <span>J'aime</span>
                            <span class="like-count">(<?= (int)($post['likes_count'] ?? 0) ?>)</span>
                        </button>

                        <button type="button" class="feed-action-btn btn-comment" 
                                onclick="LocalFeedUI.toggleComments(<?= $post['id'] ?>)">
                            <i class="fa-regular fa-comment"></i>
                            <span>Commenter</span>
                            <span class="post-<?= $post['id'] ?>-comment-count">(<?= (int)($post['comments_count'] ?? 0) ?>)</span>
                        </button>

                        <button type="button" class="feed-action-btn btn-share" 
                                onclick="LocalFeedUI.sharePost(<?= $post['id'] ?>, '<?= addslashes(htmlspecialchars($post['title'] ?: 'Actualité')) ?>', '<?= addslashes(htmlspecialchars(substr($post['content'], 0, 100))) ?>')">
                            <i class="fa-solid fa-share-nodes"></i>
                            <span>Partager</span>
                        </button>

                        <button type="button" class="feed-action-btn btn-copy-link" 
                                onclick="vbCopyLink('https://vivre-bourges.fr/localfeed/feed#feed-post-<?= $post['id'] ?>', this)"
                                title="Copier le lien direct de cette publication">
                            <i class="fa-solid fa-link"></i>
                            <span>Copier</span>
                        </button>
                    </div>

                    <!-- Zone dépliable des commentaires -->
                    <div id="comments-box-<?= $post['id'] ?>" class="poi-comments-container">
                        
                        <!-- Liste des commentaires existants -->
                        <div id="comments-list-<?= $post['id'] ?>" class="poi-comments-list">
                            <?php if (empty($post['comments'])): ?>
                                <div class="no-comments-yet">
                                    Soyez le premier à commenter cette publication !
                                </div>
                            <?php else: ?>
                                <?php foreach ($post['comments'] as $com): ?>
                                    <div class="poi-comment-item" id="comment-item-<?= $com['id'] ?>">
                                        <div class="comment-author-avatar">
                                            <i class="fa-solid fa-user-circle"></i>
                                        </div>
                                        <div class="comment-bubble">
                                            <div class="comment-header-row">
                                                <span class="comment-author-name"><?= htmlspecialchars($com['display_name']) ?></span>
                                                <span class="comment-time"><?= htmlspecialchars($com['time_ago']) ?></span>
                                            </div>
                                            <div class="comment-body">
                                                <?= nl2br(htmlspecialchars($com['content'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Formulaire d'ajout de commentaire -->
                        <form id="comment-form-<?= $post['id'] ?>" class="poi-comment-form" onsubmit="LocalFeedUI.submitComment(event, <?= $post['id'] ?>)">
                            <input type="text" name="content" placeholder="Écrire un commentaire..." required />
                            <button type="submit" class="poi-comment-submit">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Envoyer</span>
                            </button>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
