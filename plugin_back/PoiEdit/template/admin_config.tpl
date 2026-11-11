<section class="module-card">
    <header>
        <i class="fa-solid fa-cog"></i>
        Configuration POI Editor
        <a href="{{ ROUTER.generate("admin-poi-edit") }}" class="button small" style="float:right; margin-top:-5px;">Retour au tableau de bord</a>
    </header>

    <form method="post" action="{{ ROUTER.generate("admin-poi-edit-config-save") }}">
        <div class="form-group">
            <label for="helpUrl">URL "Comment contribuer"</label>
            <input type="url" id="helpUrl" name="helpUrl" value="{{ helpUrl }}" class="form-control" placeholder="https://example.com/comment-contribuer">
            <small>URL vers la page d'aide expliquant comment contribuer. Cette URL sera utilisée dans le menu de navigation.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="button success">Enregistrer</button>
            <a href="{{ ROUTER.generate("admin-poi-edit") }}" class="button">Annuler</a>
        </div>
    </form>
</section>

