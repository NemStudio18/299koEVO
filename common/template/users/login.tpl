<section class="user-auth-form">
    <form method="post" action="{{ loginLink }}" autocomplete="off">
        <input type="hidden" name="_csrf" value="{{ _csrfToken }}">
        <input type="text" name="_email" value="" class="hp-field" aria-hidden="true" tabindex="-1">
        
        <p>
            <label for="adminEmail">{{ Lang.email }}</label>
            <input type="email" id="adminEmail" name="adminEmail" required>
        </p>
        <p>
            <label for="adminPwd">{{ Lang.password }}</label>
            <input type="password" id="adminPwd" name="adminPwd" required>
        </p>
        <p>
            <input type="checkbox" name="remember" id="remember"/>
            <label for="remember">{{ Lang.users.remember }}</label>
        </p>
        <p>
            <button type="submit" class="button success">{{ Lang.validate }}</button>
            <a class="button alert" href="{{ CORE.getConfigVal("siteUrl") }}">{{ Lang.quit }}</a>
        </p>
        <p>
            <a href="{{ lostLink }}">{{ Lang.lost-password }}</a>
        </p>
    </form>
</section>
