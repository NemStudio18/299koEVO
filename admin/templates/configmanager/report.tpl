<section class="module-card">
    <header>{{ Lang.configmanager-report-title }}</header>
    
    <p>{{ Lang.configmanager-report-desc }}</p>
    
    <form method="post" action="{{ sendUrl }}" id="reportForm">
        {{ SHOW.tokenField }}
        
        <p>
            <label for="type">{{ Lang.configmanager-report-type }} <span class="required">*</span></label>
            <select name="type" id="type" required>
                <option value="bug">{{ Lang.configmanager-report-type-bug }}</option>
                <option value="feature">{{ Lang.configmanager-report-type-feature }}</option>
                <option value="question">{{ Lang.configmanager-report-type-question }}</option>
                <option value="other">{{ Lang.configmanager-report-type-other }}</option>
            </select>
        </p>
        
        <p>
            <label for="title">{{ Lang.configmanager-report-title-label }} <span class="required">*</span></label>
            <input type="text" name="title" id="title" required minlength="3" maxlength="200" placeholder="{{ Lang.configmanager-report-title-placeholder }}" />
        </p>
        
        <p>
            <label for="description">{{ Lang.configmanager-report-description }} <span class="required">*</span></label>
            <textarea name="description" id="description" rows="8" required minlength="10" placeholder="{{ Lang.configmanager-report-description-placeholder }}"></textarea>
        </p>
        
        <p>
            <label for="plugin">{{ Lang.configmanager-report-plugin }}</label>
            <select name="plugin" id="plugin">
                <option value="">{{ Lang.configmanager-report-plugin-none }}</option>
                {% FOR plugin IN plugins %}
                    <option value="{{ plugin.slug }}">{{ plugin.name }}</option>
                {% ENDFOR %}
            </select>
            <br><small>{{ Lang.configmanager-report-plugin-desc }}</small>
        </p>
        
        <p>
            <label for="email">{{ Lang.configmanager-report-email }}</label>
            <input type="email" name="email" id="email" placeholder="{{ Lang.configmanager-report-email-placeholder }}" />
            <br><small>{{ Lang.configmanager-report-email-desc }}</small>
        </p>
        
        <p>
            <label for="screenshot">{{ Lang.configmanager-report-screenshot }}</label>
            <input type="file" name="screenshot_file" id="screenshot_file" accept="image/*" />
            <input type="hidden" name="screenshot" id="screenshot" />
            <br><small>{{ Lang.configmanager-report-screenshot-desc }}</small>
            <div id="screenshot-preview" style="margin-top: 10px; display: none;">
                <img id="screenshot-img" src="" alt="Preview" style="max-width: 100%; max-height: 300px; border: 1px solid #ddd; border-radius: 4px;" />
                <button type="button" id="screenshot-remove" class="button small" style="margin-top: 5px;">{{ Lang.configmanager-report-screenshot-remove }}</button>
            </div>
        </p>
        
        <p>
            <button type="submit" class="button success">{{ Lang.configmanager-report-submit }}</button>
            <a href="{{ ROUTER.generate("configmanager-admin") }}" class="button">{{ Lang.cancel }}</a>
        </p>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('screenshot_file');
    const hiddenInput = document.getElementById('screenshot');
    const preview = document.getElementById('screenshot-preview');
    const previewImg = document.getElementById('screenshot-img');
    const removeBtn = document.getElementById('screenshot-remove');
    
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
                hiddenInput.value = e.target.result; // Base64
            };
            reader.readAsDataURL(file);
        }
    });
    
    removeBtn.addEventListener('click', function() {
        fileInput.value = '';
        hiddenInput.value = '';
        preview.style.display = 'none';
    });
});
</script>

