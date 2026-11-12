{% IF displayCategoriesBar && count(allCategories) > 0 %}
	<nav class="blog-categories-nav-bar" aria-label="{{Lang.blog-categories}}">
		<div class="blog-categories-scroll">
			<a href="{{blogHomeUrl}}" class="blog-cat-pill{% IF !currentCategory %} active{% ENDIF %}">{{Lang.blog-all-categories}}</a>
			{% FOR cat IN allCategories %}
				<a href="{{cat.url}}" class="blog-cat-pill{% IF currentCategory && currentCategory.id == cat.id %} active{% ENDIF %}">
					{{cat.label}}
					<span class="cat-pill-count">{{cat.count}}</span>
				</a>
			{% ENDFOR %}
		</div>
	</nav>
{% ENDIF %}
{% IF mode === "list_empty" %}
	<p>{{Lang.galerie.no-item-found}}</p>
{% ELSE %}
	<div class="blog-posts-list">
	{% FOR k, v IN news %}
		<article class="blog-post-card{% IF v.img %} has-thumbnail{% ENDIF %}">
			{% IF v.img %}
				<a href="{{v.url}}" class="blog-card-media" aria-hidden="true" tabindex="-1">
					<img class="featured blog-thumb" src="{{v.imgUrl}}" alt="{{v.img}}" loading="lazy"/>
				</a>
			{% ENDIF %}
			<div class="blog-card-body">
				<header class="blog-card-header">
					<h2>
						<a href="{{v.url}}">{{v.name}}</a>
					</h2>
					<p class="date">
						<span><i class="fa-regular fa-calendar"></i> {{v.date}}</span>
						{% IF runPlugin.getConfigVal("comments") && v.commentsOff == false %}
							<span class="declic-sep" aria-hidden="true">·</span>
							<a href="{{v.url}}#comments"><i class="fa-regular fa-comment"></i> commentaire{% IF newsManager.countComments(v.id) > 1 %}s{% ENDIF %}</a>
						{% ENDIF %}
						<span class="declic-sep" aria-hidden="true">·</span>
						<span class="item-categories"><i class="fa-regular fa-folder-open"></i>
							{% IF count(v.cats) == 0 %}
								Non classé
							{% ELSE %}
								{% FOR cat IN v.cats %}
									<span class="blog-label-category"><a href="{{cat.url}}">{{cat.label}}</a></span>
								{% ENDFOR %}
							{% ENDIF %}
						</span>
					</p>
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
			</div>
		</article>
	{% ENDFOR %}
	</div>
	{% IF pagination %}
		<nav class="blog-pagination" aria-label="Pagination">
			<ul class="pagination">
				{% FOR k, v IN pagination %}
					{% IF v.ellipsis %}
						<li class="pagination-ellipsis" aria-hidden="true"><span>…</span></li>
					{% ELSE %}
						<li class="{% IF v.active %}active{% ENDIF %}">
							<a href="{{v.url}}"{% IF v.active %} aria-current="page"{% ENDIF %}>{{v.num}}</a>
						</li>
					{% ENDIF %}
				{% ENDFOR %}
			</ul>
		</nav>
	{% ENDIF %}
{% ENDIF %}
