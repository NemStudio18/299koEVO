<section>
    <header>{{ Lang.newsletter-admin.list-title }}</header>
    
    <p><strong>{{ Lang.newsletter-admin.total }}:</strong> {{ subscribersCount }} | 
       <strong>{{ Lang.newsletter-admin.active }}:</strong> {{ activeCount }}</p>
    
    <table>
        <thead>
            <tr>
                <th>{{ Lang.newsletter-admin.email }}</th>
                <th>{{ Lang.newsletter-admin.date }}</th>
                <th>{{ Lang.newsletter-admin.status }}</th>
                <th>{{ Lang.newsletter-admin.delete }}</th>
            </tr>
        </thead>
        <tbody>
            {% if count(subscribers) > 0 %}
                {% for subscriber in subscribers %}
                    <tr>
                        <td>{{ subscriber.email }}</td>
                        <td>{{ subscriber.date }}</td>
                        <td>
                            {% if subscriber.active %}
                                <span class="button small success">{{ Lang.newsletter-admin.active-status }}</span>
                            {% else %}
                                <span class="button small alert">{{ Lang.newsletter-admin.inactive-status }}</span>
                            {% endif %}
                        </td>
                        <td>
                            <form method="post" action="{ ROUTER.generate("newsletter-admin-delete", ["id" => subscriber.id]) }" onsubmit="return confirm('{{ Lang.newsletter-admin.delete-confirm }}');">
                                {{ SHOW.tokenField }}
                                <button type="submit" class="button alert small">{{ Lang.newsletter-admin.delete }}</button>
                            </form>
                        </td>
                    </tr>
                {% endfor %}
            {% else %}
                <tr>
                    <td colspan="4" class="p-large text-center">
                        <p>{{ Lang.newsletter-admin.no-subscribers }}</p>
                    </td>
                </tr>
            {% endif %}
        </tbody>
    </table>
    
    <div class="mt-large">
        <h3>{{ Lang.newsletter-admin.send-email }}</h3>
        <form method="post" action="{ ROUTER.generate("newsletter-admin-send") }">
            {{ SHOW.tokenField() }}
            
            <label for="subject">{{ Lang.newsletter-admin.send-subject }}</label>
            <input type="text" name="subject" id="subject" required />
            
            <label for="content">{{ Lang.newsletter-admin.send-content }}</label>
            <textarea name="content" id="content" rows="10" class="editor" required></textarea>
            
            <button type="submit" class="button">{{ Lang.newsletter-admin.send-button }}</button>
        </form>
    </div>
</section>

