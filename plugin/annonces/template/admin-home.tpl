<section class="module-card">
    <header><i class="fa-solid fa-tags"></i> {{Lang.annonces.admin-title}}</header>

    <div class="module-actions" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 1rem;">
        <a href="{{createUrl}}" class="button success">
            <i class="fa-solid fa-plus"></i> {{Lang.annonces.add}}
        </a>
        <a href="{{publicListUrl}}" target="_blank" class="button">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir la page publique
        </a>
    </div>

    <div class="tabs-container" data-tab-key="annonces">
        <ul class="tabs-header">
            <li class="default-tab" data-tab="list">
                <i class="fa-solid fa-list"></i> {{Lang.annonces.tab-list}}
            </li>
            <li data-tab="reservations">
                <i class="fa-solid fa-bookmark"></i> {{Lang.annonces.tab-reservations}}
                {% if pendingCount %}<span style="background: #f59e0b; color: #fff; font-size: 0.72rem; padding: 1px 7px; border-radius: 999px; margin-left: 4px;">{{pendingCount}}</span>{% endif %}
            </li>
            <li data-tab="settings">
                <i class="fa-solid fa-sliders"></i> {{Lang.annonces.tab-settings}}
            </li>
            <li data-tab="info">
                <i class="fa-solid fa-circle-info"></i> {{Lang.annonces.tab-info}}
            </li>
        </ul>

        <ul class="tabs">
            <!-- TAB 1 : Liste des annonces -->
            <li class="tab">
                {% if annonces %}
                    <table>
                        <thead>
                            <tr>
                                <th>Annonce</th>
                                <th>Catégorie</th>
                                <th>Prix indicatif</th>
                                <th>Statut</th>
                                <th>Réservations</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% for item in annonces %}
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            {% if item.image %}
                                                <img src="{{item.image}}" alt="" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px;" />
                                            {% endif %}
                                            <div>
                                                <strong>{{item.title}}</strong>
                                                {% if item.location %}<br><small style="color: #64748b;"><i class="fa-solid fa-location-dot"></i> {{item.location}}</small>{% endif %}
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{item.category}}</td>
                                    <td>{{item.price_label}}</td>
                                    <td>
                                        <span class="ann-badge ann-badge-{{item.status}}">{{item.status_label}}</span>
                                    </td>
                                    <td>{{item.reservations_count}}</td>
                                    <td style="white-space: nowrap;">
                                        <a href="{{item.public_url}}" target="_blank" class="button small" title="Voir"><i class="fa-solid fa-eye"></i></a>
                                        <a href="{{item.edit_url}}" class="button small primary" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                                        <a href="{{item.delete_url}}" class="button small alert" onclick="return confirm('Supprimer cette annonce et ses réservations ?');" title="Supprimer"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                            {% endfor %}
                        </tbody>
                    </table>
                {% else %}
                    <div style="padding: 1.5rem; text-align: center; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; color: #64748b;">
                        Aucune annonce créée pour le moment. Cliquez sur <strong>{{Lang.annonces.add}}</strong> pour publier votre première petite annonce.
                    </div>
                {% endif %}
            </li>

            <!-- TAB 2 : Demandes de réservation -->
            <li class="tab">
                {% if reservations %}
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Annonce</th>
                                <th>Demandeur</th>
                                <th>Message</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% for res in reservations %}
                                <tr>
                                    <td style="white-space: nowrap; font-size: 0.85rem;">{{res.created_at}}</td>
                                    <td><strong>{{res.annonce_title}}</strong></td>
                                    <td>
                                        <strong>{{res.requester_name}}</strong><br>
                                        <a href="mailto:{{res.requester_email}}">{{res.requester_email}}</a>
                                        {% if res.requester_phone %}<br><small>{{res.requester_phone}}</small>{% endif %}
                                    </td>
                                    <td style="max-width: 280px; font-size: 0.88rem;">{{res.message}}</td>
                                    <td><span class="ann-badge ann-res-{{res.status}}">{{res.status_label}}</span></td>
                                    <td style="white-space: nowrap;">
                                        <form method="post" action="{{res.status_url}}" style="display: inline-flex; gap: 4px; align-items: center;">
                                            <input type="hidden" name="token" value="{{token}}" />
                                            <input type="hidden" name="_csrf" value="{{_csrfToken}}" />
                                            {% if not res.is_confirmed %}
                                                <button type="submit" name="status" value="confirmed" class="button small success" title="Confirmer et marquer l'annonce comme réservée">
                                                    <i class="fa-solid fa-check"></i> Confirmer
                                                </button>
                                            {% endif %}
                                            {% if res.is_pending or res.is_confirmed %}
                                                <button type="submit" name="status" value="cancelled" class="button small" title="Annuler la réservation">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            {% endif %}
                                        </form>
                                        <a href="{{res.delete_url}}" class="button small alert" onclick="return confirm('Supprimer cette demande ?');" title="Supprimer"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                            {% endfor %}
                        </tbody>
                    </table>
                {% else %}
                    <div style="padding: 1.5rem; text-align: center; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; color: #64748b;">
                        {{Lang.annonces.reservations-empty}}
                    </div>
                {% endif %}
            </li>

            <!-- TAB 3 : Paramètres -->
            <li class="tab">
                <form method="post" action="{{saveConfUrl}}">
                    <input type="hidden" name="token" value="{{token}}" />
                    <input type="hidden" name="_csrf" value="{{_csrfToken}}" />
                    <p>
                        <label for="cfg_label">Titre / libellé public</label><br>
                        <input type="text" id="cfg_label" name="label" value="{{cfgLabel}}" required />
                    </p>
                    <p>
                        <label for="cfg_intro">Texte d'introduction (en haut de la liste publique)</label><br>
                        <textarea id="cfg_intro" name="introText" class="editor" rows="4">{{cfgIntroText}}</textarea>
                    </p>
                    <p>
                        <label for="cfg_notify">Email de notification lors d'une réservation (optionnel)</label><br>
                        <input type="email" id="cfg_notify" name="notifyEmail" value="{{cfgNotifyEmail}}" placeholder="admin@exemple.fr" />
                    </p>
                    <p>
                        <label for="cfg_on_reserve">Comportement dès réception d'une demande de réservation</label><br>
                        <select id="cfg_on_reserve" name="onReserveStatus">
                            <option value="keep_available" {% if cfgOnReserveStatus == 'keep_available' %}selected{% endif %}>Laisser l'annonce « Disponible » jusqu'à confirmation manuelle</option>
                            <option value="mark_reserved" {% if cfgOnReserveStatus == 'mark_reserved' %}selected{% endif %}>Passer immédiatement l'annonce en « Réservée »</option>
                        </select>
                    </p>
                    <p>
                        <label for="cfg_notice">Mention affichée dans le bloc de réservation</label><br>
                        <textarea id="cfg_notice" name="reservationNotice" rows="2">{{cfgReservationNotice}}</textarea>
                    </p>
                    <p>
                        <input type="checkbox" id="cfg_antispam" name="requireAntispam" {% if cfgRequireAntispam %}checked{% endif %} />
                        <label for="cfg_antispam">Activer l'antispam sur le formulaire de réservation (si le plugin Antispam est actif)</label>
                    </p>
                    <p>
                        <button type="submit" class="button primary"><i class="fa-solid fa-save"></i> Enregistrer les paramètres</button>
                    </p>
                </form>
            </li>

            <!-- TAB 4 : Informations -->
            <li class="tab">
                <h4>Fonctionnement du plugin Petites Annonces</h4>
                <p>Ce module permet de publier des petites annonces génériques (objets, matériel, services, dons…) sans passerelle de paiement.</p>
                <ul style="line-height: 1.7; padding-left: 1.25rem;">
                    <li><strong>Création réservée à l'administrateur :</strong> seul l'administrateur peut créer, modifier, passer en brouillon ou clôturer des annonces.</li>
                    <li><strong>Système de réservation (zéro paiement) :</strong> côté public, les visiteurs peuvent soumettre une demande de réservation sur les annonces <em>Disponibles</em>.</li>
                    <li><strong>Workflow de validation :</strong> confirmer une réservation dans l'onglet <em>Réservations</em> passe automatiquement l'annonce au statut <strong>Réservée</strong> (l'annuler la remet <strong>Disponible</strong> si elle était réservée).</li>
                    <li><strong>Stockage agnostique :</strong> les entités <code>Annonce</code> et <code>AnnonceReservation</code> fonctionnent en JSON, SQLite ou MySQL (configurables depuis <em>Stockage</em>).</li>
                </ul>
            </li>
        </ul>
    </div>
</section>

<style>
.ann-badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
.ann-badge-available { background: #dcfce7; color: #166534; }
.ann-badge-reserved  { background: #fef3c7; color: #92400e; }
.ann-badge-closed    { background: #e2e8f0; color: #475569; }
.ann-badge-draft     { background: #f1f5f9; color: #64748b; }
.ann-res-pending     { background: #fef3c7; color: #92400e; }
.ann-res-confirmed   { background: #dcfce7; color: #166534; }
.ann-res-cancelled   { background: #fee2e2; color: #991b1b; }
</style>
