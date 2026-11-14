/**
 * PellEditor JS - Premium Evolution (Definitive Fixed Version)
 * High-end WYSIWYG with vertical contextual tools and demo safety.
 */

(function() {
    'use strict';

    // Global Utilities
    const getActionTranslation = (key, def) => (window.PELL_EDITOR_TRANSLATIONS && window.PELL_EDITOR_TRANSLATIONS[key.toLowerCase()]) || def;

    const getWordCount = (text) => {
        const words = text.trim().split(/\s+/);
        return words[0] === '' ? 0 : words.length;
    };

    const updateStatusBar = (instance, wrapper, trans) => {
        const status = wrapper.querySelector('.pell-status');
        if (!status) return;
        const text = instance.content.innerText || '';
        const words = getWordCount(text);
        const chars = text.length;
        status.innerHTML = `<div>${trans.wordcount || 'Mots'} : <span>${words}</span></div><div>Caractères : <span>${chars}</span></div>`;
    };

    // Print Functionality (Isolated)
    window.pelleditorPrint = (instance) => {
        const content = instance.content.innerHTML;
        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);
        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(`<html><head><title>Print</title><style>body{font-family:sans-serif;padding:20px;}img{max-width:100%;}table{border-collapse:collapse;width:100%;}td,th{border:1px solid #ddd;padding:8px;}</style></head><body>${content}</body></html>`);
        doc.close();
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(() => { document.body.removeChild(iframe); }, 1000);
    };

    // Export Functionality
    window.pelleditorExport = (format, instance) => {
        const form = document.createElement('form');
        form.method = 'POST';
        
        // Detect workspace prefix from URL
        const path = window.location.pathname;
        let prefix = '';
        
        // If we are in admin, use /admin
        if (path.startsWith('/admin')) {
            prefix = '/admin';
        } else {
            // Check if we have a workspace slug (first segment if not a known plugin root)
            const segments = path.split('/').filter(s => s.length > 0);
            if (segments.length > 0) {
                // List of "root" level plugins that don't need a prefix if accessed at root
                // But usually in this app, everything is /slug/plugin/...
                // Except when we are truly at local root.
                // Simple heuristic: if the first segment is NOT 'docs' or 'Mail' etc at root, it's likely a slug.
                if (!['docs', 'Mail', 'admin'].includes(segments[0])) {
                    prefix = '/' + segments[0];
                }
            }
        }
        
        form.action = prefix + '/pelleditor/export';
        form.target = '_blank';
        
        const fields = { 'format': format, 'html': instance.content.innerHTML, 'title': 'Document' };
        for (const [name, value] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = name; input.value = value;
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    // Encryption Stub / Logic
    window.PellEditor_Encrypt = (instance) => {
        if (window.PELL_EDITOR_IS_DEMO) {
            window.PellEditor_Modal('Sécurité', 'Le chiffrement n\'est pas disponible sur cette page de démonstration.', 'warn');
            return;
        }
        
        const isSodiumReady = typeof window.libsodium !== 'undefined' || typeof window.sodium !== 'undefined';
        const isKeyManagerReady = typeof window.KeyManager !== 'undefined';

        if (isSodiumReady || isKeyManagerReady) {
            window.PellEditor_Modal('Sécurité & Chiffrement', 'Le module de sécurité est **actif**. Vos documents peuvent être chiffrés de bout en bout via Libsodium.', 'info');
        } else {
            window.PellEditor_Modal('Sécurité', 'Le gestionnaire de clés ou Libsodium ne semble pas chargé. Vérifiez l\'activation des plugins correspondants.', 'warn');
        }
    };

    // Premium Modal System
    window.PellEditor_Modal = (title, content, type = 'info', callback = null, defaultValue = '') => {
        const overlay = document.createElement('div');
        overlay.className = 'pell-modal-overlay';
        overlay.style = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);backdrop-filter:blur(8px);z-index:200000;display:flex;align-items:center;justify-content:center;animation:pellFadeIn 0.3s forwards;";
        
        const modal = document.createElement('div');
        modal.className = 'pell-modal';
        modal.style = "background:#fff;padding:30px;border-radius:24px;width:95%;max-width:500px;box-shadow:0 30px 60px -12px rgba(0,0,0,0.3);transform:translateY(20px);opacity:0;transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);";
        
        let icon = 'fa-circle-info', color = '#3b82f6';
        if (type === 'error') { icon = 'fa-circle-exclamation'; color = '#ef4444'; }
        if (type === 'warn') { icon = 'fa-triangle-exclamation'; color = '#f59e0b'; }
        if (type === 'confirm' || type === 'prompt' || type === 'password') { icon = 'fa-lock'; color = '#6366f1'; }

        const showInput = (type === 'prompt' || type === 'password');
        const inputType = type === 'password' ? 'password' : 'text';

        modal.innerHTML = `
            <div style="display:flex;align-items:center;gap:18px;margin-bottom:22px;">
                <div style="width:48px;height:48px;border-radius:14px;background:${color}15;color:${color};display:flex;align-items:center;justify-content:center;font-size:24px;">
                    <i class="fa-solid ${icon}"></i>
                </div>
                <h3 style="margin:0;font-size:20px;font-weight:700;color:#1e293b;letter-spacing:-0.5px;">${title}</h3>
            </div>
            <div style="margin-bottom:28px;color:#64748b;font-size:15px;line-height:1.6;">${content}</div>
            ${showInput ? `<input type="${inputType}" id="modal-input" style="width:100%;padding:12px;margin-bottom:20px;border:1px solid #e2e8f0;border-radius:10px;outline:none;font-size:15px;" value="${defaultValue}" autocomplete="new-password">` : ''}
            <div style="display:flex;justify-content:flex-end;gap:12px;">
                ${(type === 'confirm' || showInput) ? `<button class="pell-button secondary" id="modal-cancel" style="background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;padding:0 24px;height:40px;border-radius:12px;cursor:pointer;">Annuler</button>` : ''}
                <button class="pell-button" id="modal-ok" style="background:${color};color:#fff;padding:0 28px;height:40px;border-radius:12px;font-weight:600;box-shadow:0 4px 12px ${color}40;cursor:pointer;">OK</button>
            </div>
        `;
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        requestAnimationFrame(() => { modal.style.transform = 'translateY(0)'; modal.style.opacity = '1'; });

        const close = () => {
            modal.style.transform = 'translateY(-20px)'; modal.style.opacity = '0'; overlay.style.opacity = '0';
            setTimeout(() => { if(document.body.contains(overlay)) document.body.removeChild(overlay); }, 300);
        };

        const inputField = modal.querySelector('#modal-input');
        if (inputField) {
            setTimeout(() => inputField.focus(), 100);
            inputField.onkeydown = (e) => {
                if (e.key === 'Enter') modal.querySelector('#modal-ok').click();
                if (e.key === 'Escape') modal.querySelector('#modal-cancel')?.click();
            };
        }

        modal.querySelector('#modal-ok').onclick = (e) => { 
            e.stopPropagation(); 
            const val = inputField ? inputField.value : true;
            close(); 
            if (callback) setTimeout(() => callback(val), 50); 
        };
        if (modal.querySelector('#modal-cancel')) {
            modal.querySelector('#modal-cancel').onclick = (e) => { 
                e.stopPropagation(); 
                close(); 
                if (callback) setTimeout(() => callback(false), 50); 
            };
        }
    };

    // Table Borders
    window.PellTable_ManageBorders = () => {
        const sel = window.getSelection();
        const td = sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode.closest('td, th') : sel.anchorNode.closest('td, th');
        if (!td) return;
        
        const html = `<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:15px;">
            <button class="pell-button" style="background:#2563eb;color:#fff;border-radius:10px;padding:10px;" onclick="window.applyBorder('all')">Tout</button>
            <button class="pell-button secondary" style="background:#f1f5f9;border-radius:10px;padding:10px;" onclick="window.applyBorder('none')">Aucun</button>
            <div style="grid-column:span 2;height:1px;background:#e2e8f0;margin:5px 0;"></div>
            <button class="pell-button secondary" style="background:#f8fafc;border-radius:10px;padding:10px;" onclick="window.applyBorder('Top')">Haut</button>
            <button class="pell-button secondary" style="background:#f8fafc;border-radius:10px;padding:10px;" onclick="window.applyBorder('Bottom')">Bas</button>
            <button class="pell-button secondary" style="background:#f8fafc;border-radius:10px;padding:10px;" onclick="window.applyBorder('Left')">Gauche</button>
            <button class="pell-button secondary" style="background:#f8fafc;border-radius:10px;padding:10px;" onclick="window.applyBorder('Right')">Droite</button>
        </div>`;
        window.PellEditor_Modal('Gérer les Bordures', html, 'info');
        
        window.applyBorder = (side) => {
            const style = '1px solid #e2e8f0';
            if (side === 'all') ['borderTop', 'borderBottom', 'borderLeft', 'borderRight'].forEach(s => td.style[s] = style);
            else if (side === 'none') ['borderTop', 'borderBottom', 'borderLeft', 'borderRight'].forEach(s => td.style[s] = '0px');
            else {
                const s = 'border' + side;
                td.style[s] = (td.style[s] === style || td.style[s].includes('1px solid')) ? '0px' : style;
            }
        };
    };

    // Action Trigger (Floating Button to open toolbar)
    const PellEditor_ActionTrigger = {
        el: null,
        target: null,
        type: null,
        init: () => {
            if (document.getElementById('pell-action-trigger')) return;
            const btn = document.createElement('button');
            btn.id = 'pell-action-trigger';
            btn.className = 'pell-action-trigger';
            btn.innerHTML = '<i class="fa-solid fa-ellipsis-vertical"></i>';
            btn.title = "Options";
            document.body.appendChild(btn);
            PellEditor_ActionTrigger.el = btn;
            
            btn.onclick = (e) => {
                e.stopPropagation();
                if (PellEditor_ActionTrigger.target) {
                    PellEditor_ContextToolbar.show(PellEditor_ActionTrigger.target, PellEditor_ActionTrigger.type);
                    PellEditor_ActionTrigger.hide();
                }
            };
        },
        show: (target, type) => {
            PellEditor_ActionTrigger.target = target;
            PellEditor_ActionTrigger.type = type;
            const rect = target.getBoundingClientRect();
            const btn = PellEditor_ActionTrigger.el;
            
            // Position at top-right of target
            btn.style.top = `${window.scrollY + rect.top + 10}px`;
            btn.style.left = `${window.scrollX + rect.left + rect.width - 40}px`;
            btn.style.display = 'flex';
            btn.classList.add('visible');
        },
        hide: () => {
            if (PellEditor_ActionTrigger.el) {
                PellEditor_ActionTrigger.el.classList.remove('visible');
                setTimeout(() => { if (!PellEditor_ActionTrigger.el.classList.contains('visible')) PellEditor_ActionTrigger.el.style.display = 'none'; }, 200);
            }
        }
    };

    // Context Toolbar Singleton
    const PellEditor_ContextToolbar = {
        el: null,
        target: null,
        init: () => {
            if (document.getElementById('pell-context-toolbar')) return;
            const tb = document.createElement('div');
            tb.id = 'pell-context-toolbar';
            tb.className = 'pell-context-toolbar';
            document.body.appendChild(tb);
            PellEditor_ContextToolbar.el = tb;
            tb.onmousedown = (e) => e.preventDefault();
        },
        show: (target, type) => {
            PellEditor_ContextToolbar.target = target;
            const rect = target.getBoundingClientRect();
            const tb = PellEditor_ContextToolbar.el;
            tb.innerHTML = '';
            
            // Close button inside toolbar
            const closeBtn = document.createElement('button');
            closeBtn.className = 'close-tb';
            closeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            closeBtn.onclick = () => PellEditor_ContextToolbar.hide();
            tb.appendChild(closeBtn);

            const content = document.createElement('div');
            content.className = 'tb-content';
            
            if (type === 'table') {
                content.innerHTML = `
                    <button onclick="PellTable_AddRow('below')"><i class="fa-solid fa-plus"></i> <span>Ligne après</span></button>
                    <button onclick="PellTable_AddCol('right')"><i class="fa-solid fa-plus-square"></i> <span>Colonne après</span></button>
                    <div class="sep"></div>
                    <button onclick="PellTable_SetColor()"><i class="fa-solid fa-fill-drip"></i> <span>Couleur fond</span></button>
                    <button onclick="PellTable_ManageBorders()"><i class="fa-solid fa-border-all"></i> <span>Bordures</span></button>
                    <div class="sep"></div>
                    <button class="danger" onclick="PellTable_DelRow()"><i class="fa-solid fa-minus"></i> <span>Supprimer ligne</span></button>
                    <button class="danger" onclick="PellTable_DelCol()"><i class="fa-solid fa-minus-square"></i> <span>Supprimer colonne</span></button>
                    <button class="danger" onclick="PellTable_Delete()"><i class="fa-solid fa-trash"></i> <span>Supprimer tableau</span></button>
                `;
            } else if (type === 'image') {
                content.innerHTML = `
                    <button onclick="document.execCommand('justifyLeft')"><i class="fa-solid fa-align-left"></i> <span>Aligner Gauche</span></button>
                    <button onclick="document.execCommand('justifyCenter')"><i class="fa-solid fa-align-center"></i> <span>Aligner Centre</span></button>
                    <button onclick="document.execCommand('justifyRight')"><i class="fa-solid fa-align-right"></i> <span>Aligner Droite</span></button>
                    <div class="sep"></div>
                    <button class="danger" onclick="PellImage_Delete()"><i class="fa-solid fa-trash-can"></i> <span>Supprimer image</span></button>
                `;
            }
            tb.appendChild(content);
            
            tb.style.top = `${window.scrollY + rect.top}px`;
            tb.style.left = `${window.scrollX + rect.left + rect.width + 15}px`;
            tb.classList.add('show');
            
            // Adjust if out of viewport
            const tbRect = tb.getBoundingClientRect();
            if (tbRect.right > window.innerWidth) tb.style.left = `${window.scrollX + rect.left - tbRect.width - 15}px`;
        },
        hide: () => { if (PellEditor_ContextToolbar.el) PellEditor_ContextToolbar.el.classList.remove('show'); }
    };

    // Table Structure Logic
    window.PellTable_AddRow = (pos) => {
        const sel = window.getSelection();
        const td = sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode.closest('td, th') : sel.anchorNode.closest('td, th');
        if (!td) return;
        const tr = td.parentNode;
        const newTr = tr.cloneNode(true);
        Array.from(newTr.cells).forEach(c => { c.innerHTML = '<br>'; c.style.backgroundColor = ''; });
        if (pos === 'above') tr.parentNode.insertBefore(newTr, tr);
        else tr.parentNode.insertBefore(newTr, tr.nextSibling);
    };
    window.PellTable_AddCol = (pos) => {
        const sel = window.getSelection();
        const td = sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode.closest('td, th') : sel.anchorNode.closest('td, th');
        if (!td) return;
        const idx = td.cellIndex;
        Array.from(td.closest('table').rows).forEach(row => {
            const newCell = row.insertCell(pos === 'left' ? idx : idx + 1);
            newCell.innerHTML = '<br>'; newCell.style.padding = '12px'; newCell.style.border = '1px solid #e2e8f0';
        });
    };
    window.PellTable_DelRow = () => {
        const sel = window.getSelection();
        const td = sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode.closest('td, th') : sel.anchorNode.closest('td, th');
        if (td) td.parentNode.remove();
        PellEditor_ContextToolbar.hide();
    };
    window.PellTable_DelCol = () => {
        const sel = window.getSelection();
        const td = sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode.closest('td, th') : sel.anchorNode.closest('td, th');
        if (!td) return;
        const idx = td.cellIndex;
        Array.from(td.closest('table').rows).forEach(row => row.deleteCell(idx));
        PellEditor_ContextToolbar.hide();
    };
    window.PellTable_Delete = () => {
        const sel = window.getSelection();
        const td = sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode.closest('td, th') : sel.anchorNode.closest('td, th');
        if (td) td.closest('table').remove();
        PellEditor_ContextToolbar.hide();
    };
    window.PellTable_SetColor = () => {
        const sel = window.getSelection();
        const td = sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode.closest('td, th') : sel.anchorNode.closest('td, th');
        if (!td) return;
        const current = td.style.backgroundColor || '#ffffff';
        PellEditor_Modal('Couleur de fond', `Choisir une couleur :<br><input type="color" id="cell-bg-val" style="width:100%;height:45px;border:none;margin-top:10px;" value="${current}">`, 'prompt', (ok) => {
            if (ok) td.style.backgroundColor = document.getElementById('cell-bg-val').value;
        });
    };
    window.PellImage_Delete = () => {
        if (PellEditor_ContextToolbar.target) PellEditor_ContextToolbar.target.remove();
        PellEditor_ContextToolbar.hide();
    };

    // Main Initialization
    const initPell = () => {
        // Auto-init textareas with class 'editor'
        document.querySelectorAll('textarea.editor').forEach(textarea => {
            if (textarea.dataset.pelleditorInitialized) return;
            
            // Create a wrapper and a div for Pell
            const id = textarea.id || 'pell-' + Math.random().toString(36).substr(2, 9);
            textarea.id = id;
            
            const editorDiv = document.createElement('div');
            editorDiv.className = 'pelleditor-instance';
            editorDiv.dataset.input = id;
            editorDiv.innerHTML = textarea.value;
            textarea.parentNode.insertBefore(editorDiv, textarea);
            textarea.style.display = 'none';
        });

        const editors = document.querySelectorAll('.pelleditor-instance');
        if (editors.length === 0) return;

        const translations = window.PELL_EDITOR_TRANSLATIONS || {};
        const isDemo = window.PELL_EDITOR_IS_DEMO === true;
        
        PellEditor_ContextToolbar.init();
        PellEditor_ActionTrigger.init();

        editors.forEach(editor => {
            if (editor.dataset.pelleditorInitialized) return;
            editor.dataset.pelleditorInitialized = "true";

            const input = document.getElementById(editor.dataset.input);
            if (!input) return;

            const initialHTML = editor.innerHTML.trim();
            editor.innerHTML = '';
            const instance = pell.init({
                element: editor,
                onChange: html => {
                    // Déléguer au bridge ProseMirror si disponible — il est la source de vérité
                    const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                    if (bridge) {
                        // Le bridge gère textarea.value via _syncTextarea() — on ne touche à rien ici
                        // RÈGLE : Pell.onChange() ne doit PAS écrire dans input.value directement
                    } else {
                        // Fallback sans ProseMirrorService (mode dégradé)
                        input.value = html;
                    }
                    updateStatusBar(instance, wrapper, translations);
                },
                defaultParagraphSeparator: 'p',
                actions: [
                    { name: 'formatBlock', title: getActionTranslation('format', 'Format'), icon: `<i class="fa-solid fa-paragraph"></i> <span class="pell-btn-text">${getActionTranslation('format', 'Format')}</span> <i class="fa-solid fa-chevron-down"></i>`, result: () => {} },
                    { name: 'bold', title: getActionTranslation('bold', 'Gras'), icon: '<i class="fa-solid fa-bold"></i>', result: () => {
                        console.log('[Pell] Action: bold');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('bold') : pell.exec('bold');
                    }},
                    { name: 'italic', title: getActionTranslation('italic', 'Italique'), icon: '<i class="fa-solid fa-italic"></i>', result: () => {
                        console.log('[Pell] Action: italic');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('italic') : pell.exec('italic');
                    }},
                    { name: 'underline', title: getActionTranslation('underline', 'Souligné'), icon: '<i class="fa-solid fa-underline"></i>', result: () => {
                        console.log('[Pell] Action: underline');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('underline') : pell.exec('underline');
                    }},
                    { name: 'strikethrough', title: getActionTranslation('strike-through', 'Barré'), icon: '<i class="fa-solid fa-strikethrough pell-btn-strikethrough"></i>', result: () => {
                        console.log('[Pell] Action: strikethrough');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('strikethrough') : pell.exec('strikethrough');
                    }},
                    { name: 'fontFamily', title: getActionTranslation('font', 'Police'), icon: '<i class="fa-solid fa-font pell-btn-fontfamily"></i>', result: () => {
                        console.log('[Pell] Action: fontFamily (dropdown pending)');
                    } },
                    { name: 'foreColor', title: getActionTranslation('color', 'Couleur'), icon: '<i class="fa-solid fa-palette pell-btn-forecolor"></i>', result: () => {
                        console.log('[Pell] Action: foreColor (dropdown pending)');
                    } },
                    { name: 'hiliteColor', title: getActionTranslation('highlight', 'Surligner'), icon: '<i class="fa-solid fa-highlighter pell-btn-hilitecolor"></i>', result: () => {
                        console.log('[Pell] Action: hiliteColor (dropdown pending)');
                    } },
                    { name: 'justifyLeft', title: getActionTranslation('left', 'Gauche'), icon: '<i class="fa-solid fa-align-left"></i>', result: () => {
                        console.log('[Pell] Action: justifyLeft');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('justifyLeft') : pell.exec('justifyLeft');
                    }},
                    { name: 'justifyCenter', title: getActionTranslation('center', 'Centrer'), icon: '<i class="fa-solid fa-align-center"></i>', result: () => {
                        console.log('[Pell] Action: justifyCenter');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('justifyCenter') : pell.exec('justifyCenter');
                    }},
                    { name: 'justifyRight', title: getActionTranslation('right', 'Droite'), icon: '<i class="fa-solid fa-align-right"></i>', result: () => {
                        console.log('[Pell] Action: justifyRight');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('justifyRight') : pell.exec('justifyRight');
                    }},
                    { name: 'olist', title: getActionTranslation('olist', 'Liste numérotée'), icon: '<i class="fa-solid fa-list-ol"></i>', result: () => {
                        console.log('[Pell] Action: olist');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('olist') : pell.exec('insertOrderedList');
                    }},
                    { name: 'ulist', title: getActionTranslation('ulist', 'Liste à puces'), icon: '<i class="fa-solid fa-list-ul"></i>', result: () => {
                        console.log('[Pell] Action: ulist');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('ulist') : pell.exec('insertUnorderedList');
                    }},
                    { name: 'insertion', title: getActionTranslation('insertion', 'Insérer'), icon: '<i class="fa-solid fa-plus pell-btn-insertion"></i> <i class="fa-solid fa-chevron-down"></i>', result: () => {
                        console.log('[Pell] Action: insertion');
                    } },
                    { name: 'undo', title: getActionTranslation('undo', 'Annuler'), icon: '<i class="fa-solid fa-rotate-left"></i>', result: () => {
                        console.log('[Pell] Action: undo');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('undo') : pell.exec('undo');
                    }},
                    { name: 'redo', title: getActionTranslation('redo', 'Rétablir'), icon: '<i class="fa-solid fa-rotate-right"></i>', result: () => {
                        console.log('[Pell] Action: redo');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        bridge ? bridge.command('redo') : pell.exec('redo');
                    }},
                    { name: 'clear', title: getActionTranslation('clear', 'Effacer'), icon: '<i class="fa-solid fa-eraser"></i>', result: () => {
                        console.log('[Pell] Action: clear');
                        const bridge = editor.closest('.pelleditor-wrapper')?._pmBridge;
                        if (bridge) bridge.command('clear');
                        else pell.exec('removeFormat');
                    } },
                    { name: 'maximize', title: getActionTranslation('fullscreen', 'Plein écran'), icon: '<i class="fa-solid fa-maximize"></i>', result: () => {
                        const wrap = editor.closest('.pelleditor-wrapper');
                        wrap.classList.toggle('full-screen');
                    }}
                ]
            });
            
            // ... (rest of wrapper logic) ...
            const wrapper = document.createElement('div');
            wrapper.className = 'pelleditor-wrapper';
            if (isDemo) wrapper.classList.add('is-demo');
            editor.parentNode.insertBefore(wrapper, editor);
            wrapper.appendChild(editor);

            const status = document.createElement('div');
            status.className = 'pell-status';
            wrapper.appendChild(status);

            // Add specific class for Format button to handle width/overlap
            const formatBtn = wrapper.querySelector('button[title="' + (translations.format || 'Format') + '"]');
            if (formatBtn) formatBtn.classList.add('pell-button-format');

            const actionbar = wrapper.querySelector('.pell-actionbar');
            const leftSide = document.createElement('div'); leftSide.className = 'pell-actionbar-left';
            const rightSide = document.createElement('div'); rightSide.className = 'pell-actionbar-right';
            while (actionbar.firstChild) leftSide.appendChild(actionbar.firstChild);
            actionbar.appendChild(leftSide); actionbar.appendChild(rightSide);

            // Menu "Plus..." intégré (Export PDF, DOCX, Markdown, HTML + Imprimer)
            const plusBtn = document.createElement('button');
            plusBtn.type = 'button';
            plusBtn.className = 'pell-button';
            plusBtn.title = translations.plus || 'Plus...';
            plusBtn.innerHTML = `<i class="fa-solid fa-file-export"></i> <span>${translations.plus || 'Export'}</span> <i class="fa-solid fa-chevron-down"></i>`;
            rightSide.appendChild(plusBtn);
            const plusWrap = setupDropdown(plusBtn, [
                { label: 'Exporter en PDF', icon: 'fa-file-pdf', click: () => window.pelleditorExport('pdf', instance) },
                { label: 'Exporter en Word (.docx)', icon: 'fa-file-word', click: () => window.pelleditorExport('docx', instance) },
                { label: 'Exporter en Markdown (.md)', icon: 'fa-brands fa-markdown', click: () => window.pelleditorExport('md', instance) },
                { label: 'Exporter en HTML (.html)', icon: 'fa-code', click: () => window.pelleditorExport('html', instance) },
                { label: translations.print || 'Imprimer', icon: 'fa-print', click: () => window.pelleditorPrint(instance) }
            ]);
            plusWrap.parentNode.classList.add('pell-dropdown-right');

            injectDropdowns(instance, leftSide, translations, isDemo);

            if (initialHTML) instance.content.innerHTML = initialHTML;
            updateStatusBar(instance, wrapper, translations);

            // Context events
            editor.addEventListener('mousemove', (e) => {
                const table = e.target.closest('table'), img = e.target.closest('img');
                if (img) PellEditor_ActionTrigger.show(img, 'image');
                else if (table) PellEditor_ActionTrigger.show(table, 'table');
                else {
                    // Si on n'est pas sur le bouton lui-même, on peut cacher après un petit délai
                    if (e.target.id !== 'pell-action-trigger') {
                        // Check if mouse is near the trigger or target
                        const trigger = document.getElementById('pell-action-trigger');
                        if (trigger && !trigger.matches(':hover')) PellEditor_ActionTrigger.hide();
                    }
                }
            });

            editor.addEventListener('click', (e) => {
                if (!e.target.closest('table') && !e.target.closest('img') && e.target.id !== 'pell-action-trigger') {
                    PellEditor_ContextToolbar.hide();
                }
            });
        });
    };

    const injectDropdowns = (instance, container, trans, isDemo) => {
        const query = (cls) => container.querySelector(`.${cls}`)?.closest('button');
        
        // Format
        const formatBtn = Array.from(container.querySelectorAll('button')).find(b => b.title.toLowerCase().includes('format'));
        if (formatBtn) setupDropdown(formatBtn, [
            { label: trans.paragraph || 'Paragraphe', cmd: 'formatBlock', val: 'p', icon: 'fa-paragraph' },
            { label: trans.heading1 || 'Titre 1', cmd: 'formatBlock', val: 'h1', icon: 'fa-heading' },
            { label: trans.heading2 || 'Titre 2', cmd: 'formatBlock', val: 'h2', icon: 'fa-heading' },
            { label: trans.heading3 || 'Titre 3', cmd: 'formatBlock', val: 'h3', icon: 'fa-heading' },
            { label: trans.quote || 'Citation', cmd: 'formatBlock', val: 'blockquote', icon: 'fa-quote-left' },
            { label: trans.code || 'Code', cmd: 'formatBlock', val: 'pre', icon: 'fa-code' }
        ]);

        // Fonts
        const fBtn = query('pell-btn-fontfamily');
        if (fBtn) {
            const fonts = ['Inter', 'Outfit', 'Roboto', 'Georgia', 'Courier New', 'Monospace'];
            const menu = setupDropdown(fBtn, [], 'font-list');
            fonts.forEach(f => {
                const b = document.createElement('button'); b.innerText = f; b.style.fontFamily = f;
                b.style.textAlign = 'left'; b.style.padding = '8px 15px'; b.style.width = '100%';
                b.onclick = (e) => {
                    e.stopPropagation();
                    const wrapper = fBtn.closest('.pelleditor-wrapper');
                    const bridge = wrapper?._pmBridge;
                    if (bridge) bridge.command('fontFamily', f);
                    else pell.exec('fontName', f);
                    menu.classList.remove('show');
                };
                menu.appendChild(b);
            });
        }

        // Colors
        [{cls: 'pell-btn-forecolor', cmd: 'foreColor'}, {cls: 'pell-btn-hilitecolor', cmd: 'hiliteColor'}].forEach(it => {
            const btn = query(it.cls);
            if (btn) {
                const colors = it.cmd === 'hiliteColor' ? ['#fecaca','#fed7aa','#fef08a','#bbf7d0','#99f6e4','#bfdbfe','#ffffff'] : ['#000000','#475569','#ef4444','#f97316','#eab308','#22c55e','#3b82f6'];
                const menu = setupDropdown(btn, [], 'color-grid');
                colors.forEach(c => {
                    const s = document.createElement('button'); s.className = 'color-swatch'; s.style.backgroundColor = c;
                    s.onclick = () => { 
                        const wrapper = btn.closest('.pelleditor-wrapper');
                        const bridge = wrapper?._pmBridge;
                        if (bridge) bridge.command(it.cmd, c);
                        else pell.exec(it.cmd, c);
                        menu.classList.remove('show'); 
                    };
                    menu.appendChild(s);
                });
            }
        });

        // Insertion
        const iBtn = query('pell-btn-insertion');
        if (iBtn) {
            console.log('[Pell] Initialisation du menu Insertion');
            const menu = setupDropdown(iBtn, [
                { label: trans.line || 'Ligne', icon: 'fa-minus', click: () => {
                    console.log('[Pell] Insert: Line');
                    const bridge = iBtn.closest('.pelleditor-wrapper')?._pmBridge;
                    bridge ? bridge.command('horizontal_rule') : pell.exec('insertHorizontalRule');
                }},
                { label: trans.link || 'Lien', icon: 'fa-link', click: () => {
                    console.log('[Pell] Insert: Link');
                    window.PellEditor_Modal('Lien', 'URL :<br><input type="text" id="ln-u" style="width:100%;padding:10px;margin-top:10px;border-radius:8px;border:1px solid #ddd;" value="https://">', 'prompt', (ok) => { 
                        if(ok) {
                            const val = document.getElementById('ln-u').value;
                            const bridge = iBtn.closest('.pelleditor-wrapper')?._pmBridge;
                            bridge ? bridge.command('createLink', val) : pell.exec('createLink', val);
                        }
                    });
                }},
                { label: trans.image || 'Image', icon: 'fa-image', click: () => {
                    console.log('[Pell] Insert: Image Modal');
                    window.PellEditor_Modal('Image', 'URL :<br><input type="text" id="im-u" style="width:100%;padding:10px;margin-top:10px;border-radius:8px;border:1px solid #ddd;" value="">', 'prompt', (ok) => { 
                        if(ok) {
                            const url = document.getElementById('im-u').value;
                            const bridge = iBtn.closest('.pelleditor-wrapper')?._pmBridge;
                            bridge ? bridge.command('image', url) : pell.exec('insertImage', url);
                        }
                    });
                }}
            ], 'insertion-list');
            if (!isDemo) {
                const gal = document.createElement('button'); gal.innerHTML = `<i class="fa-solid fa-photo-film"></i> ${trans.gallery || 'Galerie'}`;
                gal.onclick = () => { openGallery(instance); menu.classList.remove('show'); };
                menu.appendChild(gal);
            }
            const grid = createTablePicker(instance, menu, trans);
            menu.appendChild(grid);
        }
    };

    const setupDropdown = (btn, items, cls = '') => {
        const wrap = document.createElement('div'); wrap.className = 'pell-dropdown';
        btn.parentNode.insertBefore(wrap, btn); wrap.appendChild(btn); btn.classList.add('pell-dropdown-toggle');
        const menu = document.createElement('div'); menu.className = `pell-dropdown-menu ${cls}`;
        items.forEach(it => {
            const b = document.createElement('button'); b.innerHTML = `<i class="fa-solid ${it.icon}"></i> ${it.label}`;
            b.onclick = () => { 
                if (it.cmd) {
                    const wrapper = btn.closest('.pelleditor-wrapper');
                    const bridge = wrapper?._pmBridge;
                    if (bridge && (it.cmd === 'formatBlock' || it.cmd === 'foreColor' || it.cmd === 'hiliteColor')) {
                        bridge.command(it.cmd, it.val);
                    } else {
                        pell.exec(it.cmd, it.val); 
                    }
                } else {
                    it.click(); 
                }
                menu.classList.remove('show'); 
            };
            menu.appendChild(b);
        });
        wrap.appendChild(menu);
        btn.onclick = (e) => { 
            e.stopPropagation(); 
            console.log('[Pell] Dropdown toggle:', btn.title);
            document.querySelectorAll('.pell-dropdown-menu').forEach(m => m !== menu && m.classList.remove('show')); 
            menu.classList.toggle('show'); 
        };
        return menu;
    };

    const createTablePicker = (instance, menu, trans) => {
        const wrap = document.createElement('div'); wrap.style.padding = '10px'; wrap.innerHTML = `<div style="font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:8px;">${trans.table || 'Tableau'} <span id="t-info">0x0</span></div><div id="t-grid" style="display:grid;grid-template-columns:repeat(8,1fr);gap:3px;background:#f1f5f9;padding:4px;border-radius:6px;"></div>`;
        const grid = wrap.querySelector('#t-grid'), info = wrap.querySelector('#t-info');
        for(let r=1;r<=8;r++) for(let c=1;c<=8;c++) {
            const cell = document.createElement('div'); cell.style = "width:18px;height:18px;background:#fff;border:1px solid #cbd5e1;cursor:pointer;border-radius:2px;";
            cell.onmouseover = () => { info.innerText=`${r}x${c}`; grid.querySelectorAll('div').forEach((el,i) => { const eR=Math.floor(i/8)+1, eC=(i%8)+1; el.style.background = (eR<=r && eC<=c)?'#3b82f6':'#fff'; }); };
            cell.onclick = (e) => { e.stopPropagation(); insertTable(instance, r, c); menu.classList.remove('show'); };
            grid.appendChild(cell);
        }
        return wrap;
    };

    const insertTable = (instance, rows, cols) => {
        let h = '<table style="border-collapse:collapse;width:100%;border:1px solid #e2e8f0;margin-bottom:20px;">';
        for(let i=0;i<rows;i++) { h+='<tr>'; for(let j=0;j<cols;j++) h+='<td style="border:1px solid #e2e8f0;padding:12px;">&nbsp;</td>'; h+='</tr>'; }
        h+='</table><p>&nbsp;</p>'; instance.content.focus(); pell.exec('insertHTML', h);
    };

    const openGallery = (instance) => {
        fetch('/admin/pelleditor/gallery-api').then(r=>r.json()).then(imgs => {
            let h = `<div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(100px, 1fr));gap:15px;max-height:400px;overflow-y:auto;">`;
            imgs.forEach(img => { h += `<div class="gal-it" data-url="${img.url}" style="cursor:pointer;"><img src="${img.url}" style="width:100%;height:80px;object-fit:cover;border-radius:8px;"></div>`; });
            h += `</div>`;
            window.PellEditor_Modal('Galerie', h, 'info');
            setTimeout(() => { document.querySelectorAll('.gal-it').forEach(it => it.onclick = () => { pell.exec('insertImage', it.dataset.url); document.querySelector('.pell-modal-overlay').remove(); }); }, 100);
        });
    };

    document.addEventListener('click', () => document.querySelectorAll('.pell-dropdown-menu').forEach(m => m.classList.remove('show')));

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initPell); else initPell();
})();
