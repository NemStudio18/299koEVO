<div class="tabs-container blog-admin-tabs">
    <ul class="tabs-header">
        <li class="{% IF activeTab == 'posts' || !activeTab %}default-tab{% ENDIF %}">
            <i class="fa-solid fa-file-lines"></i> {{Lang.blog-posts-list}}
        </li>
        <li class="{% IF activeTab == 'categories' %}default-tab{% ENDIF %}">
            <i class="fa-solid fa-folder-tree"></i> {{Lang.blog-categories}}
        </li>
        <li class="{% IF activeTab == 'config' %}default-tab{% ENDIF %}">
            <i class="fa-solid fa-gear"></i> {{Lang.core-settings}}
        </li>
    </ul>
    <ul class="tabs">
        <!-- Tab 1: Posts List -->
        <li class="tab">
            <section class="overflow-auto">
                <div class="admin-actions-bar mb-medium">
                    <a class="button primary" href="{ ROUTER.generate("admin-blog-edit-post") }">
                        <i class="fa-solid fa-plus"></i> {{Lang.blog-add}}
                    </a>
                    <a target="_blank" class="button" href="{ ROUTER.generate("blog-rss") }">
                        <i class="fa-solid fa-rss"></i> {{Lang.rss_feed}}
                    </a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{Lang.blog-title}}</th>
                            <th>{{Lang.blog-date}}</th>
                            <th class="text-center">{{Lang.blog-comments}}</th>
                            <th class="text-center">{{Lang.blog-see}}</th>
                            <th>{{Lang.blog-categories}}</th>
                            <th class="text-center">{{Lang.delete}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% FOR item IN posts %}
                            <tr id="post{{item.getId()}}">
                                <td>
                                    <a class="font-bold" title="{{Lang.blog-edit}}" href="{ ROUTER.generate("admin-blog-edit-post", ["id" => item.getId()]) }">{{item.getName()}}</a>
                                </td>
                                <td>
                                    <span class="color-muted">{{util.getDate(item.getDate())}}</span>
                                </td>
                                <td class="text-center">
                                    {% IF newsManager.countComments(item.getId()) > 0 %}
                                        <a title="{{newsManager.countComments(item.getId())}} {{Lang.blog-comments}}" href="{ ROUTER.generate("admin-blog-list-comments", ["id" => item.getId()]) }" class="button small">
                                            <i class="fa-regular fa-comments"></i>
                                            {{newsManager.countComments(item.getId())}}
                                        </a>
                                    {% ELSE %}
                                        <span class="color-muted">0</span>
                                    {% ENDIF %}
                                </td>
                                <td class="text-center">
                                    <a title="{{Lang.blog-see}}" target="_blank" href="{{item.getUrl}}" class="button small">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                                <td>
                                    {% IF item.categories %}
                                        <div class="flex-wrap gap-small">
                                            {% FOR cat IN item.categories %}
                                                <span class="badge">{{cat.label}}</span>
                                            {% ENDFOR %}
                                        </div>
                                    {% ELSE %}
                                        <span class="color-muted italic font-small">{{Lang.blog.categories.none}}</span>
                                    {% ENDIF %}
                                </td>
                                <td class="text-center">
                                    <button title="{{Lang.delete}}" onclick="BlogDeletePost('{{item.getId()}}')" class="button alert small">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        {% ENDFOR %}
                    </tbody>
                </table>
            </section>
        </li>

        <!-- Tab 2: Categories Management -->
        <li class="tab">
            <section class="content">
                <header>
                    <h3>{{Lang.blog-categories}}</h3>
                </header>
                <div id="blog-categories-list-container">
                    {{categoriesManager.outputAsList()}}
                </div>
            </section>
        </li>

        <!-- Tab 3: Configuration -->
        <li class="tab">
            <section class="content">
                <header>
                    <h3>{{Lang.core-settings}}</h3>
                </header>
                {% INCLUDE "blog/template/param.tpl" %}
            </section>
        </li>
    </ul>
</div>

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
		if (response.status === 204) {
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

    window.BlogEditCategoryDisplay = function(id) {
        let data = "id=" + id + "&token=" + '{{token}}';
        new Fancybox([
            {
                src: '{{categoriesManager.getEditUrl()}}',
                type: "ajax",
                ajax : data
            },
        ]);
    }

    window.BlogAddCategoryTab = async function() {
        let url = '{{categoriesManager.getAddCategoryUrl()}}';
        let data = {
            label: document.querySelector('#category-list-add-label').value,
            parentId: document.querySelector('#category-list-add-parentId').value,
            token: '{{token}}'
        };
        if (!data.label) {
            Toastify({
                text: "{{Lang.blog.categories.none}}",
                className: "error"
            }).showToast();
            return;
        }
        try {
            let response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            if (response.status === 201) {
                Toastify({
                    text: "{{Lang.core-item-added}}",
                    className: "success"		
                }).showToast();
                if (typeof refreshBlogCategoriesList === 'function') {
                    refreshBlogCategoriesList();
                }
            } else {
                Toastify({
                    text: "{{Lang.core-item-not-added}}",
                    className: "error"		
                }).showToast();
            }	
        } catch (err) {
            console.error(err);
            alert("Error: " + err.message);
        }
    }

    window.BlogDeleteCategory = async function(id) {
        if (confirm('{{Lang.confirm.deleteItem}}') === true) {
            let url = '{{categoriesManager.getDeleteUrl()}}';
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
            
            if (response.status === 204) {
                Toastify({
                    text: "{{Lang.core-item-deleted}}",
                    className: "success"		
                }).showToast();
                if (typeof refreshBlogCategoriesList === 'function') {
                    refreshBlogCategoriesList();
                }
            } else {
                Toastify({
                    text: "{{Lang.core-item-not-deleted}}",
                    className: "error"		
                }).showToast();
            }		
        }
    }

    window.refreshBlogCategoriesList = async function() {
        const container = document.getElementById('blog-categories-list-container');
        if (!container) return;
        
        let response = await fetch('{{categoriesManager.getAjaxDisplayListUrl()}}');
        let html = await response.text();
        container.innerHTML = html;
    }

    window.CategoriesDeployChild = function(item) {
        const nextToggle = item.parentNode.nextSibling;
        item.classList.toggle('rotate-180');
        if (nextToggle.style.display === 'none') {
            nextToggle.style.display = 'block';
        } else {
            nextToggle.style.display = 'none';
        }
    }
</script>

<style>
.blog-admin-tabs .badge {
    padding: 2px 8px;
    background: var(--section-head-bg-color);
    border: 1px solid var(--form-border-color);
    border-radius: 4px;
    font-size: 0.8rem;
}
.blog-admin-tabs .admin-actions-bar {
    display: flex;
    gap: 1rem;
    align-items: center;
}
</style>
