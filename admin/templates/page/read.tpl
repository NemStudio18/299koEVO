<section>
	{% if page.isUnlocked(pageItem) %}
		{% HOOK.page:content-header(pageItem) %}
		{{pageItem.getParsedContent}}
	{% else %}
		<header>
			<div class="item-head">
				<p>Cette page est protégée par un mot de passe.</p>
			</div>
		</header>
		<form method="post" action="">
			<input type="hidden" name="unlock" value="{{sendUrl}}"/>
			<p>
				<label>Mot de passe</label><br>
				<input style="display:none;" type="text" name="_password" value=""/>
				<input required="required" type="password" name="password" value=""/>
			</p>
			<p>
				<input type="submit" value="Envoyer"/>
			</p>
		</form>
	{% endif %}
</section>
