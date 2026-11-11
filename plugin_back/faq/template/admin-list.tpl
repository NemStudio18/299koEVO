<section>
    <header>{{ Lang.faq-admin.list-title }}</header>
    
    <a href="{{ ROUTER.generate("faq-admin-edit") }}" class="button">{{ Lang.faq-admin.add-question }}</a>
    
    <table>
        <thead>
            <tr>
                <th>{{ Lang.faq-admin.question }}</th>
                <th>{{ Lang.faq-admin.category }}</th>
                <th>{{ Lang.faq-admin.order }}</th>
                <th>{{ Lang.faq-admin.votes }}</th>
                <th>{{ Lang.faq-admin.status }}</th>
                <th>{{ Lang.faq-admin.actions }}</th>
            </tr>
        </thead>
        <tbody>
            {% if count(questions) > 0 %}
                {% for q in questions %}
                    <tr>
                        <td><strong>{{ q.question }}</strong></td>
                        <td>{{ q.category }}</td>
                        <td>{{ q.order }}</td>
                        <td>{{ q.votes }}</td>
                        <td>
                            {% if q.active %}
                                <span class="button small success">{{ Lang.faq-admin.active }}</span>
                            {% else %}
                                <span class="button small alert">{{ Lang.faq-admin.inactive }}</span>
                            {% endif %}
                        </td>
                        <td>
                            <a href="{{ ROUTER.generate("faq-admin-edit", ["id" => q.id]) }}" class="button small">{{ Lang.edit }}</a>
                            <form method="post" action="{{ ROUTER.generate("faq-admin-delete", ["id" => q.id]) }}" onsubmit="return confirm('{{ Lang.faq-admin.delete-confirm }}');" style="display: inline;">
                                {{ SHOW.tokenField }}
                                <button type="submit" class="button alert small">{{ Lang.delete }}</button>
                            </form>
                        </td>
                    </tr>
                {% endfor %}
            {% else %}
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem;">
                        <p>{{ Lang.faq-admin.no-questions }}</p>
                    </td>
                </tr>
            {% endif %}
        </tbody>
    </table>
</section>

