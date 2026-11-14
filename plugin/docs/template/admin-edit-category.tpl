<section>
    <header>{{ Lang.wiki.categories.editCategory}}</header>
    <label for="category-list-edit-label">{{Lang.wiki.categories.categoryName}}</label>
    <input type="text" value="{{category.label}}" id="category-list-edit-label" name="category-list-edit-label"/>
    <label for="category-list-edit-parentId">{{Lang.wiki.categories.categoryParent}}</label>
    {{categoriesManager.outputAsSelect(category.parentId, category.id, "category-list-edit-parentId")}}
    <button onclick="WikiEditSaveCategory()">{{ Lang.wiki.categories.editCategory}}</button>
</section>
<script>
    async function WikiEditSaveCategory() {
        let url = '{{ROUTER.generate("admin-docs-save-category", ["id" => category.id])}}';
        let data = {
            label: document.querySelector('#category-list-edit-label').value,
            parentId: document.querySelector('#category-list-edit-parentId').value,
            token: '{{ token }}'
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
                text: "{{ Lang.core-item-edited }}",
                className: "success"		
            }).showToast();
            
            // Fermer la modal Fancybox
            if (typeof Fancybox !== 'undefined') {
                Fancybox.close();
            }
            
            		// Rafraîchir la liste des catégories
		setTimeout(() => {
			// Recharger seulement l'onglet des catégories
			location.reload();
		}, 1000);
        } else {
            Toastify({
                text: "{{ Lang.core-item-not-edited }}",
                className: "error"		
            }).showToast();
        }	
    };
</script> 