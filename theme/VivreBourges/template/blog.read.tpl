{% set HIDE_PAGE_TITLE = true %}
<article class="blog-article-single">
	{% IF isDraft %}
		<div style="background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 12px 18px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<i class="fa-solid fa-file-pen" style="font-size: 1.25rem; color: #d97706;"></i>
			<span>Mode Brouillon &bull; Cet article n'est pas encore publié et n'est visible que par les administrateurs.</span>
		</div>
	{% ENDIF %}
	<header class="blog-article-header">
		{% IF item.getImg() %}
			<figure class="article-featured-image">
				<img class="featured" src="{{item.getImgUrl}}" alt="{{item.getName}}" loading="eager" decoding="async" />
			</figure>
		{% ENDIF %}
		<div class="item-head">
			<p class="date">
				<i class="fa-regular fa-calendar"></i> {{formattedDate}}
				{% IF runPlugin.getConfigVal("comments") && item.getCommentsOff == false %}
					&bull;
					<a href="#comments">
						<i class="fa-regular fa-comments"></i>
						{% IF newsManager.countComments(item.getId) == 0 %}
							{{Lang.blog.comments.none-comments}}
						{% ELSEIF newsManager.countComments(item.getId) == 1 %}
							{{Lang.blog.comments-one-comment}}
						{% ELSE %}
							{{Lang.blog.comments-nb-comments(newsManager.countComments(item.getId))}}
						{% ENDIF %}
					</a>
				{% ENDIF %}
				{% IF count(categories) == 0 %}
					&bull; {{Lang.blog.categories.none}}
				{% ELSE %}
					&bull; dans
					{% FOR cat IN categories %}
						<span class="blog-label-category"><a href="{{cat.url}}">{{cat.label}}</a></span>
					{% ENDFOR %}
				{% ENDIF %}
				&bull; <a href="{{runPlugin.getPublicUrl}}"><i class="fa-solid fa-arrow-left"></i> {{Lang.blog.back-to-list}}</a>
			</p>
		</div>
	</header>

	{{TOC}}

	<div class="blog-article-content">
		{{generatedHtml}}
	</div>

	<section class="blog-share-section" style="margin: 28px 0 20px 0; padding: 16px 20px; background: rgba(24, 119, 242, 0.05); border: 1px solid rgba(24, 119, 242, 0.18); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
		<div style="font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 8px;">
			<i class="fa-solid fa-share-nodes" style="color: #1877F2;"></i> Vous avez aimé cet article ? Partagez-le !
		</div>
		<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
			<button type="button" 
			        id="blog-like-btn" 
			        data-post-id="{{item.getId}}" 
			        onclick="toggleBlogLike({{item.getId}})" 
			        style="display: inline-flex; align-items: center; gap: 6px; background: #ffffff; color: #e11d48; border: 1px solid #fecdd3; padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.2s;">
				<i class="fa-solid fa-heart"></i> <span id="blog-like-count">{{likes_count}}</span>
			</button>
			<a class="btn-facebook-share" 
			   target="_blank" 
			   rel="noopener noreferrer" 
			   href="{{ fb_share_url }}"
			   aria-label="Partager cet article sur Facebook"
			   style="display: inline-flex; align-items: center; gap: 8px; background: #1877F2; color: #ffffff; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; text-decoration: none; box-shadow: 0 2px 8px rgba(24,119,242,0.25); transition: opacity 0.2s;">
				<i class="fa-brands fa-facebook" style="font-size: 1.2rem;"></i> Partager sur Facebook
			</a>
			<button type="button" 
			        class="btn-copy-link"
			        onclick="vbCopyLink(window.location.href, this)"
			        title="Copier le lien direct vers cet article"
			        style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.2s;">
				<i class="fa-solid fa-link"></i> <span>Copier le lien</span>
			</button>
		</div>
	</section>

	{% IF runPlugin.getConfigVal("displayAuthor") %}
		<footer>
			<div class='blog-author'>
				<div class='blog-avatar'>
					<img src='{{runPlugin.getConfigVal("authorAvatar")}}' alt='{{runPlugin.getConfigVal("authorName")}}'/>
				</div>
				<div class='blog-infos'>
					<div class='blog-infos-name'>
						<span>{{runPlugin.getConfigVal("authorName")}}</span>
					</div>
					<div class='blog-infos-bio'>
						{{htmlspecialchars_decode(runPlugin.getConfigVal("authorBio"))}}
					</div>
				</div>
			</div>
		</footer>
	{% ENDIF %}
</article>

{% IF runPlugin.getConfigVal("comments") && item.getCommentsOff == false %}
	<section id="comments" class="blog-comments-section">
		<header class="comments-main-header">
			<div class="item-head">
				<h2 class="comments-main-title">
					<i class="fa-regular fa-comments"></i> {{Lang.blog-comments}}
					{% IF newsManager.countComments(item.getId) > 0 %}
						<span class="comments-count-badge">{{newsManager.countComments(item.getId)}}</span>
					{% ENDIF %}
				</h2>
			</div>
		</header>

		{% IF newsManager.countComments(item.getId) == 0 %}
			<div class="comments-empty-state">
				<div class="empty-icon"><i class="fa-regular fa-comment-dots"></i></div>
				<p class="empty-text">{{Lang.blog.there-is-no-comment}}</p>
				<p class="empty-subtext">Soyez le premier à partager votre avis sur cet article !</p>
			</div>
		{% ELSE %}
			<ul class="comments-list">
				{% FOR k, v IN newsManager.getComments() %}
					{{v.show}}
				{% ENDFOR %}
			</ul>
		{% ENDIF %}

		<div style="text-align: center; margin: 24px 0 16px 0;">
			<button type="button" 
			        id="btn-toggle-comment-form" 
			        onclick="toggleBlogCommentForm()" 
			        style="display: inline-flex; align-items: center; gap: 8px; background: #e30613; color: #ffffff; padding: 11px 22px; border: none; border-radius: 25px; font-weight: 600; font-size: 0.95rem; cursor: pointer; box-shadow: 0 4px 12px rgba(227,6,19,0.25); transition: transform 0.2s, background 0.2s;">
				<i class="fa-solid fa-pen-to-square"></i> <span id="toggle-comment-text">Laisser un commentaire</span>
			</button>
		</div>

		<div id="comments-add-container" style="display: none; transition: all 0.3s ease;">
			<div id="comments-add-respond" class="comments-form-card">
				<div class="comments-form-header">
					<h2 id="comments-title" data-title="{{Lang.blog.comments.add-comment}}">
						<i class="fa-solid fa-pen-nib"></i> {{Lang.blog.comments.add-comment}}
					</h2>
					<button id="comments-cancel-respond" class="comment-cancel-btn" title="{{Lang.blog.comments.cancel-response}}" aria-label="{{Lang.blog.comments.cancel-response}}" style="display: none;">
						<i class="fa-solid fa-xmark"></i> Annuler la réponse
					</button>
				</div>
				<form method="post" action="{{commentSendUrl}}" class="comment-form">
					{{SHOW.tokenField}}
					<input type="hidden" name="id" value="{{item.getId}}"/>
					<input type="hidden" name="commentParentId" id="commentParentId" value="0"/>
					<input type="hidden" name="back" value="{{item.getUrl}}"/>
					{% IF !isLogged %}
						<div class="comment-form-row">
							<div class="comment-form-group">
								<label for="author">{{Lang.blog.comments-name}} <span class="required">*</span></label>
								<input class="spam-protection" type="text" name="_author" value=""/>
								<input class="comment-input" type="text" name="author" id="author" required="required" placeholder="Votre nom ou pseudo"/>
							</div>
							<div class="comment-form-group">
								<label for="authorEmail">{{Lang.blog.comments-mail}} <span class="required">*</span></label>
								<input class="comment-input" type="email" name="authorEmail" id="authorEmail" required="required" placeholder="votre@email.com"/>
							</div>
						</div>
						<div class="comment-form-group">
							<label for="authorWebsite">{{Lang.blog.comments-website}}</label>
							<input class="comment-input" type="url" name="authorWebsite" id="authorWebsite" placeholder="https://votresite.fr (optionnel)"/>
						</div>
					{% ENDIF %}
					<div class="comment-form-group">
						<label for="commentContent">{{Lang.blog.comments-content}} <span class="required">*</span></label>
						<textarea class="comment-textarea" name="commentContent" id="commentContent" required="required" placeholder="Écrivez votre message ici..."></textarea>
					</div>
					{% IF !isLogged %}
						{% IF antispam %}
							{{antispamField}}
						{% ENDIF %}
					{% ENDIF %}
					<div class="comment-form-actions">
						<button type="submit" class="comment-submit-btn">
							<i class="fa-solid fa-paper-plane"></i> {{Lang.blog.comments.send}}
						</button>
					</div>
				</form>
			</div>
		</div>

		<script>
		function toggleBlogCommentForm() {
			var formContainer = document.getElementById('comments-add-container');
			var btnText = document.getElementById('toggle-comment-text');
			if (!formContainer) return;
			if (formContainer.style.display === 'none' || formContainer.style.display === '') {
				formContainer.style.display = 'block';
				btnText.textContent = 'Fermer le formulaire';
				formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
			} else {
				formContainer.style.display = 'none';
				btnText.textContent = 'Laisser un commentaire';
			}
		}

		function toggleBlogLike(postId) {
			var btn = document.getElementById('blog-like-btn');
			var countSpan = document.getElementById('blog-like-count');
			if (!btn || !countSpan) return;
			btn.disabled = true;
			fetch('/blog/like/' + postId, {
				method: 'POST',
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				}
			})
			.then(function(res) { return res.json(); })
			.then(function(data) {
				btn.disabled = false;
				if (data && typeof data.likes_count !== 'undefined') {
					countSpan.textContent = data.likes_count;
					if (data.liked) {
						btn.style.background = '#ffe4e6';
						btn.style.borderColor = '#f43f5e';
					} else {
						btn.style.background = '#ffffff';
						btn.style.borderColor = '#fecdd3';
					}
				}
			})
			.catch(function() {
				btn.disabled = false;
			});
		}
		</script>
	</section>
{% ENDIF %}
