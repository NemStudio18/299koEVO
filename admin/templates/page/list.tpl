<section>
    <header>{{ Lang.page.page-list }}</header>
    <a class="button" href="{{ ROUTER.generate("page-admin-new") }}">{{ Lang.page.add-page }}</a>
    <a class="button" href="{{ ROUTER.generate("page-admin-new-parent") }}">{{ Lang.page.add-parent-item }}</a>
    <a class="button" href="{{ ROUTER.generate("page-admin-new-link") }}">{{ Lang.page.add-external-link }}</a>
    {% if lost != "" %}
        <p>{{ Lang.page.ghost-pages-found }} <a href="{{ ROUTER.generate("page-admin-maintenance", ["id" => lost, "token" => token]) }}">{{ Lang.page.click-here }}</a> {{ Lang.page.to-execute-maintenance-script }}</p>
    {% endif %}
    <p class="sortable-hint" style="margin: 10px 0; padding: 8px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
        <i class="fa-solid fa-info-circle"></i> {{ Lang.page.drag-to-reorder }}
    </p>
    <table>
        <thead>
            <tr>
                <th style="width: 30px;"><i class="fa-solid fa-grip-vertical" style="color: #999;"></i></th>
                <th>{{ Lang.page.page-name }}</th>
                <th>{{ Lang.page.address }}</th>
                <th>{{ Lang.page.position }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="pages-sortable">
            {% for pageItem in page.getItems() %}
                {% if pageItem.getParent() == false && pageItem.isVisibleOnList() %}
                    <tr data-page-id="{{ pageItem.getId() }}" class="sortable-row">
                        <td class="drag-handle" style="cursor: move; text-align: center;"><i class="fa-solid fa-grip-vertical" style="color: #999;"></i></td>
                        <td>{{ pageItem.getName() }}</td>
                        <td>{% if pageItem.targetIs() != "parent" %}<input readonly="readonly" type="text" value="{{ page.makeUrl(pageItem) }}" />{% endif %}</td>
                        <td>
                            <span class="position-display">{{ pageItem.getPosition() }}</span>
                            <span class="legacy-controls" style="display: none;">
                                <a class="up" href="{{ ROUTER.generate("page-admin-page-up", ["id" => pageItem.getId() , "token" => token]) }}"><i class="fa-regular fa-circle-up" title="{{ Lang.page.move-up }}"></i></a>
                                <a class="down" href="{{ ROUTER.generate("page-admin-page-down", ["id" => pageItem.getId() , "token" => token]) }}"><i class="fa-regular fa-circle-down" title="{{ Lang.page.move-down }}"></i></a>
                            </span>
                        </td>
                        <td>
                            <div role="group">
                                <a class="button" href="{{ ROUTER.generate("page-admin-edit", ["id" => pageItem.getId()]) }}">{{ Lang.edit }}</a> 
                                {% if pageItem.getIsHomepage() == false && pageItem.targetIs() != "plugin" %}<a class="button alert" href="{{ ROUTER.generate("page-admin-delete", ["id" => pageItem.getId(), "token" => token]) }}" onclick = "if (!confirm('{{ Lang.confirm.deleteItem }}'))
                                                                            return false;">{{ Lang.delete }}</a>{% endif %}	
                            </div>
                        </td>
                    </tr>
                    {% for pageItemChild in page.getItems() %}
                        {% if pageItemChild.getParent() == pageItem.getId() && pageItemChild.isVisibleOnList() %}
                            <tr data-page-id="{{ pageItemChild.getId() }}" data-parent-id="{{ pageItem.getId() }}" class="sortable-row sortable-child">
                                <td class="drag-handle" style="cursor: move; text-align: center;"><i class="fa-solid fa-grip-vertical" style="color: #999;"></i></td>
                                <td>▸ {{ pageItemChild.getName() }}</td>
                                <td><input readonly="readonly" type="text" value="{{ page.makeUrl(pageItemChild) }}" /></td>
                                <td>
                                    <span class="position-display">{{ pageItemChild.getPosition() }}</span>
                                    <span class="legacy-controls" style="display: none;">
                                        <a class="up" href="{{ ROUTER.generate("page-admin-page-up", ["id" => pageItemChild.getId(), "token" => token]) }}"><i class="fa-regular fa-circle-up" title="{{ Lang.page.move-up }}"></i></a>
                                        <a class="down" href="{{ ROUTER.generate("page-admin-page-down", ["id" => pageItemChild.getId(), "token" => token]) }}"><i class="fa-regular fa-circle-down" title="{{ Lang.page.move-down }}"></i></a>
                                    </span>
                                </td>
                                <td>
                                    <div role="group">
                                        <a class="button" href="{{ ROUTER.generate("page-admin-edit", ["id" => pageItemChild.getId()]) }}">{{ Lang.edit }}</a> 
                                        {% if pageItemChild.getIsHomepage() == false && pageItemChild.targetIs() != "plugin" %}<a class="button alert" href="{{ ROUTER.generate("page-admin-delete", ["id" => pageItemChild.getId(), "token" => token]) }}" onclick = "if (!confirm('{{ Lang.confirm.deleteItem }}'))
                                                                        return false;">{{ Lang.delete }}</a>{% endif %}	
                                    </div>
                                </td>
                            </tr>
                        {% endif %}
                    {% endfor %}
                {% endif %}
            {% endfor %}
        </tbody>
    </table>
</section>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function() {
    // Traductions passées depuis PHP (JSON valide, échappé)
    const translations = JSON.parse('{{ translationsJson }}');
    
    const tbody = document.getElementById('pages-sortable');
    if (!tbody) return;
    
    // Séparer les pages parentes et enfants
    const parentRows = Array.from(tbody.querySelectorAll('tr.sortable-row:not(.sortable-child)'));
    const childRows = Array.from(tbody.querySelectorAll('tr.sortable-child'));
    
    // Créer un groupe pour les pages parentes uniquement
    const parentSortable = new Sortable(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        filter: '.sortable-child', // Empêcher le drag des enfants
        onEnd: function(evt) {
            saveOrder();
        }
    });
    
    // Stocker le token CSRF dans une variable pour pouvoir le mettre à jour
    let currentCsrfToken = '{{ _csrfToken }}';
    
    // Fonction pour sauvegarder l'ordre
    function saveOrder() {
        const rows = Array.from(tbody.querySelectorAll('tr.sortable-row:not(.sortable-child)'));
        const order = rows.map(row => parseInt(row.getAttribute('data-page-id')));
        
        if (order.length === 0) return;
        
        // Afficher un indicateur de chargement
        const hint = document.querySelector('.sortable-hint');
        const originalText = hint.innerHTML;
        hint.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + translations.savingOrder;
        hint.style.background = '#fff3cd';
        hint.style.borderLeftColor = '#ffc107';
        
        // Envoyer la requête AJAX
        const formData = new FormData();
        formData.append('order', JSON.stringify(order));
        formData.append('_csrf', currentCsrfToken);
        
        fetch('{{ ROUTER.generate("page-admin-save-order") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Expected JSON but got:', text.substring(0, 200));
                    throw new Error('Response is not JSON');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Mettre à jour le token CSRF si fourni dans la réponse
                if (data.csrfToken) {
                    currentCsrfToken = data.csrfToken;
                }
                
                hint.innerHTML = '<i class="fa-solid fa-check-circle"></i> ' + translations.orderSaved;
                hint.style.background = '#d4edda';
                hint.style.borderLeftColor = '#28a745';
                
                // Mettre à jour les numéros de position affichés
                rows.forEach((row, index) => {
                    const positionDisplay = row.querySelector('.position-display');
                    if (positionDisplay) {
                        positionDisplay.textContent = index + 1;
                    }
                });
                
                // Masquer le message après 3 secondes
                setTimeout(() => {
                    hint.innerHTML = originalText;
                    hint.style.background = '#e3f2fd';
                    hint.style.borderLeftColor = '#2196f3';
                }, 3000);
            } else {
                hint.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + (data.error || translations.orderNotSaved);
                hint.style.background = '#f8d7da';
                hint.style.borderLeftColor = '#dc3545';
                
                setTimeout(() => {
                    hint.innerHTML = originalText;
                    hint.style.background = '#e3f2fd';
                    hint.style.borderLeftColor = '#2196f3';
                }, 5000);
            }
        })
        .catch(error => {
            console.error('Error saving order:', error);
            hint.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + translations.orderNotSaved;
            hint.style.background = '#f8d7da';
            hint.style.borderLeftColor = '#dc3545';
            
            setTimeout(() => {
                hint.innerHTML = originalText;
                hint.style.background = '#e3f2fd';
                hint.style.borderLeftColor = '#2196f3';
            }, 5000);
        });
    }
    
    // Style pour le drag
    const style = document.createElement('style');
    style.textContent = `
        .sortable-ghost {
            opacity: 0.4;
            background: #f0f0f0;
        }
        .sortable-chosen {
            cursor: grabbing !important;
        }
        .sortable-row {
            transition: background-color 0.2s;
        }
        .sortable-row:hover {
            background-color: #f9f9f9;
        }
        .drag-handle:hover {
            color: #2196f3 !important;
        }
    `;
    document.head.appendChild(style);
})();
</script>
