<?php

namespace Core\Plugin;

use Core\Core;
use Core\Plugin\Plugin;
use Utils\Util;

/**
 * @copyright (C) 2025, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

class PluginsManager {

    private $plugins;
    private static $instance = null;

    ## Constructeur

    public function __construct() {
        $this->plugins = $this->listPlugins();
    }

    /**
     * Returns the list of plugins
     * 
     * @return array
     */
    public function getPlugins() {
        return $this->plugins;
    }

    public function getPlugin($name) {
        $normalizedSearch = str_replace(['-', '_'], '', strtolower((string)$name));
        foreach ($this->plugins as $plugin) {
            $normalizedPlugin = str_replace(['-', '_'], '', strtolower($plugin->getName()));
            if ($normalizedPlugin === $normalizedSearch)
                return $plugin;
        }
        return false;
    }

    ## Sauvegarde la configuration d'un objet plugin

    public function savePluginConfig($obj) {
        if ($obj->getIsValid() && $path = $obj->getDataPath()) {
            return Util::writeJsonFile($path . 'config.json', $obj->getConfig());
        }
    }

    /**
     * Installs a plugin
     * 
     * @param string $name Plugin name
     * @param bool $activate true to activate the plugin, false otherwise
     * 
     * @return bool true if the plugin has been successfully installed, false otherwise
     */
    public function installPlugin($name, $activate = false) {
        if (!is_dir(DATA_PLUGIN . $name)) {
            mkdir(DATA_PLUGIN . $name . DS, 0755);
        }
        chmod(DATA_PLUGIN . $name . DS, 0755);
        // Read original plugin config
        $config = Util::readJsonFile(PLUGINS . $name . DS .'param' . DS . 'config.json');
        // By default, the plugin is not activated
        if ($activate)
            $config['activate'] = 1;
        else
            $config['activate'] = 0;
        // Write plugin config
        Util::writeJsonFile(DATA_PLUGIN . $name . DS . 'config.json', $config);
        chmod(DATA_PLUGIN . $name . DS .'config.json', 0644);

        // Register storage requirements from param/storage.json into env.json
        $this->registerStorageRequirements($name);

        // Call install function if exists
        if (file_exists(PLUGINS . $name . DS . $name . '.php')) {
            require_once (PLUGINS . $name . DS . $name . '.php');
            if (function_exists($name . 'Install')) {
                Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_INFO, "Call function '" . $name . "Install'");
                call_user_func($name . 'Install');
            }
        }
        // Check if plugin is installed
        if (!file_exists(DATA_PLUGIN . $name . DS . 'config.json')) {
            Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "Plugin $name can't be installed");
            return false;
        }
        Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_INFO, "Plugin $name successfully installed");
        return true;
    }

    /**
     * Registers a plugin's storage requirements into env.json.
     * 
     * Reads param/storage.json from the plugin directory. For each declared entity,
     * if JSON is not in the supported drivers list, registers the entity as "sql"
     * in the global storage configuration (env.json). This ensures fresh installations
     * work correctly without manual env.json edits.
     * 
     * @param string $name Plugin name
     */
    private function registerStorageRequirements(string $name): void
    {
        $storageFile = PLUGINS . $name . DS . 'param' . DS . 'storage.json';
        if (!file_exists($storageFile)) {
            return;
        }

        $storageConfig = Util::readJsonFile($storageFile);
        if (empty($storageConfig) || !is_array($storageConfig)) {
            return;
        }

        $db = \Core\Database::getInstance();
        $newEntries = [];

        foreach ($storageConfig as $entityName => $entityConfig) {
            if (!is_array($entityConfig) || !isset($entityConfig['drivers']) || !is_array($entityConfig['drivers'])) {
                continue;
            }
            // Skip if already configured in env.json
            if ($db->getConfig($entityName) !== null) {
                continue;
            }

            $drivers = $entityConfig['drivers'];

            // If entity does NOT support JSON, it MUST be registered as "sql"
            if (!in_array('json', $drivers)) {
                $newEntries[$entityName] = 'sql';
                // Also set in-memory so the current request works immediately
                $db->setStorageConfig($entityName, 'sql');
                Core::getInstance()->getLogger()->log(
                    \Core\Logger::LEVEL_INFO,
                    "Storage auto-registered: $entityName => sql (plugin: $name)"
                );
            }
        }

        // Persist to env.json in one write
        if (!empty($newEntries)) {
            $db->saveConfig(['storage' => $newEntries]);
        }
    }

    /**
     * Uninstalls a plugin by its name.
     * 
     * This method performs the following actions:
     * - Calls the plugin's uninstall function if $purgeData is true.
     * - Removes the plugin's data directory only if $purgeData is true.
     * - Removes the plugin's directory.
     * - Logs the uninstallation process.
     *
     * @param string $name The name of the plugin to uninstall.
     * @param bool $purgeData Whether to delete data in /data/plugin/[name] as well. Default is false (preserve data).
     * @return bool Returns true if the plugin was successfully uninstalled.
     */
    public function uninstallPlugin(string $name, bool $purgeData = false) {
        // Call uninstall function if exists (only on full purge or when plugin explicitly defines clean-up)
        if ($purgeData && file_exists(PLUGINS . $name . DS . $name . '.php')) {
            require_once (PLUGINS . $name . DS . $name . '.php');
            if (function_exists($name . 'Uninstall')) {
                Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_INFO, "Call function '" . $name . "Uninstall'");
                call_user_func($name . 'Uninstall');
            }
        }
        // Remove plugin data only if explicitly requested
        if ($purgeData && is_dir(DATA_PLUGIN . $name)) {
            Util::delTree(DATA_PLUGIN . $name);
        }
        if (is_dir(PLUGINS . $name)) {
            Util::delTree(PLUGINS . $name);
        }
        Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_INFO, "Plugin $name successfully uninstalled (purgeData=" . ($purgeData ? 'true' : 'false') . ")");
        return true;
    }
        

    /**
     * Return the singleton instance
     * 
     * @return \self
     */
    public static function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new PluginsManager();
        return self::$instance;
    }

    /**
     * Retrieves a specific configuration value from a plugin.
     *
     * This function accesses a plugin's configuration by its name and 
     * returns the value of the specified configuration key.
     *
     * @param string $pluginName The name of the plugin.
     * @param string $kConf The configuration key to retrieve the value for.
     * @return mixed The value of the configuration key, or false if not found.
     */
    public static function getPluginConfVal($pluginName, $kConf) {
        $instance = self::getInstance();
        $plugin = $instance->getPlugin($pluginName);
        return $plugin->getConfigVal($kConf);
    }

    ## Détermine si le plugin ciblé existe et s'il est actif

    public static function isActivePlugin($pluginName) {
        $instance = self::getInstance();
        $plugin = $instance->getPlugin($pluginName);
        if ($plugin && $plugin->isInstalled() && $plugin->getConfigval('activate'))
            return true;
        return false;
    }

    /**
     * Creates a list of plugin objects.
     *
     * This function creates a list of all active plugins and their respective
     * configurations. It first scans the plugins directory for directories
     * and then checks if a configuration file exists for each plugin. If a
     * configuration file exists, it reads the file and adds the plugin to the
     * list. If not, it adds the plugin to the list with a low priority.
     *
     * @return array An array of plugin objects.
     */
    public function listPlugins() {
        $data = [];
        $dataNotSorted = [];
        $items = Util::scanDir(PLUGINS);
        foreach ($items['dir'] as $dir) {
            // Vérifier que c'est bien un plugin (doit avoir param/infos.json)
            if (!file_exists(PLUGINS . $dir . '/param/infos.json')) {
                // Ce n'est pas un plugin, ignorer (admin, common, plugin, theme, etc.)
                continue;
            }
            
            // If the plugin is installed, get its configuration
            if (file_exists(DATA_PLUGIN . $dir . '/config.json')) {
                $dataNotSorted[$dir] = Util::readJsonFile(DATA_PLUGIN . $dir . '/config.json', true);
            }
            // Otherwise, give it a low priority
            else {
                $dataNotSorted[$dir]['priority'] = '9';
            }
        }
        // Sort the plugins by priority
        $dataSorted = @Util::sort2DimArray($dataNotSorted, 'priority', 'num');
        foreach ($dataSorted as $plugin => $config) {
            $data[] = $this->createPlugin($plugin);
        }
        return $data;
    }

    ## Créée un objet plugin

    private function createPlugin($name) {
        // Instance du core
        $core = Core::getInstance();
        $logger = $core->getLogger();
        $problems = [];
        
        // Infos du plugin
        $infos = Util::readJsonFile(PLUGINS . $name . '/param/infos.json');
        // Si infos.json n'existe pas ou est invalide, utiliser un tableau vide
        if (!is_array($infos)) {
            $infos = [];
            $problems[] = "infos.json invalide ou manquant";
        } elseif (empty($infos['name'])) {
            $problems[] = "infos.json: champ 'name' manquant";
        }
        
        // Configuration du plugin
        $config = Util::readJsonFile(DATA_PLUGIN . $name . '/config.json');
        if (!is_array($config)) {
            $config = [];
            if (!empty($problems)) { // Ne logger que si d'autres problèmes existent
                $problems[] = "config.json invalide ou manquant";
            }
        }
        
        // Hooks du plugin
        $hooks = Util::readJsonFile(PLUGINS . $name . '/param/hooks.json');
        if (!is_array($hooks)) {
            $hooks = [];
        }
        
        // Config usine
        $initConfig = Util::readJsonFile(PLUGINS . $name . '/param/config.json');
        if (!is_array($initConfig)) {
            $initConfig = [];
            if (!empty($problems)) { // Ne logger que si d'autres problèmes existent
                $problems[] = "param/config.json invalide ou manquant";
            }
        }
        
        // Logger les problèmes
        if (!empty($problems)) {
            $logger->warning("Plugin '{$name}' a des problèmes: " . implode(', ', $problems));
        }
        
        // Création de l'objet
        $plugin = new Plugin($name, $config, $infos, $hooks, $initConfig);
        return $plugin;
    }

}