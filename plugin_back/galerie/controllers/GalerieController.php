<?php

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;

/**
 * @copyright (C) 2023, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class GalerieController extends PublicController
{

    public function home()
    {
        $galerie = new galerie();
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-image"></i> ' . ($this->runPlugin->getConfigVal('label') ?: 'Galerie'));

        $tpl = $response->createPluginTemplate('galerie', 'galerie');

        $tpl->set('galerie', $galerie);

        $response->addTemplate($tpl);
        return $response;
    }
}