<div class="prordv-admin-wrapper" style="padding:10px 0;">
    <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:var(--evo-radius-lg, 12px);padding:24px;border:1px solid var(--evo-border, #e2e8f0);box-shadow:var(--evo-shadow-sm, 0 1px 3px rgba(0,0,0,0.05));margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h2 style="font-size:1.4rem;font-weight:800;color:var(--evo-text-primary, #0f172a);margin:0 0 6px;">
                    <i class="fa-solid fa-calendar-check" style="color:var(--evo-color-success, #10b981);"></i> Supervision des Rendez-vous Pro
                </h2>
                <p style="color:var(--evo-text-muted, #64748b);margin:0;font-size:0.95rem;">
                    Gérez vos agendas de prise de rendez-vous en ligne, configurez les prestations, équipes et grilles horaires.
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <a href="/rdv" target="_blank" class="button" style="display:inline-flex;align-items:center;gap:6px;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir la page publique (/rdv)
                </a>
                <a href="/pro/rdv?commerce_id={{ commerce.id }}" target="_blank" class="button" style="display:inline-flex;align-items:center;gap:6px;background:var(--evo-color-primary, #0f766e);color:#fff;">
                    <i class="fa-solid fa-calendar-days"></i> Espace Pro (/pro/rdv)
                </a>
            </div>
        </div>
    </div>

    <!-- Formulaire de création rapide d'un agenda -->
    <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:var(--evo-radius-lg, 12px);padding:20px 24px;border:1px solid var(--evo-border, #e2e8f0);box-shadow:var(--evo-shadow-sm, 0 1px 3px rgba(0,0,0,0.05));margin-bottom:24px;">
        <h3 style="margin:0 0 14px;font-size:1.05rem;font-weight:700;color:var(--evo-text-primary, #0f172a);">
            <i class="fa-solid fa-plus-circle" style="color:var(--evo-color-success, #10b981);"></i> Créer un nouvel agenda de rendez-vous
        </h3>
        <form action="/admin/prordv" method="post" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;align-items:end;">
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:var(--evo-text-secondary, #475569);">Nom de l'établissement / service *</label>
                <input type="text" name="standalone_nom" required placeholder="Ex: Cabinet Conseil, Salon..." style="width:100%;margin:0;">
            </div>
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:var(--evo-text-secondary, #475569);">Profil métier</label>
                <select name="standalone_preset" style="width:100%;margin:0;">
                    {% for k, pr in presets %}
                    <option value="{{ k }}">{{ pr.label }}</option>
                    {% endfor %}
                </select>
            </div>
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:var(--evo-text-secondary, #475569);">Email de notification</label>
                <input type="email" name="standalone_email" placeholder="contact@domaine.fr" style="width:100%;margin:0;">
            </div>
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:var(--evo-text-secondary, #475569);">Téléphone</label>
                <input type="text" name="standalone_tel" placeholder="01 23 45 67 89" style="width:100%;margin:0;">
            </div>
            <div>
                <button type="submit" class="button" style="width:100%;margin:0;justify-content:center;">
                    <i class="fa-solid fa-plus"></i> Ajouter l'agenda
                </button>
            </div>
        </form>
    </div>

    <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:var(--evo-radius-lg, 12px);border:1px solid var(--evo-border, #e2e8f0);overflow-x:auto;box-shadow:var(--evo-shadow-sm, 0 1px 3px rgba(0,0,0,0.05));">
        <table style="width:100%;border-collapse:collapse;text-align:left;font-size:0.95rem;">
            <thead>
                <tr style="background:var(--evo-bg-tertiary, #f8fafc);border-bottom:1px solid var(--evo-border, #e2e8f0);color:var(--evo-text-secondary, #475569);font-size:0.82rem;text-transform:uppercase;">
                    <th style="padding:14px 18px;">Établissement / Agenda</th>
                    <th style="padding:14px 18px;">Profil</th>
                    <th style="padding:14px 18px;">Contact</th>
                    <th style="padding:14px 18px;text-align:center;">RDV Confirmés</th>
                    <th style="padding:14px 18px;text-align:center;">Statut</th>
                    <th style="padding:14px 18px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                {% for c in commerces %}
                <tr style="border-bottom:1px solid var(--evo-border, #e2e8f0);">
                    <td style="padding:14px 18px;font-weight:700;color:var(--evo-text-primary, #0f172a);">
                        {{ c.nom }}
                        <div style="font-size:0.8rem;font-weight:400;color:var(--evo-text-muted, #64748b);">
                            Lien public : <a href="/rdv/{{ c.slug }}" target="_blank" style="color:var(--evo-color-primary, #2563eb);">/rdv/{{ c.slug }}</a>
                        </div>
                    </td>
                    <td style="padding:14px 18px;color:var(--evo-text-secondary, #475569);">
                        <span style="background:var(--evo-bg-tertiary, #f1f5f9);padding:4px 10px;border-radius:6px;font-size:0.82rem;text-transform:capitalize;border:1px solid var(--evo-border, #e2e8f0);">
                            {{ c.type }}
                        </span>
                    </td>
                    <td style="padding:14px 18px;color:var(--evo-text-secondary, #475569);font-size:0.88rem;">
                        <div><i class="fa-solid fa-envelope"></i> {{ c.email }}</div>
                        {% if c.telephone %}
                        <div><i class="fa-solid fa-phone"></i> {{ c.telephone }}</div>
                        {% endif %}
                    </td>
                    <td style="padding:14px 18px;text-align:center;">
                        <span style="font-weight:800;color:var(--evo-color-success, #059669);font-size:1.05rem;">{{ c.confirmed_count }}</span>
                        <span style="color:var(--evo-text-muted, #94a3b8);font-size:0.8rem;">/ {{ c.total_count }}</span>
                    </td>
                    <td style="padding:14px 18px;text-align:center;">
                        {% if c.is_active %}
                        <span style="background:rgba(16,185,129,0.14);color:var(--evo-color-success, #059669);font-weight:700;padding:4px 10px;border-radius:12px;font-size:0.8rem;">
                            Actif
                        </span>
                        {% else %}
                        <span style="background:rgba(239,68,68,0.14);color:var(--evo-color-danger, #b91c1c);font-weight:700;padding:4px 10px;border-radius:12px;font-size:0.8rem;">
                            En pause
                        </span>
                        {% endif %}
                    </td>
                    <td style="padding:14px 18px;text-align:right;">
                        <div style="display:flex;justify-content:flex-end;gap:8px;align-items:center;flex-wrap:wrap;">
                            <a href="/admin/prordv/commerce/{{ c.id }}" class="button" style="padding:6px 12px;font-size:0.82rem;">
                                <i class="fa-solid fa-calendar-days"></i> Agenda
                            </a>
                            <a href="/pro/rdv/config?commerce_id={{ c.id }}" class="button" style="background:var(--evo-bg-tertiary, #f8fafc);color:var(--evo-text-primary, #334155);border:1px solid var(--evo-border, #cbd5e1);padding:6px 12px;font-size:0.82rem;" title="Configurer les prestations, équipe et horaires">
                                <i class="fa-solid fa-gear"></i> Configurer
                            </a>
                            <form action="/admin/prordv/toggle/{{ c.id }}" method="post" style="margin:0;display:inline;">
                                <button type="submit" class="button" style="background:var(--evo-bg-elevated, #fff);border:1px solid var(--evo-border, #cbd5e1);color:{% if c.is_active %}var(--evo-color-warning, #d97706){% else %}var(--evo-color-success, #059669){% endif %};padding:6px 10px;font-size:0.82rem;">
                                    {% if c.is_active %}Pause{% else %}Activer{% endif %}
                                </button>
                            </form>
                            <form action="/admin/prordv/delete/{{ c.id }}" method="post" style="margin:0;display:inline;" onsubmit="return confirm('Supprimer définitivement cet agenda et ses rendez-vous ?');">
                                <button type="submit" class="button alert" style="padding:6px 10px;font-size:0.82rem;" title="Supprimer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
</div>
