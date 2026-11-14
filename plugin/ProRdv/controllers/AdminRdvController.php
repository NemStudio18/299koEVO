<?php

namespace ProRdv\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use ProRdv\Services\RdvService;
use Template\Template;

defined('ROOT') or exit('Access denied!');

class AdminRdvController extends AdminController
{
    private RdvService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = RdvService::getInstance();
    }

    /**
     * Tableau de bord Administrateur ProRdv (/admin/prordv)
     */
    public function index()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !empty($_POST['standalone_nom'])) {
            $nom = trim($_POST['standalone_nom']);
            $preset = trim($_POST['standalone_preset'] ?? 'beaute');
            $email = trim($_POST['standalone_email'] ?? '');
            $tel = trim($_POST['standalone_tel'] ?? '') ?: null;
            $userId = ($this->user && isset($this->user->id)) ? (int)$this->user->id : 1;
            $this->service->createStandaloneCommerce($userId, $nom, $preset, $email, $tel);
            \Utils\Show::msg("Le nouvel agenda « {$nom} » a été créé avec succès.", 'success');
            \Core\Core::getInstance()->redirect($this->router->generate('rdv-admin-index'));
        }

        $commerces = $this->service->getAllCommerces();

        $response = new AdminResponse();
        $response->setTitle("Administration ProRdv");

        $tpl = $response->createPluginTemplate('ProRdv', 'admin_overview');
        $tpl->set('commerces', $commerces);
        $tpl->set('presets', $this->service->getPresets());

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Consultation / Supervision d'un commerce spécifique
     */
    public function viewCommerce(int $id)
    {
        $commerce = $this->service->getCommerceById($id);
        if (!$commerce) {
            \Utils\Show::msg("Établissement introuvable.", 'error');
            \Core\Core::getInstance()->redirect($this->router->generate('rdv-admin-index'));
        }

        $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['d'] ?? '') ? $_GET['d'] : date('Y-m-d');
        $rdvs = $this->service->getProAgenda($id, $d);
        foreach ($rdvs as &$rdv) {
            $rdv['sms_url'] = RdvService::generateSmsReminderUrl($rdv, $commerce['nom']);
        }
        unset($rdv);

        $response = new AdminResponse();
        $response->setTitle("Gestion RDV : {$commerce['nom']}");

        $tpl = $response->createPluginTemplate('ProRdv', 'pro_agenda');
        $tpl->set('commerce', $commerce);
        $tpl->set('d', $d);
        $tpl->set('prev_date', date('Y-m-d', strtotime("{$d} -1 day")));
        $tpl->set('next_date', date('Y-m-d', strtotime("{$d} +1 day")));
        $tpl->set('liste', $rdvs);
        $tpl->set('is_admin_view', true);

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Activation / Désactivation d'un commerce
     */
    public function toggleCommerce(int $id)
    {
        $this->service->toggleCommerceActive($id);
        \Utils\Show::msg("Statut de l'agenda mis à jour.", 'success');
        \Core\Core::getInstance()->redirect($this->router->generate('rdv-admin-index'));
    }

    /**
     * Suppression d'un commerce
     */
    public function deleteCommerce(int $id)
    {
        $pdo = \Core\Database::getInstance()->getPdo();
        $pdo->prepare("DELETE FROM `lbp_rdv_rendez_vous` WHERE `commerce_id` = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM `lbp_rdv_horaires` WHERE `commerce_id` = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM `lbp_rdv_prestations` WHERE `commerce_id` = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM `lbp_rdv_collaborateurs` WHERE `commerce_id` = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM `lbp_rdv_commerces` WHERE `id` = ?")->execute([$id]);
        \Utils\Show::msg("Agenda supprimé avec succès.", 'success');
        \Core\Core::getInstance()->redirect($this->router->generate('rdv-admin-index'));
    }
}
