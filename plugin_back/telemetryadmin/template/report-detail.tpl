<section class="module-card">
    <header>{{ Lang.telemetryadmin.report-detail-title }}</header>
    
    <div style="margin-bottom: 1rem;">
        <a href="{{ backUrl }}" class="button">
            <i class="fa-solid fa-arrow-left"></i> {{ Lang.telemetryadmin.back-to-list }}
        </a>
    </div>

    {% if report.error %}
        <div class="msg error">
            <p>{{ report.error }}</p>
        </div>
    {% else %}
        <div class="report-detail">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div>
                    <h3>{{ Lang.telemetryadmin.report-info }}</h3>
                    <table class="info-table">
                        <tr>
                            <th>{{ Lang.telemetryadmin.report-id }}:</th>
                            <td><code>{{ report.report_id }}</code></td>
                        </tr>
                        <tr>
                            <th>{{ Lang.telemetryadmin.type }}:</th>
                            <td>
                                <span class="badge badge-{{ report.type }}">
                                    {% if report.type == "bug" %}{{ Lang.telemetryadmin.type-bug }}
                                    {% elseif report.type == "feature" %}{{ Lang.telemetryadmin.type-feature }}
                                    {% elseif report.type == "question" %}{{ Lang.telemetryadmin.type-question }}
                                    {% else %}{{ Lang.telemetryadmin.type-other }}
                                    {% endif %}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ Lang.telemetryadmin.status }}:</th>
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
                        </tr>
                        <tr>
                            <th>{{ Lang.telemetryadmin.created-at }}:</th>
                            <td>{{ report.created_at }}</td>
                        </tr>
                        {% if report.email %}
                        <tr>
                            <th>{{ Lang.telemetryadmin.email }}:</th>
                            <td><a href="mailto:{{ report.email }}">{{ report.email }}</a></td>
                        </tr>
                        {% endif %}
                        {% if report.url %}
                        <tr>
                            <th>{{ Lang.telemetryadmin.url }}:</th>
                            <td><a href="{{ report.url }}" target="_blank">{{ report.url }}</a></td>
                        </tr>
                        {% endif %}
                        {% if report.plugin %}
                        <tr>
                            <th>{{ Lang.telemetryadmin.plugin }}:</th>
                            <td>{{ report.plugin }}</td>
                        </tr>
                        {% endif %}
                    </table>
                </div>
                
                <div>
                    <h3>{{ Lang.telemetryadmin.technical-info }}</h3>
                    <table class="info-table">
                        <tr>
                            <th>{{ Lang.telemetryadmin.cms-version }}:</th>
                            <td>{{ report.cms_version }}</td>
                        </tr>
                        <tr>
                            <th>{{ Lang.telemetryadmin.php-version }}:</th>
                            <td>{{ report.php_version }}</td>
                        </tr>
                        {% if report.installation_url %}
                        <tr>
                            <th>{{ Lang.telemetryadmin.installation-url }}:</th>
                            <td><a href="{{ report.installation_url }}" target="_blank">{{ report.installation_url }}</a></td>
                        </tr>
                        {% endif %}
                    </table>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <h3>{{ Lang.telemetryadmin.title }}:</h3>
                <p style="font-size: 1.2em; font-weight: 600;">{{ report.title }}</p>
            </div>

            <div style="margin-bottom: 2rem;">
                <h3>{{ Lang.telemetryadmin.report-description }}:</h3>
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; white-space: pre-wrap;">{{ report.description }}</div>
            </div>

            {% if report.screenshot_path %}
            <div style="margin-bottom: 2rem;">
                <h3>{{ Lang.telemetryadmin.screenshot }}:</h3>
                <img src="{{ marketplaceUrl }}/storage/reports/{{ report.screenshot_path }}" alt="Screenshot" style="max-width: 100%; border: 1px solid #dee2e6; border-radius: 4px;" />
            </div>
            {% endif %}

            {% if report.status_history %}
            <div style="margin-bottom: 2rem;">
                <h3>{{ Lang.telemetryadmin.status-history }}:</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ Lang.telemetryadmin.status }}</th>
                            <th>{{ Lang.telemetryadmin.comment }}</th>
                            <th>{{ Lang.telemetryadmin.date }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% FOR history IN report.status_history %}
                        <tr>
                            <td>
                                <span class="badge badge-status-{{ history.status }}">
                                    {{ history.status }}
                                </span>
                            </td>
                            <td>{{ history.comment }}</td>
                            <td>{{ history.changed_at }}</td>
                        </tr>
                        {% ENDFOR %}
                    </tbody>
                </table>
            </div>
            {% endif %}

            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #dee2e6;">
                <h3>{{ Lang.telemetryadmin.update-status }}</h3>
                <form method="post" action="{{ updateStatusUrl }}">
                    {{ SHOW.tokenField }}
                    <p>
                        <label for="status">{{ Lang.telemetryadmin.new-status }}:</label>
                        <select name="status" id="status" required>
                            <option value="new" {% if report.status == "new" %}selected{% endif %}>{{ Lang.telemetryadmin.status-new }}</option>
                            <option value="in_progress" {% if report.status == "in_progress" %}selected{% endif %}>{{ Lang.telemetryadmin.status-in-progress }}</option>
                            <option value="resolved" {% if report.status == "resolved" %}selected{% endif %}>{{ Lang.telemetryadmin.status-resolved }}</option>
                            <option value="closed" {% if report.status == "closed" %}selected{% endif %}>{{ Lang.telemetryadmin.status-closed }}</option>
                            <option value="rejected" {% if report.status == "rejected" %}selected{% endif %}>{{ Lang.telemetryadmin.status-rejected }}</option>
                        </select>
                    </p>
                    <p>
                        <label for="comment">{{ Lang.telemetryadmin.comment }} ({{ Lang.telemetryadmin.optional }}):</label>
                        <textarea name="comment" id="comment" rows="3" style="width: 100%;"></textarea>
                    </p>
                    <p>
                        <button type="submit" class="button success">{{ Lang.telemetryadmin.update-status }}</button>
                    </p>
                </form>
            </div>
        </div>
    {% endif %}
</section>

<style>
.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table th {
    text-align: left;
    padding: 0.5rem 1rem 0.5rem 0;
    font-weight: 600;
    color: #495057;
    width: 40%;
}

.info-table td {
    padding: 0.5rem 0;
}

.report-detail h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: #1a3a5f;
}
</style>

