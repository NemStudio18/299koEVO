<section>
	<header>{{Lang.users-list}}</header>
	<a class='button' href='{{ROUTER.generate("users-add")}}'>
		<i class="fa-solid fa-user-plus"></i>
		{{Lang.users-add}}</a>
	<table>
		<thead>
			<tr>
				<th>{{Lang.users-mail}}</th>
				<th>{{Lang.users-actions}}</th>
			</tr>
		</thead>
		{% FOR user IN users %}
			<tr>
				<td>{{user.email}}</td>
				<td>
					<div role="group">
						<a class="button small" title="{{Lang.users-edit}}" href='{{ ROUTER.generate("users-edit", ["id" => user.id]) }}'>
							<i class="fa-solid fa-user-pen"></i>
						</a>
						<a class="button small alert" title="{{Lang.users-delete}}" href='{{ user.deleteLink }}' onclick="if (!confirm('{{Lang.confirm.deleteItem}}')) return false;">
							<i class="fa-solid fa-user-xmark"></i>
						</a>
					</div>
				</td>
			</tr>
		{% ENDFOR %}
	</table>
</section>
