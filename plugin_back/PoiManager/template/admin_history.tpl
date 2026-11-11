<section class="module-card">
	<header><i class="fa-solid fa-clock-rotate-left"></i> POI Manager — Historique</header>

	<div class="form-section">
		<h3>Historique des suggestions (Corrections de fiches existantes)</h3>
		<table class="table">
			<thead>
				<tr>
					<th>Date</th>
					<th>Statut</th>
					<th>POI</th>
					<th>Champ</th>
					<th>Valeur proposée</th>
					<th>Proposé par</th>
				</tr>
			</thead>
			<tbody>
				{% if recentSuggestions %}
					{% for s in recentSuggestions %}
						<tr>
							<td>{{ s.created_at|slice(0, 16) }}</td>
							<td>
								{% if s.status == 'pending' %}
									<span class="badge" style="background:#f59e0b;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.8rem;">En attente</span>
								{% elseif s.status == 'approved' %}
									<span class="badge" style="background:#10b981;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.8rem;">Approuvé</span>
								{% elseif s.status == 'rejected' %}
									<span class="badge" style="background:#ef4444;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.8rem;">Rejeté</span>
								{% else %}
									{{ s.status }}
								{% endif %}
							</td>
							<td>{{ s.poi_name|default('POI #' ~ s.poi_id) }}</td>
							<td><code>{{ s.field_name }}</code></td>
							<td>{{ s.new_value|truncate(50) }}</td>
							<td>
								{% if s.user_username %}
									{{ s.user_username }}
								{% elseif s.user_email %}
									{{ s.user_email }}
								{% else %}
									User #{{ s.user_id }}
								{% endif %}
							</td>
						</tr>
					{% endfor %}
				{% else %}
					<tr><td colspan="6" style="text-align:center;">Aucun historique de suggestion.</td></tr>
				{% endif %}
			</tbody>
		</table>
	</div>

	<div class="form-section" style="margin-top:2rem;">
		<h3>Historique des propositions (Nouvelles fiches)</h3>
		<table class="table">
			<thead>
				<tr>
					<th>Date</th>
					<th>Statut</th>
					<th>Nom</th>
					<th>Catégorie</th>
					<th>Ville</th>
					<th>Proposé par</th>
				</tr>
			</thead>
			<tbody>
				{% if recentProposals %}
					{% for p in recentProposals %}
						<tr>
							<td>{{ p.created_at|slice(0, 16) }}</td>
							<td>
								{% if p.status == 'pending' %}
									<span class="badge" style="background:#f59e0b;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.8rem;">En attente</span>
								{% elseif p.status == 'approved' %}
									<span class="badge" style="background:#10b981;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.8rem;">Approuvé</span>
								{% elseif p.status == 'rejected' %}
									<span class="badge" style="background:#ef4444;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.8rem;">Rejeté</span>
								{% else %}
									{{ p.status }}
								{% endif %}
							</td>
							<td>{{ p.name }}</td>
							<td>{{ p.category }}</td>
							<td>{{ p.city }}</td>
							<td>
								{% if p.user_username %}
									{{ p.user_username }}
								{% elseif p.user_email %}
									{{ p.user_email }}
								{% else %}
									User #{{ p.user_id }}
								{% endif %}
							</td>
						</tr>
					{% endfor %}
				{% else %}
					<tr><td colspan="6" style="text-align:center;">Aucun historique de proposition.</td></tr>
				{% endif %}
			</tbody>
		</table>
	</div>

</section>
