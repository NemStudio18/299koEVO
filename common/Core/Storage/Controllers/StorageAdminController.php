<?php

namespace Core\Storage\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Database;
use Core\Storage\StorageMigrationService;
use Core\Lang;
use Utils\Show;
use Core\Plugin\PluginsManager;

/**
 * Contrôleur pour gérer l'agnosticisme du stockage depuis l'administration.
 */
class StorageAdminController extends AdminController
{
    public function home()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('configmanager', 'storage');
        $response->setTitle(Lang::get('configmanager-storage-title') ?: 'Gestion du Stockage');

        $db = Database::getInstance();
        $fullConfig = $db->getFullConfig();
        
        $storageConfig = $fullConfig['storage'] ?? [];
        $dbConfig = $fullConfig['database'] ?? [];

        $models = $this->discoverModels();

        // Pré-filtrage des connexions valides pour chaque modèle selon ses capacités
        foreach ($models as $key => &$model) {
            $model['valid_connections'] = [];
            $modelDrivers = $model['drivers'] ?? ['json', 'sql'];
            $pluginId = $model['plugin'] ?? 'core';
            
            $model['supports_json'] = in_array('json', $modelDrivers);
            
            // Connexion SQL par défaut
            $defaultDriver = $dbConfig['driver'] ?? 'mysql';
            if (in_array($defaultDriver, $modelDrivers) || in_array('sql', $modelDrivers)) {
                $model['valid_connections']['sql'] = 'SQL (par défaut: ' . $defaultDriver . ')';
            }

            // Option SQLite dédiée (Dynamique)
            if (in_array('sqlite', $modelDrivers) || in_array('sql', $modelDrivers)) {
                $model['valid_connections']["sqlite:$pluginId"] = 'SQLite Dédié (data/storage/' . $pluginId . '.sqlite)';
            }
            
            // Connexions supplémentaires
            foreach ($fullConfig['databases'] ?? [] as $connName => $connConf) {
                $driver = $connConf['driver'] ?? 'mysql';
                if (in_array($driver, $modelDrivers) || in_array('sql', $modelDrivers)) {
                    $model['valid_connections'][$connName] = 'SQL (' . $connName . ': ' . $driver . ')';
                }
            }
        }

    // Vérification des états de connexion
    $connectionStatuses = [];
    
    // Test par défaut
    try {
        $db->getPdo('default');
        $connectionStatuses['default'] = ['success' => true];
    } catch (\Exception $e) {
        $connectionStatuses['default'] = ['success' => false, 'message' => $e->getMessage()];
    }

    // Test des connexions nommées
    foreach ($fullConfig['databases'] ?? [] as $connName => $connConf) {
        try {
            $db->getPdo($connName);
            $connectionStatuses[$connName] = ['success' => true];
        } catch (\Exception $e) {
            $connectionStatuses[$connName] = ['success' => false, 'message' => $e->getMessage()];
        }
    }

    $tpl->set('connectionStatuses', $connectionStatuses);
    $tpl->set('models', $models);
    $tpl->set('storageConfig', $storageConfig);
    $tpl->set('dbConfig', $dbConfig);
    $tpl->set('databases', $fullConfig['databases'] ?? []);
        $tpl->set('connectionsList', $db->getConnectionsList());
        $tpl->set('saveUrl', $this->router->generate('storage-admin-save'));
        $tpl->set('saveDatabaseUrl', $this->router->generate('storage-admin-save-db'));
        $tpl->set('migrateUrl', $this->router->generate('storage-admin-migrate'));
        $tpl->set('token', $this->user->token);

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Scanne les composants core et les plugins pour découvrir les modèles agnostiques.
     */
    protected function discoverModels(): array
    {
        // Modèles Core par défaut
        $models = [
            'PageItem' => [
                'label' => 'Pages',
                'class' => 'Core\\Page\\PageItem',
                'drivers' => ['json', 'mysql', 'sqlite'],
                'plugin' => 'core'
            ],
            'User' => [
                'label' => 'Utilisateurs',
                'class' => 'Core\\Auth\\User',
                'drivers' => ['json', 'mysql', 'sqlite'],
                'plugin' => 'core'
            ]
        ];

        // Scanner les plugins
        $plugins = PluginsManager::getInstance()->getPlugins();
        foreach ($plugins as $plugin) {
            $pluginPath = method_exists($plugin, 'getPath') ? $plugin->getPath() : PLUGINS . $plugin->getName() . DS;
            $storageFile = $pluginPath . 'param' . DS . 'storage.json';
            if (file_exists($storageFile)) {
                $pluginModels = json_decode(file_get_contents($storageFile), true);
                if (is_array($pluginModels)) {
                    foreach ($pluginModels as $id => $def) {
                        if (!is_array($def) || !isset($def['class'])) {
                            continue;
                        }
                        // Valeur par défaut si drivers non spécifiés
                        if (!isset($def['drivers'])) {
                            $def['drivers'] = ['json', 'sql'];
                        }
                        $def['plugin'] = $plugin->getName();
                        $models[$id] = $def;
                    }
                }
            }
        }

        return $models;
    }

    public function save()
    {
        $db = Database::getInstance();
        $config = $db->getFullConfig();
        
        $newStorage = $_POST['storage'] ?? [];
        if (!is_array($newStorage)) {
            $newStorage = is_string($newStorage) ? (json_decode($newStorage, true) ?: []) : [];
        }
        $existingStorage = is_array($config['storage'] ?? null) ? $config['storage'] : [];
        $config['storage'] = array_merge($existingStorage, $newStorage);

        if ($db->saveConfig($config)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }

        $this->core->redirect($this->router->generate('storage-admin'));
    }

    public function saveDatabase()
    {
        $db = Database::getInstance();
        $fullConfig = $db->getFullConfig();
        
        $name = $_POST['db_name'] ?? 'default';
        $newDb = $_POST['database'] ?? [];

        if (isset($_POST['delete_conn']) && $name !== 'default') {
            unset($fullConfig['databases'][$name]);
        } else {
            // Nettoyage password
            if (empty($newDb['password'])) {
                unset($newDb['password']);
            }

            if ($name === 'default') {
                $fullConfig['database'] = array_merge($fullConfig['database'] ?? [], $newDb);
            } else {
                $fullConfig['databases'][$name] = array_merge($fullConfig['databases'][$name] ?? [], $newDb);
            }
        }

        if ($db->saveConfig($fullConfig)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }

        $this->core->redirect($this->router->generate('storage-admin'));
    }

    public function migrate()
    {
        $model = $_POST['model'] ?? '';
        $target = $_POST['target'] ?? '';
        $source = $_POST['source'] ?? null;
        $targetConn = $_POST['target_connection'] ?? null;
        $sourceConn = $_POST['source_connection'] ?? null;

        if (empty($model) || empty($target)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
            exit;
        }

        $result = StorageMigrationService::migrate($model, $target, $source, $targetConn, $sourceConn);
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
