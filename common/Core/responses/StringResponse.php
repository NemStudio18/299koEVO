<?php

namespace Core\Responses;

use Core\Responses\Response;
use Core\Theme;
use Template\Template;
use Core\Core;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

class StringResponse extends Response {

    /**
     * Current theme name
     * @var Theme
     */
    protected Theme $theme;

    public function __construct() {
        parent::__construct();
        $this->theme = new Theme(Core::getInstance()->getConfigVal('theme'));
    }

    /**
     * Return the response
     * @return string Content of all template
     */
    public function output():string
    {
        $content = '';
        foreach ($this->templates as $tpl) {
            $content .= $tpl->output();
        }
        return $content;
    }

    /**
     * Create a new Template, from plugin or core module
     * Eg : if plugin is 'blog' & asked template is 'read', look for 'THEMES/theme/template/blog.read.tpl'
     * else create tpl with PLUGINS/blog/template/read.tpl or COMMON/template/{pluginName}/{templateName}.tpl for core modules
     * @param string $pluginName
     * @param string $templateName
     * @return Template
     */
    public function createPluginTemplate(string $pluginName, string $templateName):Template {
        $themeFile = $this->theme->getPluginTemplatePath($pluginName, $templateName) ?? '';
        if (file_exists($themeFile)) {
            $tpl = new Template($themeFile);
        } else {
            // Check if it's a core module first
            $core = Core::getInstance();
            if ($core->isCoreModule($pluginName)) {
                // Try admin template location first (for core modules like filemanager)
                $adminTemplateFile = ADMIN_PATH . 'templates' . DS . $pluginName . DS . $templateName . '.tpl';
                if (file_exists($adminTemplateFile)) {
                    $tpl = new Template($adminTemplateFile);
                    return $tpl;
                }
                // Try core template location
                $coreTemplateFile = COMMON . 'template' . DS . $pluginName . DS . $templateName . '.tpl';
                if (file_exists($coreTemplateFile)) {
                    $tpl = new Template($coreTemplateFile);
                } else {
                    // Fallback to plugin location (for backward compatibility during migration)
                    $tpl = new Template(PLUGINS . $pluginName . DS . 'template' . DS . $templateName . '.tpl');
                }
            } else {
                // Regular plugin
                $tpl = new Template(PLUGINS . $pluginName . DS . 'template' . DS . $templateName . '.tpl');
            }
        }
        return $tpl;
    }

}