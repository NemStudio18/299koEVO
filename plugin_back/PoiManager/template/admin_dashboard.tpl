<section class="module-card">
	<header><i class="fa-solid fa-map-location-dot"></i> POI Manager — Dashboard</header>

	<div class="pm-stats">
		<div class="pm-stat-card pm-stat-blue">
			<div class="pm-stat-value">{{ stats.total_pois }}</div>
			<div class="pm-stat-label">Total POIs</div>
		</div>
		<div class="pm-stat-card pm-stat-amber">
			<div class="pm-stat-value">{{ stats.pending_proposals }}</div>
			<div class="pm-stat-label">Propositions en attente</div>
		</div>
		<div class="pm-stat-card pm-stat-pink">
			<div class="pm-stat-value">{{ stats.pending_suggestions }}</div>
			<div class="pm-stat-label">Corrections en attente</div>
		</div>
		<div class="pm-stat-card pm-stat-green">
			<div class="pm-stat-value">{{ stats.claimed_pois }}</div>
			<div class="pm-stat-label">POIs revendiqués</div>
		</div>
		<div class="pm-stat-card pm-stat-purple">
			<div class="pm-stat-value">{{ stats.pending_photos }}</div>
			<div class="pm-stat-label">Photos en attente</div>
		</div>
	</div>

	<div class="pm-actions">
		<a class="button" href="{{ ROUTER.generate('admin-poimanager-create') }}">
			<i class="fa-solid fa-plus"></i> Créer un POI
		</a>
		<a class="button" href="{{ ROUTER.generate('admin-poimanager-list') }}">
			<i class="fa-solid fa-list"></i> Liste des POIs
		</a>
		<a class="button" href="{{ ROUTER.generate('admin-poimanager-proposals') }}">
			<i class="fa-solid fa-inbox"></i> Propositions ({{ stats.pending_proposals }})
		</a>
		<a class="button" href="{{ ROUTER.generate('admin-poimanager-suggestions') }}">
			<i class="fa-solid fa-pen-ruler"></i> Corrections ({{ stats.pending_suggestions }})
		</a>
		<a class="button" href="{{ ROUTER.generate('admin-poimanager-history') }}">
			<i class="fa-solid fa-clock-rotate-left"></i> Historique
		</a>
		<a class="button" href="{{ ROUTER.generate('admin-poimanager-photos-validation') }}">
			<i class="fa-solid fa-image"></i> Photos ({{ stats.pending_photos }})
		</a>
		<a class="button pm-actions-right" href="{{ ROUTER.generate('admin-poimanager-config') }}">
			<i class="fa-solid fa-gear"></i> Configuration
		</a>
	</div>

	{% if pendingProposals %}
	<h3 class="pm-section-title">Propositions récentes à valider</h3>
	<table class="table">
		<thead><tr><th>Nom</th><th>Ville</th><th>Proposé par</th><th>Date</th><th>Action</th></tr></thead>
		<tbody>
		{% for p in pendingProposals %}
			<tr>
				<td>{{ p.name }}</td>
				<td>{{ p.city }}</td>
				<td>User #{{ p.user_id }}</td>
				<td>{{ p.created_at }}</td>
				<td><a class="button button-small" href="{{ ROUTER.generate('admin-poimanager-proposals') }}">Voir</a></td>
			</tr>
		{% endfor %}
		</tbody>
	</table>
	{% endif %}

	{% if pendingSuggestions %}
	<h3 class="pm-section-title">Corrections récentes à valider</h3>
	<table class="table">
		<thead><tr><th>POI</th><th>Champ</th><th>Nouvelle valeur</th><th>Proposé par</th><th>Action</th></tr></thead>
		<tbody>
		{% for s in pendingSuggestions %}
			<tr>
				<td>{{ s.poi_name|default('POI #' ~ s.poi_id) }}</td>
				<td><code>{{ s.field_name }}</code></td>
				<td>{{ s.new_value }}</td>
				<td>
					{% if s.user_username %}
						{{ s.user_username }}
					{% elseif s.user_email %}
						{{ s.user_email }}
					{% else %}
						User #{{ s.user_id }}
					{% endif %}
				</td>
				<td><a class="button button-small" href="{{ ROUTER.generate('admin-poimanager-suggestions') }}">Voir</a></td>
			</tr>
		{% endfor %}
		</tbody>
	</table>
	{% endif %}

</section>
