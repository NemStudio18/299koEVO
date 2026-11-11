<section class="module-card">
    <header>{{ Lang.telemetryadmin.sync-logs-title }}</header>

    <form method="get" class="filters" style="display:flex;flex-wrap:wrap;gap:0.5rem;margin:1rem 0;">
        <input type="hidden" name="page" value="1"/>
        <input type="text" name="installation_id" placeholder="installation_id" value="{{ filters.installation_id }}"/>
        <input type="text" name="sync_type" placeholder="sync_type" value="{{ filters.sync_type }}"/>
        <select name="success">
            <option value="">{{ Lang.telemetryadmin.all }}</option>
            <option value="1" {% IF filters.success == 1 %}selected{% ENDIF %}>{{ Lang.telemetryadmin.success }}</option>
            <option value="0" {% IF filters.success == 0 %}selected{% ENDIF %}>{{ Lang.telemetryadmin.failed }}</option>
        </select>
        <input type="text" name="from" placeholder="from (YYYY-MM-DD)" value="{{ filters.from }}"/>
        <input type="text" name="to" placeholder="to (YYYY-MM-DD)" value="{{ filters.to }}"/>
        <button class="button" type="submit">{{ Lang.telemetryadmin.filter }}</button>
        <a class="button ghost" href="{{ ROUTER.generate("telemetryadmin-sync-logs") }}">{{ Lang.telemetryadmin.reset }}</a>
    </form>

    <div style="overflow:auto;">
    <table class="table">
        <thead>
            <tr>
                <th>{{ Lang.telemetryadmin.installation }}</th>
                <th>{{ Lang.telemetryadmin.sync-type }}</th>
                <th>{{ Lang.telemetryadmin.level }}</th>
                <th>{{ Lang.telemetryadmin.success }}</th>
                <th>{{ Lang.telemetryadmin.synced-at }}</th>
                <th>{{ Lang.telemetryadmin.error }}</th>
            </tr>
        </thead>
        <tbody>
            {% IF logs %}
                {% FOR log IN logs %}
                <tr>
                    <td>{{ log.installation_id }}</td>
                    <td>{{ log.sync_type }}</td>
                    <td>{{ log.level }}</td>
                    <td>{% IF log.success %}<span class="tag success">{{ Lang.telemetryadmin.success }}</span>{% ELSE %}<span class="tag alert">{{ Lang.telemetryadmin.failed }}</span>{% ENDIF %}</td>
                    <td>{{ log.synced_at }}</td>
                    <td>{{ log.error_message }}</td>
                </tr>
                {% ENDFOR %}
            {% ELSE %}
                <tr><td colspan="6"><em>{{ Lang.telemetryadmin.no-logs }}</em></td></tr>
            {% ENDIF %}
        </tbody>
    </table>
    </div>
</section>

