<h1>{{ Lang.ecommerce.products }}</h1>

<a href="{{ ROUTER.generate('admin-ecommerce-products-add') }}" class="btn">Ajouter un produit</a>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>{{ Lang.ecommerce.price }}</th>
            <th>{{ Lang.ecommerce.stock }}</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        {% FOR product IN products %}
        <tr>
            <td>{{ product.getId() }}</td>
            <td>{{ product.getName() }}</td>
            <td>{{ product.getPrice() }}€</td>
            <td>{{ product.getStock() }}</td>
            <td>
                <a href="{{ ROUTER.generate('admin-ecommerce-products-edit', {'id': product.getId()}) }}">Modifier</a>
                <a href="{{ ROUTER.generate('admin-ecommerce-products-delete', {'id': product.getId()}) }}" onclick="return confirm('Supprimer ce produit ?')">Supprimer</a>
            </td>
        </tr>
        {% ENDFOR %}
    </tbody>
</table>

