<div class="admin-section" style="max-width: 700px;">
    <h2><i class="fa-solid fa-credit-card"></i> Configuration des paiements</h2>

    <form method="post" action="{ router.generate('paiement-admin-saveconfig') }">
        <input type="hidden" name="_csrfToken" value="{{ _csrfToken }}">

        <div class="config-section">
            <h3>💳 Stripe</h3>
            <div class="field">
                <label>Clé publique (pk_...)</label>
                <input type="text" name="stripe_public_key" value="{{ plugin ? plugin.getConfigVal('stripe_public_key') : '' }}" placeholder="pk_live_...">
            </div>
            <div class="field">
                <label>Clé secrète (sk_...)</label>
                <input type="password" name="stripe_secret_key" value="{{ plugin ? plugin.getConfigVal('stripe_secret_key') : '' }}" placeholder="sk_live_...">
            </div>
            <div class="field">
                <label>Webhook secret (whsec_...)</label>
                <input type="password" name="stripe_webhook_secret" value="{{ plugin ? plugin.getConfigVal('stripe_webhook_secret') : '' }}" placeholder="whsec_...">
            </div>
            <p class="field-help">URL webhook Stripe à configurer : <code>https://votre-domaine.fr/paiement/webhook/stripe</code></p>
        </div>

        <div class="config-section">
            <h3>🅿️ PayPal</h3>
            <div class="field">
                <label>Client ID</label>
                <input type="text" name="paypal_client_id" value="{{ plugin ? plugin.getConfigVal('paypal_client_id') : '' }}" placeholder="Client ID PayPal">
            </div>
            <div class="field">
                <label>Client Secret</label>
                <input type="password" name="paypal_client_secret" value="{{ plugin ? plugin.getConfigVal('paypal_client_secret') : '' }}" placeholder="Client Secret PayPal">
            </div>
            <div class="field">
                <label>Mode</label>
                <select name="paypal_mode">
                    <option value="sandbox" {{ (plugin and plugin.getConfigVal('paypal_mode') == 'sandbox') ? 'selected' : '' }}>Sandbox (test)</option>
                    <option value="live" {{ (plugin and plugin.getConfigVal('paypal_mode') == 'live') ? 'selected' : '' }}>Live (production)</option>
                </select>
            </div>
        </div>

        <div class="config-section">
            <h3>🏦 Mollie</h3>
            <div class="field">
                <label>Clé API (test_... ou live_...)</label>
                <input type="password" name="mollie_api_key" value="{{ plugin ? plugin.getConfigVal('mollie_api_key') : '' }}" placeholder="test_xxxxxxxxxxxxxxxxxxxxxxxx">
            </div>
            <p class="field-help">URL webhook Mollie à configurer : <code>https://votre-domaine.fr/paiement/webhook/mollie</code></p>
            <p class="field-help"><strong>Avantage Mollie :</strong> Pas de SIRET requis pour tester. Créez un compte sur <a href="https://mollie.com" target="_blank">mollie.com</a> et utilisez une clé de test.</p>
        </div>

        <button type="submit" class="button success">
            <i class="fa-solid fa-save"></i> Enregistrer la configuration
        </button>
    </form>
</div>

<style>
.config-section { background: #f9f9f9; border-radius: 12px; padding: 20px 22px; margin-bottom: 24px; border: 1px solid #e0e0e0; }
.config-section h3 { margin-top: 0; }
.field { margin-bottom: 14px; }
.field label { display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 5px; }
.field input, .field select { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box; }
.field-help { font-size: 0.82rem; color: #666; margin-top: -6px; margin-bottom: 10px; }
.field-help code { background: #eee; padding: 2px 6px; border-radius: 4px; }
</style>
