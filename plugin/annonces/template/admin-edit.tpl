<section class="module-card">
    <header>
        <i class="fa-solid fa-tags"></i>
        {% if isEdit %}{{Lang.annonces.edit}}{% else %}{{Lang.annonces.add}}{% endif %}
    </header>

    <div class="module-actions" style="margin-bottom: 1rem;">
        <a href="{{backUrl}}" class="button"><i class="fa-solid fa-arrow-left"></i> Retour à la liste</a>
    </div>

    <form method="post" action="{{saveUrl}}" enctype="multipart/form-data">
        <input type="hidden" name="token" value="{{token}}" />
        <input type="hidden" name="_csrf" value="{{_csrfToken}}" />
        <input type="hidden" name="id" value="{{annonce.id}}" />

        <p>
            <label for="ann_title">Titre de l'annonce *</label><br>
            <input type="text" id="ann_title" name="title" value="{{annonce.title}}" required maxlength="255" placeholder="ex: Vélo de ville en très bon état" />
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <p style="margin: 0;">
                <label for="ann_category">Catégorie</label><br>
                <input type="text" id="ann_category" name="category" value="{{annonce.category}}" maxlength="100" list="ann_categories_suggest" placeholder="ex: Mobilier, Multimédia, Don…" />
                <datalist id="ann_categories_suggest">
                    <option value="Mobilier" />
                    <option value="Multimédia" />
                    <option value="Loisirs" />
                    <option value="Électroménager" />
                    <option value="Services" />
                    <option value="Don / Gratuit" />
                </datalist>
            </p>
            <p style="margin: 0;">
                <label for="ann_price">Prix indicatif (aucun paiement en ligne)</label><br>
                <input type="text" id="ann_price" name="price_label" value="{{annonce.price_label}}" maxlength="100" placeholder="ex: 45 €, Gratuit, À débattre" />
            </p>
            <p style="margin: 0;">
                <label for="ann_condition">État</label><br>
                <input type="text" id="ann_condition" name="item_condition" value="{{annonce.item_condition}}" maxlength="80" placeholder="ex: Très bon état, Neuf" />
            </p>
            <p style="margin: 0;">
                <label for="ann_location">Localisation (optionnel)</label><br>
                <input type="text" id="ann_location" name="location" value="{{annonce.location}}" maxlength="150" placeholder="ex: Centre-ville, Retrait sur place" />
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-top: 14px;">
            <p style="margin: 0;">
                <label for="ann_status">Statut</label><br>
                <select id="ann_status" name="status">
                    <option value="available" {% if annonce.status == 'available' %}selected{% endif %}>Disponible (réservable)</option>
                    <option value="reserved" {% if annonce.status == 'reserved' %}selected{% endif %}>Réservée</option>
                    <option value="closed" {% if annonce.status == 'closed' %}selected{% endif %}>Clôturée</option>
                    <option value="draft" {% if annonce.status == 'draft' %}selected{% endif %}>Brouillon (masquée du public)</option>
                </select>
            </p>
            <p style="margin: 0;">
                <label for="ann_contact">Contact / modalités de retrait (optionnel)</label><br>
                <input type="text" id="ann_contact" name="contact_info" value="{{annonce.contact_info}}" maxlength="255" placeholder="ex: Retrait le samedi matin uniquement" />
            </p>
        </div>

        <p style="margin-top: 14px;">
            <label for="ann_image">Image (URL ou chemin, ou téléversez un fichier ci-dessous)</label><br>
            <input type="text" id="ann_image" name="image" value="{{annonce.image}}" maxlength="255" placeholder="/data/upload/annonces/..." />
            <input type="file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp" style="margin-top: 8px;" />
        </p>

        <p>
            <label for="ann_desc">Description complète</label><br>
            <textarea id="ann_desc" name="description" class="editor" rows="8">{{annonce.description}}</textarea>
        </p>

        <p>
            <input type="checkbox" id="ann_featured" name="is_featured" {% if annonce.is_featured %}checked{% endif %} />
            <label for="ann_featured">Mettre cette annonce en avant en haut de la liste</label>
        </p>

        <p>
            <button type="submit" class="button success"><i class="fa-solid fa-save"></i> Enregistrer l'annonce</button>
        </p>
    </form>
</section>
