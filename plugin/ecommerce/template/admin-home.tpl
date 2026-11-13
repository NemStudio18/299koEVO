<section class="overflow-auto">
    <header><i class="fa-solid fa-store"></i> {{ Lang.ecommerce.shop }}</header>

    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
        <a href="{{ ROUTER.generate('admin-ecommerce-products') }}" class="button">
            <i class="fa-solid fa-box-open"></i> {{ Lang.ecommerce.products }}
        </a>
        <a href="{{ ROUTER.generate('admin-ecommerce-orders') }}" class="button secondary">
            <i class="fa-solid fa-receipt"></i> {{ Lang.ecommerce.orders }}
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 10px; padding: 20px; text-align: center;">
            <strong style="font-size: 2rem; color: var(--adm-text-strong);">{{ stats.total_orders }}</strong><br>
            <span style="color: var(--adm-text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600;">{{ Lang.ecommerce.orders }}</span><br>
            <small style="color: var(--adm-text-muted);">{{ stats.pending_orders }} en attente</small>
        </div>
        <div style="background: var(--evo-card-bg,#fff); border: 1px solid var(--adm-border); border-radius: 10px; padding: 20px; text-align: center;">
            <strong style="font-size: 2rem; color: var(--adm-text-strong);">{{ stats.total_products }}</strong><br>
            <span style="color: var(--adm-text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600;">{{ Lang.ecommerce.products }}</span><br>
            <small style="color: var(--adm-text-muted);">{{ stats.active_products }} actifs</small>
        </div>
    </div>

    <h3>Commandes récentes</h3>
    {% IF recentOrders %}
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
            {% FOR order IN recentOrders %}
            <tr>
                <td>{{ order.getOrderNumber() }}</td>
                <td>{{ order.getCustomerName() }}</td>
                <td><strong>{{ order.getTotal() }} €</strong></td>
                <td>{{ order.getStatus() }}</td>
                <td>{{ order.getCreatedAt() }}</td>
            </tr>
            {% ENDFOR %}
        </tbody>
    </table>
    {% ELSE %}
    <p style="color: var(--adm-text-muted);">Aucune commande pour le moment.</p>
    {% ENDIF %}
</section>
