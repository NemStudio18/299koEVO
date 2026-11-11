<?php

namespace SearchUI\Controllers;

use Core\Controllers\AdminController as BaseAdminController;
use Core\Responses\AdminResponse;

class AdminController extends BaseAdminController
{
    public function home()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('SearchUi', 'param');
        $response->addTemplate($tpl);
        return $response;
    }
}
