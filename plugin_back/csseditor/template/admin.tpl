<section>
    <header>{{ Lang.csseditor.title }}</header>
    
    <form method="post" action="{{ ROUTER.generate("csseditor-admin-save") }}" id="csseditorForm">
        {{ SHOW.tokenField }}
        
        <!-- Boutons d'action principaux (hors du bloc scrollable) -->
        <div class="main-actions">
            <div class="action-group">
                <button type="button" class="button" onclick="addCustomVar()">
                    <i class="fa-solid fa-plus"></i> {{ Lang.csseditor.add-variable }}
                </button>
                <button type="button" class="button" onclick="resetAllVars()">
                    <i class="fa-solid fa-undo"></i> {{ Lang.csseditor.reset-all }}
                </button>
            </div>
            <div class="action-group">
                <button type="submit" class="button success">
                    <i class="fa-solid fa-floppy-disk"></i> {{ Lang.save }}
                </button>
                <span class="auto-save-indicator">
                    <i class="fa-solid fa-save"></i> {{ Lang.csseditor.auto-save-enabled }}
                </span>
            </div>
        </div>
        
        <div class="csseditor-container">
            <!-- Panneau de gauche : Tableau de configuration CSS -->
            <div class="csseditor-panel">
                <h3>{{ Lang.csseditor.css-configuration }}</h3>
                <p>
                    <label for="enabled">
                        <input type="checkbox" name="enabled" id="enabled" value="1" {% IF enabled == "1" %}checked{% ENDIF %} />
                        {{ Lang.csseditor.enable-custom-css }}
                    </label>
                </p>
                
                <!-- Tableau de configuration CSS -->
                <div class="css-config-table">
                    <table class="config-table">
                        <thead>
                            <tr>
                                <th>{{ Lang.csseditor.variable }}</th>
                                <th>{{ Lang.csseditor.color }}</th>
                                <th>{{ Lang.csseditor.actions }}</th>
                            </tr>
                        </thead>
                        <tbody id="cssVarsTable">
                            {% FOR var IN themeVars %}
                            <tr data-var="{{ var.var }}" data-label="{{ var.label }}" data-original="{{ var.original }}" data-type="{{ var.type }}">
                                <td>
                                    <span class="var-label">{{ var.label }}</span>
                                    <small class="var-name">{{ var.var }}</small>
                                </td>
                                <td>
                                    {% IF var.type == "color" %}
                                    <!-- Input pour les couleurs -->
                                    <input type="color" 
                                           name="cssVars[{{ var.var }}]" 
                                           value="{{ var.value }}" 
                                           class="color-picker" />
                                    <input type="text" 
                                           name="cssVarsText[{{ var.var }}]" 
                                           value="{{ var.value }}" 
                                           class="color-text"
                                           data-original="{{ var.original }}"
                                           onchange="updateColorFromText(this)" />
                                    {% ELSEIF var.type == "display" %}
                                    <!-- Select pour les valeurs de display -->
                                    <select name="cssVars[{{ var.var }}]" 
                                            class="value-select"
                                            data-original="{{ var.original }}"
                                            onchange="updateValueFromSelect(this)">
                                        <option value="block" {% IF var.value == "block" %}selected{% ENDIF %}>block</option>
                                        <option value="inline" {% IF var.value == "inline" %}selected{% ENDIF %}>inline</option>
                                        <option value="inline-block" {% IF var.value == "inline-block" %}selected{% ENDIF %}>inline-block</option>
                                        <option value="flex" {% IF var.value == "flex" %}selected{% ENDIF %}>flex</option>
                                        <option value="grid" {% IF var.value == "grid" %}selected{% ENDIF %}>grid</option>
                                        <option value="none" {% IF var.value == "none" %}selected{% ENDIF %}>none</option>
                                        <option value="contents" {% IF var.value == "contents" %}selected{% ENDIF %}>contents</option>
                                    </select>
                                    {% ELSEIF var.type == "flex-direction" %}
                                    <!-- Select pour flex-direction -->
                                    <select name="cssVars[{{ var.var }}]" 
                                            class="value-select"
                                            data-original="{{ var.original }}"
                                            onchange="updateValueFromSelect(this)">
                                        <option value="row" {% IF var.value == "row" %}selected{% ENDIF %}>row</option>
                                        <option value="column" {% IF var.value == "column" %}selected{% ENDIF %}>column</option>
                                        <option value="row-reverse" {% IF var.value == "row-reverse" %}selected{% ENDIF %}>row-reverse</option>
                                        <option value="column-reverse" {% IF var.value == "column-reverse" %}selected{% ENDIF %}>column-reverse</option>
                                    </select>
                                    {% ELSEIF var.type == "text-align" %}
                                    <!-- Select pour text-align -->
                                    <select name="cssVars[{{ var.var }}]" 
                                            class="value-select"
                                            data-original="{{ var.original }}"
                                            onchange="updateValueFromSelect(this)">
                                        <option value="left" {% IF var.value == "left" %}selected{% ENDIF %}>left</option>
                                        <option value="center" {% IF var.value == "center" %}selected{% ENDIF %}>center</option>
                                        <option value="right" {% IF var.value == "right" %}selected{% ENDIF %}>right</option>
                                        <option value="justify" {% IF var.value == "justify" %}selected{% ENDIF %}>justify</option>
                                        <option value="start" {% IF var.value == "start" %}selected{% ENDIF %}>start</option>
                                        <option value="end" {% IF var.value == "end" %}selected{% ENDIF %}>end</option>
                                    </select>
                                    {% ELSEIF var.type == "font-weight" %}
                                    <!-- Select pour font-weight -->
                                    <select name="cssVars[{{ var.var }}]" 
                                            class="value-select"
                                            data-original="{{ var.original }}"
                                            onchange="updateValueFromSelect(this)">
                                        <option value="normal" {% IF var.value == "normal" %}selected{% ENDIF %}>normal</option>
                                        <option value="bold" {% IF var.value == "bold" %}selected{% ENDIF %}>bold</option>
                                        <option value="100" {% IF var.value == "100" %}selected{% ENDIF %}>100</option>
                                        <option value="200" {% IF var.value == "200" %}selected{% ENDIF %}>200</option>
                                        <option value="300" {% IF var.value == "300" %}selected{% ENDIF %}>300</option>
                                        <option value="400" {% IF var.value == "400" %}selected{% ENDIF %}>400</option>
                                        <option value="500" {% IF var.value == "500" %}selected{% ENDIF %}>500</option>
                                        <option value="600" {% IF var.value == "600" %}selected{% ENDIF %}>600</option>
                                        <option value="700" {% IF var.value == "700" %}selected{% ENDIF %}>700</option>
                                        <option value="800" {% IF var.value == "800" %}selected{% ENDIF %}>800</option>
                                        <option value="900" {% IF var.value == "900" %}selected{% ENDIF %}>900</option>
                                    </select>
                                    {% ELSEIF var.type == "border-style" %}
                                    <!-- Select pour border-style -->
                                    <select name="cssVars[{{ var.var }}]" 
                                            class="value-select"
                                            data-original="{{ var.original }}"
                                            onchange="updateValueFromSelect(this)">
                                        <option value="none" {% IF var.value == "none" %}selected{% ENDIF %}>none</option>
                                        <option value="solid" {% IF var.value == "solid" %}selected{% ENDIF %}>solid</option>
                                        <option value="dashed" {% IF var.value == "dashed" %}selected{% ENDIF %}>dashed</option>
                                        <option value="dotted" {% IF var.value == "dotted" %}selected{% ENDIF %}>dotted</option>
                                        <option value="double" {% IF var.value == "double" %}selected{% ENDIF %}>double</option>
                                    </select>
                                    {% ELSE %}
                                    <!-- Input texte pour les autres types de valeurs -->
                                    <input type="text" 
                                           name="cssVars[{{ var.var }}]" 
                                           value="{{ var.value }}" 
                                           class="value-input"
                                           data-original="{{ var.original }}"
                                           onchange="updateValueFromText(this)" />
                                    {% ENDIF %}
                                </td>
                                <td>
                                    <button type="button" class="button small reset-btn" onclick="resetVar('{{ var.var }}')" title="{{ Lang.csseditor.reset }}">
                                        <i class="fa-solid fa-undo"></i>
                                    </button>
                                </td>
                            </tr>
                            {% ENDFOR %}
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Panneau de droite : Aperçu -->
            <div class="csseditor-preview">
                <h3>{{ Lang.csseditor.preview }}</h3>
                <div class="preview-controls">
                    <button type="button" class="button small" onclick="refreshPreview()">
                        <i class="fa-solid fa-refresh"></i> {{ Lang.csseditor.refresh-preview }}
                    </button>
                    <button type="button" class="button small" onclick="togglePreviewFullscreen()">
                        <i class="fa-solid fa-expand"></i> {{ Lang.csseditor.fullscreen }}
                    </button>
                    </div>
                <div class="preview-frame-container">
                    <iframe id="preview-iframe" 
                            src="{{ SITE_URL }}/" 
                            frameborder="0" 
                            scrolling="yes"
                            title="{{ Lang.csseditor.preview }}">
                    </iframe>
                        </div>
                <div class="preview-loading" id="preview-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i> {{ Lang.csseditor.loading-preview }}
                </div>
            </div>
        </div>
        
        <!-- Informations sur le thème -->
        <div class="theme-info">
            <h3>{{ Lang.csseditor.theme-info }}</h3>
            <p><strong>{{ Lang.csseditor.current-theme }}:</strong> {{ currentTheme }}</p>
            
            <!-- Éditeur CSS manuel -->
            <div class="manual-css-editor">
                <h4>{{ Lang.csseditor.manual-css-editor }}</h4>
                <p>{{ Lang.csseditor.manual-css-description }}</p>
                <textarea name="manualCss" id="manualCss" rows="15" cols="80" placeholder="{{ Lang.csseditor.manual-css-placeholder }}">{{ manualCss }}</textarea>
                <textarea id="themeCssSource" hidden>{{ themeCss }}</textarea>
                <div class="manual-css-actions">
                    <button type="button" class="button" onclick="resetToThemeDefault()">{{ Lang.csseditor.reset-to-theme }}</button>
                    <button type="button" class="button" onclick="restoreLastBackup()">{{ Lang.csseditor.restore-backup }}</button>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Bouton de sortie plein écran -->
    <button class="fullscreen-exit" onclick="exitFullscreen()" title="{{ Lang.csseditor.exit-fullscreen }}">
        <i class="fa-solid fa-times"></i>
    </button>
</section>

<script>
// Variables globales pour les URLs
window.CSS_EDITOR_SAVE_URL = "{{ ROUTER.generate("csseditor-admin-save-css") }}";
</script> 
