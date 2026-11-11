{% INCLUDE COMMON ~ "template/users/auth-assets.tpl" %}
<section class="user-auth-form auth-card">
    <form method="post" action="{{registerLink}}" autocomplete="off">
        <input type="hidden" name="_csrf" value="{{_csrfToken}}" class="csrf-hidden">
        <input type="text" name="_hp_website" value="" class="hp-field" aria-hidden="true" tabindex="-1" autocomplete="off" style="display:none !important; position:absolute !important; left:-9999px !important; visibility:hidden !important; pointer-events:none !important;" rel="nofollow">

        <p class="field">
            <label for="register-lastname">Nom</label>
            <input id="register-lastname" type="text" name="lastname" required minlength="2" maxlength="60" autocomplete="family-name">
            <span class="field-validation" id="lastname-validation"></span>
        </p>

        <p class="field">
            <label for="register-firstname">Prénom</label>
            <input id="register-firstname" type="text" name="firstname" required minlength="2" maxlength="60" autocomplete="given-name">
            <span class="field-validation" id="firstname-validation"></span>
        </p>

        <p class="field">
            <label for="register-email">{{Lang.users-registration-email}}</label>
            <input id="register-email" type="email" name="email" required autocomplete="email">
            <span class="field-validation" id="email-validation"></span>
        </p>

        <p class="field">
            <label for="register-password">{{Lang.users-registration-password}}</label>
            <div class="password-input-wrapper">
                <input id="register-password" type="password" name="password" required minlength="8" autocomplete="new-password">
                <button type="button" class="toggle-password" data-target="register-password" aria-label="Afficher/Masquer le mot de passe" title="Afficher/Masquer le mot de passe">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
            <div class="password-requirements" id="password-requirements">
                <small>Le mot de passe doit contenir :</small>
                <ul>
                    <li id="req-length" class="requirement">Au moins 8 caractères</li>
                </ul>
            </div>
            <span class="field-validation" id="password-validation"></span>
        </p>

        <p class="field">
            <label for="register-password-confirm">{{Lang.users-registration-password-confirm}}</label>
            <div class="password-input-wrapper">
                <input id="register-password-confirm" type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
                <button type="button" class="toggle-password" data-target="register-password-confirm" aria-label="Afficher/Masquer le mot de passe" title="Afficher/Masquer le mot de passe">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
            <span class="field-validation" id="password-confirm-validation"></span>
        </p>

        <p class="actions">
            <button type="submit" class="button success">{{Lang.users-registration-submit}}</button>
        </p>
    </form>
</section>



