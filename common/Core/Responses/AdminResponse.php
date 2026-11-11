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
    public function createPluginTemplate(string $pluginName, string $templateName): Template {
        // We use camelCase normalization for folders if they don't exist in lowercase
        $core = \Core\Core::getInstance();
        
        // 1. Try Admin Templates first (Standard location for Core Modules and Admin Overrides)
        $adminFile = ADMIN_PATH . 'templates' . DS . $pluginName . DS . $templateName . '.tpl';
        if (file_exists($adminFile)) {
            return new Template($adminFile);
        }

        // 2. Try Plugin's internal template directory (Standard location for Plugins)
        $pluginFile = PLUGINS . $pluginName . DS . 'template' . DS . $templateName . '.tpl';
        if (file_exists($pluginFile)) {
            return new Template($pluginFile);
        }

        // 3. Last fallback: Core common templates
        $commonFile = COMMON . 'template' . DS . $pluginName . DS . $templateName . '.tpl';
        if (file_exists($commonFile)) {
            return new Template($commonFile);
        }

        // Throw error if not found to avoid silent failures
        throw new \RuntimeException("Template not found: $pluginName/$templateName (searched in admin, plugin, common)");
    }

    /**
     * Create a new Template, from core
     * @param string $templateName
     * @return Template
     */
    public function createCoreTemplate(string $templateName):Template {
        return new Template(COMMON . 'template/' . $templateName . '.tpl');
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