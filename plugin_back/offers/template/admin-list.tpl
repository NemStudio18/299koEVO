<section class="offers-admin-list">
    <header class="admin-actions-header">
        <div>
            <h1>Gestion des offres</h1>
            <p>Créer et gérer les offres promotionnelles</p>
        </div>
        <a href="{{ addUrl }}" class="button primary">
            <i class="fa-solid fa-plus"></i> Créer une offre
        </a>
    </header>

    <div style="margin-bottom: 1rem; display: flex; gap: 0.5rem;">
        <a href="/admin/offers" class="button {% IF statusIsNull %}primary{% ELSE %}secondary{% ENDIF %}">Toutes</a>
        <a href="/admin/offers?status=pending" class="button {% IF statusIsPending %}primary{% ELSE %}secondary{% ENDIF %}">En attente</a>
        <a href="/admin/offers?status=approved" class="button {% IF statusIsApproved %}primary{% ELSE %}secondary{% ENDIF %}">Approuvées</a>
        <a href="/admin/offers?status=rejected" class="button {% IF statusIsRejected %}primary{% ELSE %}secondary{% ENDIF %}">Rejetées</a>
    </div>

    {% IF offers %}
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>POI ID</th>
                <th>Avantage</th>
                <th>Période</th>
                <th>Statut</th>
                <th>Vues</th>
                <th>Déblocages</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% FOR offer IN offers %}
            <tr>
                <td>{{ offer.id }}</td>
                <td><strong>{{ offer.title }}</strong></td>
                <td>{{ offer.poi_id }}</td>
                <td>{{ offer.advantage }}</td>
                <td>
                    <small>{{ offer.start_date_formatted }} - {{ offer.end_date_formatted }}</small>
                </td>
                <td>
                    {% IF offer.statusIsPending %}
                        <span style="color: #ffc107;">En attente</span>
                    {% ELSEIF offer.statusIsApproved %}
                        <span style="color: #28a745;">Approuvée</span>
                    {% ELSE %}
                        <span style="color: #dc3545;">Rejetée</span>
                    {% ENDIF %}
                    {% IF offer.isCurrentlyValid %}
                        <br><small style="color: #28a745;">Active</small>
                    {% ELSE %}
                        <br><small style="color: #6c757d;">Inactive</small>
                    {% ENDIF %}
                </td>
                <td>{{ offer.views_count }}</td>
                <td>{{ offer.unlocks_count }}</td>
                <td class="table-actions">
                    <a href="{{ offer.editUrl }}" class="button small">
                        <i class="fa-solid fa-edit"></i> Modifier
                    </a>
                    {% IF offer.statusIsPending %}
                    <form method="POST" action="{{ offer.approveUrl }}" style="display: inline-block;">
                        <input type="hidden" name="_csrf" value="{{ csrfToken }}">
                        <button type="submit" class="button success small">
                            <i class="fa-solid fa-check"></i> Approuver
                        </button>
                    </form>
                    <form method="POST" action="{{ offer.rejectUrl }}" style="display: inline-block;">
                        <input type="hidden" name="_csrf" value="{{ csrfToken }}">
                        <button type="submit" class="button alert small">
                            <i class="fa-solid fa-times"></i> Rejeter
                        </button>
                    </form>
                    {% ENDIF %}
                    <form method="POST" action="{{ offer.deleteUrl }}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette offre ?');" style="display: inline-block;">
                        <input type="hidden" name="_csrf" value="{{ csrfToken }}">
                        <button type="submit" class="button alert small">
                            <i class="fa-solid fa-trash"></i> Supprimer
                        </button>
                    </form>
                </td>
            </tr>
            {% ENDFOR %}
        </tbody>
    </table>
    {% ELSE %}
    <p>Aucune offre trouvée.</p>
    {% ENDIF %}
</section>

