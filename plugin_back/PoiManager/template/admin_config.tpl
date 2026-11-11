<section class="module-card">
	<header><i class="fa-solid fa-gear"></i> POI Manager — Configuration</header>

	<div class="tabs-container">
		<ul class="tabs-header">
			<li class="default-tab">
				<i class="fa-solid fa-shield-cat me-1"></i> Général & Limites
			</li>
		</ul>

		<ul class="tabs">
			<!-- Onglet 1: Limits -->
			<li class="tab">
				<form action="/admin/PoiManager/configSave" method="POST" class="form-standard p-4">
					<div class="form-section">
						<h3>Propriétaires & Abonnements</h3>
						<p class="text-muted mb-4">Définissez les limites applicables aux comptes revendiquant des fiches POI.</p>
						
						<div class="field">
							<label for="conf-max-pois">Nombre maximum de fiches par propriétaire (Défaut : 1)</label>
							<input type="number" id="conf-max-pois" name="config[max_pois_per_owner]" value="{{ config.max_pois_per_owner|default('1') }}" min="1">
						</div>
					</div>

					<div class="form-actions mt-4">
						<button type="submit" class="button"><i class="fa-solid fa-save"></i> Enregistrer</button>
					</div>
				</form>
			</li>
		</ul>
	</div>
</section>Ces valeurs définissent ce que les propriétaires peuvent faire selon leur plan.</small></p>
			<table class="table">
				<thead><tr><th>Plan</th><th>Max photos</th><th>Max liens</th></tr></thead>
				<tbody>
				{% for plan, limit in limits %}
					<tr>
						<td><strong>{{ plan|upper }}</strong></td>
						<td>
							<input type="number" name="limits[{{ plan }}][max_photos]"
							       value="{{ limit.max_photos }}" min="0" max="100" class="form-control" style="width:80px;">
						</td>
						<td>
							<input type="number" name="limits[{{ plan }}][max_links]"
							       value="{{ limit.max_links }}" min="0" max="50" class="form-control" style="width:80px;">
						</td>
					</tr>
				{% endfor %}
				</tbody>
			</table>
		</div>

		<div class="form-section">
			<h3>Notifications email</h3>
			<div class="field">
				<label>
					<input type="checkbox" name="notif_admin_proposal" value="1"
					       {% if notifications.admin_email_on_proposal %}checked{% endif %}>
					Notifier l'admin lors d'une nouvelle proposition (proposal)
				</label>
			</div>
			<div class="field">
				<label>
					<input type="checkbox" name="notif_admin_suggestion" value="1"
					       {% if notifications.admin_email_on_suggestion %}checked{% endif %}>
					Notifier l'admin lors d'une nouvelle correction (suggestion)
				</label>
			</div>
			<div class="field">
				<label>
					<input type="checkbox" name="notif_member_decision" value="1"
					       {% if notifications.member_email_on_decision %}checked{% endif %}>
					Notifier le membre lors d'une décision (approbation / rejet)
				</label>
			</div>
		</div>

		<div class="form-actions">
			<button class="button success" type="submit"><i class="fa-solid fa-save"></i> Sauvegarder</button>
		</div>
	</form>
</section>
