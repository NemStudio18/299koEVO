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

class PublicResponse extends Response {

    /**
     * Current theme
     * @var Theme
     */
    protected Theme $theme;

    /**
     * Layout
     * @var Template
     */
    protected Template $layout;

    protected ?string $title = null;

    protected bool $hidePageTitle = false;

    public function __construct() {
        parent::__construct();
        $this->theme = new Theme(Core::getInstance()->getConfigVal('theme'));
        $this->layout = new Template($this->theme->getLayout());
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

    /**
     * Create a new Template, from core
     * Eg : if asked template is '404', look for 'THEMES/theme/template/core.404.tpl'
     * If the template exist in the current theme, use it, else use the one from common/template
     * @param string $templateName
     * @return Template
     */
    public function createCoreTemplate(string $templateName):Template {
        $themeFile = $this->theme->getCoreTemplatePath($templateName) ?? '';
        if (file_exists($themeFile)) {
            $tpl = new Template($themeFile);
        } else {
            $tpl = new Template(COMMON . 'template/' . $templateName . '.tpl');
        }
        return $tpl;
    }

    /**
     * Return the response
     * @return string Content of all template
     */
    public function output():string
    {
        // Ensure proper UTF-8 encoding
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        
        $content = '';
        foreach ($this->templates as $tpl) {
            $content .= $tpl->output();
        }
        $this->layout->set('CONTENT', Core::getInstance()->callHook('publicContent', $content));
        $this->layout->set('PAGE_TITLE' , $this->title ?? '');
        // Also set mainTitle in Core to avoid 404 title
        if ($this->title !== null && $this->title !== '') {
            Core::getInstance()->setMainTitle($this->title);
        }
        // Hide page title for auth pages (login, register, profile)
        $hidePageTitle = ($this->hidePageTitle ?? false);
        $this->layout->set('HIDE_PAGE_TITLE', $hidePageTitle);
        return $this->layout->output();
    }

    /**
     * Set the title of the public page
     * @param string $title
     */
    public function setTitle(string $title) {
        $this->title = $title;
    }

    /**
     * Hide the page title for this response
     * @param bool $hide
     */
    public function hidePageTitle(bool $hide = true) {
        $this->hidePageTitle = $hide;
    }
}