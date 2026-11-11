<section class="module-card">
	<header>
		<i class="fa-solid fa-map-location-dot"></i>
		Proposer un nouveau point d'intérêt
	</header>

	<p>Vous souhaitez ajouter un commerce, une association ou un service qui n'existe pas encore ?
	   Remplissez le formulaire ci-dessous. Un administrateur validera votre proposition avant publication.</p>

	<form method="post" action="{{ ROUTER.generate('poimanager-propose-save') }}" class="pm-form-public" novalidate enctype="multipart/form-data">
		<input type="hidden" name="_csrf" value="{{ _csrfToken }}">

		<div class="form-section">
			<h3>Informations principales</h3>
			<div class="field">
				<label for="propose-name">Nom <span class="required">*</span></label>
				<input type="text" id="propose-name" name="name" required placeholder="Nom du lieu">
			</div>
			<div class="field-row">
				<div class="field">
					<label for="propose-category">Catégorie</label>
					<input type="text" id="propose-category" name="category" placeholder="Ex: shops, food..." list="cat-list">
					<datalist id="cat-list">
						{% if categories %}
							{% for cat in categories %}
								<option value="{{ cat }}">
							{% endfor %}
						{% endif %}
					</datalist>
				</div>
				<div class="field">
					<label for="propose-phone">Téléphone</label>
					<input type="text" id="propose-phone" name="phone" placeholder="+33 2 48 ...">
				</div>
			</div>
			<div class="field">
				<label for="propose-website">Site web</label>
				<input type="url" id="propose-website" name="website" placeholder="https://...">
			</div>
		</div>

		<div class="form-section">
			<h3>Adresse</h3>
			<div class="field-row">
				<div class="field">
					<label for="propose-housenumber">N° de rue</label>
					<input type="text" id="propose-housenumber" name="housenumber" placeholder="12">
				</div>
				<div class="field">
					<label for="propose-street">Rue</label>
					<input type="text" id="propose-street" name="street" placeholder="Rue de la Paix">
				</div>
			</div>
			<div class="field-row">
				<div class="field">
					<label for="propose-postcode">Code postal</label>
					<input type="text" id="propose-postcode" name="postcode" placeholder="18000">
				</div>
				<div class="field">
					<label for="propose-city">Ville <span class="required">*</span></label>
					<input type="text" id="propose-city" name="city" required placeholder="Bourges">
				</div>
			</div>
		</div>

		<div class="form-section">
			<h3>Note pour les administrateurs</h3>
			<div class="field">
				<textarea name="note" rows="4" placeholder="Informations complémentaires, précisions..."></textarea>
			</div>
			<div class="field">
				<label>
					<input type="checkbox" name="is_owner" value="1" id="is-owner-checkbox">
					Je suis le propriétaire ou le gestionnaire de ce lieu
				</label>
			</div>
			<div class="field" id="owner-proof-field" style="display: none; margin-top: 15px; padding: 15px; background: #fff8f1; border: 1px dashed #ff8c42; border-radius: 8px;">
				<label for="propose-proof" style="color: #ff8c42; font-weight: 700;">
					<i class="fa-solid fa-certificate"></i> Justificatif de propriété (Kbis, facture...)
				</label>
				<input type="file" id="propose-proof" name="owner_proof" accept="image/*,.pdf">
				<p style="font-size: 0.8rem; color: #666; margin-top: 5px;">Format : JPG, PNG ou PDF (Max 5 Mo). Requis pour accélérer la validation.</p>
			</div>
		</div>

		<div class="form-actions">
			<button class="button success" type="submit">
				<i class="fa-solid fa-paper-plane"></i> Envoyer ma proposition
			</button>
			<a class="button" href="javascript:history.back()">Annuler</a>
		</div>
	</form>

</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const checkbox = document.getElementById('is-owner-checkbox');
	const proofField = document.getElementById('owner-proof-field');
	if (checkbox && proofField) {
		checkbox.addEventListener('change', function() {
			proofField.style.display = this.checked ? 'block' : 'none';
		});
	}
});
</script>
