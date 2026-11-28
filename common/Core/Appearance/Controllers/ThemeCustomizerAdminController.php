<?php

namespace Core\Appearance\Controllers;

use Core\Appearance\ThemeCustomizer;
use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Storage\Cache;
use Utils\Show;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class ThemeCustomizerAdminController extends AdminController
{
    public function index(): AdminResponse
    {
        $customizer = ThemeCustomizer::getInstance();
        $themeSlug = $customizer->getActiveThemeSlug();
        $schema = $customizer->getSchema($themeSlug);
        $values = $customizer->getValues($themeSlug);

        $themeInfos = Util::readJsonFile(THEMES . $themeSlug . '/infos.json', true) ?: [];
        $themeName = $themeInfos['name'] ?? $themeSlug;

        $fields = [];
        foreach ($schema as $key => $def) {
            $fields[] = [
                'key' => $key,
                'type' => $def['type'] ?? 'text',
                'label' => $def['label'] ?? $key,
                'description' => $def['description'] ?? '',
                'value' => $values[$key] ?? ($def['default'] ?? ''),
                'default' => $def['default'] ?? '',
                'min' => $def['min'] ?? 0,
                'max' => $def['max'] ?? 32,
                'step' => $def['step'] ?? 1,
                'options' => $def['options'] ?? [],
            ];
        }

        $response = new AdminResponse();
        $response->setTitle('Apparence & Personnalisation du Thème');
        $tpl = $response->createPluginTemplate('customizer', 'index');
        $tpl->set('themeSlug', $themeSlug);
        $tpl->set('themeName', $themeName);
        $tpl->set('fields', $fields);
        $tpl->set('values', $values);
        $tpl->set('saveUrl', $this->router->generate('customizer-admin-save'));
        $tpl->set('resetUrl', $this->router->generate('customizer-admin-reset'));
        $tpl->set('siteUrl', rtrim((string) $this->core->getConfigVal('siteUrl'), '/') . '/');
        $response->addTemplate($tpl);
        return $response;
    }

    public function save(): void
    {
        if (!$this->validateCsrfToken()) {
            Show::msg('Jeton de sécurité CSRF invalide.', 'error');
            $this->core->redirect($this->router->generate('customizer-admin'));
            return;
        }

        $customizer = ThemeCustomizer::getInstance();
        $input = isset($_POST['customizer']) && is_array($_POST['customizer']) ? $_POST['customizer'] : [];
        if ($customizer->saveValues($input)) {
            (new Cache())->clear();
            Show::msg('Personnalisation du thème enregistrée et appliquée sur le site.', 'success');
        } else {
            Show::msg('Erreur lors de la sauvegarde des réglages d\'apparence.', 'error');
        }

        $this->core->redirect($this->router->generate('customizer-admin'));
    }

    public function reset(): void
    {
        if (!$this->validateCsrfToken()) {
            Show::msg('Jeton de sécurité CSRF invalide.', 'error');
            $this->core->redirect($this->router->generate('customizer-admin'));
            return;
        }

        $customizer = ThemeCustomizer::getInstance();
        $customizer->resetValues();
        (new Cache())->clear();
        Show::msg('Les couleurs et typographies par défaut du thème ont été restaurées.', 'success');
        $this->core->redirect($this->router->generate('customizer-admin'));
    }
}
