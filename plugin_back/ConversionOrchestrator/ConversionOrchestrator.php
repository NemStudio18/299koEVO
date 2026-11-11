<?php
namespace ConversionOrchestrator;

use Core\Core;
use Core\Plugin\Plugin;

class ConversionOrchestrator extends Plugin {

    /**
     * Installation: Create necessary tables if any (none for now)
     */
    public static function install() {
        return true;
    }

    /* -------------------------------------------------------------------------
       ASSETS LOADER
    ------------------------------------------------------------------------- */
    public static function onEndFrontHead($param = '') {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('ConversionOrchestrator');
        if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
            return (string)$param;
        }

        $core = Core::getInstance();
        $siteUrl = $core->getConfigVal('siteUrl');
        $output = (string)$param;
        $output .= '<link rel="stylesheet" href="' . $siteUrl . '/plugin/ConversionOrchestrator/assets/css/orchestrator.css?v=1.0">' . "\n";
        
        // CSS for our custom Toasts
        $output .= '<style>
        .orch-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #2d3436;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-left: 4px solid #ff8c42;
        }
        .orch-toast.show { transform: translateX(0); }
        .orch-toast i { color: #ff8c42; font-size: 1.2rem; }
        </style>';
        
        return $output;
    }
    
    /**
     * Inject UI Toast in footer if needed
     */
    public static function onEndFrontBody($param = '') {
        if (!isset($_SESSION['pending_toast'])) return (string)$param;
        
        $msg = '';
        if ($_SESSION['pending_toast'] === 'favorite_restored') {
            $msg = 'Super ! Ce lieu a bien été ajouté à vos favoris.';
        }
        
        unset($_SESSION['pending_toast']);
        
        if (!$msg) return (string)$param;

        return (string)$param . '
        <div id="orch-toast" class="orch-toast">
            <i class="fa-solid fa-heart-circle-check"></i>
            <span>' . $msg . '</span>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var toast = document.getElementById("orch-toast");
            setTimeout(function() {
                toast.classList.add("show");
                setTimeout(function() {
                    toast.classList.remove("show");
                }, 4000);
            }, 1000);
        });
        </script>';
    }

    /**
     * Boot logic: Load routes
     */
    public static function onInit() {
        if (file_exists(__DIR__ . '/param/routes.php')) {
            require_once __DIR__ . '/param/routes.php';
        }
    }

    /* -------------------------------------------------------------------------
       ANALYTICS & PERSISTENCE HELPERS
    ------------------------------------------------------------------------- */

    /**
     * Generates a stable fingerprint for the visitor
     */
    public static function getVisitorHash(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return sha1($ip . $ua);
    }

    /**
     * Tracks a visit in the database using fingerprinting
     * @return int The total number of visits for this fingerprint
     */
    public static function trackVisit(): int {
        $hash = self::getVisitorHash();
        $user = \Core\Auth\CurrentUser::get();
        $userId = $user->isLogged ? $user->id : null;
        
        $db = \Core\Database::getInstance();
        $pdo = $db->getPdo();
              try {
            // 1. Check if the fingerprint exists
            $stmt = $pdo->prepare("SELECT id, visit_count, user_id FROM visitor_analytics WHERE fingerhash = ?");
            $stmt->execute([$hash]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($row) {
                $newCount = (int)$row['visit_count'] + 1;
                // Update counts and link user_id if newly available
                $updateSql = "UPDATE visitor_analytics SET visit_count = ?, last_visit = NOW()";
                $params = [$newCount];
                if ($userId !== null && $row['user_id'] === null) {
                    $updateSql .= ", user_id = ?";
                    $params[] = $userId;
                    // LOG CONVERSION EVENT: visitore -> user
                    self::logEvent('login_conversion', $userId, [
                        'last_fingerhash' => $hash,
                        'source' => 'orchestrator_tracking'
                    ]);
                }
                $updateSql .= " WHERE id = ?";
                $params[] = $row['id'];
                
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute($params);
                return $newCount;
            } else {
                // 2. First visit
                $stmt = $pdo->prepare("INSERT INTO visitor_analytics (fingerhash, user_id, visit_count, first_visit, last_visit) VALUES (?, ?, 1, NOW(), NOW())");
                $stmt->execute([$hash, $userId]);
                return 1;
            }
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist or other DB issues
            return 0;
        }
    }

    /**
     * Logs a conversion event
     */
    public static function logEvent(string $type, ?int $userId, array $context = []): void {
        $db = \Core\Database::getInstance();
        $pdo = $db->getPdo();
        
        $stmt = $pdo->prepare("INSERT INTO conversion_events (type, user_id, context, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$type, $userId, json_encode($context)]);
    }


    /* -------------------------------------------------------------------------
       TUNNEL 1: CITIZEN (Découverte -> Engagement)
       Trigger: auth:registered
    ------------------------------------------------------------------------- */
    public static function onUserRegistered($user) {
        $core = Core::getInstance();
        
        // 1. Send Welcome Notification
        $core->callHook('notifications:send', [
            'user_id' => $user['id'],
            'title'   => 'Bienvenue sur ' . $core->getConfigVal('siteName'),
            'message' => 'Ravi de vous compter parmi nous ! Explorez votre ville et commencez à sauvegarder vos lieux préférés.',
            'type'    => 'system'
        ]);

        // 2. Restore Context (if they clicked "Favorite" before login)
        if (isset($_SESSION['pending_action'])) {
            $pending = $_SESSION['pending_action'];
            $poiId = (int)$pending['poi_id'];
            
            if ($pending['type'] === 'favorite' && $poiId > 0) {
                try {
                    $favoriteManager = new \PoiFavorites\Entities\FavoriteManager();
                    $favoriteManager->add($poiId, $user['id']);
                    
                    // LOG CONVERSION EVENT: soft_friction success
                    self::logEvent('soft_friction', $user['id'], [
                        'poi_id' => $poiId,
                        'action' => 'favorite'
                    ]);

                    // Set Pending Toast for next page load
                    $_SESSION['pending_toast'] = 'favorite_restored';
                    
                } catch (\Exception $e) {
                    // Silently fail if plugin not active
                }
            }
            unset($_SESSION['pending_action']);
        }
    }

    /**
     * Inject soft-friction CTA for guests
     */
    public static function poiDetailActions($args) {
        $poi = $args['poi'] ?? null;
        if (!$poi) return;

        $isAdmin = $args['is_admin'] ?? false;
        $user = \Core\Auth\CurrentUser::get();
        $isLogged = $user->isLogged();
        
        // Use our new profile system
        $poiId = (int)(is_object($poi) ? $poi->id : $poi['id']);
        $isOwner = $user->isOwner() && in_array($poiId, $user->claims() ?? []);
        
        $claimedBy = is_object($poi) ? ($poi->claimed_by ?? null) : ($poi['claimed_by'] ?? null);
        $poiId = is_object($poi) ? $poi->id : $poi['id'];
        $poiName = is_object($poi) ? ($poi->name ?? '') : ($poi['name'] ?? '');
        
        // Construire le return_url pour rediriger après login
        $returnUrl = $_SERVER['REQUEST_URI'] ?? '/';
        $encodedName = urlencode($poiName);
        $encodedReturn = urlencode($returnUrl);
        
        // 1. SECURITY: Owners have their own "Manager Section" below.
        // We hide visitor/guest actions for them to keep the UI clean.
        // Admins can see EVERYTHING (Manager Section + Visitor actions).
        if ($isOwner) {
            return;
        }

        $router = \Core\Router\Router::getInstance();
        $output = '';

        // Admin Specific: Reset Ownership tool
        if ($isAdmin && $claimedBy) {
            $output .= '<button type="button" class="button-search button admin-reset-owner" 
                style="background: #e74c3c; color: white;" 
                onclick="adminResetOwnership(' . $poiId . ')" 
                title="Supprimer le propriétaire actuel">
                <i class="fa-solid fa-user-slash"></i> <span class="button-text">Supprimer Propriété</span>
            </button>';
        }

        if ($claimedBy) {
            // --- CASE: CLAIMED POI (Visitor view) ---
            
            // S'abonner (Favorite)
            if ($isLogged) {
                // Logged-in user: show direct favorite button
                $output .= '<button type="button" id="poi-favorite-btn-orchestrator" class="button-search button favorite-button" data-poi-id="' . $poiId . '">
                    <i class="fa-solid fa-star"></i> <span class="button-text">S\'abonner</span>
                </button>';
            } else {
                // Guest friction
                try {
                    $favUrl = '/users/login?msg=subscribe&poi_name=' . $encodedName . '&return_url=' . $encodedReturn;
                    $output .= '<a href="' . $favUrl . '" class="button-search button favorite-button orchestrator-friction">
                        <i class="fa-solid fa-star"></i> <span class="button-text">S\'abonner</span>
                    </a>';
                } catch (\Exception $e) {}
            }

            // Certified Badge
            $output .= '<span class="button small poi-badge-verified-visitor" style="background: #2ecc71; color: white; cursor: default; height: 38px; display: inline-flex; align-items: center; border-radius: 4px; padding: 0 15px; font-weight: 500; font-size: 0.9rem;">
                <i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i> <span class="button-text">Établissement certifié</span>
            </span>';

            // Contacter (only if logged)
            if ($isLogged) {
                try {
                    $contactUrl = $router->generate('messaging-compose') . '?to=' . $claimedBy;
                    $output .= '<a href="' . $contactUrl . '" class="button-search button contact-owner-button" title="Contacter le propriétaire">
                        <i class="fa-solid fa-envelope"></i> <span class="button-text">Contacter</span>
                    </a>';
                } catch (\Exception $e) {}
            }

            // Signaler (Report) — accessible à TOUS via modale
            $output .= '<a href="#" class="button-search button report-poi-button" title="Signaler ce POI" data-report-modal="true">
                <i class="fa-solid fa-flag"></i> <span class="button-text">Signaler</span>
            </a>';

        } else {
            // --- CASE: UNCLAIMED POI (Visitor view) ---
            
            // Signaler (Report) — accessible à TOUS via modale
            $output .= '<a href="#" class="button-search button report-poi-button" title="Signaler ce POI" data-report-modal="true">
                <i class="fa-solid fa-flag"></i> <span class="button-text">Signaler</span>
            </a>';

            // S'abonner (Guest friction only, logged is handled here)
            if (!$isLogged) {
                try {
                    $favUrl = '/users/login?msg=subscribe&poi_name=' . $encodedName . '&return_url=' . $encodedReturn;
                    $output .= '<a href="' . $favUrl . '" class="button-search button favorite-button orchestrator-friction">
                        <i class="fa-solid fa-star"></i> <span class="button-text">S\'abonner</span>
                    </a>';
                } catch (\Exception $e) {}
            } else {
                $output .= '<button type="button" id="poi-favorite-btn" class="button-search button favorite-button" data-poi-id="' . $poiId . '">
                    <i class="fa-solid fa-star"></i> <span class="button-text">S\'abonner</span>
                </button>';
            }

            // Corriger (Edit Suggestion)
            $editUrl = $isLogged ? '/PoiManager/suggest/' . $poiId : '/users/login?msg=edit&poi_name=' . $encodedName . '&return_url=' . $encodedReturn;
            $output .= '<a href="' . $editUrl . '" class="button-search button" title="Corriger les informations">
                <i class="fa-solid fa-edit"></i> <span class="button-text">Corriger</span>
            </a>';

            $poiSource = is_object($poi) ? ($poi->source ?? '') : ($poi['source'] ?? '');
            $claimText = ($poiSource === 'admin') ? "Cette fiche a été complétée par l'administration, vous êtes le propriétaire, réclamez-la" : "C'est chez moi";
            
            // C'est chez moi (Claim)
            if (!$isLogged) {
                 $claimUrl = '/users/login?msg=claim&poi_name=' . $encodedName . '&return_url=' . $encodedReturn;
                 $output .= '<a href="' . $claimUrl . '" class="button-search button claim-poi-button" style="background-color: #3498db; color: white;">
                    <i class="fa-solid fa-house-chimney-user"></i> <span class="button-text">' . $claimText . '</span>
                 </a>';
            } else {
                 // Logged but not owner: show direct claim button
                 $claimUrl = '/claim/' . $poiId;
                 $output .= '<a href="' . $claimUrl . '" class="button-search button claim-poi-button" style="background-color: #3498db; color: white;">
                    <i class="fa-solid fa-house-chimney-user"></i> <span class="button-text">' . $claimText . '</span>
                 </a>';
            }
        }

        echo $output;
    }

    /* -------------------------------------------------------------------------
       TUNNEL 2: CONTRIBUTOR (Membre -> Acteur)
       Trigger: POI Contribution / Creation / Forum Reply
    ------------------------------------------------------------------------- */
    public static function onContributionAccepted($args) {
        $userId = $args['user_id'] ?? 0;
        if ($userId > 0) {
            self::rewardContribution($userId, 'poi_contribution_accepted', 'Félicitations ! Votre contribution a été validée. Votre réputation grimpe !');
        }
    }

    public static function onPoiCreated($args) {
        // Triggered when someone creates a POI
        $user = \Core\Auth\CurrentUser::get();
        if ($user->isLogged) {
            self::rewardContribution($user->id, 'poi_creation', 'Nouveau lieu ajouté ! Merci pour cette pierre apportée à l\'édifice.');
        }
    }

    public static function onForumReplyCreated($args) {
        $userId = $args['user_id'] ?? 0;
        if ($userId > 0) {
            self::rewardContribution($userId, 'forum_reply', 'Merci pour votre réponse ! Chaque échange renforce la communauté.');
        }
    }

    private static function rewardContribution($userId, $actionType, $message) {
        $core = Core::getInstance();

        // 1. Give XP via Gamification if available
        try {
            require_once PLUGINS . 'gamification/services/PointsService.php';
            $pointsService = new \Gamification\Services\PointsService();
            
            // Map action types to points (simplified)
            $points = 10;
            if ($actionType === 'poi_contribution_accepted') $points = 50;
            if ($actionType === 'poi_creation') $points = 20;
            
            $pointsService->addPoints($userId, $points, $actionType, []);
        } catch (\Exception $e) {
            // Gamification might not be active
        }
        
        // 2. Send an instant notification
        $core->callHook('notifications:send', [
            'user_id' => $userId,
            'title'   => 'Contribution récompensée 🏅',
            'message' => $message,
            'type'    => 'reward'
        ]);
    }

    /* -------------------------------------------------------------------------
       TUNNEL 3: PROFESSIONAL (Pro -> Business)
       Trigger: Claim Validated / Offer Created
    ------------------------------------------------------------------------- */
    public static function onClaimValidated($args) {
        $core = Core::getInstance();
        
        // Onboarding Pro
        $core->callHook('notifications:send', [
            'user_id' => $args['user_id'],
            'title'   => 'Établissement validé !',
            'message' => 'Vous êtes maintenant le gestionnaire officiel. Accédez à votre tableau de bord pour booster votre visibilité.',
            'link'    => '/BusinessDashboard', // Future plugin link
            'type'    => 'business'
        ]);
    }

    public static function onOfferCreated($args) {
        $core = Core::getInstance();
        $user = \Core\Auth\CurrentUser::get();
        
        if ($user->isLogged) {
            // Upsell Notification for Pro Users
            $core->callHook('notifications:send', [
                'user_id' => $user->id,
                'title'   => 'Offre publiée 🚀',
                'message' => 'Votre offre est en ligne. Saviez-vous que les membres Premium ont 3x plus de visibilité ?',
                'type'    => 'upsell',
                'link'    => '/BusinessDashboard/premium'
            ]);
        }
    }

    /**
     * Retention Loop: Newsletter & PWA Triggers
     */
    public static function injectRetentionTriggers() {
        if (!isset($_SESSION)) session_start();
        
        // DEBUG LOG
        // echo '<script>console.log("Orchestrator: injectRetentionTriggers called");</script>';
        
        // [TESTING] Reset Mechanism
        if (isset($_GET['reset_orchestrator'])) {
            $_SESSION['orchestrator_visits'] = 0;
            setcookie('pwa_refused', '', time() - 3600, '/');
            echo '<script>alert("Orchestrator Reset! Visits: 0");</script>';
        }
        
        // 1. Update Visit Counter (Robust Hash-based tracking)
        $visits = self::trackVisit();
        
        $user = \Core\Auth\CurrentUser::get();
        $isLogged = $user->isLogged;
        $isRealUser = $isLogged && $user->role !== 'guest';

        // 2. Newsletter Orchestration (Magic Moment: 10th visit for Guests)
        if (\Core\Plugin\PluginsManager::isActivePlugin('newsletter')) {
            // Trigger ONLY for guests on the 10th visit
            if (!$user->isLogged || $user->role === 'guest') {
                // LOG EVENT
                self::logEvent('magic_moment', null, ['visits' => 10, 'pwa_status' => 'not_installed']);
                
                echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    setTimeout(function() {
                        if(typeof openNewsletterModal === "function") {
                            openNewsletterModal();
                        }
                    }, 5000);
                });
                </script>';
            }
        }

        // 3. PWA Orchestration (Frequencies: 5, 15, 25)
        if (\Core\Plugin\PluginsManager::isActivePlugin('pwa')) {
             // UNIFIED PERSISTENCE: Use the $visits variable updated via Cookie at line 314
             $shouldShowPwa = ($visits % 20 === 5) || ($isRealUser && $visits === 5);
             
             // DEBUG ORCHESTRATOR
             echo '<script>console.log("Orchestrator Debug: Visits=' . $visits . ', User=' . ($isRealUser ? 'Yes' : 'No') . ', Guest=' . ($user->role === 'guest' ? 'Yes' : 'No') . ', ShowPWA=' . ($shouldShowPwa ? 'Yes' : 'No') . '");</script>';

             if ($shouldShowPwa && !isset($_COOKIE['pwa_refused']) && !isset($_COOKIE['pwa_snooze'])) {
                 // Inject our Custom PWA Popup
                 echo '
                 <div id="orchestrator-pwa-popup">
                    <div class="orch-pwa-header">
                        <h3 class="orch-pwa-title">Installez l\'App 📱</h3>
                        <button onclick="document.getElementById(\'orchestrator-pwa-popup\').style.display=\'none\'; document.cookie=\'pwa_refused=1; max-age=31536000; path=/\'" class="orch-pwa-close">&times;</button>
                    </div>
                    <p class="orch-pwa-desc">Accédez à votre tableau de bord et vos lieux favoris plus rapidement.</p>
                    <div class="orch-pwa-actions">
                        <button id="orch-pwa-install-btn">Installer</button>
                        <button onclick="document.getElementById(\'orchestrator-pwa-popup\').style.display=\'none\'; document.cookie=\'pwa_snooze=1; max-age=86400; path=/\'" class="orch-pwa-snooze-btn">Plus tard</button>
                    </div>
                 </div>
                 <script>
                 document.addEventListener("DOMContentLoaded", function() {
                    setTimeout(function() {
                        var nativeBtn = document.getElementById("pwa-install-button");
                        if (nativeBtn && nativeBtn.style.display !== "none") {
                             document.getElementById("orchestrator-pwa-popup").style.display = "block";
                             
                             document.getElementById("orch-pwa-install-btn").addEventListener("click", function() {
                                 nativeBtn.click();
                                 document.getElementById("orchestrator-pwa-popup").style.display = "none";
                             });
                        } else {
                            console.log("Orchestrator Debug: Native PWA button not found or hidden");
                        }
                    }, 5000);
                 });
                 </script>';
             }
        }
    }

    /* -------------------------------------------------------------------------
       ORCHESTRATION: HERO ZONE
       Trigger: layout:hero-content
    ------------------------------------------------------------------------- */
    public static function injectHeroContent($isGlobalBanner = true) {
        $core = Core::getInstance();
        $user = \Core\Auth\CurrentUser::get();
        
        if ($user->isLogged && $user->role !== 'guest') {
            // LOGGED USER: Show Personal Dashboard
            if ($isGlobalBanner) {
                echo '<div class="lumiere-dashboard-container">';
                echo '<div class="hero-dashboard">';
                $pseudo = $user->username() ?: 'Membre';
                echo '<h2>Bonjour ' . htmlspecialchars($pseudo) . ' ! 👋</h2>';
            } else {
                echo '<div class="searchui-dashboard-inline">';
                $pseudo = $user->username ?: 'Membre';
                echo '<h2>Bonjour ' . htmlspecialchars($pseudo) . ' ! 👋</h2>';
            }
            
            echo '<div class="hero-stats">';
            // Simple status injection
            try {
                require_once PLUGINS . 'gamification/services/PointsService.php';
                $pointsService = new \Gamification\Services\PointsService();
                $stats = $pointsService->getUserStats($user->id);
                $palier = $stats['current_palier'] ?? null;
                if ($palier) {
                    echo '<div class="hero-stat-item">
                            <i class="fa-solid ' . ($palier['badge_icon'] ?? 'fa-trophy') . '" style="color:' . ($palier['badge_color'] ?? '#ffc107') . '"></i> ' . $palier['name'] . '
                          </div>';
                    
                    if (isset($stats['next_palier']) && $stats['next_palier']) {
                        $remaining = $stats['points_remaining'] ?? ($stats['next_palier']['points_required'] - $stats['total_points']);
                        echo '<div class="hero-stat-item">
                                <i class="fa-solid fa-arrow-up-right-dots"></i> ' . $remaining . ' pts avant ' . $stats['next_palier']['name'] . '
                              </div>';
                    }
                }
                echo '<div class="hero-stat-item">
                        <i class="fa-solid fa-star" style="color:#ffc107"></i> ' . ($stats['total_points'] ?? 0) . ' pts
                      </div>';
                
                if (isset($stats['total_transactions']) && $stats['total_transactions'] > 0) {
                    echo '<div class="hero-stat-item">
                            <i class="fa-solid fa-pen-nib"></i> ' . $stats['total_transactions'] . ' contributions
                          </div>';
                }
            } catch (\Exception $e) {}
            echo '</div>';
            
            if ($isGlobalBanner) {
                echo '</div>'; // End hero-user-dashboard
                echo '</div>'; // End lumiere-dashboard-container
            } else {
                echo '</div>'; // End searchui-dashboard-inline
            }
        } else {
             // GUEST Logic: Inject the Intent "Vous êtes ici plutôt pour :" section globally
             echo '
            <div class="landing-intent-container">
                <p class="landing-intent-title">Vous êtes ici plutôt pour :</p>
                <div class="landing-intent-actions">
                    <button type="button" class="intent-btn" data-intent="discover">
                        <i class="fa-solid fa-earth-europe"></i> Découvrir Bourges
                    </button>
                    <button type="button" class="intent-btn" data-intent="contribute">
                        <i class="fa-solid fa-pen-to-square"></i> Contribuer
                    </button>
                    <button type="button" class="intent-btn highlight-pro" data-intent="pro">
                        <i class="fa-solid fa-chart-line"></i> Gagner en visibilité
                    </button>
                </div>

                <div class="landing-intent-ctas">
                    <div class="intent-block intent-discover" hidden>
                        <p>Explorez Bourges et découvrez tous les lieux incontournables.</p>
                        <a href="/SearchUi" class="cta-button cta-secondary">Explorer maintenant</a>
                    </div>
                    <div class="intent-block intent-contribute" hidden>
                        <p>Contribuez à la base locale en corrigeant ou ajoutant des informations.</p>
                        <a href="/users/register" class="cta-button cta-primary">Créer un compte et contribuer</a>
                    </div>
                    <div class="intent-block intent-pro" hidden>
                        <p><strong>Dernière chance :</strong> Gagnez le badge <b>"Actif ce mois-ci"</b> et remontez en haut des recherches en publiant vos actualités !</p>
                        <a href="/SearchUi" class="cta-button cta-primary">Rechercher mon établissement</a>
                    </div>
                </div>
            </div>
            <script>
            (function() {
                var blocks = document.querySelectorAll(".intent-block");
                var buttons = document.querySelectorAll(".intent-btn");
                function setIntent(intent) {
                    blocks.forEach(function(b) { b.hidden = true; });
                    buttons.forEach(function(btn) { btn.classList.remove("active"); });
                    var activeBlock = document.querySelector(".intent-block.intent-" + intent);
                    var activeBtn = document.querySelector(".intent-btn[data-intent=\"" + intent + "\"]");
                    if (activeBlock) activeBlock.hidden = false;
                    if (activeBtn) activeBtn.classList.add("active");
                }
                buttons.forEach(function(btn) {
                    btn.addEventListener("click", function() {
                        setIntent(this.dataset.intent);
                    });
                });
            })();
            </script>
            ';
        }
    }

    /**
     * Determine if the top banner should be visible for a given route
     */
    public static function shouldShowBanner() {
        $router = \Core\Router\Router::getInstance();
        $match = $router->match();
        $routeName = $match['name'] ?? null;
        
        // Hide banner content on search pages (where we want fixed header logic)
        $hiddenRoutes = ['SearchUi-results', 'search', 'SearchUi-search', 'login', 'register'];
        
        if (isset($_SERVER['REQUEST_URI'])) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if (strpos($path, '/login') !== false || strpos($path, '/register') !== false) {
                return false;
            }
        }
        
        return !in_array($routeName, $hiddenRoutes);
    }

    /* -------------------------------------------------------------------------
       ORCHESTRATION: HOME SECTIONS (SearchUi Hook)
       Trigger: SearchUi:home-sections
    ------------------------------------------------------------------------- */
    public static function onHomeSections($data = [])
    {

        // Si $data n'est pas un tableau (ex: null), on l'initialise
        if (!is_array($data)) {
            $data = [];
        }
        
        $isLoggedIn = $data['isLoggedIn'] ?? false;
        // Check if user is logged in AND not a guest (handled by HomeController now)
        // But for safety, if we have a user object that is not a guest, we skip.
        
        $user = $data['user'] ?? null;
        $isGuest = ($user && method_exists($user, 'getRole') && $user->getRole() === 'guest');
        
        // If we are logged in AND NOT A GUEST, show the dashboard inline
        if ($isLoggedIn && !$isGuest) {
            ob_start();
            self::injectHeroContent(false);
            $dashboardHtml = ob_get_clean();
            
            if (!isset($data['sections'])) {
                $data['sections'] = [];
            }
            $data['sections'][] = $dashboardHtml;
            return $data;
        }

        // GUEST on SearchUI: Show intent block (same as accueil, not CTAs)
        ob_start();
        self::injectHeroContent(false);
        $intentHtml = ob_get_clean();
        
        if (!isset($data['sections'])) {
            $data['sections'] = [];
        }
        $data['sections'][] = $intentHtml;
        
        return $data;
    }

    /* -------------------------------------------------------------------------
       ORCHESTRATION: POI CARD REASSURANCE
       Trigger: poi:card-reassurance
    ------------------------------------------------------------------------- */
    public static function onPoiCardReassurance($poi) {
        if (empty($poi['claimed_by'])) return;

        $userId = (int)$poi['claimed_by'];
        $core = Core::getInstance();
        
        try {
            // Check subscription via Offers plugin service
            require_once PLUGINS . 'offers/services/SubscriptionService.php';
            $subService = new \Offers\Services\SubscriptionService($core->getLogger());
            
            if ($subService->hasActiveSubscription($userId)) {
                echo '<div class="poi-reassurance-banner">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Établissement vérifié & actif sur ' . $core->getConfigVal('siteName') . '</span>
                </div>';
            }
        } catch (\Exception $e) {
            // Service not available
        }
    }
}
?>
