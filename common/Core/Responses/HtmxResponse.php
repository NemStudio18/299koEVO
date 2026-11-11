<?php

namespace Core\Responses;

use Core\Responses\Response;

/**
 * Response class for HTMX fragments (HTML without layout)
 */
defined('ROOT') OR exit('Access denied!');

class HtmxResponse extends Response
{

    protected \Core\Theme $theme;

    public function __construct()
    {
        parent::__construct();
        $this->theme = new \Core\Theme(\Core\Core::getInstance()->getConfigVal('theme'));
    }

    /**
     * Return the response content directly without layout
     * @return string Content of all templates
     */
    public function output(): string
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        $content = '';
        foreach ($this->templates as $tpl) {
            $content .= $tpl->output();
        }

        return $content;
    }

    /**
     * Create a new Template, from plugin or core module
     * @param string $pluginName
     * @param string $templateName
     * @return \Template\Template
     */
    public function createPluginTemplate(string $pluginName, string $templateName): \Template\Template
    {
        // 1. Try Theme override first
        $themeFile = $this->theme->getPluginTemplatePath($pluginName, $templateName) ?? '';
        if (file_exists($themeFile)) {
            return new \Template\Template($themeFile);
        }

        // 2. Try Plugin internal directory
        $pluginFile = PLUGINS . $pluginName . DS . 'template' . DS . $templateName . '.tpl';
        if (file_exists($pluginFile)) {
            return new \Template\Template($pluginFile);
        }

        // 3. Try Core common directory
        $commonFile = COMMON . 'template' . DS . $pluginName . DS . $templateName . '.tpl';
        if (file_exists($commonFile)) {
            return new \Template\Template($commonFile);
        }

        throw new \RuntimeException("HTMX template not found: $pluginName/$templateName (searched in theme, plugin, common)");
    }

    /**
     * Create a new Template, from core
     * @param string $templateName
     * @return \Template\Template
     */
    public function createCoreTemplate(string $templateName): \Template\Template
    {
        $themeFile = $this->theme->getCoreTemplatePath($templateName) ?? '';
        if (file_exists($themeFile)) {
            $tpl = new \Template\Template($themeFile);
        } else {
            $tpl = new \Template\Template(COMMON . 'template/' . $templateName . '.tpl');
        }
        return $tpl;
    }
}
