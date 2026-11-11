<ul class="nested-sortable">
    {% FOR node IN treeItems %}
        <li class="sortable-item" data-id="{{ node.id }}" data-type="{{ node.type }}">
            <div class="item-content">
                <i class="fa-solid fa-grip-vertical drag-handle"></i>
                <div class="item-icon"><i class="{{ node.icon }}"></i></div>
                <strong style="flex-grow: 1;">{{ node.label }}</strong>
                
                <div class="item-url">{{ node.url }}</div>
                
                {% IF node.badge == 'page' %}
                    <span class="item-type badge-page">Page</span>
                {% ELSEIF node.badge == 'folder' %}
                    <span class="item-type badge-folder">Dossier</span>
                {% ELSEIF node.badge == 'link' %}
                    <span class="item-type badge-link">Lien</span>
                {% ELSEIF node.badge == 'plugin' %}
                    <span class="item-type badge-plugin">Plugin</span>
                {% ENDIF %}
            </div>
            <div class="children-container">
                {% IF node.children %}
                    {% SET treeItems = node.children %}
                    {% INCLUDE 'admin/templates/page/tree_node.tpl' %}
                {% ENDIF %}
            </div>
        </li>
    {% ENDFOR %}
</ul>
