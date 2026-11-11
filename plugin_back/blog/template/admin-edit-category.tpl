<section>
    <header>{{Lang.blog.categories.editCategory}}</header>
    <label for="category-list-edit-label">{{Lang.blog.categories.categoryName}}</label>
    <input type="text" value="{{category.label}}" id="category-list-edit-label" name="category-list-edit-label"/>
    <label for="category-list-edit-parentId">{{Lang.blog.categories.categoryParent}}</label>
    {{categoriesManager.outputAsSelect(category.parentId, category.id, "category-list-edit-parentId")}}
    <button onclick="BlogEditSaveCategory()">{{Lang.blog.categories.editCategory}}</button>
</section>
<script>
    async function BlogEditSaveCategory() {
        let url = '{ ROUTER.generate("admin-blog-save-category", ["id" => category.id]) }';
        let data = {
            label: document.querySelector('#category-list-edit-label').value,
            parentId: document.querySelector('#category-list-edit-parentId').value,
            token: '{{token}}'
        };
        try {
            let response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            if (response.status === 202) {
                Toastify({
                    text: "{{Lang.core-item-edited}}",
                    className: "success"		
                }).showToast();
                // Refresh list
                Fancybox.close();
                Fancybox.show([
                    {
                        src: "{{categoriesManager.getAjaxDisplayListUrl()}}",
                        type: "ajax",
                    },
                ]);
            } else {
                Toastify({
                    text: "{{Lang.core-item-not-edited}}",
                    className: "error"		
                }).showToast();
            }
        } catch (err) {
            console.error(err);
            alert("Error: " + err.message);
        }
    };
</script>