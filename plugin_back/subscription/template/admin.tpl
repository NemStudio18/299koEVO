<section class="module-card">
    <header>
        <i class="fa-solid fa-credit-card"></i>
        Gestion des abonnements
    </header>

    <div class="tabs-container">
        <ul class="tabs-header">
            <li class="default-tab">
                <i class="fa-solid fa-chart-line"></i> Tableau de bord
            </li>
            <li>
                <i class="fa-solid fa-users"></i> Abonnés
            </li>
            <li>
                <i class="fa-solid fa-money-bill"></i> Paiements
            </li>
            <li>
                <i class="fa-solid fa-puzzle-piece"></i> Add-ons
            </li>
            <li>
                <i class="fa-solid fa-ticket"></i> Codes Promo
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
                            <h3>Total abonnements</h3>
                            <p class="stat-number">{% if stats.total_subscriptions %}{{ stats.total_subscriptions }}{% else %}0{% endif %}</p>
                        </div>
                        <div class="stat-card">
                            <h3>Abonnements actifs</h3>
                            <p class="stat-number" style="color: #10b981;">{% if stats.active_count %}{{ stats.active_count }}{% else %}0{% endif %}</p>
                        </div>
                        <div class="stat-card">
                            <h3>Abonnements annulés</h3>
                            <p class="stat-number" style="color: #ef4444;">{% if stats.cancelled_count %}{{ stats.cancelled_count }}{% else %}0{% endif %}</p>
                        </div>
                        <div class="stat-card">
                            <h3>Abonnements expirés</h3>
                            <p class="stat-number" style="color: #f59e0b;">{% if stats.expired_count %}{{ stats.expired_count }}{% else %}0{% endif %}</p>
                        </div>
                    </div>

                    {% if subscribers %}
                    <div class="table-responsive">
                        <h3>Derniers abonnés</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Add-ons</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                {% FOR subscriber IN subscribers %}
                                <tr>
                                    <td>{% if subscriber.username %}{{ subscriber.username }}{% elseif subscriber.email %}{{ subscriber.email }}{% else %}-{% endif %}</td>
                                    <td>
                                        {% if subscriber.plan_type == "annual" %}
                                            <span class="badge">Annuel</span>
                                        {% else %}
                                            <span class="badge">Mensuel</span>
                                        {% endif %}
                                    </td>
                                    <td>
                                        {% if subscriber.status == "active" %}
                                            <span class="badge success">Actif</span>
                                        {% elseif subscriber.status == "cancelled" %}
                                            <span class="badge alert">Annulé</span>
                                        {% elseif subscriber.status == "expired" %}
                                            <span class="badge warning">Expiré</span>
                                        {% else %}
                                            <span class="badge">En attente</span>
                                        {% endif %}
                                    </td>
                                    <td>{% if subscriber.addons_count %}{{ subscriber.addons_count }}{% else %}0{% endif %}</td>
                                    <td>
                                        {% if subscriber.created_at %}
                                            {{ Util.getDateHour(subscriber.created_at) }}
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
                </div>
            </li>

            <!-- Onglet Abonnés -->
            <li class="tab">
                <div class="table-responsive">
                    <h3>Liste des abonnés</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Prix mensuel</th>
                                <th>Add-ons</th>
                                <th>Date début</th>
                                <th>Date expiration</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% FOR subscriber IN subscribers %}
                            <tr>
                                <td>{% if subscriber.username %}{{ subscriber.username }}{% elseif subscriber.email %}{{ subscriber.email }}{% else %}-{% endif %}</td>
                                <td>
                                    {% if subscriber.plan_type == "annual" %}
                                        <span class="badge">Annuel</span>
                                    {% else %}
                                        <span class="badge">Mensuel</span>
                                    {% endif %}
                                </td>
                                <td>
                                    {% if subscriber.status == "active" %}
                                        <span class="badge success">Actif</span>
                                    {% elseif subscriber.status == "cancelled" %}
                                        <span class="badge alert">Annulé</span>
                                    {% elseif subscriber.status == "expired" %}
                                        <span class="badge warning">Expiré</span>
                                    {% else %}
                                        <span class="badge">En attente</span>
                                    {% endif %}
                                </td>
                                <td>{{ subscriber.monthly_price }} €</td>
                                <td>{% if subscriber.addons_count %}{{ subscriber.addons_count }}{% else %}0{% endif %}</td>
                                <td>
                                    {% if subscriber.started_at %}
                                        {{ Util.getDateHour(subscriber.started_at) }}
                                    {% else %}
                                        -
                                    {% endif %}
                                </td>
                                <td>
                                    {% if subscriber.expires_at %}
                                        {{ Util.getDateHour(subscriber.expires_at) }}
                                    {% else %}
                                        -
                                    {% endif %}
                                </td>
                            </tr>
                            {% ENDFOR %}
                        </tbody>
                    </table>
                </div>
            </li>

            <!-- Onglet Paiements -->
            <li class="tab">
                <div class="table-responsive">
                    <h3>Historique des paiements</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Montant</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% FOR payment IN payments %}
                            <tr>
                                <td>{% if payment.username %}{{ payment.username }}{% elseif payment.email %}{{ payment.email }}{% else %}-{% endif %}</td>
                                <td><strong>{{ payment.amount }} {{ payment.currency }}</strong></td>
                                <td>
                                    {% if payment.payment_type == "subscription" %}
                                        <span class="badge">Abonnement</span>
                                    {% elseif payment.payment_type == "addon" %}
                                        <span class="badge">Add-on</span>
                                    {% else %}
                                        <span class="badge">Ponctuel</span>
                                    {% endif %}
                                </td>
                                <td>
                                    {% if payment.status == "succeeded" %}
                                        <span class="badge success">Réussi</span>
                                    {% elseif payment.status == "failed" %}
                                        <span class="badge alert">Échoué</span>
                                    {% elseif payment.status == "refunded" %}
                                        <span class="badge warning">Remboursé</span>
                                    {% else %}
                                        <span class="badge">En attente</span>
                                    {% endif %}
                                </td>
                                <td>
                                    {% if payment.created_at %}
                                        {{ Util.getDateHour(payment.created_at) }}
                                    {% else %}
                                        -
                                    {% endif %}
                                </td>
                            </tr>
                            {% ENDFOR %}
                        </tbody>
                    </table>
                </div>
            </li>

            <!-- Onglet Add-ons -->
            <li class="tab">
                <div class="table-responsive">
                    <h3>Add-ons disponibles</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Prix mensuel</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% FOR addon IN addons %}
                            <tr>
                                <td><strong>{{ addon.name }}</strong></td>
                                <td>{% if addon.description %}{{ addon.description }}{% else %}-{% endif %}</td>
                                <td>{{ addon.monthly_price }} €</td>
                                <td>
                                    {% if addon.active %}
                                        <span class="badge success">Actif</span>
                                    {% else %}
                                        <span class="badge">Inactif</span>
                                    {% endif %}
                                </td>
                                <td>
                                    <button class="button small" onclick="editAddon({{ addon.id }})">Modifier</button>
                                    <button class="button small alert" onclick="deleteAddon({{ addon.id }})">Supprimer</button>
                                </td>
                            </tr>
                            {% ENDFOR %}
                        </tbody>
                    </table>
                    <p>
                        <button class="button success" onclick="editAddon(0)">Ajouter un add-on</button>
                    </p>
                </div>
            </li>

            <!-- Onglet Codes Promo -->
            <li class="tab">
                <div class="table-responsive">
                    <h3>Codes Promotionnels</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Réduction</th>
                                <th>Durée</th>
                                <th>Utilisations</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% FOR promo IN promocodes %}
                            <tr>
                                <td><strong>{{ promo.code }}</strong></td>
                                <td>{{ promo.discount_percentage }}%</td>
                                <td>
                                    {% if promo.duration == 'infinite' %}Infini
                                    {% elseif promo.duration == '1_month' %}1 Mois
                                    {% elseif promo.duration == '3_months' %}3 Mois
                                    {% elseif promo.duration == '1_year' %}1 An
                                    {% else %}{{ promo.duration }}
                                    {% endif %}
                                </td>
                                <td>{{ promo.current_uses }} / {% if promo.max_uses %}{{ promo.max_uses }}{% else %}∞{% endif %}</td>
                                <td>
                                    {% if promo.is_active %}
                                        <span class="badge success">Actif</span>
                                    {% else %}
                                        <span class="badge">Inactif</span>
                                    {% endif %}
                                </td>
                                <td>
                                    {% if promo.is_active %}
                                        <a href="{{ ROUTER.generate('admin-subscription-toggle-promocode', ['codeId' => promo.id]) }}" class="button small alert">Désactiver</a>
                                    {% else %}
                                        <a href="{{ ROUTER.generate('admin-subscription-toggle-promocode', ['codeId' => promo.id]) }}" class="button small success">Activer</a>
                                    {% endif %}
                                    <button class="button small alert" onclick="deletePromoCode({{ promo.id }})">Supprimer</button>
                                </td>
                            </tr>
                            {% ENDFOR %}
                            {% if not promocodes %}
                            <tr>
                                <td colspan="6" style="text-align:center;">Aucun code promotionnel n'a été généré.</td>
                            </tr>
                            {% endif %}
                        </tbody>
                    </table>

                    <h3 style="margin-top: 2rem;">Générer un nouveau code</h3>
                    <form method="post" action="{{ ROUTER.generate('admin-subscription-save-promocode') }}">
                        {{ SHOW.tokenField }}
                        <div class="form-group">
                            <label for="promo_code">Code (ex: LANCEMENT50)</label>
                            <input type="text" id="promo_code" name="code" required style="text-transform: uppercase;" placeholder="Saisissez un code unique">
                        </div>
                        <div class="form-group">
                            <label for="promo_discount">Réduction en %</label>
                            <input type="number" id="promo_discount" name="discount_percentage" min="1" max="100" value="10" required>
                        </div>
                        <div class="form-group">
                            <label for="promo_duration">Durée de l'avantage</label>
                            <select id="promo_duration" name="duration">
                                <option value="infinite">Infini (applique la remise à chaque renouvellement)</option>
                                <option value="1_month">1 Mois</option>
                                <option value="3_months">3 Mois</option>
                                <option value="1_year">1 An</option>
                            </select>
                            <small>Les durées limitées nécessitent une intégration API avancée avec votre passerelle de paiement. 100% de réduction bypasse la passerelle.</small>
                        </div>
                        <div class="form-group">
                            <label for="promo_max_uses">Utilisations maximales globales (Laisser vide pour illimité)</label>
                            <input type="number" id="promo_max_uses" name="max_uses" min="1" placeholder="Ex: 50">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="button success">Générer le code</button>
                        </div>
                    </form>
                </div>
            </li>

            <!-- Onglet Configuration -->
            <li class="tab">
                <form method="post" action="{{ ROUTER.generate("admin-subscription-save-config") }}">
                    {{ SHOW.tokenField }}
                    <h3>Activation des abonnements</h3>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="subscriptions_enabled" value="1" {% if config.enabled %}checked{% endif %}>
                            Activer les abonnements
                        </label>
                        <small>Si désactivé, les utilisateurs verront un message au lieu des cartes de tarifs</small>
                    </div>
                    <div class="form-group">
                        <label for="disabled_message">Message affiché si les abonnements sont désactivés</label>
                        <textarea id="disabled_message" name="disabled_message" rows="4">{% if config.disabled_message %}{{ config.disabled_message }}{% else %}Les abonnements sont temporairement indisponibles.{% endif %}</textarea>
                        <small>Ce message sera affiché à la place des cartes de tarifs si les abonnements sont désactivés</small>
                    </div>
                    
                    <h3 style="margin-top: 2rem;">Plan mensuel</h3>
                    <div class="form-group">
                        <label for="monthly_title">Titre du plan mensuel</label>
                        <input type="text" id="monthly_title" name="monthly_title" value="{% if config.monthly_title %}{{ config.monthly_title }}{% else %}Abonnement mensuel{% endif %}" required>
                    </div>
                    <div class="form-group">
                        <label for="monthly_description">Description du plan mensuel</label>
                        <textarea id="monthly_description" name="monthly_description" rows="3">{% if config.monthly_description %}{{ config.monthly_description }}{% endif %}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="monthly_price">Prix mensuel (€)</label>
                        <input type="number" id="monthly_price" name="monthly_price" step="0.01" value="{% if config.monthly_price %}{{ config.monthly_price }}{% else %}9.99{% endif %}" required>
                    </div>
                    
                    <h3 style="margin-top: 2rem;">Plan annuel</h3>
                    <div class="form-group">
                        <label for="annual_title">Titre du plan annuel</label>
                        <input type="text" id="annual_title" name="annual_title" value="{% if config.annual_title %}{{ config.annual_title }}{% else %}Abonnement annuel{% endif %}" required>
                    </div>
                    <div class="form-group">
                        <label for="annual_description">Description du plan annuel</label>
                        <textarea id="annual_description" name="annual_description" rows="3">{% if config.annual_description %}{{ config.annual_description }}{% endif %}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="annual_price">Prix annuel (€)</label>
                        <input type="number" id="annual_price" name="annual_price" step="0.01" value="{% if config.annual_price %}{{ config.annual_price }}{% else %}99.99{% endif %}" required>
                    </div>
                    <div class="form-group">
                        <label for="annual_discount">Réduction annuelle (%)</label>
                        <input type="number" id="annual_discount" name="annual_discount" step="0.01" min="0" max="100" value="{% if config.annual_discount %}{{ config.annual_discount }}{% else %}0{% endif %}">
                        <small>Pourcentage de réduction appliqué au prix annuel</small>
                    </div>
                    <div class="form-group">
                        <label for="mollie_api_key">Mollie API Key</label>
                        <input type="password" id="mollie_api_key" name="mollie_api_key" value="{% if config.mollie_api_key %}{{ config.mollie_api_key }}{% endif %}" placeholder="test_... ou live_...">
                        <small>Clé API Mollie (commence par test_ pour le sandbox, live_ pour la production)</small>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="mollie_sandbox" value="1" {% if config.mollie_sandbox %}checked{% endif %}>
                            Mode sandbox (test)
                        </label>
                        <small>Cocher si vous utilisez une clé API de test (test_...)</small>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button success">Enregistrer</button>
                    </div>
                </form>
            </li>
        </ul>
    </div>
</section>

<script>
function editAddon(id) {
    // TODO: Ouvrir un modal ou rediriger vers un formulaire
    alert('Fonctionnalité à implémenter');
}

function deleteAddon(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet add-on ?')) {
        window.location.href = '{{ ROUTER.generate("admin-subscription-delete-addon", ["addonId" => "ID_PLACEHOLDER"]) }}'.replace('ID_PLACEHOLDER', id);
    }
}

function deletePromoCode(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce code promo ? Cette action est irréversible.')) {
        window.location.href = '{{ ROUTER.generate("admin-subscription-delete-promocode", ["codeId" => "ID_PLACEHOLDER"]) }}'.replace('ID_PLACEHOLDER', id);
    }
}
</script>

