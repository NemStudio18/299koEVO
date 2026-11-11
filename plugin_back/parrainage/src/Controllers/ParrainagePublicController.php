<?php

namespace Parrainage\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Responses\StringResponse;
use Core\Auth\UsersManager;
use Parrainage\Services\ParrainageService;

defined('ROOT') or exit('Access denied!');

class ParrainagePublicController extends PublicController
{
    private ParrainageService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ParrainageService();
    }

    /**
     * Dashboard du Parrain (Public).
     */
    public function dashboard()
    {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            $this->core->redirect($this->router->generate('users-login'));
            return new StringResponse(''); 
        }

        $userId = (int)$user->attributes['id'];
        $userEmail = $user->attributes['email'];

        // 1. Vérifier si l'utilisateur possède l'addon actif ou un code pré-existant
        // getOrCreateUserCode se charge de vérifier l'addon si aucun code n'existe
        $userCode = $this->service->getOrCreateUserCode($userId, $userEmail);

        if (!$userCode) {
            // Si pas de code et pas d'addon actif, on affiche une page d'incitation
            $response = new PublicResponse();
            $tpl = $response->createPluginTemplate('parrainage', 'public_no_access');
            $tpl->set('user', $user);
            $response->setTitle('Mon Espace Parrainage');
            $response->addTemplate($tpl);
            return $response;
        }

        $codes = $this->service->getCodesByUser($userId, $userEmail);

        // Pour chaque code, on récupère les détails (filleuls, commissions)
        foreach ($codes as &$code) {
            $details = $this->service->getCodeDetails((int)$code['id']);
            $code['referrals'] = $details['referrals'] ?? [];
            $code['total_estimated_monthly'] = $details['total_estimated_monthly'] ?? 0;
        }

        // On utilise la vue publique du plugin via PublicResponse
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('parrainage', 'public_dashboard');
        
        $tpl->set('codes', $codes);
        $tpl->set('user', $user);
        
        $response->setTitle('Mon Espace Parrainage');
        $response->addTemplate($tpl);
        
        return $response;
    }
}
