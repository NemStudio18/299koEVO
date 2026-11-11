<div class="admin-content">
    <h1>{{ Lang.ecommerce.shop }}</h1>
    
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3>{{ Lang.ecommerce.orders }}</h3>
            <p style="font-size: 2em; font-weight: bold; color: #2c3e50;">{{ stats.total_orders }}</p>
            <small style="color: #7f8c8d;">{{ stats.pending_orders }} en attente</small>
        </div>
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3>{{ Lang.ecommerce.products }}</h3>
            <p style="font-size: 2em; font-weight: bold; color: #2c3e50;">{{ stats.total_products }}</p>
            <small style="color: #7f8c8d;">{{ stats.active_products }} actifs</small>
        </div>
    </div>
    
    <h2>Commandes récentes</h2>
    {% IF recentOrders %}
    <table class="table">
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
                <td>{{ order.getTotal() }}€</td>
                <td>{{ order.getStatus() }}</td>
                <td>{{ order.getCreatedAt() }}</td>
            </tr>
            {% ENDFOR %}
        </tbody>
    </table>
    {% ELSE %}
    <p>Aucune commande récente</p>
    {% ENDIF %}
</div>

