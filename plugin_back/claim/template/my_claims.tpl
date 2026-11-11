<section class="module-card">
	<header style="display: flex; justify-content: space-between; align-items: center;">
		<div><i class="fa-solid fa-list"></i> Mes fiches revendiquées</div>
		{% if claims %}
		<div>
			{% HOOK.claim:my-claims-header-actions(claims) %}
		</div>
		{% endif %}
	</header>
	
	{% if claims %}
	<ul class="claim-list">
		{% for claim in claims %}
		<li class="claim-item claim-item--{{ claim.claim_status }}">
			<div class="claim-item-header">
				<h3>
					{% if claim.name %}
						{{ claim.name }}
					{% else %}
						POI #{{ claim.poi_id }}
					{% endif %}
				</h3>
				<span class="claim-status-badge claim-status-badge--{{ claim.claim_status }}">
					{% if claim.claim_status == "pending" %}
						<i class="fa-solid fa-clock"></i> En attente
					{% elseif claim.claim_status == "approved" %}
						<i class="fa-solid fa-check-circle"></i> Validé
					{% elseif claim.claim_status == "rejected" %}
						<i class="fa-solid fa-times-circle"></i> Rejeté
					{% endif %}
				</span>
			</div>
			
			{% if claim.street or claim.city %}
			<div class="claim-item-address">
				<i class="fa-solid fa-location-dot"></i>
				{% if claim.housenumber %}{{ claim.housenumber }} {% endif %}{% if claim.street %}{{ claim.street }}{% endif %}
				{% if claim.postcode or claim.city %}
					{% if claim.postcode %}{{ claim.postcode }} {% endif %}{% if claim.city %}{{ claim.city }}{% endif %}
				{% endif %}
			</div>
			{% endif %}
			
			<div class="claim-item-meta">
				<small>
					Demandé le {% if claim.requested_at_formatted %}{{ claim.requested_at_formatted }}{% else %}{{ claim.requested_at }}{% endif %}
					{% if claim.approved_at %}
						· Validé le {% if claim.approved_at_formatted %}{{ claim.approved_at_formatted }}{% else %}{{ claim.approved_at }}{% endif %}
					{% endif %}
				</small>
			</div>
			
			<div class="claim-item-actions">
				{% if claim.claim_status == "pending" %}
					<a class="button" href="{{ ROUTER.generate("claim-claim", ["id" => claim.poi_id]) }}">
						Voir la demande
					</a>
				{% elseif claim.claim_status == "approved" %}
					{% if claim.detail_url_short %}
						<a class="button primary" href="{{ claim.detail_url_short }}">
							Voir la fiche
						</a>
					{% else %}
						<a class="button primary" href="{{ ROUTER.generate("search-detail", ["type" => "poi", "id" => claim.poi_id]) }}">
							Voir la fiche
						</a>
					{% endif %}
					{% HOOK.claim:my-claims-item-actions(claim) %}
				{% elseif claim.claim_status == "rejected" %}
					{% if claim.detail_url_short %}
						<a class="button" href="{{ claim.detail_url_short }}">
							Voir la fiche
						</a>
					{% else %}
						<a class="button" href="{{ ROUTER.generate("search-detail", ["type" => "poi", "id" => claim.poi_id]) }}">
							Voir la fiche
						</a>
					{% endif %}
				{% endif %}
			</div>
		</li>
		{% endfor %}
	</ul>
	{% else %}
	<p>Aucune revendication pour le moment.</p>
	{% endif %}
</section>

