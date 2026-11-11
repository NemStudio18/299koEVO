<section class="module-card">
    <header>{{ Lang.telemetryadmin.installations-title }}</header>

    <form method="get" class="filters" style="display:flex;flex-wrap:wrap;gap:0.5rem;margin:1rem 0;">
        <input type="hidden" name="page" value="1"/>
        <input type="text" name="search" placeholder="{{ Lang.telemetryadmin.search }}" value="{{ filters.search }}"/>
        <input type="text" name="lang" placeholder="{{ Lang.telemetryadmin.lang }}" value="{{ filters.lang }}"/>
        <input type="text" name="theme" placeholder="{{ Lang.telemetryadmin.theme }}" value="{{ filters.theme }}"/>
        <input type="text" name="cms_version" placeholder="CMS" value="{{ filters.cms_version }}"/>
        <input type="text" name="php_version" placeholder="PHP" value="{{ filters.php_version }}"/>
        <select name="status">
            <option value="">{{ Lang.telemetryadmin.all-statuses }}</option>
            <option value="online" {% IF filters.status == "online" %}selected{% ENDIF %}>{{ Lang.telemetryadmin.online }}</option>
            <option value="offline" {% IF filters.status == "offline" %}selected{% ENDIF %}>{{ Lang.telemetryadmin.offline }}</option>
        </select>
        <input type="number" name="since_days" placeholder="{{ Lang.telemetryadmin.since-days }}" value="{{ filters.since_days }}"/>
        <input type="number" name="level" placeholder="Level" value="{{ filters.level }}"/>
        <button class="button" type="submit">{{ Lang.telemetryadmin.filter }}</button>
        <a class="button ghost" href="{{ ROUTER.generate("telemetryadmin-installations") }}">{{ Lang.telemetryadmin.reset }}</a>
        <a class="button" href="{{ exportUrl }}">{{ Lang.telemetryadmin.export-csv }}</a>
    </form>

    <div style="overflow:auto;">
    <table class="table">
        <thead>
            <tr>
                <th>{{ Lang.telemetryadmin.site-name }}</th>
                <th>{{ Lang.telemetryadmin.site-url }}</th>
                <th>{{ Lang.telemetryadmin.lang }}</th>
                <th>{{ Lang.telemetryadmin.theme }}</th>
                <th>CMS / PHP</th>
                <th>{{ Lang.telemetryadmin.users }}</th>
                <th>{{ Lang.telemetryadmin.pages }}</th>
                <th>{{ Lang.telemetryadmin.plugins }}</th>
                <th>{{ Lang.telemetryadmin.themes }}</th>
                <th>{{ Lang.telemetryadmin.last-seen }}</th>
                <th>{{ Lang.telemetryadmin.status }}</th>
                <th>{{ Lang.telemetryadmin.actions }}</th>
            </tr>
        </thead>
        <tbody>
            {% IF installations %}
                {% FOR inst IN installations %}
                <tr>
                    <td>{% IF inst.site_name %}{{ inst.site_name }}{% ELSE %}{{ inst.installation_id }}{% ENDIF %}</td>
                    <td><a href="{{ inst.site_url }}" target="_blank" rel="noreferrer">{{ inst.site_url }}</a></td>
                    <td>{% IF inst.site_lang %}{{ inst.site_lang }}{% ELSE %}{{ inst.default_language }}{% ENDIF %}</td>
                    <td>{% IF inst.site_theme %}{{ inst.site_theme }}{% ELSE %}{{ inst.default_theme }}{% ENDIF %}</td>
                    <td>{{ inst.cms_version }} / {{ inst.php_version }}</td>
                    <td>{{ inst.users_count }}</td>
                    <td>{{ inst.pages_count }}</td>
                    <td>
                        {% IF inst.active_plugins_count %}{{ inst.active_plugins_count }}{% ELSE %}?{% ENDIF %}/
                        {% IF inst.total_plugins_count %}{{ inst.total_plugins_count }}{% ELSE %}{% IF inst.plugins_count %}{{ inst.plugins_count }}{% ELSE %}?{% ENDIF %}{% ENDIF %}
                    </td>
                    <td>{{ inst.themes_count }}</td>
                    <td>{{ inst.last_seen }}</td>
                    <td>
                        {% IF inst.is_online %}<span class="tag success">{{ Lang.telemetryadmin.online }}</span>{% ELSE %}<span class="tag alert">{{ Lang.telemetryadmin.offline }}</span>{% ENDIF %}
                    </td>
                    <td>
                        <a class="button small" href="{{ ROUTER.generate("telemetryadmin-installation-plugins", ["installationId" => inst.installation_id]) }}">{{ Lang.telemetryadmin.view-plugins }}</a>
                    </td>
                </tr>
                {% ENDFOR %}
            {% ELSE %}
                <tr><td colspan="12"><em>{{ Lang.telemetryadmin.no-installations }}</em></td></tr>
            {% ENDIF %}
        </tbody>
    </table>
    </div>
</section>

