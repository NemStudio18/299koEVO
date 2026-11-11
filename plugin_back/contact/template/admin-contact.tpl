
<div class="tabs-container">
    <ul class="tabs-header">
        <li class="default-tab"><i class="fa-solid fa-message"></i> Messages Reçus</li>
        <li><i class="fa-solid fa-gear"></i> Configuration</li>
        <li><i class="fa-solid fa-at"></i> Emails Collectés</li>
    </ul>
    <ul class="tabs">
        <!-- Messages Tab -->
        <li class="tab">
            <header>Messages Reçus</header>
            {% if messages %}
                <div class="table-responsive">
                    <table class="contact-admin-table table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>De</th>
                                <th>Sujet</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% for msg in messages %}
                            <tr>
                                <td class="white-space-nowrap">{{ Util.getFormattedDate(msg.date, 'd/m/Y H:i') }}</td>
                                <td>
                                    <strong>{{ msg.firstname }} {{ msg.name }}</strong><br>
                                    <a href="mailto:{{ msg.email }}">{{ msg.email }}</a>
                                </td>
                                <td>{{ msg.topic }}</td>
                                <td>
                                    <div class="message-content-scroll">
                                        {{ msg.message }}
                                    </div>
                                </td>
                            </tr>
                            {% endfor %}
                        </tbody>
                    </table>
                </div>
                <p>
                    <a href="{ ROUTER.generate("contact-empty-messages", ["token" => token]) }" class="button alert"
                       onclick="return(confirm('Voulez-vous vraiment supprimer tous les messages reçus ?'));">Supprimer l'historique des messages</a>
                </p>
            {% else %}
                <p>Aucun message reçu pour le moment.</p>
            {% endif %}
        </li>

        <!-- Configuration Tab -->
        <li class="tab">
            <form method="post" action="{ ROUTER.generate("contact-saveConfig") }">
                {{SHOW.tokenField}}
                
                <div class="tabs-container">
                    <ul class="tabs-header">
                        <li class="default-tab">Général</li>
                        <li>Contenu</li>
                    </ul>
                    <ul class="tabs">
                        <!-- Général -->
                        <li class="tab">
                            <p>
                                <label for="copy">{{Lang.contact.copy_recipient}}</label><br>
                                <input type="email" name="copy" id="copy" value="{{ runPlugin.getConfigVal("copy") }}" />
                            </p>
                            <p>
                                <label for="label">{{Lang.contact.page_title}}</label><br>
                                <input type="text" name="label" id="label" value="{{ runPlugin.getConfigVal("label") }}" required />
                            </p>
                            <p>
                                <label for="selectedUser">{{Lang.contact.select-user}}</label><br>
                                <select name="selectedUser" id="selectedUser">
                                {% for user in contactUsers %}
                                     <option value='{{user.id}}'
                                        {% if contactSelected == user.id %} selected {% endif %}
                                    >{{user.email}}</option>
                                {% endfor %}
                                </select>
                            </p>
                            <p>
                                <label for="acceptation">{{Lang.contact.form_acceptance_text}}</label><br>
                                <textarea name="acceptation" id="acceptation" rows="3">{{ runPlugin.getConfigVal("acceptation") }}</textarea>
                            </p>
                            <p>
                                <label for="showInFooter">
                                    <input type="checkbox" name="showInFooter" id="showInFooter" value="1" {% if showInFooter %}checked{% endif %}>
                                    Afficher le lien dans le pied de page
                                </label>
                            </p>
                            <p>
                                <label for="topics">Sujets de contact (un par ligne)</label><br>
                                <textarea name="topics" id="topics" rows="5">{{topics}}</textarea>
                                <small>Ces sujets apparaîtront dans un menu déroulant obligatoire sur le formulaire.</small>
                            </p>
                        </li>
                        <!-- Contenu -->
                        <li class="tab">
                            <p>
                                <label for="content1">{{Lang.contact.before_form}}</label><br>
                                <textarea name="content1" id="content1" class="editor" rows="10">{{ runPlugin.getConfigVal("content1") }}</textarea>
                            </p>
                            <p>
                                <label for="content2">{{Lang.contact.after_form}}</label><br>
                                <textarea name="content2" id="content2" class="editor" rows="10">{{ runPlugin.getConfigVal("content2") }}</textarea>
                            </p>
                        </li>
                    </ul>
                </div>

                <p style="margin-top: 20px;"><button type="submit" class="button primary">{{Lang.save}}</button></p>
            </form>
        </li>

        <!-- Emails Tab -->
        <li class="tab">
            <header>{{Lang.contact.collected_email_addresses}}</header>
            <p>
                <label for="savedMails">{{Lang.contact.emails_collected}}</label>
                <textarea readonly="readonly" id="savedMails" style="height: 300px;">{{emails}}</textarea>
            </p>
            <a href="{ ROUTER.generate("contact-empty-mails", ["token" => token]) }" class="button alert"
               onclick="return(confirm('{{Lang.contact.confirm_empty_mail_base}}'));">{{Lang.contact.delete_base}}</a> 
        </li>
    </ul>
</div>
