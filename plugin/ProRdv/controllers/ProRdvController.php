<?php

namespace ProRdv\Controllers;

use Core\Controllers\Controller;
use Core\Responses\PublicResponse;
use Core\Auth\CurrentUser;
use ProRdv\Services\RdvService;
use Template\Template;

defined('ROOT') or exit('Access denied!');

class ProRdvController extends Controller
{
    private RdvService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = RdvService::getInstance();
    }

    /**
     * Vérifie si l'utilisateur courant est administrateur 299koEVO
     */
    private function isAdminUser($user): bool
    {
        if (defined('ADMIN_MODE') && ADMIN_MODE) return true;
        if (defined('IS_ADMIN') && IS_ADMIN) return true;
        if (!$user || !$user->isLogged()) return false;
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) return true;
        if (method_exists($user, 'can_access') && $user->can_access('system:admin-access')) return true;
        if ((int)($user->attributes['group_id'] ?? 0) === 1) return true;
        return false;
    }

    /**
     * Vérifie les droits d'accès et résout le commerce associé
     */
    private function resolveCommerce(): ?array
    {
        $user = CurrentUser::get();
        $isAdmin = $this->isAdminUser($user);

        // 1. Si admin avec un paramètre poi_id ou commerce_id
        if ($isAdmin) {
            if (!empty($_GET['commerce_id'])) {
                $c = $this->service->getCommerceById((int)$_GET['commerce_id']);
                if ($c) return $c;
            }
            if (!empty($_GET['poi_id'])) {
                $c = $this->service->getCommerceByPoiId((int)$_GET['poi_id']);
                if ($c) return $c;
            }
        }

        // 2. Si utilisateur connecté (Pro ou Propriétaire de POI)
        if ($user && $user->isLogged()) {
            $userId = method_exists($user, 'id') ? $user->id() : (isset($user->id) ? $user->id : null);
            if ($userId) {
                // Si l'utilisateur demande explicitement un poi_id ou commerce_id
                if (!empty($_GET['commerce_id'])) {
                    $c = $this->service->getCommerceById((int)$_GET['commerce_id']);
                    if ($c && ((int)($c['user_id'] ?? 0) === (int)$userId || $isAdmin)) {
                        return $c;
                    }
                }
                if (!empty($_GET['poi_id'])) {
                    $c = $this->service->getCommerceByPoiId((int)$_GET['poi_id']);
                    if ($c && ((int)($c['user_id'] ?? 0) === (int)$userId || $isAdmin)) {
                        return $c;
                    }
                }

                $commerce = $this->service->getCommerceByUserId((int)$userId, false);
                if ($commerce) {
                    return $commerce;
                }
            }
        }

        // 3. Fallback Admin : premier commerce existant
        if ($isAdmin) {
            $all = $this->service->getAllCommerces();
            if (!empty($all)) {
                return $all[0];
            }
        }

        return null;
    }

    /**
     * Tableau de bord Agenda Pro (/pro/rdv)
     */
    public function index()
    {
        $user = CurrentUser::get();
        $isLogged = ($user && $user->isLogged()) || $this->isAdminUser($user);
        if (!$isLogged) {
            header('Location: /login');
            exit;
        }

        $userId = ($user && method_exists($user, 'id')) ? $user->id() : (isset($user->id) ? $user->id : null);
        $commerce = $this->resolveCommerce();

        // Si l'utilisateur n'a pas encore de commerce RDV associé
        if (!$commerce) {
            $claimedPois = $userId ? $this->service->getUserClaimedPois((int)$userId) : [];

            $response = new PublicResponse();
            $response->setTitle("Activation de mon Agenda Pro RDV");

            $tpl = new Template();
            $html = '<div style="max-width:760px;margin:40px auto;padding:32px;background:#fff;border-radius:16px;border:1px solid #e2e8f0;box-shadow:0 4px 20px rgba(0,0,0,0.06);font-family:system-ui,-apple-system,sans-serif;">';
            $html .= '<div style="text-align:center;margin-bottom:28px;">';
            $html .= '<div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:#ecfdf5;color:#059669;font-size:1.8rem;margin-bottom:12px;"><i class="fa-solid fa-calendar-check"></i></div>';
            $html .= '<h1 style="color:#0f172a;font-size:1.75rem;font-weight:800;margin:0 0 8px;">Module Prise de Rendez-vous Pro</h1>';
            $html .= '<p style="color:#64748b;font-size:1rem;margin:0;">Permettez à vos clients de réserver vos prestations et créneaux en direct.</p>';
            $html .= '</div>';

            if (!empty($claimedPois)) {
                $html .= '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;">';
                $html .= '<h3 style="margin-top:0;margin-bottom:14px;color:#1e293b;font-size:1.1rem;"><i class="fa-solid fa-store" style="color:#059669;"></i> Vos établissements éligibles à l\'activation :</h3>';
                $html .= '<div style="display:flex;flex-direction:column;gap:12px;">';
                foreach ($claimedPois as $cp) {
                    $html .= '<div style="display:flex;align-items:center;justify-content:space-between;background:#fff;border:1px solid #cbd5e1;padding:14px 18px;border-radius:10px;">';
                    $html .= '<div><strong style="font-size:1.05rem;color:#0f172a;">' . htmlspecialchars($cp['name']) . '</strong><div style="font-size:0.85rem;color:#64748b;">ID #' . (int)$cp['id'] . '</div></div>';
                    $html .= '<form action="/pro/rdv/activate" method="post" style="margin:0;">';
                    $html .= '<input type="hidden" name="poi_id" value="' . (int)$cp['id'] . '">';
                    $html .= '<button type="submit" style="background:#10b981;color:#fff;border:none;padding:10px 18px;border-radius:8px;font-weight:700;font-size:0.9rem;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="fa-solid fa-bolt"></i> Activer en 1 clic (Gratuit - Pilote)</button>';
                    $html .= '</form>';
                    $html .= '</div>';
                }
                $html .= '</div></div>';
            } else {
                $html .= '<form action="/pro/rdv/activate" method="post" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px;">';
                $html .= '<h3 style="margin-top:0;margin-bottom:16px;color:#1e293b;font-size:1.1rem;"><i class="fa-solid fa-plus-circle" style="color:#059669;"></i> Créer votre agenda professionnel</h3>';
                $html .= '<div style="margin-bottom:14px;"><label style="display:block;font-weight:600;margin-bottom:6px;color:#334155;">Nom de votre établissement / activité</label>';
                $html .= '<input type="text" name="standalone_nom" required placeholder="Ex: Atelier Martin, Cabinet Conseil..." style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;"></div>';
                $html .= '<div style="margin-bottom:18px;"><label style="display:block;font-weight:600;margin-bottom:6px;color:#334155;">Profil métier</label>';
                $html .= '<select name="standalone_preset" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;">';
                foreach ($this->service->getPresets() as $k => $pr) {
                    $html .= '<option value="' . htmlspecialchars($k) . '">' . htmlspecialchars($pr['label']) . '</option>';
                }
                $html .= '</select></div>';
                $html .= '<button type="submit" style="background:#10b981;color:#fff;border:none;padding:12px 24px;border-radius:8px;font-weight:700;font-size:0.95rem;cursor:pointer;"><i class="fa-solid fa-bolt"></i> Initialiser mon agenda RDV</button>';
                $html .= '</form>';
            }

            $html .= '</div>';
            $tpl->setContent($html);
            $response->addTemplate($tpl);
            return $response;
        }

        $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['d'] ?? '') ? $_GET['d'] : date('Y-m-d');
        $prevDate = date('Y-m-d', strtotime("{$d} -1 day"));
        $nextDate = date('Y-m-d', strtotime("{$d} +1 day"));

        $liste = $this->service->getProAgenda((int)$commerce['id'], $d);

        $nbConfirmes = 0;
        $nbAttente = 0;
        foreach ($liste as &$rdv) {
            $rdv['sms_url'] = RdvService::generateSmsReminderUrl($rdv, $commerce['nom']);
            if (($rdv['statut'] ?? '') === 'confirme') $nbConfirmes++;
            if (($rdv['statut'] ?? '') === 'attente') $nbAttente++;
        }
        unset($rdv);

        $response = new PublicResponse();
        $response->setTitle("Mon Agenda Pro - {$commerce['nom']}");

        $tpl = new Template(ROOT . 'plugin/ProRdv/template/pro_agenda.tpl');
        $tpl->set('commerce', $commerce);
        $tpl->set('d', $d);
        $tpl->set('prev_date', $prevDate);
        $tpl->set('next_date', $nextDate);
        $tpl->set('liste', $liste);
        $tpl->set('nb_confirmes', $nbConfirmes);
        $tpl->set('nb_attente', $nbAttente);

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Active le module RDV pour un POI revendiqué par l'utilisateur connecté
     */
    public function activate()
    {
        $user = CurrentUser::get();
        $isLogged = ($user && $user->isLogged()) || $this->isAdminUser($user);
        if (!$isLogged) {
            header('Location: /login');
            exit;
        }

        $userId = ($user && method_exists($user, 'id')) ? (int)$user->id() : (isset($user->id) ? (int)$user->id : 0);
        $poiId = (int)($_REQUEST['poi_id'] ?? 0);
        $standaloneNom = trim($_POST['standalone_nom'] ?? '');
        $isAdmin = $this->isAdminUser($user);

        if ($poiId <= 0 && $standaloneNom !== '') {
            $presetKey = trim($_POST['standalone_preset'] ?? 'beaute');
            $userEmail = $user->email ?? '';
            $commerce = $this->service->createStandaloneCommerce($userId, $standaloneNom, $presetKey, $userEmail);
            if ($commerce) {
                header("Location: /pro/rdv/config?new=1&commerce_id=" . (int)$commerce['id']);
                exit;
            }
        }

        if ($poiId <= 0) {
            header('Location: /pro/rdv');
            exit;
        }

        // Vérification de légitimité : admin ou propriétaire vérifié
        $canActivate = $isAdmin;
        if (!$canActivate) {
            $claimedPois = $this->service->getUserClaimedPois($userId);
            foreach ($claimedPois as $p) {
                if ((int)$p['id'] === $poiId) {
                    $canActivate = true;
                    break;
                }
            }
        }

        if (!$canActivate) {
            exit("Accès refusé : vous devez être propriétaire de cet établissement pour activer le module.");
        }

        $commerce = $this->service->createOrGetForPoi($poiId, $userId);
        if ($commerce) {
            header("Location: /pro/rdv/config?new=1&commerce_id=" . (int)$commerce['id']);
            exit;
        }

        header('Location: /pro/rdv');
        exit;
    }

    /**
     * Action sur un rendez-vous (confirmer ou annuler)
     */
    public function action()
    {
        $commerce = $this->resolveCommerce();
        if (!$commerce) {
            exit('Accès refusé');
        }

        $rdvId = (int)($_POST['id'] ?? 0);
        $act = $_POST['act'] ?? '';
        $d = $_POST['d'] ?? date('Y-m-d');

        if ($rdvId > 0 && in_array($act, ['confirme', 'annule'], true)) {
            $this->service->updateStatus((int)$commerce['id'], $rdvId, $act);
        }

        header("Location: /pro/rdv?d=" . urlencode($d));
        exit;
    }

    /**
     * Configuration des prestations, collaborateurs, presets et horaires (/pro/rdv/config)
     */
    public function config()
    {
        $commerce = $this->resolveCommerce();
        if (!$commerce) {
            header('Location: /pro/rdv');
            exit;
        }

        $cid = (int)$commerce['id'];
        $msg = '';
        $msgType = 'success';
        $activeTab = $_GET['tab'] ?? 'prestations';

        // Traitement des actions POST
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $subaction = $_POST['subaction'] ?? '';
            $activeTab = $_POST['active_tab'] ?? $activeTab;

            switch ($subaction) {
                // 1. Appliquer un profil métier (Preset)
                case 'apply_preset':
                    $presetKey = trim($_POST['preset_key'] ?? '');
                    if ($this->service->applyPreset($cid, $presetKey)) {
                        $msg = "Le profil métier a été appliqué avec succès !";
                        $commerce = $this->service->getCommerceById($cid); // Recharger
                    } else {
                        $msg = "Profil métier introuvable.";
                        $msgType = 'danger';
                    }
                    $activeTab = 'presets';
                    break;

                // 2. Enregistrer les règles de réservation
                case 'save_settings':
                    $cfg = json_decode($commerce['config'] ?? '{}', true) ?: [];
                    if (isset($_POST['mode_validation'])) {
                        $cfg['mode_validation'] = in_array($_POST['mode_validation'], ['auto', 'manuel'], true) ? $_POST['mode_validation'] : ($cfg['mode_validation'] ?? 'auto');
                        $cfg['validation_auto'] = ($cfg['mode_validation'] === 'auto');
                    }
                    if (isset($_POST['delai_min_h'])) {
                        $cfg['delai_min_h'] = max(0, min(168, (int)$_POST['delai_min_h']));
                    }
                    if (isset($_POST['pas_min'])) {
                        $pas = (int)$_POST['pas_min'];
                        $cfg['pas_min'] = in_array($pas, [15, 20, 30, 45, 60], true) ? $pas : ($cfg['pas_min'] ?? 30);
                    }
                    if (isset($_POST['horizon_jours'])) {
                        $cfg['horizon_jours'] = max(7, min(180, (int)$_POST['horizon_jours']));
                    }
                    if (isset($_POST['notice'])) {
                        $cfg['notice'] = trim($_POST['notice']);
                    }
                    
                    // Champs personnalisés requis (uniquement si le formulaire de règles est soumis)
                    if (!empty($_POST['rules_form_submitted'])) {
                        $champsRequis = [];
                        foreach (['adresse', 'details', 'marque_modele', 'lieu_rdv', 'objet', 'remarque'] as $champKey) {
                            $champsRequis[$champKey] = !empty($_POST['champ_' . $champKey]);
                        }
                        $cfg['champs_requis'] = $champsRequis;
                    }

                    $nomCommerce = !empty($_POST['commerce_nom']) ? trim($_POST['commerce_nom']) : null;
                    if ($this->service->updateCommerceConfig($cid, $cfg, null, $nomCommerce)) {
                        $msg = "Vos paramètres de réservation ont bien été enregistrés !";
                        $commerce = $this->service->getCommerceById($cid);
                    } else {
                        $msg = "Erreur lors de l'enregistrement des paramètres.";
                        $msgType = 'danger';
                    }
                    $activeTab = 'rules';
                    break;

                // 2b. Basculer le choix du collaborateur (Onglet Équipe)
                case 'toggle_choix_collab':
                    $cfg = json_decode($commerce['config'] ?? '{}', true) ?: [];
                    $cfg['choix_collab'] = !empty($_POST['choix_collab']);
                    $this->service->updateCommerceConfig($cid, $cfg);
                    $commerce = $this->service->getCommerceById($cid);
                    $msg = $cfg['choix_collab'] ? "Choix du collaborateur activé pour vos clients !" : "Choix du collaborateur désactivé (réservation individuelle).";
                    $activeTab = 'equipe';
                    break;

                // 3. Prestations : Ajout
                case 'presta_add':
                    $nom = trim($_POST['presta_nom'] ?? '');
                    $duree = max(5, (int)($_POST['presta_duree'] ?? 30));
                    $prix = ($_POST['presta_prix'] !== '' && is_numeric($_POST['presta_prix'])) ? (float)$_POST['presta_prix'] : null;
                    $desc = trim($_POST['presta_desc'] ?? '');

                    if (!empty($nom)) {
                        $this->service->addPrestation($cid, $nom, $duree, $prix, $desc ?: null);
                        $msg = "Prestation « {$nom} » ajoutée avec succès !";
                    }
                    $activeTab = 'prestations';
                    break;

                // 4. Prestations : Modification
                case 'presta_edit':
                    $prestaId = (int)($_POST['presta_id'] ?? 0);
                    $nom = trim($_POST['presta_nom'] ?? '');
                    $duree = max(5, (int)($_POST['presta_duree'] ?? 30));
                    $prix = ($_POST['presta_prix'] !== '' && is_numeric($_POST['presta_prix'])) ? (float)$_POST['presta_prix'] : null;
                    $desc = trim($_POST['presta_desc'] ?? '');

                    if ($prestaId > 0 && !empty($nom)) {
                        $this->service->updatePrestation($cid, $prestaId, $nom, $duree, $prix, $desc ?: null);
                        $msg = "Prestation mise à jour !";
                    }
                    $activeTab = 'prestations';
                    break;

                // 5. Prestations : Activer / Désactiver
                case 'presta_toggle':
                    $prestaId = (int)($_POST['presta_id'] ?? 0);
                    if ($prestaId > 0) {
                        $this->service->togglePrestation($cid, $prestaId);
                        $msg = "Visibilité de la prestation modifiée.";
                    }
                    $activeTab = 'prestations';
                    break;

                // 6. Prestations : Suppression
                case 'presta_delete':
                    $prestaId = (int)($_POST['presta_id'] ?? 0);
                    if ($prestaId > 0) {
                        $this->service->deletePrestation($cid, $prestaId);
                        $msg = "Prestation supprimée avec succès.";
                    }
                    $activeTab = 'prestations';
                    break;

                // 7. Collaborateurs : Ajout
                case 'collab_add':
                    $nom = trim($_POST['collab_nom'] ?? '');
                    if (!empty($nom)) {
                        $this->service->addCollaborateur($cid, $nom);
                        $msg = "Collaborateur « {$nom} » ajouté à votre équipe !";
                    }
                    $activeTab = 'equipe';
                    break;

                // 8. Collaborateurs : Modification
                case 'collab_edit':
                    $collabId = (int)($_POST['collab_id'] ?? 0);
                    $nom = trim($_POST['collab_nom'] ?? '');
                    if ($collabId > 0 && !empty($nom)) {
                        $this->service->updateCollaborateur($cid, $collabId, $nom);
                        $msg = "Collaborateur mis à jour !";
                    }
                    $activeTab = 'equipe';
                    break;

                // 9. Collaborateurs : Activer / Désactiver
                case 'collab_toggle':
                    $collabId = (int)($_POST['collab_id'] ?? 0);
                    if ($collabId > 0) {
                        $this->service->toggleCollaborateur($cid, $collabId);
                        $msg = "Statut du collaborateur modifié.";
                    }
                    $activeTab = 'equipe';
                    break;

                // 10. Collaborateurs : Suppression
                case 'collab_delete':
                    $collabId = (int)($_POST['collab_id'] ?? 0);
                    if ($collabId > 0) {
                        $this->service->deleteCollaborateur($cid, $collabId);
                        $msg = "Collaborateur retiré de l'équipe.";
                    }
                    $activeTab = 'equipe';
                    break;

                // 11. Horaires hebdomadaires
                case 'save_horaires':
                    $horairesPost = $_POST['horaires'] ?? [];
                    $formattedHoraires = [];
                    for ($j = 1; $j <= 7; $j++) {
                        $formattedHoraires[$j] = [];
                        if (!empty($horairesPost[$j]['active'])) {
                            // Plage matin
                            $mDeb = trim($horairesPost[$j]['m_deb'] ?? '');
                            $mFin = trim($horairesPost[$j]['m_fin'] ?? '');
                            if (!empty($mDeb) && !empty($mFin) && $mDeb < $mFin) {
                                $formattedHoraires[$j][] = ['debut' => $mDeb, 'fin' => $mFin];
                            }
                            // Plage après-midi
                            $aDeb = trim($horairesPost[$j]['a_deb'] ?? '');
                            $aFin = trim($horairesPost[$j]['a_fin'] ?? '');
                            if (!empty($aDeb) && !empty($aFin) && $aDeb < $aFin) {
                                $formattedHoraires[$j][] = ['debut' => $aDeb, 'fin' => $aFin];
                            }
                        }
                    }
                    if ($this->service->saveHoraires($cid, $formattedHoraires)) {
                        $msg = "Grille horaire hebdomadaire mise à jour avec succès !";
                    } else {
                        $msg = "Erreur lors de l'enregistrement des horaires.";
                        $msgType = 'danger';
                    }
                    $activeTab = 'horaires';
                    break;

                // 12. Activer / Désactiver le module complet
                case 'toggle_active':
                    $this->service->toggleCommerceActive($cid);
                    $commerce = $this->service->getCommerceById($cid);
                    $msg = !empty($commerce['is_active']) ? "Module RDV activé pour votre établissement !" : "Module RDV temporairement mis en pause.";
                    break;
            }
        }

        if (!empty($_GET['new'])) {
            $msg = "Félicitations ! Votre module de rendez-vous a été initialisé. Personnalisez votre profil métier et vos prestations ci-dessous.";
        }

        $allPrestas = $this->service->getAllPrestations($cid);
        $allCollabs = $this->service->getAllCollaborateurs($cid);
        $horaires = $this->service->getHoraires($cid);
        $presets = $this->service->getPresets();
        $cfg = json_decode($commerce['config'] ?? '{}', true) ?: [];

        // Préparer une structure lisible pour les horaires 1..7 (Lundi..Dimanche)
        $joursSemaine = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];

        $horairesDisplay = [];
        foreach ($joursSemaine as $jNum => $jNom) {
            $plages = $horaires[$jNum] ?? [];
            $isOpen = !empty($plages);
            $p1 = $plages[0] ?? ['debut' => '09:00', 'fin' => '12:00'];
            $p2 = $plages[1] ?? ['debut' => '14:00', 'fin' => '18:00'];
            $horairesDisplay[] = [
                'num'    => $jNum,
                'nom'    => $jNom,
                'open'   => $isOpen,
                'm_deb'  => $p1['debut'] ?? '09:00',
                'm_fin'  => $p1['fin'] ?? '12:00',
                'a_deb'  => (isset($plages[1]) ? $p2['debut'] : '14:00'),
                'a_fin'  => (isset($plages[1]) ? $p2['fin'] : '18:00'),
                'has_p2' => count($plages) > 1,
            ];
        }

        $response = new PublicResponse();
        $response->setTitle("Configuration de mes Rendez-vous - {$commerce['nom']}");

        $tpl = new Template(ROOT . 'plugin/ProRdv/template/pro_config.tpl');
        $tpl->set('commerce', $commerce);
        $tpl->set('cfg', $cfg);
        $tpl->set('presets', $presets);
        $tpl->set('prestas', $allPrestas);
        $tpl->set('collabs', $allCollabs);
        $tpl->set('horaires_list', $horairesDisplay);
        $tpl->set('active_tab', $activeTab);
        $tpl->set('msg', $msg);
        $tpl->set('msg_type', $msgType);

        $response->addTemplate($tpl);
        return $response;
    }
}
