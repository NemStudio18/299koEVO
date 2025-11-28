<section class="module-card">
	<header>{{Lang.groups-add}}</header>
	<form method="POST" action="{{link}}">
		<input type="hidden" name="_csrf" value="{{ _csrfToken }}" />
		<fieldset>
			<label for="name">{{Lang.groups-name}} *</label>
			<input type="text" id="name" name="name" required />
		</fieldset>
		
		<fieldset>
			<legend>{{Lang.groups-permissions}}</legend>
			<div class="permissions-grid">
				{% FOR category, definition IN permissionsDefinitions %}
					<div class="permissions-category">
						<h4>{{definition.label}}</h4>
						{% FOR permission IN definition.items %}
							<label class="checkbox">
								<input type="checkbox" name="permissions[]" value="{{permission.key}}" />
								{{permission.label}}
							</label>
						{% ENDFOR %}
					</div>
				{% ENDFOR %}
			</div>
			<label class="checkbox">
				<input type="checkbox" name="permissions[]" value="*" id="permission-all" />
				<strong>{{Lang.permissions.all}}</strong>
			</label>
		</fieldset>
		
		<footer>
			<button type="submit" class="button success">{{Lang.submit}}</button>
			<a href="{{ROUTER.generate("groups-admin-home")}}" class="button">{{Lang.cancel}}</a>
		</footer>
	</form>
</section>

<script>
document.getElementById('permission-all').addEventListener('change', function(e) {
	const checkboxes = document.querySelectorAll('input[name="permissions[]"]:not(#permission-all)');
	checkboxes.forEach(cb => {
		cb.checked = e.target.checked;
		cb.disabled = e.target.checked;
	});
});
</script>

