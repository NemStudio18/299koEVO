<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class AntispamAdminController extends AdminController
{
    public function home() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('antispam:admin'), $ctx)) {
            $this->core->error404();
        }
        $stats = AntispamStats::getStats();
        $blocked = (int) ($stats['blocked_total'] ?? 0);
        $passed = (int) ($stats['passed_total'] ?? 0);
        $total = $blocked + $passed;
        $blockRate = $total > 0 ? round(($blocked / $total) * 100) : 0;

        $reasons = [];
        foreach ((array) ($stats['by_reason'] ?? []) as $code => $cnt) {
            $reasons[] = [
                'code'  => $code,
                'label' => AntispamStats::reasonLabel((string) $code),
                'count' => (int) $cnt,
            ];
        }
        usort($reasons, fn($a, $b) => $b['count'] <=> $a['count']);

        $recent = [];
        foreach ((array) ($stats['recent'] ?? []) as $entry) {
            $recent[] = [
                'date'         => (string) ($entry['date'] ?? ''),
                'status'       => (string) ($entry['status'] ?? 'blocked'),
                'is_blocked'   => (($entry['status'] ?? '') === 'blocked'),
                'method_label' => AntispamStats::methodLabel((string) ($entry['method'] ?? '')),
                'reason_label' => AntispamStats::reasonLabel((string) ($entry['reason'] ?? '')),
                'uri'          => (string) ($entry['uri'] ?? ''),
                'ip'           => (string) ($entry['ip'] ?? ''),
            ];
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('antispam', 'config');
        $tpl->set('useText', ($this->runPlugin->getConfigVal('type') === 'useText') ? 'checked' : '' );
        $tpl->set('useIcon', ($this->runPlugin->getConfigVal('type') === 'useIcon') ? 'checked' : '' );
        $tpl->set('useRecaptcha', ($this->runPlugin->getConfigVal('type') === 'useRecaptcha') ? 'checked' : '' );
        $tpl->set('useFlexybot', ($this->runPlugin->getConfigVal('type') === 'useFlexybot') ? 'checked' : '' );
        $tpl->set('activeMethodLabel', AntispamStats::methodLabel((string) ($this->runPlugin->getConfigVal('type') ?: 'useText')));
        $tpl->set('blockedTotal', $blocked);
        $tpl->set('passedTotal', $passed);
        $tpl->set('blockRate', $blockRate);
        $tpl->set('reasons', $reasons);
        $tpl->set('recentLogs', $recent);
        $tpl->set('clearStatsUrl', $this->router->generate('antispam-clear-stats'));
        $response->addTemplate($tpl);
        return $response;
    }

    public function clearStats() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('antispam:admin'), $ctx)) {
            $this->core->error404();
        }
        if (($this->user->token ?? '') !== ($_POST['token'] ?? '')) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('antispam-admin') . '?tab=logs');
            return;
        }
        AntispamStats::clear();
        Show::msg(Lang::get('core-changes-saved'), 'success');
        $this->core->redirect($this->router->generate('antispam-admin') . '?tab=logs');
    }

    public function save() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('antispam:admin'), $ctx)) {
            $this->core->error404();
        }
        if ($_POST['captcha'] === 'useRecaptcha') {
            // Use ReCaptcha
            if (!isset($_POST['recaptchaPublicKey']) || !isset($_POST['recaptchaSecretKey']) ||
                    trim($_POST['recaptchaPublicKey']) == '' || trim($_POST['recaptchaSecretKey']) == '') {
                // Empty keys
                Show::msg(Lang::get('antispam.google-captcha-empty'), 'error');
                $this->core->redirect($this->router->generate('antispam-admin'));
            }
            // Save ReCaptcha
            $this->runPlugin->setConfigVal('recaptchaPublicKey', trim($_POST['recaptchaPublicKey']));
            $this->runPlugin->setConfigVal('recaptchaSecretKey', trim($_POST['recaptchaSecretKey']));
        }
        // Save Type
        $this->runPlugin->setConfigVal('type', trim($_POST['captcha']));
        $this->pluginsManager->savePluginConfig($this->runPlugin);

        Show::msg(Lang::get('core-changes-saved'), 'success');
        $this->core->redirect($this->router->generate('antispam-admin'));
    }
}