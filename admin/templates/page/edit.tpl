<form method="post" action="{ ROUTER.generate("page-admin-save") }" enctype="multipart/form-data" onsubmit="console.log('Form submitted to:', this.action); return true;">
    <input type="hidden" name="_csrf" value="{{_csrfToken}}" />
    <section>
        <header>Type d'élément</header>
        <p>
            <label for="targetType">Que souhaitez-vous créer ?</label><br>
            <select id="targetType" name="targetType">
                <option value="page" {% if pageItem.targetIs() == 'page' %}selected{% endif %}>Page statique (Contenu modifiable)</option>
                <option value="plugin" {% if pageItem.targetIs() == 'plugin' %}selected{% endif %}>Lien vers un Plugin (ex: Blog, Forum, Galerie...)</option>
                <option value="url" {% if pageItem.targetIs() == 'url' %}selected{% endif %}>Lien externe (URL personnalisée)</option>
                <option value="parent" {% if pageItem.targetIs() == 'parent' %}selected{% endif %}>Libellé / Dossier (Item non cliquable pour grouper des sous-pages)</option>
            </select>
        </p>

        <!-- Hidden target field that will be updated by JS -->
        <input type="hidden" name="target" id="target" value="{{pageItem.getTarget()}}" />

        <!-- Plugin selector (hidden by default unless type is plugin) -->
        <div id="section-plugin-selector" style="display:none;">
            <p>
                <label for="pluginSelect">Choisir le plugin</label><br>
                <select id="pluginSelect">
                    <option value="">-- Sélectionner --</option>
                    {% for p in activePlugins %}
                        <option value="{{p.id}}" {% if pageItem.getTarget() == p.id or pageItem.getTarget() starts with p.id ~ '-' %}selected{% endif %}>{{p.name}}</option>
                    {% endfor %}
                </select>
            </p>
        </div>

        <!-- Route selector (hidden by default, shown if plugin has public routes) -->
        <div id="section-route-selector" style="display:none; background: #f0f7ff; padding: 10px; border-radius: 4px; border: 1px solid #cce5ff; margin-bottom: 20px;">
            <p style="margin:0;">
                <label for="routeSelect">Action / Page spécifique</label><br>
                <select id="routeSelect" style="width:100%;">
                    <option value="">-- Accueil du plugin (Défaut) --</option>
                </select>
                <small id="routeDescription" style="display:block; margin-top:5px; color:#666;"></small>
            </p>
        </div>

        <!-- URL input (hidden by default unless type is url) -->
        <div id="section-url-input" style="display:none;">
            <p>
                <label for="urlInput">URL du lien</label><br>
                <input type="text" id="urlInput" value="{% if pageItem.targetIs() == 'url' %}{{pageItem.getTarget()}}{% endif %}" placeholder="https://..." />
            </p>
        </div>
    </section>

    <section id="section-settings">
        <input type="hidden" name="id" value="{{pageItem.getId()}}" />
        <input type="hidden" name="isHidden" value="{% if pageItem.getIsHidden() %}1{% else %}0{% endif %}" />
        <input type="hidden" name="showIcon" value="{% if pageItem.getShowIcon() %}1{% else %}0{% endif %}" />
        {{extraHiddenFields}}

        <header>{{Lang.core-settings}}</header>
        <p id="row-homepage">
            <input {% if pageItem.getIsHomepage() %}checked{% endif %} type="checkbox" name="isHomepage" id="isHomepage" />
            <label for="isHomepage">{{Lang.page.homepage}}</label>
        </p>
        <p>
            <label for="icon">Icône (FontAwesome)</label><br>
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="text" name="icon" id="icon" value="{{pageItem.getIcon()}}" placeholder="{% if pluginIcon %}défaut: {{pluginIcon}}{% else %}ex: fa-solid fa-star{% endif %}" style="flex: 1;" />
                <div id="icon-preview" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #f0f0f0; border-radius: 4px; font-size: 20px;">
                    <i class="{{pageItem.getIcon()|default(pluginIcon|default('fa-solid fa-circle-question'))}}"></i>
                </div>
            </div>
            {% if pluginIcon %}<small>Icône du plugin par défaut : <i class="{{pluginIcon}}"></i> <code>{{pluginIcon}}</code></small>{% else %}<small>Laissez vide pour utiliser l'icône par défaut (si plugin) ou aucune icône.</small>{% endif %}
        </p>

        <script>
        document.getElementById('icon').addEventListener('input', function() {
            const preview = document.getElementById('icon-preview');
            const icon = this.value.trim();
            const fallback = '{{pluginIcon|default("fa-solid fa-circle-question")}}';
            preview.innerHTML = `<i class="${icon || fallback}"></i>`;
        });
        </script>
        
        <p>
            <label for="legalType">Type de page légale (Préréglage)</label><br>
            <select name="legalType" id="legalType">
                <option value="none" {% if pageItem.getLegalType() == 'none' %}selected{% endif %}>-- Aucun --</option>
                <option value="cgu" {% if pageItem.getLegalType() == 'cgu' %}selected{% endif %}>CGU (Conditions Générales d'Utilisation)</option>
                <option value="cgv" {% if pageItem.getLegalType() == 'cgv' %}selected{% endif %}>CGV (Conditions Générales de Vente)</option>
                <option value="privacy" {% if pageItem.getLegalType() == 'privacy' %}selected{% endif %}>Politique de Confidentialité</option>
            </select>
            <button type="button" id="applyPreset" style="margin-top: 5px;">Appliquer le préréglage</button>
        </p>
<script>
document.getElementById('applyPreset').addEventListener('click', function() {
    const type = document.getElementById('legalType').value;
    if (type === 'none') {
        alert('Veuillez sélectionner un type de page légale.');
        return;
    }
    
    if (!confirm('Cela remplacera le titre et le contenu actuel. Continuer ?')) {
        return;
    }

    const presets = {
        'cgu': {
            'name': 'CGU',
            'mainTitle': 'Conditions Générales d\'Utilisation',
            'content': '<h2>1. Objet</h2><p>Les présentes CGU ont pour objet de définir les modalités de mise à disposition du site...</p>'
        },
        'cgv': {
            'name': 'CGV',
            'mainTitle': 'Conditions Générales de Vente',
            'content': '<h2>1. Objet</h2><p>Les présentes CGV définissent les conditions applicables aux ventes conclues entre...</p>'
        },
        'privacy': {
            'name': 'Confidentialité',
            'mainTitle': 'Politique de Confidentialité',
            'content': '<h2>Collecte des données</h2><p>Nous collectons les données suivantes : Nom, Prénom, Email...</p>'
        }
    };

    if (presets[type]) {
        document.getElementById('name').value = presets[type].name;
        document.getElementById('mainTitle').value = presets[type].mainTitle;
        if (typeof tinymce !== 'undefined') {
            tinymce.activeEditor.setContent(presets[type].content);
        } else {
            const contentArea = document.querySelector('textarea[name="content"]');
            if (contentArea) contentArea.value = presets[type].content;
        }
    }
});
</script>
        <p>
            <label for="parent">{{Lang.page.parent-item}}</label><br>
            <select name="parent" id="parent">
                <option value="">{{Lang.page.no-parent-option}}</option>
                {% for v in page.getItems() %}
                    {% if v.targetIs() == "parent" %}
                        <option {% if v.getId() == pageItem.getParent() %}selected{% endif %} value="{{v.getId()}}">{{v.getName()}}</option>
                    {% endif %}
                {% endfor %}
            </select>
        </p>
        <p>
            <label for="cssClass">{{Lang.page.css-class}}</label>
            <input type="text" name="cssClass" id="cssClass" value="{{pageItem.getCssClass()}}" />
        </p>
        <p>
            <label for="position">{{Lang.page.position}}</label>
            <input type="number" name="position" id="position" value="{{pageItem.getPosition()}}" />
        </p>
        <p>
            <label for="_password">{{Lang.page.restrict-access-password}}</label>
            <input type="password" name="_password" id="_password" value="" autocomplete="new-password" />
        </p>
        {% if pageItem.getPassword() != "" %}
            <p>
                <input type="checkbox" name="resetPassword" id="resetPassword" /> 
                <label for="resetPassword">{{Lang.page.remove-password-restriction}}</label>
            </p>
        {% endif %}
    </section>

    <section id="section-seo">
        <header>{{Lang.page.seo-heading}}</header>
        <p>
            <input {% if pageItem.getNoIndex() %}checked{% endif %} type="checkbox" name="noIndex" id="noIndex"/>
            <label for="noIndex">{{Lang.page.no-index-checkbox}}</label>
        </p>
        <p>
            <label for="metaTitleTag">{{Lang.page.meta-title}}</label>
            <input type="text" name="metaTitleTag" id="metaTitleTag" value="{{pageItem.getMetaTitleTag()}}" />
        </p>
        <p>
            <label for="metaDescriptionTag">{{Lang.page.meta-description}}</label>
            <input type="text" name="metaDescriptionTag" id="metaDescriptionTag" value="{{pageItem.getMetaDescriptionTag()}}" />
        </p>
    </section>

    <section id="section-content">
        <header>{{Lang.page.content-heading}}</header>
        <p>
            <label for="name">{{Lang.page.name-label}}</label><br>
            <input type="text" name="name" id="name" value="{{pageItem.getName()}}" required="required" />
        </p>
        <div id="row-page-details">
            <p>
                <label for="mainTitle">{{Lang.page.page-title}}</label><br>
                <input type="text" name="mainTitle" id="mainTitle" value="{{pageItem.getMainTitle()}}" />
            </p>
            <p>
                <label for="file">{{Lang.page.include-file}}
                    <select name="file" id="file">
                        <option value="">--</option>
                        {% for file in page.listTemplates() %}
                            <option {% if file == pageItem.getFile() %}selected{% endif %} value="{{file}}">{{file}}</option>
                        {% endfor %}
                    </select>
            </p>
            <p>
                {{contentEditor}}
            </p>
        </div>
    </section>

    <script>
    (function() {
        const typeSelect = document.getElementById('targetType');
        const targetInput = document.getElementById('target');
        const pluginSelector = document.getElementById('section-plugin-selector');
        const pluginSelect = document.getElementById('pluginSelect');
        const routeSelector = document.getElementById('section-route-selector');
        const routeSelect = document.getElementById('routeSelect');
        const routeDescription = document.getElementById('routeDescription');
        const urlInputSection = document.getElementById('section-url-input');
        const urlInput = document.getElementById('urlInput');
        const contentSection = document.getElementById('section-content');
        const pageDetails = document.getElementById('row-page-details');
        const seoSection = document.getElementById('section-seo');
        const rowHomepage = document.getElementById('row-homepage');
        const legalTypeSelect = document.getElementById('legalType').parentElement;

        function updateUI() {
            const type = typeSelect.value;
            
            // Default visibility
            pluginSelector.style.display = 'none';
            urlInputSection.style.display = 'none';
            contentSection.style.display = 'block';
            pageDetails.style.display = 'none';
            seoSection.style.display = 'none';
            rowHomepage.style.display = 'none';
            legalTypeSelect.style.display = 'none';

            if (type === 'page') {
                targetInput.value = '';
                pageDetails.style.display = 'block';
                seoSection.style.display = 'block';
                rowHomepage.style.display = 'block';
                legalTypeSelect.style.display = 'block';
            } else if (type === 'plugin') {
                pluginSelector.style.display = 'block';
                targetInput.value = pluginSelect.value;
                seoSection.style.display = 'block';
            } else if (type === 'url') {
                urlInputSection.style.display = 'block';
                targetInput.value = urlInput.value;
            } else if (type === 'parent') {
                targetInput.value = 'javascript:';
            }
        }

        typeSelect.addEventListener('change', updateUI);
        
        pluginSelect.addEventListener('change', function() {
            if (typeSelect.value === 'plugin') {
                targetInput.value = this.value;
                fetchRoutes(this.value);
            }
        });

        routeSelect.addEventListener('change', function() {
            if (typeSelect.value === 'plugin') {
                targetInput.value = this.value || pluginSelect.value;
                const selectedOption = this.options[this.selectedIndex];
                routeDescription.innerText = selectedOption.getAttribute('data-desc') || '';
            }
        });

        async function fetchRoutes(pluginName) {
            if (!pluginName) {
                routeSelector.style.display = 'none';
                return;
            }

            try {
                // Use Router to generate the dynamic URL with a placeholder
                const ajaxUrlPattern = '{{ ROUTER.generate("page-admin-list-plugin-routes-ajax", ["plugin" => "PLUGIN_NAME"]) }}';
                const url = ajaxUrlPattern.replace('PLUGIN_NAME', pluginName);
                
                const response = await fetch(url);
                const routes = await response.json();

                if (Array.isArray(routes) && routes.length > 0) {
                    routeSelect.innerHTML = '<option value="">-- Accueil du plugin (Défaut) --</option>';
                    routes.forEach(route => {
                        const option = document.createElement('option');
                        option.value = route.route;
                        option.text = route.label;
                        option.setAttribute('data-desc', route.description || '');
                        if (targetInput.value === route.route) {
                            option.selected = true;
                            routeDescription.innerText = route.description || '';
                        }
                        routeSelect.appendChild(option);
                    });
                    routeSelector.style.display = 'block';
                } else {
                    routeSelector.style.display = 'none';
                }
            } catch (error) {
                console.error('Error fetching routes:', error);
                routeSelector.style.display = 'none';
            }
        }

        urlInput.addEventListener('input', function() {
            if (typeSelect.value === 'url') targetInput.value = this.value;
        });

        // Initialize with existing route if any
        if (typeSelect.value === 'plugin' && pluginSelect.value) {
            fetchRoutes(pluginSelect.value);
        }

        // Initialize
        updateUI();
    })();
    </script>


    <!-- Hidden navigation context to preserve multi-context set in list view -->
    <input type="hidden" name="navContext" value="{{pageItem.getNavContext()}}" />

    <!-- Navigation & Visibilité Simplified -->
    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
        <header style="margin-bottom: 10px; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px;">
            Conditions d'affichage</header>

        <div id="conditions-container">
            <input type="hidden" name="conditions" id="conditions" value="{{pageItem.getConditions()|json_encode}}" />

            <div id="conditions-list" style="margin-bottom: 10px;">
                <!-- Conditions rendered here via JS -->
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" class="button tiny secondary" id="add-condition-btn">+ Ajouter une condition</button>
                <button type="button" class="button tiny warning" id="test-conditions-btn">Test Policy (Dry-run)</button>
            </div>
            <div id="test-result" style="margin-top: 10px; padding: 10px; border-radius: 4px; display: none;"></div>
        </div>

        <script>
            (function () {
                const conditionsInput = document.getElementById('conditions');
                const listContainer = document.getElementById('conditions-list');
                const addBtn = document.getElementById('add-condition-btn');
                const testBtn = document.getElementById('test-conditions-btn');
                const testResult = document.getElementById('test-result');

                let providers = {};
                let conditions = [];
                try {
                    conditions = JSON.parse(conditionsInput.value || '[]');
                } catch (e) {
                    conditions = [];
                }

                async function fetchMetadata() {
                    try {
                        const response = await fetch('{ ROUTER.generate("page-admin-list-providers-ajax") }');
                        providers = await response.json();
                        render();
                    } catch (e) {
                        console.error('Failed to fetch providers metadata', e);
                    }
                }

                function render() {
                    listContainer.innerHTML = '';
                    if (conditions.length === 0) {
                        listContainer.innerHTML = '<div style="font-style:italic; color:#999; padding:5px;">Aucune condition (Visible par défaut)</div>';
                    }

                    conditions.forEach((cond, index) => {
                        const provider = providers[cond.type] || { label: cond.type, fields: {} };
                        const row = document.createElement('div');
                        row.style.cssText = 'display: flex; gap: 10px; align-items: start; background: #fff; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);';

                        // 1. Provider Select
                        const leftCol = document.createElement('div');
                        leftCol.style.width = '150px';
                        const typeSelect = document.createElement('select');
                        typeSelect.style.cssText = 'margin:0; width: 100%;';
                        Object.keys(providers).forEach(pid => {
                            const opt = document.createElement('option');
                            opt.value = pid;
                            opt.textContent = providers[pid].label;
                            if (cond.type === pid) opt.selected = true;
                            typeSelect.appendChild(opt);
                        });
                        typeSelect.onchange = (e) => {
                            conditions[index] = { type: e.target.value }; // Reset fields on type change
                            render();
                        };
                        leftCol.appendChild(typeSelect);

                        // 2. Dynamic Fields
                        const midCol = document.createElement('div');
                        midCol.style.cssText = 'flex:1; display:flex; flex-wrap:wrap; gap:10px;';
                        
                        if (provider.fields) {
                            Object.keys(provider.fields).forEach(fname => {
                                const field = provider.fields[fname]; // Use const!
                                const fieldWrapper = document.createElement('div');
                                fieldWrapper.style.minWidth = '120px';
                                
                                const label = document.createElement('label');
                                label.textContent = field.label;
                                label.style.cssText = 'font-size:11px; display:block; color:#777; margin-bottom:2px;';
                                fieldWrapper.appendChild(label);

                                let input;
                                if (field.type === 'select') {
                                    input = document.createElement('select');
                                    input.style.margin = '0';
                                    
                                    const fillOptions = (opts) => {
                                        const currentVal = cond[fname];
                                        input.innerHTML = '<option value="">-- Sélectionner --</option>';
                                        Object.keys(opts).forEach(okey => {
                                            const opt = document.createElement('option');
                                            opt.value = okey;
                                            opt.textContent = opts[okey];
                                            if (currentVal === okey) opt.selected = true;
                                            input.appendChild(opt);
                                        });
                                    };

                                    fillOptions(field.options || {});

                                    // Gestion des dépendances (ex: Colonnes dépendant de Table)
                                    if (field.dependsOn && cond[field.dependsOn]) {
                                        const depVal = cond[field.dependsOn];
                                        console.log(`[ACL] Fetching options for ${fname} (depends on ${field.dependsOn}=${depVal})`);
                                        fetch('{ ROUTER.generate("page-admin-list-providers-ajax") }?table=' + encodeURIComponent(depVal))
                                            .then(r => r.json())
                                            .then(data => {
                                                const currentMeta = data[cond.type];
                                                if (currentMeta && currentMeta.fields[fname] && currentMeta.fields[fname].options) {
                                                    console.log(`[ACL] Filling ${fname} with ${Object.keys(currentMeta.fields[fname].options).length} options`);
                                                    fillOptions(currentMeta.fields[fname].options);
                                                }
                                            })
                                            .catch(err => console.error('[ACL] Fetch error:', err));
                                    }
                                } else if (field.type === 'suggest') {
                                    // Utilisation d'un datalist pour suggérer des variables tout en permettant l'entrée libre
                                    const listId = 'datalist_' + index + '_' + fname;
                                    const datalist = document.createElement('datalist');
                                    datalist.id = listId;
                                    Object.keys(field.options || {}).forEach(okey => {
                                        const opt = document.createElement('option');
                                        opt.value = okey;
                                        opt.textContent = field.options[okey];
                                        datalist.appendChild(opt);
                                    });
                                    fieldWrapper.appendChild(datalist);

                                    input = document.createElement('input');
                                    input.type = 'text';
                                    input.setAttribute('list', listId);
                                    input.value = cond[fname] || '';
                                    input.placeholder = field.placeholder || '';
                                    input.style.margin = '0';
                                } else if (field.type === 'boolean') {
                                    input = document.createElement('select');
                                    input.style.margin = '0';
                                    input.innerHTML = '<option value="1">Oui</option><option value="0">Non</option>';
                                    input.value = (cond[fname] === true || cond[fname] === "1") ? "1" : "0";
                                } else {
                                    input = document.createElement('input');
                                    input.type = 'text';
                                    input.value = cond[fname] || '';
                                    input.placeholder = field.placeholder || '';
                                    input.style.margin = '0';
                                }

                                input.onchange = (e) => {
                                    let val = e.target.value;
                                    if (field.type === 'boolean') val = (val === "1");
                                    updateCondition(index, fname, val);
                                    
                                    if (field.dynamic_reload) {
                                        render(); 
                                    }
                                };
                                fieldWrapper.appendChild(input);
                                midCol.appendChild(fieldWrapper);
                            });
                        }

                        // 3. Remove Btn
                        const rightCol = document.createElement('div');
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
                        removeBtn.style.cssText = 'background:#fde8e8; color:#e02424; border:1px solid #f8b4b4; border-radius:3px; padding: 5px 10px; cursor:pointer; margin-top:15px;';
                        removeBtn.onclick = () => removeCondition(index);
                        rightCol.appendChild(removeBtn);

                        row.appendChild(leftCol);
                        row.appendChild(midCol);
                        row.appendChild(rightCol);
                        listContainer.appendChild(row);
                    });
                    
                    conditionsInput.value = JSON.stringify(conditions);
                }

                function updateCondition(index, key, value) {
                    conditions[index][key] = value;
                    conditionsInput.value = JSON.stringify(conditions);
                }

                function removeCondition(index) {
                    conditions.splice(index, 1);
                    render();
                }

                addBtn.onclick = () => {
                    const firstProvider = Object.keys(providers)[0] || 'auth';
                    conditions.push({ type: firstProvider });
                    render();
                };

                testBtn.onclick = async () => {
                    testResult.style.display = 'block';
                    testResult.style.background = '#f3f4f6';
                    testResult.textContent = 'Évaluation en cours...';

                    try {
                        const formData = new FormData();
                        formData.append('conditions', JSON.stringify(conditions));
                        formData.append('_csrf', '{{_csrfToken}}');

                        const response = await fetch('{ ROUTER.generate("page-admin-explain-policy-ajax") }', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        if (data.allowed) {
                            testResult.style.background = '#def7ec';
                            testResult.style.color = '#03543f';
                            testResult.innerHTML = `<strong>Accès Autorisé</strong> pour votre rôle actuel (<code>${data.user_role}</code>).`;
                        } else {
                            testResult.style.background = '#fde8e8';
                            testResult.style.color = '#9b1c1c';
                            let html = `<strong>Accès Refusé</strong> pour votre rôle actuel (<code>${data.user_role}</code>).`;
                            if (data.trace && data.trace.length > 0) {
                                html += '<ul style="margin-top:10px; padding-left:20px; font-size:12px;">';
                                data.trace.forEach(err => {
                                    html += `<li>${err}</li>`;
                                });
                                html += '</ul>';
                            }
                            testResult.innerHTML = html;
                        }
                    } catch (e) {
                        testResult.style.background = '#FEF3C7';
                        testResult.textContent = "Erreur lors du test.";
                    }
                };

                // Init
                fetchMetadata();
            })();
        </script>
    </div>

    {% for section in extraSections %}
        {{section}}
    {% endfor %}
    <p>
        <button type="submit" class="button success">{{Lang.submit}}</button>
    </p>
</form>
