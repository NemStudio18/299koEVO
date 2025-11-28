<section class="user-auth-form">
    <header>
        <h2>{{ Lang.users-registration-title }}</h2>
    </header>
    <form method="post" action="{{ registerLink }}" autocomplete="off">
        <input type="hidden" name="_csrf" value="{{ _csrfToken }}">
        <input type="text" name="_email" value="" class="hp-field" aria-hidden="true" tabindex="-1">

        <p>
            <label for="register-username">{{ Lang.users-registration-username }}</label>
            <input id="register-username" type="text" name="username" required minlength="3" maxlength="60">
        </p>

        <p>
            <label for="register-email">{{ Lang.users-registration-email }}</label>
            <input id="register-email" type="email" name="email" required>
        </p>

        <p>
            <label for="register-password">{{ Lang.users-registration-password }}</label>
            <input id="register-password" type="password" name="password" required minlength="8">
        </p>

        <p>
            <label for="register-password-confirm">{{ Lang.users-registration-password-confirm }}</label>
            <input id="register-password-confirm" type="password" name="password_confirm" required minlength="8">
        </p>

        <p>
            <button type="submit" class="button success">{{ Lang.users-registration-submit }}</button>
        </p>
    </form>
</section>

