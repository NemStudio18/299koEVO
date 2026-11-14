<div class="gamification-public-profile">
    <header class="profile-header" style="text-align: center; margin-bottom: 2rem;">
        <div class="profile-avatar" style="width: 80px; height: 80px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem;">
            {{ targetUser.username|slice(0, 1)|upper }}
        </div>
        <h1>{{ targetUser.username }}</h1>
        {% if targetUser.custom_badge %}
            <div style="margin-bottom: 0.5rem;">
                <span class="badge" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 0.3rem 0.8rem; border-radius: 999px; font-weight: bold; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-award"></i> {{ targetUser.custom_badge }}
                </span>
            </div>
        {% endif %}
        <p style="color: #6c757d;">Membre depuis le {{ targetUser.created_at|date('d/m/Y') }}</p>
        
        {% IF messagingEnabled and currentUser.id != targetUser.id %}
        <div style="margin-top: 1rem;">
            <a href="/messaging/compose/{{ targetUser.id }}" class="button primary small" style="border-radius: 20px; padding: 0.5rem 1.5rem;">
                <i class="fa-solid fa-envelope"></i> Envoyer un message
            </a>
        </div>
        {% ENDIF %}
    </header>

    <div class="gamification-overview" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Carte Niveau Actuel -->
        <div class="stat-card current-level" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid {{ stats.current_color }};">
            <h3 style="margin-top: 0; color: #6c757d; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Niveau Actuel</h3>
            <div style="display: flex; align-items: center; gap: 1rem; margin-top: 1rem;">
                <div class="level-icon" style="width: 50px; height: 50px; background: {{ stats.current_color }}; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="fa-solid {{ stats.current_palier.badge_icon }}"></i>
                </div>
                <div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: {{ stats.current_color }};">{{ stats.current_palier.name }}</div>
                    {% IF stats.has_next_palier %}
                    <div style="font-size: 0.85rem; color: #6c757d;">Prochain : {{ stats.next_palier.name }}</div>
                    {% ELSE %}
                    <div style="font-size: 0.85rem; color: #6c757d;">Niveau maximum !</div>
                    {% ENDIF %}
                </div>
            </div>
            
            {% IF stats.has_next_palier %}
            <div class="progress-container" style="margin-top: 1.5rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.85rem;">
                    <span>Progression</span>
                    <span>{{ stats.progress_percent_rounded }}%</span>
                </div>
                <div class="progress-bar" style="width: 100%; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden;">
                    <div style="width: {{ stats.progress_percent_rounded }}%; height: 100%; background: {{ stats.current_color }};"></div>
                </div>
                <div style="text-align: right; margin-top: 0.25rem; font-size: 0.75rem; color: #6c757d;">
                    Encore {{ stats.points_remaining }} points
                </div>
            </div>
            {% ENDIF %}
        </div>

        <!-- Carte Points Totaux -->
        <div class="stat-card total-points" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid var(--warning-color);">
            <h3 style="margin-top: 0; color: #6c757d; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Score Total</h3>
            <div style="font-size: 2.5rem; font-weight: bold; color: var(--warning-color); margin: 1rem 0;">
                {{ stats.total_points|number_format(0, ',', ' ') }} <small style="font-size: 1rem; color: #6c757d;">pts</small>
            </div>
            <div style="font-size: 0.9rem; color: #6c757d;">
                <i class="fa-solid fa-chart-line"></i> {{ stats.total_transactions }} actions réalisées
            </div>
        </div>
    </div>

    <div class="gamification-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <!-- Liste des Paliers -->
        <div class="paliers-list" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem;">
                <i class="fa-solid fa-layer-group"></i> Paliers
            </h3>
            <div class="paliers-timeline" style="position: relative; padding-left: 20px;">
                <div style="position: absolute; left: 9px; top: 10px; bottom: 10px; width: 2px; background: #f0f0f0;"></div>
                
                {% for palier in paliers %}
                <div class="palier-item" style="position: relative; margin-bottom: 1.5rem; {% IF palier.is_reached %}opacity: 1;{% ELSE %}opacity: 0.6;{% ENDIF %}">
                    <div class="dot" style="position: absolute; left: -15px; width: 12px; height: 12px; border-radius: 50%; background: {% IF palier.is_reached %}{{ palier.badge_color }}{% ELSE %}#ddd{% ENDIF %}; border: 2px solid white; box-shadow: 0 0 0 1px {% IF palier.is_reached %}{{ palier.badge_color }}{% ELSE %}#ddd{% ENDIF %}; margin-top: 5px;"></div>
                    <div class="palier-content" style="background: {% IF palier.is_current %}{{ palier.bg_color }}{% ELSE %}white{% ENDIF %}; padding: 0.75rem; border-radius: 8px; border: 1px solid {% IF palier.is_current %}{{ palier.border_color }}{% ELSE %}#eee{% ENDIF %};">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: bold; color: {% IF palier.is_reached %}{{ palier.badge_color }}{% ELSE %}#6c757d{% ENDIF %};">
                                <i class="fa-solid {{ palier.badge_icon }}"></i> {{ palier.name }}
                            </div>
                            <div style="font-size: 0.85rem; color: #6c757d;">{{ palier.points_required }} pts</div>
                        </div>
                    </div>
                </div>
                {% endfor %}
            </div>
        </div>

        <!-- Répartition des actions -->
        <div class="actions-stats" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem;">
                <i class="fa-solid fa-chart-pie"></i> Répartition des actions
            </h3>
            
            {% IF stats.actions_count %}
            <div class="actions-list">
                {% for action in stats.actions_count %}
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f0f0f0;">
                    <span style="display: flex; align-items: center; gap: 0.5rem;">
                        {% IF action.action == 'contribution_accepted' %}
                        <i class="fa-solid fa-hand-holding-heart" style="color: var(--success-color);"></i>
                        {% ELSEIF action.action == 'claim_validated' %}
                        <i class="fa-solid fa-house-user" style="color: var(--primary-color);"></i>
                        {% ELSEIF action.action == 'login_daily' %}
                        <i class="fa-solid fa-calendar-day" style="color: var(--info-color);"></i>
                        {% ELSE %}
                        <i class="fa-solid fa-star" style="color: #6c757d;"></i>
                        {% ENDIF %}
                        {{ action.label }}
                    </span>
                    <span style="font-weight: bold; background: #f8f9fa; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.85rem;">{{ action.count }}</span>
                </div>
                {% endfor %}
            </div>
            {% ELSE %}
            <p style="text-align: center; color: #adb5bd; padding: 2rem;">Aucune action enregistrée.</p>
            {% ENDIF %}
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
        <a href="{{ ROUTER.generate('gamification-leaderboard') }}" class="button secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour au classement
        </a>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .gamification-details {
        grid-template-columns: 1fr !important;
    }
}
</style>
