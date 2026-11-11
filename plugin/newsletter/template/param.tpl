<section class="content">
    <header><h3>{{ Lang.newsletter-admin.params-title }}</h3></header>
    
    <form method="post" action="{ ROUTER.generate("newsletter-admin-save-params") }">
        {{ SHOW.tokenField }}
        
        <p>
            <label for="pageTitle">{{ Lang.newsletter-admin.pageTitle }}</label><br>
            <input type="text" name="pageTitle" id="pageTitle" value="{{ runPlugin.getConfigVal("pageTitle") }}" />
        </p>
        
        <p>
            <label for="subscriptionMessage">{{ Lang.newsletter-admin.subscriptionMessage }}</label><br>
            <textarea name="subscriptionMessage" id="subscriptionMessage" rows="3">{{ runPlugin.getConfigVal("subscriptionMessage") }}</textarea>
        </p>
        
        <p>
            <label for="unsubscriptionMessage">{{ Lang.newsletter-admin.unsubscriptionMessage }}</label><br>
            <textarea name="unsubscriptionMessage" id="unsubscriptionMessage" rows="3">{{ runPlugin.getConfigVal("unsubscriptionMessage") }}</textarea>
        </p>
        
        <p>
            <label for="alreadySubscribedMessage">{{ Lang.newsletter-admin.alreadySubscribedMessage }}</label><br>
            <textarea name="alreadySubscribedMessage" id="alreadySubscribedMessage" rows="3">{{ runPlugin.getConfigVal("alreadySubscribedMessage") }}</textarea>
        </p>
        
        <p><button type="submit" class="button primary"><i class="fa-solid fa-save"></i> {{ Lang.save }}</button></p>
    </form>
</section>


