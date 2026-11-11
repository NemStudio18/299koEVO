<?php

namespace PoiData\Controllers;

use Core\Controllers\AdminController as BaseAdminController;
use Core\Responses\AdminResponse;

class AdminController extends BaseAdminController
{
    public function home()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('poi-data', 'param');
        $response->addTemplate($tpl);
        return $response;
    }
}
