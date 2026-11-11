<section class="progression-leaderboard">
    <header style="margin-bottom: 2rem; text-align: center;">
        <h1><i class="fa-solid fa-trophy"></i> Classement</h1>
        <p style="color: #6c757d; margin-top: 0.5rem;">Classement des membres les plus actifs</p>
    </header>
    
    {% IF leaderboard %}
    <div class="leaderboard-table" style="background: var(--panel-bg); border-radius: var(--panel-radius); overflow: hidden; box-shadow: var(--panel-shadow); border: 1px solid var(--panel-border);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--primary-color); color: white;">
                    <th style="padding: 1rem; text-align: center; width: 80px;">Rang</th>
                    <th style="padding: 1rem; text-align: left;">Utilisateur</th>
                    <th style="padding: 1rem; text-align: center;">Palier</th>
                    <th style="padding: 1rem; text-align: right;">Points</th>
                </tr>
            </thead>
            <tbody>
                {% for entry in leaderboard %}
                <tr style="border-bottom: 1px solid var(--panel-border); {% IF currentUserId AND entry.user_id == currentUserId %}background: rgba(255, 140, 66, 0.1); font-weight: bold;{% ENDIF %}">
                    <td style="padding: 1rem; text-align: center; font-weight: bold; font-size: 1.25rem; color: {% IF entry.rank == 1 %}var(--warning-color){% ELSEIF entry.rank == 2 %}#c0c0c0{% ELSEIF entry.rank == 3 %}#cd7f32{% ELSE %}var(--font-color){% ENDIF %};">
                        {% IF entry.rank == 1 %}
                        <i class="fa-solid fa-trophy" style="color: var(--warning-color);"></i>
                        {% ELSEIF entry.rank == 2 %}
                        <i class="fa-solid fa-medal" style="color: #c0c0c0;"></i>
                        {% ELSEIF entry.rank == 3 %}
                        <i class="fa-solid fa-award" style="color: #cd7f32;"></i>
                        {% ELSE %}
                        #{{entry.rank}}
                        {% ENDIF %}
                    </td>
                    <td style="padding: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                {{entry.username|slice(0, 1)|upper}}
                            </div>
                            <div>
                                <a href="{{ ROUTER.generate('gamification-public-profile', {'id': entry.user_id}) }}" style="font-weight: bold; color: var(--font-color); text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                    {{entry.username}}
                                </a>
                                {% IF currentUserId AND entry.user_id == currentUserId %}
                                <small style="color: var(--primary-color); display: block;">(Vous)</small>
                                {% ENDIF %}
                            </div>
                        </div>
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        {% IF entry.current_palier %}
                        <span style="display: inline-block; padding: 0.5rem 1rem; background: {{entry.current_palier.badge_color}}; color: white; border-radius: 20px; font-size: 0.875rem;">
                            <i class="fa-solid {{entry.current_palier.badge_icon}}"></i> {{entry.current_palier.badge_name}}
                        </span>
                        {% ELSE %}
                        <span style="color: #6c757d;">-</span>
                        {% ENDIF %}
                    </td>
                    <td style="padding: 1rem; text-align: right; font-weight: bold; font-size: 1.125rem; color: var(--primary-color);">
                        {{entry.total_points}} <i class="fa-solid fa-coins" style="color: var(--warning-color);"></i>
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
    {% ELSE %}
    <div style="text-align: center; padding: 3rem; background: #f8f9fa; border-radius: 8px;">
        <i class="fa-solid fa-trophy" style="font-size: 4rem; color: #dee2e6; margin-bottom: 1rem; display: block;"></i>
        <p style="color: #6c757d; font-size: 1.125rem;">Aucun utilisateur dans le leaderboard pour le moment.</p>
    </div>
    {% ENDIF %}
    
    {% IF currentUserId %}
    <div style="margin-top: 2rem; padding: 1rem; background: rgba(255, 140, 66, 0.1); border-left: 4px solid var(--primary-color); border-radius: 4px;">
        <p style="margin: 0; color: var(--font-color);">
            <i class="fa-solid fa-info-circle"></i> Vous pouvez consulter vos statistiques détaillées sur 
            <a href="{{ ROUTER.generate('gamification-profile') }}" style="color: var(--primary-color); font-weight: bold;">votre page de progression</a>.
        </p>
    </div>
    {% ENDIF %}
</section>

<style>
.progression-leaderboard {
    max-width: 1200px;
    margin: 0 auto;
}

.leaderboard-table {
    background: var(--panel-bg);
    border-radius: var(--panel-radius);
    overflow: hidden;
    box-shadow: var(--panel-shadow);
    border: 1px solid var(--panel-border);
}

.leaderboard-table table {
    width: 100%;
    border-collapse: collapse;
}

.leaderboard-table thead tr {
    background: var(--primary-color);
    color: white;
}

.leaderboard-table tbody tr {
    border-bottom: 1px solid var(--panel-border);
    transition: background-color 0.2s ease;
}

.leaderboard-table tbody tr:hover {
    background: rgba(0, 0, 0, 0.02);
}

@media (max-width: 768px) {
    .leaderboard-table {
        overflow-x: auto;
    }
    
    .leaderboard-table table {
        min-width: 600px;
    }
    
    .leaderboard-table th,
    .leaderboard-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.9rem;
    }
}
</style>



