<div class="login-required-notice">
    <div class="login-required-content">
        <i class="fa-solid fa-{{iconClass}} icon-main"></i>
        <div class="login-required-text">
            <h3>Connectez-vous pour {{action}}</h3>
            <p>Rejoignez la communauté en quelques secondes.</p>
        </div>
    </div>
    <div class="login-required-buttons">
        <a href="{ ROUTER.generate("register") }" class="button large btn-register">
            Créer un compte
        </a>
        <a href="{ ROUTER.generate("login") }" class="button large btn-login">
            <i class="fa-solid fa-sign-in-alt"></i> Connexion
        </a>
    </div>
</div>
