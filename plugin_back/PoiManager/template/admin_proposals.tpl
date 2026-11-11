<section class="module-card">
	<header><i class="fa-solid fa-inbox"></i> POI Manager — Propositions de nouveaux POIs</header>

	{% if proposals %}
	<table class="table">
		<thead>
			<tr>
				<th>Nom proposé</th><th>Catégorie</th><th>Ville</th>
				<th>Proposé par</th><th>Date</th><th>Statut</th><th>Actions</th>
			</tr>
		</thead>
		<tbody>
		{% for p in proposals %}
			<tr>
				<td><strong>{{ p.name }}</strong></td>
				<td>{{ p.category }}</td>
				<td>{{ p.city }}</td>
				<td>User #{{ p.user_id }}</td>
				<td><small>{{ p.created_at }}</small></td>
				<td>
					{% if p.status == 'pending' %}
						<span class="badge badge-warning">En attente</span>
					{% elseif p.status == 'approved' %}
						<span class="badge badge-success">Approuvée</span>
					{% else %}
						<span class="badge badge-danger">Rejetée</span>
					{% endif %}
				</td>
				<td>
					{% if p.status == 'pending' %}
					<div class="pm-decision-form">
						<form method="post" action="{{ ROUTER.generate('admin-poimanager-decide-proposal') }}">
							<input type="hidden" name="_csrf" value="{{ _csrfToken }}">
							<input type="hidden" name="proposal_id" value="{{ p.id }}">
							<button class="button success button-small" name="decision" value="approve" type="submit">
								<i class="fa-solid fa-check"></i> Approuver
							</button>
						</form>
						<form method="post" action="{{ ROUTER.generate('admin-poimanager-decide-proposal') }}" class="pm-reject-form">
							<input type="hidden" name="_csrf" value="{{ _csrfToken }}">
							<input type="hidden" name="proposal_id" value="{{ p.id }}">
							<input type="hidden" name="decision" value="reject">
							<button class="button alert button-small" type="button" onclick="this.closest('.pm-reject-form').querySelector('.pm-reject-reason').style.display=''">
								<i class="fa-solid fa-times"></i> Rejeter
							</button>
							<div class="pm-reject-reason" style="display:none;">
								<input type="text" name="reason" placeholder="Raison du rejet..." class="form-control" style="margin:.25rem 0;">
								<button class="button alert button-small" type="submit">Confirmer le rejet</button>
							</div>
						</form>
					</div>
					{% endif %}
				</td>
			</tr>
			{% if p.note %}
			<tr>
				<td colspan="7">
					<div class="form-section">
						<strong>Note du proposant :</strong> {{ p.note }}
						{% if p.lat and p.lon %}| 📍 {{ p.lat }}, {{ p.lon }}{% endif %}
						{% if p.phone %}| ☎ {{ p.phone }}{% endif %}
						{% if p.website %}| 🌐 <a href="{{ p.website }}" target="_blank">{{ p.website }}</a>{% endif %}
					</div>
				</td>
			</tr>
			{% endif %}
		{% endfor %}
		</tbody>
	</table>
	{% else %}
	<div class="pm-empty-state">
		<i class="fa-solid fa-inbox"></i>
		Aucune proposition pour l'instant.
	</div>
	{% endif %}

</section>
