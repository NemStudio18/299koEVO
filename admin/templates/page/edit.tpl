<form method="post" action="{ ROUTER.generate("page-admin-save") }" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="{{_csrfToken}}" />
    <input type="hidden" name="target" value="{{pageItem.getTarget()}}" />
    <input type="hidden" name="id" value="{{pageItem.getId()}}" />
    {{extraHiddenFields}}

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
                <label for="file">{{Lang.page.include-file}}</label>
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

    <section id="section-settings">
        <header>{{Lang.core-settings}}</header>
        <p>
            <label for="isHidden">Statut de publication</label><br>
            <select name="isHidden" id="isHidden">
                <option value="0" {% if not pageItem.getIsHidden() %}selected{% endif %}>Publiée (visible sur le site)</option>
                <option value="1" {% if pageItem.getIsHidden() %}selected{% endif %}>Brouillon (masquée et retirée des menus)</option>
            </select>
        </p>
        <p id="row-homepage">
            <input {% if pageItem.getIsHomepage() %}checked{% endif %} type="checkbox" name="isHomepage" id="isHomepage" />
            <label for="isHomepage">{{Lang.page.homepage}}</label>
        </p>
        {% if not pageItem.getId() %}
        <p style="background: #eff6ff; padding: 10px 12px; border-radius: 6px; border: 1px solid #bfdbfe;">
            <input type="checkbox" name="addToMainMenu" id="addToMainMenu" value="1" checked />
            <label for="addToMainMenu"><strong>Ajouter automatiquement un lien dans le menu principal</strong></label><br>
            <small style="color: #475569;">Vous pourrez ensuite le déplacer ou le retirer depuis <a href="/admin/menus">Menus</a>.</small>
        </p>
        {% endif %}

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
                if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                    tinymce.activeEditor.setContent(presets[type].content);
                } else {
                    const contentArea = document.querySelector('textarea[name="content"]');
                    if (contentArea) contentArea.value = presets[type].content;
                }
            }
        });
        </script>

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

    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
        <header style="margin-bottom: 10px; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px;">
            Conditions d'accès à la page
        </header>

        <div id="conditions-container">
            <input type="hidden" name="conditions" id="conditions" value="{{pageItem.getConditions()|json_encode}}" />
            <div id="conditions-list" style="margin-bottom: 10px;"></div>
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
                        listContainer.innerHTML = '<div style="font-style:italic; color:#999; padding:5px;">Aucune condition (Page accessible publiquement)</div>';
                    }

                    conditions.forEach((cond, index) => {
                        const provider = providers[cond.type] || { label: cond.type, fields: {} };
                        const row = document.createElement('div');
                        row.style.cssText = 'display: flex; gap: 10px; align-items: start; background: #fff; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px;';

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
                            conditions[index] = { type: e.target.value };
                            render();
                        };
                        leftCol.appendChild(typeSelect);

                        const midCol = document.createElement('div');
                        midCol.style.cssText = 'flex:1; display:flex; flex-wrap:wrap; gap:10px;';

                        if (provider.fields) {
                            Object.keys(provider.fields).forEach(fname => {
                                const field = provider.fields[fname];
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
                                    if (field.dependsOn && cond[field.dependsOn]) {
                                        const depVal = cond[field.dependsOn];
                                        fetch('{ ROUTER.generate("page-admin-list-providers-ajax") }?table=' + encodeURIComponent(depVal))
                                            .then(r => r.json())
                                            .then(data => {
                                                const currentMeta = data[cond.type];
                                                if (currentMeta && currentMeta.fields[fname] && currentMeta.fields[fname].options) {
                                                    fillOptions(currentMeta.fields[fname].options);
                                                }
                                            })
                                            .catch(() => {});
                                    }
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
                                    if (field.dynamic_reload) render();
                                };
                                fieldWrapper.appendChild(input);
                                midCol.appendChild(fieldWrapper);
                            });
                        }

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
                            testResult.innerHTML = `<strong>Accès Refusé</strong> pour votre rôle actuel (<code>${data.user_role}</code>).`;
                        }
                    } catch (e) {
                        testResult.style.background = '#FEF3C7';
                        testResult.textContent = "Erreur lors du test.";
                    }
                };

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
