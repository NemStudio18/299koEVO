<section>
    <header>{{ Lang.newsletter-admin.params-title }}</header>
    
    <form method="post" action="{ ROUTER.generate("newsletter-admin-save-params") }">
        {{ SHOW.tokenField }}
        
        <label for="pageTitle">{{ Lang.newsletter-admin.pageTitle }}</label>
        <input type="text" name="pageTitle" id="pageTitle" value="{{ runPlugin.getConfigVal("pageTitle") }}" />
        
        <label for="subscriptionMessage">{{ Lang.newsletter-admin.subscriptionMessage }}</label>
        <textarea name="subscriptionMessage" id="subscriptionMessage" rows="3">{{ runPlugin.getConfigVal("subscriptionMessage") }}</textarea>
        
        <label for="unsubscriptionMessage">{{ Lang.newsletter-admin.unsubscriptionMessage }}</label>
        <textarea name="unsubscriptionMessage" id="unsubscriptionMessage" rows="3">{{ runPlugin.getConfigVal("unsubscriptionMessage") }}</textarea>
        
        <label for="alreadySubscribedMessage">{{ Lang.newsletter-admin.alreadySubscribedMessage }}</label>
        <textarea name="alreadySubscribedMessage" id="alreadySubscribedMessage" rows="3">{{ runPlugin.getConfigVal("alreadySubscribedMessage") }}</textarea>
        
        <button type="submit" class="button">{{ Lang.save }}</button>
    </form>
</section>


