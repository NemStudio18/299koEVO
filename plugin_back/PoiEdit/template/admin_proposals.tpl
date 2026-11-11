<section class="module-card">
    <header>
        <i class="fa-solid fa-plus-circle"></i>
        Propositions de nouveaux POI en attente
        <a href="{{ ROUTER.generate("admin-poi-edit") }}" class="button small" style="float:right; margin-top:-5px;">Retour au tableau de bord</a>
    </header>

    {% if pendingProposals and pendingProposals|length > 0 %}
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Adresse</th>
                    <th>Propriétaire</th>
                    <th>Proposé par</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {% FOR proposal IN pendingProposals %}
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
                        {% if proposal.is_owner %}
                            <span class="badge success">Oui</span>
                        {% else %}
                            <span class="badge">Non</span>
                        {% endif %}
                    </td>
                    <td>{{ proposal.username|default:proposal.email }}</td>
                    <td>
                        {% if proposal.created_at %}
                            {{ Util.getDateHour(proposal.created_at) }}
                        {% else %}
                            -
                        {% endif %}
                    </td>
                    <td>
                        <form method="post" action="{{ ROUTER.generate("admin-poi-edit-proposal-decide") }}" style="display:inline;">
                            <input type="hidden" name="proposal_id" value="{{ proposal.id }}">
                            <button type="submit" name="decision" value="approved" class="button small success" onclick="return confirm('Approuver cette proposition et créer le POI ?');">
                                <i class="fa-solid fa-check"></i> Approuver
                            </button>
                            <button type="submit" name="decision" value="rejected" class="button small alert" onclick="return confirm('Rejeter cette proposition ?');">
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
    <p>Aucune proposition en attente.</p>
    {% endif %}
</section>

