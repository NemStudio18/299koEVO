<div class="livechat-admin-container">
    <div class="livechat-admin-header">
        <div class="livechat-title-wrap">
            <h1 class="livechat-title"><i class="fa-solid fa-comments"></i> LiveChat Direct</h1>
            <span class="livechat-subtitle">Échanges en direct avec les commerçants et internautes</span>
        </div>
        <button type="button" class="livechat-btn-settings" onclick="toggleSettingsModal(true)">
            <i class="fa-solid fa-sliders"></i> <span>Configuration & Webhook Discord</span>
        </button>
    </div>

    <div class="livechat-main-layout {% if mobileList or not activeConversation %}mobile-show-list{% endif %}"
         id="livechatMainLayout"
         data-active-conv="{{activeConvId|default(0)}}"
         data-filter-status="{{filterStatus|default('all')}}"
         data-total-matching="{{totalMatching|default(0)}}"
         data-page-limit="{{pageLimit|default(50)}}">
        <!-- Colonne Gauche : Liste des Conversations -->
        <aside class="livechat-sidebar" id="livechatSidebar">
            <div class="livechat-sidebar-header">
                <div class="livechat-sidebar-title-row">
                    <h3>Discussions</h3>
                    <span class="livechat-badge-count" id="convTotalBadge" title="Total des discussions">
                        {{stats.all|default(0)}}
                    </span>
                </div>
                <span class="livechat-unread-pill {% if not stats.unread %}is-hidden{% endif %}" id="convUnreadPill">
                    <span id="convUnreadCount">{{stats.unread|default(0)}}</span> non lue(s)
                </span>
            </div>

            <div class="livechat-sidebar-tools">
                <div class="livechat-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search"
                           id="convSearchInput"
                           placeholder="Rechercher nom, contact, message..."
                           value="{{searchQuery|default('')}}"
                           autocomplete="off"
                           aria-label="Rechercher dans les discussions">
                    <button type="button"
                            id="convSearchClear"
                            class="livechat-search-clear {% if not searchQuery %}is-hidden{% endif %}"
                            title="Effacer la recherche"
                            onclick="clearConversationSearch()">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="livechat-filter-tabs" role="tablist" aria-label="Filtrer les discussions">
                    <button type="button"
                            class="livechat-filter-tab {% if filterStatus == 'all' or not filterStatus %}is-active{% endif %}"
                            data-status="all"
                            onclick="setStatusFilter('all')">
                        Toutes <span class="tab-count" id="tabCountAll">{{stats.all|default(0)}}</span>
                    </button>
                    <button type="button"
                            class="livechat-filter-tab {% if filterStatus == 'unread' %}is-active{% endif %}"
                            data-status="unread"
                            onclick="setStatusFilter('unread')">
                        Non lues <span class="tab-count" id="tabCountUnread">{{stats.unread|default(0)}}</span>
                    </button>
                    <button type="button"
                            class="livechat-filter-tab {% if filterStatus == 'open' %}is-active{% endif %}"
                            data-status="open"
                            onclick="setStatusFilter('open')">
                        Ouvertes <span class="tab-count" id="tabCountOpen">{{stats.open|default(0)}}</span>
                    </button>
                    <button type="button"
                            class="livechat-filter-tab {% if filterStatus == 'closed' %}is-active{% endif %}"
                            data-status="closed"
                            onclick="setStatusFilter('closed')">
                        Clôturées <span class="tab-count" id="tabCountClosed">{{stats.closed|default(0)}}</span>
                    </button>
                </div>
            </div>

            <div class="livechat-conv-list" id="convListContainer">
                {% if conversations %}
                    {% for conv in conversations %}
                    <a href="/admin/livechat?conv={{conv.id}}{% if filterStatus and filterStatus != 'all' %}&status={{filterStatus}}{% endif %}{% if searchQueryEncoded %}&q={{searchQueryEncoded}}{% endif %}"
                       class="livechat-conv-item {% if conv.id == activeConvId %}active{% endif %} {% if conv.unread_admin > 0 %}has-unread{% endif %} {% if conv.status == 'closed' %}is-closed{% endif %}"
                       data-conv-id="{{conv.id}}">
                        <div class="conv-avatar">
                            <i class="fa-solid fa-user"></i>
                            {% if conv.unread_admin > 0 %}
                            <span class="conv-unread-dot">{{conv.unread_admin}}</span>
                            {% endif %}
                        </div>
                        <div class="conv-meta">
                            <div class="conv-top-line">
                                <span class="conv-name">{{conv.visitor_name|default('Visiteur')}}</span>
                                <span class="conv-time">{{conv.updated_at|date('d/m H:i')}}</span>
                            </div>
                            <div class="conv-preview-row">
                                <div class="conv-preview">
                                    {{conv.last_message|default('Nouvelle discussion...')|slice(0, 60)}}
                                </div>
                                {% if conv.status == 'closed' %}
                                <span class="conv-status-pill">Clôturée</span>
                                {% endif %}
                            </div>
                            {% if conv.visitor_contact %}
                            <div class="conv-contact-badge">
                                <i class="fa-solid fa-address-book"></i> {{conv.visitor_contact}}
                            </div>
                            {% endif %}
                        </div>
                    </a>
                    {% endfor %}
                {% else %}
                    <div class="livechat-empty-sidebar">
                        <i class="fa-solid fa-inbox"></i>
                        <p>Aucune discussion trouvée.<br>Modifiez votre filtre ou attendez de nouveaux messages.</p>
                    </div>
                {% endif %}
            </div>

            <div class="livechat-sidebar-footer" id="sidebarFooter">
                <span class="sidebar-count-summary" id="convShowingSummary">
                    {{loadedCount|default(0)}} / {{totalMatching|default(0)}} affichée(s)
                </span>
                <button type="button"
                        id="btnLoadMoreConvs"
                        class="btn-load-more-convs {% if not hasMore %}is-hidden{% endif %}"
                        onclick="loadMoreConversations()">
                    <i class="fa-solid fa-angles-down"></i> Charger plus
                </button>
            </div>
        </aside>

        <!-- Colonne Droite : Fil de discussion actif -->
        <section class="livechat-chatbox" id="livechatChatbox">
            {% if activeConversation %}
            <!-- En-tête de la discussion -->
            <header class="chatbox-header">
                <button type="button" class="chatbox-back-btn" onclick="showSidebarOnMobile()">
                    <i class="fa-solid fa-arrow-left"></i> <span>Discussions</span>
                </button>
                <div class="chatbox-user-info">
                    <div class="chatbox-user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="chatbox-user-text">
                        <h2 class="chatbox-username">{{activeConversation.visitor_name|default('Visiteur')}}</h2>
                        {% if activeConversation.visitor_contact %}
                        <div class="chatbox-contact-links">
                            <span>Contact :</span>
                            <strong>{{activeConversation.visitor_contact}}</strong>
                        </div>
                        {% endif %}
                    </div>
                </div>
                <div class="chatbox-actions">
                    <button type="button" class="btn-toggle-status {% if activeConversation.status == 'closed' %}is-closed{% endif %}"
                            onclick="toggleConvStatus({{activeConversation.id}})">
                        <i class="fa-solid {% if activeConversation.status == 'closed' %}fa-box-archive{% else %}fa-check{% endif %}"></i>
                        <span>{% if activeConversation.status == 'closed' %}Rouvrir{% else %}Clôturer{% endif %}</span>
                    </button>
                </div>
            </header>

            <!-- Corps des messages -->
            <div class="chatbox-messages" id="chatboxMessages">
                {% if activeMessages %}
                    {% for msg in activeMessages %}
                    <div class="chat-msg-row {% if msg.sender == 'admin' %}msg-admin{% else %}msg-visitor{% endif %}" data-msg-id="{{msg.id}}">
                        <div class="chat-bubble">
                            <div class="bubble-sender">{{msg.sender_name|default(msg.sender == 'admin' ? 'Moi' : 'Visiteur')}}</div>
                            <div class="bubble-text">{{msg.message|nl2br}}</div>
                            <div class="bubble-time">{{msg.created_at|date('d/m H:i')}}</div>
                        </div>
                    </div>
                    {% endfor %}
                {% else %}
                    <div class="chatbox-placeholder">
                        <i class="fa-solid fa-comments"></i>
                        <p>Aucun message dans cette conversation.</p>
                    </div>
                {% endif %}
            </div>

            <!-- Barre de saisie de réponse -->
            <footer class="chatbox-footer">
                <form id="adminReplyForm" onsubmit="sendAdminReply(event)">
                    <input type="hidden" id="replyConvId" value="{{activeConversation.id}}">
                    <div class="reply-input-wrap">
                        <textarea id="replyMessage"
                                  placeholder="Répondre à {{activeConversation.visitor_name|default('cet interlocuteur')}}... (Entrée pour envoyer)"
                                  rows="2"
                                  required
                                  onkeydown="handleReplyKeydown(event)"></textarea>
                        <button type="submit" id="btnSendReply" class="btn-send-reply">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Envoyer</span>
                        </button>
                    </div>
                </form>
            </footer>
            {% else %}
            <div class="livechat-no-active-conv">
                <div class="no-conv-icon">
                    <i class="fa-regular fa-comment-dots"></i>
                </div>
                <h2>Sélectionnez une discussion</h2>
                <p>Choisissez une conversation dans la liste de gauche pour échanger en direct avec le commerçant ou l'internaute.</p>
            </div>
            {% endif %}
        </section>
    </div>
</div>

<!-- MODAL CONFIGURATION DISCORD & BIENVENUE -->
<div id="settingsModal" class="livechat-modal" style="display: none;">
    <div class="modal-backdrop" onclick="toggleSettingsModal(false)"></div>
    <div class="modal-content">
        <header class="modal-header">
            <h3><i class="fa-solid fa-sliders"></i> Configuration LiveChat</h3>
            <button type="button" class="modal-close" onclick="toggleSettingsModal(false)">&times;</button>
        </header>
        <form id="liveChatConfigForm" onsubmit="saveLiveChatConfig(event)">
            <div class="modal-body">
                <div class="form-group-chat">
                    <label for="cfg_webhook"><strong>URL de Webhook Discord</strong></label>
                    <input type="url" id="cfg_webhook" name="discord_webhook" value="{{config.discord_webhook}}"
                           placeholder="https://discord.com/api/webhooks/..." class="chat-input-full">
                    <small>Dès qu'un visiteur vous écrit sur le site, une notification avec le message et un lien direct pour répondre sur votre smartphone sera envoyée sur ce salon.</small>
                </div>

                <div class="form-group-chat">
                    <label for="cfg_name"><strong>Nom de l'administrateur / Expéditeur</strong></label>
                    <input type="text" id="cfg_name" name="admin_name" value="{{config.admin_name|default('Équipe Déclic')}}"
                           class="chat-input-full">
                </div>

                <div class="form-group-chat">
                    <label for="cfg_welcome"><strong>Message de bienvenue automatique</strong></label>
                    <textarea id="cfg_welcome" name="welcome_message" rows="3" class="chat-input-full">{{config.welcome_message}}</textarea>
                    <small>Ce message s'affiche automatiquement au démarrage de la discussion chez le visiteur.</small>
                </div>

                <div id="cfgFeedback" style="display:none; padding:10px; border-radius:8px; margin-top:10px;"></div>
            </div>
            <footer class="modal-footer">
                <button type="button" class="btn-cancel" onclick="toggleSettingsModal(false)">Annuler</button>
                <button type="submit" id="btnSaveConfig" class="btn-save">Enregistrer</button>
            </footer>
        </form>
    </div>
</div>

<style>
/* --- STYLES DU TABLEAU DE BORD LIVECHAT ADMIN --- */
.livechat-admin-container {
    --lc-accent: #1f62a9;
    --lc-accent-dark: #164980;
    --lc-accent-soft: #eff6ff;
    --lc-accent-border: #93c5fd;
    max-width: 1400px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.livechat-admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}
.livechat-title {
    font-size: 1.55rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.livechat-title i {
    color: var(--lc-accent);
}
.livechat-subtitle {
    font-size: 0.9rem;
    color: #64748b;
}
.livechat-btn-settings {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    color: #334155;
    padding: 8px 16px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s ease;
}
.livechat-btn-settings:hover {
    background: #f8fafc;
    border-color: var(--lc-accent);
    color: var(--lc-accent);
}

/* Layout 2 colonnes avec contrainte stricte de hauteur pour scroller indépendamment */
.livechat-main-layout {
    display: grid;
    grid-template-columns: 370px minmax(0, 1fr);
    grid-template-rows: minmax(0, 1fr);
    gap: 0;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    height: calc(100vh - 185px);
    min-height: 540px;
    max-height: 840px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
}

/* Colonne Gauche (Sidebar) */
.livechat-sidebar {
    min-height: 0;
    height: 100%;
    overflow: hidden;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
}
.livechat-sidebar-header {
    padding: 14px 16px 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    flex-shrink: 0;
}
.livechat-sidebar-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.livechat-sidebar-header h3 {
    margin: 0;
    font-size: 1.02rem;
    font-weight: 800;
    color: #0f172a;
}
.livechat-badge-count {
    background: #e2e8f0;
    color: #334155;
    padding: 2px 9px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
}
.livechat-unread-pill {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 700;
}
.livechat-sidebar-tools {
    padding: 8px 12px 10px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex-shrink: 0;
}
.livechat-search-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.livechat-search-wrap > i {
    position: absolute;
    left: 11px;
    color: #94a3b8;
    font-size: 0.85rem;
    pointer-events: none;
}
.livechat-search-wrap input {
    width: 100%;
    padding: 7px 30px 7px 32px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 0.86rem;
    background: #f8fafc;
    color: #0f172a;
    transition: border-color 0.15s, background 0.15s;
}
.livechat-search-wrap input:focus {
    outline: none;
    background: #ffffff;
    border-color: var(--lc-accent);
    box-shadow: 0 0 0 3px rgba(31, 98, 169, 0.12);
}
.livechat-search-clear {
    position: absolute;
    right: 6px;
    border: none;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 6px;
    font-size: 0.85rem;
}
.livechat-search-clear:hover {
    color: #0f172a;
    background: #e2e8f0;
}
.livechat-filter-tabs {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 4px;
}
.livechat-filter-tab {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    border-radius: 8px;
    padding: 5px 4px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    white-space: nowrap;
    transition: all 0.15s ease;
}
.livechat-filter-tab:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.livechat-filter-tab.is-active {
    background: var(--lc-accent);
    border-color: var(--lc-accent);
    color: #ffffff;
}
.livechat-filter-tab .tab-count {
    font-size: 0.7rem;
    opacity: 0.85;
    font-weight: 700;
}

.livechat-conv-list {
    flex: 1 1 0;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.livechat-conv-list::-webkit-scrollbar,
.chatbox-messages::-webkit-scrollbar {
    width: 7px;
}
.livechat-conv-list::-webkit-scrollbar-thumb,
.chatbox-messages::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}
.livechat-conv-list::-webkit-scrollbar-thumb:hover,
.chatbox-messages::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.livechat-conv-item {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 12px;
    border-radius: 11px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    text-decoration: none !important;
    color: inherit;
    transition: all 0.15s ease;
}
.livechat-conv-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}
.livechat-conv-item.active {
    background: var(--lc-accent-soft) !important;
    border-color: var(--lc-accent-border) !important;
    box-shadow: 0 2px 8px rgba(31, 98, 169, 0.12);
}
.livechat-conv-item.has-unread {
    border-left: 4px solid var(--lc-accent);
}
.livechat-conv-item.is-closed {
    opacity: 0.78;
}
.livechat-conv-item.is-closed.active {
    opacity: 1;
}
.conv-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dbeafe;
    color: var(--lc-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    position: relative;
    flex-shrink: 0;
}
.conv-unread-dot {
    position: absolute;
    top: -3px;
    right: -3px;
    background: #ef4444;
    color: #ffffff;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    font-size: 0.68rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
}
.conv-meta {
    flex: 1;
    min-width: 0;
}
.conv-top-line {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 6px;
    margin-bottom: 2px;
}
.conv-name {
    font-weight: 700;
    color: #0f172a;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.conv-time {
    font-size: 0.72rem;
    color: #64748b;
    flex-shrink: 0;
}
.conv-preview-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
}
.conv-preview {
    font-size: 0.8rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
    flex: 1;
}
.conv-status-pill {
    flex-shrink: 0;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.conv-contact-badge {
    margin-top: 3px;
    font-size: 0.72rem;
    color: var(--lc-accent);
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.livechat-sidebar-footer {
    padding: 9px 12px;
    border-top: 1px solid #e2e8f0;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-shrink: 0;
}
.sidebar-count-summary {
    font-size: 0.76rem;
    color: #64748b;
    font-weight: 600;
}
.btn-load-more-convs {
    background: var(--lc-accent-soft);
    color: var(--lc-accent);
    border: 1px solid var(--lc-accent-border);
    border-radius: 8px;
    padding: 5px 10px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.btn-load-more-convs:hover {
    background: #dbeafe;
}
.btn-load-more-convs:disabled {
    opacity: 0.6;
    cursor: wait;
}

.livechat-empty-sidebar,
.chatbox-placeholder,
.livechat-no-active-conv {
    margin: auto;
    text-align: center;
    padding: 32px 20px;
    color: #64748b;
}
.livechat-empty-sidebar i,
.chatbox-placeholder i,
.no-conv-icon {
    font-size: 2.2rem;
    color: #94a3b8;
    margin-bottom: 10px;
    display: block;
}
.livechat-no-active-conv h2 {
    margin: 0 0 8px;
    font-size: 1.2rem;
    color: #1e293b;
}
.livechat-no-active-conv p,
.livechat-empty-sidebar p {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Colonne Droite (Chatbox) */
.livechat-chatbox {
    min-height: 0;
    height: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: #ffffff;
}
.chatbox-header {
    padding: 13px 18px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    flex-shrink: 0;
}
.chatbox-back-btn {
    display: none;
    background: var(--lc-accent-soft);
    border: 1px solid var(--lc-accent-border);
    border-radius: 8px;
    color: var(--lc-accent);
    font-weight: 700;
    font-size: 0.86rem;
    cursor: pointer;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    flex-shrink: 0;
}
.chatbox-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
.chatbox-user-text {
    min-width: 0;
}
.chatbox-user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #dbeafe;
    color: var(--lc-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.chatbox-username {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chatbox-contact-links {
    font-size: 0.82rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chatbox-contact-links strong {
    color: #0f172a;
}
.btn-toggle-status {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.btn-toggle-status.is-closed {
    background: #fef2f2;
    border-color: #fecaca;
    color: #b91c1c;
}

/* Messages */
.chatbox-messages {
    flex: 1 1 0;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #fdfefe;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.chat-msg-row {
    display: flex;
    width: 100%;
    flex-shrink: 0;
}
.chat-msg-row.msg-visitor {
    justify-content: flex-start;
}
.chat-msg-row.msg-admin {
    justify-content: flex-end;
}
.chat-bubble {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 14px;
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.msg-visitor .chat-bubble {
    background: #f1f5f9;
    color: #0f172a;
    border-bottom-left-radius: 3px;
    border: 1px solid #e2e8f0;
}
.msg-admin .chat-bubble {
    background: linear-gradient(135deg, var(--lc-accent) 0%, var(--lc-accent-dark) 100%);
    color: #ffffff;
    border-bottom-right-radius: 3px;
}
.bubble-sender {
    font-size: 0.72rem;
    font-weight: 700;
    margin-bottom: 2px;
    opacity: 0.82;
}
.bubble-text {
    font-size: 0.94rem;
    line-height: 1.45;
    word-break: break-word;
}
.bubble-time {
    font-size: 0.68rem;
    text-align: right;
    margin-top: 4px;
    opacity: 0.75;
}

/* Footer Saisie */
.chatbox-footer {
    padding: 12px 18px;
    border-top: 1px solid #e2e8f0;
    background: #ffffff;
    flex-shrink: 0;
}
.reply-input-wrap {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}
.reply-input-wrap textarea {
    flex: 1;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 0.95rem;
    font-family: inherit;
    resize: none;
    box-sizing: border-box;
    transition: border-color 0.2s;
}
.reply-input-wrap textarea:focus {
    outline: none;
    border-color: var(--lc-accent);
    box-shadow: 0 0 0 3px rgba(31, 98, 169, 0.15);
}
.btn-send-reply {
    background: linear-gradient(135deg, var(--lc-accent) 0%, var(--lc-accent-dark) 100%);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    padding: 12px 20px;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(31, 98, 169, 0.25);
    transition: transform 0.15s ease;
    height: 48px;
}
.btn-send-reply:hover {
    transform: translateY(-1px);
}

/* Modal */
.livechat-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(2px);
}
.modal-content {
    position: relative;
    background: #ffffff;
    border-radius: 16px;
    width: 90%;
    max-width: 550px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    z-index: 1;
}
.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    margin: 0;
    font-size: 1.15rem;
    color: #0f172a;
}
.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #94a3b8;
}
.modal-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.form-group-chat {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.form-group-chat label {
    font-size: 0.88rem;
    color: #334155;
}
.form-group-chat small {
    font-size: 0.8rem;
    color: #64748b;
}
.chat-input-full {
    width: 100%;
    padding: 10px 12px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.95rem;
    box-sizing: border-box;
}
.chat-input-full:focus {
    border-color: var(--lc-accent);
    outline: none;
}
.modal-footer {
    padding: 14px 20px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.btn-cancel {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}
.btn-save {
    background: var(--lc-accent);
    color: #fff;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
}
.is-hidden {
    display: none !important;
}

/* RESPONSIVE MOBILE */
@media (max-width: 850px) {
    .livechat-main-layout {
        grid-template-columns: 1fr;
        height: calc(100vh - 165px);
        min-height: 460px;
        max-height: none;
    }
    .livechat-main-layout:not(.mobile-show-list) .livechat-sidebar {
        display: none;
    }
    .livechat-main-layout:not(.mobile-show-list) .chatbox-back-btn {
        display: inline-flex;
    }
    .livechat-main-layout.mobile-show-list .livechat-chatbox {
        display: none;
    }
}
</style>

<script>
const mainLayout = document.getElementById('livechatMainLayout');
const activeConvId = parseInt(mainLayout ? mainLayout.dataset.activeConv : '0', 10) || 0;
let lastMessageId = 0;
let currentFilterStatus = (mainLayout && mainLayout.dataset.filterStatus) || 'all';
let currentLimit = parseInt((mainLayout && mainLayout.dataset.pageLimit) || '50', 10) || 50;
let loadedCount = document.querySelectorAll('#convListContainer .livechat-conv-item').length;
let totalMatching = parseInt((mainLayout && mainLayout.dataset.totalMatching) || '0', 10) || loadedCount;
let searchDebounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    updateLastMessageId();
    scrollMessagesToBottom();

    const searchInput = document.getElementById('convSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const clearBtn = document.getElementById('convSearchClear');
            if (clearBtn) {
                clearBtn.classList.toggle('is-hidden', searchInput.value.trim() === '');
            }
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                fetchConversationList(false);
            }, 250);
        });
    }

    setInterval(pollAdminMessages, 3500);
});

function getCurrentSearch() {
    const input = document.getElementById('convSearchInput');
    return input ? input.value.trim() : '';
}

function setStatusFilter(status) {
    if (currentFilterStatus === status) return;
    currentFilterStatus = status;
    currentLimit = 50;

    document.querySelectorAll('.livechat-filter-tab').forEach(btn => {
        btn.classList.toggle('is-active', btn.dataset.status === status);
    });

    fetchConversationList(false);
}

function clearConversationSearch() {
    const input = document.getElementById('convSearchInput');
    const clearBtn = document.getElementById('convSearchClear');
    if (input) {
        input.value = '';
        input.focus();
    }
    if (clearBtn) clearBtn.classList.add('is-hidden');
    fetchConversationList(false);
}

function loadMoreConversations() {
    const btn = document.getElementById('btnLoadMoreConvs');
    if (btn) btn.disabled = true;
    fetchConversationList(true).finally(() => {
        if (btn) btn.disabled = false;
    });
}

function fetchConversationList(append) {
    const offset = append ? loadedCount : 0;
    const pageSize = 50;
    const q = getCurrentSearch();
    const params = new URLSearchParams({
        status: currentFilterStatus,
        q: q,
        limit: String(pageSize),
        offset: String(offset),
    });

    return fetch(`/admin/livechat/conversations?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            if (append) {
                appendConversationItems(data.conversations || []);
                currentLimit = Math.max(currentLimit, loadedCount);
            } else {
                renderConversationList(data.conversations || [], false);
                currentLimit = pageSize;
            }
            totalMatching = parseInt(data.total || 0, 10);
            updateSidebarStats(data.stats || null);
            updateSidebarFooter(Boolean(data.has_more));
        })
        .catch(() => {});
}

function buildConversationHref(convId) {
    const params = new URLSearchParams({ conv: String(convId) });
    if (currentFilterStatus && currentFilterStatus !== 'all') {
        params.set('status', currentFilterStatus);
    }
    const q = getCurrentSearch();
    if (q) {
        params.set('q', q);
    }
    return `/admin/livechat?${params.toString()}`;
}

function formatConvDate(dateStr) {
    if (!dateStr) return '';
    const cleaned = String(dateStr).replace(' ', 'T');
    const match = cleaned.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);
    if (match) {
        return `${match[3]}/${match[2]} ${match[4]}:${match[5]}`;
    }
    return String(dateStr).substring(11, 16);
}

function createConversationElement(conv) {
    const a = document.createElement('a');
    const unread = parseInt(conv.unread_admin || 0, 10);
    const isClosed = conv.status === 'closed';
    const isActive = parseInt(conv.id, 10) === activeConvId;

    a.href = buildConversationHref(conv.id);
    a.className = 'livechat-conv-item'
        + (isActive ? ' active' : '')
        + (unread > 0 ? ' has-unread' : '')
        + (isClosed ? ' is-closed' : '');
    a.setAttribute('data-conv-id', conv.id);

    const previewRaw = conv.last_message ? String(conv.last_message) : 'Nouvelle discussion...';
    const preview = previewRaw.length > 60 ? previewRaw.slice(0, 60) : previewRaw;
    const contactHtml = conv.visitor_contact
        ? `<div class="conv-contact-badge"><i class="fa-solid fa-address-book"></i> ${escapeHtml(String(conv.visitor_contact))}</div>`
        : '';
    const unreadHtml = unread > 0 ? `<span class="conv-unread-dot">${unread}</span>` : '';
    const statusPillHtml = isClosed ? '<span class="conv-status-pill">Clôturée</span>' : '';

    a.innerHTML = `
        <div class="conv-avatar">
            <i class="fa-solid fa-user"></i>
            ${unreadHtml}
        </div>
        <div class="conv-meta">
            <div class="conv-top-line">
                <span class="conv-name">${escapeHtml(conv.visitor_name || 'Visiteur')}</span>
                <span class="conv-time">${escapeHtml(formatConvDate(conv.updated_at))}</span>
            </div>
            <div class="conv-preview-row">
                <div class="conv-preview">${escapeHtml(preview)}</div>
                ${statusPillHtml}
            </div>
            ${contactHtml}
        </div>
    `;
    return a;
}

function renderConversationList(conversations, preserveScroll) {
    const container = document.getElementById('convListContainer');
    if (!container) return;
    const prevScroll = preserveScroll ? container.scrollTop : 0;

    container.innerHTML = '';
    if (!conversations || conversations.length === 0) {
        container.innerHTML = `
            <div class="livechat-empty-sidebar">
                <i class="fa-solid fa-inbox"></i>
                <p>Aucune discussion trouvée.<br>Modifiez votre filtre ou attendez de nouveaux messages.</p>
            </div>
        `;
        loadedCount = 0;
    } else {
        conversations.forEach(conv => container.appendChild(createConversationElement(conv)));
        loadedCount = conversations.length;
    }

    if (preserveScroll) {
        container.scrollTop = prevScroll;
    } else {
        container.scrollTop = 0;
    }
}

function appendConversationItems(conversations) {
    const container = document.getElementById('convListContainer');
    if (!container || !conversations.length) return;

    conversations.forEach(conv => {
        if (!container.querySelector(`.livechat-conv-item[data-conv-id="${conv.id}"]`)) {
            container.appendChild(createConversationElement(conv));
        }
    });
    loadedCount = container.querySelectorAll('.livechat-conv-item').length;
}

function updateSidebarStats(stats) {
    if (!stats) return;
    const setTxt = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = String(val ?? 0);
    };
    setTxt('convTotalBadge', stats.all);
    setTxt('tabCountAll', stats.all);
    setTxt('tabCountUnread', stats.unread);
    setTxt('tabCountOpen', stats.open);
    setTxt('tabCountClosed', stats.closed);
    setTxt('convUnreadCount', stats.unread);

    const unreadPill = document.getElementById('convUnreadPill');
    if (unreadPill) {
        unreadPill.classList.toggle('is-hidden', !(stats.unread > 0));
    }
}

function updateSidebarFooter(hasMore) {
    const summary = document.getElementById('convShowingSummary');
    if (summary) {
        summary.textContent = `${loadedCount} / ${totalMatching} affichée(s)`;
    }
    const btn = document.getElementById('btnLoadMoreConvs');
    if (btn) {
        btn.classList.toggle('is-hidden', !hasMore);
    }
}

function updateLastMessageId() {
    const rows = document.querySelectorAll('.chat-msg-row');
    if (rows.length > 0) {
        const last = rows[rows.length - 1];
        lastMessageId = parseInt(last.getAttribute('data-msg-id') || 0, 10);
    }
}

function scrollMessagesToBottom() {
    const container = document.getElementById('chatboxMessages');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

function showSidebarOnMobile() {
    if (mainLayout) {
        mainLayout.classList.add('mobile-show-list');
        if (window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('list', '1');
            window.history.replaceState({}, '', url.toString());
        }
    } else {
        window.location.href = '/admin/livechat?list=1';
    }
}

function handleReplyKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('adminReplyForm').requestSubmit();
    }
}

function sendAdminReply(e) {
    e.preventDefault();
    const textarea = document.getElementById('replyMessage');
    const msg = textarea.value.trim();
    if (!msg) return;

    const btn = document.getElementById('btnSendReply');
    btn.disabled = true;

    const formData = new FormData();
    formData.append('conversation_id', activeConvId);
    formData.append('message', msg);

    fetch('/admin/livechat/reply', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.message) {
            appendMessageToChat(data.message, true);
            textarea.value = '';
            scrollMessagesToBottom();
            pollAdminMessages();
        } else {
            alert(data.error || 'Erreur lors de l\'envoi.');
        }
    })
    .catch(() => alert('Erreur réseau lors de l\'envoi'))
    .finally(() => {
        btn.disabled = false;
        textarea.focus();
    });
}

function pollAdminMessages() {
    const params = new URLSearchParams({
        conversation_id: String(activeConvId),
        after_id: String(lastMessageId),
        status: currentFilterStatus,
        q: getCurrentSearch(),
        limit: String(Math.max(50, loadedCount, currentLimit)),
    });

    fetch(`/admin/livechat/poll?${params.toString()}`)
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        if (data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => {
                appendMessageToChat(msg, false);
            });
            scrollMessagesToBottom();
        }
        if (Array.isArray(data.conversations)) {
            renderConversationList(data.conversations, true);
            totalMatching = parseInt(data.total || 0, 10);
            updateSidebarStats(data.stats || null);
            updateSidebarFooter(Boolean(data.has_more));
        }
    })
    .catch(() => {});
}

function appendMessageToChat(msg, isMine) {
    const container = document.getElementById('chatboxMessages');
    if (!container) return;

    if (document.querySelector(`.chat-msg-row[data-msg-id="${msg.id}"]`)) {
        return;
    }

    const row = document.createElement('div');
    row.className = `chat-msg-row ${msg.sender === 'admin' ? 'msg-admin' : 'msg-visitor'}`;
    row.setAttribute('data-msg-id', msg.id);

    const timeStr = formatConvDate(msg.created_at);
    const senderTitle = msg.sender_name || (msg.sender === 'admin' ? 'Moi' : 'Visiteur');

    row.innerHTML = `
        <div class="chat-bubble">
            <div class="bubble-sender">${escapeHtml(senderTitle)}</div>
            <div class="bubble-text">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</div>
            <div class="bubble-time">${escapeHtml(timeStr)}</div>
        </div>
    `;

    container.appendChild(row);
    lastMessageId = Math.max(lastMessageId, parseInt(msg.id, 10));
}

function toggleConvStatus(convId) {
    const formData = new FormData();
    formData.append('conversation_id', convId);

    fetch('/admin/livechat/toggle-status', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

function toggleSettingsModal(show) {
    document.getElementById('settingsModal').style.display = show ? 'flex' : 'none';
}

function saveLiveChatConfig(e) {
    e.preventDefault();
    const form = document.getElementById('liveChatConfigForm');
    const btn = document.getElementById('btnSaveConfig');
    const feedback = document.getElementById('cfgFeedback');

    btn.disabled = true;
    const formData = new FormData(form);

    fetch('/admin/livechat/config', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        feedback.style.display = 'block';
        if (data.success) {
            feedback.style.background = '#ecfdf5';
            feedback.style.color = '#065f46';
            feedback.innerHTML = 'Paramètres enregistrés avec succès !';
            setTimeout(() => toggleSettingsModal(false), 1200);
        } else {
            feedback.style.background = '#fef2f2';
            feedback.style.color = '#991b1b';
            feedback.innerHTML = escapeHtml(data.error || 'Erreur lors de l\'enregistrement');
        }
    })
    .catch(() => {
        feedback.style.display = 'block';
        feedback.style.background = '#fef2f2';
        feedback.style.color = '#991b1b';
        feedback.innerHTML = 'Erreur réseau.';
    })
    .finally(() => btn.disabled = false);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text == null ? '' : String(text);
    return div.innerHTML;
}
</script>
