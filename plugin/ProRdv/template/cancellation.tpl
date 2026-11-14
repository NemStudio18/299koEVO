<div style="max-width:580px;margin:50px auto;padding:0 16px;font-family:system-ui,-apple-system,sans-serif;">
    <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);padding:36px;border:1px solid var(--evo-border, #e2e8f0);text-align:center;">
        <div style="width:64px;height:64px;background:#fef2f2;color:#dc2626;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2rem;">
            <i class="fa-solid fa-calendar-xmark"></i>
        </div>
        <h1 style="font-size:1.5rem;font-weight:800;color:var(--evo-text-primary, #0f172a);margin:0 0 10px;">Annulation de Rendez-vous</h1>
        <p style="color:var(--evo-text-secondary, #334155);font-size:1rem;line-height:1.6;margin:0 0 24px;">
            {{ res.message|default(res.error) }}
        </p>
        <a href="/" class="btn" style="display:inline-block;background:#0f172a;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">
            Retour à l'accueil 299koEVO
        </a>
    </div>
</div>
