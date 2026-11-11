<section class="module-card">
	<header>
		<i class="fa-solid fa-map-location-dot"></i>
		Mes fiches POI
		<span class="pm-plan-badge">Plan : {{ plan|upper }}</span>
	</header>

	<div class="pm-toolbar">
		<a class="button" href="{{ ROUTER.generate('poimanager-propose') }}">
			<i class="fa-solid fa-plus"></i> Proposer un nouveau POI
		</a>
	</div>

	{% if pois %}
	<div class="pm-poi-list">
		{% for poi in pois %}
		<div class="pm-poi-card">
			<div class="pm-poi-card-header">
				<div>
					<strong>{{ poi.name }}</strong>
					{% if poi.brand %} — <em>{{ poi.brand }}</em>{% endif %}
				</div>
				<div>
					<a class="button button-small" href="{{ ROUTER.generate('poimanager-owner-edit', {'id': poi.id}) }}">
						<i class="fa-solid fa-pen"></i> Modifier
					</a>
				</div>
			</div>
			<div class="pm-poi-card-body">
				{% if poi.addr_street %}{{ poi.addr_housenumber }} {{ poi.addr_street }}, {% endif %}
				{{ poi.addr_postcode }} {{ poi.addr_city }}
				{% if poi.contact_phone %} · ☎ {{ poi.contact_phone }}{% endif %}
				{% if poi.website %} · <a href="{{ poi.website }}" target="_blank" rel="noopener">{{ poi.website }}</a>{% endif %}
			</div>
		</div>
		{% endfor %}
	</div>
	{% else %}
	<div class="pm-empty-state">
		<i class="fa-solid fa-map-location-dot"></i>
		Vous ne gérez aucune fiche POI pour l'instant.
	</div>
	{% endif %}

</section>
