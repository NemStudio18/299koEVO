/**
 * 299koEVO — Gestionnaire de fichiers (mode autonome + modale AJAX)
 */
(function (global) {
    if (global.EvoFileManager) {
        return;
    }

    function notify(text, type) {
        if (typeof global.Toastify === 'function') {
            global.Toastify({
                text: text,
                className: type || 'info',
                duration: 3200
            }).showToast();
        }
    }

    function copyText(text, successMsg, failMsg) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () {
                notify(successMsg, 'success');
            }).catch(function () {
                fallbackCopy(text, successMsg, failMsg);
            });
            return;
        }
        fallbackCopy(text, successMsg, failMsg);
    }

    function fallbackCopy(text, successMsg, failMsg) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        var ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (e) {
            ok = false;
        }
        document.body.removeChild(ta);
        notify(ok ? successMsg : failMsg, ok ? 'success' : 'error');
    }

    function insertTarget(editorId, relUrl, fullUrl, isPicture, closeModal) {
        if (!editorId) return;
        var target = document.getElementById(editorId);

        if (target && (target.tagName === 'INPUT' || target.tagName === 'SELECT')) {
            target.value = relUrl;
            if (typeof target.oninput === 'function') {
                target.oninput();
            }
            target.dispatchEvent(new Event('input', { bubbles: true }));
            target.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (typeof global.processInsertImgInEditor === 'function') {
            global.processInsertImgInEditor(editorId, relUrl, isPicture);
        } else if (target) {
            var val = isPicture ? '<img src="' + relUrl + '" alt="" />' : relUrl;
            if (typeof target.selectionStart === 'number') {
                var s = target.selectionStart;
                var e = target.selectionEnd;
                target.value = target.value.substring(0, s) + val + target.value.substring(e);
            } else {
                target.value += val;
            }
        }

        if (closeModal && typeof global.Fancybox !== 'undefined') {
            try {
                global.Fancybox.close(false);
            } catch (e) {}
        }
    }

    function initContainer(root) {
        if (!root || root.dataset.fmBound === '1') {
            return;
        }
        root.dataset.fmBound = '1';

        var cfg = {
            dir: root.dataset.dir || '',
            editor: root.dataset.editor || '',
            ajaxView: root.dataset.ajaxView === '1',
            token: root.dataset.token || '',
            uploadUrl: root.dataset.uploadUrl || '',
            deleteUrl: root.dataset.deleteUrl || '',
            createUrl: root.dataset.createUrl || '',
            redirectAjaxUrl: root.dataset.redirectAjaxUrl || '',
            viewUrl: root.dataset.viewUrl || '',
            msgFolderEmpty: root.dataset.msgFolderEmpty || 'Le nom du dossier est vide',
            msgFolderCreated: root.dataset.msgFolderCreated || 'Le dossier a été créé',
            msgFolderFailed: root.dataset.msgFolderFailed || 'La création du dossier a échoué',
            msgNoFile: root.dataset.msgNoFile || 'Aucun fichier sélectionné',
            msgUploadFailed: root.dataset.msgUploadFailed || "L'envoi a échoué",
            msgUploadDone: root.dataset.msgUploadDone || 'Fichier envoyé',
            msgCopied: root.dataset.msgCopied || 'Copié dans le presse-papier',
            msgCopyFailed: root.dataset.msgCopyFailed || 'La copie a échoué',
            msgDeleted: root.dataset.msgDeleted || 'Élément supprimé',
            msgConfirmDelete: root.dataset.msgConfirmDelete || 'Confirmer la suppression ?'
        };

        var fileInput = root.querySelector('.fm-file-input');
        var dropZone = root.querySelector('.fm-dropzone');
        var progress = root.querySelector('.fm-progress');
        var searchInput = root.querySelector('.fm-search-input');
        var newFolderBar = root.querySelector('.fm-new-folder-bar');
        var newFolderInput = root.querySelector('.fm-new-folder-input');
        var isBusy = false;

        function refreshView(targetDir) {
            var fd = new FormData();
            fd.append('fmFolderToSee', targetDir || '');
            fd.append('token', cfg.token);
            fd.append('editor', cfg.editor);

            root.classList.add('is-loading');

            if (cfg.ajaxView) {
                fetch(cfg.redirectAjaxUrl, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                })
                .then(function (html) {
                    var wrap = document.createElement('div');
                    wrap.innerHTML = html.trim();
                    var nextRoot = wrap.querySelector('#fm-listview-container') || wrap.firstElementChild;
                    if (nextRoot && root.parentNode) {
                        root.parentNode.replaceChild(nextRoot, root);
                        initContainer(nextRoot);
                    }
                })
                .catch(function () {
                    root.classList.remove('is-loading');
                    notify('Erreur de chargement du dossier.', 'error');
                });
            } else {
                var url = cfg.viewUrl + (targetDir ? '?dir=' + encodeURIComponent(targetDir) : '');
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, '', url);
                }
                fetch(cfg.redirectAjaxUrl, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                })
                .then(function (html) {
                    var wrap = document.createElement('div');
                    wrap.innerHTML = html.trim();
                    var nextRoot = wrap.querySelector('#fm-listview-container') || wrap.firstElementChild;
                    if (nextRoot && root.parentNode) {
                        nextRoot.dataset.ajaxView = '0';
                        root.parentNode.replaceChild(nextRoot, root);
                        initContainer(nextRoot);
                    }
                })
                .catch(function () {
                    window.location.href = url;
                });
            }
        }

        function uploadFiles(files) {
            if (isBusy || !files || !files.length) {
                if (!files || !files.length) notify(cfg.msgNoFile, 'error');
                return;
            }
            isBusy = true;
            var total = files.length;
            var idx = 0;
            var failed = 0;

            if (progress) {
                progress.value = 0;
                progress.classList.add('is-visible');
            }

            function next() {
                if (idx >= total) {
                    isBusy = false;
                    if (progress) progress.classList.remove('is-visible');
                    if (total - failed > 0) {
                        notify(cfg.msgUploadDone + (total > 1 ? ' (' + (total - failed) + '/' + total + ')' : ''), 'success');
                        refreshView(cfg.dir);
                    }
                    return;
                }

                var fd = new FormData();
                fd.append('image', files[idx]);
                fd.append('fmFolderToSee', cfg.dir);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', cfg.uploadUrl, true);
                xhr.upload.addEventListener('progress', function (e) {
                    if (progress && e.lengthComputable) {
                        var base = (idx / total) * 100;
                        progress.value = base + ((e.loaded / e.total) * (100 / total));
                    }
                });
                xhr.onreadystatechange = function () {
                    if (xhr.readyState !== 4) return;
                    var ok = false;
                    if (xhr.status === 200) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            ok = !!res.success;
                        } catch (e) {
                            ok = false;
                        }
                    }
                    if (!ok) {
                        failed++;
                        notify(cfg.msgUploadFailed + ' (' + files[idx].name + ')', 'error');
                    }
                    idx++;
                    next();
                };
                xhr.send(fd);
            }

            next();
        }

        function createFolder() {
            if (isBusy || !newFolderInput) return;
            var name = newFolderInput.value.trim();
            if (!name) {
                notify(cfg.msgFolderEmpty, 'error');
                newFolderInput.focus();
                return;
            }
            isBusy = true;
            var fd = new FormData();
            fd.append('folderName', name);
            fd.append('fmFolderToSee', cfg.dir);

            fetch(cfg.createUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                isBusy = false;
                if (data && data.success) {
                    notify(cfg.msgFolderCreated, 'success');
                    var finalName = data.folderName || name;
                    var target = cfg.dir ? (cfg.dir + '/' + finalName) : finalName;
                    refreshView(target);
                } else {
                    notify(cfg.msgFolderFailed, 'error');
                }
            })
            .catch(function () {
                isBusy = false;
                notify(cfg.msgFolderFailed, 'error');
            });
        }

        function deleteEntry(kind, name, thumbEl) {
            if (!name || !window.confirm(cfg.msgConfirmDelete)) {
                return;
            }
            var fd = new FormData();
            fd.append(kind === 'folder' ? 'foldername' : 'filename', name);
            fd.append('fmFolderToSee', cfg.dir);

            fetch(cfg.deleteUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success) {
                    notify(cfg.msgDeleted + ' (' + name + ')', 'success');
                    if (thumbEl) {
                        thumbEl.classList.add('is-removing');
                        setTimeout(function () {
                            refreshView(cfg.dir);
                        }, 260);
                    } else {
                        refreshView(cfg.dir);
                    }
                } else {
                    notify('Impossible de supprimer « ' + name + ' ».', 'error');
                }
            })
            .catch(function () {
                notify('Erreur lors de la suppression.', 'error');
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files.length) {
                    uploadFiles(fileInput.files);
                }
            });
        }

        if (dropZone) {
            ['dragenter', 'dragover'].forEach(function (ev) {
                dropZone.addEventListener(ev, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function (ev) {
                dropZone.addEventListener(ev, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.classList.remove('is-dragover');
                });
            });
            dropZone.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                    uploadFiles(e.dataTransfer.files);
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = searchInput.value.trim().toLowerCase();
                root.querySelectorAll('.fm-thumb[data-filter-name]').forEach(function (card) {
                    var n = (card.dataset.filterName || '').toLowerCase();
                    card.style.display = (q === '' || n.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }

        if (newFolderInput) {
            newFolderInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    createFolder();
                } else if (e.key === 'Escape' && newFolderBar) {
                    newFolderBar.classList.remove('is-open');
                }
            });
        }

        root.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-fm-action]');
            if (!trigger || !root.contains(trigger)) return;

            var action = trigger.dataset.fmAction;
            if (action === 'pick-files') {
                if (fileInput) fileInput.click();
            } else if (action === 'open-dir') {
                e.preventDefault();
                refreshView(trigger.dataset.dir || '');
            } else if (action === 'toggle-new-folder') {
                e.preventDefault();
                if (newFolderBar) {
                    var open = newFolderBar.classList.toggle('is-open');
                    if (open && newFolderInput) {
                        newFolderInput.value = '';
                        setTimeout(function () { newFolderInput.focus(); }, 30);
                    }
                }
            } else if (action === 'cancel-new-folder') {
                e.preventDefault();
                if (newFolderBar) newFolderBar.classList.remove('is-open');
            } else if (action === 'create-folder') {
                e.preventDefault();
                createFolder();
            } else if (action === 'copy') {
                e.preventDefault();
                copyText(trigger.dataset.url || '', cfg.msgCopied, cfg.msgCopyFailed);
            } else if (action === 'insert') {
                e.preventDefault();
                insertTarget(
                    cfg.editor,
                    trigger.dataset.relUrl || '',
                    trigger.dataset.url || '',
                    trigger.dataset.isPicture === '1',
                    cfg.ajaxView
                );
            } else if (action === 'delete-file') {
                e.preventDefault();
                deleteEntry('file', trigger.dataset.name || '', trigger.closest('.fm-thumb'));
            } else if (action === 'delete-folder') {
                e.preventDefault();
                deleteEntry('folder', trigger.dataset.name || '', trigger.closest('.fm-thumb'));
            }
        });
    }

    function initAll() {
        document.querySelectorAll('#fm-listview-container').forEach(initContainer);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    global.EvoFileManager = {
        init: initContainer,
        initAll: initAll
    };
})(window);

