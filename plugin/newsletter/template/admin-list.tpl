<div class="tabs-container" data-tab-key="newsletter">
    <ul class="tabs-header">
        <li data-tab="subscribers" class="default-tab">
            <i class="fa-solid fa-users"></i> {{ Lang.newsletter-admin.subscribers }}
        </li>
        <li data-tab="send">
            <i class="fa-solid fa-paper-plane"></i> {{ Lang.newsletter-admin.send-email }}
        </li>
        <li data-tab="config">
            <i class="fa-solid fa-gear"></i> {{ Lang.core-settings }}
        </li>
    </ul>
    <ul class="tabs">
        <!-- Tab 1: Subscribers -->
        <li class="tab">
            <section class="content">
                <header>
                    <h3>{{ Lang.newsletter-admin.list-title }}</h3>
                </header>

                <p><strong>{{ Lang.newsletter-admin.total }}:</strong> {{ subscribersCount }} | 
                   <strong>{{ Lang.newsletter-admin.active }}:</strong> {{ activeCount }}</p>

                <table class="table">
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
            </section>
        </li>

        <!-- Tab 2: Send Email -->
        <li class="tab">
            <section class="content">
                <header>
                    <h3>{{ Lang.newsletter-admin.send-email }}</h3>
                </header>
                <form method="post" action="{ ROUTER.generate("newsletter-admin-send") }">
                    {{ SHOW.tokenField() }}

                    <p>
                        <label for="subject">{{ Lang.newsletter-admin.send-subject }}</label><br>
                        <input type="text" name="subject" id="subject" required />
                    </p>

                    <p>
                        <label for="content">{{ Lang.newsletter-admin.send-content }}</label><br>
                        <textarea name="content" id="content" rows="10" class="editor" required></textarea>
                    </p>

                    <p>
                        <button type="submit" class="button primary"><i class="fa-solid fa-paper-plane"></i> {{ Lang.newsletter-admin.send-button }}</button>
                    </p>
                </form>
            </section>
        </li>

        <!-- Tab 3: Settings -->
        <li class="tab">
            {% INCLUDE PLUGINS ~ "newsletter/template/param.tpl" %}
        </li>
    </ul>
</div>

