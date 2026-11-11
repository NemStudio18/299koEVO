<section class="module-card">
    <header>
        <i class="fa-solid fa-list"></i>
        Mes contributions
    </header>

    <div class="contributions-stats">
        <p class="stat-number">Total : <strong>{{ totalContributions }}</strong> contribution{% if totalContributions > 1 %}s{% endif %}</p>
    </div>

    {% if proposals %}
    <div class="table-responsive">
        <h3>Mes propositions de nouveaux POI</h3>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Adresse</th>
                    <th>Statut</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                {% FOR proposal IN proposals %}
                <tr>
                    <td><strong>{{ proposal.name }}</strong></td>
                    <td>{{ proposal.category }}</td>
                    <td>
                        {% if proposal.housenumber %}{{ proposal.housenumber }} {% endif %}
                        {% if proposal.street %}{{ proposal.street }}{% endif %}
                        {% if proposal.postcode or proposal.city %}
                            <br>{{ proposal.postcode }} {{ proposal.city }}
                        {% endif %}
                    </td>
                    <td>
                        {% if proposal.status == "pending" %}
                            <span class="badge warning">En attente</span>
                        {% elseif proposal.status == "approved" %}
                            <span class="badge success">Approuvé</span>
                        {% else %}
                            <span class="badge alert">Rejeté</span>
                        {% endif %}
                    </td>
                    <td>
                        {% if proposal.created_at %}
                            {{ Util.getDateHour(proposal.created_at) }}
                        {% else %}
                            -
                        {% endif %}
                    </td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>
    </div>
    {% endif %}

    {% if suggestions %}
    <div class="table-responsive">
        <h3>Mes suggestions de corrections</h3>
        <table>
            <thead>
                <tr>
                    <th>POI</th>
                    <th>Modifications</th>
                    <th>Statut</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                {% FOR poiGroup IN suggestions %}
                <tr>
                    <td>
                        {% if poiGroup.detail_url %}
                        <a href="{{ poiGroup.detail_url }}">{{ poiGroup.poi_name }}</a>
                        {% else %}
                        {{ poiGroup.poi_name }}
                        {% endif %}
                    </td>
                    <td>
                        {% FOR suggestion IN poiGroup.suggestions %}
                            <div><strong>{{ suggestion.field_name }}</strong>: 
                                {% if suggestion.old_value and suggestion.old_value != "" %}{{ suggestion.old_value }}{% else %}Non renseigné{% endif %} 
                                → <strong>{{ suggestion.new_value }}</strong>
                                <span style="margin-left: 10px;">
                                    {% if suggestion.status == "pending" %}
                                        <span class="badge warning">En attente</span>
                                    {% elseif suggestion.status == "approved" %}
                                        <span class="badge success">Approuvé</span>
                                    {% else %}
                                        <span class="badge alert">Rejeté</span>
                                    {% endif %}
                                </span>
                            </div>
                        {% ENDFOR %}
                    </td>
                    <td>
                        {% FOR suggestion IN poiGroup.suggestions %}
                            {% if suggestion.status == "pending" %}
                                <span class="badge warning">En attente</span>
                            {% elseif suggestion.status == "approved" %}
                                <span class="badge success">Approuvé</span>
                            {% else %}
                                <span class="badge alert">Rejeté</span>
                            {% endif %}
                            <br>
                        {% ENDFOR %}
                    </td>
                    <td>
                        {% if poiGroup.created_at %}
                            {{ Util.getDateHour(poiGroup.created_at) }}
                        {% else %}
                            -
                        {% endif %}
                    </td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>
    </div>
    {% endif %}

    {% if not proposals and not suggestions %}
    <p>Vous n'avez pas encore de contributions.</p>
    <p><a href="{{ ROUTER.generate("poi-edit-contribute") }}" class="button">Proposer un nouveau POI</a></p>
    {% endif %}
</section>
