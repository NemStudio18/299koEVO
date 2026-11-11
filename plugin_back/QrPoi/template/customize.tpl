{# Template de customization QR code #}

<style>
.qr-customize-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
    font-family: 'Inter', sans-serif;
}

.qr-customize-layout {
    display: flex;
    gap: 2rem;
    margin-top: 2rem;
}

.qr-preview-section {
    flex: 1;
    background: #fff;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    align-items: center;
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.qr-preview-box {
    margin: 2rem 0;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    display: flex;
    justify-content: center;
    align-items: center;
    min-width: 300px;
    min-height: 300px;
}

.qr-preview-box img {
    max-width: 100%;
    height: auto;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.qr-customize-form {
    flex: 2;
    background: #fff;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.form-group input[type="color"] {
    width: 50px;
    height: 40px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    vertical-align: middle;
}

.color-hex {
    margin-left: 10px;
    font-family: monospace;
    color: #666;
}

.form-group input[type="range"] {
    width: 100%;
    margin: 10px 0;
}

.form-group output {
    display: block;
    text-align: right;
    color: #666;
    font-size: 0.9rem;
}

.upload-zone {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-zone:hover, .upload-zone.dragover {
    border-color: #4a90e2;
    background: #f0f7ff;
}

.logo-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.logo-preview img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .qr-customize-layout {
        flex-direction: column;
    }
    .qr-preview-section {
        position: static;
        order: -1;
    }
}
</style>

<div class="qr-customize-container">
    <h1>Personnaliser le QR Code - {{poi.name}}</h1>
    
    <div class="qr-customize-layout">
        {# Colonne gauche : Preview #}
        <div class="qr-preview-section">
            <h2>Aperçu</h2>
            <div class="qr-preview-box">
                <img id="qr-preview" src="{{qrPreviewUrl}}" alt="QR Code Preview" />
            </div>
            
            <div class="qr-actions">
                <a href="#" id="reset-styles" class="btn btn-secondary">Réinitialiser</a>
            </div>
        </div>
        
        {# Colonne droite : Formulaire #}
        <div class="qr-customize-form" style="position: relative; {% if not isPremium %}overflow: hidden;{% endif %}">
            <div style="margin-bottom: 1rem;">
                <a href="{{backUrl}}" style="text-decoration: none; color: #667eea; font-weight: bold;">
                    <i class="fa-solid fa-arrow-left"></i> Retour
                </a>
            </div>

            {% if not isPremium %}
            <div class="premium-lock-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); backdrop-filter: blur(4px); z-index: 100; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 2rem;">
                <div style="background: white; padding: 2.5rem; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 400px; border: 2px solid #667eea;">
                    <i class="fa-solid fa-crown" style="font-size: 3rem; color: #ffd700; margin-bottom: 1rem;"></i>
                    <h2 style="color: #333; margin-bottom: 1rem;">Personnalisation Premium</h2>
                    <p style="color: #666; margin-bottom: 1.5rem;">Démarquez-vous avec un QR Code unique ! La personnalisation des couleurs et l'ajout de logo sont réservés aux abonnés Premium.</p>
                    <a href="/page/offres-premium-18" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 0.8rem 1.5rem; font-weight: bold;">Découvrir le Premium</a>
                </div>
            </div>
            {% endif %}

            <form method="POST" action="{{saveUrl}}" id="customize-form" enctype="multipart/form-data">

                <input type="hidden" name="token" value="{{csrfToken}}" />
                
                {# Couleur du QR #}
                <div class="form-group">
                    <label for="color">Couleur du QR Code</label>
                    <div style="display:flex; align-items:center;">
                        <input type="color" id="color" name="color" value="#{{style.color}}" />
                        <span class="color-hex" id="color-hex">#{{style.color}}</span>
                    </div>
                </div>
                
                {# Couleur de fond #}
                <div class="form-group">
                    <label for="bgcolor">Couleur de fond</label>
                    <div style="display:flex; align-items:center;">
                        <input type="color" id="bgcolor" name="bgcolor" value="#{{style.bgcolor}}" />
                        <span class="color-hex" id="bgcolor-hex">#{{style.bgcolor}}</span>
                    </div>
                </div>
                
                {# Taille #}
                <div class="form-group">
                    <label for="size">Taille (px)</label>
                    <input type="range" id="size" name="size" min="100" max="1000" step="50" value="{{style.size}}" />
                    <output for="size" id="size-output">{{style.size}}px</output>
                </div>
                
                {# Marge (quiet zone) #}
                <div class="form-group">
                    <label for="qzone">Marge autour du QR</label>
                    <input type="range" id="qzone" name="qzone" min="0" max="10" value="{{style.qzone}}" />
                    <output for="qzone" id="qzone-output">{{style.qzone}}</output>
                </div>

                {# Texte personnalisé #}
                <div class="form-group">
                    <label for="custom_text">Texte personnalisé (sous le QR)</label>
                    <input type="text" id="custom_text" name="custom_text" value="{{style.custom_text}}" placeholder="Ex: Scannez moi !" style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px;" />
                </div>

                {# Couleur du texte #}
                <div class="form-group">
                    <label for="text_color">Couleur du texte</label>
                    <div style="display:flex; align-items:center;">
                        <input type="color" id="text_color" name="text_color" value="#{{style.text_color}}" />
                        <span class="color-hex" id="text_color-hex">#{{style.text_color}}</span>
                    </div>
                </div>
                
                 {# Format #}
                 <input type="hidden" name="format" value="png" />
                
                {# Upload Logo #}
                <div class="form-group">
                    <label>Logo (optionnel, affichera au centre)</label>
                    
                    {% if style.logo_path %}
                        <div class="logo-preview">
                            <img src="{{style.logo_path}}" alt="Logo actuel" />
                            <span>Logo actuel</span>
                            <button type="button" id="remove-logo-btn" data-url="{{removeLogoUrl}}" class="btn btn-danger btn-sm">Supprimer</button>
                        </div>
                    {% else %}
                        <div class="upload-zone" id="upload-zone">
                            <input type="file" id="logo-file" name="logo" accept="image/png,image/jpeg,image/gif" style="display:none" />
                            <p>Glissez votre logo ici ou <a href="#" id="browse-logo">parcourez</a></p>
                            <small class="text-muted">PNG, JPG ou GIF - Max 200 KB</small>
                            <div id="file-name" style="margin-top:10px; font-weight:bold; color:#4a90e2;"></div>
                        </div>
                    {% endif %}
                </div>
                
                <button type="submit" class="btn btn-success" style="width:100%; margin-top:1rem;">Sauvegarder les modifications</button>
            </form>
        </div>
    </div>
</div>

{# Script JS inline #}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const previewImg = document.getElementById('qr-preview');
    const form = document.getElementById('customize-form');
    // Base URL for preview (without query params)
    const basePreviewUrl = '{{qrPreviewUrl}}'.split('?')[0];
    
    // Inputs
    const colorInput = document.getElementById('color');
    const bgcolorInput = document.getElementById('bgcolor');
    const sizeInput = document.getElementById('size');
    const qzoneInput = document.getElementById('qzone');
    const textInput = document.getElementById('custom_text');
    const textColorInput = document.getElementById('text_color');
    
    // Outputs
    const sizeOutput = document.getElementById('size-output');
    const qzoneOutput = document.getElementById('qzone-output');
    const colorHex = document.getElementById('color-hex');
    const bgcolorHex = document.getElementById('bgcolor-hex');
    const textColorHex = document.getElementById('text_color-hex');
    
    // Upload
    const uploadZone = document.getElementById('upload-zone');
    const fileInput = document.getElementById('logo-file');
    const browseLink = document.getElementById('browse-logo');
    const removeBtn = document.getElementById('remove-logo-btn');
    
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }
    
    // Update Preview
    const updatePreview = debounce(function() {
        const color = colorInput.value.replace('#', '');
        const bgcolor = bgcolorInput.value.replace('#', '');
        const size = sizeInput.value;
        const qzone = qzoneInput.value;
        
        const params = new URLSearchParams({
            color: color,
            bgcolor: bgcolor,
            size: size,
            qzone: qzone,
            custom_text: textInput.value,
            text_color: textColorInput.value.replace('#', '')
        });
        
        previewImg.src = `${basePreviewUrl}?${params.toString()}`;
    }, 300);
    
    // Listeners
    [colorInput, bgcolorInput, sizeInput, qzoneInput, textInput, textColorInput].forEach(input => {
        if(input) {
            input.addEventListener('input', function() {
                updatePreview();
                if(input === sizeInput) sizeOutput.textContent = input.value + 'px';
                if(input === qzoneInput) qzoneOutput.textContent = input.value;
                if(input === colorInput) colorHex.textContent = input.value;
                if(input === bgcolorInput) bgcolorHex.textContent = input.value;
                if(input === textColorInput) textColorHex.textContent = input.value;
            });
        }
    });
    
    // Upload Interaction
    if (uploadZone && fileInput) {
        browseLink.addEventListener('click', (e) => {
            e.preventDefault();
            fileInput.click();
        });
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });
        
        fileInput.addEventListener('change', () => {
             if (fileInput.files.length) {
                handleFileSelect(fileInput.files[0]);
            }
        });
        
        function handleFileSelect(file) {
            const fileNameDiv = document.getElementById('file-name');
            if(fileNameDiv) {
                fileNameDiv.textContent = "Fichier sélectionné : " + file.name;
                // Optional: Client side image preview?
                // For now just showing name to verify selection.
            }
        }
    }
    
    // Remove Logo
    if (removeBtn) {
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if(confirm('Voulez-vous vraiment supprimer le logo ?')) {
                // Create a temporary form to submit POST request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.dataset.url; // Use URL from data attribute
                
                // Add CSRF token if present in main form
                const mainToken = document.querySelector('input[name="token"]');
                if (mainToken) {
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = 'token';
                    hiddenField.value = mainToken.value;
                    form.appendChild(hiddenField);
                }
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    
    // Reset
    const resetBtn = document.getElementById('reset-styles');
    if(resetBtn) {
        resetBtn.addEventListener('click', (e) => {
            e.preventDefault();
            // Reset values to default
            colorInput.value = '#000000';
            bgcolorInput.value = '#FFFFFF';
            sizeInput.value = 300;
            qzoneInput.value = 1;
            textInput.value = '';
            textColorInput.value = '#000000';
            
            // Trigger events
            colorInput.dispatchEvent(new Event('input'));
            bgcolorInput.dispatchEvent(new Event('input'));
            sizeInput.dispatchEvent(new Event('input'));
            qzoneInput.dispatchEvent(new Event('input'));
        });
    }
});
</script>
