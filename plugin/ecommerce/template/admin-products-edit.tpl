<section class="overflow-auto">
    <header>
        <i class="fa-solid fa-box-open"></i>
        {% IF product %}Modifier le produit{% ELSE %}Ajouter un produit{% ENDIF %}
    </header>

    <div style="margin-bottom: 16px;">
        <a href="{{ ROUTER.generate('admin-ecommerce-products') }}" class="button secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour aux produits
        </a>
    </div>

    <form method="post" action="{% IF product %}{{ ROUTER.generate('admin-ecommerce-products-edit', {'id': product.getId()}) }}{% ELSE %}{{ ROUTER.generate('admin-ecommerce-products-add') }}{% ENDIF %}">
        <input type="hidden" name="token" value="{{ token }}" />
        {% IF product %}
        <input type="hidden" name="id" value="{{ product.getId() }}" />
        {% ENDIF %}

        <p>
            <label for="prod_name">Nom du produit *</label><br>
            <input type="text" id="prod_name" name="name" value="{% IF product %}{{ product.getName() }}{% ENDIF %}" required style="width: 100%; max-width: 500px;" />
        </p>

        <p>
            <label for="prod_desc">Description</label><br>
            <textarea id="prod_desc" name="description" rows="5" style="width: 100%; max-width: 500px;">{% IF product %}{{ product.getDescription() }}{% ENDIF %}</textarea>
        </p>

        <p>
            <label for="prod_price">Prix (€) *</label><br>
            <input type="number" id="prod_price" name="price" step="0.01" min="0" value="{% IF product %}{{ product.getPrice() }}{% ELSE %}0.00{% ENDIF %}" required style="width: 150px;" />
        </p>

        <p>
            <label for="prod_stock">Stock</label><br>
            <input type="number" id="prod_stock" name="stock" min="0" value="{% IF product %}{{ product.getStock() }}{% ELSE %}0{% ENDIF %}" style="width: 150px;" />
        </p>

        <p>
            <label for="prod_image">URL de l'image</label><br>
            <input type="text" id="prod_image" name="image" value="{% IF product %}{{ product.getImage() }}{% ENDIF %}" placeholder="/data/upload/..." style="width: 100%; max-width: 500px;" />
        </p>

        <p>
            <label>
                <input type="checkbox" name="active" value="1" {% IF not product || product.isActive() %}checked{% ENDIF %} />
                Produit actif (visible en boutique)
            </label>
        </p>

        <p>
            <button type="submit" class="button success">
                <i class="fa-solid fa-floppy-disk"></i> Enregistrer
            </button>
        </p>
    </form>
</section>
