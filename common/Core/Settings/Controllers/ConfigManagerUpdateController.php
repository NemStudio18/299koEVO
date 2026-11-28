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
        @ini_set('max_execution_time', '180');
        @set_time_limit(180);
        $actor = $this->user->email ?? 'unknown';
        $updaterManager = new UpdaterManager(true);
        $nextVersion = $updaterManager->getNextVersion();
        $settings = \Core\Core::getInstance()->settings();
        $cacheFile = $settings->cacheFile();

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if ($nextVersion && $evaluator->isAdmin($ctx)) {
            $isPatch = $updaterManager->isSecurityPatch();
            $label = $isPatch ? ('patch ' . substr((string) $nextVersion, 0, 7)) : ('v' . $nextVersion);
            
            // Snapshot automatique pré-mise à jour Core (fichiers système: admin, common, index.php)
            \Core\Settings\SettingsService::createAutoSnapshot('core', 'pre-update-core-' . ($isPatch ? 'patch' : ('v' . $nextVersion)));

            if ($updaterManager->update()) {
                $this->logger->info('Config update applied to ' . $label . ' by ' . $actor);
                show::msg($isPatch ? "Le correctif de sécurité {$label} a été appliqué avec succès." : Lang::get('configmanager-updated', $nextVersion), 'success');
                $updaterManager->clearCache();
                
                // Nettoyer immédiatement le cache local de version
                $cached = \Utils\Util::readJsonFile($cacheFile);
                if (is_array($cached)) {
                    $cached['nextVersion'] = false;
                    $cached['isPatch'] = false;
                    $cached['lastCheckUpdate'] = time();
                    \Utils\Util::writeJsonFile($cacheFile, $cached);
                }
            } else {
                $this->logger->error('Config update failed for ' . $nextVersion . ' by ' . $actor);
                show::msg(Lang::get('configmanager-update-error'), 'error');
            }
        } else {
            // Pas de mise à jour réelle : nettoyer le cache obsolète
            $cached = \Utils\Util::readJsonFile($cacheFile);
            if (is_array($cached)) {
                $cached['nextVersion'] = false;
                $cached['isPatch'] = false;
                $cached['lastCheckUpdate'] = time();
                \Utils\Util::writeJsonFile($cacheFile, $cached);
            }
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

        $settings = \Core\Core::getInstance()->settings();
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

    public function checkUpdatesAsync(): \Core\Responses\ApiResponse
    {
        $resp = new \Core\Responses\ApiResponse();
        $resp->httpCode = 200;
        $resp->status = \Core\Responses\ApiResponse::STATUS_SUCCESS;

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            $resp->body = ['hasUpdate' => false, 'error' => 'unauthorized'];
            return $resp;
        }

        $settings = \Core\Core::getInstance()->settings();
        $cacheFile = $settings->cacheFile();
        $cachedInfos = \Utils\Util::readJsonFile($cacheFile);
        if (!is_array($cachedInfos)) {
            $cachedInfos = [];
        }
        $lastCheck = (int)($cachedInfos['lastCheckUpdate'] ?? 0);

        // Si le cache a plus de 300 secondes (5 min), on interroge le Hub en arrière-plan
        if (time() - $lastCheck > 300) {
            $nextVersion = $settings->getNewVersion();
            $cachedInfos = \Utils\Util::readJsonFile($cacheFile);
        } else {
            $nextVersion = $cachedInfos['nextVersion'] ?? false;
        }

        if (!$nextVersion) {
            $resp->body = ['hasUpdate' => false];
            return $resp;
        }

        $isPatch = !empty($cachedInfos['isPatch']) || (bool) preg_match('/^[a-f0-9]{7,40}$/i', (string) $nextVersion);
        $token = $this->user->token ?? '';
        $updateUrl = $this->router->generate('configmanager-update', ['token' => $token]);

        if ($isPatch) {
            $shortCommit = substr((string) $nextVersion, 0, 7);
            $label = 'Correctif ' . $shortCommit;
            $title = 'Un correctif CMS est disponible (' . $shortCommit . '). Cliquez pour l\'appliquer.';
            $icon = 'fa-shield-halved';
            $cssClass = 'adm-install-badge adm-update-badge is-patch';
        } else {
            $label = 'Màj v' . $nextVersion;
            $title = 'Une nouvelle version ' . $nextVersion . ' est disponible. Cliquez pour mettre à jour.';
            $icon = 'fa-cloud-arrow-down';
            $cssClass = 'adm-install-badge adm-update-badge is-update';
        }

        $badgeHtml = '<a class="' . $cssClass . '" href="' . htmlspecialchars($updateUrl, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="fa-solid ' . $icon . '" aria-hidden="true"></i>'
            . '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</a>';

        $resp->body = [
            'hasUpdate' => true,
            'nextVersion' => (string) $nextVersion,
            'isPatch' => $isPatch,
            'label' => $label,
            'updateUrl' => $updateUrl,
            'badgeHtml' => $badgeHtml
        ];
        return $resp;
    }

}