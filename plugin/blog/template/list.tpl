{% IF mode === "list_empty" %}
	<p>{{Lang.galerie.no-item-found}}</p>
{% ELSE %}
	{% FOR k, v IN news %}
		<article>
			{% IF runPlugin.getConfigVal("hideContent") == false %}
				<header>
					{% IF v.img %}
						<img class="featured" src="{{v.imgUrl}}" alt="{{v.img}}"/>
					{% ENDIF %}
					<div class="item-head">
						<h2>
							<a href="{{v.url}}">{{v.name}}</a>
						</h2>
						<p class="date">{{v.date}}
							{% IF runPlugin.getConfigVal("comments") && v.commentsOff == false %}
								|
								commentaire{% IF newsManager.countComments(v.id) > 1 %}s{% ENDIF %}</a>
							{% ENDIF %}
							 | <span class="item-categories"><i class="fa-regular fa-folder-open"></i>
							{% IF count(v.cats) == 0 %}
								Non classé
							{% ELSE %}
								{% FOR cat IN v.cats %}
									<span class="blog-label-category"><a href="{{cat.url}}">{{cat.label}}</a></span>
								{% ENDFOR %}
							{% ENDIF %}
						</p>
                    </span>
					</div>
				</header>
				{% IF v.intro %}
					{{htmlspecialchars_decode(v.intro)}}
				{% ELSE %}
					{{htmlspecialchars_decode(v.content)}}
				{% ENDIF %}
			{% ELSE %}
				<h2>
					<a href="{{v.url}}">{{v.name}}</a>
				</h2>
				<p class="date">{{v.date}}
					{% IF runPlugin.getConfigVal("comments") && v.commentsOff == false %}
						|
						commentaire{% IF newsManager.countComments(v.id) > 1 %}s{% ENDIF %}
					{% ENDIF %}
				</p>
			{% ENDIF %}
		</article>
	{% ENDFOR %}
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
