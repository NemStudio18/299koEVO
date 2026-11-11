<section class="module-card">
    <header>
        <i class="fa-solid fa-lightbulb"></i>
        Suggestions de corrections en attente
        <a href="{{ ROUTER.generate("admin-poi-edit") }}" class="button small" style="float:right; margin-top:-5px;">Retour au tableau de bord</a>
    </header>

    {% if pendingSuggestions %}
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>POI</th>
                    <th>Modifications</th>
                    <th>Proposé par</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {% FOR poiGroup IN pendingSuggestions %}
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
                            </div>
                        {% ENDFOR %}
                    </td>
                    <td>{% if poiGroup.username %}{{ poiGroup.username }}{% elseif poiGroup.email %}{{ poiGroup.email }}{% else %}-{% endif %}</td>
                    <td>
                        {% if poiGroup.created_at %}
                            {{ Util.getDateHour(poiGroup.created_at) }}
                        {% else %}
                            -
                        {% endif %}
                    </td>
                    <td>
                        <form method="post" action="{{ ROUTER.generate("admin-poi-edit-decide") }}" style="display:inline;">
                            <input type="hidden" name="poi_id" value="{{ poiGroup.poi_id }}">
                            <button type="submit" name="decision" value="approved" class="button small success" onclick="return confirm('Approuver toutes les suggestions pour ce POI ?');">
                                <i class="fa-solid fa-check"></i> Approuver
                            </button>
                            <button type="submit" name="decision" value="rejected" class="button small alert" onclick="return confirm('Rejeter toutes les suggestions pour ce POI ?');">
                                <i class="fa-solid fa-times"></i> Rejeter
                            </button>
                        </form>
                    </td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>
    </div>
    {% else %}
    <p>Aucune suggestion en attente.</p>
    {% endif %}
</section>
