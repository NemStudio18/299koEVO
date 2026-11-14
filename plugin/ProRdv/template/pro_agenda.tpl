<div class="prordv-agenda-wrapper" style="max-width:850px;margin:30px auto;padding:0 16px;font-family:system-ui,-apple-system,sans-serif;">
    <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);padding:30px;border:1px solid var(--evo-border, #e2e8f0);">
        
        <!-- En-tête Espace Pro -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;border-bottom:1px solid var(--evo-border, #f1f5f9);padding-bottom:18px;">
            <div>
                <span style="display:inline-block;background:#ecfdf5;color:#059669;font-weight:700;font-size:0.8rem;padding:4px 10px;border-radius:12px;margin-bottom:6px;">
                    <i class="fa-solid fa-crown"></i> Espace Professionnel
                </span>
                <h1 style="font-size:1.6rem;font-weight:800;color:var(--evo-text-primary, #0f172a);margin:0;">
                    Agenda : {{ commerce.nom }}
                </h1>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="/rdv/{{ commerce.slug }}" target="_blank" class="btn" style="display:inline-flex;align-items:center;gap:6px;background:var(--evo-bg-tertiary, #f8fafc);color:var(--evo-text-secondary, #334155);border:1px solid var(--evo-border, #cbd5e1);padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.9rem;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Page publique
                </a>
                <a href="/pro/rdv/config?commerce_id={{ commerce.id }}" class="btn" style="display:inline-flex;align-items:center;gap:6px;background:#0f766e;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.9rem;">
                    <i class="fa-solid fa-gear"></i> Paramètres
                </a>
            </div>
        </div>

        <!-- Navigation par date -->
        <div style="display:flex;align-items:center;justify-content:space-between;background:var(--evo-bg-tertiary, #f8fafc);padding:12px 16px;border-radius:12px;border:1px solid var(--evo-border, #e2e8f0);margin-bottom:24px;">
            <a href="/pro/rdv?d={{ prev_date }}" style="display:inline-flex;align-items:center;gap:6px;background:var(--evo-bg-elevated, #ffffff);border:1px solid var(--evo-border, #cbd5e1);padding:8px 14px;border-radius:8px;color:var(--evo-text-secondary, #334155);text-decoration:none;font-weight:600;font-size:0.9rem;">
                <i class="fa-solid fa-chevron-left"></i> Jour précédent
            </a>
            <div style="text-align:center;">
                <div style="font-size:1.15rem;font-weight:800;color:var(--evo-text-primary, #0f172a);">
                    <i class="fa-regular fa-calendar"></i> {{ d|date('d/m/Y') }}
                </div>
                {% if d == 'now'|date('Y-m-d') %}
                <span style="font-size:0.8rem;color:#059669;font-weight:700;">(Aujourd'hui)</span>
                {% else %}
                <a href="/pro/rdv?d={{ 'now'|date('Y-m-d') }}" style="font-size:0.8rem;color:#2563eb;font-weight:600;text-decoration:underline;">Revenir à aujourd'hui</a>
                {% endif %}
            </div>
            <a href="/pro/rdv?d={{ next_date }}" style="display:inline-flex;align-items:center;gap:6px;background:var(--evo-bg-elevated, #ffffff);border:1px solid var(--evo-border, #cbd5e1);padding:8px 14px;border-radius:8px;color:var(--evo-text-secondary, #334155);text-decoration:none;font-weight:600;font-size:0.9rem;">
                Jour suivant <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>

        <!-- Liste des rendez-vous -->
        {% if liste|length > 0 %}
        <div style="display:flex;flex-direction:column;gap:14px;">
            {% for rdv in liste %}
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:18px 20px;border-radius:12px;background:var(--evo-bg-elevated, #ffffff);border:1px solid {% if rdv.statut == 'confirme' %}#a7f3d0;box-shadow:0 2px 6px rgba(16,185,129,0.08){% elseif rdv.statut == 'attente' %}#fde68a;background:#fffbeb{% else %}#e2e8f0{% endif %};">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                        <span style="font-size:1.15rem;font-weight:800;color:var(--evo-text-primary, #0f172a);">
                            {{ rdv.debut|date('H:i') }} - {{ rdv.fin|date('H:i') }}
                        </span>
                        {% if rdv.statut == 'confirme' %}
                        <span style="background:#ecfdf5;color:#059669;font-size:0.75rem;font-weight:700;padding:3px 8px;border-radius:6px;border:1px solid #a7f3d0;">
                            <i class="fa-solid fa-circle-check"></i> Confirmé
                        </span>
                        {% elseif rdv.statut == 'attente' %}
                        <span style="background:#fef3c7;color:#d97706;font-size:0.75rem;font-weight:700;padding:3px 8px;border-radius:6px;border:1px solid #fde68a;">
                            <i class="fa-solid fa-clock"></i> En attente de validation
                        </span>
                        {% endif %}
                        {% if rdv.collab_nom %}
                        <span style="background:var(--evo-bg-tertiary, #f1f5f9);color:var(--evo-text-secondary, #475569);font-size:0.75rem;font-weight:600;padding:3px 8px;border-radius:6px;">
                            <i class="fa-solid fa-user"></i> {{ rdv.collab_nom }}
                        </span>
                        {% endif %}
                    </div>
                    <div style="font-weight:700;color:var(--evo-text-primary, #1e293b);font-size:1.05rem;margin-bottom:4px;">
                        {{ rdv.nom }}
                    </div>
                    <div style="color:var(--evo-text-muted, #64748b);font-size:0.9rem;display:flex;flex-wrap:wrap;gap:14px;">
                        <span><i class="fa-solid fa-scissors"></i> {{ rdv.presta_nom }}</span>
                        <span><a href="tel:{{ rdv.tel }}" style="color:#0f766e;text-decoration:none;"><i class="fa-solid fa-phone"></i> {{ rdv.tel }}</a></span>
                        <span><i class="fa-solid fa-envelope"></i> {{ rdv.email }}</span>
                    </div>
                </div>

                <!-- Actions sur le rendez-vous -->
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <!-- Bouton SMS Magique de rappel -->
                    {% if rdv.sms_url %}
                    <a href="{{ rdv.sms_url }}" class="btn" style="display:inline-flex;align-items:center;gap:6px;background:#3b82f6;color:#fff;padding:8px 12px;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.85rem;" title="Envoyer un rappel par SMS en 1 clic">
                        <i class="fa-solid fa-comment-sms"></i> SMS Rappel
                    </a>
                    {% endif %}

                    {% if rdv.statut == 'attente' %}
                    <form action="/pro/rdv/action" method="post" style="display:inline;">
                        <input type="hidden" name="id" value="{{ rdv.id }}">
                        <input type="hidden" name="act" value="confirme">
                        <input type="hidden" name="d" value="{{ d }}">
                        <button type="submit" style="display:inline-flex;align-items:center;gap:6px;background:#10b981;color:#fff;border:none;padding:8px 12px;border-radius:8px;font-weight:600;font-size:0.85rem;cursor:pointer;">
                            <i class="fa-solid fa-check"></i> Valider
                        </button>
                    </form>
                    {% endif %}

                    <form action="/pro/rdv/action" method="post" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?');">
                        <input type="hidden" name="id" value="{{ rdv.id }}">
                        <input type="hidden" name="act" value="annule">
                        <input type="hidden" name="d" value="{{ d }}">
                        <button type="submit" style="display:inline-flex;align-items:center;gap:6px;background:#fee2e2;color:#b91c1c;border:none;padding:8px 12px;border-radius:8px;font-weight:600;font-size:0.85rem;cursor:pointer;">
                            <i class="fa-solid fa-xmark"></i> Annuler
                        </button>
                    </form>
                </div>
            </div>
            {% endfor %}
        </div>
        {% else %}
        <div style="text-align:center;padding:48px 20px;background:var(--evo-bg-tertiary, #f8fafc);border-radius:12px;border:1px dashed #cbd5e1;">
            <i class="fa-regular fa-calendar-check" style="font-size:2.5rem;color:#94a3b8;margin-bottom:12px;display:block;"></i>
            <h3 style="font-size:1.15rem;font-weight:700;color:var(--evo-text-secondary, #334155);margin:0 0 6px;">Aucun rendez-vous pour ce jour</h3>
            <p style="color:var(--evo-text-muted, #64748b);font-size:0.9rem;margin:0;">Vos créneaux libres restent ouverts à la réservation en ligne.</p>
        </div>
        {% endif %}
    </div>
</div>
