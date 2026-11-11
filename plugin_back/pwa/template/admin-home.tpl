<section class="content">
    <header>{{lang_pwa_name}}</header>
    
    <div class="card">
        <h3>Clés VAPID</h3>
        {% IF hasKeys %}
            <p><strong>{{lang_pwa_public_key}}:</strong></p>
            <pre class="pwa-pre">{{publicKey}}</pre>
            <form method="post" class="pwa-form-mt">
                {{SHOW.tokenField}}
                <button type="submit" name="regenerate_keys" class="button">{{lang_pwa_regenerate_keys}}</button>
            </form>
        {% ELSE %}
            <p class="msg warning">{{keysNotGeneratedText}}</p>
            <form method="post" action="" class="pwa-form-mt">
                {{SHOW.tokenField}}
                <button type="submit" name="generate_keys" class="button">{{generateKeysText}}</button>
            </form>
        {% ENDIF %}
    </div>
    
    <div class="card pwa-card-mt">
        <h3>Abonnements</h3>
        <p><strong>Nombre d'abonnements:</strong> {{subscriptionCount}}</p>
    </div>
    
    <div class="card pwa-card-mt">
        <h3>{{lang_pwa_send_notification}}</h3>
        <form method="post" action="{{sendNotificationUrl}}">
            {{SHOW.tokenField()}}
            <div class="pwa-form-group">
                <label for="notification-title"><strong>{{lang_pwa_notification_title}}:</strong></label>
                <input type="text" id="notification-title" name="title" required class="pwa-input-full">
            </div>
            <div class="pwa-form-group">
                <label for="notification-message"><strong>{{lang_pwa_notification_message}}:</strong></label>
                <textarea id="notification-message" name="message" required class="pwa-textarea-notification"></textarea>
            </div>
            <div class="pwa-form-group">
                <label for="notification-url"><strong>{{lang_pwa_notification_url}}:</strong></label>
                <input type="url" id="notification-url" name="url" value="/" class="pwa-input-full">
            </div>
            <button type="submit" class="button">{{lang_pwa_send}}</button>
        </form>
    </div>
</section>



