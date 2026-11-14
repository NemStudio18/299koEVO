// Variables CSS générées dynamiquement
let cssVars = {};
let originalThemeCss = '';
let lastBackupCss = '';
let iframeLoaded = false;
let saveTimeout = null;
let isSaving = false;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('CSS Editor initializing...');
        initializeCssVars();
        initializeThemeCss();
        initializeIframe();
        initializeSaveIndicator();
        markModifiedRows(); // Marquer les lignes modifiées au chargement
        console.log('CSS Editor initialized successfully');
    } catch (error) {
        console.error('Error initializing CSS Editor:', error);
    }
});

// Initialise l'indicateur de sauvegarde
function initializeSaveIndicator() {
    const indicator = document.querySelector('.auto-save-indicator');
    if (indicator) {
        // Ajouter une classe pour l'animation
        indicator.classList.add('ready');
    }
}

// Affiche l'indicateur de sauvegarde
function showSaveIndicator() {
    const indicator = document.querySelector('.auto-save-indicator');
    if (indicator) {
        indicator.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';
        indicator.classList.add('saving');
        indicator.classList.remove('saved', 'ready');
    }
}

// Affiche l'indicateur de sauvegarde réussie
function showSaveSuccess() {
    const indicator = document.querySelector('.auto-save-indicator');
    if (indicator) {
        indicator.innerHTML = '<i class="fa-solid fa-check"></i> Sauvegardé';
        indicator.classList.remove('saving');
        indicator.classList.add('saved');
        
        // Revenir à l'état normal après 2 secondes
        setTimeout(() => {
            if (indicator.classList.contains('saved')) {
                indicator.innerHTML = '<i class="fa-solid fa-save"></i> Sauvegarde automatique activée';
                indicator.classList.remove('saved');
                indicator.classList.add('ready');
            }
        }, 2000);
    }
}

// Affiche l'indicateur d'erreur de sauvegarde
function showSaveError() {
    const indicator = document.querySelector('.auto-save-indicator');
    if (indicator) {
        indicator.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i> Erreur de sauvegarde';
        indicator.classList.remove('saving', 'saved');
        indicator.classList.add('error');
        
        // Revenir à l'état normal après 3 secondes
        setTimeout(() => {
            if (indicator.classList.contains('error')) {
                indicator.innerHTML = '<i class="fa-solid fa-save"></i> Sauvegarde automatique activée';
                indicator.classList.remove('error');
                indicator.classList.add('ready');
            }
        }, 3000);
    }
}

// Initialise l'iframe de prévisualisation
function initializeIframe() {
    const iframe = document.getElementById('preview-iframe');
    const loading = document.getElementById('preview-loading');
    
    console.log('Initializing iframe:', iframe);
    
    if (iframe) {
        iframe.addEventListener('load', function() {
            console.log('Iframe loaded successfully');
            iframeLoaded = true;
            if (loading) {
                loading.classList.add('hidden');
            }
            
            // Vérifier que l'iframe est accessible
            setTimeout(function() {
                try {
                    if (iframe.contentDocument && iframe.contentDocument.body) {
                        console.log('Iframe contentDocument accessible');
                        // Injecter le CSS custom dès que l'iframe est chargée
                        injectCssIntoIframe();
                    } else {
                        console.error('Iframe contentDocument not accessible after load');
                        // Essayer de recharger l'iframe
                        setTimeout(function() {
                            console.log('Retrying iframe load');
                            iframe.src = iframe.src;
                        }, 1000);
                    }
                } catch (error) {
                    console.error('Error accessing iframe contentDocument:', error);
                }
            }, 500);
        });
        
        iframe.addEventListener('error', function() {
            console.error('Iframe load error');
            if (loading) {
                loading.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i> Erreur de chargement';
            }
        });
    }
}

// Fonction manquante pour la mise à jour de l'aperçu CSS
function updateCssPreview() {
    console.log('updateCssPreview called');
    // Cette fonction est appelée depuis le template HTML
    // Elle peut être utilisée pour mettre à jour l'aperçu en temps réel
    // Pour l'instant, on programme juste une sauvegarde
    scheduleCssSave();
}

// Initialise les variables CSS depuis le tableau
function initializeCssVars() {
    const rows = document.querySelectorAll('#cssVarsTable tr');
    rows.forEach(row => {
        const varName = row.dataset.var;
        const varType = row.dataset.type;
        const originalValue = row.dataset.original;
        
        if (varType === 'color') {
            const colorInput = row.querySelector('.color-picker');
            const textInput = row.querySelector('.color-text');
            
            if (colorInput && textInput) {
                cssVars[varName] = colorInput.value;
                
                // Synchroniser les inputs avec événements plus robustes
                colorInput.addEventListener('input', function() {
                    console.log('Color picker changed:', varName, this.value);
                    textInput.value = this.value;
                    cssVars[varName] = this.value;
                    scheduleCssSave();
                    injectCssIntoIframe();
                    markModifiedRows();
                });
                
                colorInput.addEventListener('change', function() {
                    console.log('Color picker changed (change):', varName, this.value);
                    textInput.value = this.value;
                    cssVars[varName] = this.value;
                    scheduleCssSave();
                    injectCssIntoIframe();
                    markModifiedRows();
                });
                
                textInput.addEventListener('input', function() {
                    if (isValidColor(this.value)) {
                        console.log('Text input changed:', varName, this.value);
                        colorInput.value = this.value;
                        cssVars[varName] = this.value;
                        scheduleCssSave();
                        injectCssIntoIframe();
                        markModifiedRows();
                    }
                });
                
                textInput.addEventListener('change', function() {
                    if (isValidColor(this.value)) {
                        console.log('Text input changed (change):', varName, this.value);
                        colorInput.value = this.value;
                        cssVars[varName] = this.value;
                        scheduleCssSave();
                        injectCssIntoIframe();
                        markModifiedRows();
                    }
                });
            }
        } else if (varType === 'display' || varType === 'flex-direction' || varType === 'text-align' || varType === 'font-weight' || varType === 'border-style') {
            // Pour les selects
            const selectInput = row.querySelector('.value-select');
            
            if (selectInput) {
                cssVars[varName] = selectInput.value;
                
                selectInput.addEventListener('change', function() {
                    console.log('Select changed:', varName, this.value);
                    cssVars[varName] = this.value;
                    scheduleCssSave();
                    injectCssIntoIframe();
                    markModifiedRows();
                });
            }
        } else {
            // Pour les autres types (taille, fonction, etc.)
            const valueInput = row.querySelector('.value-input');
            
            if (valueInput) {
                cssVars[varName] = valueInput.value;
                
                valueInput.addEventListener('input', function() {
                    console.log('Value input changed:', varName, this.value);
                    cssVars[varName] = this.value;
                    scheduleCssSave();
                    injectCssIntoIframe();
                    markModifiedRows();
                });
                
                valueInput.addEventListener('change', function() {
                    console.log('Value input changed (change):', varName, this.value);
                    cssVars[varName] = this.value;
                    scheduleCssSave();
                    injectCssIntoIframe();
                    markModifiedRows();
                });
            }
        }
    });
}

// Initialise le CSS du thème
function initializeThemeCss() {
    const manualCss = document.getElementById('manualCss');
    const themeSource = document.getElementById('themeCssSource');
    if (themeSource) {
        originalThemeCss = themeSource.value;
    }
    if (!originalThemeCss && manualCss) {
        originalThemeCss = manualCss.value;
    }
    if (manualCss) {
        lastBackupCss = manualCss.value;
        manualCss.addEventListener('input', function() {
            console.log('Manual CSS input changed');
            clearTimeout(window.backupTimeout);
            window.backupTimeout = setTimeout(function() {
                lastBackupCss = manualCss.value;
            }, 2000);
            scheduleCssSave();
            injectCssIntoIframe();
        });
    }
}

// Programme la sauvegarde du CSS avec un délai
function scheduleCssSave() {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(function() {
        saveCssToFile();
    }, 500); // Délai de 500ms pour éviter trop de sauvegardes
}

// Sauvegarde le CSS dans le fichier
function saveCssToFile() {
    console.log('=== DÉBUT saveCssToFile ===');
    console.log('Saving CSS to file...');
    
    // Générer seulement les variables CSS personnalisées qui ont été modifiées
    let cssContent = '';
    let hasModifiedVars = false;
    
    // Traiter chaque variable CSS
    Object.keys(cssVars).forEach(varName => {
        if (cssVars[varName] && cssVars[varName] !== '') {
            // Vérifier si la valeur a été modifiée par rapport à l'original
            const row = document.querySelector(`[data-var="${varName}"]`);
            if (row) {
                const originalValue = row.dataset.original;
                const currentValue = cssVars[varName];
                
                // Vérification plus stricte : ne sauvegarder que si la valeur a vraiment changé
                // et n'est pas une valeur par défaut commune
                if (currentValue !== originalValue && 
                    currentValue !== '#000000' && 
                    currentValue !== '#ffffff' && 
                    currentValue !== 'transparent' && 
                    currentValue !== 'inherit' && 
                    currentValue !== 'initial' && 
                    currentValue !== 'unset') {
                    
                    console.log('Variable modifiée:', varName, 'original:', originalValue, 'nouveau:', currentValue);
                    
                    if (varName.startsWith('--')) {
                        // Variable CSS native
                        cssContent += `${varName}: ${currentValue};\n`;
                        hasModifiedVars = true;
                    } else {
                        // Sélecteur CSS classique (ignorer pour l'instant)
                        console.log('Sélecteur CSS ignoré:', varName);
                    }
                } else {
                    console.log('Variable non modifiée ou valeur par défaut, ignorée:', varName, 'valeur:', currentValue);
                }
            }
        }
    });
    
    // Si on a des variables modifiées, les entourer dans un bloc :root
    if (cssContent.trim()) {
        cssContent = ':root {\n' + cssContent + '}\n';
    }
    
    // Ajouter le CSS manuel s'il y en a (mais pas s'il contient tout le thème)
    const manualCss = document.getElementById('manualCss');
    if (manualCss && manualCss.value.trim()) {
        const manualContent = manualCss.value.trim();
        cssContent += '\n' + manualContent + '\n';
    }
    
    console.log('CSS content final à sauvegarder:', cssContent);
    
    // Si aucune variable modifiée et pas de CSS manuel, ne pas sauvegarder
    if (!hasModifiedVars && (!manualCss || !manualCss.value.trim())) {
        console.log('Aucune modification détectée, pas de sauvegarde nécessaire');
        return;
    }
    
    // Récupérer le token CSRF
    const tokenInput = document.querySelector('input[name="token"]');
    if (!tokenInput) {
        console.error('❌ Token input non trouvé!');
        showSaveError();
        return;
    }
    
    const token = tokenInput.value;
    console.log('Token trouvé:', token ? 'OUI' : 'NON');
    
    // Préparer les données POST
    const postData = 'action=save_css&css_content=' + encodeURIComponent(cssContent) + '&token=' + encodeURIComponent(token);
    console.log('Données POST à envoyer:', postData);
    
    // URL de sauvegarde
    const saveUrl = window.CSS_EDITOR_SAVE_URL || '/admin/csseditor/save-css';
    console.log('URL de sauvegarde:', saveUrl);
    
    // Envoyer le CSS au serveur pour sauvegarde
    console.log('Envoi de la requête fetch...');
    console.log('Méthode: POST');
    console.log('Headers: Content-Type: application/x-www-form-urlencoded');
    console.log('Body:', postData);
    
    fetch(saveUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: postData
    })
    .then(response => {
        console.log('Réponse reçue, status:', response.status);
        console.log('Headers de réponse:', response.headers);
        return response.text();
    })
    .then(data => {
        console.log('Données de réponse:', data);
        if (data === 'success') {
            console.log('✅ CSS sauvegardé avec succès');
            showSaveSuccess();
        } else {
            console.log('❌ Erreur lors de la sauvegarde:', data);
            showSaveError();
        }
        // Pas de rafraîchissement automatique, l'utilisateur utilisera le bouton si nécessaire
    })
    .catch(error => {
        console.error('❌ Erreur fetch:', error);
        console.error('Détails de l\'erreur:', error.message);
        showSaveError();
    });
    
    console.log('=== FIN saveCssToFile ===');
}

// Injecte le CSS dans l'iframe (pour l'aperçu en temps réel)
function injectCssIntoIframe() {
    const iframe = document.getElementById('preview-iframe');
    console.log('injectCssIntoIframe called, iframe:', iframe);
    
    if (!iframe) {
        console.error('Iframe not found');
        return;
    }
    
    if (!iframe.contentDocument) {
        console.error('Iframe contentDocument not accessible');
        return;
    }
    
    try {
        let cssContent = '';
        
        // Générer le CSS à partir des variables personnalisées modifiées avec !important pour la priorité
        Object.keys(cssVars).forEach(varName => {
            if (cssVars[varName] && cssVars[varName] !== '') {
                // Vérifier si la valeur a été modifiée par rapport à l'original
                const row = document.querySelector(`[data-var="${varName}"]`);
                if (row) {
                    const originalValue = row.dataset.original;
                    const currentValue = cssVars[varName];
                    
                    // Ne traiter que si la valeur a changé
                    if (currentValue !== originalValue) {
                        if (varName.startsWith('--')) {
                            // Variable CSS native - appliquée au document avec !important
                            cssContent += `:root {\n    ${varName}: ${currentValue} !important;\n}\n\n`;
                        } else {
                            // Sélecteur CSS classique
                            const parts = varName.split(':');
                            if (parts.length === 2) {
                                cssContent += `${parts[0]} {\n    ${parts[1]}: ${currentValue} !important;\n}\n\n`;
                            }
                        }
                    }
                }
            }
        });
        
        // Ajouter le CSS manuel personnalisé (pas le thème complet)
        const manualCss = document.getElementById('manualCss');
        if (manualCss && manualCss.value.trim()) {
            const manualContent = manualCss.value.trim();
            
            // Vérifier si le contenu manuel contient tout le thème (plus de 1000 caractères)
            // Si oui, ne pas l'inclure car c'est probablement le thème complet
            if (manualContent.length < 1000) {
                cssContent += manualContent + '\n';
            } else {
                console.log('CSS manuel ignoré pour l\'injection car il semble contenir tout le thème');
            }
        }
        
        console.log('CSS content to inject:', cssContent);
        
        // Supprimer l'ancien style s'il existe
        const existingStyle = iframe.contentDocument.getElementById('csseditor-preview-style');
        if (existingStyle) {
            existingStyle.remove();
        }
        
        // Créer et insérer le nouveau style en dernier dans le head pour la priorité
        const style = iframe.contentDocument.createElement('style');
        style.id = 'csseditor-preview-style';
        style.textContent = cssContent;
        
        const head = iframe.contentDocument.head;
        head.appendChild(style); // Ajouter à la fin pour la priorité
        
        console.log('CSS custom injected successfully into iframe');
        
    } catch (error) {
        console.error('Error injecting CSS into iframe:', error);
    }
}

// Rafraîchit la prévisualisation
function refreshPreview() {
    const iframe = document.getElementById('preview-iframe');
    const loading = document.getElementById('preview-loading');
    
    if (iframe) {
        iframeLoaded = false;
        loading.classList.remove('hidden');
        iframe.src = iframe.src; // Recharge l'iframe
    }
}

// Bascule le mode plein écran
function togglePreviewFullscreen() {
    const container = document.querySelector('.preview-frame-container');
    const button = document.querySelector('[onclick="togglePreviewFullscreen()"]');
    const exitButton = document.querySelector('.fullscreen-exit');
    const icon = button.querySelector('i');
    
    if (container.classList.contains('fullscreen')) {
        // Sortir du plein écran
        exitFullscreen();
    } else {
        // Entrer en plein écran
        enterFullscreen();
    }
}

// Entrer en mode plein écran
function enterFullscreen() {
    const container = document.querySelector('.preview-frame-container');
    const button = document.querySelector('[onclick="togglePreviewFullscreen()"]');
    const exitButton = document.querySelector('.fullscreen-exit');
    const icon = button.querySelector('i');
    
    container.classList.add('fullscreen');
    icon.className = 'fa-solid fa-compress';
    button.innerHTML = '<i class="fa-solid fa-compress"></i> {{ Lang.csseditor.exit-fullscreen }}';
    exitButton.classList.add('visible');
    
    // Empêcher le scroll du body
    document.body.style.overflow = 'hidden';
    
    // Ajouter l'écouteur pour la touche Echap
    document.addEventListener('keydown', handleEscapeKey);
}

// Sortir du mode plein écran
function exitFullscreen() {
    const container = document.querySelector('.preview-frame-container');
    const button = document.querySelector('[onclick="togglePreviewFullscreen()"]');
    const exitButton = document.querySelector('.fullscreen-exit');
    const icon = button.querySelector('i');
    
    container.classList.remove('fullscreen');
    icon.className = 'fa-solid fa-expand';
    button.innerHTML = '<i class="fa-solid fa-expand"></i> {{ Lang.csseditor.fullscreen }}';
    exitButton.classList.remove('visible');
    
    // Restaurer le scroll du body
    document.body.style.overflow = '';
    
    // Supprimer l'écouteur pour la touche Echap
    document.removeEventListener('keydown', handleEscapeKey);
}

// Gérer la touche Echap pour sortir du plein écran
function handleEscapeKey(event) {
    if (event.key === 'Escape') {
        exitFullscreen();
    }
}

// Marque visuellement les lignes modifiées
function markModifiedRows() {
    const rows = document.querySelectorAll('#cssVarsTable tr');
    rows.forEach(row => {
        const varName = row.dataset.var;
        const originalValue = row.dataset.original;
        const currentValue = cssVars[varName];
        
        if (currentValue && currentValue !== originalValue) {
            row.classList.add('modified');
        } else {
            row.classList.remove('modified');
        }
    });
}

// Met à jour la couleur depuis le texte
function updateColorFromText(textInput) {
    if (isValidColor(textInput.value)) {
        const colorInput = textInput.parentNode.querySelector('.color-picker');
        if (colorInput) {
            colorInput.value = textInput.value;
            const varName = textInput.parentNode.parentNode.dataset.var;
            cssVars[varName] = textInput.value;
            scheduleCssSave();
            injectCssIntoIframe(); // Mettre à jour l'aperçu en temps réel
            markModifiedRows(); // Marquer les lignes modifiées
        }
    }
}

// Met à jour la valeur depuis le texte (pour les non-couleurs)
function updateValueFromText(textInput) {
    const varName = textInput.parentNode.parentNode.dataset.var;
    cssVars[varName] = textInput.value;
    scheduleCssSave();
    injectCssIntoIframe(); // Mettre à jour l'aperçu en temps réel
    markModifiedRows(); // Marquer les lignes modifiées
}

// Met à jour la valeur depuis un select
function updateValueFromSelect(selectInput) {
    const varName = selectInput.parentNode.parentNode.dataset.var;
    cssVars[varName] = selectInput.value;
    scheduleCssSave();
    injectCssIntoIframe(); // Mettre à jour l'aperçu en temps réel
    markModifiedRows(); // Marquer les lignes modifiées
}

// Vérifie si une couleur est valide
function isValidColor(color) {
    const s = new Option().style;
    s.color = color;
    return s.color !== '';
}

// Réinitialise une variable
function resetVar(varName) {
    const row = document.querySelector(`[data-var="${varName}"]`);
    if (row) {
        const varType = row.dataset.type;
        const originalValue = row.dataset.original;
        
        if (varType === 'color') {
            const colorInput = row.querySelector('.color-picker');
            const textInput = row.querySelector('.color-text');
            
            if (colorInput && textInput) {
                colorInput.value = originalValue;
                textInput.value = originalValue;
                cssVars[varName] = originalValue;
            }
        } else {
            const valueInput = row.querySelector('.value-input');
            
            if (valueInput) {
                valueInput.value = originalValue;
                cssVars[varName] = originalValue;
            }
        }
        
        scheduleCssSave();
        injectCssIntoIframe(); // Mettre à jour l'aperçu en temps réel
        markModifiedRows(); // Marquer les lignes modifiées
    }
}

// Réinitialise toutes les variables
function resetAllVars() {
    if (confirm("Êtes-vous sûr de vouloir réinitialiser toutes les variables ?")) {
        const rows = document.querySelectorAll('#cssVarsTable tr');
        rows.forEach(row => {
            const varName = row.dataset.var;
            const varType = row.dataset.type;
            const originalValue = row.dataset.original;
            
            if (varType === 'color') {
                const colorInput = row.querySelector('.color-picker');
                const textInput = row.querySelector('.color-text');
                
                if (colorInput && textInput) {
                    colorInput.value = originalValue;
                    textInput.value = originalValue;
                    cssVars[varName] = originalValue;
                }
            } else if (varType === 'display' || varType === 'flex-direction' || varType === 'text-align' || varType === 'font-weight' || varType === 'border-style') {
                const selectInput = row.querySelector('.value-select');
                
                if (selectInput) {
                    selectInput.value = originalValue;
                    cssVars[varName] = originalValue;
                }
            } else {
                const valueInput = row.querySelector('.value-input');
                
                if (valueInput) {
                    valueInput.value = originalValue;
                    cssVars[varName] = originalValue;
                }
            }
        });
        scheduleCssSave();
        injectCssIntoIframe(); // Mettre à jour l'aperçu en temps réel
        markModifiedRows(); // Marquer les lignes modifiées
    }
}

// Ajoute une variable personnalisée
function addCustomVar() {
    const tbody = document.getElementById('cssVarsTable');
    const newRow = document.createElement('tr');
    const varId = 'custom_' + Date.now();
    
    newRow.innerHTML = `
        <td>
            <input type="text" 
                   name="customVarLabel[${varId}]" 
                   placeholder="Nom de la variable" 
                   class="var-label-input"
                   onchange="updateCustomVarLabel(this, '${varId}')" />
            <input type="text" 
                   name="customVarName[${varId}]" 
                   placeholder="--variable-name ou selector:property" 
                   class="var-name-input"
                   onchange="updateCustomVarName(this, '${varId}')" />
        </td>
        <td>
            <input type="color" 
                   name="cssVars[${varId}]" 
                   value="#000000" 
                   class="color-picker"
                   onchange="updateCustomVarColor(this, '${varId}')" />
            <input type="text" 
                   name="cssVarsText[${varId}]" 
                   value="#000000" 
                   class="color-text"
                   onchange="updateCustomVarColorText(this, '${varId}')" />
        </td>
        <td>
            <button type="button" class="button small danger" onclick="removeCustomVar(this)">
                {{ Lang.csseditor.remove }}
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    
    // Initialiser la variable
    cssVars[varId] = '#000000';
    injectCssIntoIframe(); // Mettre à jour l'aperçu en temps réel
    markModifiedRows(); // Marquer les lignes modifiées
}

// Met à jour le label d'une variable personnalisée
function updateCustomVarLabel(input, varId) {
    // Cette fonction peut être étendue pour sauvegarder le label
    console.log('Custom var label updated:', varId, input.value);
}

// Met à jour le nom d'une variable personnalisée
function updateCustomVarName(input, varId) {
    const newName = input.value.trim();
    if (newName) {
        // Supprimer l'ancienne variable et ajouter la nouvelle
        delete cssVars[varId];
        cssVars[newName] = input.parentNode.parentNode.querySelector('.color-picker').value;
        input.parentNode.parentNode.dataset.var = newName;
        scheduleCssSave();
        injectCssIntoIframe();
        markModifiedRows(); // Marquer les lignes modifiées
    }
}

// Met à jour la couleur d'une variable personnalisée
function updateCustomVarColor(colorInput, varId) {
    const textInput = colorInput.parentNode.querySelector('.color-text');
    textInput.value = colorInput.value;
    cssVars[varId] = colorInput.value;
    scheduleCssSave();
    injectCssIntoIframe();
    markModifiedRows(); // Marquer les lignes modifiées
}

// Met à jour la couleur texte d'une variable personnalisée
function updateCustomVarColorText(textInput, varId) {
    if (isValidColor(textInput.value)) {
        const colorInput = textInput.parentNode.querySelector('.color-picker');
        colorInput.value = textInput.value;
        cssVars[varId] = textInput.value;
        scheduleCssSave();
        injectCssIntoIframe();
        markModifiedRows(); // Marquer les lignes modifiées
    }
}

// Supprime une variable personnalisée
function removeCustomVar(button) {
    const row = button.parentNode.parentNode;
    const varName = row.dataset.var;
    
    if (varName) {
        delete cssVars[varName];
        scheduleCssSave();
        injectCssIntoIframe();
        markModifiedRows(); // Marquer les lignes modifiées
    }
    
    row.remove();
}

// Réinitialise au CSS du thème par défaut
function resetToThemeDefault() {
    if (confirm("Êtes-vous sûr de vouloir réinitialiser le CSS manuel au thème par défaut ?")) {
        const manualCss = document.getElementById('manualCss');
        if (manualCss) {
            manualCss.value = originalThemeCss;
            scheduleCssSave();
            injectCssIntoIframe();
            markModifiedRows(); // Marquer les lignes modifiées
        }
    }
}

// Restaure la dernière sauvegarde
function restoreLastBackup() {
    if (confirm("Êtes-vous sûr de vouloir restaurer la dernière sauvegarde ?")) {
        const manualCss = document.getElementById('manualCss');
        if (manualCss) {
            manualCss.value = lastBackupCss;
            scheduleCssSave();
            injectCssIntoIframe();
            markModifiedRows(); // Marquer les lignes modifiées
        }
    }
}

// Charge le CSS du thème (fonction utilitaire)
function loadThemeCss() {
    // Cette fonction peut être utilisée pour recharger le CSS du thème
    console.log('Loading theme CSS...');
}

// Efface le CSS manuel
function clearManualCss() {
    if (confirm("Êtes-vous sûr de vouloir effacer tout le CSS manuel ?")) {
        const manualCss = document.getElementById('manualCss');
        if (manualCss) {
            manualCss.value = '';
            scheduleCssSave();
            injectCssIntoIframe();
            markModifiedRows(); // Marquer les lignes modifiées
        }
    }
} 