<section class="module-card BugTracker-form">
	<header>
		<i class="fa-solid fa-bug"></i>
		Signaler un bug
	</header>
	
	<form method="POST" action="{{ ROUTER.generate("BugTracker-submit") }}" enctype="multipart/form-data">
		{{ SHOW.tokenField() }}
		
		<div class="form-group">
			<label for="title">Titre du bug <span class="required">*</span></label>
			<input type="text" id="title" name="title" required placeholder="Ex: Le bouton de connexion ne fonctionne pas">
		</div>
		
		<div class="form-group">
			<label for="description">Description du problème <span class="required">*</span></label>
			<textarea id="description" name="description" rows="5" required placeholder="Décrivez le problème rencontré..."></textarea>
		</div>
		
		<div class="form-group">
			<label for="steps">Étapes pour reproduire le bug</label>
			<textarea id="steps" name="steps" rows="4" placeholder="1. Aller sur la page...&#10;2. Cliquer sur...&#10;3. Le problème apparaît..."></textarea>
		</div>
		
		<div class="form-group">
			<label for="actual">Comportement observé</label>
			<textarea id="actual" name="actual" rows="3" placeholder="Ce qui se passe réellement..."></textarea>
		</div>
		
		<div class="form-group">
			<label for="url">URL de la page concernée</label>
			<input type="text" id="url" name="url" placeholder="Ex: /page/contact ou https://exemple.com/page">
			<small>Indiquez l'URL de la page où vous avez rencontré le bug (optionnel)</small>
		</div>
		
		<div class="form-row">
			<div class="form-group">
				<label for="browser">Navigateur</label>
				<input type="text" id="browser" name="browser" placeholder="Ex: Chrome 120, Firefox 121">
			</div>
			
			<div class="form-group">
				<label for="os">Système d'exploitation</label>
				<input type="text" id="os" name="os" placeholder="Ex: Windows 11, macOS 14">
			</div>
		</div>
		
		<div class="form-group">
			<label>Images (optionnel, max 2 images, 5 MB chacune)</label>
			<div class="image-uploads">
				<div class="image-upload-item">
					<label for="image1">Image 1</label>
					<input type="file" id="image1" name="image1" accept="image/jpeg,image/png,image/gif,image/webp">
				</div>
				<div class="image-upload-item">
					<label for="image2">Image 2</label>
					<input type="file" id="image2" name="image2" accept="image/jpeg,image/png,image/gif,image/webp">
				</div>
			</div>
		</div>
		
		<div class="form-row">
			<div class="form-group">
				<label for="reporter_name">Votre nom</label>
				<input type="text" id="reporter_name" name="reporter_name" placeholder="Optionnel">
			</div>
			
			<div class="form-group">
				<label for="reporter_email">Votre email</label>
				<input type="email" id="reporter_email" name="reporter_email" placeholder="Optionnel, pour vous tenir informé">
			</div>
		</div>
		
		<div class="form-actions">
			<button type="submit" class="button primary">
				<i class="fa-solid fa-paper-plane"></i> Envoyer le rapport
			</button>
			<a href="{{ ROUTER.generate("BugTracker-home") }}" class="button secondary">Annuler</a>
		</div>
	</form>
</section>

