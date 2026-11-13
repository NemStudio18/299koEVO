<section class="overflow-auto">
    <header><i class="fa-solid fa-receipt"></i> Commande {{ order.getOrderNumber() }}</header>

    <div style="margin-bottom: 16px;">
        <a href="{{ ROUTER.generate('admin-ecommerce-orders') }}" class="button secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour aux commandes
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div style="background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
            <h4>Informations client</h4>
            <p><strong>Nom :</strong> {{ order.getCustomerName() }}</p>
            <p><strong>Email :</strong> {{ order.getCustomerEmail() }}</p>
            <p><strong>Téléphone :</strong> {{ order.getCustomerPhone() }}</p>
            <p><strong>Adresse :</strong><br>{{ order.getCustomerAddress() }}</p>
        </div>

        <div style="background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
            <h4>Détails commande</h4>
            <p><strong>Statut :</strong> {{ order.getStatus() }}</p>
            <p><strong>Paiement :</strong> {{ order.getPaymentMethod() }}</p>
            <p><strong>Total :</strong> <strong>{{ order.getTotal() }} €</strong></p>
        </div>
    </div>

    <h3>Articles commandés</h3>
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Prix unitaire</th>
                <th>Quantité</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            {% FOR item IN order.getItems() %}
            <tr>
                <td>{{ item.getProductName() }}</td>
                <td>{{ item.getPrice() }} €</td>
                <td>{{ item.getQuantity() }}</td>
                <td><strong>{{ item.getPrice() * item.getQuantity() }} €</strong></td>
            </tr>
            {% ENDFOR %}
        </tbody>
    </table>
</section>
