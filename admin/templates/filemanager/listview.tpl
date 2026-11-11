<section class="module-card" id='fm-listview-container'>
    <header>
        <i class="fa-solid fa-folder-tree"></i> {{Lang.filemanager.name}}
    </header>
    
    <div class="tabs-container">
        <ul class="tabs-header">
            <li {% if activeTab != "manage" %}class="default-tab"{% endif %}><i class="fa-solid fa-chart-simple"></i> {{Lang.core-overview}}</li>
            <li {% if activeTab == "manage" %}class="default-tab"{% endif %}><i class="fa-solid fa-folder-tree"></i> {{Lang.filemanager.manage-tab}}</li>
        </ul>
        
        <ul class="tabs">
            <!-- OVERVIEW TAB -->
            <li {% if activeTab != "manage" %}class="tab"{% endif %}>
                <div class="module-actions">
                    <button type="button" class="button primary" onclick='refreshView("Back%To%Home%")'>
                        <i class="fa-solid fa-house"></i> {{Lang.filemanager.back-to-root}}
                    </button>
                    <button type="button" class="button success" onClick='displayNewFolder()'>
                        <i class="fa-solid fa-folder-plus"></i> {{Lang.filemanager.add-folder}}
                    </button>
                </div>
                
                <div class="module-grid" style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                    <div class="stat-card">
                        <span class="stat-value">{{stats.files}}</span>
                        <span class="stat-label">{{Lang.filemanager.stats-total-files}}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{stats.folders}}</span>
                        <span class="stat-label">{{Lang.filemanager.stats-total-folders}}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{stats.pictures}}</span>
                        <span class="stat-label">{{Lang.filemanager.stats-total-pictures}}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{stats.size_formatted}}</span>
                        <span class="stat-label">{{Lang.filemanager.stats-total-size}}</span>
                    </div>
                </div>
                
                <div class="info-message">
                    <i class="fa-solid fa-location-dot"></i>
                    <strong>{{Lang.filemanager.current-location}}:</strong>
                    {% if stats.path == "/" %}
                        <span class="path-root">{{Lang.filemanager.root}}</span>
                    {% else %}
                        <span class="path-breadcrumb">
                            <i class="fa-solid fa-house" style="font-size: 0.8rem; margin: 0 5px;"></i>
                            {{stats.path}}
                        </span>
                    {% endif %}
                </div>
            </li>
            
            <!-- MANAGE TAB -->
            <li {% if activeTab == "manage" %}class="tab"{% endif %}>
                <header class="fm-toolbar" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; margin-bottom: 1rem;">
                    <input type="file" name="image_file" id="customFile" onchange="onSetFilename(this)" style="display: none;">
                    <input id='dirInput' type='hidden' value='{{dir}}'>
                    <button type="button" class="button" onclick="document.getElementById('customFile').click()">
                        <i class="fa-solid fa-file-circle-plus"></i> {{Lang.filemanager.add-file}}
                    </button>
                    <span id="custom-file-label" style="font-size: 0.9rem; color: #666; font-style: italic;"></span>
                    <button id="btnUpload" type="button" class="button primary" onclick="uploadFile()" style="margin-left: auto;">
                        <i class="fa-solid fa-cloud-arrow-up"></i> {{Lang.filemanager.send-file}}
                    </button>
                </header>
                
                <progress value="0" max="100" id="filesProgressAjax"></progress>

                <div id='fm-breadcrumb-container'>
                    <div id='fm-breadcrumb'>
                        <a onclick='refreshView("Back%To%Home%")'><i class="fa-solid fa-house"></i></a>
                        {% set fullPath = "" %}
                        {% for item in dirParts %}
                            {% if item != "" %}
                                {% set fullPath = fullPath ~ item ~ "/" %}
                                <a onclick="refreshView('{{fullPath}}')">{{item}}</a>
                            {% endif %}
                        {% endfor %}
                    </div>
                    <div id='fm-add-folder-btn'>
                        <button class="button small success" onClick='displayNewFolder()'>
                            <i class="fa-solid fa-folder-plus"></i> {{Lang.filemanager.add-folder}}
                        </button>
                    </div>
                </div>

                <div id="fm-listview">
                    <div id="fm-view-files">
                        {% if dir != "" %}
                            <div class='fm-thumb' onClick='refreshView("{{dir}}/..")' style="cursor: pointer;">
                                <div class='fm-folder parent-dir'>
                                    <i class="fa-solid fa-level-up-alt" style="transform: rotate(90deg); color: #868e96;"></i>
                                </div>
                                <p class='fm-title'>..</p>
                                <div class='fm-actions' style="justify-content: center; font-size: 0.7rem; color: #868e96;">
                                    {{Lang.filemanager.parent-folder}}
                                </div>
                            </div>
                        {% endif %}
                        
                        {% for item in manager.getFolders() %}
                            <div class='fm-thumb' id='folder-{{item.name}}'>
                                <div class='fm-folder' onClick='refreshView("{{dir}}/{{item.name}}")'>
                                    <i class="fa-solid fa-folder"></i>
                                </div>
                                <p class='fm-title' title='{{item.name}}'>{{item.name}}</p>
                                <div class='fm-actions'>
                                    <a class="fm-link alert" href="javascript:deleteFolder('{{item.name}}')" title="{{Lang.delete}}"><i class="fa-solid fa-trash-can"></i></a>
                                </div>
                            </div>
                        {% endfor %}

                        {% for item in manager.getFiles() %}
                            <div class='fm-thumb' id='file-{{item.name}}'>
                                {% if item.isPicture() %}
                                    <div class='fm-picture'>
                                        <a style="background-image: url('{{item.getUrl()}}')" class="fm-link" href="{{item.getUrl()}}" data-fancybox data-caption="{{item.name}}"></a>
                                    </div>
                                {% else %}
                                    <div class='fm-file'>
                                        <i class="fa-regular fa-file-lines"></i>
                                    </div>
                                {% endif %}
                                <p class='fm-title' title='{{item.name}}'>{{item.name}}</p>
                                <div class='fm-actions'>
                                    <a class="fm-link" onClick="copyToClipboard('{{item.getUrl()}}')" title="{{Lang.copy}}"><i class="fa-regular fa-copy"></i></a>
                                    <a class="fm-link alert" href="javascript:deleteFile('{{item.name}}')" title="{{Lang.delete}}"><i class="fa-solid fa-trash-can"></i></a>
                                    {% if item.isPicture() && editor != false %}
                                        <a class="fm-link warning" onclick="insertImgInEditor('{{editor}}', '{{item.getRelUrl()}}')" title="Insérer"><i class="fa-solid fa-plus-circle"></i></a>
                                    {% endif %}
                                </div>
                            </div>
                        {% endfor %}
                    </div>
                </div>
            </li>
        </ul>
    </div>
</section>

<!-- MODAL FOR NEW FOLDER -->
<section id="fmAddAFolder" style="display:none; width: 400px;">
    <header style="font-weight: 700; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
        <i class="fa-solid fa-folder-plus"></i> {{Lang.filemanager.add-folder}}
    </header>
    <div class="form-group" style="margin-bottom: 20px;">
        <label for="addFolderName" style="display: block; margin-bottom: 8px; font-weight: 500;">{{Lang.filemanager.folder-name}}</label>
        <input id="addFolderName" name="addFolderName" type="text" class="input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" autofocus />
    </div>
    <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button class="button secondary" onclick="Fancybox.close()">{{Lang.cancel}}</button>
        <button class="button success" id="btnConfirmCreateFolder" onClick="goCreateFolder()">{{Lang.filemanager.add-folder}}</button>
    </div>
</section>

<form id="fmRedirectFlat" method="POST" action=" {{redirectUrl}}" style="display:none">
    <input type="text" id="fmCurrDir" name="fmCurrDir" value="{{dir}}" />
    <input type="text" id="fmFolderToSee" name="fmFolderToSee" value="" />
</form>

<script>
    // Double submission guard
    let isCreatingFolder = false;
    let isUploading = false;

    function goCreateFolder() {
        if (isCreatingFolder) return;
        
        const folderNameInput = document.getElementById('addFolderName');
        const folderName = folderNameInput.value.trim();
        
        if (folderName === '') {
            Toastify({ text: "{{Lang.filemanager.folder-name-empty}}", className: "error" }).showToast();
            return;
        }

        isCreatingFolder = true;
        const btn = document.getElementById('btnConfirmCreateFolder');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        let formData = new FormData();
        formData.append('folderName', folderName);
        formData.append('dir', document.getElementById("dirInput").value);
        
        let xhr = new XMLHttpRequest();
        xhr.open("POST", '{{createUrl}}', true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                isCreatingFolder = false;
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                if (xhr.status === 200) {
                    const data = JSON.parse(this.responseText);
                    if (data.success) {
                        Toastify({ text: "{{Lang.filemanager.folder-created}}", className: "success" }).showToast();
                        Fancybox.close();
                        const finalName = data.folderName || folderName;
                        setTimeout(() => refreshView("{{dir}}/" + finalName, true), 100);
                    } else {
                        Toastify({ text: "{{Lang.filemanager.folder-creation-failed}}", className: "error" }).showToast();
                    }
                }
            }
        };
        xhr.send(formData);
    }

    function uploadFile() {
        if (isUploading) return;
        
        const image_files = document.getElementById('customFile').files;
        if (!image_files.length) {
            Toastify({ text: "{{Lang.filemanager.no-file-selected}}", className: "error" }).showToast();
            return;
        }

        isUploading = true;
        const progress = document.getElementById("filesProgressAjax");
        const btn = document.getElementById("btnUpload");
        const originalHtml = btn.innerHTML;

        progress.style.display = "block";
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        let formData = new FormData();
        formData.append('image', image_files[0]);
        formData.append('fmFolderToSee', document.getElementById("dirInput").value);
        
        let xhr = new XMLHttpRequest();
        xhr.open("POST", '{{uploadUrl}}', true);
        xhr.upload.addEventListener("progress", (e) => {
            if (e.lengthComputable) {
                progress.value = (e.loaded / e.total) * 100;
            }
        });

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                isUploading = false;
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                progress.style.display = "none";
                
                if (xhr.status === 200) {
                    const data = JSON.parse(this.responseText);
                    if (data.success) {
                        refreshView("{{dir}}");
                    } else {
                        Toastify({ text: "{{Lang.filemanager.upload-failed}}", className: "error" }).showToast();
                    }
                }
            }
        };
        xhr.send(formData);
    }

    function refreshView(folderName, stayOnManageTab = false) {
        {% if ajaxView %}
            let data = "fmFolderToSee=" + folderName + "&token={{token}}&editor={{editor}}";
            if (stayOnManageTab) data += "&activeTab=manage";
            Fancybox.close(false);
            new Fancybox([{ src: '{{redirectAjaxUrl}}', type: "ajax", ajax: data }]);
        {% else %}
            document.querySelector('#fmFolderToSee').value = folderName;
            if (stayOnManageTab) {
                let input = document.querySelector('#fmRedirectFlat input[name="activeTab"]') || document.createElement('input');
                input.type = 'hidden';
                input.name = 'activeTab';
                input.value = 'manage';
                if (!input.parentNode) document.querySelector('#fmRedirectFlat').appendChild(input);
            }
            document.querySelector('#fmRedirectFlat').submit();
        {% endif %}
    }

    function deleteFile(name) {
        if (!confirm("{{Lang.confirm.deleteItem}}")) return;
        let formData = new FormData();
        formData.append('filename', name);
        formData.append('fmFolderToSee', document.getElementById("dirInput").value);
        let xhr = new XMLHttpRequest();
        xhr.open("POST", '{{deleteUrl}}', true);
        xhr.onload = function () {
            const data = JSON.parse(this.responseText);
            if (data.success) {
                Toastify({ text: "{{Lang.core-item-deleted}} " + name, className: "success" }).showToast();
                const item = document.getElementById('file-' + name);
                if (item) { item.style.opacity = '0'; setTimeout(() => item.remove(), 400); }
            }
        };
        xhr.send(formData);
    }

    function deleteFolder(name) {
        if (!confirm("{{Lang.confirm.deleteItem}}")) return;
        let formData = new FormData();
        formData.append('foldername', name);
        formData.append('fmFolderToSee', document.getElementById("dirInput").value);
        let xhr = new XMLHttpRequest();
        xhr.open("POST", '{{deleteUrl}}', true);
        xhr.onload = function () {
            const data = JSON.parse(this.responseText);
            if (data.success) {
                Toastify({ text: "{{Lang.core-item-deleted}} " + name, className: "success" }).showToast();
                const item = document.getElementById('folder-' + name);
                if (item) { item.style.opacity = '0'; setTimeout(() => item.remove(), 400); }
            }
        };
        xhr.send(formData);
    }

    function displayNewFolder() {
        Fancybox.show([{ src: "#fmAddAFolder", type: "inline" }]);
    }

    function onSetFilename(data) {
        document.getElementById("custom-file-label").innerText = data.files.length ? data.files[0].name : "";
    }

    function copyToClipboard(text) {
        if (!navigator.clipboard) return;
        navigator.clipboard.writeText(text).then(() => {
            Toastify({ text: "{{Lang.filemanager.copied-to-clipboard}}", className: "success" }).showToast();
        });
    }

    function insertImgInEditor(editorId, imgUrl) {
        let editor = document.getElementById(editorId);
        if (editor.tagName === "INPUT") {
            editor.value = imgUrl;
            if (editor.oninput) editor.oninput();
            editor.dispatchEvent(new Event('input'));
        } else {
            if (typeof processInsertImgInEditor === 'function') processInsertImgInEditor(editorId, imgUrl);
            else insertAtCursor(editorId, imgUrl);
        }
        {% if ajaxView %} Fancybox.close(false); {% endif %}
    }

    function insertAtCursor(myFieldId, myValue) {
        let myField = document.getElementById(myFieldId);
        if (myField.selectionStart || myField.selectionStart == '0') {
            var startPos = myField.selectionStart;
            var endPos = myField.selectionEnd;
            myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
        } else {
            myField.value += myValue;
        }
    }
</script>
