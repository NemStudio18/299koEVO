<section class="module-card">
    <header>
        <i class="fa-solid fa-credit-card"></i>
        Gestion de mon abonnement
    </header>

    {% if subscription and subscription.status == "active" %}
    <div class="subscription-status">
        <h3>Abonnement actif</h3>
        <p>
            <strong>Type :</strong> 
            {% if subscription.plan_type == "trial" %}
                <span class="subscription-trial-text"><i class="fa-solid fa-flask-vial"></i> Essai gratuit</span>
            {% elseif subscription.plan_type == "annual" %}
                Abonnement annuel
            {% else %}
                Abonnement mensuel
            {% endif %}
        </p>
        <p>
            <strong>Statut :</strong> 
            {% if subscription.plan_type == "trial" %}
                <span class="badge badge-trial">Essai en cours</span>
            {% else %}
                <span class="badge success">Actif</span>
            {% endif %}
        </p>
        {% if subscription.expires_at %}
        <p>
            <strong>Expire le :</strong> {{ Util.getDateHour(subscription.expires_at) }}
        </p>
        {% endif %}
        
        <form method="post" action="{{ ROUTER.generate("subscription-cancel") }}" style="margin-top: 1rem;">
            {{ SHOW.tokenField }}
            <button type="submit" class="button alert" onclick="return confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?');">
                Annuler mon abonnement
            </button>
        </form>
    </div>
    {% else %}
    {% if not config.enabled %}
    <div class="subscription-disabled">
        <div class="subscription-disabled-message">
            <i class="fa-solid fa-info-circle subscription-disabled-icon"></i>
            <p class="subscription-disabled-text">{% if config.disabled_message %}{{ config.disabled_message }}{% else %}Les abonnements sont temporairement indisponibles.{% endif %}</p>
        </div>
    </div>
    {% else %}
    <div class="subscription-plans">
        <h3>Choisissez votre abonnement</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
            <!-- Plan mensuel -->
            <div class="plan-card">
                <h4>{% if config.monthly_title %}{{ config.monthly_title }}{% else %}Abonnement mensuel{% endif %}</h4>
                {% if config.monthly_description %}
                <p class="plan-description">{{ config.monthly_description }}</p>
                {% endif %}
                <p class="plan-price">{{ monthlyPrice }} €<small>/mois</small></p>
                {% if isLegende %}
                <p style="color: #9b59b6; font-size: 0.85rem; font-weight: bold; margin-bottom: 15px;">
                    <i class="fa-solid fa-trophy"></i> Remise automatique -10% (Rang Légende)
                </p>
                {% endif %}
                <form method="post" action="{{ ROUTER.generate("subscription-subscribe") }}">
                    {{ SHOW.tokenField }}
                    <input type="hidden" name="plan_type" value="monthly">
                    <div style="margin: 1rem 0;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Méthode de paiement :</label>
                        <select name="payment_method" required style="width: 100%; padding: 0.5rem;">
                            <option value="card">Carte bancaire</option>
                            <option value="directdebit">Prélèvement SEPA</option>
                        </select>
                    </div>
                    <div style="margin: 1rem 0;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Code Promo (Optionnel) :</label>
                        <input type="text" name="promo_code" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" placeholder="EX: LANCEMENT50">
                    </div>
                    <button type="submit" class="button success" style="width: 100%;">S'abonner</button>
                </form>
            </div>

            <!-- Plan annuel -->
            <div class="plan-card plan-card-annual">
                <h4>{% if config.annual_title %}{{ config.annual_title }}{% else %}Abonnement annuel{% endif %}</h4>
                {% if config.annual_description %}
                <p class="plan-description">{{ config.annual_description }}</p>
                {% endif %}
                {% if config.annual_discount > 0 %}
                <p style="color: #10b981; font-weight: bold;">-{{ config.annual_discount }}% de réduction !</p>
                {% endif %}
                <p class="plan-price">{{ annualPrice }} €<small>/an</small></p>
                {% if isLegende %}
                <p style="color: #9b59b6; font-size: 0.85rem; font-weight: bold; margin-bottom: 15px;">
                    <i class="fa-solid fa-trophy"></i> Remise automatique -10% (Rang Légende)
                </p>
                {% endif %}
                {% if config.annual_discount > 0 %}
                <p style="text-decoration: line-through; color: #999;">{{ config.annual_price }} €</p>
                {% endif %}
                <form method="post" action="{{ ROUTER.generate("subscription-subscribe") }}">
                    {{ SHOW.tokenField }}
                    <input type="hidden" name="plan_type" value="annual">
                    <div style="margin: 1rem 0;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Méthode de paiement :</label>
                        <select name="payment_method" required style="width: 100%; padding: 0.5rem;">
                            <option value="card">Carte bancaire</option>
                            <option value="directdebit">Prélèvement SEPA</option>
                        </select>
                    </div>
                    <div style="margin: 1rem 0;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Code Promo (Optionnel) :</label>
                        <input type="text" name="promo_code" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" placeholder="EX: LANCEMENT50">
                    </div>
                    <button type="submit" class="button success" style="width: 100%;">S'abonner</button>
                </form>
            </div>

            {% if canActivateTrial %}
            <!-- Essai gratuit -->
            <div class="plan-card trial-card" style="border: 2px solid #8b5cf6; background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);">
                <div style="display: inline-block; background: #8b5cf6; color: white; font-size: 0.75rem; font-weight: bold; padding: 0.25rem 0.75rem; border-radius: 999px; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    Gratuit
                </div>
                <h4 style="color: #6d28d9;">{% if config.trial_title %}{{ config.trial_title }}{% else %}Essai gratuit{% endif %}</h4>
                {% if config.trial_description %}
                <p style="color: #6b7280; margin-bottom: 1rem;">{{ config.trial_description }}</p>
                {% endif %}
                <p class="plan-price" style="color: #8b5cf6;">0 €<small> / {{ config.trial_duration }} jours</small></p>
                <form method="post" action="{{ ROUTER.generate("subscription-start-trial") }}">
                    {{ SHOW.tokenField }}
                    <button type="submit" class="button" style="width: 100%; background: #8b5cf6; color: white; border: none;" onclick="return confirm('Activer votre essai gratuit de {{ config.trial_duration }} jours ? Cette offre est unique et ne peut pas être renouvelée.');">
                        <i class="fa-solid fa-rocket"></i> Activer mon essai gratuit
                    </button>
                </form>
                <p style="font-size: 0.8rem; color: #9ca3af; margin-top: 0.75rem;">
                    <i class="fa-solid fa-info-circle"></i> Offre unique, non renouvelable
                </p>
            </div>
            {% endif %}
        </div>
    </div>
    {% endif %}
    {% endif %}

    {% if subscription and addons %}
    <div class="subscription-addons" style="margin-top: 2rem;">
        <h3>Mes add-ons</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Prix mensuel</th>
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
                            <form method="post" action="{{ ROUTER.generate("subscription-remove-addon", ["addonId" => addon.id]) }}" class="inline-form">
                                {{ SHOW.tokenField }}
                                <button type="submit" class="button small alert" onclick="return confirm('Retirer cet add-on ?');">Retirer</button>
                            </form>
                        </td>
                    </tr>
                    {% ENDFOR %}
                </tbody>
            </table>
        </div>
    </div>
    {% endif %}

    {% if subscription and availableAddons %}
    <div class="available-addons" style="margin-top: 2rem;">
        <h3>Add-ons disponibles</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Prix mensuel</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {% FOR addon IN availableAddons %}
                    <tr>
                        <td><strong>{{ addon.name }}</strong></td>
                        <td>{% if addon.description %}{{ addon.description }}{% else %}-{% endif %}</td>
                        <td>{{ addon.monthly_price }} €</td>
                        <td>
                            <form method="post" action="{{ ROUTER.generate("subscription-add-addon") }}" style="display:inline;">
                                {{ SHOW.tokenField }}
                                <input type="hidden" name="addon_id" value="{{ addon.id }}">
                                <button type="submit" class="button small success">Ajouter</button>
                            </form>
                        </td>
                    </tr>
                    {% ENDFOR %}
                </tbody>
            </table>
        </div>
    </div>
    {% endif %}

    {% if subscription %}
    <div class="payment-methods" style="margin-top: 2rem;">
        <h3>Moyens de paiement</h3>
        {% if paymentMethods %}
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Derniers 4 chiffres</th>
                        <th>Expiration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {% FOR method IN paymentMethods %}
                    <tr>
                        <td>{% if method.card_type %}{{ method.card_type }}{% else %}-{% endif %}</td>
                        <td>**** **** **** {{ method.card_last4 }}</td>
                        <td>{% if method.card_exp_month and method.card_exp_year %}{{ method.card_exp_month }}/{{ method.card_exp_year }}{% else %}-{% endif %}</td>
                        <td>
                            <form method="post" action="{{ ROUTER.generate("subscription-remove-payment-method", ["methodId" => method.id]) }}" style="display:inline;">
                                {{ SHOW.tokenField }}
                                <button type="submit" class="button small alert" onclick="return confirm('Supprimer ce moyen de paiement ?');">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    {% ENDFOR %}
                </tbody>
            </table>
        </div>
        {% else %}
        <p>Aucun moyen de paiement enregistré.</p>
        {% endif %}
        <p>
            <button class="button" onclick="addPaymentMethod()">Ajouter un moyen de paiement</button>
        </p>
    </div>
    {% endif %}
</section>

<script>
function addPaymentMethod() {
    // TODO: Intégration Mollie pour ajouter un mandat SEPA
    alert('Fonctionnalité en cours de développement');
}
</script>

<style>
.plan-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
}

.plan-price {
    font-size: 2rem;
    font-weight: bold;
    margin: 1rem 0;
}

.plan-price small {
    font-size: 1rem;
    font-weight: normal;
    color: #6b7280;
}
</style>

