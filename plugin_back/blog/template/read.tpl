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