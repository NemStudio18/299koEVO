{% set HIDE_PAGE_TITLE = true %}
<article class="blog-article-single">
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

	{% IF seoActive and SeoShareConfig and (SeoShareConfig.facebook or SeoShareConfig.x or SeoShareConfig.linkedin) %}
	<section class="seo-share">
		<h3>{{Lang.seo.share-title}}</h3>
		<div class="seo-share-buttons">
			{% SET shareUrl = SITE_URL ~ item.getUrl %}
			{% SET shareTitle = item.getName %}
			{% IF SeoShareConfig.facebook %}
			<a class="seo-share-button facebook" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{shareUrl}}">
				<i class="fa-brands fa-facebook"></i> Facebook
			</a>
			{% ENDIF %}
			{% IF SeoShareConfig.x %}
			<a class="seo-share-button twitter" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url={{shareUrl}}">
				<i class="fa-brands fa-x-twitter"></i> X
			</a>
			{% ENDIF %}
			{% IF SeoShareConfig.linkedin %}
			<a class="seo-share-button linkedin" target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url={{shareUrl}}">
				<i class="fa-brands fa-linkedin"></i> LinkedIn
			</a>
			{% ENDIF %}
		</div>
	</section>
	{% ENDIF %}

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

		<div id="comments-add-container">
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
	</section>
{% ENDIF %}
