{% IF mode === "list_empty" %}
	<p>{{Lang.galerie.no-item-found}}</p>
{% ELSE %}
	{% IF allCategories %}
		<nav class="blog-categories-nav-bar" aria-label="Catégories du blog">
			<div class="blog-categories-scroll">
				<a href="/blog" class="blog-cat-pill {% IF not currentCategory %}active{% ENDIF %}">
					<i class="fa-solid fa-layer-group"></i> Tous les articles
				</a>
				{% FOR cat IN allCategories %}
					<a href="{{cat.url}}" class="blog-cat-pill {% IF currentCategory.id == cat.id %}active{% ENDIF %}">
						<i class="fa-regular fa-folder"></i> {{cat.label}} <span class="cat-pill-count">{{cat.count}}</span>
					</a>
				{% ENDFOR %}
			</div>
		</nav>
	{% ENDIF %}
	<div class="blog-articles-grid">
	{% FOR k, v IN news %}
		<article class="blog-article-card">
			<header>
				{% IF v.img %}
					<figure class="article-featured-image">
						<a href="{{v.url}}">
							<img class="featured" src="{{v.imgUrl}}" alt="{{v.name}}" loading="lazy" decoding="async"/>
						</a>
					</figure>
				{% ENDIF %}
				<div class="item-head">
					<h2>
						<a href="{{v.url}}">{{v.name}}</a>
					</h2>
					<p class="date">
						<i class="fa-regular fa-calendar"></i> {{v.date}}
						{% IF runPlugin.getConfigVal("comments") && v.commentsOff == false %}
							<span class="meta-sep">•</span>
							<i class="fa-regular fa-comment"></i>
							{{newsManager.countComments(v.id)}} commentaire{% IF newsManager.countComments(v.id) > 1 %}s{% ENDIF %}
						{% ENDIF %}
						{% IF count(v.cats) > 0 %}
							<span class="meta-sep">•</span>
							<i class="fa-regular fa-folder-open"></i>
							{% FOR cat IN v.cats %}
								<span class="blog-label-category"><a href="{{cat.url}}">{{cat.label}}</a></span>
							{% ENDFOR %}
						{% ENDIF %}
					</p>
				</div>
			</header>

			{% IF runPlugin.getConfigVal("hideContent") == false %}
				<div class="blog-card-excerpt">
					{% IF v.intro %}
						{{htmlspecialchars_decode(v.intro)}}
					{% ELSE %}
						{{htmlspecialchars_decode(v.content)}}
					{% ENDIF %}
				</div>
			{% ENDIF %}

			<div class="blog-card-footer">
				<a href="{{v.url}}" class="read-more-btn">Lire l'article <i class="fa-solid fa-arrow-right"></i></a>
			</div>
		</article>
	{% ENDFOR %}
	</div>
	{% IF pagination %}
		<ul class="pagination">
			{% FOR k, v IN pagination %}
				<li>
					<a href="{{v.url}}">{{v.num}}</a>
				</li>
			{% ENDFOR %}
		</ul>
	{% ENDIF %}
{% ENDIF %}
