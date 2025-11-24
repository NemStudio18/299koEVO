<section>
    <header>{{ Lang.page.page-list }}</header>
    <a class="button" href="{{ ROUTER.generate("page-admin-new") }}">{{ Lang.page.add-page }}</a>
    <a class="button" href="{{ ROUTER.generate("page-admin-new-parent") }}">{{ Lang.page.add-parent-item }}</a>
    <a class="button" href="{{ ROUTER.generate("page-admin-new-link") }}">{{ Lang.page.add-external-link }}</a>
    {% if lost != "" %}
        <p>{{ Lang.page.ghost-pages-found }} <a href="{{ ROUTER.generate("page-admin-maintenance", ["id" => lost, "token" => token]) }}">{{ Lang.page.click-here }}</a> {{ Lang.page.to-execute-maintenance-script }}</p>
    {% endif %}
    <table>
        <thead>
            <tr>
                <th>{{ Lang.page.page-name }}</th>
                <th>{{ Lang.page.address }}</th>
                <th>{{ Lang.page.position }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {% for pageItem in page.getItems() %}
                {% if pageItem.getParent() == false && pageItem.isVisibleOnList() %}
                    <tr>
                        <td>{{ pageItem.getName() }}</td>
                        <td>{% if pageItem.targetIs() != "parent" %}<input readonly="readonly" type="text" value="{{ page.makeUrl(pageItem) }}" />{% endif %}</td>
                        <td>
                            <a class="up" href="{{ ROUTER.generate("page-admin-page-up", ["id" => pageItem.getId() , "token" => token]) }}"><i class="fa-regular fa-circle-up" title="{{ Lang.page.move-up }}"></i></a>
                            <a class="down" href="{{ ROUTER.generate("page-admin-page-down", ["id" => pageItem.getId() , "token" => token]) }}"><i class="fa-regular fa-circle-down" title="{{ Lang.page.move-down }}"></i></a>
                        </td>
                        <td>
                            <div role="group">
                                <a class="button" href="{{ ROUTER.generate("page-admin-edit", ["id" => pageItem.getId()]) }}">{{ Lang.edit }}</a> 
                                {% if pageItem.getIsHomepage() == false && pageItem.targetIs() != "plugin" %}<a class="button alert" href="{{ ROUTER.generate("page-admin-delete", ["id" => pageItem.getId(), "token" => token]) }}" onclick = "if (!confirm('{{ Lang.confirm.deleteItem }}'))
                                                                            return false;">{{ Lang.delete }}</a>{% endif %}	
                            </div>
                        </td>
                    </tr>
                    {% for pageItemChild in page.getItems() %}
                        {% if pageItemChild.getParent() == pageItem.getId() && pageItemChild.isVisibleOnList() %}
                            <tr>
                                <td>▸ {{ pageItemChild.getName() }}</td>
                                <td><input readonly="readonly" type="text" value="{{ page.makeUrl(pageItemChild) }}" /></td>
                                <td>
                                    <a class="up" href="{{ ROUTER.generate("page-admin-page-up", ["id" => pageItemChild.getId(), "token" => token]) }}"><i class="fa-regular fa-circle-up" title="{{ Lang.page.move-up }}"></i></a>
                                    <a class="down" href="{{ ROUTER.generate("page-admin-page-down", ["id" => pageItemChild.getId(), "token" => token]) }}"><i class="fa-regular fa-circle-down" title="{{ Lang.page.move-down }}"></i></a>
                                </td>
                                <td>
                                    <div role="group">
                                        <a class="button" href="{{ ROUTER.generate("page-admin-edit", ["id" => pageItemChild.getId()]) }}">{{ Lang.edit }}</a> 
                                        {% if pageItemChild.getIsHomepage() == false && pageItemChild.targetIs() != "plugin" %}<a class="button alert" href="{{ ROUTER.generate("page-admin-delete", ["id" => pageItemChild.getId(), "token" => token]) }}" onclick = "if (!confirm('{{ Lang.confirm.deleteItem }}'))
                                                                        return false;">{{ Lang.delete }}</a>{% endif %}	
                                    </div>
                                </td>
                            </tr>
                        {% endif %}
                    {% endfor %}
                {% endif %}
            {% endfor %}
        </tbody>
    </table>
</section>
