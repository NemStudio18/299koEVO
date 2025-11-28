<section class="module-card">
	<header>{{Lang.users-edit}}</header>
	<form method="POST" action="{{link}}" class="user-form-grid">
		{{SHOW.tokenField}}
		<input type="hidden" name="id" value="{{user.id}}" />
		<div class="form-grid">
			<p>
				<label for="username">{{ Lang.users-username }}</label>
				<input type="text" id="username" name="username" value="{{user.username}}" required minlength="3" />
			</p>
			<p>
				<label for="email">{{ Lang.users-mail}}</label>
				<input type="email" id="email" name="email" value="{{user.email}}" required />
			</p>
			<p>
				<label for="pwd">{{Lang.users-new-password-optional}}</label>
				<input type="password" id="pwd" name="pwd" minlength="8" autocomplete="new-password" />
			</p>
			<p>
				<label for="group">{{ Lang.users-group }}</label>
				<select id="group" name="group_slug">
					{% for group in groups %}
						<option value="{{ group.getSlug() }}" {% IF group.getSlug() == user.group_slug %}selected{% ENDIF %}>{{ group.getName() }}</option>
					{% endfor %}
				</select>
			</p>
			<p>
				<label for="status">{{ Lang.users-status }}</label>
				<select id="status" name="status" {% IF user.isSuperAdmin() %}disabled{% ENDIF %}>
					<option value="active" {% IF user.status == "active" %}selected{% ENDIF %}>{{ Lang.users-status-active }}</option>
					<option value="pending" {% IF user.status == "pending" %}selected{% ENDIF %}>{{ Lang.users-status-pending }}</option>
					<option value="disabled" {% IF user.status == "disabled" %}selected{% ENDIF %}>{{ Lang.users-status-disabled }}</option>
				</select>
				{% if user.isSuperAdmin() %}
					<input type="hidden" name="status" value="{{ user.status }}" />
				{% endif %}
			</p>
		</div>

		<fieldset class="permissions-fieldset">
			<legend>{{ Lang.users-permissions }}</legend>
			<div class="permissions-grid">
				<?php $currentPermissions = $user->permissions ?? []; ?>
				{% for groupKey, definition in permissionsDefinitions %}
					<div class="permissions-card">
						<strong>{{ definition.label }}</strong>
						{% for permission in definition.items %}
							<?php $isChecked = in_array($permission['key'], $currentPermissions ?? []); ?>
							<label class="checkbox {% if user.isSuperAdmin() %}disabled{% endif %}">
								<input type="checkbox" name="permissions[]" value="{{ permission.key }}" {% if user.isSuperAdmin() %}disabled{% endif %} <?php echo $isChecked ? 'checked' : ''; ?>>
								<span>{{ permission.label }}</span>
							</label>
						{% endfor %}
					</div>
				{% endfor %}
			</div>
		</fieldset>

		<p>
			<button class="button success">{{Lang.submit}}</button>
		</p>
	</form>
</section>
