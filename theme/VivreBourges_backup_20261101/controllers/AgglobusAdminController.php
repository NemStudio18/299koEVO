<?php

namespace Agglobus\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;

class AgglobusAdminController extends AdminController
{
    public function home()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('Agglobus', 'admin/home');
        $response->setTitle('Agglobus Administration');
        $response->addTemplate($tpl);
        return $response;
    }
}
