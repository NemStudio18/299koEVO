<section class="module-card" id='fm-listview-container'>
    <header>{{ Lang.filemanager.name }}</header>
    <div class="tabs-container">
        <ul class="tabs-header">
            <li {% if activeTab != "manage" %}class="default-tab"{% endif %}><i class="fa-solid fa-chart-simple"></i> {{ Lang.core-overview }}</li>
            <li {% if activeTab == "manage" %}class="default-tab"{% endif %}><i class="fa-solid fa-folder-tree"></i> {{ Lang.filemanager.manage-tab }}</li>
        </ul>
        <ul class="tabs">
            <li {% if activeTab != "manage" %}class="tab"{% endif %}>
                <div class="module-actions">
                    <button type="button" class="button" onclick='refreshView("Back%To%Home%")'>
                        <i class="fa-solid fa-house"></i> {{ Lang.filemanager.back-to-root }}
                    </button>
                    <button type="button" class="button" onClick='displayNewFolder()'>
                        <i class="fa-solid fa-folder-plus"></i> {{ Lang.filemanager.add-folder }}
                    </button>
                </div>
                <div class="module-grid">
                    <div class="stat-card">
                        <span class="stat-value">{{ stats.files }}</span>
                        <span class="stat-label">{{ Lang.filemanager.stats-total-files }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ stats.folders }}</span>
                        <span class="stat-label">{{ Lang.filemanager.stats-total-folders }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ stats.pictures }}</span>
                        <span class="stat-label">{{ Lang.filemanager.stats-total-pictures }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ stats.size_formatted }}</span>
                        <span class="stat-label">{{ Lang.filemanager.stats-total-size }}</span>
                    </div>
                </div>
                <div class="info-message">
                    <p>
                        <i class="fa-solid fa-folder-open"></i>
                        <strong>{{ Lang.filemanager.current-location }}:</strong>
                        {% if stats.path == "/" %}
                            <span class="path-root">{{ Lang.filemanager.root }}</span>
                        {% else %}
                            <span class="path-breadcrumb">
                                <a onclick='refreshView("Back%To%Home%")' title="{{ Lang.filemanager.root }}">
                                    <i class="fa-solid fa-house"></i>
                                </a>
                                {% for item in dirParts %}
                                    {% if item != "" %}
                                        <span class="path-separator">/</span>
                                        <span class="path-item">{{ item }}</span>
                                    {% endif %}
                                {% endfor %}
                            </span>
                        {% endif %}
                    </p>
                </div>
            </li>
            <li {% if activeTab == "manage" %}class="tab"{% endif %}>
                <header>
                    <input type="file" name="image_file" id="customFile" onchange="onSetFilename(this)" style="display: none;">
                    <input id='dirInput' type='hidden' value='{{ dir }}'>
                    <button type="button" onclick="document.getElementById('customFile').click()">{{ Lang.filemanager.add-file }}</button>
                    <span id="custom-file-label" style="margin-left: 10px;"></span>
                    <button id="btnUpload" type="button" onclick="uploadFile()">{{ Lang.filemanager.send-file }}</button>
                    <progress value="0" max="100" id="filesProgressAjax"></progress>
                </header>
                <div id='fm-breadcrumb-container'>
                    <div id='fm-breadcrumb'>
                        <a onclick='refreshView("Back%To%Home%")'><i class="fa-solid fa-house"></i></a>
                        {% set fullPath = "" %}
                        {% for item in dirParts %}
                            {% if item != "" %}
                                {% set fullPath = fullPath ~ item ~ "/" %}
                                <a onclick="refreshView('{{ fullPath }}')">{{ item }}</a>
                            {% endif %}
                        {% endfor %}
                    </div>
                    <div id='fm-add-folder'>
                        <a class="button" onClick='displayNewFolder()'><i class="fa-solid fa-folder-plus"></i> {{ Lang.filemanager.add-folder }}</a>
                    </div>
                </div>

                <div id="fm-listview">
                    <div id="fm-view-files">
                        {% if dir != "" %}
                            <div class='fm-thumb'>
                                <div class='fm-folder' onClick='refreshView("{{ dir }}/..")'>
                                    <i class="fa-solid fa-arrow-turn-up"></i>
                                    <p class='fm-title' title='{{ Lang.filemanager.go-up }}'>..</p>
                                </div>
                                <div class='fm-actions'>
                                    {{ Lang.filemanager.parent-folder }}
                                </div>
                            </div>
                        {% endif %}
                        {% for item in manager.getFolders() %}
                            <div class='fm-thumb' id='{{ item.name }}'>
                                <div class='fm-folder' title='{{ item.name }}' onClick='refreshView("{{ dir }}/{{ item.name }}")'>
                                    <i class="fa-regular fa-folder-open"></i>
                                    <p class='fm-title' title='{{ item.name }}'>{{ item.name }}</p>
                                </div>
                                <div class='fm-actions'>
                                    <a class="fm-link alert" href="javascript:deleteFolder('{{ item.name }}')"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </div>
                        {% endfor %}

                        {% for item in manager.getFiles() %}
                            <div class='fm-thumb' id='{{ item.name }}'>
                                {% if item.isPicture() %}
                                    <div class='fm-picture'>
                                        <a style="background-image: url('{{ item.getUrl() }}')" class="fm-link" href="{{ item.getUrl() }}" data-fancybox data-caption="{{ item.name }}" title="{{ item.name }}"></a>
                                    </div>
                                {% else %}
                                    <div class='fm-file'>
                                        <i class="fa-regular fa-file" title="{{ item.name }}"></i>
                                    </div>
                                {% endif %}
                                <p class='fm-title' title='{{ item.name }}'>{{ item.name }}</p>
                                <div class='fm-actions'>
                                    <a class="fm-link" onClick="copyToClipboard('{{ item.getUrl() }}')"><i class="fa-regular fa-clipboard"></i></a>
                                    <a class="fm-link" href="{{ item.getUrl() }}"><i class="fa-solid fa-link"></i></a>
                                    <a class="fm-link alert" href="javascript:deleteFile('{{ item.name }}')"><i class="fa-solid fa-trash"></i></a>
                                    {% if item.isPicture() && editor != false %}
                                        <a onclick="insertImgInEditor('{{editor}}', '{{item.getRelUrl()}}')" ><i class="fa-solid fa-laptop-code"></i></a>
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

<section id="fmAddAFolder">
    <header>
        {{ Lang.filemanager.add-folder }}
    </header>
    <label for="addFolderName">{{ Lang.filemanager.folder-name }}</label>
    <input id="addFolderName" name="addFolderName" type="text" />
    <button class="success" id="addFolderNameValidate" data-fancybox-close tabindex="-1" onClick="goCreateFolder(document.getElementById('addFolderName').value)">{{ Lang.filemanager.add-folder }}</button>
</section>

<form id="fmRedirectFlat" method="POST" action=" {{ redirectUrl }}">
    <input type="text" id="fmCurrDir" name="fmCurrDir" value="{{ dir }}" />
    <input type="text" id="fmFolderToSee" name="fmFolderToSee" value="" />
</form>
<script>
    function insertImgInEditor(editorId, imgUrl) {
        let editor = document.getElementById(editorId);
        let tagName = editor.tagName;
        if(tagName === "INPUT"){
            editor.value = imgUrl;
            editor.oninput();
        } else {
            if (typeof processInsertImgInEditor === 'function') {
                processInsertImgInEditor(editorId, imgUrl);
            } else {
                insertAtCursor(editorId, imgUrl);
            }
        }
        {% if ajaxView %}
            Fancybox.close(false);
        {% endif %}
    }
    function insertAtCursor(myFieldId, myValue) {
        let myField = document.getElementById(myFieldId);
    //IE support
        if (document.selection) {
            myField.focus();
            sel = document.selection.createRange();
            sel.text = myValue;
        }
        //MOZILLA and others
        else if (myField.selectionStart || myField.selectionStart == '0') {
            var startPos = myField.selectionStart;
            var endPos = myField.selectionEnd;
            myField.value = myField.value.substring(0, startPos)
                + myValue
                + myField.value.substring(endPos, myField.value.length);
        } else {
            myField.value += myValue;
        }
    }
    function onSetFilename(data) {
        if (data.files && data.files.length > 0) {
            let fileName = data.files[0].name;
            document.getElementById("custom-file-label").innerText = fileName;
        } else {
            document.getElementById("custom-file-label").innerText = "";
        }
    }

    function displayNewFolder() {
        Fancybox.show([{src: "#fmAddAFolder", type: "inline"}]);
    }

    let isCreatingFolder = false;
    function goCreateFolder(folderName) {
        if (isCreatingFolder) {
            return; // Prevent double submission
        }
        
        if (folderName === '') {
            Toastify({
                text: "{{ Lang.filemanager.folder-name-empty }}",
                className: "error"
            }).showToast();
            Fancybox.show([{src: "#fmAddAFolder", type: "inline"}]);
            return;
        }

        isCreatingFolder = true;
        let formData = new FormData();
        formData.append('folderName', folderName);
        formData.append('dir', document.getElementById("dirInput").value);
        let xhr = new XMLHttpRequest();
        xhr.open("POST", '{{ createUrl }}', true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                isCreatingFolder = false;
                const data = JSON.parse(this.responseText);
                if (data.success === 0) {
                    Toastify({
                        text: "{{ Lang.filemanager.folder-creation-failed }}",
                        className: "error"
                    }).showToast();
                } else {
                    Toastify({
                        text: "{{ Lang.filemanager.folder-created }}",
                        className: "success"
                    }).showToast();
                    // Stay on the "Manage" tab and refresh the view
                    setTimeout(function() {
                        refreshView("{{ dir }}/" + folderName, true);
                    }, 100);
                }
            }
        };
        xhr.send(formData);
    }

    function copyToClipboard(text) {
        if (!navigator.clipboard) {
            Toastify({
                text: "{{ Lang.filemanager.clipboard-unavailable }}",
                className: "error"
            }).showToast();
        } else {
            navigator.clipboard.writeText(text).then(() => {
                Toastify({
                    text: "{{ Lang.filemanager.copied-to-clipboard }}",
                    className: "success"
                }).showToast();
            }, () => {
                Toastify({
                    text: "{{ Lang.filemanager.copy-failed }}",
                    className: "error"
                }).showToast();
            });
        }
    }

    function refreshView(folderName, stayOnManageTab = false) {
        {% if ajaxView %}
            let data = "fmFolderToSee=" + folderName + "&token={{ token }}&editor={{ editor}}";
            if (stayOnManageTab) {
                data += "&activeTab=manage";
            }
            Fancybox.close(false);
                new Fancybox([
                {
                    src: '{{ redirectAjaxUrl }}',
                    type: "ajax",
                    ajax : data
                },
                ],);
        {% else %}
            document.querySelector('#fmFolderToSee').value = folderName;
            if (stayOnManageTab) {
                // Add hidden input to indicate we want to stay on manage tab
                let existingInput = document.querySelector('#fmRedirectFlat input[name="activeTab"]');
                if (!existingInput) {
                    let input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'activeTab';
                    input.value = 'manage';
                    document.querySelector('#fmRedirectFlat').appendChild(input);
                }
            }
            document.querySelector('#fmRedirectFlat').submit();
        {% endif %}
    }

    function deleteFile(name) {
        if (!confirm("{{ Lang.confirm.deleteItem }}")) {
            return;
        }

        let formData = new FormData();
        formData.append('filename', name);
        formData.append('fmFolderToSee', document.getElementById("dirInput").value);
        let xhr = new XMLHttpRequest();
        xhr.open("POST", '{{ deleteUrl }}', true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const data = JSON.parse(this.responseText);
                if (data.success === 0) {
                    Toastify({
                        text: "{{ Lang.core-item-not-deleted }}" + name,
                        className: "error"
                    }).showToast();
                } else {
                    Toastify({
                        text: "{{ Lang.core-item-deleted }}" + name,
                        className: "success"
                    }).showToast();
                    const item = document.getElementById(name);
                    fadeOut(item);
                    setTimeout(function () {
                        item.remove();
                    }, 1000);
                }
            }
        };
        xhr.send(formData);
    }

    function deleteFolder(name) {
        if (!confirm("{{ Lang.confirm.deleteItem }}")) {
            return;
        }

        let formData = new FormData();
        formData.append('foldername', name);
        formData.append('fmFolderToSee', document.getElementById("dirInput").value);
        let xhr = new XMLHttpRequest();
        xhr.open("POST", '{{ deleteUrl }}', true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const data = JSON.parse(this.responseText);
                if (data.success === 0) {
                    Toastify({
                        text: "{{ Lang.core-item-not-deleted }}" + name,
                        className: "error"
                    }).showToast();
                } else {
                    Toastify({
                        text: "{{ Lang.core-item-deleted }}" + name,
                        className: "success"
                    }).showToast();
                    const item = document.getElementById(name);
                    fadeOut(item);
                    setTimeout(function () {
                        item.remove();
                    }, 1000);
                }
            }
        };
        xhr.send(formData);
    }

    let isUploading = false;
    function uploadFile() {
        if (isUploading) {
            return; // Prevent double submission
        }
        
        const image_files = document.getElementById('customFile').files;
        document.getElementById("filesProgressAjax").style.visibility = 'visible';
        if (image_files.length) {
            isUploading = true;
            document.getElementById("filesProgressAjax").style.display = "block";
            const btnUpload = document.getElementById("btnUpload");
            const originalHtml = btnUpload.innerHTML;
            btnUpload.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>';
            btnUpload.setAttribute('disabled', true);
            let formData = new FormData();
            formData.append('image', image_files[0]);
            formData.append('fmFolderToSee', document.getElementById("dirInput").value);
            let xhr = new XMLHttpRequest();
            xhr.open("POST", '{{ uploadUrl }}', true);
            xhr.upload.addEventListener("progress", function (e) {
                if (e.lengthComputable) {
                    let percentComplete = e.loaded / e.total * 100;
                    document.getElementById("filesProgressAjax").value = percentComplete;
                }
            }, false);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    isUploading = false;
                    btnUpload.innerHTML = originalHtml;
                    btnUpload.removeAttribute('disabled');
                    document.getElementById("filesProgressAjax").style.display = "none";
                    document.getElementById("customFile").value = "";
                    document.getElementById("custom-file-label").innerText = "";
                    const data = JSON.parse(this.responseText);
                    if (data.success === 0) {
                        Toastify({
                            text: "{{ Lang.filemanager.upload-failed }}",
                            className: "error"
                        }).showToast();
                    } else {
                        refreshView("{{ dir }}");
                    }
                }
            };
            xhr.send(formData);
        } else {
            Toastify({
                text: "{{ Lang.filemanager.no-file-selected }}",
                className: "error"
            }).showToast();
        }
    }
</script>
