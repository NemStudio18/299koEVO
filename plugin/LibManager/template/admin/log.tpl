<section class="overflow-auto">
    <header><i class="fa-solid fa-file-lines"></i> LibManager — Historique des mises à jour</header>

    <div style="margin-bottom: 16px;">
        <a href="{ ROUTER.generate('libmanager-dashboard') }" class="button secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour aux bibliothèques
        </a>
    </div>

    <div style="background: #ffffff; border: 1px solid var(--adm-border); border-radius: 10px; padding: 16px;">
        <pre style="margin: 0; max-height: 550px; overflow-y: auto; font-size: 0.88rem; line-height: 1.6; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--adm-text-strong); white-space: pre-wrap;">{{ log_content }}</pre>
    </div>
</section>

