<section class="module-card">
	<header>{{Lang.users-list}}</header>
	<div class="module-actions">
		<a class='button success' href='{{ROUTER.generate("users-add")}}'>
			<i class="fa-solid fa-user-plus"></i>
			{{Lang.users-add}}</a>
		<a class='button' href='{{ROUTER.generate("groups-admin-home")}}'>
			<i class="fa-solid fa-users-line"></i>
			{{Lang.groups-list}}</a>
	</div>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th>{{Lang.users-username}}</th>
					<th>{{Lang.users-mail}}</th>
					<th>{{Lang.users-group}}</th>
					<th>{{Lang.users-status}}</th>
					<th>{{Lang.users-permissions}}</th>
					<th>{{Lang.users-actions}}</th>
				</tr>
			</thead>
			<tbody>
				{% FOR user IN users %}
					<tr>
						<td>{{user.username}}</td>
						<td>{{user.email}}</td>
						<td>{{user.group_name}}</td>
						<td>
							{% IF user.status == "disabled" %}
								<span class="badge alert">{{ Lang.users-status-disabled }}</span>
							{% ELSEIF user.status == "pending" %}
								<span class="badge warning">{{ Lang.users-status-pending }}</span>
							{% ELSE %}
								<span class="badge success">{{ Lang.users-status-active }}</span>
							{% ENDIF %}
						</td>
						<td>
							{% if user.permissions %}
								<ul class="permissions-list">
									{% for permission in user.permissions %}
										<li>{{ permission }}</li>
									{% endfor %}
								</ul>
							{% else %}
								<span class="text-muted">-</span>
							{% endif %}
						</td>
						<td>
							<div role="group">
								<a class="button small" title="{{Lang.users-edit}}" href='{{ ROUTER.generate("users-edit", ["id" => user.id]) }}'>
									<i class="fa-solid fa-user-pen"></i>
								</a>
								{% if not user.isSuperAdmin() %}
									<a class="button small alert" title="{{Lang.users-delete}}" href='{{ user.deleteLink }}' onclick="if (!confirm('{{Lang.confirm.deleteItem}}')) return false;">
										<i class="fa-solid fa-user-xmark"></i>
									</a>
								{% endif %}
							</div>
						</td>
					</tr>
				{% ENDFOR %}
			</tbody>
		</table>
	</div>
</section>
