<section class="ecommerce-order-success">
    <div class="order-receipt-card">
        <header class="receipt-header">
            <div class="receipt-status-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h1>Merci pour votre commande !</h1>
            <p class="receipt-subtitle">Votre commande <strong>#{{ order.id }}</strong> a été enregistrée avec succès.</p>
            <p class="receipt-notice">Un e-mail récapitulatif vous a été envoyé à <strong>{{ order.customer_email }}</strong>.</p>
        </header>

        <div class="receipt-details-grid">
            <div class="receipt-box">
                <h3><i class="fa-solid fa-user"></i> Coordonnées &amp; Livraison</h3>
                <p><strong>{{ order.customer_name }}</strong></p>
                <p><i class="fa-solid fa-location-dot"></i> {{ order.customer_address }}</p>
                {% if order.customer_phone %}
                    <p><i class="fa-solid fa-phone"></i> {{ order.customer_phone }}</p>
                {% endif %}
                <p><i class="fa-solid fa-envelope"></i> {{ order.customer_email }}</p>
            </div>

            <div class="receipt-box">
                <h3><i class="fa-solid fa-credit-card"></i> Paiement</h3>
                <p>Mode choisi : <strong>
                    {% if order.payment_method == 'bank_transfer' %}Virement bancaire
                    {% elseif order.payment_method == 'stripe' %}Carte bancaire (Stripe)
                    {% elseif order.payment_method == 'paypal' %}PayPal
                    {% elseif order.payment_method == 'check' %}Chèque
                    {% else %}{{ order.payment_method }}{% endif %}
                </strong></p>
                <p>Statut actuel : <span class="badge status-{{ order.status }}">{{ order.status }}</span></p>
                <p>Date : {{ order.created_at }}</p>
            </div>
        </div>

        {% if order.payment_method == 'bank_transfer' and bankDetails %}
            <div class="receipt-payment-instructions">
                <h4><i class="fa-solid fa-building-columns"></i> Instructions pour le virement bancaire :</h4>
                <p>Veuillez effectuer votre virement en précisant la référence <strong>CMD-{{ order.id }}</strong> :</p>
                <div class="bank-details-box">
                    <div><strong>Bénéficiaire :</strong> {{ bankDetails.owner }}</div>
                    <div><strong>IBAN :</strong> <code>{{ bankDetails.iban }}</code></div>
                    <div><strong>BIC / SWIFT :</strong> <code>{{ bankDetails.bic }}</code></div>
                    <div><strong>Montant :</strong> {{ order.total }} {{ currency }}</div>
                </div>
            </div>
        {% endif %}

        <div class="receipt-table-wrapper">
            <h3><i class="fa-solid fa-bag-shopping"></i> Récapitulatif des articles</h3>
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th style="text-align: center;">Qté</th>
                        <th style="text-align: right;">Prix unitaire</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {% for item in items %}
                        <tr>
                            <td>{{ item.product_name }}</td>
                            <td style="text-align: center;">{{ item.quantity }}</td>
                            <td style="text-align: right;">{{ item.price }} {{ currency }}</td>
                            <td style="text-align: right;">{{ item.total }} {{ currency }}</td>
                        </tr>
                    {% endfor %}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 700;">Total TTC :</td>
                        <td style="text-align: right; font-weight: 700; font-size: 1.15rem; color: var(--evo-primary, #0284c7);">
                            {{ order.total }} {{ currency }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="receipt-actions no-print">
            <button type="button" class="button secondary" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Imprimer mon reçu
            </button>
            <a href="/shop" class="button primary">
                <i class="fa-solid fa-store"></i> Continuer mes achats
            </a>
        </div>
    </div>
</section>

<style>
.ecommerce-order-success { max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
.order-receipt-card { background: var(--evo-surface, #ffffff); border: 1px solid var(--evo-border, #e2e8f0); border-radius: 12px; padding: 2.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.receipt-header { text-align: center; margin-bottom: 2rem; border-bottom: 1px solid var(--evo-border, #e2e8f0); padding-bottom: 1.5rem; }
.receipt-status-icon { font-size: 3.5rem; color: #16a34a; margin-bottom: 0.75rem; }
.receipt-header h1 { margin: 0 0 0.5rem; font-size: 1.85rem; }
.receipt-subtitle { font-size: 1.1rem; margin: 0 0 0.25rem; color: var(--evo-text, inherit); }
.receipt-notice { font-size: 0.95rem; color: var(--evo-text-muted, #64748b); margin: 0; }
.receipt-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
@media (max-width: 600px) { .receipt-details-grid { grid-template-columns: 1fr; } }
.receipt-box { background: var(--evo-surface-alt, #f8fafc); border: 1px solid var(--evo-border, #e2e8f0); border-radius: 8px; padding: 1.25rem; }
.receipt-box h3 { margin: 0 0 0.75rem; font-size: 1rem; color: var(--evo-primary, #0284c7); }
.receipt-box p { margin: 0 0 0.35rem; font-size: 0.9rem; }
.receipt-payment-instructions { background: rgba(2, 132, 199, 0.08); border-left: 4px solid var(--evo-primary, #0284c7); border-radius: 6px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
.receipt-payment-instructions h4 { margin: 0 0 0.5rem; font-size: 0.95rem; }
.bank-details-box { display: flex; flex-direction: column; gap: 4px; font-size: 0.9rem; margin-top: 0.5rem; }
.bank-details-box code { background: rgba(0,0,0,0.06); padding: 2px 6px; border-radius: 4px; font-weight: 700; }
.receipt-table-wrapper { margin-bottom: 2rem; }
.receipt-table-wrapper h3 { font-size: 1.05rem; margin-bottom: 0.75rem; }
.receipt-table { width: 100%; border-collapse: collapse; }
.receipt-table th, .receipt-table td { padding: 10px 12px; border-bottom: 1px solid var(--evo-border, #e2e8f0); font-size: 0.9rem; }
.receipt-actions { display: flex; justify-content: center; gap: 1rem; margin-top: 2rem; }
@media print {
    .no-print, #header, #navigation, #footer, .site-header { display: none !important; }
    .order-receipt-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
    body { background: #fff !important; color: #000 !important; }
}
</style>

