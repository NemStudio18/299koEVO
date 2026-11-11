<section class="forum-admin-reports">
    <header>
        <h1>Signalements</h1>
        <p>Liste des sujets et messages signalés par les utilisateurs.</p>
    </header>

    {% IF reports %}
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Contenu signalé</th>
                <th>Signalé par</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% FOR report IN reports %}
            <tr>
                <td>{{report.id}}</td>
                <td>
                    {% IF report.report_type == "topic" %}
                        <span class="badge">Sujet</span>
                    {% ELSE %}
                        <span class="badge">Message</span>
                    {% ENDIF %}
                </td>
                <td>
                    {% IF report.report_type == "topic" %}
                        {% IF report.topic_title %}
                            <strong><a href="/forum/topic/{{report.topic_id}}">{{report.topic_title}}</a></strong><br>
                        {% ELSE %}
                            <strong>Sujet #{{report.topic_id}}</strong><br>
                        {% ENDIF %}
                        <small>ID {{report.topic_id}}</small>
                    {% ELSE %}
                        {% IF report.post_content %}
                            <div class="max-width-300">
                                {{report.post_content}}
                            </div>
                        {% ELSE %}
                            Message #{{report.post_id}}
                        {% ENDIF %}
                        <br><small>ID {{report.post_id}}</small>
                    {% ENDIF %}
                </td>
                <td>{{report.reporter_name}}</td>
                <td>{{report.created_at}}</td>
                <td>
                    {% IF report.report_type == "topic" %}
                        <a href="/forum/topic/{{report.topic_id}}" class="button small" target="_blank">Voir</a>
                    {% ELSE %}
                        <a href="/forum/topic/{{report.topic_id}}#post-{{report.post_id}}" class="button small" target="_blank">Voir</a>
                    {% ENDIF %}
                </td>
            </tr>
            {% ENDFOR %}
        </tbody>
    </table>
    {% ELSE %}
    <p>Aucun signalement.</p>
    {% ENDIF %}
</section>
