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

        <div class="ann-form-grid">
            <p class="ann-field">
                <label for="ann_category_select">Catégorie</label>
                <select id="ann_category_select">
                    <option value="">-- Sans catégorie --</option>
                    <option value="Mobilier">Mobilier</option>
                    <option value="Multimédia">Multimédia</option>
                    <option value="Loisirs">Loisirs</option>
                    <option value="Électroménager">Électroménager</option>
                    <option value="Vêtements &amp; Accessoires">Vêtements &amp; Accessoires</option>
                    <option value="Livres &amp; Culture">Livres &amp; Culture</option>
                    <option value="Bricolage &amp; Jardin">Bricolage &amp; Jardin</option>
                    <option value="Véhicules">Véhicules</option>
                    <option value="Services">Services</option>
                    <option value="Don / Gratuit">Don / Gratuit</option>
                    <option value="__custom__">Autre catégorie…</option>
                </select>
                <input type="text" id="ann_category" name="category" value="{{annonce.category}}" maxlength="100" placeholder="Précisez la catégorie…" style="display: none; margin-top: 6px;" />
            </p>

            <p class="ann-field">
                <label for="ann_condition">État</label>
                <select id="ann_condition" name="item_condition" data-initial="{{annonce.item_condition}}">
                    <option value="">-- Non précisé --</option>
                    <option value="Neuf" {% if annonce.item_condition == 'Neuf' %}selected{% endif %}>Neuf</option>
                    <option value="Comme neuf" {% if annonce.item_condition == 'Comme neuf' %}selected{% endif %}>Comme neuf</option>
                    <option value="Très bon état" {% if annonce.item_condition == 'Très bon état' %}selected{% endif %}>Très bon état</option>
                    <option value="Bon état" {% if annonce.item_condition == 'Bon état' %}selected{% endif %}>Bon état</option>
                    <option value="État correct" {% if annonce.item_condition == 'État correct' %}selected{% endif %}>État correct</option>
                    <option value="Pour pièces / À réparer" {% if annonce.item_condition == 'Pour pièces / À réparer' %}selected{% endif %}>Pour pièces / À réparer</option>
                </select>
            </p>

            <p class="ann-field">
                <label for="ann_location">Localisation (optionnel)</label>
                <input type="text" id="ann_location" name="location" value="{{annonce.location}}" maxlength="150" placeholder="ex: Centre-ville, Retrait sur place" />
            </p>

            <div class="ann-field ann-price-box">
                <label for="ann_price">Prix indicatif <small style="font-weight: 400; opacity: 0.8;">(sans paiement en ligne)</small></label>
                <div class="ann-price-row">
                    <input type="range" id="ann_price_range" min="0" max="1000" step="5" value="0" aria-label="Curseur de prix" />
                    <input type="text" id="ann_price" name="price_label" value="{{annonce.price_label}}" maxlength="100" placeholder="ex: 45 €, Gratuit…" />
                </div>
                <div class="ann-price-chips">
                    <button type="button" class="ann-chip" data-price="Gratuit">Gratuit</button>
                    <button type="button" class="ann-chip" data-price="À débattre">À débattre</button>
                    <button type="button" class="ann-chip" data-price="Faire offre">Faire offre</button>
                    <button type="button" class="ann-chip" data-price="Sur devis">Sur devis</button>
                </div>
            </div>
        </div>

        <div class="ann-form-grid" style="margin-top: 14px;">
            {% if isEdit %}
            <p class="ann-field">
                <label for="ann_status">Statut</label>
                <select id="ann_status" name="status">
                    <option value="available" {% if annonce.status == 'available' %}selected{% endif %}>Disponible (réservable)</option>
                    <option value="reserved" {% if annonce.status == 'reserved' %}selected{% endif %}>Réservée</option>
                    <option value="closed" {% if annonce.status == 'closed' %}selected{% endif %}>Clôturée</option>
                    <option value="draft" {% if annonce.status == 'draft' %}selected{% endif %}>Brouillon (masquée du public)</option>
                </select>
            </p>
            {% else %}
            <p class="ann-field">
                <label for="ann_status">Visibilité initiale</label>
                <select id="ann_status" name="status">
                    <option value="available" selected>Disponible (par défaut)</option>
                    <option value="draft">Brouillon (masquée du public)</option>
                </select>
            </p>
            {% endif %}
            <p class="ann-field">
                <label for="ann_contact">Contact / modalités de retrait (optionnel)</label>
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

<style>
.ann-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 14px;
    align-items: start;
}
.ann-field {
    margin: 0;
}
.ann-field > label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}
.ann-field select,
.ann-field input[type="text"] {
    width: 100%;
    box-sizing: border-box;
}
.ann-price-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ann-price-row input[type="range"] {
    flex: 1 1 120px;
    min-width: 90px;
    accent-color: var(--primary-color, #2563eb);
    cursor: pointer;
}
.ann-price-row input[type="text"] {
    flex: 0 1 130px;
    min-width: 100px;
}
.ann-price-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 7px;
}
.ann-chip {
    padding: 3px 9px;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 999px;
    border: 1px solid var(--border-color, #cbd5e1);
    background: var(--section-head-bg-color, rgba(148, 163, 184, 0.14));
    color: inherit;
    cursor: pointer;
}
.ann-chip:hover {
    border-color: var(--primary-color, #2563eb);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Catégorie : liste déroulante + saisie libre si "Autre" ou valeur historique
    const catSelect = document.getElementById('ann_category_select');
    const catInput = document.getElementById('ann_category');
    if (catSelect && catInput) {
        const initCat = (catInput.value || '').trim();
        const hasPreset = Array.from(catSelect.options).some(o => o.value === initCat && o.value !== '__custom__');
        if (initCat === '') {
            catSelect.value = '';
        } else if (hasPreset) {
            catSelect.value = initCat;
        } else {
            catSelect.value = '__custom__';
            catInput.style.display = 'block';
        }
        catSelect.addEventListener('change', () => {
            if (catSelect.value === '__custom__') {
                catInput.style.display = 'block';
                catInput.focus();
            } else {
                catInput.style.display = 'none';
                catInput.value = catSelect.value;
            }
        });
    }

    // 2. État : préserver une éventuelle valeur personnalisée existante
    const condSelect = document.getElementById('ann_condition');
    if (condSelect) {
        const initCond = (condSelect.dataset.initial || '').trim();
        if (initCond && !Array.from(condSelect.options).some(o => o.value === initCond)) {
            const opt = document.createElement('option');
            opt.value = initCond;
            opt.textContent = initCond;
            opt.selected = true;
            condSelect.appendChild(opt);
        }
    }

    // 3. Prix : curseur + champ texte pour affiner + raccourcis
    const priceRange = document.getElementById('ann_price_range');
    const priceInput = document.getElementById('ann_price');
    const chips = document.querySelectorAll('.ann-chip');

    function syncRangeFromText() {
        if (!priceRange || !priceInput) return;
        const raw = priceInput.value.replace(/\s+/g, '').replace(',', '.');
        const m = raw.match(/(\d+(?:\.\d+)?)/);
        if (m) {
            const val = Math.min(Number(priceRange.max), Math.max(0, Math.round(parseFloat(m[1]))));
            priceRange.value = String(val);
        } else {
            priceRange.value = '0';
        }
    }

    if (priceRange && priceInput) {
        syncRangeFromText();
        priceRange.addEventListener('input', () => {
            const n = parseInt(priceRange.value, 10) || 0;
            priceInput.value = n === 0 ? 'Gratuit' : `${n} €`;
        });
        priceInput.addEventListener('input', syncRangeFromText);
        chips.forEach(btn => {
            btn.addEventListener('click', () => {
                priceInput.value = btn.dataset.price || '';
                syncRangeFromText();
            });
        });
    }
});
</script>

