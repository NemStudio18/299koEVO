<section>
    <header>{{Lang.blog-comments-list}}</header>
    <a class="button" href="{ ROUTER.generate("admin-blog-list") }">{{Lang.blog-back-to-posts}}</a>
    <table>
        <tr>
            <th>{{Lang.blog-comments}}</th>
            <th></th>
        </tr>
        {% FOR k, v IN newsManager.getFlatComments() %}
            <tr id="list-comment-{{v.getId()}}">
                <td>
                    {{v.getAuthor()}} <i>{{v.getAuthorMail()}}</i> - {{util::getNaturalDate(v.getDate())}} :<br><br>
                    <textarea id="content{{v.getId()}}" name="content{{v.getId()}}">{{v.getContent()}}</textarea>
                </td>
                <td>
                    <a onclick="BlogUpdateComment({{v.getId()}});" class="button">{{Lang.save}}</a>
                    <a onclick="BlogDeleteComment('{{v.getId()}}')" class="button alert">{{Lang.delete}}</a>
                </td>
            </tr>
        {% ENDFOR %}
    </table>
    <script>
        async function BlogUpdateComment(id) {
            let url = '{ ROUTER.generate("admin-blog-save-comment") }';
                let data = {
                    idComment: id,
                    token: '{{token}}',
                    idPost: '{{idPost}}',
                    content: document.querySelector('#content' + id).value
                };
                let response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                let result = await response;
                if (result.status === 202) {
                    Toastify({
                        text: "{{Lang.core-changes-saved}}",
                        className: "success"		
                    }).showToast();
                } else {
                    Toastify({
                        text: "{{Lang.core-changes-not-saved}}",
                        className: "error"		
                    }).showToast();
                }
        }

        async function BlogDeleteComment(id) {
            if (confirm('{{Lang.confirm.deleteItem}}') === true) {
                let url = '{ ROUTER.generate("admin-blog-delete-comment") }';
                let data = {
                    idComment: id,
                    token: '{{token}}',
                    idPost: '{{idPost}}'
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
                    fadeOut(document.querySelector('#list-comment-' + id));
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
</section>

<script>

</script>