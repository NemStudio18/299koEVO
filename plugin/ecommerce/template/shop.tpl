<h1>{{ Lang.ecommerce.shop }}</h1>

<div class="ecommerce-shop">
    {% FOR product IN products %}
    <div class="ecommerce-product-card">
        {% IF product.getImage() %}
        <img src="{{ product.getImage() }}" alt="{{ product.getName() }}" class="ecommerce-product-image">
        {% ENDIF %}
        <h3><a href="{{ product.getUrl() }}">{{ product.getName() }}</a></h3>
        <p class="ecommerce-product-price">{{ product.getPrice() }}€</p>
        {% IF product.isInStock() %}
        <p style="color: green;">{{ Lang.ecommerce.in-stock }}</p>
        <a href="{{ product.getUrl() }}" class="btn">{{ Lang.ecommerce.add-to-cart }}</a>
        {% ELSE %}
        <p style="color: red;">{{ Lang.ecommerce.out-of-stock }}</p>
        {% ENDIF %}
    </div>
    {% ENDFOR %}
</div>

