<section>
	{% if page.isUnlocked(pageItem) %}
		{% HOOK.page:content-header(pageItem) %}
		{{htmlspecialchars_decode(pageItem.getContent)}}
	{% else %}
		<header>
			<div class="item-head">
				<p>Cette page est protégée par un mot de passe.</p>
			</div>
		</header>
		<form method="post" action="">
			<input type="hidden" name="unlock" value="{{sendUrl}}"/>
			<input type="hidden" name="_csrf" value="{{_csrfToken}}"/>
			<p>
				<label>Mot de passe</label><br>
				<input required="required" type="password" name="password" value=""/>
			</p>
			<p>
				<input type="submit" value="Envoyer"/>
			</p>
		</form>
	{% endif %}
</section>
