<div class="prordv-config-wrapper" style="max-width:1050px;margin:30px auto;padding:0 16px;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">

    <!-- En-tête Espace Pro Configuration -->
    <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.06);padding:24px 28px;border:1px solid var(--evo-border, #e2e8f0);margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                    <span style="display:inline-flex;align-items:center;gap:5px;background:#ecfdf5;color:#059669;font-weight:700;font-size:0.8rem;padding:4px 10px;border-radius:12px;">
                        <i class="fa-solid fa-calendar-check"></i> Prise de Rendez-vous Pro
                    </span>
                    {% if commerce.is_active %}
                    <span style="display:inline-block;background:#dcfce7;color:#15803d;font-weight:700;font-size:0.75rem;padding:3px 8px;border-radius:10px;">
                        ● Module Actif
                    </span>
                    {% else %}
                    <span style="display:inline-block;background:#fee2e2;color:#b91c1c;font-weight:700;font-size:0.75rem;padding:3px 8px;border-radius:10px;">
                        ○ Module en pause
                    </span>
                    {% endif %}
                </div>
                <h1 style="font-size:1.65rem;font-weight:800;color:var(--evo-text-primary, #0f172a);margin:0;">
                    Configuration : {{ commerce.nom }}
                </h1>
                <p style="color:var(--evo-text-muted, #64748b);font-size:0.9rem;margin:4px 0 0 0;">
                    Personnalisez votre parcours client, vos prestations, votre équipe et votre grille horaire.
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                {% if commerce.is_active %}
                <a href="/rdv/{{ commerce.slug }}" target="_blank" class="btn" style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;padding:9px 14px;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.88rem;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Tester la vue client
                </a>
                {% endif %}
                <a href="/pro/rdv?commerce_id={{ commerce.id }}" class="btn" style="display:inline-flex;align-items:center;gap:6px;background:#0f766e;color:#fff;padding:9px 16px;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.88rem;box-shadow:0 2px 6px rgba(15,118,110,0.25);">
                    <i class="fa-solid fa-calendar-days"></i> Mon Agenda Pro
                </a>
            </div>
        </div>

        {% if msg %}
        <div style="margin-top:20px;background:{% if msg_type == 'danger' %}#fef2f2;border-color:#fecaca;color:#b91c1c;{% else %}#ecfdf5;border-color:#a7f3d0;color:#065f46;{% endif %}border-width:1px;border-style:solid;padding:12px 18px;border-radius:10px;font-weight:600;font-size:0.95rem;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid {% if msg_type == 'danger' %}fa-triangle-exclamation{% else %}fa-circle-check{% endif %}"></i>
            <span>{{ msg }}</span>
        </div>
        {% endif %}
    </div>

    <!-- Navigation des 5 Onglets -->
    <div style="display:flex;gap:8px;border-bottom:2px solid var(--evo-border, #e2e8f0);margin-bottom:24px;overflow-x:auto;padding-bottom:2px;">
        <button type="button" onclick="switchConfigTab('prestations')" id="tab-btn-prestations" class="config-tab-btn" style="padding:10px 18px;border:none;background:none;font-size:0.95rem;font-weight:700;color:var(--evo-text-muted, #64748b);cursor:pointer;border-bottom:3px solid transparent;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-scissors"></i> 1. Prestations &amp; Tarifs
        </button>
        <button type="button" onclick="switchConfigTab('presets')" id="tab-btn-presets" class="config-tab-btn" style="padding:10px 18px;border:none;background:none;font-size:0.95rem;font-weight:700;color:var(--evo-text-muted, #64748b);cursor:pointer;border-bottom:3px solid transparent;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-wand-magic-sparkles"></i> 2. Profils Métiers
        </button>
        <button type="button" onclick="switchConfigTab('rules')" id="tab-btn-rules" class="config-tab-btn" style="padding:10px 18px;border:none;background:none;font-size:0.95rem;font-weight:700;color:var(--evo-text-muted, #64748b);cursor:pointer;border-bottom:3px solid transparent;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-sliders"></i> 3. Règles &amp; Validation
        </button>
        <button type="button" onclick="switchConfigTab('equipe')" id="tab-btn-equipe" class="config-tab-btn" style="padding:10px 18px;border:none;background:none;font-size:0.95rem;font-weight:700;color:var(--evo-text-muted, #64748b);cursor:pointer;border-bottom:3px solid transparent;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-users"></i> 4. Équipe
        </button>
        <button type="button" onclick="switchConfigTab('horaires')" id="tab-btn-horaires" class="config-tab-btn" style="padding:10px 18px;border:none;background:none;font-size:0.95rem;font-weight:700;color:var(--evo-text-muted, #64748b);cursor:pointer;border-bottom:3px solid transparent;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-clock"></i> 5. Horaires Hebdo
        </button>
    </div>

    <!-- ========================================== -->
    <!-- ONGLET 1 : Prestations & Tarifs (CRUD complet) -->
    <!-- ========================================== -->
    <div id="tab-pane-prestations" class="config-tab-pane" style="display:none;">
        <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.06);padding:26px;border:1px solid var(--evo-border, #e2e8f0);margin-bottom:24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
                <div>
                    <h2 style="font-size:1.25rem;font-weight:800;color:var(--evo-text-primary, #1e293b);margin:0 0 4px;">
                        Catalogue de vos prestations
                    </h2>
                    <p style="color:var(--evo-text-muted, #64748b);font-size:0.88rem;margin:0;">
                        Ajoutez, renommez, modifiez le tarif ou la durée de chaque prestation proposée à la réservation.
                    </p>
                </div>
            </div>

            <!-- Liste des prestations existantes -->
            {% if prestas|length > 0 %}
            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:28px;">
                {% for p in prestas %}
                <div style="background:{% if p.is_active %}#ffffff{% else %}#f8fafc{% endif %};border:1px solid {% if p.is_active %}#cbd5e1{% else %}#e2e8f0{% endif %};border-radius:10px;padding:16px 20px;transition:all 0.15s ease;">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                        <div style="flex:1;min-width:240px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <strong style="font-size:1.05rem;color:{% if p.is_active %}#0f172a{% else %}#94a3b8{% endif %};">
                                    {{ p.nom }}
                                </strong>
                                {% if not p.is_active %}
                                <span style="background:#fee2e2;color:#b91c1c;font-size:0.75rem;padding:2px 8px;border-radius:10px;font-weight:700;">Masquée</span>
                                {% endif %}
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;margin-top:6px;font-size:0.88rem;color:var(--evo-text-muted, #64748b);">
                                <span><i class="fa-regular fa-clock"></i> {{ p.duree_min }} min</span>
                                <span>•</span>
                                {% if p.prix %}
                                <strong style="color:#059669;font-size:0.95rem;">{{ p.prix }} €</strong>
                                {% else %}
                                <span>Gratuit / Sur devis</span>
                                {% endif %}
                            </div>
                            {% if p.description %}
                            <p style="margin:6px 0 0 0;font-size:0.85rem;color:var(--evo-text-muted, #64748b);font-style:italic;">
                                {{ p.description }}
                            </p>
                            {% endif %}
                        </div>

                        <!-- Actions Prestation -->
                        <div style="display:flex;align-items:center;gap:8px;">
                            <!-- Bouton Déplier Modification -->
                            <button type="button" onclick="toggleEditPresta({{ p.id }})" style="background:var(--evo-bg-tertiary, #f1f5f9);color:var(--evo-text-secondary, #334155);border:1px solid var(--evo-border, #cbd5e1);padding:7px 12px;border-radius:6px;font-size:0.85rem;font-weight:600;cursor:pointer;">
                                <i class="fa-solid fa-pen"></i> Modifier
                            </button>

                            <!-- Toggle Actif / Inactif -->
                            <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="margin:0;">
                                <input type="hidden" name="subaction" value="presta_toggle">
                                <input type="hidden" name="active_tab" value="prestations">
                                <input type="hidden" name="presta_id" value="{{ p.id }}">
                                <button type="submit" style="background:var(--evo-bg-elevated, #ffffff);border:1px solid var(--evo-border, #cbd5e1);color:{% if p.is_active %}#d97706{% else %}#059669{% endif %};padding:7px 12px;border-radius:6px;font-size:0.85rem;font-weight:600;cursor:pointer;" title="{% if p.is_active %}Désactiver temporairement{% else %}Réactiver{% endif %}">
                                    <i class="fa-solid {% if p.is_active %}fa-eye-slash{% else %}fa-eye{% endif %}"></i>
                                    {% if p.is_active %}Masquer{% else %}Activer{% endif %}
                                </button>
                            </form>

                            <!-- Supprimer Prestation -->
                            <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="margin:0;" onsubmit="return confirm('Confirmez-vous la suppression définitive de cette prestation ?');">
                                <input type="hidden" name="subaction" value="presta_delete">
                                <input type="hidden" name="active_tab" value="prestations">
                                <input type="hidden" name="presta_id" value="{{ p.id }}">
                                <button type="submit" style="background:var(--evo-bg-elevated, #ffffff);border:1px solid #fecaca;color:#dc2626;padding:7px 10px;border-radius:6px;font-size:0.85rem;cursor:pointer;" title="Supprimer">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Formulaire d'édition masqué par défaut -->
                    <div id="edit-presta-{{ p.id }}" style="display:none;margin-top:16px;padding-top:16px;border-top:1px dashed #cbd5e1;">
                        <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;align-items:end;">
                            <input type="hidden" name="subaction" value="presta_edit">
                            <input type="hidden" name="active_tab" value="prestations">
                            <input type="hidden" name="presta_id" value="{{ p.id }}">
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--evo-text-secondary, #475569);margin-bottom:4px;">Nom de la prestation</label>
                                <input type="text" name="presta_nom" value="{{ p.nom }}" required style="width:100%;padding:8px 10px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--evo-text-secondary, #475569);margin-bottom:4px;">Durée (minutes)</label>
                                <input type="number" name="presta_duree" value="{{ p.duree_min }}" min="5" step="5" required style="width:100%;padding:8px 10px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--evo-text-secondary, #475569);margin-bottom:4px;">Prix (€)</label>
                                <input type="number" name="presta_prix" value="{{ p.prix }}" step="0.5" placeholder="Gratuit" style="width:100%;padding:8px 10px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;">
                            </div>
                            <div style="grid-column:1 / -1;">
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--evo-text-secondary, #475569);margin-bottom:4px;">Description / Précisions</label>
                                <input type="text" name="presta_desc" value="{{ p.description }}" placeholder="Détails visibles par le client lors du choix..." style="width:100%;padding:8px 10px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;">
                            </div>
                            <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;gap:8px;">
                                <button type="button" onclick="toggleEditPresta({{ p.id }})" style="background:#e2e8f0;color:var(--evo-text-secondary, #475569);border:none;padding:8px 14px;border-radius:6px;font-size:0.85rem;cursor:pointer;">Annuler</button>
                                <button type="submit" style="background:#10b981;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-weight:600;font-size:0.85rem;cursor:pointer;">Enregistrer les modifications</button>
                            </div>
                        </form>
                    </div>
                </div>
                {% endfor %}
            </div>
            {% else %}
            <div style="text-align:center;padding:30px;background:var(--evo-bg-tertiary, #f8fafc);border-radius:10px;border:1px dashed #cbd5e1;margin-bottom:24px;color:var(--evo-text-muted, #64748b);">
                <i class="fa-solid fa-scissors" style="font-size:2rem;color:#cbd5e1;margin-bottom:10px;display:block;"></i>
                Aucune prestation enregistrée pour le moment. Ajoutez votre premier soin ou service ci-dessous.
            </div>
            {% endif %}

            <!-- Formulaire d'ajout rapide -->
            <div style="background:var(--evo-bg-tertiary, #f8fafc);border:1px solid var(--evo-border, #e2e8f0);border-radius:12px;padding:20px;">
                <h3 style="margin-top:0;margin-bottom:12px;font-size:1.05rem;font-weight:700;color:var(--evo-text-primary, #1e293b);">
                    <i class="fa-solid fa-circle-plus" style="color:#0f766e;"></i> Ajouter une nouvelle prestation
                </h3>
                <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;align-items:end;">
                    <input type="hidden" name="subaction" value="presta_add">
                    <input type="hidden" name="active_tab" value="prestations">
                    <div>
                        <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--evo-text-secondary, #475569);margin-bottom:4px;">Nom de la prestation *</label>
                        <input type="text" name="presta_nom" placeholder="Ex: Coupe &amp; Coiffage, Dépannage 1h, Devis..." required style="width:100%;padding:10px 12px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--evo-text-secondary, #475569);margin-bottom:4px;">Durée estimée *</label>
                        <input type="number" name="presta_duree" value="30" min="5" step="5" required style="width:100%;padding:10px 12px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--evo-text-secondary, #475569);margin-bottom:4px;">Tarif (€)</label>
                        <input type="number" name="presta_prix" placeholder="Gratuit / devis" step="0.5" style="width:100%;padding:10px 12px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;">
                    </div>
                    <div style="grid-column:1 / 3;">
                        <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--evo-text-secondary, #475569);margin-bottom:4px;">Description (facultative)</label>
                        <input type="text" name="presta_desc" placeholder="Conseils, ce qui est inclus..." style="width:100%;padding:10px 12px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;">
                    </div>
                    <div>
                        <button type="submit" style="width:100%;background:#0f766e;color:#fff;border:none;padding:10px 14px;border-radius:6px;font-weight:700;font-size:0.9rem;cursor:pointer;">
                            + Ajouter au catalogue
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ONGLET 2 : Profils Métiers (Presets)       -->
    <!-- ========================================== -->
    <div id="tab-pane-presets" class="config-tab-pane" style="display:none;">
        <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.06);padding:26px;border:1px solid var(--evo-border, #e2e8f0);margin-bottom:24px;">
            <div style="margin-bottom:20px;">
                <h2 style="font-size:1.25rem;font-weight:800;color:var(--evo-text-primary, #1e293b);margin:0 0 4px;">
                    Modèles &amp; Profils Métiers Pré-configurés
                </h2>
                <p style="color:var(--evo-text-muted, #64748b);font-size:0.9rem;margin:0;">
                    Sélectionnez votre type d'activité pour pré-remplir instantanément les durées, règles de validation et champs demandés au client.
                </p>
                <div style="background:#eff6ff;border-left:4px solid #3b82f6;padding:10px 14px;border-radius:6px;margin-top:12px;color:#1e40af;font-size:0.85rem;">
                    <i class="fa-solid fa-shield-halved"></i> <strong>Souveraineté &amp; Sécurité :</strong> 299koEVO dessert tous les commerçants et artisans locaux. Les professions de santé sont strictement exclues de ce module pour préserver le secret médical et respecter les normes RGPD/HDS.
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:20px;">
                <!-- Preset Beauté -->
                <div style="background:#fdf2f8;border:2px solid {% if commerce.type == 'beaute' %}#ec4899{% else %}#fbcfe8{% endif %};border-radius:12px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;">
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;background:#fce7f3;color:#db2777;border-radius:10px;font-size:1.2rem;">
                                <i class="fa-solid fa-scissors"></i>
                            </span>
                            {% if commerce.type == 'beaute' %}
                            <span style="background:#ec4899;color:#fff;font-size:0.75rem;padding:3px 8px;border-radius:10px;font-weight:700;">Actif</span>
                            {% endif %}
                        </div>
                        <h3 style="margin:0 0 6px;color:#831843;font-size:1.1rem;font-weight:700;">Beauté &amp; Bien-être</h3>
                        <p style="margin:0 0 12px;color:#9d174d;font-size:0.83rem;">Coiffure, barbier, esthétique, onglerie, massage, spa.</p>
                        <ul style="margin:0 0 16px 0;padding-left:18px;font-size:0.8rem;color:#701a75;line-height:1.5;">
                            <li>Validation instantanée automatique</li>
                            <li>Choix du collaborateur activé</li>
                            <li>Pas de 15 minutes</li>
                        </ul>
                    </div>
                    <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="margin:0;" onsubmit="return confirm('Appliquer le preset Beauté & Bien-être ?');">
                        <input type="hidden" name="subaction" value="apply_preset">
                        <input type="hidden" name="preset_key" value="beaute">
                        <input type="hidden" name="active_tab" value="presets">
                        <button type="submit" style="width:100%;padding:8px 12px;background:#db2777;color:#fff;border:none;border-radius:6px;font-weight:600;font-size:0.85rem;cursor:pointer;">
                            Appliquer ce profil
                        </button>
                    </form>
                </div>

                <!-- Preset Artisan -->
                <div style="background:#fffbeb;border:2px solid {% if commerce.type == 'artisan' %}#d97706{% else %}#fde68a{% endif %};border-radius:12px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;">
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;background:#fef3c7;color:#b45309;border-radius:10px;font-size:1.2rem;">
                                <i class="fa-solid fa-wrench"></i>
                            </span>
                            {% if commerce.type == 'artisan' %}
                            <span style="background:#d97706;color:#fff;font-size:0.75rem;padding:3px 8px;border-radius:10px;font-weight:700;">Actif</span>
                            {% endif %}
                        </div>
                        <h3 style="margin:0 0 6px;color:#92400e;font-size:1.1rem;font-weight:700;">Artisan &amp; Chantier</h3>
                        <p style="margin:0 0 12px;color:#b45309;font-size:0.83rem;">Plombier, électricien, serrurier, menuisier, paysagiste.</p>
                        <ul style="margin:0 0 16px 0;padding-left:18px;font-size:0.8rem;color:#78350f;line-height:1.5;">
                            <li>Validation manuelle sur devis</li>
                            <li>Adresse d'intervention obligatoire</li>
                            <li>Description des travaux requis</li>
                        </ul>
                    </div>
                    <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="margin:0;" onsubmit="return confirm('Appliquer le preset Artisan & Chantier ?');">
                        <input type="hidden" name="subaction" value="apply_preset">
                        <input type="hidden" name="preset_key" value="artisan">
                        <input type="hidden" name="active_tab" value="presets">
                        <button type="submit" style="width:100%;padding:8px 12px;background:#d97706;color:#fff;border:none;border-radius:6px;font-weight:600;font-size:0.85rem;cursor:pointer;">
                            Appliquer ce profil
                        </button>
                    </form>
                </div>

                <!-- Preset Réparation -->
                <div style="background:#eff6ff;border:2px solid {% if commerce.type == 'reparation' %}#2563eb{% else %}#bfdbfe{% endif %};border-radius:12px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;">
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;background:#dbeafe;color:#1d4ed8;border-radius:10px;font-size:1.2rem;">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                            </span>
                            {% if commerce.type == 'reparation' %}
                            <span style="background:#2563eb;color:#fff;font-size:0.75rem;padding:3px 8px;border-radius:10px;font-weight:700;">Actif</span>
                            {% endif %}
                        </div>
                        <h3 style="margin:0 0 6px;color:#1e40af;font-size:1.1rem;font-weight:700;">Atelier &amp; Réparation</h3>
                        <p style="margin:0 0 12px;color:#1d4ed8;font-size:0.83rem;">Garage auto, moto, vélo, téléphonie, informatique.</p>
                        <ul style="margin:0 0 16px 0;padding-left:18px;font-size:0.8rem;color:#1e3a8a;line-height:1.5;">
                            <li>Validation manuelle ou auto</li>
                            <li>Marque et modèle demandés</li>
                            <li>Description de la panne</li>
                        </ul>
                    </div>
                    <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="margin:0;" onsubmit="return confirm('Appliquer le preset Atelier & Réparation ?');">
                        <input type="hidden" name="subaction" value="apply_preset">
                        <input type="hidden" name="preset_key" value="reparation">
                        <input type="hidden" name="active_tab" value="presets">
                        <button type="submit" style="width:100%;padding:8px 12px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-weight:600;font-size:0.85rem;cursor:pointer;">
                            Appliquer ce profil
                        </button>
                    </form>
                </div>

                <!-- Preset Conseil -->
                <div style="background:#ecfdf5;border:2px solid {% if commerce.type == 'conseil' %}#059669{% else %}#a7f3d0{% endif %};border-radius:12px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;">
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;background:#d1fae5;color:#047857;border-radius:10px;font-size:1.2rem;">
                                <i class="fa-solid fa-briefcase"></i>
                            </span>
                            {% if commerce.type == 'conseil' %}
                            <span style="background:#059669;color:#fff;font-size:0.75rem;padding:3px 8px;border-radius:10px;font-weight:700;">Actif</span>
                            {% endif %}
                        </div>
                        <h3 style="margin:0 0 6px;color:#065f46;font-size:1.1rem;font-weight:700;">Conseil &amp; Services</h3>
                        <p style="margin:0 0 12px;color:#047857;font-size:0.83rem;">Agence immo, expert, photographe, coach, tatoueur.</p>
                        <ul style="margin:0 0 16px 0;padding-left:18px;font-size:0.8rem;color:#064e3b;line-height:1.5;">
                            <li>Validation sur validation pro</li>
                            <li>Lieu au choix (bureau, visio, domicile)</li>
                            <li>Motif et objet de l'échange</li>
                        </ul>
                    </div>
                    <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="margin:0;" onsubmit="return confirm('Appliquer le preset Conseil & Services ?');">
                        <input type="hidden" name="subaction" value="apply_preset">
                        <input type="hidden" name="preset_key" value="conseil">
                        <input type="hidden" name="active_tab" value="presets">
                        <button type="submit" style="width:100%;padding:8px 12px;background:#059669;color:#fff;border:none;border-radius:6px;font-weight:600;font-size:0.85rem;cursor:pointer;">
                            Appliquer ce profil
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ONGLET 3 : Règles & Validation            -->
    <!-- ========================================== -->
    <div id="tab-pane-rules" class="config-tab-pane" style="display:none;">
        <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.06);padding:26px;border:1px solid var(--evo-border, #e2e8f0);margin-bottom:24px;">
            <h2 style="font-size:1.25rem;font-weight:800;color:var(--evo-text-primary, #1e293b);margin:0 0 4px;">
                Règles de réservation &amp; Validation
            </h2>
            <p style="color:var(--evo-text-muted, #64748b);font-size:0.9rem;margin:0 0 20px 0;">
                Ajustez précisément comment vos clients peuvent prendre rendez-vous avec vous.
            </p>

            <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post">
                <input type="hidden" name="subaction" value="save_settings">
                <input type="hidden" name="active_tab" value="rules">
                <input type="hidden" name="rules_form_submitted" value="1">

                <!-- Mode de Validation -->
                <div style="background:var(--evo-bg-tertiary, #f8fafc);border:1px solid var(--evo-border, #e2e8f0);border-radius:12px;padding:20px;margin-bottom:20px;">
                    <label style="display:block;font-size:1rem;font-weight:700;color:var(--evo-text-primary, #0f172a);margin-bottom:10px;">
                        Mode de confirmation des rendez-vous
                    </label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <label style="display:flex;align-items:flex-start;gap:12px;background:var(--evo-bg-elevated, #ffffff);border:2px solid {% if cfg.mode_validation == 'auto' or not cfg.mode_validation %}#10b981{% else %}#e2e8f0{% endif %};padding:14px;border-radius:10px;cursor:pointer;">
                            <input type="radio" name="mode_validation" value="auto" {% if cfg.mode_validation == 'auto' or not cfg.mode_validation %}checked{% endif %} style="margin-top:3px;">
                            <div>
                                <strong style="display:block;color:var(--evo-text-primary, #0f172a);font-size:0.95rem;">Confirmation Automatique</strong>
                                <span style="display:block;color:var(--evo-text-muted, #64748b);font-size:0.83rem;margin-top:3px;">
                                    Le créneau est validé dès que le client confirme par email. Recommandé pour la coiffure, l'esthétique et les ateliers rapides.
                                </span>
                            </div>
                        </label>

                        <label style="display:flex;align-items:flex-start;gap:12px;background:var(--evo-bg-elevated, #ffffff);border:2px solid {% if cfg.mode_validation == 'manuel' %}#f59e0b{% else %}#e2e8f0{% endif %};padding:14px;border-radius:10px;cursor:pointer;">
                            <input type="radio" name="mode_validation" value="manuel" {% if cfg.mode_validation == 'manuel' %}checked{% endif %} style="margin-top:3px;">
                            <div>
                                <strong style="display:block;color:var(--evo-text-primary, #0f172a);font-size:0.95rem;">Validation Manuelle (Sur demande)</strong>
                                <span style="display:block;color:var(--evo-text-muted, #64748b);font-size:0.83rem;margin-top:3px;">
                                    Le client pose une option, et vous confirmez ou refusez depuis votre agenda. Recommandé pour artisans, devis et conseils.
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Paramètres temporels -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:20px;">
                    <div style="background:var(--evo-bg-tertiary, #f8fafc);border:1px solid var(--evo-border, #e2e8f0);border-radius:10px;padding:16px;">
                        <label style="display:block;font-size:0.85rem;font-weight:700;color:var(--evo-text-secondary, #334155);margin-bottom:6px;">
                            Pas entre les créneaux
                        </label>
                        <select name="pas_min" style="width:100%;padding:10px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;background:var(--evo-bg-elevated, #ffffff);">
                            <option value="15" {% if cfg.pas_min == 15 %}selected{% endif %}>Toutes les 15 minutes</option>
                            <option value="20" {% if cfg.pas_min == 20 %}selected{% endif %}>Toutes les 20 minutes</option>
                            <option value="30" {% if cfg.pas_min == 30 or not cfg.pas_min %}selected{% endif %}>Toutes les 30 minutes</option>
                            <option value="45" {% if cfg.pas_min == 45 %}selected{% endif %}>Toutes les 45 minutes</option>
                            <option value="60" {% if cfg.pas_min == 60 %}selected{% endif %}>Toutes les heures (60 min)</option>
                        </select>
                    </div>

                    <div style="background:var(--evo-bg-tertiary, #f8fafc);border:1px solid var(--evo-border, #e2e8f0);border-radius:10px;padding:16px;">
                        <label style="display:block;font-size:0.85rem;font-weight:700;color:var(--evo-text-secondary, #334155);margin-bottom:6px;">
                            Délai minimum avant RDV
                        </label>
                        <select name="delai_min_h" style="width:100%;padding:10px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;background:var(--evo-bg-elevated, #ffffff);">
                            <option value="0" {% if cfg.delai_min_h == 0 %}selected{% endif %}>Immédiat (0h)</option>
                            <option value="2" {% if cfg.delai_min_h == 2 or not cfg.delai_min_h %}selected{% endif %}>2 heures avant</option>
                            <option value="4" {% if cfg.delai_min_h == 4 %}selected{% endif %}>4 heures avant</option>
                            <option value="12" {% if cfg.delai_min_h == 12 %}selected{% endif %}>12 heures avant</option>
                            <option value="24" {% if cfg.delai_min_h == 24 %}selected{% endif %}>24 heures avant (J-1)</option>
                            <option value="48" {% if cfg.delai_min_h == 48 %}selected{% endif %}>48 heures avant (J-2)</option>
                        </select>
                    </div>

                    <div style="background:var(--evo-bg-tertiary, #f8fafc);border:1px solid var(--evo-border, #e2e8f0);border-radius:10px;padding:16px;">
                        <label style="display:block;font-size:0.85rem;font-weight:700;color:var(--evo-text-secondary, #334155);margin-bottom:6px;">
                            Horizon maximal
                        </label>
                        <select name="horizon_jours" style="width:100%;padding:10px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;background:var(--evo-bg-elevated, #ffffff);">
                            <option value="15" {% if cfg.horizon_jours == 15 %}selected{% endif %}>15 jours à l'avance</option>
                            <option value="30" {% if cfg.horizon_jours == 30 %}selected{% endif %}>1 mois à l'avance</option>
                            <option value="60" {% if cfg.horizon_jours == 60 or not cfg.horizon_jours %}selected{% endif %}>2 mois à l'avance</option>
                            <option value="90" {% if cfg.horizon_jours == 90 %}selected{% endif %}>3 mois à l'avance</option>
                        </select>
                    </div>
                </div>

                <!-- Champs requis personnalisés -->
                <div style="background:var(--evo-bg-tertiary, #f8fafc);border:1px solid var(--evo-border, #e2e8f0);border-radius:12px;padding:20px;margin-bottom:20px;">
                    <label style="display:block;font-size:1rem;font-weight:700;color:var(--evo-text-primary, #0f172a);margin-bottom:6px;">
                        Champs demandés au client lors de la réservation
                    </label>
                    <p style="color:var(--evo-text-muted, #64748b);font-size:0.85rem;margin:0 0 14px 0;">
                        Le nom, le numéro de téléphone et l'email sont toujours demandés. Cochez les informations complémentaires nécessaires à votre métier :
                    </p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:10px;">
                        <label style="display:flex;align-items:center;gap:10px;background:var(--evo-bg-elevated, #ffffff);padding:10px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.9rem;cursor:pointer;">
                            <input type="checkbox" name="champ_adresse" value="1" {% if cfg.champs_requis.adresse %}checked{% endif %}>
                            <span><strong>Adresse de l'intervention</strong> (Artisans)</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;background:var(--evo-bg-elevated, #ffffff);padding:10px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.9rem;cursor:pointer;">
                            <input type="checkbox" name="champ_details" value="1" {% if cfg.champs_requis.details %}checked{% endif %}>
                            <span><strong>Détails des travaux / panne</strong></span>
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;background:var(--evo-bg-elevated, #ffffff);padding:10px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.9rem;cursor:pointer;">
                            <input type="checkbox" name="champ_marque_modele" value="1" {% if cfg.champs_requis.marque_modele %}checked{% endif %}>
                            <span><strong>Marque &amp; Modèle</strong> (Réparation)</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;background:var(--evo-bg-elevated, #ffffff);padding:10px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.9rem;cursor:pointer;">
                            <input type="checkbox" name="champ_lieu_rdv" value="1" {% if cfg.champs_requis.lieu_rdv %}checked{% endif %}>
                            <span><strong>Lieu du RDV</strong> (Visio/Bureau)</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;background:var(--evo-bg-elevated, #ffffff);padding:10px 14px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.9rem;cursor:pointer;">
                            <input type="checkbox" name="champ_remarque" value="1" {% if cfg.champs_requis.remarque or not cfg.champs_requis %}checked{% endif %}>
                            <span><strong>Remarque libre facultative</strong></span>
                        </label>
                    </div>
                </div>

                <!-- Consignes spécifiques au client -->
                <div style="background:var(--evo-bg-tertiary, #f8fafc);border:1px solid var(--evo-border, #e2e8f0);border-radius:12px;padding:20px;margin-bottom:20px;">
                    <label style="display:block;font-size:0.95rem;font-weight:700;color:var(--evo-text-primary, #0f172a);margin-bottom:6px;">
                        Consigne ou message particulier affiché aux clients
                    </label>
                    <textarea name="notice" rows="2" placeholder="Ex: Merci de vous présenter 5 minutes avant l'heure. Stationnement gratuit sur le parking de la gare." style="width:100%;padding:10px 12px;border:1px solid var(--evo-border, #cbd5e1);border-radius:8px;font-size:0.9rem;resize:vertical;">{{ cfg.notice }}</textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" style="background:#0f766e;color:#fff;border:none;padding:12px 24px;border-radius:8px;font-weight:700;font-size:0.95rem;cursor:pointer;box-shadow:0 2px 6px rgba(15,118,110,0.3);">
                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer les règles de réservation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ONGLET 4 : Équipe & Collaborateurs         -->
    <!-- ========================================== -->
    <div id="tab-pane-equipe" class="config-tab-pane" style="display:none;">
        <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.06);padding:26px;border:1px solid var(--evo-border, #e2e8f0);margin-bottom:24px;">
            <div style="margin-bottom:18px;">
                <h2 style="font-size:1.25rem;font-weight:800;color:var(--evo-text-primary, #1e293b);margin:0 0 4px;">
                    Gestion de l'Équipe &amp; Collaborateurs
                </h2>
                <p style="color:var(--evo-text-muted, #64748b);font-size:0.88rem;margin:0;">
                    Si vous avez plusieurs intervenants, vos clients pourront choisir la personne souhaitée ou opter pour le premier disponible.
                </p>
            </div>

            <!-- Interrupteur Choix Collaborateur -->
            <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="background:var(--evo-bg-tertiary, #f8fafc);border:1px solid var(--evo-border, #e2e8f0);border-radius:10px;padding:14px 18px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <input type="hidden" name="subaction" value="toggle_choix_collab">
                <input type="hidden" name="active_tab" value="equipe">
                <div>
                    <strong style="color:var(--evo-text-primary, #0f172a);display:block;">Activer le choix du collaborateur par le client</strong>
                    <span style="color:var(--evo-text-muted, #64748b);font-size:0.83rem;">Si désactivé, le client réserve sans distinction d'intervenant.</span>
                </div>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="choix_collab" value="1" {% if cfg.choix_collab %}checked{% endif %} onchange="this.form.submit()">
                    <span style="font-weight:700;color:#0f766e;font-size:0.9rem;">{% if cfg.choix_collab %}Activé{% else %}Désactivé{% endif %}</span>
                </label>
            </form>

            <!-- Liste des membres -->
            {% if collabs|length > 0 %}
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px;">
                {% for c in collabs %}
                <div style="display:flex;align-items:center;justify-content:space-between;background:var(--evo-bg-elevated, #ffffff);border:1px solid var(--evo-border, #cbd5e1);padding:12px 18px;border-radius:10px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:var(--evo-bg-tertiary, #f1f5f9);color:#0f766e;border-radius:50%;font-size:1rem;">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <div>
                            <strong style="font-size:1rem;color:{% if c.is_active %}#0f172a{% else %}#94a3b8{% endif %};">
                                {{ c.nom }}
                            </strong>
                            {% if not c.is_active %}
                            <span style="background:#fee2e2;color:#b91c1c;font-size:0.75rem;padding:2px 8px;border-radius:10px;font-weight:700;margin-left:8px;">Inactif</span>
                            {% endif %}
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:8px;">
                        <!-- Renommer -->
                        <button type="button" onclick="editCollabPrompt({{ c.id }}, '{{ c.nom }}')" style="background:var(--evo-bg-tertiary, #f1f5f9);color:var(--evo-text-secondary, #334155);border:1px solid var(--evo-border, #cbd5e1);padding:6px 12px;border-radius:6px;font-size:0.85rem;font-weight:600;cursor:pointer;">
                            <i class="fa-solid fa-pen"></i>
                        </button>

                        <!-- Toggle -->
                        <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="margin:0;">
                            <input type="hidden" name="subaction" value="collab_toggle">
                            <input type="hidden" name="active_tab" value="equipe">
                            <input type="hidden" name="collab_id" value="{{ c.id }}">
                            <button type="submit" style="background:var(--evo-bg-elevated, #ffffff);border:1px solid var(--evo-border, #cbd5e1);color:{% if c.is_active %}#d97706{% else %}#059669{% endif %};padding:6px 10px;border-radius:6px;font-size:0.85rem;font-weight:600;cursor:pointer;">
                                {% if c.is_active %}Masquer{% else %}Activer{% endif %}
                            </button>
                        </form>

                        <!-- Supprimer -->
                        <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="margin:0;" onsubmit="return confirm('Supprimer ce collaborateur ?');">
                            <input type="hidden" name="subaction" value="collab_delete">
                            <input type="hidden" name="active_tab" value="equipe">
                            <input type="hidden" name="collab_id" value="{{ c.id }}">
                            <button type="submit" style="background:var(--evo-bg-elevated, #ffffff);border:1px solid #fecaca;color:#dc2626;padding:6px 10px;border-radius:6px;font-size:0.85rem;cursor:pointer;">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>
                {% endfor %}
            </div>
            {% else %}
            <div style="text-align:center;padding:24px;background:var(--evo-bg-tertiary, #f8fafc);border-radius:10px;border:1px dashed #cbd5e1;margin-bottom:20px;color:var(--evo-text-muted, #64748b);">
                Aucun collaborateur spécifique défini (l'agenda fonctionne en mode individuel / établissement).
            </div>
            {% endif %}

            <!-- Formulaire ajout collaborateur -->
            <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="background:var(--evo-bg-tertiary, #f8fafc);padding:16px;border-radius:10px;border:1px solid var(--evo-border, #e2e8f0);display:flex;gap:10px;">
                <input type="hidden" name="subaction" value="collab_add">
                <input type="hidden" name="active_tab" value="equipe">
                <input type="text" name="collab_nom" placeholder="Prénom ou nom du membre de l'équipe (ex: Sophie, Thomas...)" required style="flex:1;padding:10px 12px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.9rem;">
                <button type="submit" style="background:#0f766e;color:#fff;border:none;padding:10px 18px;border-radius:6px;font-weight:600;font-size:0.9rem;cursor:pointer;">
                    + Ajouter à l'équipe
                </button>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ONGLET 5 : Horaires Hebdomadaires          -->
    <!-- ========================================== -->
    <div id="tab-pane-horaires" class="config-tab-pane" style="display:none;">
        <div style="background:var(--evo-bg-elevated, #ffffff);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.06);padding:26px;border:1px solid var(--evo-border, #e2e8f0);margin-bottom:24px;">
            <div style="margin-bottom:18px;">
                <h2 style="font-size:1.25rem;font-weight:800;color:var(--evo-text-primary, #1e293b);margin:0 0 4px;">
                    Grille Horaire d'Ouverture &amp; Prise de RDV
                </h2>
                <p style="color:var(--evo-text-muted, #64748b);font-size:0.88rem;margin:0;">
                    Définissez vos plages horaires du Lundi au Dimanche. Les créneaux réservables seront automatiquement calculés dans ces intervalles.
                </p>
            </div>

            <form action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post">
                <input type="hidden" name="subaction" value="save_horaires">
                <input type="hidden" name="active_tab" value="horaires">

                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px;">
                    {% for h in horaires_list %}
                    <div style="display:flex;align-items:center;justify-content:space-between;background:var(--evo-bg-tertiary, #f8fafc);border:1px solid var(--evo-border, #e2e8f0);padding:12px 18px;border-radius:10px;flex-wrap:wrap;gap:12px;">
                        <div style="min-width:140px;display:flex;align-items:center;gap:10px;">
                            <input type="checkbox" name="horaires[{{ h.num }}][active]" value="1" id="jour-check-{{ h.num }}" {% if h.open %}checked{% endif %} onchange="toggleJourInputs({{ h.num }})">
                            <label for="jour-check-{{ h.num }}" style="font-weight:700;color:var(--evo-text-primary, #0f172a);cursor:pointer;font-size:0.95rem;">
                                {{ h.nom }}
                            </label>
                        </div>

                        <div id="jour-inputs-{{ h.num }}" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;{% if not h.open %}opacity:0.4;pointer-events:none;{% endif %}">
                            <!-- Matin -->
                            <div style="display:flex;align-items:center;gap:6px;font-size:0.85rem;color:var(--evo-text-secondary, #475569);">
                                <span>Matin :</span>
                                <input type="time" name="horaires[{{ h.num }}][m_deb]" value="{{ h.m_deb }}" style="padding:6px 8px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.85rem;">
                                <span>à</span>
                                <input type="time" name="horaires[{{ h.num }}][m_fin]" value="{{ h.m_fin }}" style="padding:6px 8px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.85rem;">
                            </div>

                            <span style="color:#cbd5e1;">|</span>

                            <!-- Après-midi -->
                            <div style="display:flex;align-items:center;gap:6px;font-size:0.85rem;color:var(--evo-text-secondary, #475569);">
                                <span>Après-midi :</span>
                                <input type="time" name="horaires[{{ h.num }}][a_deb]" value="{{ h.a_deb }}" style="padding:6px 8px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.85rem;">
                                <span>à</span>
                                <input type="time" name="horaires[{{ h.num }}][a_fin]" value="{{ h.a_fin }}" style="padding:6px 8px;border:1px solid var(--evo-border, #cbd5e1);border-radius:6px;font-size:0.85rem;">
                            </div>
                        </div>
                    </div>
                    {% endfor %}
                </div>

                <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" style="background:#0f766e;color:#fff;border:none;padding:12px 24px;border-radius:8px;font-weight:700;font-size:0.95rem;cursor:pointer;box-shadow:0 2px 6px rgba(15,118,110,0.3);">
                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer ma grille horaire
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Formulaire fantôme pour renommage rapide d'un collaborateur -->
    <form id="collab-rename-form" action="/pro/rdv/config?commerce_id={{ commerce.id }}" method="post" style="display:none;">
        <input type="hidden" name="subaction" value="collab_edit">
        <input type="hidden" name="active_tab" value="equipe">
        <input type="hidden" name="collab_id" id="collab-rename-id" value="">
        <input type="hidden" name="collab_nom" id="collab-rename-nom" value="">
    </form>
</div>

<script>
function switchConfigTab(tabName) {
    document.querySelectorAll('.config-tab-pane').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.config-tab-btn').forEach(btn => {
        btn.style.color = '#64748b';
        btn.style.borderBottomColor = 'transparent';
    });
    const targetPane = document.getElementById('tab-pane-' + tabName);
    const targetBtn = document.getElementById('tab-btn-' + tabName);
    if (targetPane) targetPane.style.display = 'block';
    if (targetBtn) {
        targetBtn.style.color = '#0f766e';
        targetBtn.style.borderBottomColor = '#0f766e';
    }
    // Mémoriser dans l'URL sans rechargement
    if (window.history && window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);
    }
}

function toggleEditPresta(id) {
    const el = document.getElementById('edit-presta-' + id);
    if (el) {
        el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
    }
}

function toggleJourInputs(jourNum) {
    const check = document.getElementById('jour-check-' + jourNum);
    const container = document.getElementById('jour-inputs-' + jourNum);
    if (check && container) {
        if (check.checked) {
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
        } else {
            container.style.opacity = '0.4';
            container.style.pointerEvents = 'none';
        }
    }
}

function editCollabPrompt(id, currentNom) {
    const newNom = prompt("Nouveau nom pour ce collaborateur :", currentNom);
    if (newNom !== null && newNom.trim() !== "" && newNom.trim() !== currentNom) {
        document.getElementById('collab-rename-id').value = id;
        document.getElementById('collab-rename-nom').value = newNom.trim();
        document.getElementById('collab-rename-form').submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const initTab = '{{ active_tab }}' || 'prestations';
    switchConfigTab(initTab);
});
</script>
