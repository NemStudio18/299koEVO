<div class="checkout-page">
    {% if error is defined and error %}
        <p>Vous devez être <a href="{{ SHOW.siteUrl }}/users/login">connecté</a> pour effectuer un paiement.</p>
    {% else %}
        <div class="checkout-summary">
            <div class="checkout-icon">💳</div>
            <h2>{{ description }}</h2>
            <div class="checkout-amount">{{ amountFormate }} €</div>
        </div>

        <p class="checkout-subtitle">Choisissez votre moyen de paiement sécurisé :</p>

        <div class="payment-methods">
            {% if stripeConfigured %}
            <form method="post" action="{{ SHOW.siteUrl }}/paiement/checkout">
                <input type="hidden" name="_csrfToken" value="{{ _csrfToken }}">
                <input type="hidden" name="provider" value="stripe">
                <input type="hidden" name="type" value="{{ type }}">
                <input type="hidden" name="id" value="{{ refId }}">
                <button type="submit" class="payment-method-btn stripe">
                    <span style="font-size:1.4rem">💳</span>
                    <span>Carte bancaire<br><small>via Stripe — sécurisé</small></span>
                </button>
            </form>
            {% endif %}

            {% if paypalConfigured %}
            <form method="post" action="{{ SHOW.siteUrl }}/paiement/checkout">
                <input type="hidden" name="_csrfToken" value="{{ _csrfToken }}">
                <input type="hidden" name="provider" value="paypal">
                <input type="hidden" name="type" value="{{ type }}">
                <input type="hidden" name="id" value="{{ refId }}">
                <button type="submit" class="payment-method-btn paypal">
                    <span style="font-size:1.4rem">🅿️</span>
                    <span>PayPal<br><small>Compte ou carte PayPal</small></span>
                </button>
            </form>
            {% endif %}

            {% if mollieConfigured %}
            <form method="post" action="{{ SHOW.siteUrl }}/paiement/checkout">
                <input type="hidden" name="_csrfToken" value="{{ _csrfToken }}">
                <input type="hidden" name="provider" value="mollie">
                <input type="hidden" name="type" value="{{ type }}">
                <input type="hidden" name="id" value="{{ refId }}">
                <button type="submit" class="payment-method-btn mollie">
                    <span style="font-size:1.4rem">🏦</span>
                    <span>Payer en ligne<br><small>via Mollie — CB, iDEAL, Bancontact…</small></span>
                </button>
            </form>
            {% endif %}

            {% if not stripeConfigured and not paypalConfigured and not mollieConfigured %}
                <div class="msg warning" style="grid-column:1/-1;">
                    ⚠️ Aucun prestataire de paiement n'est encore configuré. Contactez l'administrateur.
                </div>
            {% endif %}
        </div>

        <div class="checkout-secure">
            🔒 Paiement 100% sécurisé — vos données bancaires ne transitent jamais par nos serveurs
        </div>
    {% endif %}
</div>

<style>
.checkout-page { max-width: 600px; margin: 0 auto; }
.checkout-summary { text-align: center; background: var(--color-berry-muted, #e8f5e9); border-radius: 16px; padding: 32px 24px; margin-bottom: 28px; }
.checkout-icon { font-size: 3rem; margin-bottom: 12px; }
.checkout-summary h2 { margin: 0 0 8px; color: var(--color-berry-dark, #1b5e20); }
.checkout-amount { font-size: 2.5rem; font-weight: 800; color: var(--color-berry, #2e7d32); }
.checkout-subtitle { text-align: center; color: var(--font-color-muted, #666); margin-bottom: 20px; }
.payment-methods { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 0 0 24px; }
.payment-method-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 12px; padding: 20px 16px; border: 2px solid var(--border-color, #e0e0e0); border-radius: 14px; background: var(--panel-bg, #fff); cursor: pointer; transition: all 0.25s ease; font-size: 0.95rem; font-weight: 600; text-align: left; }
.payment-method-btn small { font-weight: 400; font-size: 0.78rem; color: var(--font-color-muted, #666); }
.payment-method-btn.stripe:hover { border-color: #6772e5; background: #f0f0ff; }
.payment-method-btn.paypal:hover  { border-color: #003087; background: #f0f4ff; }
.payment-method-btn.mollie:hover  { border-color: #cc0000; background: #fff5f5; }
.checkout-secure { text-align: center; font-size: 0.82rem; color: var(--font-color-muted, #666); padding: 12px; background: var(--bg-body, #f5f5f5); border-radius: 8px; }
@media(max-width:500px) { .payment-methods { grid-template-columns: 1fr; } }
</style>
