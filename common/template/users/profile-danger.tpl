<section class="user-profile-danger">
    <div class="profile-danger-header">
        <h1>Zone de danger</h1>
        <a href="{{ ROUTER.generate("profile") }}" class="button secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour au profil
        </a>
    </div>

    <!-- Section de suppression de compte -->
    <div class="user-delete-account">
        <h2>
            <i class="fa-solid fa-exclamation-triangle"></i> Suppression de compte
        </h2>
        <p class="user-delete-lead">
            <strong>Attention :</strong> La suppression de votre compte est définitive et irréversible. 
            Toutes vos données seront supprimées, y compris :
        </p>
        <ul class="user-delete-list">
            <li>Vos informations personnelles</li>
            <li>Vos contributions (revendications, suggestions, propositions)</li>
            <li>Vos photos et contenus uploadés</li>
            <li>Vos messages privés</li>
            <li>Toutes les données associées à votre compte</li>
        </ul>
        <p class="user-delete-warn">
            Cette action ne peut pas être annulée. Êtes-vous absolument sûr de vouloir continuer ?
        </p>
        <form method="post" action="{{profileDeleteLink}}" autocomplete="off" onsubmit="return confirm('Êtes-vous absolument sûr de vouloir supprimer votre compte ? Cette action est irréversible et toutes vos données seront définitivement perdues.');">
            <input type="hidden" name="_csrf" value="{{_csrfToken}}">
            <p>
                <label for="delete-confirmation" class="user-delete-label">
                    Pour confirmer, tapez "supprimer" ci-dessous :
                </label>
                <input id="delete-confirmation" type="text" name="delete_confirmation"
                       placeholder="supprimer"
                       class="user-delete-confirm-input"
                       required autocomplete="off">
            </p>
            <p class="user-delete-actions">
                <button type="submit" class="button alert user-delete-submit-btn">
                    <i class="fa-solid fa-trash"></i> Supprimer mon compte définitivement
                </button>
                <a href="{{ ROUTER.generate("profile") }}" class="button secondary user-delete-cancel-btn">Annuler</a>
            </p>
        </form>
    </div>
</section>


