<h1>{{ Lang.ecommerce.cart }}</h1>

{% IF items %}
<table class="ecommerce-cart-table">
    <thead>
        <tr>
            <th>Produit</th>
            <th>Prix</th>
            <th>Quantité</th>
            <th>Sous-total</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        {% FOR item IN items %}
        <tr>
            <td>{{ item.product.getName() }}</td>
            <td>{{ item.product.getPrice() }}€</td>
            <td>
                <input type="number" value="{{ item.quantity }}" min="1" data-product-id="{{ item.product.getId() }}" class="cart-quantity">
            </td>
            <td>{{ item.subtotal }}€</td>
            <td><a href="/cart/remove/{{ item.product.getId() }}">Supprimer</a></td>
        </tr>
        {% ENDFOR %}
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3"><strong>{{ Lang.ecommerce.subtotal }}</strong></td>
            <td><strong>{{ subtotal }}€</strong></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3"><strong>{{ Lang.ecommerce.shipping }}</strong></td>
            <td><strong>{% IF shipping == 0 %}{{ Lang.ecommerce.free-shipping }}{% ELSE %}{{ shipping }}€{% ENDIF %}</strong></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3"><strong>{{ Lang.ecommerce.total }}</strong></td>
            <td><strong>{{ total }}€</strong></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<a href="/checkout" class="btn btn-primary">{{ Lang.ecommerce.checkout }}</a>
{% ELSE %}
<p>{{ Lang.ecommerce.cart-empty }}</p>
<a href="/shop" class="btn">{{ Lang.ecommerce.shop }}</a>
{% ENDIF %}

<script>
document.querySelectorAll('.cart-quantity').forEach(input => {
    input.addEventListener('change', function() {
        const productId = this.dataset.productId;
        const quantity = parseInt(this.value);
        fetch('/cart/update', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `product_id=${productId}&quantity=${quantity}`
        }).then(() => location.reload());
    });
});
</script>

