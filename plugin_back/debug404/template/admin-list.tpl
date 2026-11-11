<section class="debug404-admin-list">
    <header class="admin-actions-header">
        <div>
            <h1>Debug 404 Logger</h1>
            <p>Historique des erreurs 404 détectées sur le site</p>
        </div>
        <div class="header-info">
            <span class="badge secondary">{{ logs|@count }} entrées</span>
        </div>
    </header>

    <div class="admin-content-card">
        {% IF logs %}
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>URL demandée</th>
                        <th>Plugin probable</th>
                        <th>Méthode</th>
                        <th>Referer</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    {% FOR log IN logs %}
                    <tr>
                        <td nowrap><small>{{ log.time }}</small></td>
                        <td>
                            <div style="max-width: 400px; overflow-wrap: break-word;">
                                <strong>{{ log.uri }}</strong>
                            </div>
                        </td>
                        <td>
                            <span class="badge {% IF log.plugin|@str_contains:'Plugin exists' %}warning{% ELSE %}info{% ENDIF %}">
                                {{ log.plugin }}
                            </span>
                        </td>
                        <td><small>{{ log.method }}</small></td>
                        <td>
                            <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ log.referer }}">
                                <small>{{ log.referer }}</small>
                            </div>
                        </td>
                        <td><small>{{ log.ip }}</small></td>
                    </tr>
                    {% ENDFOR %}
                </tbody>
            </table>
        </div>
        {% ELSE %}
        <div class="empty-state">
            <i class="fa-solid fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;"></i>
            <p>Aucune erreur 404 enregistrée pour le moment.</p>
        </div>
        {% ENDIF %}
    </div>

    <style>
        .admin-content-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-top: 1rem;
        }
        .table-container {
            overflow-x: auto;
        }
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge.info { background: #e3f2fd; color: #1976d2; }
        .badge.warning { background: #fff3e0; color: #f57c00; }
        .badge.secondary { background: #f5f5f5; color: #616161; }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
    </style>
</section>
