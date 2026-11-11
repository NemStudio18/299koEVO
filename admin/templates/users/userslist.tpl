{% if pendingRegistrations and pendingRegistrations|length > 0 %}
<section class="module-card">
	<header>
		<i class="fa-solid fa-user-clock"></i>
		Demandes d'inscription en attente ({{pendingRegistrations|length}})
	</header>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th>Pseudo</th>
					<th>Email</th>
					<th>Date de demande</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				{% FOR reg IN pendingRegistrations %}
					<tr>
						<td>{{reg.username}}</td>
						<td>{{reg.email}}</td>
						<td>
							{% if reg.created_at %}
								{{Util.getDateHour(reg.created_at)}}
							{% else %}
								-
							{% endif %}
						</td>
						<td>
							<div role="group">
								<a class="button small success" title="Approuver" href='{ ROUTER.generate("users-registration-approve", ["token" => reg.token]) }' onclick="return confirm('Approuver cette demande d\'inscription ?');">
									<i class="fa-solid fa-check"></i> Approuver
								</a>
								<a class="button small alert" title="Rejeter" href='{ ROUTER.generate("users-registration-reject", ["token" => reg.token]) }' onclick="return confirm('Rejeter cette demande d\'inscription ?');">
									<i class="fa-solid fa-times"></i> Rejeter
								</a>
							</div>
						</td>
					</tr>
				{% ENDFOR %}
			</tbody>
		</table>
	</div>
</section>
{% endif %}

<section class="module-card">
	<header>{{Lang.users-list}}</header>
	<div class="module-actions">
		<a class='button success' href='{ ROUTER.generate("users-add") }'>
			<i class="fa-solid fa-user-plus"></i>
			{{Lang.users-add}}</a>
		<a class='button' href='{ ROUTER.generate("groups-admin-home") }'>
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
						<td>
							{{user.username}}
							{% if user.isSuspicious() %}
								<span class="badge alert" title="Multi-compte suspecté">
									<i class="fa-solid fa-triangle-exclamation"></i>
								</span>
							{% endif %}
						</td>
						<td>{{user.email}}</td>
						<td>{{user.group_name}}</td>
						<td>
							{% IF user.status == "disabled" %}
								<span class="badge alert">{{Lang.users-status-disabled}}</span>
							{% ELSEIF user.status == "pending" %}
								<span class="badge warning">{{Lang.users-status-pending}}</span>
							{% ELSE %}
								<span class="badge success">{{Lang.users-status-active}}</span>
							{% ENDIF %}
						</td>
						<td>
							{% if user.permissions %}
								<ul class="permissions-list">
									{% for permission in user.permissions %}
										<li>{{permission}}</li>
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
									<a class="button small alert" title="{{Lang.users-delete}}" href='{{user.deleteLink}}' onclick="if (!confirm('{{Lang.confirm.deleteItem}}')) return false;">
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
