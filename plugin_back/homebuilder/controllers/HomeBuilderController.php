<?php

namespace HomeBuilder\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use HomeBuilder\Entities\BlockManager;

defined('ROOT') or exit('Access denied!');

class HomeBuilderController extends PublicController
{
    public function home()
    {
        $manager = new BlockManager();
        $blocks = $manager->getActiveBlocks();

        // Définir les métas de la page d'accueil
        $label = $this->runPlugin ? $this->runPlugin->getConfigVal('label') : 'Accueil';
        $this->runPlugin?->setTitleTag($label ?: 'Accueil');
        if ($this->runPlugin && $this->runPlugin->getIsDefaultPlugin()) {
            $this->runPlugin->setMetaDescriptionTag($this->core->getConfigVal('siteDescription'));
        }

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('homebuilder', 'index');

        $tpl->set('blocks', $blocks);
        $tpl->set('blockManager', $manager);

        $response->addTemplate($tpl);
        $response->setTitle($label ?: 'Accueil');

        return $response;
    }
}