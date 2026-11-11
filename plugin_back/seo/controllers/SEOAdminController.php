<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;
use Seo\Entities\SocialConfigManager;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class SEOAdminController extends AdminController {
    
    public function home() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('seo:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('seo', 'admin');

        $tpl->set('position', $this->runPlugin->getConfigVal('position'));
        $tpl->set('socialConfig', SocialConfigManager::getAll());
        $tpl->set('shareNetworks', ['facebook','x','linkedin']);
        $tpl->set('redirects', \Seo\Entities\Redirect::all());

        $response->addTemplate($tpl);
        return $response;
    }

    public function save() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('seo:admin'), $ctx)) {
            return $this->home();
        }
        $pos = $_POST['position'] ?? 'menu';
        $this->seoSavePositionMenu($pos);

        $this->runPlugin->setConfigVal('position', $pos);
        $this->runPlugin->setConfigVal('trackingId', trim($_POST['trackingId'] ?? ''));
        $this->runPlugin->setConfigVal('wt', trim($_POST['wt'] ?? ''));
        $this->runPlugin->setConfigVal('defaultOgImage', trim($_POST['defaultOgImage'] ?? ''));

        // Save social addresses
        $vars = seoGetSocialVars();
        foreach ($vars as $v) {
            $this->runPlugin->setConfigVal($v, trim($_POST[$v] ?? ''));
        }

        // Save new redirection if provided
        if (!empty($_POST['new_redirect_from']) && !empty($_POST['new_redirect_to'])) {
            $rd = new \Seo\Entities\Redirect();
            $rd->old_url = '/' . ltrim(trim($_POST['new_redirect_from']), '/');
            $rd->new_url = trim($_POST['new_redirect_to']);
            $rd->code = (int)$_POST['new_redirect_code'];
            $rd->save();
        }

        // Save extended config
        $cfg = SocialConfigManager::getAll();
        foreach (['facebook','x','linkedin'] as $network) {
            if (!isset($cfg[$network]) || !is_array($cfg[$network])) {
                $cfg[$network] = [];
            }
            $cfg[$network]['enabled'] = !empty($_POST[$network . '_enabled']);
            switch ($network) {
                case 'facebook':
                    $cfg[$network]['appId']       = trim($_POST['facebook_appId'] ?? '');
                    $cfg[$network]['appSecret']   = trim($_POST['facebook_appSecret'] ?? '');
                    $cfg[$network]['accessToken'] = trim($_POST['facebook_accessToken'] ?? '');
                    break;
                case 'x':
                    $cfg[$network]['bearerToken'] = trim($_POST['x_bearerToken'] ?? '');
                    break;
                case 'linkedin':
                    $cfg[$network]['clientId']    = trim($_POST['linkedin_clientId'] ?? '');
                    $cfg[$network]['clientSecret']= trim($_POST['linkedin_clientSecret'] ?? '');
                    $cfg[$network]['accessToken'] = trim($_POST['linkedin_accessToken'] ?? '');
                    break;
            }
        }

        $cfg['languages'] = array_values(array_filter(array_map('trim', explode(',', $_POST['languages'] ?? ''))));

        $pluginSaved = $this->pluginsManager->savePluginConfig($this->runPlugin);
        $socialSaved = SocialConfigManager::saveAll($cfg);

        if ($pluginSaved && $socialSaved) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }
        $this->core->redirect($this->router->generate('seo-admin-home'));
    }

    public function generateSitemap()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('seo:admin'), $ctx)) {
            return $this->home();
        }
        $code = seoGenerateSitemap();
        if ($code === 0) {
            Show::msg(Lang::get('seo.sitemap.generated'), 'success');
        } else {
            Show::msg(Lang::get('seo.sitemap.error'), 'error');
        }
        $this->core->redirect($this->router->generate('seo-admin-home'));
    }

    public function deleteRedirect($id)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('seo:admin'), $ctx)) {
            return $this->home();
        }
        $rd = \Seo\Entities\Redirect::findPK($id);
        if ($rd) {
            $rd->delete();
            Show::msg('Redirection supprimée.', 'success');
        }
        $this->core->redirect($this->router->generate('seo-admin-home'));
    }

    protected function seoSavePositionMenu($position) {
        $hooksFile = PLUGINS . 'seo/param/hooks.json';
        $existingHooks = [];
        if (file_exists($hooksFile)) {
            $existingHooks = Util::readJsonFile($hooksFile);
            if (!is_array($existingHooks)) {
                $existingHooks = [];
            }
        }

        // Remove old display hooks
        $displayHooks = ['endMainNavigation', 'footer', 'endFooter', 'endFrontBody'];
        foreach ($displayHooks as $hook) {
            unset($existingHooks[$hook]);
        }

        $arr = ['endFrontHead' => 'seoEndFrontHead'];
        switch ($position) {
            case 'menu':
                $tmp = ['endMainNavigation' => 'seoMainNavigation'];
                break;
            case 'footer':
                $tmp = ['footer' => 'seoFooter'];
                break;
            case 'endfooter':
                $tmp = ['endFooter' => 'seoFooter'];
                break;
            case 'float':
                $tmp = ['endFrontBody' => 'seoEndFrontBody'];
                break;
            default:
                $tmp = [];
        }
        $data = array_merge($existingHooks, $arr, $tmp);
        Util::writeJsonFile($hooksFile, $data);
    }
}