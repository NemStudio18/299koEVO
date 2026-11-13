<section class="module-card">
    <header>
        <h1>{{Lang.ecommerce.checkout}}</h1>
    </header>

    <form id="checkout-form" class="checkout-form">
        <div class="checkout-grid">
            <div class="checkout-details">
                <h3>Informations client</h3>
                
                <div class="form-group">
                    <label for="customer_name">Nom complet</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>

                <div class="form-group">
                    <label for="customer_email">Email</label>
                    <input type="email" id="customer_email" name="customer_email" required>
                </div>

                <div class="form-group">
                    <label for="customer_address">Adresse de livraison</label>
                    <textarea id="customer_address" name="customer_address" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="customer_phone">Téléphone</label>
                    <input type="tel" id="customer_phone" name="customer_phone">
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Moyen de paiement</label>
                    <select id="payment_method" name="payment_method">
                        <option value="bank_transfer">Virement bancaire</option>
                        <option value="check">Chèque</option>
                        <option value="cash_on_delivery">Paiement à la livraison</option>
                    </select>
                </div>
            </div>

            <div class="checkout-summary">
                <div class="cart-summary">
                    <h3>Résumé de la commande</h3>
                    {% FOR item IN items %}
                    <div class="cart-summary-row" style="font-size: 0.9em; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.5rem;">
                        <span>{{item.quantity}}x {{item.product.getName()}}</span>
                        <span>{{item.subtotal}}€</span>
                    </div>
                    {% ENDFOR %}
                    
                    <div class="cart-summary-row" style="margin-top: 1rem;">
                        <span>{{Lang.ecommerce.subtotal}}</span>
                        <strong>{{subtotal}}€</strong>
                    </div>
                    <div class="cart-summary-row">
                        <span>{{Lang.ecommerce.shipping}}</span>
                        <strong>{% IF shipping == 0 %}{{Lang.ecommerce.free-shipping}}{% ELSE %}{{shipping}}€{% ENDIF %}</strong>
                    </div>
                    <div class="cart-summary-row cart-total">
                        <span>{{Lang.ecommerce.total}}</span>
                        <strong>{{total}}€</strong>
                    </div>

                    {{token}}
                    
                    <button type="submit" class="button success full-width" style="margin-top: 1rem;">
                        {{Lang.ecommerce.confirm-order}}
                    </button>
                    <div id="checkout-error" class="alert alert-danger" style="display:none; margin-top: 1rem;"></div>
                </div>
            </div>
        </div>
    </form>
</section>

<script>
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const errorDiv = document.getElementById('checkout-error');
    const submitBtn = this.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement...';
    errorDiv.style.display = 'none';

    fetch('/checkout/process', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = data.redirect || '/shop';
        } else {
            errorDiv.textContent = data.message || 'Une erreur est survenue.';
            errorDiv.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '{{Lang.ecommerce.confirm-order}}';
        }
    })
    .catch(error => {
        errorDiv.textContent = 'Erreur de connexion.';
        errorDiv.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = '{{Lang.ecommerce.confirm-order}}';
    });
});
</script>
