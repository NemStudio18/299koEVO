<section class="module-card">
	<header><i class="fa-solid fa-list"></i> POI Manager — Liste des POIs</header>

	<div class="pm-toolbar">
		<a class="button" href="{{ ROUTER.generate('admin-poimanager-create') }}">
			<i class="fa-solid fa-plus"></i> Créer un POI
		</a>
		<form method="get" class="pm-search">
			<select name="owner" onchange="this.form.submit()">
				<option value="all" {% if ownerFilter == 'all' %}selected{% endif %}>Tous les propriétaires</option>
				<option value="claimed" {% if ownerFilter == 'claimed' %}selected{% endif %}>Avec propriétaire</option>
				<option value="unclaimed" {% if ownerFilter == 'unclaimed' %}selected{% endif %}>Sans propriétaire</option>
			</select>
			<input type="text" name="q" value="{{ search }}" placeholder="Rechercher par nom ou marque...">
			<button class="button" type="submit"><i class="fa-solid fa-search"></i></button>
			{% if search or ownerFilter != 'all' %}
			<a class="button" href="/admin/PoiManager/list">Effacer</a>
			{% endif %}
		</form>
		<span class="pm-result-count">{{ total }} POI(s)</span>
	</div>

	{% if pois %}
	<table class="table">
		<thead>
			<tr>
				<th>ID</th><th>Nom</th><th>Catégorie</th><th>Ville</th>
				<th>Statut</th><th>Propriétaire</th><th>Mis à jour</th><th>Action</th>
			</tr>
		</thead>
		<tbody>
		{% for poi in pois %}
			<tr>
				<td><code>{{ poi.id }}</code></td>
				<td>
					<strong>{{ poi.name }}</strong>
					{% if poi.brand %}<br><small>{{ poi.brand }}</small>{% endif %}
				</td>
				<td>{{ poi.category }}{% if poi.subcategory %} / {{ poi.subcategory }}{% endif %}</td>
				<td>{{ poi.addr_city }}</td>
				<td>
					{% if poi.is_closed %}
						<span class="badge badge-danger">Fermé</span>
					{% elseif poi.status == 'public' %}
						<span class="badge badge-success">Public</span>
					{% else %}
						<span class="badge">{{ poi.status }}</span>
					{% endif %}
				</td>
				<td>{% if poi.claimed_by %}#{{ poi.claimed_by }}{% else %}—{% endif %}</td>
				<td><small>{{ poi.updated_at }}</small></td>
				<td>
					<div style="display: flex; gap: 5px;">
						<a class="button button-small" href="/admin/PoiManager/edit/{{ poi.id }}" title="Éditer">
							<i class="fa-solid fa-pen"></i>
						</a>
						<form action="/admin/PoiManager/delete/{{ poi.id }}" method="POST" style="margin:0;" onsubmit="return confirm('Voulez-vous vraiment supprimer ce POI ?');">
							<input type="hidden" name="_csrf" value="{{ _csrfToken|default('') }}">
							<button type="submit" class="button button-small alert" title="Supprimer">
								<i class="fa-solid fa-trash"></i>
							</button>
						</form>
					</div>
				</td>
			</tr>
		{% endfor %}
		</tbody>
	</table>

	{% if pages > 1 %}
	<div class="pagination">
		{% for p in pageRange %}
			<a class="button button-small {% if p == page %}button-active{% endif %}"
			   href="/admin/PoiManager/list?page={{ p }}&q={{ search }}&owner={{ ownerFilter }}">{{ p }}</a>
		{% endfor %}
	</div>
	{% endif %}

	{% else %}
	<div class="pm-empty-state">
		<i class="fa-solid fa-map-location-dot"></i>
		{% if search %}Aucun POI ne correspond à "{{ search }}".{% else %}Aucun POI trouvé.{% endif %}
	</div>
	{% endif %}

</section>
