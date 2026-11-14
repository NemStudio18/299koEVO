<?php

namespace ProRdv\Hooks;

use ProRdv\Services\RdvService;

defined('ROOT') or exit('Access denied!');

class ProRdvHooks
{
    /**
     * Hook poi:detail-actions
     * Injecte les boutons adaptés aux rôles sur la fiche POI :
     * - Pour l'Admin : (A) Gérer RDV
     * - Pour le Pro/Propriétaire : 📅 Mon Agenda RDV
     * - Pour le Visiteur : 📅 Prendre Rendez-vous
     */
    public static function onPoiDetailActions($params): string
    {
        $poi = $params['poi'] ?? null;
        if (!$poi) return '';

        $poiId = is_array($poi) ? ($poi['id'] ?? 0) : (property_exists($poi, 'id') ? $poi->id : 0);
        if (!$poiId) return '';

        $isAdmin = !empty($params['is_admin']);
        $isOwner = !empty($params['is_owner']);

        $service = RdvService::getInstance();
        $commerce = $service->getCommerceByPoiId((int)$poiId);

        // Si aucun commerce RDV n'est associé à ce POI ou s'il est inactif, ne rien afficher
        if (!$commerce || empty($commerce['is_active'])) {
            return '';
        }

        $html = '';

        // 1. Bouton Public "Prendre Rendez-vous" (pour les clients)
        if (!$isOwner) {
            $html .= '<a href="/rdv/' . htmlspecialchars($commerce['slug']) . '" class="button-search button rdv-book-btn" style="background:#10b981;color:#fff;font-weight:600;" title="Prendre rendez-vous en ligne">'
                   . '<i class="fa-solid fa-calendar-check"></i> <span class="button-text">Prendre RDV</span>'
                   . '</a>';
        }

        // 2. Bouton Propriétaire / Pro "Mon Agenda RDV"
        if ($isOwner) {
            $html .= '<a href="/pro/rdv?poi_id=' . $poiId . '" class="button-search button rdv-pro-btn" style="background:#059669;color:#fff;" title="Consulter mon agenda et mes rendez-vous">'
                   . '<i class="fa-solid fa-calendar-days"></i> <span class="button-text">📅 Mon Agenda RDV</span>'
                   . '</a>';
        }

        // 3. Bouton Admin "(A) Gérer RDV"
        if ($isAdmin && !$isOwner) {
            $html .= '<a href="/admin/prordv/commerce/' . $commerce['id'] . '" class="button-search button rdv-admin-btn" style="background:#047857;color:#fff;" title="Superviser les rendez-vous de ce commerce">'
                   . '<i class="fa-solid fa-calendar-week"></i> <span class="button-text">(A) Gérer RDV</span>'
                   . '</a>';
        }

        return $html;
    }

    /**
     * Hook poi:detail-tabs — Ajoute l'onglet Rendez-vous dans la navigation de la fiche
     */
    public static function onPoiDetailTabs($poi): void
    {
        if (!$poi) return;
        $poiId = is_array($poi) ? ($poi['id'] ?? 0) : (property_exists($poi, 'id') ? $poi->id : 0);
        if (!$poiId) return;

        $service = RdvService::getInstance();
        $commerce = $service->getCommerceByPoiId((int)$poiId);
        if (!$commerce || empty($commerce['is_active'])) return;

        echo '<button type="button" class="detail-tab-button" role="tab" aria-selected="false" data-tab="rendez-vous">'
           . '<i class="fa-solid fa-calendar-check" style="color: #10b981;"></i> <span>Rendez-vous</span>'
           . '</button>';
    }

    /**
     * Hook poi:detail-tab-content — Injecte le widget de prise de RDV dans la fiche
     */
    public static function onPoiDetailTabContent($poi): void
    {
        if (!$poi) return;
        $poiId = is_array($poi) ? ($poi['id'] ?? 0) : (property_exists($poi, 'id') ? $poi->id : 0);
        if (!$poiId) return;

        $service = RdvService::getInstance();
        $commerce = $service->getCommerceByPoiId((int)$poiId);
        if (!$commerce || empty($commerce['is_active'])) return;

        $prestas = $service->getPrestations((int)$commerce['id']);
        echo '<div class="detail-tab-pane" id="tab-rendez-vous" role="tabpanel" style="display:none;padding:20px 0;">'
           . '<div style="background:#fff;border-radius:12px;padding:24px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,0.05);">'
           . '<h3 style="font-size:1.25rem;font-weight:700;margin-top:0;margin-bottom:12px;color:#1e293b;">📅 Prendre rendez-vous chez ' . htmlspecialchars($commerce['nom']) . '</h3>'
           . '<p style="color:#64748b;margin-bottom:18px;">Choisissez une prestation pour consulter les créneaux disponibles en direct :</p>'
           . '<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;">';

        foreach ($prestas as $p) {
            $prix = $p['prix'] !== null ? ' • ' . (float)$p['prix'] . ' €' : '';
            echo '<a href="/rdv/' . htmlspecialchars($commerce['slug']) . '?p=' . $p['id'] . '" class="btn" style="display:inline-block;padding:10px 16px;background:#10b981;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.95rem;">'
               . htmlspecialchars($p['nom']) . ' (' . $p['duree_min'] . ' min' . $prix . ')'
               . '</a>';
        }

        echo '</div>'
           . '<p><a href="/rdv/' . htmlspecialchars($commerce['slug']) . '" style="color:#059669;font-weight:600;text-decoration:underline;">Ouvrir le module complet de réservation &rarr;</a></p>'
           . '</div>'
           . '</div>';
    }

    /**
     * Hook user:avatar-menu — Injecte un lien vers l'agenda Pro dans le menu avatar utilisateur
     */
    public static function onUserAvatarMenu($data): array
    {
        if (!is_array($data)) {
            $data = ['items' => []];
        }
        if (!isset($data['items']) || !is_array($data['items'])) {
            $data['items'] = [];
        }

        $currentUser = \Core\Auth\CurrentUser::get();
        if (!$currentUser || !$currentUser->isLogged()) {
            return $data;
        }

        // Si l'utilisateur est admin ou possède un commerce avec RDV activé
        $isAdmin = (defined('ADMIN_MODE') && ADMIN_MODE)
            || (defined('IS_ADMIN') && IS_ADMIN)
            || (method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin'))
            || (method_exists($currentUser, 'can_access') && $currentUser->can_access('system:admin-access'))
            || ((int)($currentUser->attributes['group_id'] ?? 0) === 1);
        
        $hasActiveCommerce = false;
        $service = RdvService::getInstance();
        $userId = method_exists($currentUser, 'id') ? $currentUser->id() : (isset($currentUser->id) ? $currentUser->id : null);
        if ($userId) {
            $commerce = $service->getCommerceByUserId((int)$userId);
            if ($commerce && !empty($commerce['is_active'])) {
                $hasActiveCommerce = true;
            }
        }

        // Le lien n'apparaît QUE si le pro a un commerce activé, ou si admin
        if ($hasActiveCommerce || $isAdmin) {
            $data['items'][] = [
                'label' => 'Mon Agenda Pro (RDV)',
                'url'   => '/pro/rdv',
                'icon'  => 'fa-solid fa-calendar-days',
                'class' => 'avatar-menu-item rdv-pro-link',
                'order' => 88
            ];
        }

        return $data;
    }
}
