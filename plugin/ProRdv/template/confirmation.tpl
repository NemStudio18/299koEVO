<div style="max-width:580px;margin:50px auto;padding:0 16px;font-family:system-ui,-apple-system,sans-serif;">
    <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);padding:36px;border:1px solid var(--evo-border, #e2e8f0);text-align:center;">
        {% if res.success %}
            <div style="width:64px;height:64px;background:#ecfdf5;color:#059669;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2rem;">
                <i class="fa-solid fa-check"></i>
            </div>
            <h1 style="font-size:1.5rem;font-weight:800;color:var(--evo-text-primary, #0f172a);margin:0 0 10px;">
                {% if res.statut == 'confirme' %}
                    Rendez-vous Confirmé !
                {% else %}
                    Demande Transmise au Commerçant
                {% endif %}
            </h1>
            <p style="color:var(--evo-text-secondary, #334155);font-size:1rem;line-height:1.6;margin:0 0 20px;">
                {% if res.already %}
                    {{ res.message }}
                {% else %}
                    Votre rendez-vous pour <strong>{{ res.presta }}</strong> chez <strong>{{ res.commerce }}</strong> est bien enregistré pour le <strong>{{ res.quand }}</strong>.
                {% endif %}
            </p>
            <div style="background:var(--evo-bg-tertiary, #f8fafc);border-radius:10px;padding:14px;color:var(--evo-text-muted, #64748b);font-size:0.9rem;margin-bottom:24px;">
                <i class="fa-solid fa-circle-info"></i> Un récapitulatif complet vous a été adressé par email.
            </div>
        {% else %}
            <div style="width:64px;height:64px;background:#fef2f2;color:#dc2626;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2rem;">
                <i class="fa-solid fa-xmark"></i>
            </div>
            <h1 style="font-size:1.5rem;font-weight:800;color:var(--evo-text-primary, #0f172a);margin:0 0 10px;">Lien non valide ou expiré</h1>
            <p style="color:#b91c1c;font-size:1rem;margin:0 0 24px;">{{ res.error }}</p>
        {% endif %}

        <a href="/" class="btn" style="display:inline-block;background:#0f172a;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">
            Retour à l'accueil 299koEVO
        </a>
    </div>
</div>
