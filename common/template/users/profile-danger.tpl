<section class="user-profile-danger">
    <div class="profile-danger-header">
        <h1>Zone de danger</h1>
        <a href="{{ ROUTER.generate("profile") }}" class="button secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour au profil
        </a>
    </div>

    <!-- Section de suppression de compte -->
    <div class="user-delete-account" style="margin-top: 2rem; padding: 2rem; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px;">
        <h2 style="margin-top: 0; margin-bottom: 1rem; color: #856404;">
            <i class="fa-solid fa-exclamation-triangle"></i> Suppression de compte
        </h2>
        <p style="color: #856404; margin-bottom: 1rem; font-size: 1.1rem;">
            <strong>Attention :</strong> La suppression de votre compte est définitive et irréversible. 
            Toutes vos données seront supprimées, y compris :
        </p>
        <ul style="color: #856404; margin-bottom: 1.5rem; padding-left: 2rem;">
            <li>Vos informations personnelles</li>
            <li>Vos contributions (revendications, suggestions, propositions)</li>
            <li>Vos photos et contenus uploadés</li>
            <li>Vos messages privés</li>
            <li>Toutes les données associées à votre compte</li>
        </ul>
        <p style="color: #856404; margin-bottom: 1.5rem; font-weight: bold;">
            Cette action ne peut pas être annulée. Êtes-vous absolument sûr de vouloir continuer ?
        </p>
        <form method="post" action="{{profileDeleteLink}}" autocomplete="off" onsubmit="return confirm('Êtes-vous absolument sûr de vouloir supprimer votre compte ? Cette action est irréversible et toutes vos données seront définitivement perdues.');">
            <input type="hidden" name="_csrf" value="{{_csrfToken}}">
            <p>
                <label for="delete-confirmation" style="color: #856404; font-weight: bold; display: block; margin-bottom: 0.5rem;">
                    Pour confirmer, tapez "supprimer" ci-dessous :
                </label>
                <input id="delete-confirmation" type="text" name="delete_confirmation" 
                       placeholder="supprimer" 
                       style="width: 100%; max-width: 400px; padding: 0.75rem; margin-top: 0.5rem; border: 2px solid #dc3545; border-radius: 4px; font-size: 1rem;"
                       required autocomplete="off">
            </p>
            <p style="margin-top: 1.5rem;">
                <button type="submit" class="button alert" style="background: #dc3545; color: white; border: none; padding: 0.75rem 1.5rem; font-size: 1rem; font-weight: bold;">
                    <i class="fa-solid fa-trash"></i> Supprimer mon compte définitivement
                </button>
                <a href="{{ ROUTER.generate("profile") }}" class="button secondary" style="margin-left: 0.5rem;">Annuler</a>
            </p>
        </form>
    </div>
</section>

<style>
.profile-danger-header,
.profile-edit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--panel-border);
}

.profile-danger-header h1,
.profile-edit-header h1 {
    margin: 0;
    color: var(--font-color);
}

@media (max-width: 768px) {
    .profile-danger-header,
    .profile-edit-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>


