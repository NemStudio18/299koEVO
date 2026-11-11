<div class="poi-feed-container">
    <div class="poi-feed-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">
        <div class="poi-feed-title" style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255, 140, 66, 0.15); color: #ff8c42; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                <i class="fa-solid fa-newspaper"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: #1e293b;">Mur d'Actualités &amp; Nouveautés</h4>
                <span style="font-size: 0.8rem; color: #64748b;">Directement depuis l'établissement</span>
            </div>
        </div>
        <span style="font-size: 0.8rem; font-weight: 600; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; padding: 4px 12px; border-radius: 20px;">
            <?= count($posts) ?> publication(s)
        </span>
    </div>

    <?php if (!empty($canManage)): ?>
        <div class="poi-owner-publish-wrapper" style="margin-bottom: 25px;">
            <?php include __DIR__ . '/publish_box.tpl'; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <div class="poi-feed-empty" style="text-align: center; padding: 35px 20px; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 14px; margin: 15px 0;">
            <div style="width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-regular fa-comment-dots"></i>
            </div>
            <h5 style="margin: 0 0 6px; font-size: 1.05rem; color: #334155; font-weight: 700;">Aucune publication pour l'instant</h5>
            <p style="margin: 0; font-size: 0.9rem; color: #64748b;">Cet établissement n'a pas encore partagé d'actualité récente sur son mur.</p>
        </div>
    <?php else: ?>
        <div class="poi-feed-list" style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($posts as $post): ?>
                <div class="poi-feed-item" id="poi-post-<?= $post['id'] ?>" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); transition: all 0.2s ease;">
                    
                    <!-- En-tête de la publication -->
                    <div class="poi-feed-item-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span class="poi-feed-item-badge <?= htmlspecialchars($post['post_type']) ?>" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; padding: 3px 10px; border-radius: 20px; background: #fff7ed; color: #ea580c; border: 1px solid rgba(234, 88, 12, 0.2);">
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
                        
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php if (!empty($post['pinned'])): ?>
                                <span style="font-size: 0.75rem; color: #d97706; font-weight: 700; background: #fef3c7; padding: 2px 8px; border-radius: 12px;">
                                    <i class="fa-solid fa-thumbtack"></i> Épinglé
                                </span>
                            <?php endif; ?>
                            <span class="poi-feed-item-time" style="font-size: 0.8rem; color: #94a3b8;">
                                <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($post['time_ago']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Titre du post -->
                    <?php if (!empty($post['title'])): ?>
                        <h5 class="poi-feed-item-title" style="margin: 0 0 10px; font-size: 1.15rem; font-weight: 700; color: #0f172a; line-height: 1.35;">
                            <?= htmlspecialchars($post['title']) ?>
                        </h5>
                    <?php endif; ?>

                    <!-- Contenu textuel -->
                    <div class="poi-feed-item-content" style="font-size: 0.95rem; color: #334155; line-height: 1.6; margin-bottom: 14px;">
                        <p style="margin: 0; white-space: pre-line;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    </div>

                    <!-- Média / Photo attachée -->
                    <?php if (!empty($post['media_url'])): ?>
                        <div class="poi-feed-item-media" style="margin-bottom: 14px; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; max-height: 420px; background: #000;">
                            <img src="<?= htmlspecialchars($post['media_url']) ?>" alt="Photo de publication" style="width: 100%; height: auto; max-height: 420px; object-fit: cover; display: block;" loading="lazy" />
                        </div>
                    <?php endif; ?>

                    <!-- Barre d'actions : Like, Commenter, Partager -->
                    <div class="poi-feed-item-footer" style="display: flex; align-items: center; gap: 10px; padding-top: 12px; border-top: 1px solid #f1f5f9; flex-wrap: wrap;">
                        <button type="button" class="feed-action-btn btn-like <?= !empty($post['has_liked']) ? 'active' : '' ?>" 
                                onclick="LocalFeedUI.toggleLike(this, <?= $post['id'] ?>)"
                                style="background: <?= !empty($post['has_liked']) ? '#fee2e2' : '#f8fafc' ?>; border: 1px solid <?= !empty($post['has_liked']) ? '#f87171' : '#e2e8f0' ?>; color: <?= !empty($post['has_liked']) ? '#dc2626' : '#475569' ?>; border-radius: 20px; padding: 6px 14px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                            <i class="<?= !empty($post['has_liked']) ? 'fa-solid' : 'fa-regular' ?> fa-heart" style="color: <?= !empty($post['has_liked']) ? '#dc2626' : '#ef4444' ?>;"></i>
                            <span>J'aime</span>
                            <span class="like-count">(<?= (int)($post['likes_count'] ?? 0) ?>)</span>
                        </button>

                        <button type="button" class="feed-action-btn btn-comment" 
                                onclick="LocalFeedUI.toggleComments(<?= $post['id'] ?>)"
                                style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; border-radius: 20px; padding: 6px 14px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                            <i class="fa-regular fa-comment" style="color: #3b82f6;"></i>
                            <span>Commenter</span>
                            <span class="post-<?= $post['id'] ?>-comment-count">(<?= (int)($post['comments_count'] ?? 0) ?>)</span>
                        </button>

                        <button type="button" class="feed-action-btn btn-share" 
                                onclick="LocalFeedUI.sharePost(<?= $post['id'] ?>, '<?= addslashes(htmlspecialchars($post['title'] ?: 'Actualité')) ?>', '<?= addslashes(htmlspecialchars(substr($post['content'], 0, 100))) ?>')"
                                style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; border-radius: 20px; padding: 6px 14px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                            <i class="fa-solid fa-share-nodes" style="color: #64748b;"></i>
                            <span>Partager</span>
                        </button>

                        <button type="button" class="feed-action-btn btn-copy-link" 
                                onclick="vbCopyLink('https://vivre-bourges.fr/localfeed/feed#feed-post-<?= $post['id'] ?>', this)"
                                style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; border-radius: 20px; padding: 6px 14px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;"
                                title="Copier le lien direct de cette publication">
                            <i class="fa-solid fa-link" style="color: #64748b;"></i>
                            <span>Copier</span>
                        </button>
                    </div>

                    <!-- Zone dépliable des commentaires -->
                    <div id="comments-box-<?= $post['id'] ?>" class="poi-comments-container" style="display: none; margin-top: 14px; padding-top: 14px; border-top: 1px solid #f1f5f9; background: #fafafa; border-radius: 10px; padding: 12px 14px;">
                        
                        <!-- Liste des commentaires existants -->
                        <div id="comments-list-<?= $post['id'] ?>" class="poi-comments-list" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                            <?php if (empty($post['comments'])): ?>
                                <div class="no-comments-yet" style="font-size: 0.85rem; color: #94a3b8; font-style: italic; padding: 4px 0;">
                                    Soyez le premier à commenter cette publication !
                                </div>
                            <?php else: ?>
                                <?php foreach ($post['comments'] as $com): ?>
                                    <div class="poi-comment-item" id="comment-item-<?= $com['id'] ?>" style="display: flex; gap: 10px; align-items: flex-start;">
                                        <div class="comment-author-avatar" style="font-size: 1.5rem; color: #cbd5e1; line-height: 1;">
                                            <i class="fa-solid fa-user-circle"></i>
                                        </div>
                                        <div class="comment-bubble" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 8px 12px; flex: 1; font-size: 0.88rem;">
                                            <div class="comment-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                                                <span class="comment-author-name" style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($com['display_name']) ?></span>
                                                <span class="comment-time" style="font-size: 0.75rem; color: #94a3b8;"><?= htmlspecialchars($com['time_ago']) ?></span>
                                            </div>
                                            <div class="comment-body" style="color: #334155; line-height: 1.4; word-break: break-word;">
                                                <?= nl2br(htmlspecialchars($com['content'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Formulaire d'ajout de commentaire -->
                        <form id="comment-form-<?= $post['id'] ?>" onsubmit="LocalFeedUI.submitComment(event, <?= $post['id'] ?>)" style="display: flex; gap: 8px; align-items: center; margin-top: 8px;">
                            <input type="text" name="content" placeholder="Écrire un commentaire..." required 
                                   style="flex: 1; padding: 8px 14px; font-size: 0.88rem; border: 1.5px solid #cbd5e1; border-radius: 20px; outline: none; background: #ffffff; transition: border-color 0.2s;" />
                            <button type="submit" style="background: #ff8c42; color: #ffffff; border: none; border-radius: 20px; padding: 8px 16px; font-size: 0.85rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(255, 140, 66, 0.3);">
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
