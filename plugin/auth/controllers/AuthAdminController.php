<?php

namespace Auth\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;

defined('ROOT') or exit('Access denied!');

class AuthAdminController extends AdminController
{
    private function getConfig(): array
    {
        $file = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';
        $default = [
            'activate' => true,
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
            'spoiler_title' => 'Besoin d\'aide ?',
            'spoiler_type' => 'accordion',
            'spoiler_content' => '',
            'checkboxes' => [],
            'dynamic_fields' => [
                ['id' => 'age', 'label' => 'Âge', 'type' => 'number', 'icon' => 'fa-solid fa-birthday-cake', 'enabled' => true, 'required' => false],
                ['id' => 'sex', 'label' => 'Sexe', 'type' => 'select', 'icon' => 'fa-solid fa-venus-mars', 'options' => 'M:Homme,F:Femme,O:Autre', 'enabled' => true, 'required' => false],
                ['id' => 'city', 'label' => 'Ville', 'type' => 'select', 'icon' => 'fa-solid fa-city', 'source' => 'common/data/agglo.json', 'enabled' => true, 'required' => false],
                ['id' => 'district', 'label' => 'Quartier', 'type' => 'select', 'icon' => 'fa-solid fa-map-marker-alt', 'source' => 'common/data/quartiers-bourges.json', 'enabled' => true, 'required' => false]
            ]
        ];
        
        if (!is_dir(DATA_PLUGIN . 'auth' . DS)) {
            @mkdir(DATA_PLUGIN . 'auth' . DS, 0755, true);
        }

        if (!is_file($file)) {
            \Utils\Util::writeJsonFile($file, $default);
            return $default;
        }
        
        // Fusionner avec les valeurs par défaut pour la rétrocompatibilité
        $existing = \Utils\Util::readJsonFile($file, true) ?: [];
        
        // Migration simple pour show_localisation vers dynamic_fields si nécessaire
        if (isset($existing['show_localisation'])) {
            $show = $existing['show_localisation'];
            foreach ($default['dynamic_fields'] as &$df) {
                if ($df['id'] === 'city' || $df['id'] === 'district') {
                    $df['enabled'] = $show;
                }
            }
            unset($existing['show_localisation']);
        }

        return array_merge($default, $existing);
    }

    private function saveConfig(array $config): void
    {
        $file = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';
        \Utils\Util::writeJsonFile($file, $config);
    }

    public function index()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('auth:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $config = $this->getConfig();
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('auth', 'admin');
        $tpl->set('config', $config);
        $response->addTemplate($tpl);
        return $response;
    }

    public function users()
    {
        return $this->index();
    }

    public function save()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('auth:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $config = $this->getConfig();

        $config['facebook_enabled'] = isset($_POST['facebook_enabled']);
        $config['google_enabled'] = isset($_POST['google_enabled']);
        $config['facebook_app_id'] = trim($_POST['facebook_app_id'] ?? '');
        $config['facebook_app_secret'] = trim($_POST['facebook_app_secret'] ?? '');
        $config['google_client_id'] = trim($_POST['google_client_id'] ?? '');
        $config['google_client_secret'] = trim($_POST['google_client_secret'] ?? '');
        
        $config['content_position'] = $_POST['content_position'] ?? 'bottom';
        $config['login_text'] = trim($_POST['login_text'] ?? '');
        $config['register_text'] = trim($_POST['register_text'] ?? '');
        $config['show_spoiler'] = isset($_POST['show_spoiler']);
        $config['spoiler_title'] = trim($_POST['spoiler_title'] ?? 'Besoin d\'aide ?');
        $config['spoiler_type'] = $_POST['spoiler_type'] ?? 'accordion';
        $config['spoiler_content'] = trim($_POST['spoiler_content'] ?? '');
        
        // Checkboxes handling
        $checkboxes = [];
        if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes'])) {
            foreach ($_POST['checkboxes'] as $cb) {
                if (!empty(trim($cb['label'] ?? ''))) {
                    $checkboxes[] = [
                        'label' => trim($cb['label']),
                        'required' => isset($cb['required'])
                    ];
                }
            }
        }
        $config['checkboxes'] = $checkboxes;

        // Dynamic fields handling
        $dynamicFields = [];
        if (isset($_POST['dynamic_fields']) && is_array($_POST['dynamic_fields'])) {
            foreach ($_POST['dynamic_fields'] as $df) {
                if (!empty(trim($df['id'] ?? ''))) {
                    $dynamicFields[] = [
                        'id' => trim($df['id']),
                        'label' => trim($df['label'] ?? ''),
                        'icon' => trim($df['icon'] ?? ''),
                        'type' => $df['type'] ?? 'text',
                        'options' => trim($df['options'] ?? ''),
                        'source' => trim($df['source'] ?? ''),
                        'enabled' => isset($df['enabled']),
                        'required' => isset($df['required']),
                        'show_on_register' => isset($df['show_on_register'])
                    ];
                }
            }
        }
        $config['dynamic_fields'] = $dynamicFields;

        $this->saveConfig($config);
        Show::msg('Configuration sauvegardée.', 'success');
        $this->core->redirect($this->router->generate('auth-admin-home'));
        return null;
    }
}
