<div class="list-item-container">
    <div class="list-item-list">
        <div>{{Lang.blog.categories.name}}</div>
        <div>{{Lang.blog.categories.itemsNumber}}</div>
        <div>{{Lang.blog.categories.actions}}</div>
    </div>
    {% IF !categoriesManager.imbricatedCategories %}
        <div class="list-item-list">{{Lang.blog.categories.none}}</div>
    {% ELSE %}
        {% FOR cat IN categoriesManager.imbricatedCategories %}
            {{cat.outputAsList()}}
        {% ENDFOR %}
    {% ENDIF %}
</div>
<footer id="list-item-endlist">
    <div id="categorie-add-form-container">
        <h4 id="head-add-cat">{{Lang.blog.categories.addCategory}}</h4>
        {{SHOW.tokenField}}
        <div class="input-field">
            <label for="category-list-add-label">{{Lang.blog.categories.categoryName}}</label>
            <input type="text" name="category-list-add-label" id="category-list-add-label" required/>
            <label for="categoy-list-add-parentId">{{Lang.blog.categories.categoryParent}}</label>
            <select name="category-list-add-parentId" id="category-list-add-parentId">
                <option value="0">{{Lang.blog.categories.none}}</option>
                {% IF categoriesManager.imbricatedCategories %}
                    {% FOR cat IN categoriesManager.imbricatedCategories %}
                        {{cat.outputAsSelectOne(0)}}
                    {% ENDFOR %}
                {% ENDIF %}
            </select>
        </div>
        <button onclick="BlogAddCategoryTab()" id="list-item-add-btn">{{Lang.blog.categories.addCategory}}</button>
    </div>
</footer>

