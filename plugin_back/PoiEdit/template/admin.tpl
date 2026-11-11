<section class="module-card">
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <span>
            <i class="fa-solid fa-pen-to-square"></i>
            Gestion des éditions POI
        </span>
        <a href="{{ ROUTER.generate('admin-poi-edit-create') }}" class="button success" style="font-size: 0.9em;">
            <i class="fa-solid fa-plus"></i> Créer un POI
        </a>
    </header>

    <div class="tabs-container">
        <ul class="tabs-header">
            <li class="default-tab">
                <i class="fa-solid fa-chart-line"></i> Tableau de bord
            </li>
            <li {% if suggestionsCount > 0 %}style="color: #f59e0b;"{% endif %}>
                <i class="fa-solid fa-lightbulb"></i> Suggestions
                {% if suggestionsCount > 0 %}
                    <span style="background: #f59e0b; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;">{{ suggestionsCount }}</span>
                {% endif %}
            </li>
            <li {% if proposalsCount > 0 %}style="color: #f59e0b;"{% endif %}>
                <i class="fa-solid fa-plus-circle"></i> Propositions
                {% if proposalsCount > 0 %}
                    <span style="background: #f59e0b; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;">{{ proposalsCount }}</span>
                {% endif %}
            </li>
            <li>
                <i class="fa-solid fa-tags"></i> Types & Catégories
            </li>
            <li>
                <i class="fa-solid fa-sliders-h"></i> Attributs
            </li>
            <li>
                <i class="fa-solid fa-cog"></i> Configuration
            </li>
        </ul>

        <ul class="tabs">
            <!-- Onglet Tableau de bord -->
            <li class="tab">
                <div class="admin-dashboard">
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <h3>Suggestions</h3>
                            <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                <div>
                                    <p class="stat-number" style="color: #f59e0b;">{% if suggestionsStats.pending_count %}{{ suggestionsStats.pending_count }}{% else %}0{% endif %}</p>
                                    <small>En attente</small>
                                </div>
                                <div>
                                    <p class="stat-number" style="color: #10b981;">{% if suggestionsStats.approved_count %}{{ suggestionsStats.approved_count }}{% else %}0{% endif %}</p>
                                    <small>Validées</small>
                                </div>
                                <div>
                                    <p class="stat-number" style="color: #ef4444;">{% if suggestionsStats.rejected_count %}{{ suggestionsStats.rejected_count }}{% else %}0{% endif %}</p>
                                    <small>Rejetées</small>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <h3>Propositions</h3>
                            <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                <div>
                                    <p class="stat-number" style="color: #f59e0b;">{% if proposalsStats.pending_count %}{{ proposalsStats.pending_count }}{% else %}0{% endif %}</p>
                                    <small>En attente</small>
                                </div>
                                <div>
                                    <p class="stat-number" style="color: #10b981;">{% if proposalsStats.approved_count %}{{ proposalsStats.approved_count }}{% else %}0{% endif %}</p>
                                    <small>Validées</small>
                                </div>
                                <div>
                                    <p class="stat-number" style="color: #ef4444;">{% if proposalsStats.rejected_count %}{{ proposalsStats.rejected_count }}{% else %}0{% endif %}</p>
                                    <small>Rejetées</small>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <h3>Contributeurs</h3>
                            <p class="stat-number">{{ contributorsCount }}</p>
                            <small>Contributeurs actifs</small>
                        </div>
                    </div>

                    {% if pendingSuggestions %}
                    <div class="table-responsive">
                        <h3>Dernières suggestions</h3>
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

                    {% if pendingProposals %}
                    <div class="table-responsive">
                        <h3>Dernières propositions</h3>
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
                                    <td>{% if proposal.username %}{{ proposal.username }}{% elseif proposal.email %}{{ proposal.email }}{% else %}-{% endif %}</td>
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
                    {% endif %}
                </div>
            </li>

            <!-- Onglet Suggestions -->
            <li class="tab">
                {% if allSuggestions %}
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
                            {% FOR poiGroup IN allSuggestions %}
                            <tr>
                                <td>
                                    <a href="{{ ROUTER.generate("search-detail", ["type" => "poi", "id" => poiGroup.poi_id]) }}">
                                        {{ poiGroup.poi_name }}
                                    </a>
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
            </li>

            <!-- Onglet Propositions -->
            <li class="tab">
                {% if allProposals %}
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
                            {% FOR proposal IN allProposals %}
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
                                <td>{% if proposal.username %}{{ proposal.username }}{% elseif proposal.email %}{{ proposal.email }}{% else %}-{% endif %}</td>
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
            </li>

            <!-- Onglet Types & Catégories -->
            <li class="tab">
                <div style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>Gestion des types et catégories</h2>
                        <div style="display: flex; gap: 10px;">
                            <a href="{{ ROUTER.generate("admin-poi-edit-types") }}" class="button primary">
                                <i class="fa-solid fa-list"></i> Voir tous les types
                            </a>
                            <a href="{{ ROUTER.generate("admin-poi-edit-type-proposals") }}" class="button secondary">
                                <i class="fa-solid fa-lightbulb"></i> Propositions de types
                            </a>
                        </div>
                    </div>
                    <p style="color: #666; margin-bottom: 20px;">
                        Gérez les types métier et leurs catégories. Les types personnalisés permettent d'étendre la liste des 14 types standards.
                    </p>
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <strong>Types standards :</strong> restauration, commerce, service, artisanat, sante, education, administration, transport, culture, sport, tourisme, hebergement, nature, autre
                    </div>
                </div>
            </li>

            <!-- Onglet Attributs -->
            <li class="tab">
                <div style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>Gestion des attributs POI</h2>
                        <div style="display: flex; gap: 10px;">
                            <a href="{{ ROUTER.generate("admin-poi-edit-features") }}" class="button primary">
                                <i class="fa-solid fa-list"></i> Voir tous les attributs
                            </a>
                        </div>
                    </div>
                    <p style="color: #666; margin-bottom: 20px;">
                        Gérez les métadonnées des attributs (wheelchair, outdoor_seating, etc.) pour l'affichage et le filtrage dans l'interface publique.
                    </p>
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <strong>💡 Astuce :</strong> Vous pouvez exécuter <code>php bin/create_poi_features_table.php</code> pour insérer les attributs OSM courants (wheelchair, outdoor_seating, etc.).
                    </div>
                </div>
            </li>

            <!-- Onglet Configuration -->
            <li class="tab">
                <form method="post" action="{{ ROUTER.generate("admin-poi-edit-config-save") }}">
                    {{ SHOW.tokenField }}
                    <p>
                        <label for="helpUrl">URL "Comment contribuer"</label>
                        <input type="url" id="helpUrl" name="helpUrl" value="{{ helpUrl }}" placeholder="https://example.com/comment-contribuer" style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
                        <br><small>URL vers la page d'aide expliquant comment contribuer. Cette URL sera utilisée dans le menu de navigation.</small>
                    </p>
                    <p>
                        <button type="submit" class="button success">Enregistrer</button>
                    </p>
                </form>
            </li>
        </ul>
    </div>
</section>
