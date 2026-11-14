<?php

namespace Core\Settings\Controllers;

use Core\Controllers\AdminController;
use Core\Settings\UpdaterManager;
use Core\Settings\UpdaterManualManager;
use Core\Lang;
use Utils\Show;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class ConfigManagerUpdateController extends AdminController {

    public function process($token) {
        $actor = $this->user->email ?? 'unknown';
        $updaterManager = new UpdaterManager();
        if ($updaterManager->isReady) {
            $nextVersion = $updaterManager->getNextVersion();
        } else {
            $nextVersion = false;
        }
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if ($nextVersion && $evaluator->isAdmin($ctx)) {
            $isPatch = $updaterManager->isSecurityPatch();
            $label = $isPatch ? ('patch ' . substr((string) $nextVersion, 0, 7)) : ('v' . $nextVersion);
            if ($updaterManager->update()) {
                $this->logger->info('Config update applied to ' . $label . ' by ' . $actor);
                show::msg($isPatch ? "Le correctif de sécurité {$label} a été appliqué avec succès." : Lang::get('configmanager-updated', $nextVersion), 'success');
                $updaterManager->clearCache();
            } else {
                $this->logger->error('Config update failed for ' . $nextVersion . ' by ' . $actor);
                show::msg(Lang::get('configmanager-update-error'), 'error');
            }
            $this->core->redirect($this->router->generate('configmanager-admin'));
        }
        $this->core->redirect($this->router->generate('configmanager-admin'));
    }

    public function processManual($token) {
        $actor = $this->user->email ?? 'unknown';
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            $this->logger->warning('Config manual update attempt blocked for unauthorized user ' . $actor);
            $this->core->redirect($this->router->generate('configmanager-admin'));
        }
        if (!is_dir(ROOT .'update')) {
            $this->logger->warning('Config manual update failed - update directory missing for ' . $actor);
            show::msg(Lang::get('configmanager-update-dir-not-found'), 'error');
            $this->core->redirect($this->router->generate('configmanager-admin'));
        }
        $updater = new UpdaterManualManager();
        if ($updater->check()) {
            $nextVersion = $updater->getNextVersion();
            $updater->update();
            $this->logger->info('Config manual update applied to ' . $nextVersion . ' by ' . $actor);
            show::msg(Lang::get('configmanager-updated', $nextVersion ), 'success');
            $this->core->redirect($this->router->generate('configmanager-admin'));
        } else {
            $this->logger->warning('Config manual update failed during check for ' . $actor);
            show::msg(Lang::get('configmanager-update-error'), 'error');
            $this->core->redirect($this->router->generate('configmanager-admin'));
        }
    }

    /**
     * Force an immediate core update check bypassing the 1-hour cache.
     */
    public function checkNow(string $token): void
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx) || $this->user->token !== $token) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('configmanager-admin'));
            return;
        }

        $settings = new \Core\Settings\SettingsService();
        $nextVersion = $settings->getNewVersion();
        if ($nextVersion) {
            $settings->displayNewVersion((string) $nextVersion);
            // Mark as notified so the redirect to configmanager-admin does not trigger a duplicate message
            $cacheFile = $settings->cacheFile();
            $cached = \Utils\Util::readJsonFile($cacheFile);
            if (is_array($cached)) {
                $cached['lastNotifiedUpdate'] = (string) $nextVersion;
                \Utils\Util::writeJsonFile($cacheFile, $cached);
            }
        } else {
            $current = defined('VERSION') ? VERSION : '';
            $updater = new UpdaterManager();
            $commit = $updater->getLocalCommit();
            $commitSuffix = $commit ? ' (patch ' . substr($commit, 0, 7) . ')' : '';
            Show::msg('Votre CMS est à jour' . ($current !== '' ? ' (version ' . htmlspecialchars($current . $commitSuffix, ENT_QUOTES, 'UTF-8') . ')' : '') . '.', 'success');
        }
        $this->core->redirect($this->router->generate('configmanager-admin'));
    }

}