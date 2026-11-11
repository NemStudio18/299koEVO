/**
 * Vivre-Bourges Native LiveChat Widget
 * 100% autonome, sans dépendance externe
 */
(function() {
    let sessionToken = localStorage.getItem('vb_livechat_token');
    if (!sessionToken || sessionToken.length < 16) {
        sessionToken = 'vb_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        localStorage.setItem('vb_livechat_token', sessionToken);
    }

    let visitorName = localStorage.getItem('vb_livechat_name') || '';
    let visitorContact = localStorage.getItem('vb_livechat_contact') || '';
    let lastMessageId = 0;
    let pollInterval = null;
    let isOpen = false;

    // Création de l'interface DOM
    function initDOM() {
        if (document.getElementById('vbChatLauncher')) return;

        // 1. Bouton Lanceur Flottant
        const launcher = document.createElement('button');
        launcher.id = 'vbChatLauncher';
        launcher.className = 'vb-chat-launcher';
        launcher.setAttribute('aria-label', 'Discuter avec l\'équipe');
        launcher.innerHTML = `
            <span class="vb-chat-launcher-dot"></span>
            <i class="fa-solid fa-comments vb-chat-launcher-icon"></i>
            <span class="vb-chat-launcher-text">Une question ? Discuter</span>
        `;
        launcher.onclick = () => window.openLiveChat();
        document.body.appendChild(launcher);

        // 2. Fenêtre de Tchat
        const win = document.createElement('div');
        win.id = 'vbChatWindow';
        win.className = 'vb-chat-window';
        win.innerHTML = `
            <header class="vb-chat-header">
                <div class="vb-chat-header-info">
                    <div class="vb-chat-avatar">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <div class="vb-chat-header-text">
                        <h4>Discussion en direct</h4>
                        <div class="vb-chat-status">
                            <span class="vb-chat-status-dot"></span>
                            <span>Équipe locale en ligne</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="vb-chat-close-btn" onclick="window.closeLiveChat()">&times;</button>
            </header>

            <div class="vb-chat-user-banner" id="vbUserBanner">
                <span>👤</span>
                <input type="text" id="vbInputName" placeholder="Votre nom ou commerce (optionnel)" 
                       value="${escapeHtml(visitorName)}" class="vb-chat-user-input" onchange="saveUserInfo()">
            </div>

            <div class="vb-chat-body" id="vbChatMessages"></div>

            <footer class="vb-chat-footer">
                <form id="vbChatForm" class="vb-chat-form" onsubmit="handleVisitorSend(event)">
                    <input type="text" name="_gotcha" style="display:none !important;" tabindex="-1">
                    <input type="text" id="vbChatInput" class="vb-chat-input" 
                           placeholder="Écrivez votre message..." autocomplete="off" required>
                    <button type="submit" id="vbChatSendBtn" class="vb-chat-send-btn" title="Envoyer">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </footer>
        `;
        document.body.appendChild(win);
    }

    window.saveUserInfo = function() {
        const input = document.getElementById('vbInputName');
        if (input) {
            visitorName = input.value.trim();
            localStorage.setItem('vb_livechat_name', visitorName);
        }
    };

    window.openLiveChat = function() {
        const win = document.getElementById('vbChatWindow');
        const launcher = document.getElementById('vbChatLauncher');
        if (!win) return;

        win.classList.add('is-open');
        if (launcher) launcher.style.display = 'none';
        isOpen = true;

        initConversation();

        setTimeout(() => {
            const input = document.getElementById('vbChatInput');
            if (input) input.focus();
        }, 150);

        if (!pollInterval) {
            pollInterval = setInterval(pollVisitorMessages, 3500);
        }
    };

    window.closeLiveChat = function() {
        const win = document.getElementById('vbChatWindow');
        const launcher = document.getElementById('vbChatLauncher');
        if (!win) return;

        win.classList.remove('is-open');
        if (launcher) launcher.style.display = 'flex';
        isOpen = false;

        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    };

    function initConversation() {
        const formData = new FormData();
        formData.append('session_token', sessionToken);
        formData.append('visitor_name', visitorName);
        formData.append('visitor_contact', visitorContact);

        fetch('/api/livechat/init', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.messages) {
                renderMessages(data.messages);
            }
        })
        .catch(() => {});
    }

    window.handleVisitorSend = function(e) {
        e.preventDefault();
        const input = document.getElementById('vbChatInput');
        const btn = document.getElementById('vbChatSendBtn');
        const text = input.value.trim();
        if (!text) return;

        btn.disabled = true;

        // Message temporaire optimiste
        const tempMsg = {
            id: 'temp_' + Date.now(),
            sender: 'visitor',
            message: text,
            created_at: new Date().toISOString()
        };
        appendBubble(tempMsg);
        input.value = '';

        const formData = new FormData();
        formData.append('session_token', sessionToken);
        formData.append('message', text);
        formData.append('visitor_name', visitorName);
        formData.append('visitor_contact', visitorContact);

        fetch('/api/livechat/send', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.message) {
                // Remplacer l'ID du message temporaire par le vrai
                const tempEl = document.querySelector(`[data-msg-id="${tempMsg.id}"]`);
                if (tempEl) {
                    tempEl.setAttribute('data-msg-id', data.message.id);
                }
                lastMessageId = Math.max(lastMessageId, parseInt(data.message.id, 10));
            }
        })
        .catch(() => {})
        .finally(() => {
            btn.disabled = false;
            input.focus();
        });
    };

    function pollVisitorMessages() {
        if (!isOpen) return;

        fetch(`/api/livechat/poll?session_token=${encodeURIComponent(sessionToken)}&after_id=${lastMessageId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    appendBubble(msg);
                });
            }
        })
        .catch(() => {});
    }

    function renderMessages(messages) {
        const container = document.getElementById('vbChatMessages');
        if (!container) return;
        container.innerHTML = '';

        messages.forEach(msg => appendBubble(msg));
    }

    function appendBubble(msg) {
        const container = document.getElementById('vbChatMessages');
        if (!container) return;

        // Éviter les doublons
        if (msg.id && document.querySelector(`[data-msg-id="${msg.id}"]`)) {
            return;
        }

        const row = document.createElement('div');
        row.className = `vb-chat-msg-row ${msg.sender === 'admin' ? 'msg-admin' : 'msg-visitor'}`;
        if (msg.id) row.setAttribute('data-msg-id', msg.id);

        const timeStr = msg.created_at ? msg.created_at.substring(11, 16) : '';

        row.innerHTML = `
            <div class="vb-chat-bubble">
                <div class="vb-bubble-text">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</div>
                <div class="vb-bubble-time">${timeStr}</div>
            </div>
        `;

        container.appendChild(row);
        container.scrollTop = container.scrollHeight;

        if (msg.id && !String(msg.id).startsWith('temp_')) {
            lastMessageId = Math.max(lastMessageId, parseInt(msg.id, 10));
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialiser une fois le DOM prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDOM);
    } else {
        initDOM();
    }
})();
