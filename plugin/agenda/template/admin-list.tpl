<div class="tabs-container agenda-admin-tabs">
    <ul class="tabs-header">
        <li class="default-tab">
            <i class="fa-solid fa-calendar-days"></i> Liste des évènements
        </li>
        <li>
            <i class="fa-solid fa-gear"></i> Paramètres
        </li>
    </ul>
    <ul class="tabs">
        <!-- Tab 1: Events List -->
        <li class="tab">
            <section class="agenda-admin-list">
                <header class="mb-medium">
                    <a class="button primary" href="{ ROUTER.generate("admin-agenda-events-add") }">
                        <i class="fa-solid fa-plus"></i> Nouvel évènement
                    </a>
                </header>

                {% IF events %}
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{Lang.agenda.title}}</th>
                            <th>Statut</th>
                            <th>{{Lang.agenda.start-date}}</th>
                            <th>{{Lang.agenda.location}}</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% FOR event IN events %}
                        {% SET eventId = event.getId() %}
                        {% SET eventStatus = event.getStatus() %}
                        <tr class="status-{{eventStatus}}">
                            <td>{{event.getId()}}</td>
                            <td><strong>{{event.getTitle()}}</strong></td>
                            <td>
                                {% IF eventStatus == "pending" %}
                                <span class="badge warning">En attente</span>
                                {% ELSE %}
                                <span class="badge success">Publié</span>
                                {% ENDIF %}
                            </td>
                            <td>{{event.getStartDate()}}</td>
                            <td>{{event.getLocation()}}</td>
                            <td>
                                <div class="button-group">
                                    {% IF eventStatus == "pending" %}
                                    <a class="button success small" href="{ ROUTER.generate("admin-agenda-events-approve", ["id" => eventId]) }">Approuver</a>
                                    {% ENDIF %}
                                    <a title="Modifier" class="button small" href="{ ROUTER.generate("admin-agenda-events-edit", ["id" => eventId]) }">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a title="Supprimer" class="button alert small" href="{ ROUTER.generate("admin-agenda-events-delete", ["id" => eventId]) }" onclick="return confirm('Supprimer cet évènement ?');">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        {% ENDFOR %}
                    </tbody>
                </table>
                {% ELSE %}
                <p>Aucun évènement enregistré pour le moment.</p>
                {% ENDIF %}
            </section>
        </li>
        
        <!-- Tab 2: Configuration -->
        <li class="tab">
            <section class="content">
                <form method="post" action="{ ROUTER.generate("admin-agenda-save-config") }">
                    <input type="hidden" name="_csrf" value="{{token}}">
                    
                    <div class='form'>
                        <label for="items_per_page">Évènements par page</label><br>
                        <input type="number" name="items_per_page" id="items_per_page" value="{{ config.items_per_page ?: config.eventsPerPage ?: 20 }}" />
                    </div>

                    <div class='form'>
                        <label for="date_format">Format de date (PHP)</label><br>
                        <input type="text" name="date_format" id="date_format" value="{{ config.date_format ?: 'd/m/Y' }}" placeholder="d/m/Y" />
                        <small>Exemple: d/m/Y pour 31/12/2024, Y-m-d pour 2024-12-31</small>
                    </div>

                    <div class='form'>
                        <label for="notify_admin_email">Email de notification admin</label><br>
                        <input type="email" name="notify_admin_email" id="notify_admin_email" value="{{ config.notify_admin_email }}" placeholder="admin@example.com" />
                        <small>Laissez vide pour désactiver les alertes email lors de nouvelles propositions d'évènements.</small>
                    </div>

                    <div class='form mt-medium'>
                        <button type="submit" class="button primary">Enregistrer les paramètres</button>
                    </div>
                </form>
            </section>
        </li>
    </ul>
</div>

