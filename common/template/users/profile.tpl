    <div class="profile-tabs-nav" role="tablist">
        <button type="button" class="profile-tab-button active" role="tab" aria-selected="true" aria-controls="tab-profile" id="tab-button-profile" data-tab="profile">
            <i class="fa-solid fa-user"></i> <span>Profil</span>
        </button>
        {% IF hasStats %}
        <button type="button" class="profile-tab-button" role="tab" aria-selected="false" aria-controls="tab-contributions" id="tab-button-contributions" data-tab="contributions">
            <i class="fa-solid fa-chart-line"></i> <span>Mes contributions</span>
        </button>
        {% ENDIF %}
        <a href="{{ ROUTER.generate("profile-edit") }}" class="profile-tab-button" role="tab">
            <i class="fa-solid fa-edit"></i> <span>Modifier</span>
        </a>
        <a href="{{ ROUTER.generate("profile-danger") }}" class="profile-tab-button profile-tab-danger" role="tab">
             <i class="fa-solid fa-exclamation-triangle"></i> <span>Zone de danger</span>
        </a>
        {% HOOK.user:profile-tabs(user) %}
    </div>
    
    <section class="user-profile">
    <!-- Système d'onglets -->

    <div class="profile-tabs">


            <!-- Onglet Profil -->
        <div class="profile-tab-content active" id="tab-profile" role="tabpanel" aria-labelledby="tab-button-profile">
            <div class="profile-header">
                <div class="profile-avatar-section">
                    {% if hasProfilePhoto %}
                    <img src="{{profilePhotoUrl}}" alt="Photo de profil" class="profile-avatar-img">
                    {% else %}
                    <div class="profile-avatar-css" style="background-color: {{avatarData.color}};">
                        {{avatarData.initials}}
                    </div>
                    {% endif %}
                </div>
                <div class="profile-info">
                    <h1 class="profile-username">{{user.username}}</h1>
                    {% if user.custom_badge %}
                    <div style="margin-bottom: 1rem;">
                        <span class="badge" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 0.3rem 0.8rem; border-radius: 999px; font-weight: bold; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);">
                            <i class="fa-solid fa-award"></i> {{ user.custom_badge }}
                        </span>
                    </div>
                    {% endif %}
                    
                    <div class="profile-data-list">
                        <div class="profile-data-item">
                            <span class="data-label" title="Pseudo"><i class="fa-solid fa-user"></i></span>
                            <span class="data-value">{{user.username}}</span>
                        </div>
                        <div class="profile-data-item">
                            <span class="data-label" title="Email"><i class="fa-solid fa-envelope"></i></span>
                            <span class="data-value">
                                {{user.email}}
                                {% IF public_email %}
                                    <span class="badge success profile-badge">Public</span>
                                {% ELSE %}
                                    <span class="badge secondary profile-badge"><i class="fa-solid fa-eye-slash"></i> Privé</span>
                                {% ENDIF %}
                            </span>
                        </div>
                        {% FOR field IN dynamicFields %}
                        {% IF field.value %}
                        <div class="profile-data-item">
                            <span class="data-label" title="{{field.label}}"><i class="{{field.icon}}"></i></span>
                            <span class="data-value">{{field.value}}</span>
                        </div>
                        {% ENDIF %}
                        {% ENDFOR %}
                    </div>
                </div>
            </div>

            <!-- Section Réglages -->
            <div class="profile-settings-section">
                <h2 class="profile-settings-title"><i class="fa-solid fa-cog"></i> Réglages</h2>
                
                <div class="settings-list">
                    
                    <!-- Confidentialité Email -->
                    <div class="setting-item">
                        <label class="toggle-switch">
                            <input type="checkbox" class="toggle-input" data-setting="public_email" {% IF public_email %}checked{% ENDIF %}>
                            <span class="toggle-slider"></span>
                        </label>
                        <div class="setting-info">
                            <span class="setting-label">Rendre mon mail public</span>
                            <span class="setting-description">
                                Permet aux autres utilisateurs de voir votre adresse email sur votre profil.
                            </span>
                        </div>
                    </div>

                    <!-- Theme Toggle (if enabled) -->
                    {% if CORE.getConfigVal("enable_theme_toggle") %}
                    <div class="setting-item">
                        <label class="toggle-switch">
                            <input type="checkbox" class="toggle-input" id="theme-toggle-btn">
                            <span class="toggle-slider"></span>
                        </label>
                        <div class="setting-info">
                            <span class="setting-label">Mode Sombre</span>
                            <span class="setting-description">
                                Bascule l'apparence du site entre le mode clair et le mode sombre.
                            </span>
                        </div>
                    </div>
                    {% endif %}

                    <!-- Settings injected by plugins -->
                    {% HOOK.user:profile-settings(user) %}
                </div>
            </div>
        </div>

        <!-- Onglet Mes contributions -->
        <div class="profile-tab-content" id="tab-contributions" role="tabpanel" aria-labelledby="tab-button-contributions">
            <div class="profile-stats">
                <h2 class="profile-subsection-title">Mes statistiques</h2>
                
                {% IF stats %}
                <div class="profile-stats-grid">
                    {% FOR stat IN stats %}
                    <div class="profile-stat-card">
                        <div class="profile-stat-icon" style="color: {{stat.color}};">
                            <i class="{{stat.icon}}"></i>
                        </div>
                        <div class="profile-stat-value">{{stat.value}}</div>
                        <div class="profile-stat-label">{{stat.label}}</div>
                    </div>
                    {% ENDFOR %}
                </div>
                {% ELSE %}
                <div class="profile-empty-state">
                    <i class="fa-solid fa-chart-line profile-empty-icon"></i>
                    <p class="profile-empty-text">Aucune statistique disponible pour le moment.</p>
                </div>
                {% ENDIF %}

                <h2 class="profile-subsection-title">Activités récentes</h2>
                
                {% IF activities %}
                    {% FOR activityGroup IN activities %}
                    <div class="activity-group">
                        <h3 class="activity-group-title">{{activityGroup.title}}</h3>
                        <div class="activity-list">
                            {% FOR item IN activityGroup.items %}
                            <a href="{{item.url}}" class="activity-list-item">
                                <div class="activity-icon-wrapper">
                                    <i class="{{item.icon}}"></i>
                                </div>
                                <div class="activity-content-wrapper">
                                    <div class="activity-text-main">{{item.text}}</div>
                                    <div class="activity-text-sub">{{item.subtext}}</div>
                                    <div class="activity-date-small">{{item.date}}</div>
                                </div>
                            </a>
                            {% ENDFOR %}
                        </div>
                    </div>
                    {% ENDFOR %}
                {% ELSE %}
                    <div class="profile-empty-activity">
                        <p>Aucune activité récente.</p>
                    </div>
                {% ENDIF %}
            </div>
        </div>
        {% HOOK.user:profile-tabs-content(user) %}
    </div>
</section>


<script>
// Gestion des onglets
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.profile-tab-button[data-tab]');
        const tabContents = document.querySelectorAll('.profile-tab-content');
        
        console.log('Profile Tabs Init:', tabButtons.length, 'buttons found');
        console.log('Profile Tabs Content:', tabContents.length, 'contents found');

        tabButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Tab clicked:', this.getAttribute('data-tab'));
                const targetTab = this.getAttribute('data-tab');
                
                // Désactiver tous les onglets
                let clearedCount = 0;
                tabButtons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                });
                tabContents.forEach(content => {
                    if (content.classList.contains('active')) {
                        content.classList.remove('active');
                        clearedCount++;
                    }
                });
                console.log('Cleared active class from', clearedCount, 'content elements');
                
                // Activer l'onglet sélectionné
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');
                const targetContent = document.getElementById('tab-' + targetTab);
                console.log('Target content ID:', 'tab-' + targetTab, targetContent);
                if (targetContent) {
                    targetContent.classList.add('active');
                    console.log('Class list after add:', targetContent.classList.toString());
                } else {
                    console.error('Target content NOT FOUND!');
                }
                
                // Mettre à jour l'URL sans recharger la page
                if (history.pushState) {
                    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab=' + targetTab;
                    window.history.pushState({path: newUrl}, '', newUrl);
                }
            });
        });
        
        // Gérer l'onglet actif au chargement si un paramètre URL est présent ou forcé
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        if (activeTab) {
            const defaultButton = document.querySelector(`.profile-tab-button[data-tab="${activeTab}"]`);
            if (defaultButton) {
                defaultButton.click();
            }
        }
    });
    // Gérer l'URL hash au chargement
    if (window.location.hash) {
        const hash = window.location.hash.substring(1);
        const button = document.querySelector(`[data-tab="${hash}"]`);
        if (button) {
            button.click();
        }
    }

    // Gestion des réglages AJAX
    const toggles = document.querySelectorAll('.toggle-switch input[data-setting]');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const setting = this.getAttribute('data-setting');
            const value = this.checked ? '1' : '0';
            const container = this.closest('.toggle-switch');
            
            // Effet visuel immédiat
            container.style.opacity = '0.5';
            
            // Si on change "allow_notifications", on grise/dégrise les préférences
            if (setting === 'allow_notifications') {
                const prefsContainer = document.getElementById('notif-prefs-container');
                if (prefsContainer) {
                    if (this.checked) {
                        prefsContainer.style.opacity = '1';
                        prefsContainer.style.pointerEvents = 'auto';
                    } else {
                        prefsContainer.style.opacity = '0.5';
                        prefsContainer.style.pointerEvents = 'none';
                    }
                }
            }

            const formData = new FormData();
            formData.append('setting', setting);
            formData.append('value', value);
            formData.append('_csrf', '{{_csrfToken}}');
            formData.append('request', 'ajax');
            
            fetch('{{ ROUTER.generate("profile-update-setting") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                container.style.opacity = '1';
                if (data.status === 'success') {
                    // Update visual feedback for email visibility
                    if (setting === 'public_email') {
                        const emailContainer = document.querySelector('.profile-data-item:nth-child(2) .data-value');
                        if (emailContainer) {
                            if (this.checked) {
                                emailContainer.innerHTML = '{{user.email}} <span class="badge success profile-badge">Public</span>';
                            } else {
                                emailContainer.innerHTML = '{{user.email}} <span class="badge secondary profile-badge"><i class="fa-solid fa-eye-slash"></i> Privé</span>';
                            }
                        }
                    }
                    
                    // Rafraîchir le menu avatar si nécessaire
                    if (setting === 'allow_messaging' || setting === 'allow_notifications') {
                        fetch('{{ ROUTER.generate("profile-avatar-menu") }}')
                            .then(res => res.text())
                            .then(html => {
                                const avatarMenu = document.querySelector('.nav-user-avatar-menu');
                                if (avatarMenu) {
                                    avatarMenu.outerHTML = html;
                                }
                            });
                    }
                    console.log('Réglage mis à jour:', setting);
                } else {
                    // Revenir en arrière en cas d'erreur
                    this.checked = !this.checked;
                    alert('Erreur: ' + (data.message || 'Impossible de sauvegarder le réglage'));
                }
            })
            .catch(error => {
                container.style.opacity = '1';
                this.checked = !this.checked;
                console.error('Erreur AJAX:', error);
                alert('Erreur réseau ou serveur');
            });
        });
    });

    // Envoyer les drapeaux de présence lors de la sauvegarde complète (au cas où un plugin l'utiliserait)
    // Mais ici c'est surtout pour la sauvegarde via le bouton submit si jamais il y en avait un.
    // Pour l'edit page, on n'ajoute rien car on VEUT qu'ils soient absents.
    
})();
</script>
