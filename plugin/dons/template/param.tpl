<form method="post" action="{ ROUTER.generate("dons-admin-save-params") }" class="admin-form">
    <input type="hidden" name="token" value="{{ token }}" />
    
    <fieldset>
        <legend>{{ Lang.dons-admin.public-page }}</legend>
        <div class="form-group">
            <label for="pageTitle">{{ Lang.dons-admin.page-title }}</label>
            <input type="text" name="pageTitle" id="pageTitle" value="{{ pageTitle }}" required />
        </div>
        <div class="form-group">
            <label for="description">{{ Lang.dons-admin.description }}</label>
            <textarea name="description" id="description" rows="3">{{ description }}</textarea>
        </div>
        <div class="form-group">
            <label for="targetAmount">{{ Lang.dons-admin.target-amount }}</label>
            <input type="number" name="targetAmount" id="targetAmount" value="{{ targetAmount }}" step="0.01" />
            <small>{{ Lang.dons-admin.target-help }}</small>
        </div>
    </fieldset>

    <fieldset>
        <legend>PayPal</legend>
        <div class="form-group">
            <label for="paypalClientId">Client ID</label>
            <input type="text" name="paypalClientId" id="paypalClientId" value="{{ paypalClientId }}" />
        </div>
        <div class="form-group">
            <label for="paypalSecret">Client Secret</label>
            <input type="password" name="paypalSecret" id="paypalSecret" value="{{ paypalSecret }}" />
        </div>
        <div class="form-group">
            <label for="paypalMode">{{ Lang.dons-admin.mode }}</label>
            <select name="paypalMode" id="paypalMode">
                <option value="sandbox" {% if paypalMode == "sandbox" %}selected{% endif %}>Sandbox (Test)</option>
                <option value="live" {% if paypalMode == "live" %}selected{% endif %}>Live (Production)</option>
            </select>
        </div>
    </fieldset>

    <fieldset>
        <legend>Stripe</legend>
        <div class="form-group">
            <label for="stripePublishableKey">Publishable Key</label>
            <input type="text" name="stripePublishableKey" id="stripePublishableKey" value="{{ stripePublishableKey }}" />
        </div>
        <div class="form-group">
            <label for="stripeSecretKey">Secret Key</label>
            <input type="password" name="stripeSecretKey" id="stripeSecretKey" value="{{ stripeSecretKey }}" />
        </div>
        <div class="form-group">
            <label for="stripeMode">{{ Lang.dons-admin.mode }}</label>
            <select name="stripeMode" id="stripeMode">
                <option value="test" {% if stripeMode == "test" %}selected{% endif %}>Test</option>
                <option value="live" {% if stripeMode == "live" %}selected{% endif %}>Live</option>
            </select>
        </div>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="button success">
            <i class="fa-solid fa-floppy-disk"></i> {{ Lang.core-save }}
        </button>
    </div>
</form>
