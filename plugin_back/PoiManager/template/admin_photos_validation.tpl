<section class="module-card">
	<header>
		<i class="fa-solid fa-image"></i>
		Validation des photos envoyées par les membres
	</header>

	<div class="pm-actions">
		<a class="button" href="{{ ROUTER.generate('admin-poimanager') }}">
			<i class="fa-solid fa-chevron-left"></i> Retour au Dashboard
		</a>
	</div>

	{% if photos %}
		<div class="pm-photos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
			{% for p in photos %}
				<div class="pm-photo-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #fff;">
					<div style="height: 200px; overflow: hidden; border-radius: 4px; margin-bottom: 10px; background: #f9f9f9; display: flex; align-items: center; justify-content: center;">
						<img src="{{ SHOW.siteUrl }}/{{ p.url }}" style="max-width: 100%; max-height: 100%; object-fit: contain;" alt="Suggestion photo">
					</div>
					
					<div style="margin-bottom: 15px;">
						<p><strong>POI :</strong> {{ p.poi_name }} <small>(#{{ p.poi_id }})</small></p>
						<p><strong>Envoyée par :</strong> {{ p.user_email|default('User #' ~ p.uploaded_by) }}</p>
						<p><strong>Le :</strong> {{ p.created_at }}</p>
						<p><strong>Légende :</strong> <em>{{ p.caption|default('Aucune') }}</em></p>
					</div>

					<div style="display: flex; gap: 10px;">
						<form method="post" action="{{ ROUTER.generate('admin-poimanager-decide-photo') }}">
							<input type="hidden" name="_csrf" value="{{ _csrfToken }}">
							<input type="hidden" name="photo_id" value="{{ p.id }}">
							<input type="hidden" name="decision" value="approve">
							<button type="submit" class="button success button-small" onclick="return confirm('Approuver cette photo ? Elle sera visible publiquement.')">
								<i class="fa-solid fa-check"></i> Approuver
							</button>
						</form>

						<form method="post" action="{{ ROUTER.generate('admin-poimanager-decide-photo') }}">
							<input type="hidden" name="_csrf" value="{{ _csrfToken }}">
							<input type="hidden" name="photo_id" value="{{ p.id }}">
							<input type="hidden" name="decision" value="reject">
							<button type="submit" class="button button-small" style="color:red;" onclick="return confirm('Rejeter et SUPPRIMER définitivement cette photo ?')">
								<i class="fa-solid fa-trash"></i> Rejeter
							</button>
						</form>
					</div>
				</div>
			{% endfor %}
		</div>
	{% else %}
		<div style="text-align: center; padding: 50px; color: #666;">
			<i class="fa-solid fa-circle-check" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
			<p>Aucune photo en attente de validation.</p>
		</div>
	{% endif %}

</section>
