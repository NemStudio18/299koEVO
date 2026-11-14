<section class="module-card">
    <header>{{ Lang.telemetryadmin.reports-title }}</header>
    
    <div style="margin-bottom: 1.5rem;">
        <form method="get" action="{{ ROUTER.generate("telemetryadmin-reports") }}" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div>
                <label for="status">{{ Lang.telemetryadmin.filter-status }}:</label>
                <select name="status" id="status">
                    <option value="">{{ Lang.telemetryadmin.all-statuses }}</option>
                    <option value="new" {% if currentStatus == "new" %}selected{% endif %}>{{ Lang.telemetryadmin.status-new }}</option>
                    <option value="in_progress" {% if currentStatus == "in_progress" %}selected{% endif %}>{{ Lang.telemetryadmin.status-in-progress }}</option>
                    <option value="resolved" {% if currentStatus == "resolved" %}selected{% endif %}>{{ Lang.telemetryadmin.status-resolved }}</option>
                    <option value="closed" {% if currentStatus == "closed" %}selected{% endif %}>{{ Lang.telemetryadmin.status-closed }}</option>
                    <option value="rejected" {% if currentStatus == "rejected" %}selected{% endif %}>{{ Lang.telemetryadmin.status-rejected }}</option>
                </select>
            </div>
            <div>
                <label for="type">{{ Lang.telemetryadmin.filter-type }}:</label>
                <select name="type" id="type">
                    <option value="">{{ Lang.telemetryadmin.all-types }}</option>
                    <option value="bug" {% if currentType == "bug" %}selected{% endif %}>{{ Lang.telemetryadmin.type-bug }}</option>
                    <option value="feature" {% if currentType == "feature" %}selected{% endif %}>{{ Lang.telemetryadmin.type-feature }}</option>
                    <option value="question" {% if currentType == "question" %}selected{% endif %}>{{ Lang.telemetryadmin.type-question }}</option>
                    <option value="other" {% if currentType == "other" %}selected{% endif %}>{{ Lang.telemetryadmin.type-other }}</option>
                </select>
            </div>
            <button type="submit" class="button">{{ Lang.telemetryadmin.filter }}</button>
        </form>
    </div>

    {% if reports %}
    <table class="table">
        <thead>
            <tr>
                <th>{{ Lang.telemetryadmin.report-id }}</th>
                <th>{{ Lang.telemetryadmin.type }}</th>
                <th>{{ Lang.telemetryadmin.title }}</th>
                <th>{{ Lang.telemetryadmin.status }}</th>
                <th>{{ Lang.telemetryadmin.created-at }}</th>
                <th>{{ Lang.telemetryadmin.actions }}</th>
            </tr>
        </thead>
        <tbody>
            {% FOR report IN reports %}
            <tr>
                <td><code style="font-size: 0.85em;">{{ report.report_id }}</code></td>
                <td>
                    <span class="badge badge-{{ report.type }}">
                        {% if report.type == "bug" %}{{ Lang.telemetryadmin.type-bug }}
                        {% elseif report.type == "feature" %}{{ Lang.telemetryadmin.type-feature }}
                        {% elseif report.type == "question" %}{{ Lang.telemetryadmin.type-question }}
                        {% else %}{{ Lang.telemetryadmin.type-other }}
                        {% endif %}
                    </span>
                </td>
                <td>
                    <a href="{{ ROUTER.generate("telemetryadmin-report-detail", ["reportId" => report.report_id]) }}">
                        {{ report.title }}
                    </a>
                </td>
                <td>
                    <span class="badge badge-status-{{ report.status }}">
                        {% if report.status == "new" %}{{ Lang.telemetryadmin.status-new }}
                        {% elseif report.status == "in_progress" %}{{ Lang.telemetryadmin.status-in-progress }}
                        {% elseif report.status == "resolved" %}{{ Lang.telemetryadmin.status-resolved }}
                        {% elseif report.status == "closed" %}{{ Lang.telemetryadmin.status-closed }}
                        {% else %}{{ Lang.telemetryadmin.status-rejected }}
                        {% endif %}
                    </span>
                </td>
                <td>{{ report.created_at }}</td>
                <td>
                    <a href="{{ ROUTER.generate("telemetryadmin-report-detail", ["reportId" => report.report_id]) }}" class="button small">
                        {{ Lang.telemetryadmin.view }}
                    </a>
                </td>
            </tr>
            {% ENDFOR %}
        </tbody>
    </table>

    {% if pagination.total_pages > 1 %}
    <div style="margin-top: 1.5rem; text-align: center;">
        {% if pagination.page > 1 %}
        <a href="{{ ROUTER.generate("telemetryadmin-reports") }}?page={{ pagination.page - 1 }}{% if currentStatus %}&status={{ currentStatus }}{% endif %}{% if currentType %}&type={{ currentType }}{% endif %}" class="button">
            {{ Lang.telemetryadmin.previous }}
        </a>
        {% endif %}
        
        <span style="margin: 0 1rem;">
            {{ Lang.telemetryadmin.page }} {{ pagination.page }} / {{ pagination.total_pages }}
        </span>
        
        {% if pagination.page < pagination.total_pages %}
        <a href="{{ ROUTER.generate("telemetryadmin-reports") }}?page={{ pagination.page + 1 }}{% if currentStatus %}&status={{ currentStatus }}{% endif %}{% if currentType %}&type={{ currentType }}{% endif %}" class="button">
            {{ Lang.telemetryadmin.next }}
        </a>
        {% endif %}
    </div>
    {% endif %}
    {% else %}
    <p>{{ Lang.telemetryadmin.no-reports }}</p>
    {% endif %}
</section>

<style>
.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 500;
}

.badge-bug { background-color: #dc3545; color: white; }
.badge-feature { background-color: #28a745; color: white; }
.badge-question { background-color: #17a2b8; color: white; }
.badge-other { background-color: #6c757d; color: white; }

.badge-status-new { background-color: #007bff; color: white; }
.badge-status-in_progress { background-color: #ffc107; color: #000; }
.badge-status-resolved { background-color: #28a745; color: white; }
.badge-status-closed { background-color: #6c757d; color: white; }
.badge-status-rejected { background-color: #dc3545; color: white; }
</style>

