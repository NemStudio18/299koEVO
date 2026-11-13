<section class="overflow-auto">
    <header><i class="fa-solid fa-receipt"></i> {{ Lang.ecommerce.orders }}</header>

    <div style="margin-bottom: 16px;">
        <a href="{{ ROUTER.generate('admin-ecommerce-home') }}" class="button secondary">
            <i class="fa-solid fa-arrow-left"></i> Tableau de bord
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ Lang.ecommerce.order-number }}</th>
                <th>{{ Lang.ecommerce.customer-name }}</th>
                <th>{{ Lang.ecommerce.total }}</th>
                <th>{{ Lang.ecommerce.order-status }}</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            {% FOR order IN orders %}
            <tr>
                <td>{{ order.getOrderNumber() }}</td>
                <td>{{ order.getCustomerName() }}</td>
                <td><strong>{{ order.getTotal() }} €</strong></td>
                <td>{{ order.getStatus() }}</td>
                <td>{{ order.getCreatedAt() }}</td>
            </tr>
            {% ENDFOR %}
            {% IF not orders %}
            <tr>
                <td colspan="5" style="text-align: center; color: var(--adm-text-muted);">Aucune commande enregistrée.</td>
            </tr>
            {% ENDIF %}
        </tbody>
    </table>
</section>
