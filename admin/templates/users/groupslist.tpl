<section class="module-card">
	<header>{{Lang.groups-list}}</header>
	<div class="module-actions">
		<a class='button success' href='{{ROUTER.generate("groups-add")}}'>
			<i class="fa-solid fa-users-line"></i>
			{{Lang.groups-add}}</a>
	</div>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th>{{Lang.groups-name}}</th>
					<th>{{Lang.groups-slug}}</th>
					<th>{{Lang.groups-users-count}}</th>
					<th>{{Lang.groups-permissions}}</th>
					<th>{{Lang.groups-system}}</th>
					<th>{{Lang.groups-actions}}</th>
				</tr>
			</thead>
			<tbody>
				{% FOR group IN groups %}
					<tr>
						<td>{{group.name}}</td>
						<td><code>{{group.slug}}</code></td>
						<td>{{group.userCount}}</td>
						<td>
							{% if group.permissions %}
								<ul class="permissions-list">
									{% for permission in group.permissions %}
										<li>
											{% if permission == "*" %}
												<span class="badge success">{{Lang.permissions.all}}</span>
											{% else %}
												{{permission}}
											{% endif %}
										</li>
									{% endfor %}
								</ul>
							{% else %}
								<span class="text-muted">-</span>
							{% endif %}
						</td>
						<td>
							{% if group.system %}
								<span class="badge warning">{{Lang.groups-system-yes}}</span>
							{% else %}
								<span class="text-muted">{{Lang.groups-system-no}}</span>
							{% endif %}
						</td>
						<td>
							<div role="group">
								{% if not group.system %}
									<a class="button small" title="{{Lang.groups-edit}}" href='{{ ROUTER.generate("groups-edit", ["id" => group.id]) }}'>
										<i class="fa-solid fa-pen"></i>
									</a>
									{% if group.userCount == 0 %}
										<a class="button small alert" title="{{Lang.groups-delete}}" href='{{ ROUTER.generate("groups-delete", ["id" => group.id, "token" => token]) }}' onclick="if (!confirm('{{Lang.confirm.deleteItem}}')) return false;">
											<i class="fa-solid fa-trash"></i>
										</a>
									{% endif %}
								{% else %}
									<span class="text-muted">{{Lang.groups-system-readonly}}</span>
								{% endif %}
							</div>
						</td>
					</tr>
				{% ENDFOR %}
			</tbody>
		</table>
	</div>
</section>

