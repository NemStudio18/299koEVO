<section class="overflow-auto">
    <header>{{ Lang.wiki-history-title }} - {{ page.getName() }}</header>
    <a class="button" href="{{ ROUTER.generate("admin-docs-list") }}"><i class="fa-solid fa-arrow-left"></i> {{ Lang.wiki-back-to-pages }}</a>
    <a class="button" href="{{ ROUTER.generate("admin-docs-edit-page", ["id" => page.getId()]) }}">{{ Lang.wiki-back-to-page }}</a>
    
    {% if history %}
        <table class="small">
            <tr>
                <th>{{ Lang.wiki-version }}</th>
                <th>{{ Lang.wiki-date }}</th>
                <th>{{ Lang.wiki-modified-by }}</th>
                <th>{{ Lang.wiki-change-description }}</th>
                <th>{{ Lang.wiki-actions }}</th>
            </tr>
            {% for version in history %}
                <tr>
                    <td>
                        <span class="wiki-version">{{ version.getVersion() }}</span>
                        {% if version.getVersion() == page.getVersion() %}
                            <span class="wiki-current-version">{{ Lang.wiki-current-version }}</span>
                        {% endif %}
                    </td>
                    <td>{{ util.getDate(version.getModifiedAt()) }}</td>
                    <td>{{ version.getModifiedBy() }}</td>
                    <td>{{ version.getChangeDescription() }}</td>
                    <td>
                        <a href="{{ ROUTER.generate("admin-docs-view-version", ["id" => page.getId(), "version" => version.getVersion()]) }}" class="button">
                            <i class="fa-solid fa-eye"></i> {{ Lang.wiki-view-version }}
                        </a>
                        {% if version.getVersion() != page.getVersion() %}
                            <a onclick="WikiRestoreVersion({{ page.getId() }}, {{ version.getVersion() }})" class="button warning">
                                <i class="fa-solid fa-undo"></i> {{ Lang.wiki-restore-version }}
                            </a>
                        {% endif %}
                    </td>
                </tr>
            {% endfor %}
        </table>
    {% else %}
        <p>{{ Lang.wiki-no-history }}</p>
    {% endif %}
</section>

<script>
async function WikiRestoreVersion(pageId, version) {
    if (confirm('{{ Lang.wiki-confirm-restore-version }}') === true) {
        let url = '{{ ROUTER.generate("admin-docs-restore-version") }}';
        let data = {
            id: pageId,
            version: version,
            token: '{{ token }}'
        };
        let response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        let result = await response;
        if (result.status === 202) {
            Toastify({
                text: "{{ Lang.wiki-version-restored }}",
                className: "success"		
            }).showToast();
            // Rediriger vers la page d'édition
            setTimeout(() => {
                window.location.href = '{{ ROUTER.generate("admin-docs-edit-page", ["id" => page.getId()]) }}';
            }, 1000);
        } else {
            Toastify({
                text: "{{ Lang.wiki-version-restore-error }}",
                className: "error"		
            }).showToast();
        }		
    };
}
</script> 