<section class="module-card BugTracker-view">
	<header>
		<i class="fa-solid fa-bug"></i>
		Bug #{{ bug.id }}
	</header>
	
	<div class="bug-details">
		<div class="bug-header">
			<h2>{{ bug.title }}</h2>
			<div class="bug-badges">
				<span class="badge badge-{{ bug.statusClass }}">{{ bug.statusLabel }}</span>
				<span class="badge badge-{{ bug.priorityClass }}">{{ bug.priorityLabel }}</span>
			</div>
		</div>
		
		<div class="bug-meta">
			{% if bug.created_at %}
				<span><i class="fa-solid fa-calendar"></i> Signalé le {{ Util.getDateHour(bug.created_at) }}</span>
			{% endif %}
			{% if bug.reporter_name %}
				<span><i class="fa-solid fa-user"></i> {{ bug.reporter_name }}</span>
			{% endif %}
			{% if bug.hasUrl %}
				<span><i class="fa-solid fa-link"></i> <a href="{{ bug.url }}" target="_blank">Page concernée</a></span>
			{% endif %}
		</div>
		
		<div class="bug-section">
			<h3>Description</h3>
			<p>{{ bug.description }}</p>
		</div>
		
		{% if bug.steps %}
		<div class="bug-section">
			<h3>Étapes pour reproduire</h3>
			<p>{{ bug.steps }}</p>
		</div>
		{% endif %}
		
		{% if bug.expected or bug.actual %}
		<div class="bug-comparison">
			{% if bug.expected %}
			<div class="bug-section">
				<h3>Comportement attendu</h3>
				<p>{{ bug.expected }}</p>
			</div>
			{% endif %}
			
			{% if bug.actual %}
			<div class="bug-section">
				<h3>Comportement observé</h3>
				<p>{{ bug.actual }}</p>
			</div>
			{% endif %}
		</div>
		{% endif %}
		
		{% if bug.browser or bug.os %}
		<div class="bug-section">
			<h3>Informations techniques</h3>
			<ul>
				{% if bug.browser %}<li><strong>Navigateur :</strong> {{ bug.browser }}</li>{% endif %}
				{% if bug.os %}<li><strong>Système :</strong> {{ bug.os }}</li>{% endif %}
			</ul>
		</div>
		{% endif %}
		
		{% if bug.images and bug.images|length > 0 %}
		<div class="bug-section">
			<h3>Images</h3>
			<div class="bug-images">
				{% FOR image IN bug.images %}
					<div class="bug-image">
						<a href="{{ image.url }}" target="_blank">
							<img src="{{ image.url }}" alt="Capture d'écran du bug">
						</a>
					</div>
				{% ENDFOR %}
			</div>
		</div>
		{% endif %}
		
		{% if bug.comments and bug.comments|length > 0 %}
		<div class="bug-section">
			<h3>Commentaires</h3>
			<div class="bug-comments">
				{% FOR comment IN bug.comments %}
					<div class="bug-comment {% if comment.is_admin %}bug-comment-admin{% endif %}">
						<div class="comment-header">
							<strong>{{ comment.author_name }}</strong>
							{% if comment.is_admin %}<span class="badge badge-info">Admin</span>{% endif %}
							<span class="comment-date">{{ Util.getDateHour(comment.created_at) }}</span>
						</div>
						<div class="comment-body">
							{{ comment.comment }}
						</div>
					</div>
				{% ENDFOR %}
			</div>
		</div>
		{% endif %}
	</div>
	
	<div class="bug-actions">
		<a href="{{ ROUTER.generate("BugTracker-list") }}" class="button secondary">
			<i class="fa-solid fa-arrow-left"></i> Retour à la liste
		</a>
		<a href="{{ ROUTER.generate("BugTracker-report") }}" class="button primary">
			<i class="fa-solid fa-plus"></i> Signaler un autre bug
		</a>
	</div>
</section>

