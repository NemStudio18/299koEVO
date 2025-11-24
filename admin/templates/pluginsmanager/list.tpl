<section>
    <header>{{ Lang.pluginsmanager.plugins-list }}</header>
    <form method="post" action="{{ ROUTER.generate("pluginsmanager-save") }}" id="pluginsmanagerForm">
        {{ show.tokenField() }}
        <table>
            <thead>
                <tr>
                    <th>{{ Lang.pluginsmanager.plugin-name }}</th>
                    <th>{{ Lang.pluginsmanager.plugin-version }}</th>
                    <th>{{ Lang.pluginsmanager.priority }}</th>
                    <th>{{ Lang.pluginsmanager.activate }}</th>
                </tr>
            </thead>
            <tbody>
                {% FOR plugin IN plugins %}
                    <tr>
                        <td>
                            {{ plugin.getTranslatedName() }}
                             : {{ plugin.getTranslatedDesc() }}
                            {% IF plugin.getConfigVal("activate") && plugin.isInstalled() == false %}
                                <p>
                                    <a class="button" href="{{ ROUTER.generate("pluginsmanager-maintenance", ["plugin" => plugin.getName(), "token" => token]) }}">{{ Lang.pluginsmanager.maintenance-required }}</a>
                                </p>
                            {% ENDIF %}
                        </td>
                        <td>{{ plugin.getInfoVal("version") }}</td>
                        <td>
                            <select name="priority[{{ plugin.getName() }}]" onchange="document.getElementById('pluginsmanagerForm').submit();">
                                {% FOR k, v IN priority %}
                                    <option {% IF plugin.getconfigVal("priority") == v %}selected{% ENDIF %} value="{{ v }}">{{ v }}</option>
                                {% ENDFOR %}
                            </select>
                        </td>
                        <td>
                            <input onchange="document.getElementById('pluginsmanagerForm').submit();" id="activate[{{ plugin.getName() }}]" type="checkbox" name="activate[{{ plugin.getName() }}]" {% IF plugin.getConfigVal("activate") %}checked{% ENDIF %} />
                        </td>
                    </tr>
                {% ENDFOR %}
            </tbody>
        </table>
    </form>
</section>
