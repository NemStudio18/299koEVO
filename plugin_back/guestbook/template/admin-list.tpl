<section>
    <header>{{ Lang.guestbook-admin-list }}</header>
    
    <!-- DEBUG: Informations de debug (affiché seulement si le mode debug du CMS est actif) -->
    {% if isDebugMode %}
        <div class="guestbook-warning-box">
            <strong>DEBUG:</strong><br/>
            File exists: {% if debugInfo.file_exists %}YES{% else %}NO{% endif %}<br/>
            File path: {{ debugInfo.file_path }}<br/>
            Items loaded: {{ debugInfo.items_count }}<br/>
            Entries array count: {{ entriesCount }}<br/>
            JSON decode result: {{ debugInfo.json_decode_result }}<br/>
            JSON error: {{ debugInfo.json_error }}<br/>
            Raw JSON preview (first 200 chars): {{ debugInfo.raw_json }}
        </div>
    {% endif %}
    
    <div class="guestbook-stats">
        <p>{{ Lang.guestbook.status }}: {{ guestbookManager.countApproved() }} {{ Lang.guestbook.approved }}, {{ guestbookManager.countPending() }} {{ Lang.guestbook.pending }}</p>
    </div>
    <table>
        <tr>
            <th>{{ Lang.guestbook.author }}</th>
            <th>{{ Lang.guestbook.message }}</th>
            <th>{{ Lang.guestbook.date }}</th>
            <th>{{ Lang.guestbook.status }}</th>
            <th>{{ Lang.guestbook.likes }}</th>
            <th>{{ Lang.actions }}</th>
        </tr>
        {% for entry in entries %}
            <tr id="entry{{ entry.id }}">
                <td>
                    <strong>{{ entry.name }}</strong>
                    {% if entry.email %}
                        <br><small>{{ entry.email }}</small>
                    {% endif %}
                </td>
                <td>
                    <div class="entry-message-preview">{{ entry.message }}</div>
                    {% if entry.hasAdminReply %}
                        <div class="admin-reply-preview">
                            <strong>{{ Lang.guestbook.admin-reply }}:</strong> {{ entry.adminReply }}
                            {% if entry.adminReplyDate %}
                                <small>({{ entry.adminReplyDate }})</small>
                            {% endif %}
                            {% if entry.adminReplyAuthor %}
                                <small class="guestbook-admin-reply-author">— {{ entry.adminReplyAuthor }}</small>
                            {% endif %}
                        </div>
                    {% endif %}
                </td>
                <td>{{ entry.date }}</td>
                <td class="text-center">
                    {% if entry.approved %}
                        <span class="status-approved">{{ Lang.guestbook.approved }}</span>
                    {% else %}
                        <span class="status-pending">{{ Lang.guestbook.pending }}</span>
                    {% endif %}
                </td>
                <td class="text-center">{{ entry.likesCount }}</td>
                <td class="text-center">
                    {% if entry.isPending %}
                        <a title="{{ Lang.guestbook-admin-approve }}" href="{ ROUTER.generate("guestbook-admin-approve", ["id" => entry.id, "token" => token]) }" class="button">{{ Lang.guestbook-admin-approve }}</a>
                    {% endif %}
                    <button type="button" onclick="showReplyForm(event, {{ entry.id }})" class="button" data-entry-id="{{ entry.id }}" data-reply-text="{% if entry.hasAdminReply %}{{ entry.adminReply }}{% endif %}">
                        {% if entry.hasAdminReply %}{{ Lang.guestbook.edit-reply }}{% else %}{{ Lang.guestbook.reply }}{% endif %}
                    </button>
                    {% if entry.hasAdminReply %}
                        <a title="{{ Lang.guestbook.delete-reply }}" onclick="GuestbookDeleteReply('{{ entry.id }}', '{{ token }}')" class="button alert">{{ Lang.guestbook.delete-reply }}</a>
                    {% endif %}
                    <a title="{{ Lang.delete }}" onclick="GuestbookDeleteEntry('{{ entry.id }}', '{{ token }}')" class="button alert">{{ Lang.delete }}</a>
                </td>
            </tr>
        {% endfor %}
        {% if entriesCount == 0 %}
            <tr>
                <td colspan="6" class="guestbook-empty-message">
                    <p>{{ Lang.guestbook.no-messages }}</p>
                </td>
            </tr>
        {% endif %}
    </table>
</section>

<!-- Modal pour répondre -->
<div id="reply-modal" class="modal-hidden">
    <div class="modal-content">
        <form method="post" action="" id="reply-form">
            {{ SHOW.tokenField }}
            <input type="hidden" name="reply" id="reply-text" />
            <label for="reply-message">{{ Lang.guestbook.reply }}:</label>
            <textarea id="reply-message" name="reply-message" rows="5" class="full-width"></textarea>
            <div class="modal-actions">
                <button type="submit" class="button">{{ Lang.save }}</button>
                <button type="button" onclick="closeReplyForm()" class="button">{{ Lang.cancel }}</button>
            </div>
        </form>
    </div>
</div>

<style>
.entry-message-preview {
    max-height: 100px;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
}

.admin-reply-preview {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: var(--section-head-bg-color);
    border-left: 3px solid var(--primary-color);
    font-size: 0.9rem;
}

.status-approved {
    color: var(--success-color);
    font-weight: 600;
}

.status-pending {
    color: var(--warning-color);
    font-weight: 600;
}

#reply-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--section-bg-color);
    color: var(--font-color);
    padding: 2rem;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}
</style>

<script>
function showReplyForm(event, entryId) {
    event.preventDefault();
    const modal = document.getElementById('reply-modal');
    const form = document.getElementById('reply-form');
    const textarea = document.getElementById('reply-message');
    const replyUrl = '{ ROUTER.generate("guestbook-admin-reply", ["id" => "REPLACE_ID", "token" => token]) }'.replace('REPLACE_ID', entryId);
    const button = event.target || document.querySelector('[data-entry-id="' + entryId + '"]');
    const existingReply = button ? button.getAttribute('data-reply-text') : '';
    
    form.action = replyUrl;
    textarea.value = existingReply || '';
    modal.style.display = 'flex';
    textarea.focus();
}

function closeReplyForm() {
    document.getElementById('reply-modal').style.display = 'none';
    document.getElementById('reply-message').value = '';
}

document.getElementById('reply-form')?.addEventListener('submit', function(e) {
    const textarea = document.getElementById('reply-message');
    document.getElementById('reply-text').value = textarea.value;
});

// Fermer la modal en cliquant à l'extérieur
document.getElementById('reply-modal')?.addEventListener('click', function(e) {
    if (e.target === e.currentTarget) {
        closeReplyForm();
    }
});

function GuestbookDeleteEntry(id, token) {
    if (confirm('{{ Lang.confirm.deleteItem }}')) {
        window.location.href = '{ ROUTER.generate("guestbook-admin-delete", ["id" => "ENTRY_ID", "token" => "TOKEN"]) }'.replace('ENTRY_ID', id).replace('TOKEN', token);
    }
}

function GuestbookDeleteReply(id, token) {
    if (confirm('{{ Lang.guestbook.delete-reply-confirm }}')) {
        window.location.href = '{ ROUTER.generate("guestbook-admin-delete-reply", ["id" => "ENTRY_ID", "token" => "TOKEN"]) }'.replace('ENTRY_ID', id).replace('TOKEN', token);
    }
}
</script>
