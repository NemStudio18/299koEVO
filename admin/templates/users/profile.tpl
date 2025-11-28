<section class="user-auth-form">
    <header>
        <h2>{{ Lang.users-profile-title }}</h2>
    </header>
    <form method="post" action="{{ profileSaveLink }}" autocomplete="off">
        <input type="hidden" name="_csrf" value="{{ _csrfToken }}">

        <p>
            <label for="profile-username">{{ Lang.users-registration-username }}</label>
            <input id="profile-username" type="text" name="username" value="{{ user.username }}" required minlength="3" maxlength="60">
        </p>

        <p>
            <label for="profile-email">{{ Lang.users-registration-email }}</label>
            <input id="profile-email" type="email" name="email" value="{{ user.email }}" required>
        </p>

        <fieldset>
            <legend>{{ Lang.users-profile-password-section }}</legend>
            <p>
                <label for="profile-current-password">{{ Lang.users-profile-current-password }}</label>
                <input id="profile-current-password" type="password" name="current_password" autocomplete="off">
            </p>
            <p>
                <label for="profile-new-password">{{ Lang.users-profile-new-password }}</label>
                <input id="profile-new-password" type="password" name="new_password" autocomplete="off">
            </p>
            <p>
                <label for="profile-password-confirm">{{ Lang.users-registration-password-confirm }}</label>
                <input id="profile-password-confirm" type="password" name="password_confirm" autocomplete="off">
            </p>
        </fieldset>

        <p>
            <button type="submit" class="button success">{{ Lang.submit }}</button>
        </p>
    </form>
</section>

