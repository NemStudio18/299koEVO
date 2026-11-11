{% INCLUDE COMMON ~ "template/users/auth-assets.tpl" %}
<section class="user-profile-edit">
    <div class="profile-edit-header">
        <a href="{{ ROUTER.generate("profile") }}" class="button secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour au profil
        </a>
    </div>

    <!-- Photo de profil -->
    <div class="user-auth-form user-profile-photo-container">
        <h2>Photo de profil</h2>
        {% IF hasProfilePhoto %}
        <div class="profile-photo-preview">
            <img src="{{profilePhotoUrl}}" alt="Photo de profil">
        </div>
        <form method="post" action="{{profilePhotoDeleteLink}}" class="profile-photo-actions">
            <input type="hidden" name="_csrf" value="{{_csrfToken}}">
            <button type="submit" class="button alert">
                <i class="fa-solid fa-trash"></i> Supprimer la photo
            </button>
        </form>
        {% ENDIF %}
        
        <form method="post" action="{{profilePhotoUploadLink}}" enctype="multipart/form-data" class="profile-photo-upload-form">
            <input type="hidden" name="_csrf" value="{{_csrfToken}}">
            <p>
                <label for="profile-photo-input">{% IF hasProfilePhoto %}Remplacer la photo{% ELSE %}Ajouter une photo{% ENDIF %}</label>
                <input id="profile-photo-input" type="file" name="profile_photo" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required>
                <small class="form-help">Formats acceptés : JPG, PNG, GIF, WebP. Taille maximale : 5 MB. L'image sera redimensionnée à 500x500px maximum.</small>
            </p>
            <p>
                <button type="submit" class="button success">
                    <i class="fa-solid fa-upload"></i> {% IF hasProfilePhoto %}Remplacer{% ELSE %}Télécharger{% ENDIF %}
                </button>
            </p>
        </form>
    </div>

    <!-- Formulaire de modification du profil -->
    <div class="user-auth-form">
        <h2>Informations du compte</h2>
        <form method="post" action="{{profileSaveLink}}" autocomplete="off">
            <input type="hidden" name="_csrf" value="{{_csrfToken}}">

            <p>
                <label for="profile-username">{{Lang.users-registration-username}}</label>
                <input id="profile-username" type="text" name="username" value="{{user.username}}" required minlength="3" maxlength="60">
            </p>

            <p>
                <label for="profile-firstname">Prénom</label>
                <input id="profile-firstname" type="text" name="firstname" value="{{firstname}}" maxlength="60" placeholder="Votre prénom">
            </p>

            <p>
                <label for="profile-lastname">Nom</label>
                <input id="profile-lastname" type="text" name="lastname" value="{{lastname}}" maxlength="60" placeholder="Votre nom">
            </p>

            <p>
                <label for="profile-email">{{Lang.users-registration-email}}</label>
                <input id="profile-email" type="email" name="email" value="{{user.email}}" required>
            </p>
            
            <div class="dynamic-profile-fields">
                <!-- Plugin specific fields injected via hook -->
                {% HOOK.user:profile-edit-dynamic-fields(user) %}
            </div>



            <div style="margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1.5rem;">
                <h3>{{Lang.users-registration-password}}</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                    Laissez vide pour conserver votre mot de passe actuel.
                </p>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="profile-password">{{Lang.users-registration-password}}</label>
                    <div class="password-input-wrapper">
                        <input id="profile-password" type="password" name="password" autocomplete="new-password">
                        <button type="button" class="toggle-password" aria-label="Afficher/Masquer le mot de passe" title="Afficher/Masquer le mot de passe">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="profile-password-confirm">{{Lang.users-registration-password-confirm}}</label>
                    <div class="password-input-wrapper">
                        <input id="profile-password-confirm" type="password" name="password_confirm" autocomplete="new-password">
                        <button type="button" class="toggle-password" aria-label="Afficher/Masquer le mot de passe" title="Afficher/Masquer le mot de passe">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div style="margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1rem;">
                <button type="submit" class="button success">{{Lang.submit}}</button>
                <a href="{{ ROUTER.generate("profile") }}" class="button secondary" style="margin-left: 0.5rem;">Annuler</a>
            </div>
        </form>
    </div>
</section>

<script>
// Les fonctions toggleCityOther et toggleDistrictOther ont été remplacées par une logique générique dans le loop FOR
</script>



