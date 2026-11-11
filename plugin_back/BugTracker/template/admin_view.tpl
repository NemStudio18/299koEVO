<section class="module-card BugTracker-admin-view">
	<header>
		<i class="fa-solid fa-bug"></i>
		Bug #{{ bug.id }}
	</header>
	
	<div class="bug-details-admin">
		<div class="bug-header-admin">
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
			{% if bug.updated_at and bug.updated_at != bug.created_at %}
				<span><i class="fa-solid fa-clock"></i> Modifié le {{ Util.getDateHour(bug.updated_at) }}</span>
			{% endif %}
			{% if bug.hasReporterId %}
				<span><i class="fa-solid fa-user-check"></i> Membre #{{ bug.reporter_id }}: {{ bug.reporter_name }} {% if bug.reporter_email %}({{ bug.reporter_email }}){% endif %}</span>
			{% elseif bug.reporter_name %}
				<span><i class="fa-solid fa-user"></i> {{ bug.reporter_name }}</span>
			{% endif %}
			{% if bug.reporter_email and not bug.hasReporterId %}
				<span><i class="fa-solid fa-envelope"></i> {{ bug.reporter_email }}</span>
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
		
		{% if bug.hasImages %}
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
		
		<div class="bug-actions-admin">
			<h3>Actions</h3>
			
		<form method="POST" action="{{ ROUTER.generate("admin-BugTracker-update-status") }}" class="inline-form">
			{{ SHOW.tokenField() }}
				<input type="hidden" name="id" value="{{ bug.id }}"/>
				<label for="status">Changer le statut :</label>
				<select name="status" id="status">
					<option value="pending" {% if bug.isStatusPending %}selected{% endif %}>En attente</option>
					<option value="in_progress" {% if bug.isStatusInProgress %}selected{% endif %}>Pris en charge</option>
					<option value="fixed" {% if bug.isStatusFixed %}selected{% endif %}>Corrigé</option>
					<option value="rejected" {% if bug.isStatusRejected %}selected{% endif %}>Rejeté</option>
				</select>
				<button type="submit" class="button small primary">
					<i class="fa-solid fa-save"></i> Mettre à jour
				</button>
			</form>
			
		<form method="POST" action="{{ ROUTER.generate("admin-BugTracker-update-priority") }}" class="inline-form">
			{{ SHOW.tokenField() }}
			<input type="hidden" name="id" value="{{ bug.id }}"/>
				<label for="priority">Changer la priorité :</label>
				<select name="priority" id="priority">
					<option value="low" {% if bug.isPriorityLow %}selected{% endif %}>Basse</option>
					<option value="medium" {% if bug.isPriorityMedium %}selected{% endif %}>Moyenne</option>
					<option value="high" {% if bug.isPriorityHigh %}selected{% endif %}>Haute</option>
					<option value="critical" {% if bug.isPriorityCritical %}selected{% endif %}>Critique</option>
				</select>
				<button type="submit" class="button small primary">
					<i class="fa-solid fa-save"></i> Mettre à jour
				</button>
			</form>
			
		<form method="POST" action="{{ ROUTER.generate("admin-BugTracker-delete") }}" class="inline-form" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce bug ?');">
			{{ SHOW.tokenField() }}
				<input type="hidden" name="id" value="{{ bug.id }}"/>
				<button type="submit" class="button small alert">
					<i class="fa-solid fa-trash"></i> Supprimer
				</button>
			</form>
		</div>
		
		{% if bug.hasComments %}
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
		
		<div class="bug-section">
			<h3>Ajouter un commentaire</h3>
		<form method="POST" action="{{ ROUTER.generate("admin-BugTracker-add-comment") }}">
			{{ SHOW.tokenField() }}
				<input type="hidden" name="id" value="{{ bug.id }}"/>
				<div class="form-group">
					<textarea name="comment" rows="4" required placeholder="Votre commentaire..."></textarea>
				</div>
				<button type="submit" class="button primary">
					<i class="fa-solid fa-comment"></i> Ajouter le commentaire
				</button>
			</form>
		</div>
	</div>
	
	<div class="bug-actions">
		<a href="{{ ROUTER.generate("admin-BugTracker") }}" class="button secondary">
			<i class="fa-solid fa-arrow-left"></i> Retour à la liste
		</a>
	</div>
</section>

