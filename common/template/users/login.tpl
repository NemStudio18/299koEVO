{% INCLUDE COMMON ~ "template/users/auth-assets.tpl" %}
<section class="user-auth-form auth-card">
    <form method="post" action="{{loginLink}}" autocomplete="off" id="login-form">
        <input type="hidden" name="_csrf" value="{{_csrfToken}}" class="csrf-hidden">
        <input type="text" name="_hp_website" value="" class="hp-field" aria-hidden="true" tabindex="-1" autocomplete="off" style="display:none !important; position:absolute !important; left:-9999px !important; visibility:hidden !important; pointer-events:none !important;" rel="nofollow">
        
        <p class="field">
            <label for="adminEmail">Adresse email ou Nom_Prénom</label>
            <input type="text" id="adminEmail" name="adminEmail" required autocomplete="username" placeholder="ex: dupont_jean ou email@exemple.com">
            <span class="field-validation" id="email-validation"></span>
        </p>
        <p class="field">
            <label for="adminPwd">{{Lang.password}}</label>
            <div class="password-input-wrapper">
                <input type="password" id="adminPwd" name="adminPwd" required autocomplete="current-password">
                <button type="button" class="toggle-password" aria-label="Afficher/Masquer le mot de passe" title="Afficher/Masquer le mot de passe">
                    <i class="fa-solid fa-eye" id="toggle-password-icon"></i>
                </button>
            </div>
            <span class="field-validation" id="password-validation"></span>
        </p>
        <p class="field checkbox">
            <input type="checkbox" name="remember" id="remember"/>
            <label for="remember">{{Lang.users.remember}}</label>
        </p>
        <p class="actions">
            <button type="submit" class="button success">{{Lang.validate}}</button>
            <a class="button alert" href="{{CORE.getConfigVal("siteUrl") }}">{{ Lang.quit}}</a>
        </p>
        <p class="links">
            <a href="{{lostLink}}">{{Lang.lost-password}}</a>
            <span class="auth-sep">|</span>
            <a href="{{ ROUTER.generate('register') }}">Pas encore de compte ?</a>
        </p>
    </form>
</section>


