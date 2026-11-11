{% set HIDE_PAGE_TITLE = true %}
<article>
	<header>
		{% IF item.getImg() %}
			<img class="featured" src="{{item.getImgUrl}}" alt="{{item.getName}}"/>
		{% ENDIF %}
		<div class="item-head">
			<p class="date">
			{{formattedDate}}
				{% IF runPlugin.getConfigVal("comments") && item.getCommentsOff == false %}
					|
					<a href="#comments">
					{% IF newsManager.countComments() == 0 %}
						{{Lang.blog.comments.none-comments}}
					{% ELSEIF newsManager.countComments() == 1 %}
						{{Lang.blog.comments-one-comment}}
					{% ELSE %}
						{{Lang.blog.comments-nb-comments(newsManager.countComments())}}
					{% ENDIF %}
					</a> | 
				{% ENDIF %}
				{% IF count(categories) == 0 %}
					{{Lang.blog.categories.none}}
				{% ELSE %}
					dans
					{% FOR cat IN categories %}
						<span class="blog-label-category"><a href="{{cat.url}}">{{cat.label}}</a></span> 
					{% ENDFOR %}
				{% ENDIF %}
				| <a href="{{runPlugin.getPublicUrl}}">{{Lang.blog.back-to-list}}</a>
			</p>
		</div>
		
	</header>
	{{TOC}}
	{{generatedHtml}}

	<section class="blog-share-section" style="margin: 28px 0 20px 0; padding: 16px 20px; background: var(--section-head-bg-color, #f3f1ec); border: 1px solid var(--border-color, #e2e8f0); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
		<div style="font-weight: 600; color: var(--font-color, #1e293b); display: flex; align-items: center; gap: 8px;">
			<i class="fa-solid fa-share-nodes" style="color: var(--primary-color, #1d4ed8);"></i> Vous avez aimé cet article ? Partagez-le !
		</div>
		<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
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
			        style="display: inline-flex; align-items: center; gap: 8px; background: var(--section-bg-color, #ffffff); color: var(--font-color, #475569); border: 1px solid var(--form-border-color, #cbd5e1); padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.2s;">
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
	<section id="comments">
		<header>
			<div class="item-head">
				<h2>{{Lang.blog-comments}}</h2>
			</div>
		</header>
		{% IF newsManager.countComments(item.getId) == 0 %}
			<p>{{Lang.blog.there-is-no-comment}}</p>
		{% ELSE %}
			<ul class="comments-list">
				{% FOR k, v IN newsManager.getComments() %}
					{{v. show}}
				{% ENDFOR %}
			</ul>
		{% ENDIF %}
		<div id="comments-add-container">
			<div id="comments-add-respond">
				<h2 id="comments-title" data-title="{{Lang.blog.comments.add-comment}}">{{Lang.blog.comments.add-comment}}</h2>
				<form method="post" action="{{commentSendUrl}}">
					{{SHOW.tokenField}}
					<button id="comments-cancel-respond" class="small" title="{{Lang.blog.comments.cancel-response}}" aria-label="{{Lang.blog.comments.cancel-response}}"><i class="fa-solid fa-xmark"></i></button>
					<input type="hidden" name="id" value="{{item.getId}}"/>
					<input type="hidden" name="commentParentId" id="commentParentId" value="0"/>
					<input type="hidden" name="back" value="{{item.getUrl}}"/>
					{% IF !isLogged %}
						<p>
							<label for="author">{{Lang.blog.comments-name}}</label><br>
							<input class="spam-protection" type="text" name="_author" value=""/>
							<input type="text" name="author" id="author" required="required"/>
						</p>
						<p>
							<label for="authorEmail">{{Lang.blog.comments-mail}}</label><br><input type="email" name="authorEmail" id="authorEmail" required="required"/></p>
						<p>
						<p>
							<label for="authorWebsite">{{Lang.blog.comments-website}}</label><br><input type="url" name="authorWebsite" id="authorWebsite"/></p>
					{% ENDIF %}
					<p>
						<label for="commentContent">{{Lang.blog.comments-content}}</label>
						<textarea name="commentContent" id="commentContent" required="required"></textarea>
					</p>
					{% IF !isLogged %}
						{% IF antispam %}
							{{antispamField}}
						{% ENDIF %}
					{% ENDIF %}
					<p><input type="submit" value="{{Lang.blog.comments.send}}"/></p>
				</form>
			</div>
		</div>
	</section>
{% ENDIF %}