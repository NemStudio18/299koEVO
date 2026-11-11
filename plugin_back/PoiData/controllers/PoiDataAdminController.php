<?php

namespace PoiData\Controllers;

use Core\Controllers\AdminController as BaseAdminController;
use Core\Responses\AdminResponse;

defined('ROOT') or exit('Access denied!');

class PoiDataAdminController extends BaseAdminController
{
    public function index()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('PoiData', 'param');
        $response->addTemplate($tpl);
        return $response;
    }
}
