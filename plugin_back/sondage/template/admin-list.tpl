<section>
	<header>{{ Lang.sondage-admin-list }}</header>
	<a class="button" href="{{ addUrl }}">{{ Lang.sondage-admin-add }}</a>
	<table>
		<tr>
			<th>{{ Lang.sondage.title }}</th>
			<th>{{ Lang.sondage.status }}</th>
			<th>{{ Lang.sondage.votes }}</th>
			<th>{{ Lang.sondage.date }}</th>
			<th>{{ Lang.sondage.actions }}</th>
		</tr>
		{% for sondage in sondageManager.getItems() %}
			<tr>
				<td>
					<a title="{{ Lang.edit }}" href="{ ROUTER.generate("sondage-admin-edit-id", ["id" => sondage.getId()]) }">{{ sondage.getTitle() }}</a>
				</td>
				<td>
					{% if sondage.isClosed() %}<span style="color: #f39c12; font-weight: bold;">Clôturé</span>
					{% elseif sondage.getActive() %}{{ Lang.core-active }}
					{% else %}{{ Lang.core-inactive }}{% endif %}
				</td>
				<td>{{ sondage.getTotalVotes() }}</td>
				<td>{{ util.getDate(sondage.getDate()) }}</td>
				<td>
					<a title="{{ Lang.edit }}" href="{ ROUTER.generate("sondage-admin-edit-id", ["id" => sondage.getId()]) }" class="button">{{ Lang.edit }}</a>
					<a title="{{ Lang.delete }}" href="{ ROUTER.generate("sondage-admin-delete", ["id" => sondage.getId(), "token" => token]) }" class="button alert" onclick="return confirm('{{ Lang.confirm.deleteItem }}')">{{ Lang.delete }}</a>
				</td>
			</tr>
		{% endfor %}
	</table>
</section>
