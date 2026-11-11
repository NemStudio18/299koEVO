<section class="module-card">
    <header>{{ Lang.telemetryadmin.installation-plugins-title }}</header>

    <p><a class="button ghost" href="{{ backUrl }}">{{ Lang.telemetryadmin.back-to-list }}</a></p>

    <table class="table">
        <thead>
            <tr>
                <th>{{ Lang.telemetryadmin.plugin }}</th>
                <th>{{ Lang.telemetryadmin.status }}</th>
                <th>{{ Lang.telemetryadmin.version }}</th>
                <th>{{ Lang.telemetryadmin.synced-at }}</th>
            </tr>
        </thead>
        <tbody>
            {% IF plugins %}
                {% FOR p IN plugins %}
                <tr>
                    <td>{{ p.plugin_slug }}</td>
                    <td>{% IF p.is_active %}<span class="tag success">{{ Lang.telemetryadmin.active }}</span>{% ELSE %}<span class="tag">{{ Lang.telemetryadmin.inactive }}</span>{% ENDIF %}</td>
                    <td>{{ p.version }}</td>
                    <td>{{ p.synced_at }}</td>
                </tr>
                {% ENDFOR %}
            {% ELSE %}
                <tr><td colspan="4"><em>{{ Lang.telemetryadmin.no-plugins }}</em></td></tr>
            {% ENDIF %}
        </tbody>
    </table>
</section>










