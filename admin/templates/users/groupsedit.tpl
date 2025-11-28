<section class="module-card">
	<header>{{Lang.groups-edit}}</header>
	<form method="POST" action="{{link}}">
		<input type="hidden" name="_csrf" value="{{ _csrfToken }}" />
		<input type="hidden" name="id" value="{{group.id}}" />
		<fieldset>
			<label for="name">{{Lang.groups-name}} *</label>
			<input type="text" id="name" name="name" value="{{group.name}}" required />
		</fieldset>
		
		<fieldset>
			<legend>{{Lang.groups-permissions}}</legend>
			<div class="permissions-grid">
				{% FOR category, definition IN permissionsDefinitions %}
					<div class="permissions-category">
						<h4>{{definition.label}}</h4>
						{% FOR permission IN definition.items %}
							{% SET isChecked = false %}
							{% FOR groupPerm IN group.permissions %}
								{% IF groupPerm == permission.key %}
									{% SET isChecked = true %}
								{% ENDIF %}
							{% ENDFOR %}
							<label class="checkbox">
								<input type="checkbox" name="permissions[]" value="{{permission.key}}" {% if isChecked %}checked{% endif %} />
								{{permission.label}}
							</label>
						{% ENDFOR %}
					</div>
				{% ENDFOR %}
			</div>
			{% SET hasAll = false %}
			{% FOR groupPerm IN group.permissions %}
				{% IF groupPerm == "*" %}
					{% SET hasAll = true %}
				{% ENDIF %}
			{% ENDFOR %}
			<label class="checkbox">
				<input type="checkbox" name="permissions[]" value="*" id="permission-all" {% if hasAll %}checked{% endif %} />
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

