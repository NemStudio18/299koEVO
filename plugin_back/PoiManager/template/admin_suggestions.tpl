<section class="module-card">
	<header><i class="fa-solid fa-pen-ruler"></i> POI Manager — Suggestions de corrections ({{ count }} en attente)</header>

	{% if grouped %}
	{% for poiId, suggestions in grouped %}

	{% set firstSuggestion = suggestions[0] %}
	<div class="form-section">
		<h3>
			{{ firstSuggestion.poi_name|default('POI #' ~ poiId) }}
			<a class="button button-small" href="/admin/PoiManager/edit/{{ poiId }}" target="_blank">
				<i class="fa-solid fa-arrow-up-right-from-square"></i> Voir la fiche
			</a>
			<form method="post" action="{{ ROUTER.generate('admin-poimanager-decide-suggestion') }}" style="display:inline;">
				<input type="hidden" name="_csrf" value="{{ _csrfToken }}">
				<input type="hidden" name="poi_id" value="{{ poiId }}">
				<input type="hidden" name="decision" value="approve_all">
				<button class="button success button-small" type="submit">
					<i class="fa-solid fa-check-double"></i> Tout approuver
				</button>
			</form>
		</h3>

		<table class="table">
			<thead>
				<tr><th>Champ</th><th>Valeur actuelle</th><th>Valeur proposée</th><th>Par</th><th>Actions</th></tr>
			</thead>
			<tbody>
			{% for s in suggestions %}
				<tr>
					<td><code>{{ s.field_name }}</code></td>
					<td class="pm-old-value">{{ s.old_value|default('—') }}</td>
					<td class="pm-new-value">{{ s.new_value }}</td>
					<td>
						{% if s.user_username %}
							{{ s.user_username }}
						{% elseif s.user_email %}
							{{ s.user_email }}
						{% else %}
							User #{{ s.user_id }}
						{% endif %}
					</td>
					<td>
						<div class="pm-decision-form">
							<form method="post" action="{{ ROUTER.generate('admin-poimanager-decide-suggestion') }}">
								<input type="hidden" name="_csrf" value="{{ _csrfToken }}">
								<input type="hidden" name="suggestion_id" value="{{ s.id }}">
								<input type="hidden" name="decision" value="approve">
								<button class="button success button-small" type="submit">
									<i class="fa-solid fa-check"></i>
								</button>
							</form>
							<form method="post" action="{{ ROUTER.generate('admin-poimanager-decide-suggestion') }}">
								<input type="hidden" name="_csrf" value="{{ _csrfToken }}">
								<input type="hidden" name="suggestion_id" value="{{ s.id }}">
								<input type="hidden" name="decision" value="reject">
								<button class="button alert button-small" type="submit">
									<i class="fa-solid fa-times"></i>
								</button>
							</form>
						</div>
					</td>
				</tr>
			{% endfor %}
			</tbody>
		</table>
	</div>

	{% endfor %}
	{% else %}
	<div class="pm-empty-state">
		<i class="fa-solid fa-pen-ruler"></i>
		Aucune correction en attente.
	</div>
	{% endif %}

</section>
