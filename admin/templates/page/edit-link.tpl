<section>
    <header>{{Lang.page.edit-link}}</header>
    <form method="post" action="{ ROUTER.generate("page-admin-save") }" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="{{_csrfToken}}" />
        <input type="hidden" name="id" value="{{pageItem.getId()}}" />
        <input type="hidden" name="isHidden" value="{% if pageItem.getIsHidden() %}1{% else %}0{% endif %}" />
        <input type="hidden" name="navContext" value="{{pageItem.getNavContext()}}" />
        <input type="hidden" name="showIcon" value="{% if pageItem.getShowIcon() %}1{% else %}0{% endif %}" />
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
            <label for="name">{{Lang.page.name-label}}</label><br>
            <input type="text" name="name" id="name" value="{{pageItem.getName()}}" required="required" />
        </p>
        {% if pageItem.targetIs() == "plugin" %}
            <p>
                <label for="target">{{Lang.page.target}} : {{pageItem.getTarget()}}</label>
                <input style="display:none;" type="text" name="target" id="target" value="{{pageItem.getTarget()}}" />
            </p>
        {% else %}
            <p>
                <label for="target">{{Lang.page.target}}</label><br>
                <input placeholder="{{Lang.page.example}} : https://299ko.flexcb.fr" {% if pageItem.targetIs() == "plugin" %}readonly{% endif %} type="url" name="target" id="target" value="{{pageItem.getTarget()}}" required="required" />
            </p>
        {% endif %}
        <p>
            <label for="targetAttr">{{Lang.page.open}}</label><br>
            <select name="targetAttr" id="targetAttr">
                <option value="_self" {% if pageItem.getTargetAttr() == "_self" %}selected{% endif %}>{{Lang.page.open-same-window}}</option>
                <option value="_blank" {% if pageItem.getTargetAttr() == "_blank" %}selected{% endif %}>{{Lang.page.open-new-window}}</option>
            </select>
        </p>
        <p>
            <label for="cssClass">{{Lang.page.css-class}}</label>
            <input type="text" name="cssClass" id="cssClass" value="{{pageItem.getCssClass()}}" />
        </p>
        <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
            <header style="margin-bottom: 10px; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                Conditions d'affichage</header>

            <div id="conditions-container">
                <input type="hidden" name="conditions" id="conditions" value="{{pageItem.getConditions()|json_encode}}" />
                <div id="conditions-list" style="margin-bottom: 10px;"></div>
                <button type="button" class="button tiny secondary" id="add-condition-btn">+ Ajouter une condition</button>
            </div>

            <script>
                (function () {
                    const conditionsInput = document.getElementById('conditions');
                    const listContainer = document.getElementById('conditions-list');
                    const addBtn = document.getElementById('add-condition-btn');

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
                                                });
                                        }
                                    } else if (field.type === 'suggest') {
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
                                        if (field.dynamic_reload) render(); 
                                    };
                                    fieldWrapper.appendChild(input);
                                    midCol.appendChild(fieldWrapper);
                                });
                            }

                            // 3. Remove Btn
                            const rightCol = document.createElement('div');
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.innerHTML = '×';
                            removeBtn.style.cssText = 'background:#fde8e8; color:#e02424; border:1px solid #f8b4b4; border-radius:3px; padding: 5px 10px; cursor:pointer; margin-top:15px; font-weight:bold;';
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
                        const firstType = Object.keys(providers)[0] || 'auth';
                        conditions.push({ type: firstType });
                        render();
                    };

                    fetchMetadata();
                })();
            </script>
        </div>
        <p>
            <button type="submit" class="button success radius">{{Lang.submit}}</button>
            <a href="{ ROUTER.generate("page-admin-home") }" class="button secondary">
                <i class="fa-solid fa-arrow-left"></i> {{Lang.page.back-to-list}}
            </a>
        </p>
    </form>
</section>
