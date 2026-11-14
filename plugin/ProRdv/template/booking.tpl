<div class="prordv-booking-wrapper" style="max-width:680px;margin:30px auto;padding:0 16px;font-family:system-ui,-apple-system,sans-serif;">
    <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);padding:30px;border:1px solid var(--evo-border, #e2e8f0);">
        <div style="text-align:center;margin-bottom:24px;">
            <span style="display:inline-block;background:#ecfdf5;color:#059669;font-weight:700;font-size:0.85rem;padding:6px 14px;border-radius:20px;margin-bottom:10px;">
                <i class="fa-solid fa-calendar-check"></i> Prise de Rendez-vous en Ligne
            </span>
            <h1 style="font-size:1.75rem;font-weight:800;color:var(--evo-text-primary, #0f172a);margin:0 0 6px;">{{ commerce.nom }}</h1>
            <p style="color:var(--evo-text-muted, #64748b);font-size:0.95rem;margin:0;">Alternative locale &amp; souveraine berruyère</p>
        </div>

        {% if success %}
        <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;">
            <i class="fa-solid fa-envelope-circle-check" style="font-size:2.5rem;color:#059669;margin-bottom:12px;display:block;"></i>
            <h2 style="font-size:1.25rem;font-weight:700;color:#065f46;margin:0 0 8px;">Dernière étape : Confirmez votre demande</h2>
            <p style="color:#047857;margin:0 0 14px;font-size:0.95rem;">
                Un email de validation vient de vous être envoyé. 
                Veuillez cliquer sur le lien reçu dans les <strong>{{ resData.hold_minutes|default(15) }} minutes</strong> pour bloquer votre créneau du <strong>{{ resData.quand }}</strong>.
            </p>
            {% if cfg.mode_validation == 'manuel' %}
            <div style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;padding:10px 14px;border-radius:8px;font-size:0.88rem;margin-bottom:14px;text-align:left;">
                <i class="fa-solid fa-clock"></i> <strong>Validation sur demande :</strong> Ce professionnel valide manuellement ses interventions. Dès votre confirmation email effectuée, il vérifiera sa disponibilité et vous confirmera définitivement le rendez-vous.
            </div>
            {% endif %}
            <p style="color:var(--evo-text-muted, #64748b);font-size:0.85rem;margin:0;">Pensez à vérifier votre dossier de courriers indésirables (spams) si vous ne le recevez pas d'ici quelques instants.</p>
        </div>
        <div style="text-align:center;">
            <a href="/rdv/{{ commerce.slug }}" class="btn" style="display:inline-block;background:#059669;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;">Réserver un autre créneau</a>
        </div>
        {% else %}

        {% if error %}
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:14px 18px;border-radius:10px;margin-bottom:20px;font-size:0.95rem;font-weight:600;">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ error }}
        </div>
        {% endif %}

        {% if cfg.notice %}
        <div style="background:#eff6ff;border-left:4px solid #3b82f6;padding:12px 16px;border-radius:8px;margin-bottom:20px;color:#1e40af;font-size:0.9rem;">
            <i class="fa-solid fa-circle-info"></i> <strong>Consigne du professionnel :</strong> {{ cfg.notice }}
        </div>
        {% endif %}

        <form action="/rdv/reserver" method="post" id="prordv-form">
            <input type="hidden" name="commerce_id" value="{{ commerce.id }}">
            <!-- Honeypot anti-bot -->
            <input type="text" name="website_hp" style="display:none!important;" tabindex="-1" autocomplete="off">

            <!-- Étape 1 : Choix de la Prestation -->
            <div style="margin-bottom:24px;">
                <label style="display:block;font-weight:700;color:var(--evo-text-primary, #1e293b);margin-bottom:8px;font-size:1rem;">
                    1. Choisissez votre prestation
                </label>
                <div style="display:grid;grid-template-columns:1fr;gap:10px;">
                    {% for p in prestas %}
                    <label style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border:2px solid {% if selectedPresta.id == p.id %}#10b981;background:#f0fdf4{% else %}#e2e8f0;background:#fff{% endif %};border-radius:10px;cursor:pointer;transition:all 0.15s ease;">
                        <span style="display:flex;align-items:center;gap:12px;">
                            <input type="radio" name="presta_id" value="{{ p.id }}" {% if selectedPresta.id == p.id %}checked{% endif %} onchange="window.location.href='/rdv/{{ commerce.slug }}?p=' + this.value + '&d={{ selectedDate }}'">
                            <div>
                                <strong style="color:var(--evo-text-primary, #1e293b);font-size:0.95rem;display:block;">{{ p.nom }}</strong>
                                {% if p.description %}
                                <span style="font-size:0.8rem;color:var(--evo-text-muted, #64748b);">{{ p.description }}</span>
                                {% endif %}
                            </div>
                        </span>
                        <span style="display:flex;align-items:center;gap:8px;">
                            <span style="background:var(--evo-bg-tertiary, #f1f5f9);color:var(--evo-text-secondary, #475569);font-size:0.8rem;padding:4px 8px;border-radius:6px;font-weight:600;">
                                <i class="fa-regular fa-clock"></i> {{ p.duree_min }} min
                            </span>
                            {% if p.prix %}
                            <span style="font-weight:700;color:#059669;font-size:1rem;">{{ p.prix }} €</span>
                            {% else %}
                            <span style="color:#94a3b8;font-size:0.85rem;">Devis</span>
                            {% endif %}
                        </span>
                    </label>
                    {% endfor %}
                </div>
            </div>

            <!-- Étape 2 : Choix du Collaborateur (si activé) -->
            {% if collabs|length > 0 %}
            <div style="margin-bottom:24px;">
                <label style="display:block;font-weight:700;color:var(--evo-text-primary, #1e293b);margin-bottom:8px;font-size:1rem;">
                    2. Intervenant / Collaborateur (facultatif)
                </label>
                <select name="collab_id" style="width:100%;padding:12px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.95rem;background:var(--evo-bg-elevated, #ffffff);" onchange="window.location.href='/rdv/{{ commerce.slug }}?p={{ selectedPresta.id }}&d={{ selectedDate }}&k=' + this.value">
                    <option value="">Sans préférence (premier disponible)</option>
                    {% for c in collabs %}
                    <option value="{{ c.id }}" {% if selectedCollabId == c.id %}selected{% endif %}>{{ c.nom }}</option>
                    {% endfor %}
                </select>
            </div>
            {% endif %}

            <!-- Étape 3 : Date et Sélection du Créneau -->
            <div style="margin-bottom:24px;">
                <label style="display:block;font-weight:700;color:var(--evo-text-primary, #1e293b);margin-bottom:8px;font-size:1rem;">
                    3. Date et heure du rendez-vous
                </label>
                <div style="margin-bottom:12px;">
                    <input type="date" name="date" value="{{ selectedDate }}" min="{{ 'now'|date('Y-m-d') }}" style="width:100%;padding:12px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.95rem;font-weight:600;" onchange="window.location.href='/rdv/{{ commerce.slug }}?p={{ selectedPresta.id }}&k={{ selectedCollabId }}&d=' + this.value">
                </div>

                <div style="background:var(--evo-bg-tertiary, #f8fafc);border-radius:12px;padding:16px;border:1px solid var(--evo-border, #e2e8f0);">
                    <div style="font-size:0.85rem;font-weight:600;color:var(--evo-text-muted, #64748b);margin-bottom:10px;">
                        Créneaux disponibles pour le {{ selectedDate|date('d/m/Y') }} :
                    </div>
                    {% if slots|length > 0 %}
                    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(75px, 1fr));gap:8px;">
                        {% for s in slots %}
                        <label style="display:block;text-align:center;">
                            <input type="radio" name="heure" value="{{ s }}" required style="display:none;" id="slot-{{ s }}">
                            <span class="slot-pill" onclick="document.querySelectorAll('.slot-pill').forEach(el=>el.classList.remove('selected'));this.classList.add('selected');" style="display:block;padding:10px 4px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;background:var(--evo-bg-elevated, #ffffff);color:var(--evo-text-primary, #0f172a);font-weight:700;font-size:0.9rem;cursor:pointer;transition:all 0.15s ease;">
                                {{ s }}
                            </span>
                        </label>
                        {% endfor %}
                    </div>
                    {% else %}
                    <p style="color:#94a3b8;font-size:0.9rem;margin:8px 0;text-align:center;">
                        <i class="fa-regular fa-calendar-xmark"></i> Aucun créneau libre ce jour. Choisissez une autre date.
                    </p>
                    {% endif %}
                </div>
            </div>

            <!-- Étape 4 : Coordonnées du Client & Champs Métiers -->
            <div style="margin-bottom:24px;">
                <label style="display:block;font-weight:700;color:var(--evo-text-primary, #1e293b);margin-bottom:8px;font-size:1rem;">
                    4. Vos coordonnées &amp; informations
                </label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <input type="text" name="nom" placeholder="Votre nom complet *" required style="width:100%;padding:12px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.95rem;">
                    </div>
                    <div>
                        <input type="tel" name="tel" placeholder="N° de téléphone (SMS) *" required style="width:100%;padding:12px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.95rem;">
                    </div>
                </div>
                <div style="margin-bottom:12px;">
                    <input type="email" name="email" placeholder="Adresse email pour la confirmation *" required style="width:100%;padding:12px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.95rem;">
                </div>

                <!-- Champs Personnalisés selon le Profil Métier -->
                {% if cfg.champs_requis.adresse %}
                <div style="margin-bottom:12px;">
                    <input type="text" name="adresse" placeholder="Adresse complète de l'intervention (Rue, CP, Ville) *" required style="width:100%;padding:12px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.95rem;">
                </div>
                {% endif %}

                {% if cfg.champs_requis.marque_modele %}
                <div style="margin-bottom:12px;">
                    <input type="text" name="marque_modele" placeholder="Marque et modèle de l'appareil / véhicule (ex: Renault Clio, iPhone 14...) *" required style="width:100%;padding:12px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.95rem;">
                </div>
                {% endif %}

                {% if cfg.champs_requis.lieu_rdv %}
                <div style="margin-bottom:12px;">
                    <select name="lieu_rdv" style="width:100%;padding:12px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.95rem;background:var(--evo-bg-elevated, #ffffff);">
                        <option value="Au bureau / En agence">Au bureau / En agence</option>
                        <option value="À mon domicile">À mon domicile</option>
                        <option value="Par visio / téléphone">Par téléphone / visio</option>
                    </select>
                </div>
                {% endif %}

                {% if cfg.champs_requis.objet %}
                <div style="margin-bottom:12px;">
                    <input type="text" name="objet" placeholder="Motif ou objet de l'échange *" required style="width:100%;padding:12px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.95rem;">
                </div>
                {% endif %}

                {% if cfg.champs_requis.details %}
                <div style="margin-bottom:12px;">
                    <textarea name="details" placeholder="Description des travaux souhaités ou du problème rencontré..." rows="2" style="width:100%;padding:10px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.9rem;resize:vertical;"></textarea>
                </div>
                {% endif %}

                {% if cfg.champs_requis.remarque or not cfg.champs_requis %}
                <div>
                    <textarea name="remarque" placeholder="Remarque ou précision particulière pour le commerçant (facultatif)" rows="2" style="width:100%;padding:10px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.9rem;resize:vertical;"></textarea>
                </div>
                {% endif %}
            </div>

            <button type="submit" style="width:100%;padding:14px;background:#10b981;color:#fff;border:none;border-radius:10px;font-size:1.05rem;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(16,185,129,0.3);transition:background 0.2s ease;">
                <i class="fa-solid fa-check"></i> Valider et Réserver ce Créneau
            </button>
        </form>
        {% endif %}
    </div>
</div>

<style>
.slot-pill.selected {
    background: #10b981 !important;
    color: #ffffff !important;
    border-color: #059669 !important;
    box-shadow: 0 2px 6px rgba(16,185,129,0.4);
}
.slot-pill:hover:not(.selected) {
    background: var(--evo-bg-tertiary, #f1f5f9);
    border-color: #94a3b8;
}
</style>
