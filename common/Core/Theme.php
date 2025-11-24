<?php

namespace Core;

use Utils\Util;
use Core\Storage\Cache;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * Class Theme
 */
class Theme {

    public string $slug = '';

    public string $name = '';

    public string $version = '';

    public string $authorEmail = '';

    public string $authorWebsite = '';

    public array $pics = [];

    public string $folderName = '';

    public string $folderPath = '';

    public string $parentName = '';

    public ?Theme $parent;

    /**
     * Construct a new instance of the Theme class
     *
     * @param string $folderName The name of the theme folder
     */
    public function __construct(string $folderName) {
        $this->folderName = $folderName;
        $this->folderPath = THEMES . $folderName . DS;
        if (file_exists($this->folderPath . 'infos.json')) {
            $infos = Util::readJsonFile($this->folderPath . 'infos.json');
            foreach ($infos as $key => $info) {
                $this->$key = $info;
            }
        }
        $this->parent = $this->getParent($this->parentName);
    }

    public function isInstalled():bool {
        return is_dir($this->folderPath);
    }

    /**
     * Get the parent theme
     *
     * @param string $parentName The name of the parent theme
     * @return Theme|null The parent theme or null if the parent does not exist
     */
    protected function getParent($parentName): ?Theme {
        if (empty($parentName)) {
            return null;
        }
        return new Theme($parentName);
    }

    /**
     * Get the path of the theme's layout file
     *
     * The function looks for a 'layout.tpl' file in the theme folder, and if it does not exist, it will look in the parent theme folder
     *
     * @return string|null The path of the theme's layout file or null if the file does not exist
     */
    public function getLayout(): ?string {
        if (file_exists($this->folderPath . 'layout.tpl')) {
            return $this->folderPath . 'layout.tpl';
        }
        if (!is_null($this->parent)) {
            $layoutParent = $this->parent->getLayout();
            if (!empty($layoutParent)) {
                return $layoutParent;
            }
        }
        return null;
    }

    /**
     * Retrieve the path for a plugin's template file.
     *
     * This function checks for the existence of a template file specific to a plugin within the current theme's directory. 
     * If the template file does not exist, it recursively checks in the parent theme's directory.
     *
     * @param string $pluginName The name of the plugin.
     * @param string $templateName The name of the template file.
     * @return string|null The path to the template file if found, or null if the file does not exist.
     */
    public function getPluginTemplatePath(string $pluginName, string $templateName): ?string {
        if (file_exists($this->folderPath . 'template' . DS . $pluginName . '.' . $templateName . '.tpl')) {
            return $this->folderPath . 'template' . DS . $pluginName . '.' . $templateName . '.tpl';
        }
        if (!is_null($this->parent)) {
            $tplParent = $this->parent->getPluginTemplatePath($pluginName, $templateName);
            if (!empty($tplParent)) {
                return $tplParent;
            }
        }
        return null;
    }

    /**
     * Retrieve the path for a core template file.
     *
     * This function checks for the existence of a core template file within the current theme's directory. 
     * If the template file does not exist, it recursively checks in the parent theme's directory.
     *
     * @param string $templateName The name of the template file.
     * @return string|null The path to the template file if found, or null if the file does not exist.
     */
    public function getCoreTemplatePath(string $templateName): ?string {
        if (file_exists($this->folderPath . 'template' . DS . 'core' . '.' . $templateName . '.tpl')) {
            return $this->folderPath . 'template' . DS . 'core' . '.' .  $templateName . '.tpl';
        }
        if (!is_null($this->parent)) {
            $tplParent = $this->parent->getCoreTemplatePath($templateName);
            if (!empty($tplParent)) {
                return $tplParent;
            }
        }
        return null;
    }

    /**
     * Retrieve the CSS links of the theme.
     *
     * This function will check if there is a 'styles.css' file in the current theme's directory, and if it does not exist, it will check in the parent theme's directory.
     * If the 'styles.css' file does not exist in any of the parent themes, it will return an empty string.
     *
     * @return string The CSS links of the theme.
     */
    public function getCSSLinks(): string {
        $str = '';
        if (!is_null($this->parent)) {
            $str .= $this->parent->getCSSLinks();
        }
        if (file_exists($this->folderPath . 'styles.css')) {
            $str .= '<link rel="stylesheet" href="' . Util::urlBuild(THEMES . $this->folderName . '/styles.css') . '" />';
        }
        return $str;
    }

    /**
     * Retrieve the JavaScript links of the theme.
     *
     * This function checks for the presence of a 'script.js' file in the current theme's directory and 
     * returns an HTML script tag referencing it. If the file does not exist, it will recursively check 
     * in the parent theme's directory for JavaScript links.
     *
     * @return string The JavaScript links of the theme if found, otherwise an empty string.
     */
    public function getJSLinks(): string {
        if (file_exists($this->folderPath . 'scripts.js')) {
            return '<script type="text/javascript" src="' . Util::urlBuild(THEMES . $this->folderName . '/scripts.js') . '"></script>';
        }
        $js = '';
        if (!is_null($this->parent)) {
            $js = $this->parent->getJSLinks();
        }
        return $js;
    }

    /**
     * Retrieve the URL of the theme's icon.
     *
     * This function first checks in the current theme's directory for an 'icon.png' file, and if it does not exist, it will
     * recursively check in the parent theme's directory. If the 'icon.png' file does not exist in any of the parent themes, it
     * will return an empty string.
     *
     * @return string The URL of the theme's icon if found, otherwise an empty string.
     */
    public function getIconUrl():string {
        if (file_exists($this->folderPath . 'icon.png')) {
            return Util::urlBuild(THEMES . $this->folderName . '/icon.png');
        }
        $url = '';
        if (!is_null($this->parent)) {
            $url = $this->parent->getIconUrl();
        }
        return $url;
    }

    /**
     * Invalidate theme cache
     * 
     * @return void
     */
    public function invalidateCache(): void
    {
        $cache = new Cache();
        $cache->deleteByTag('theme_' . $this->folderName);
        
        // Also invalidate parent theme cache if exists
        if ($this->parent) {
            $this->parent->invalidateCache();
        }
    }
}