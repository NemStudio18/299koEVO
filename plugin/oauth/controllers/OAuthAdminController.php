<?php

namespace OAuth\Controllers;

use Core\Controllers\AdminController;
use Core\Navigation\AccessPolicyEvaluator;
use Core\Navigation\ConditionContext;
use Core\Navigation\GenericResource;
use Core\Responses\AdminResponse;
use Core\Security\Csrf;
use Utils\Show;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class OAuthAdminController extends AdminController
{
    private function getConfig(): array
    {
        $file = DATA_PLUGIN . 'oauth' . DS . 'config.json';
        $legacyFile = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';

        $default = [
            'activate' => 1,
            'priority' => 10,
            'facebook_enabled' => false,
            'google_enabled' => false,
            'facebook_app_id' => '',
            'facebook_app_secret' => '',
            'google_client_id' => '',
            'google_client_secret' => '',
            'content_position' => 'bottom',
            'login_text' => '',
            'register_text' => '',
            'show_spoiler' => false,
            'spoiler_title' => "Besoin d'aide ?",
            'spoiler_type' => 'accordion',
            'spoiler_content' => '',
            'checkboxes' => [],
            'dynamic_fields' => []
        ];

        $targetDir = DATA_PLUGIN . 'oauth' . DS;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        if (is_file($file)) {
            $existing = Util::readJsonFile($file, true) ?: [];
            return array_merge($default, $existing);
        }

        // Migration transparente depuis l'ancienne configuration si présente
        if (is_file($legacyFile)) {
            $legacy = Util::readJsonFile($legacyFile, true) ?: [];
            $merged = array_merge($default, $legacy);
            Util::writeJsonFile($file, $merged);
            return $merged;
        }

        Util::writeJsonFile($file, $default);
        return $default;
    }

    private function saveConfig(array $config): void
    {
        $targetDir = DATA_PLUGIN . 'oauth' . DS;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        $file = $targetDir . 'config.json';
        Util::writeJsonFile($file, $config);
    }

    public function index()
    {
        $evaluator = AccessPolicyEvaluator::getInstance();
        $ctx = new ConditionContext($this->user);

        if (!$evaluator->canAccess(new GenericResource('oauth:admin'), $ctx)) {
            Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return null;
        }

        $config = $this->getConfig();
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('oauth', 'admin');
        $tpl->set('config', $config);
        $tpl->set('_csrfToken', Csrf::token());
        $tpl->set('saveUrl', $this->router->generate('oauth-admin-save'));
        $response->addTemplate($tpl);
        return $response;
    }

    public function save()
    {
        $evaluator = AccessPolicyEvaluator::getInstance();
        $ctx = new ConditionContext($this->user);

        if (!$evaluator->canAccess(new GenericResource('oauth:admin'), $ctx)) {
            Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return null;
        }

        if ($this->user->token !== ($_POST['token'] ?? '') && !Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg('Jeton de sécurité invalide.', 'error');
            $this->core->redirect($this->router->generate('oauth-admin-home'));
            return null;
        }

        $config = $this->getConfig();

        $config['facebook_enabled'] = isset($_POST['facebook_enabled']);
        $config['google_enabled'] = isset($_POST['google_enabled']);
        $config['facebook_app_id'] = trim((string) ($_POST['facebook_app_id'] ?? ''));
        $config['facebook_app_secret'] = trim((string) ($_POST['facebook_app_secret'] ?? ''));
        $config['google_client_id'] = trim((string) ($_POST['google_client_id'] ?? ''));
        $config['google_client_secret'] = trim((string) ($_POST['google_client_secret'] ?? ''));

        $config['content_position'] = (string) ($_POST['content_position'] ?? 'bottom');
        $config['login_text'] = trim((string) ($_POST['login_text'] ?? ''));
        $config['register_text'] = trim((string) ($_POST['register_text'] ?? ''));
        $config['show_spoiler'] = isset($_POST['show_spoiler']);
        $config['spoiler_title'] = trim((string) ($_POST['spoiler_title'] ?? "Besoin d'aide ?"));
        $config['spoiler_type'] = (string) ($_POST['spoiler_type'] ?? 'accordion');
        $config['spoiler_content'] = trim((string) ($_POST['spoiler_content'] ?? ''));

        // Checkboxes CGU / validation
        $checkboxes = [];
        if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes'])) {
            foreach ($_POST['checkboxes'] as $cb) {
                $lbl = trim((string) ($cb['label'] ?? ''));
                if ($lbl !== '') {
                    $checkboxes[] = [
                        'label' => $lbl,
                        'required' => isset($cb['required'])
                    ];
                }
            }
        }
        $config['checkboxes'] = $checkboxes;

        // Champs de profil dynamiques
        $dynamicFields = [];
        if (isset($_POST['dynamic_fields']) && is_array($_POST['dynamic_fields'])) {
            foreach ($_POST['dynamic_fields'] as $df) {
                $id = trim((string) ($df['id'] ?? ''));
                if ($id !== '') {
                    $dynamicFields[] = [
                        'id' => $id,
                        'label' => trim((string) ($df['label'] ?? '')),
                        'icon' => trim((string) ($df['icon'] ?? '')),
                        'type' => (string) ($df['type'] ?? 'text'),
                        'options' => trim((string) ($df['options'] ?? '')),
                        'source' => trim((string) ($df['source'] ?? '')),
                        'enabled' => isset($df['enabled']),
                        'required' => isset($df['required']),
                        'show_on_register' => isset($df['show_on_register'])
                    ];
                }
            }
        }
        $config['dynamic_fields'] = $dynamicFields;

        $this->saveConfig($config);
        Show::msg('Configuration OAuth enregistrée avec succès.', 'success');
        $this->core->redirect($this->router->generate('oauth-admin-home'));
        return null;
    }
}
