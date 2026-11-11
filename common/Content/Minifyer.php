<?php

namespace Content;

use Core\Core;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Maxime Blanc <nemstudio18@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

/**
 * Minifyer
 */
class Minifyer {

    public function minify(string $content): string {
        if ($this->isLazyLoadingEnabled())
            $content = $this->addLazyLoading($content);
        if ($this->isMinifyEnabled())
            $content = $this->minifyHtml($content);
        return $content;
    }

    /**
     * Add lazy loading to images and iframes
     * 
     * @param string $content
     * @return string
     */
    public function addLazyLoading(string $content): string {
        // Protect script and style content
        $scripts = [];
        $content = preg_replace_callback('/<(script|style)[^>]*>.*?<\/\1>/is', function($matches) use (&$scripts) {
            $key = '%%%PROTECTED_SCRIPT_' . count($scripts) . '%%%';
            $scripts[$key] = $matches[0];
            return $key;
        }, $content);

        // Add lazy loading to images
        $content = preg_replace_callback('/<img([^>]*)>/i', function($matches) {
            $attributes = $matches[1];
            
            // Check if loading="lazy" already exists
            if (preg_match('/\bloading\s*=\s*["\']lazy["\']/i', $attributes)) {
                return $matches[0]; // Don't change if already present
            }
            
            // Check if alt exists, otherwise add it
            if (!preg_match('/\balt\s*=/i', $attributes)) {
                $attributes .= ' alt=""';
            }
            
            // Add loading="lazy"
            $attributes .= ' loading="lazy"';
            
            return '<img' . $attributes . '>';
        }, $content);

        // Add lazy loading to iframes
        $content = preg_replace_callback('/<iframe([^>]*)>/i', function($matches) {
            $attributes = $matches[1];
            
            // Check if loading="lazy" already exists
            if (preg_match('/\bloading\s*=\s*["\']lazy["\']/i', $attributes)) {
                return $matches[0]; // Don't change if already present
            }
            
            // Add loading="lazy"
            $attributes .= ' loading="lazy"';
            
            return '<iframe' . $attributes . '>';
        }, $content);

        // Restore protected scripts/styles
        if (!empty($scripts)) {
            $content = str_replace(array_keys($scripts), array_values($scripts), $content);
        }

        return $content;
    }

    /**
     * Minify HTML content
     * 
     * @param string $content
     * @return string
     */
    public function minifyHtml(string $content): string {
        // Ensure proper UTF-8 encoding
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }
        
        // Protect script and style content first
        $scripts = [];
        $content = preg_replace_callback('/<(script|style)[^>]*>.*?<\/\1>/is', function($matches) use (&$scripts) {
            $key = '%%%PROTECTED_SCRIPT_' . count($scripts) . '%%%';
            $scripts[$key] = $matches[0];
            return $key;
        }, $content);

        // Remove HTML comments (except conditional IE comments)
        $content = preg_replace('/<!--(?!\s*\[if [^]]+]|<!|>)(?:(?!-->).)*-->/s', '', $content);

        // Remove unnecessary whitespace between tags, but preserve some spacing
        $content = preg_replace('/>\s*\n\s*</', '><', $content);
        $content = preg_replace('/>\s{2,}</', '> <', $content);

        // Remove multiple spaces, but preserve single spaces
        $content = preg_replace('/[ \t]+/', ' ', $content);

        // Remove leading/trailing whitespace from lines
        $content = preg_replace('/^[ \t]+/m', '', $content);
        $content = preg_replace('/[ \t]+$/m', '', $content);

        // Clean start and end
        $content = trim($content);

        // Restore protected scripts/styles
        if (!empty($scripts)) {
            $content = str_replace(array_keys($scripts), array_values($scripts), $content);
        }

        return $content;
    }

    /**
     * Check if minification is enabled
     * 
     * @return bool
     */
    public function isMinifyEnabled(): bool {
        // Toujours récupérer la configuration actuelle de core
        return Core::getInstance()->getConfigVal('cache_minify') ?: false;
    }

    /**
     * Check if lazy loading is enabled
     * 
     * @return bool
     */
    public function isLazyLoadingEnabled(): bool {
        // Toujours récupérer la configuration actuelle de core
        return Core::getInstance()->getConfigVal('cache_lazy_loading') ?: false;
    }

}