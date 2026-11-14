<?php

defined('ROOT') OR exit('Access denied!');

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Core\Core;
use Utils\Util;

class PaiementAdminController extends AdminController
{
    public function home(): AdminResponse
    {
        $response = new AdminResponse();
        $response->setTitle('<i class="fa-solid fa-credit-card"></i> Configuration paiement');
        $tpl = $response->createPluginTemplate('paiement', 'admin_config');
        $tpl->set('plugin', $this->runPlugin);
        $tpl->set('router', $this->router);
        $response->addTemplate($tpl);
        return $response;
    }

    public function saveConfig(): AdminResponse
    {
        $configFile = DATA_PLUGIN . 'paiement/config.json';
        $config = Util::readJsonFile($configFile);
        if (!is_array($config)) $config = [];

        $fields = [
            'stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret',
            'paypal_client_id', 'paypal_client_secret', 'paypal_mode',
            'mollie_api_key',
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $config[$field] = trim($_POST[$field]);
            }
        }

        Util::writeJsonFile($configFile, $config);
        Show::msg(Lang::get('paiement.config-saved'), 'success');
        return $this->home();
    }
}
