<?php

namespace Core\Responses;

use Core\Responses\Response;
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

class AdminResponse extends Response {

    /**
     * Layout
     * @var Template
     */
    protected Template $layout;

    protected ?string $title = null;

    public function __construct() {
        parent::__construct();
        $this->layout = new Template(ADMIN_PATH .'layout.tpl');
    }

    /**
     * Create a new Template, from plugin
     * @param string $pluginName
     * @param string $templateName
     * @return Template
     */
    public function createPluginTemplate(string $pluginName, string $templateName):Template {
        // Normalize function to ensure absolute paths
        $normalizePath = function($path) {
            // Convert to absolute path if relative
            if (!empty($path)) {
                // Replace mixed slashes with DS
                $path = str_replace(['/', '\\'], DS, $path);
                // If path doesn't start with ROOT, make it absolute
                if (strpos($path, ROOT) !== 0) {
                    $path = ROOT . ltrim($path, DS);
                }
                // Try to get realpath, but keep original if file doesn't exist
                $realPath = realpath($path);
                return $realPath ?: $path;
            }
            return $path;
        };
        
        // Check if it's a core module first
        $core = Core::getInstance();
        if ($core->isCoreModule($pluginName)) {
            // Try admin template location first (for core modules like filemanager)
            $adminTemplateFile = ADMIN_PATH . 'templates' . DS . $pluginName . DS . $templateName . '.tpl';
            $adminTemplateFile = $normalizePath($adminTemplateFile);
            if (file_exists($adminTemplateFile)) {
                $tpl = new Template($adminTemplateFile);
                return $tpl;
            }
            // Try core template location
            $coreTemplateFile = COMMON . 'template' . DS . $pluginName . DS . $templateName . '.tpl';
            $coreTemplateFile = $normalizePath($coreTemplateFile);
            if (file_exists($coreTemplateFile)) {
                $tpl = new Template($coreTemplateFile);
                return $tpl;
            }
            // Fallback to plugin location (for backward compatibility during migration)
            $file = PLUGINS . $pluginName . DS . 'template' . DS . $templateName . '.tpl';
            $file = $normalizePath($file);
        } else {
            // Regular plugin - try plugin location first
            $file = PLUGINS . $pluginName . DS . 'template' . DS . $templateName . '.tpl';
            $file = $normalizePath($file);
            if (!file_exists($file)) {
                // Fallback to admin templates
                $alternative = ADMIN_PATH . 'templates' . DS . $pluginName . DS . $templateName . '.tpl';
                $alternative = $normalizePath($alternative);
                if (file_exists($alternative)) {
                    $file = $alternative;
                }
            }
        }
        $tpl = new Template($file);
        return $tpl;
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
        $this->layout->set('CONTENT', Core::getInstance()->callHook('adminContent', $content));
        $this->layout->set('PAGE_TITLE' , $this->title ?? false);
        return $this->layout->output();
    }

    /**
     * Set the title of the admin page
     * @param string $title
     */
    public function setTitle(string $title) {
        $this->title = $title;
    }
}