<section class="module-card">
	<header>{{Lang.users-add}}</header>
	<form method="POST" action="{{link}}" class="user-form-grid">
		{{SHOW.tokenField}}
		<div class="form-grid">
			<p>
				<label for="username">{{ Lang.users-username }}</label>
				<input type="text" id="username" name="username" required minlength="3" />
			</p>
			<p>
				<label for="email">{{ Lang.users-mail}}</label>
				<input type="email" id="email" name="email" required />
			</p>
			<p>
				<label for="pwd">{{Lang.password}}</label>
				<input type="password" id="pwd" name="pwd" required minlength="8" />
			</p>
			<p>
				<label for="group">{{ Lang.users-group }}</label>
				<select id="group" name="group_slug">
					{% for group in groups %}
						<option value="{{ group.getSlug() }}">{{ group.getName() }}</option>
					{% endfor %}
				</select>
			</p>
			<p>
				<label for="status">{{ Lang.users-status }}</label>
				<select id="status" name="status">
					<option value="active">{{ Lang.users-status-active }}</option>
					<option value="pending">{{ Lang.users-status-pending }}</option>
					<option value="disabled">{{ Lang.users-status-disabled }}</option>
				</select>
			</p>
		</div>

		<fieldset class="permissions-fieldset">
			<legend>{{ Lang.users-permissions }}</legend>
			<div class="permissions-grid">
				{% for groupKey, definition in permissionsDefinitions %}
					<div class="permissions-card">
						<strong>{{ definition.label }}</strong>
						{% for permission in definition.items %}
							<label class="checkbox">
								<input type="checkbox" name="permissions[]" value="{{ permission.key }}" />
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
