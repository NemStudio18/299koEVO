<section class="overflow-auto">
	<header>{{ Lang.configmanager-backup-list }}</header>
    <a class="button" href="{{ ROUTER.generate("configmanager-create-backup", ["token" => token]) }}">{{ Lang.configmanager-backup-create }}</a>
    <table>
        <tr>
            <th>{{ Lang.configmanager-backup-date }}</th>
            <th>{{ Lang.configmanager-backup-size }}</th>
            <th>{{ Lang.configmanager-backup-download }}</th>
            <th>{{ Lang.configmanager-backup-delete }}</th>
        </tr>
        {% FOR backup IN backups %}
        <tr id="post{{ backup.timestamp }}">
            <td>{{ Util.getDateHour(backup.date) }}</td>
            <td>{{ backup.filesize }}</td>
            <td>
                <a title="{{ Lang.configmanager-backup-download }}" class="button" href="{{ backup.url }}"><i class="fa-solid fa-download"></i></a>
            </td>
            <td style="text-align: center">
                <a title="{{ Lang.configmanager-backup-delete }}" onclick="ConfigManagerDeleteBackup('{{ backup.timestamp }}')" class="button alert">
                    <i class="fa-regular fa-trash-can"></i>
                </a>
            </td>
        </tr>
        {% ENDFOR %}
		{% IF emptyBackups %}
		<tr>
			<td colspan="4">{{ Lang.configmanager-backup-no-backup }}</td>
		</tr>
		{% ENDIF %}
    </table>
</section>

<script>
async function ConfigManagerDeleteBackup(timestamp) {
	if (confirm('{{ Lang.confirm.deleteItem }}') === true) {
		let url = '{{ ROUTER.generate("configmanager-delete-backup") }}';
		let data = {
			timestamp: timestamp,
			token: '{{ token }}'
		};
		let response = await fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify(data)
		});
		// See body : let result = await response.json();
		let result = await response;
		if (result.status === 204) {
			fadeOut(document.querySelector('#post' + timestamp));
			Toastify({
				text: "{{ Lang.core-item-deleted}}",
				className: "success"		
			}).showToast();
		} else {
			Toastify({
				text: "{{ Lang.core-item-not-deleted}}",
				className: "error"		
			}).showToast();
		}				
	};
}
</script>
