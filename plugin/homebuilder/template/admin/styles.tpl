{% INCLUDE admin/header %}

<div class="container">
    <h1>{% IF isCreating %}Créer un nouveau bloc{% ELSE %}Styles du bloc : {{ block.getTitle() }}{% ENDIF %}</h1>
    
    <div class="styles-editor">
        <form method="POST" action="{{ edit_link }}">
            
            <!-- Section de prévisualisation avec drag & drop -->
            <div class="preview-section">
                <h3>Aperçu en temps réel</h3>
                <div class="preview-container">
                    <div id="preview-block" class="homepage-block homepage-block-{{ block.getType() }}">
                        <div class="block-elements" id="block-elements">
                            <h2 class="element" data-type="title" data-id="title-1">{{ block.getTitle() }}</h2>
                            <div class="content element" data-type="content" data-id="content-1">{{ block.getContent() }}</div>
                            <h3 class="element" data-type="subtitle" data-id="subtitle-1">Sous-titre</h3>
                            <div class="content element" data-type="content" data-id="content-2">Contenu secondaire</div>
                        </div>
                    </div>
                </div>
                
                <!-- Contrôles des éléments -->
                <div class="elements-controls">
                    <h4>Gestion des éléments</h4>
                    <div class="elements-list">
                        <div class="element-item" data-element-id="title-1">
                            <span class="element-name">Titre principal</span>
                            <div class="element-actions">
                                <button type="button" class="btn-edit" data-action="edit">Éditer</button>
                                <button type="button" class="btn-toggle" data-action="toggle">Masquer</button>
                                <button type="button" class="btn-delete" data-action="delete">Supprimer</button>
                            </div>
                        </div>
                        <div class="element-item" data-element-id="content-1">
                            <span class="element-name">Contenu principal</span>
                            <div class="element-actions">
                                <button type="button" class="btn-edit" data-action="edit">Éditer</button>
                                <button type="button" class="btn-toggle" data-action="toggle">Masquer</button>
                                <button type="button" class="btn-delete" data-action="delete">Supprimer</button>
                            </div>
                        </div>
                        <div class="element-item" data-element-id="subtitle-1">
                            <span class="element-name">Sous-titre</span>
                            <div class="element-actions">
                                <button type="button" class="btn-edit" data-action="edit">Éditer</button>
                                <button type="button" class="btn-toggle" data-action="toggle">Masquer</button>
                                <button type="button" class="btn-delete" data-action="delete">Supprimer</button>
                            </div>
                        </div>
                        <div class="element-item" data-element-id="content-2">
                            <span class="element-name">Contenu secondaire</span>
                            <div class="element-actions">
                                <button type="button" class="btn-edit" data-action="edit">Éditer</button>
                                <button type="button" class="btn-toggle" data-action="toggle">Masquer</button>
                                <button type="button" class="btn-delete" data-action="delete">Supprimer</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Boutons d'ajout d'éléments -->
                    <div class="add-elements">
                        <h5>Ajouter un élément</h5>
                        <div class="add-buttons">
                            <button type="button" class="btn-add" data-type="title">+ Titre</button>
                            <button type="button" class="btn-add" data-type="subtitle">+ Sous-titre</button>
                            <button type="button" class="btn-add" data-type="content">+ Contenu</button>
                            <button type="button" class="btn-add" data-type="button">+ Bouton</button>
                            <button type="button" class="btn-add" data-type="image">+ Image</button>
                        </div>
                    </div>
                </div>
                
                <!-- Modal d'édition d'élément -->
                <div id="edit-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Éditer l'élément</h3>
                            <button type="button" class="close-modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Type d'élément</label>
                                <select id="edit-element-type">
                                    <option value="title">Titre</option>
                                    <option value="subtitle">Sous-titre</option>
                                    <option value="content">Contenu</option>
                                    <option value="button">Bouton</option>
                                    <option value="image">Image</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Contenu</label>
                                <textarea id="edit-element-content" rows="4" placeholder="Entrez le contenu..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Position</label>
                                <select id="edit-element-position">
                                    <option value="vertical">Vertical (empilé)</option>
                                    <option value="horizontal">Horizontal (côte à côte)</option>
                                </select>
                            </div>
                            <div class="form-group button-options" style="display: none;">
                                <label>URL du bouton</label>
                                <input type="text" id="edit-button-url" placeholder="https://...">
                            </div>
                            <div class="form-group image-options" style="display: none;">
                                <label>URL de l'image</label>
                                <input type="text" id="edit-image-url" placeholder="https://...">
                                <label>Texte alternatif</label>
                                <input type="text" id="edit-image-alt" placeholder="Description de l'image">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" id="cancel-edit">Annuler</button>
                            <button type="button" class="btn btn-primary" id="save-edit">Sauvegarder</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Styles du conteneur (layout uniquement) -->
            <div class="style-section">
                <h3>Styles du conteneur (Layout)</h3>
                <div class="style-grid">
                    <div class="style-group">
                        <label>Couleur de fond</label>
                        <div class="color-control">
                            <input type="color" name="container_backgroundColor" value="{{ styles.container.backgroundColor }}" data-target="#preview-block" data-property="backgroundColor">
                            <select name="container_backgroundType" data-target="#preview-block" data-property="backgroundType">
                                <option value="solid">Couleur unie</option>
                                <option value="gradient">Dégradé</option>
                            </select>
                        </div>
                    </div>
                    
                                         <!-- Contrôles de dégradé (masqués par défaut) -->
                     <div class="gradient-controls" style="display: none;">
                         <div class="style-group">
                             <label>Couleur de début</label>
                             <input type="color" name="container_gradientStart" value="{{ styles.container.gradientStart }}" data-target="#preview-block" data-property="gradientStart">
                         </div>
                         <div class="style-group">
                             <label>Couleur de fin</label>
                             <input type="color" name="container_gradientEnd" value="{{ styles.container.gradientEnd }}" data-target="#preview-block" data-property="gradientEnd">
                         </div>
                        <div class="style-group">
                            <label>Direction</label>
                            <select name="container_gradientDirection" data-target="#preview-block" data-property="gradientDirection">
                                <option value="to bottom">Vers le bas</option>
                                <option value="to top">Vers le haut</option>
                                <option value="to right">Vers la droite</option>
                                <option value="to left">Vers la gauche</option>
                                <option value="45deg">Diagonale</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="style-group">
                        <label>Padding</label>
                        <input type="text" name="container_padding" value="{{ styles.container.padding }}" placeholder="20px" data-target="#preview-block" data-property="padding">
                    </div>
                    <div class="style-group">
                        <label>Margin</label>
                        <input type="text" name="container_margin" value="{{ styles.container.margin }}" placeholder="10px 0" data-target="#preview-block" data-property="margin">
                    </div>
                    <div class="style-group">
                        <label>Rayon de bordure</label>
                        <input type="text" name="container_borderRadius" value="{{ styles.container.borderRadius }}" placeholder="8px" data-target="#preview-block" data-property="borderRadius">
                    </div>
                    <div class="style-group">
                        <label>Bordure</label>
                        <input type="text" name="container_border" value="{{ styles.container.border }}" placeholder="1px solid #e0e0e0" data-target="#preview-block" data-property="border">
                    </div>
                    <div class="style-group">
                        <label>Ombre</label>
                        <input type="text" name="container_boxShadow" value="{{ styles.container.boxShadow }}" placeholder="0 2px 4px rgba(0,0,0,0.1)" data-target="#preview-block" data-property="boxShadow">
                    </div>
                    <div class="style-group">
                        <label>Largeur</label>
                        <input type="text" name="container_width" value="{{ styles.container.width }}" placeholder="100%" data-target="#preview-block" data-property="width">
                    </div>
                    <div class="style-group">
                        <label>Largeur max</label>
                        <input type="text" name="container_maxWidth" value="{{ styles.container.maxWidth }}" placeholder="none" data-target="#preview-block" data-property="maxWidth">
                    </div>
                </div>
            </div>

            <!-- Styles des titres -->
            <div class="style-section">
                <h3>Styles des titres</h3>
                <div class="style-grid">
                    <div class="style-group">
                        <label>Couleur</label>
                        <input type="color" name="title_color" value="{{ styles.title.color }}" data-target="#preview-block h2, #preview-block h3" data-property="color">
                    </div>
                    <div class="style-group">
                        <label>Taille de police</label>
                        <input type="text" name="title_fontSize" value="{{ styles.title.fontSize }}" placeholder="24px" data-target="#preview-block h2, #preview-block h3" data-property="fontSize">
                    </div>
                    <div class="style-group">
                        <label>Poids de police</label>
                        <select name="title_fontWeight" data-target="#preview-block h2, #preview-block h3" data-property="fontWeight">
                            <option value="normal" {% IF titleFontWeightNormal %}selected{% ENDIF %}>Normal</option>
                            <option value="bold" {% IF titleFontWeightBold %}selected{% ENDIF %}>Gras</option>
                            <option value="lighter" {% IF titleFontWeightLighter %}selected{% ENDIF %}>Fin</option>
                        </select>
                    </div>
                    <div class="style-group">
                        <label>Margin bottom</label>
                        <input type="text" name="title_marginBottom" value="{{ styles.title.marginBottom }}" placeholder="15px" data-target="#preview-block h2, #preview-block h3" data-property="marginBottom">
                    </div>
                    <div class="style-group">
                        <label>Alignement</label>
                        <select name="title_textAlign" data-target="#preview-block h2, #preview-block h3" data-property="textAlign">
                            <option value="left" {% IF titleTextAlignLeft %}selected{% ENDIF %}>Gauche</option>
                            <option value="center" {% IF titleTextAlignCenter %}selected{% ENDIF %}>Centre</option>
                            <option value="right" {% IF titleTextAlignRight %}selected{% ENDIF %}>Droite</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Styles du contenu -->
            <div class="style-section">
                <h3>Styles du contenu</h3>
                <div class="style-grid">
                    <div class="style-group">
                        <label>Couleur</label>
                        <input type="color" name="content_color" value="{{ styles.content.color }}" data-target="#preview-block .content" data-property="color">
                    </div>
                    <div class="style-group">
                        <label>Taille de police</label>
                        <input type="text" name="content_fontSize" value="{{ styles.content.fontSize }}" placeholder="16px" data-target="#preview-block .content" data-property="fontSize">
                    </div>
                    <div class="style-group">
                        <label>Hauteur de ligne</label>
                        <input type="text" name="content_lineHeight" value="{{ styles.content.lineHeight }}" placeholder="1.6" data-target="#preview-block .content" data-property="lineHeight">
                    </div>
                    <div class="style-group">
                        <label>Margin bottom</label>
                        <input type="text" name="content_marginBottom" value="{{ styles.content.marginBottom }}" placeholder="15px" data-target="#preview-block .content" data-property="marginBottom">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">{% IF isCreating %}Créer le bloc{% ELSE %}Appliquer les styles{% ENDIF %}</button>
                <a href="{{ ROUTER.generate("admin-homebuilder") }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<style>
.styles-editor {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.preview-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.preview-section h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.preview-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

#preview-block {
    max-width: 100%;
    width: 100%;
}

.block-elements {
    position: relative;
    min-height: 50px;
}

.element {
    cursor: move;
    padding: 5px;
    margin: 5px 0;
    border: 1px dashed #ccc;
    background: rgba(255,255,255,0.8);
    transition: all 0.3s ease;
}

.element:hover {
    border-color: #007bff;
    background: rgba(0,123,255,0.1);
}

.element.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.element.hidden {
    display: none;
}

.elements-controls {
    margin-top: 20px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.elements-controls h4 {
    margin-top: 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.elements-list {
    margin-bottom: 15px;
}

.element-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    margin: 5px 0;
    background: #f8f9fa;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.element-name {
    font-weight: 500;
    color: #333;
}

.element-actions {
    display: flex;
    gap: 5px;
}

.btn-toggle, .btn-delete {
    padding: 4px 8px;
    font-size: 12px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.btn-edit {
    padding: 4px 8px;
    font-size: 12px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    background: #17a2b8;
    color: white;
}

.btn-toggle {
    background: #ffc107;
    color: #000;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.add-elements {
    margin-top: 20px;
    padding: 15px;
    background: #e9ecef;
    border-radius: 8px;
}

.add-elements h5 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.add-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.btn-add {
    padding: 6px 12px;
    font-size: 12px;
    border: 1px solid #007bff;
    background: #007bff;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-add:hover {
    background: #0056b3;
    border-color: #0056b3;
}

/* Modal styles */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #fefefe;
    margin: 20px;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

/* Responsive pour petits écrans */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        max-width: none;
        margin: 10px;
        max-height: 95vh;
    }
    
    .modal-body {
        padding: 15px;
    }
    
    .modal-footer {
        padding: 15px;
    }
    
    .modal-footer .btn {
        padding: 8px 16px;
        font-size: 13px;
    }
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.close-modal {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.close-modal:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-shrink: 0;
    background: #fefefe;
    border-radius: 0 0 8px 8px;
}

.modal-footer .btn {
    padding: 10px 20px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
    border: none;
    transition: all 0.3s ease;
}

.modal-footer .btn-primary {
    background: #007bff;
    color: white;
}

.modal-footer .btn-primary:hover {
    background: #0056b3;
}

.modal-footer .btn-secondary {
    background: #6c757d;
    color: white;
}

.modal-footer .btn-secondary:hover {
    background: #545b62;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

/* Layout options */
.layout-horizontal {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.layout-horizontal .element {
    flex: 1;
    min-width: 0;
}

.color-control {
    display: flex;
    gap: 10px;
    align-items: center;
}

.color-control input[type="color"] {
    width: 50px;
    height: 35px;
}

.color-control select {
    flex: 1;
}

.gradient-controls {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 15px;
}

.style-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.style-section h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.style-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.style-group {
    display: flex;
    flex-direction: column;
}

.style-group label {
    font-weight: bold;
    margin-bottom: 5px;
    color: #555;
}

.style-group input,
.style-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.style-group input[type="color"] {
    height: 40px;
    padding: 2px;
}

.form-actions {
    margin-top: 30px;
    text-align: center;
}

.form-actions .btn {
    margin: 0 10px;
    padding: 12px 24px;
    font-size: 16px;
}

.btn-primary {
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}

.btn:hover {
    opacity: 0.9;
}
</style>

{% INCLUDE admin/footer %}

<script>
// Variable globale pour éviter les initialisations multiples
let isInitialized = false;

// Attendre que le DOM soit complètement chargé
function initPreviewStyles() {
    // Éviter les initialisations multiples
    if (isInitialized) {
        console.log('Script déjà initialisé, sortie');
        return;
    }
    
    console.log('=== INITIALISATION PREVIEW STYLES ===');
    
    // Marquer comme initialisé
    isInitialized = true;
    
    // Fonction pour nettoyer les événements existants
    function cleanupExistingEvents() {
        // Supprimer tous les événements sur les boutons d'ajout
        const addButtons = document.querySelectorAll('.btn-add');
        addButtons.forEach(btn => {
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
        });
        
        // Supprimer tous les événements sur les inputs
        const inputs = document.querySelectorAll('input[data-target], select[data-target]');
        inputs.forEach(input => {
            const newInput = input.cloneNode(true);
            input.parentNode.replaceChild(newInput, input);
        });
    }
    
    // Nettoyer les événements existants
    cleanupExistingEvents();
    
    // Fonction pour convertir camelCase en kebab-case
    function camelToKebab(str) {
        return str.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
    }
    
    // Fonction pour appliquer les styles en temps réel
    function applyLiveStyle(input) {
        const target = input.getAttribute('data-target');
        const property = input.getAttribute('data-property');
        const value = input.value;
        
        console.log('Application du style:', { target, property, value });
        
        if (target && property) {
            const elements = document.querySelectorAll(target);
            const cssProperty = camelToKebab(property);
            
            console.log('Éléments trouvés:', elements.length, 'Propriété CSS:', cssProperty);
            
            elements.forEach(element => {
                element.style[cssProperty] = value;
                console.log('Style appliqué à', element, ':', cssProperty, '=', value);
            });
        }
    }
    
    // Gestion des dégradés
    function updateGradientControls() {
        const backgroundType = document.querySelector('select[name="container_backgroundType"]');
        const gradientControls = document.querySelector('.gradient-controls');
        
        if (backgroundType.value === 'gradient') {
            gradientControls.style.display = 'block';
            applyGradient();
        } else {
            gradientControls.style.display = 'none';
        }
    }
    
    function applyGradient() {
        const startColor = document.querySelector('input[name="container_gradientStart"]').value;
        const endColor = document.querySelector('input[name="container_gradientEnd"]').value;
        const direction = document.querySelector('select[name="container_gradientDirection"]').value;
        
        const previewBlock = document.querySelector('#preview-block');
        if (previewBlock) {
            previewBlock.style.background = `linear-gradient(${direction}, ${startColor}, ${endColor})`;
        }
    }
    
    // Gestion du drag & drop
    function initDragAndDrop() {
        const elements = document.querySelectorAll('.element');
        const container = document.querySelector('.block-elements');
        
        elements.forEach(element => {
            element.addEventListener('dragstart', function(e) {
                this.classList.add('dragging');
                e.dataTransfer.setData('text/plain', this.dataset.id);
            });
            
            element.addEventListener('dragend', function() {
                this.classList.remove('dragging');
            });
        });
        
        container.addEventListener('dragover', function(e) {
            e.preventDefault();
            const draggingElement = document.querySelector('.dragging');
            if (draggingElement) {
                const afterElement = getDragAfterElement(container, e.clientY);
                if (afterElement) {
                    container.insertBefore(draggingElement, afterElement);
                } else {
                    container.appendChild(draggingElement);
                }
            }
        });
    }
    
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.element:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    
    // Gestion des éléments (masquer/supprimer)
    function initElementControls() {
        document.querySelectorAll('.btn-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const elementId = this.closest('.element-item').dataset.elementId;
                const element = document.querySelector(`[data-id="${elementId}"]`);
                const isHidden = element.classList.contains('hidden');
                
                if (isHidden) {
                    element.classList.remove('hidden');
                    this.textContent = 'Masquer';
                } else {
                    element.classList.add('hidden');
                    this.textContent = 'Afficher';
                }
            });
        });
        
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const elementId = this.closest('.element-item').dataset.elementId;
                const element = document.querySelector(`[data-id="${elementId}"]`);
                const elementItem = this.closest('.element-item');
                
                if (confirm('Supprimer cet élément ?')) {
                    element.remove();
                    elementItem.remove();
                }
            });
        });
        
        // Gestion de l'édition
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function() {
                const elementId = this.closest('.element-item').dataset.elementId;
                openEditModal(elementId);
            });
        });
        
        // Gestion de l'ajout d'éléments
        document.querySelectorAll('.btn-add').forEach(btn => {
            btn.addEventListener('click', function() {
                const elementType = this.dataset.type;
                addNewElement(elementType);
            });
        });
    }
    
    // Fonction pour ouvrir la modal d'édition
    function openEditModal(elementId) {
        const element = document.querySelector(`[data-id="${elementId}"]`);
        const modal = document.getElementById('edit-modal');
        
        if (element && modal) {
            // Remplir la modal avec les données actuelles
            const elementType = element.dataset.type;
            const content = element.textContent.trim();
            
            document.getElementById('edit-element-type').value = elementType;
            document.getElementById('edit-element-content').value = content;
            
            // Afficher les options spécifiques selon le type
            toggleElementOptions(elementType);
            
            // Stocker l'ID de l'élément en cours d'édition
            modal.dataset.editingElement = elementId;
            
            // Afficher la modal
            modal.style.display = 'block';
        }
    }
    
    // Fonction pour fermer la modal
    function closeEditModal() {
        const modal = document.getElementById('edit-modal');
        modal.style.display = 'none';
        modal.dataset.editingElement = '';
    }
    
    // Fonction pour basculer les options selon le type d'élément
    function toggleElementOptions(elementType) {
        const buttonOptions = document.querySelector('.button-options');
        const imageOptions = document.querySelector('.image-options');
        
        buttonOptions.style.display = 'none';
        imageOptions.style.display = 'none';
        
        if (elementType === 'button') {
            buttonOptions.style.display = 'block';
        } else if (elementType === 'image') {
            imageOptions.style.display = 'block';
        }
    }
    
    // Fonction pour ajouter un nouvel élément
    function addNewElement(elementType) {
        console.log('Ajout d\'élément:', elementType);
        
        const container = document.querySelector('.block-elements');
        const elementId = 'element-' + Date.now();
        
        let elementHTML = '';
        let elementName = '';
        
        switch (elementType) {
            case 'title':
                elementHTML = `<h2 class="element" data-type="title" data-id="${elementId}">Nouveau titre</h2>`;
                elementName = 'Nouveau titre';
                break;
            case 'subtitle':
                elementHTML = `<h3 class="element" data-type="subtitle" data-id="${elementId}">Nouveau sous-titre</h3>`;
                elementName = 'Nouveau sous-titre';
                break;
            case 'content':
                elementHTML = `<div class="content element" data-type="content" data-id="${elementId}">Nouveau contenu</div>`;
                elementName = 'Nouveau contenu';
                break;
            case 'button':
                elementHTML = `<button class="btn element" data-type="button" data-id="${elementId}">Nouveau bouton</button>`;
                elementName = 'Nouveau bouton';
                break;
            case 'image':
                elementHTML = `<img class="element" data-type="image" data-id="${elementId}" src="https://via.placeholder.com/300x200" alt="Nouvelle image">`;
                elementName = 'Nouvelle image';
                break;
        }
        
        // Ajouter l'élément au conteneur
        container.insertAdjacentHTML('beforeend', elementHTML);
        
        // Ajouter l'élément à la liste de contrôle
        const elementsList = document.querySelector('.elements-list');
        const elementItemHTML = `
            <div class="element-item" data-element-id="${elementId}">
                <span class="element-name">${elementName}</span>
                <div class="element-actions">
                    <button type="button" class="btn-edit" data-action="edit">Éditer</button>
                    <button type="button" class="btn-toggle" data-action="toggle">Masquer</button>
                    <button type="button" class="btn-delete" data-action="delete">Supprimer</button>
                </div>
            </div>
        `;
        elementsList.insertAdjacentHTML('beforeend', elementItemHTML);
        
        // Réinitialiser les événements
        initElementControls();
        initDragAndDrop();
        
        console.log('Élément ajouté avec succès:', elementId);
    }
    
    // Gestion des événements de la modal
    function initModalEvents() {
        const modal = document.getElementById('edit-modal');
        const closeBtn = modal.querySelector('.close-modal');
        const cancelBtn = document.getElementById('cancel-edit');
        const saveBtn = document.getElementById('save-edit');
        const elementTypeSelect = document.getElementById('edit-element-type');
        
        // Fermer la modal
        closeBtn.addEventListener('click', closeEditModal);
        cancelBtn.addEventListener('click', closeEditModal);
        
        // Fermer en cliquant à l'extérieur
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeEditModal();
            }
        });
        
        // Changer le type d'élément
        elementTypeSelect.addEventListener('change', function() {
            toggleElementOptions(this.value);
        });
        
        // Sauvegarder les modifications
        saveBtn.addEventListener('click', function() {
            const elementId = modal.dataset.editingElement;
            const element = document.querySelector(`[data-id="${elementId}"]`);
            
            if (element) {
                const newType = document.getElementById('edit-element-type').value;
                const newContent = document.getElementById('edit-element-content').value;
                const newPosition = document.getElementById('edit-element-position').value;
                
                // Mettre à jour l'élément
                element.dataset.type = newType;
                element.textContent = newContent;
                
                // Changer la balise si nécessaire
                if (newType === 'title' && element.tagName !== 'H2') {
                    const newElement = document.createElement('h2');
                    newElement.className = element.className;
                    newElement.dataset.type = newType;
                    newElement.dataset.id = elementId;
                    newElement.textContent = newContent;
                    element.replaceWith(newElement);
                } else if (newType === 'subtitle' && element.tagName !== 'H3') {
                    const newElement = document.createElement('h3');
                    newElement.className = element.className;
                    newElement.dataset.type = newType;
                    newElement.dataset.id = elementId;
                    newElement.textContent = newContent;
                    element.replaceWith(newElement);
                } else if (newType === 'content' && element.tagName !== 'DIV') {
                    const newElement = document.createElement('div');
                    newElement.className = element.className;
                    newElement.dataset.type = newType;
                    newElement.dataset.id = elementId;
                    newElement.textContent = newContent;
                    element.replaceWith(newElement);
                }
                
                // Appliquer le positionnement
                const container = document.querySelector('.block-elements');
                if (newPosition === 'horizontal') {
                    container.classList.add('layout-horizontal');
                } else {
                    container.classList.remove('layout-horizontal');
                }
                
                // Mettre à jour le nom dans la liste
                const elementItem = document.querySelector(`[data-element-id="${elementId}"]`);
                const elementName = elementItem.querySelector('.element-name');
                elementName.textContent = newContent || `Élément ${newType}`;
            }
            
            closeEditModal();
        });
    }
    
    // Écouter les changements sur tous les inputs et selects
    const inputs = document.querySelectorAll('input[data-target], select[data-target]');
    console.log('Inputs trouvés:', inputs.length);
    
    inputs.forEach((input, index) => {
        console.log(`Input ${index}:`, input.name, 'valeur:', input.value, 'target:', input.getAttribute('data-target'));
        
        // Appliquer le style initial
        applyLiveStyle(input);
        
        // Écouter les changements
        input.addEventListener('input', function() {
            console.log('Changement input:', this.name, 'nouvelle valeur:', this.value);
            applyLiveStyle(this);
        });
        
        input.addEventListener('change', function() {
            console.log('Changement select:', this.name, 'nouvelle valeur:', this.value);
            applyLiveStyle(this);
            
            // Gestion spéciale pour le type de fond
            if (this.name === 'container_backgroundType') {
                updateGradientControls();
            }
        });
    });
    
    // Initialiser les fonctionnalités
    initDragAndDrop();
    initElementControls();
    initModalEvents();
    updateGradientControls();
}

// Initialisation unique du script
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPreviewStyles);
} else {
    initPreviewStyles();
}
</script> 
