<section>
    <header>{{ Lang.guestbook-admin-params }}</header>
    
    <form method="post" action="{ ROUTER.generate("guestbook-admin-save-params") }">
        {{ SHOW.tokenField }}
        
        <label for="pageTitle">{{ Lang.guestbook.pageTitle }}</label>
        <input type="text" name="pageTitle" id="pageTitle" value="{{ runPlugin.getConfigVal("pageTitle") }}" placeholder="{{ Lang.guestbook.pageTitle-placeholder }}" />
        <small>{{ Lang.guestbook.pageTitle-help }}</small>
        
        <label for="messagesTabTitle">{{ Lang.guestbook.messagesTabTitle }}</label>
        <input type="text" name="messagesTabTitle" id="messagesTabTitle" value="{{ runPlugin.getConfigVal("messagesTabTitle") }}" placeholder="{{ Lang.guestbook.messagesTabTitle-placeholder }}" />
        <small>{{ Lang.guestbook.messagesTabTitle-help }}</small>
        
        <label for="messagesTabContent">{{ Lang.guestbook.messagesTabContent }}</label>
        <textarea name="messagesTabContent" id="messagesTabContent" rows="10" class="editor">{% HOOK.beforeEditEditor(runPlugin.getConfigVal("messagesTabContent")) %}</textarea>
        <small>{{ Lang.guestbook.messagesTabContent-help }}</small>
        
        <label for="adminReplyName">{{ Lang.guestbook.adminReplyName }}</label>
        <input type="text" name="adminReplyName" id="adminReplyName" value="{{ runPlugin.getConfigVal("adminReplyName") }}" placeholder="{{ Lang.guestbook.adminReplyName-placeholder }}" />
        <small>{{ Lang.guestbook.adminReplyName-help }}</small>
        
        <button type="submit" class="button">{{ Lang.save }}</button>
    </form>
</section>

