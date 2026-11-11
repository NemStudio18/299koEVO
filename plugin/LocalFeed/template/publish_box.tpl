<div class="pro-publish-card">
    <div class="publish-card-header">
        <div class="publish-author-badge">
            <i class="fa-solid fa-bullhorn text-gold"></i>
            <h3>Quoi de neuf dans votre établissement aujourd'hui ?</h3>
        </div>
        <span class="publish-subtitle">Votre publication sera diffusée instantanément sur le Mur de Bourges et sur votre fiche.</span>
    </div>

    <form id="pro-feed-publish-form" action="/localfeed/poi/<?= $poi['id'] ?>/publish" method="POST" class="pro-publish-form" onsubmit="if (typeof tinymce !== 'undefined') { tinymce.triggerSave(); }">
        <div class="publish-type-selector">
            <label class="type-pill active">
                <input type="radio" name="post_type" value="news" checked />
                <i class="fa-solid fa-newspaper"></i> Actualité
            </label>
            <label class="type-pill">
                <input type="radio" name="post_type" value="special_offer" />
                <i class="fa-solid fa-tag"></i> Bon plan
            </label>
            <label class="type-pill">
                <input type="radio" name="post_type" value="photo" />
                <i class="fa-solid fa-camera"></i> Photo du jour
            </label>
            <label class="type-pill">
                <input type="radio" name="post_type" value="event" />
                <i class="fa-solid fa-calendar-day"></i> Événement
            </label>
            <label class="type-pill">
                <input type="radio" name="post_type" value="alert" />
                <i class="fa-solid fa-triangle-exclamation"></i> Changement d'horaires
            </label>
        </div>

        <div class="publish-input-group">
            <input type="text" name="title" class="publish-title-input" placeholder="Titre de votre publication (optionnel)..." />
        </div>

        <div class="publish-input-group">
            <textarea id="pro-feed-content" name="content" class="publish-content-textarea editor" rows="3" required placeholder="Partagez un nouvel arrivage, un plat du jour, une actualité avec les Berruyers..."></textarea>
        </div>

        <div class="publish-input-group publish-media-row">
            <div class="publish-media-input-wrapper">
                <i class="fa-solid fa-image"></i>
                <input type="url" name="media_url" class="publish-media-input" placeholder="Lien d'une photo / image d'illustration (https://...)" />
            </div>
            <label class="publish-pinned-label">
                <input type="checkbox" name="pinned" value="1" />
                <span>Épingler en haut</span>
            </label>
        </div>

        <div class="publish-actions">
            <button type="submit" class="btn-publish-submit">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Publier sur le Mur de Bourges</span>
            </button>
        </div>
    </form>
</div>
