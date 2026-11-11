<section class="overflow-auto">
	<header>{{Lang.blog-posts-list}}</header>
	<a class="button" href="{ ROUTER.generate("admin-blog-edit-post") }">{{Lang.blog-add}}</a>
	<a target="_blank" class="button" href="{ ROUTER.generate("blog-rss") }">{{Lang.rss_feed}}</a>
	<table class="small">
		<tr>
			<th>{{Lang.blog-title}}</th>
			<th>{{Lang.blog-date}}</th>
			<th>{{Lang.blog-comments}}</th>
			<th>{{Lang.blog-see}}</th>
			<th>{{Lang.blog-categories}}</th>
			<th>{{Lang.delete}}</th>
		</tr>
		{% FOR item IN newsManager.getItems() %}
			<tr id="post{{item.getId()}}">
				<td>
					<a title="{{Lang.blog-edit}}" href="{ ROUTER.generate("admin-blog-edit-post", ["id" => item.getId()]) }">{{item.getName()}}</a>
				</td>
				<td>
                    {{util.getDate(item.getDate())}}
                </td>
				<td class="text-center">
					{% IF newsManager.countComments(item.getId()) > 0 %}
						<a title="{{newsManager.countComments(item.getId())}} {{Lang.blog-comments}}" href="{ ROUTER.generate("admin-blog-list-comments", ["id" => item.getId()]) }" class="button">
							<i class="fa-regular fa-comments"></i>
							{{newsManager.countComments(item.getId())}}</a>
                    {% else %}
                        0
					{% ENDIF %}
				</td>
				<td class="text-center">
					<a title="{{Lang.blog-see}}" target="_blank" href="{{item.getUrl}}" class="button">
						<i class="fa-solid fa-eye"></i>
					</a>
				</td>
                <td>
                    {% IF item.categories %}
						{% FOR cat IN item.categories %}
                            <span class="blog-category">{{cat.label}}</span>
                        {% ENDFOR %}
                    {% ELSE %}
                        {{Lang.blog.categories.none}}
                    {% ENDIF %}
                </td>
				<td class="text-center">
					<a title="{{Lang.delete}}" onclick="BlogDeletePost('{{item.getId()}}')" class="button alert">
						<i class="fa-regular fa-trash-can"></i>
					</a>
				</td>
			</tr>
		{% ENDFOR %}
	</table>
</section>

<script>
async function BlogDeletePost(id) {
	if (confirm('{{Lang.confirm.deleteItem}}') === true) {
		let url = '{ ROUTER.generate("admin-blog-delete-post") }';
		let data = {
			id: id,
			token: '{{token}}'
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
			fadeOut(document.querySelector('#post' + id));
			Toastify({
				text: "{{Lang.core-item-deleted}}",
				className: "success"		
			}).showToast();
		} else {
			Toastify({
				text: "{{Lang.core-item-not-deleted}}",
				className: "error"		
			}).showToast();
		}				
	};
}
</script>