<?php

namespace Content;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * ContentParser is a class to deal with content and modify it
 */
class ContentParser
{

    protected string $content;

    protected ?string $parsedContent = null;

    protected ?string $withoutShortcodesContent = null;

    protected static array $shortcodes = [];

    /**
     * Construct a new content parser
     *
     * @param string $content The content to parse
     */
    public function __construct(string $content = '')
    {
        $this->content = $content;
    }

    /**
     * Re-set the content. Core hooks are called after setting content.
     *
     * @param string $content The content to set
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->parsedContent = null;
        $this->withoutShortcodesContent = null;
    }

    /**
     * Return the original content, before any parsing
     *
     * @return string The original content
     */
    public function getOriginalContent(): string
    {
        return $this->content;
    }

    /**
     * Return the parsed content, parsing it first if necessary.
     *
     * @return string The parsed content
     */

    public function getParsedContent(): string
    {
        return $this->parsedContent ?? $this->parseContent();
    }

    /**
     * Return the content without shortcodes, parsing it first if necessary.
     *
     * @return string The content without shortcodes
     */
    public function getWithoutShortcodesContent(): string
    {
        return $this->withoutShortcodesContent ?? $this->removeShortcodes();
    }

    /**
     * Add a shortcode to the parser
     * static::addShortcode('link', [$this, 'parseLinkShortcode']);
     *
     * @param string $name The name of the shortcode
     * @param callable $callback The callback to call when the shortcode is encountered
     */
    public static function addShortcode(string $name, callable $callback): void
    {
        static::$shortcodes[$name] = $callback;
    }

    /**
     * Remove all shortcodes from the content and return the result.
     *
     * This method processes the content to strip out any shortcodes,
     * storing the result for future retrieval.
     *
     * @return string The content with shortcodes removed.
     */
    protected function removeShortcodes(): string
    {
        $this->withoutShortcodesContent = $this->processRemoveShortcodes($this->content);
        return $this->withoutShortcodesContent;
    }

    /**
     * Parse the content and return the parsed result.
     *
     * @return string The parsed content
     */
    protected function parseContent(): string
    {
        $this->parsedContent = $this->parseShortcodes($this->content);
        return $this->parsedContent;
    }

    /**
     * Process the given content to remove any shortcodes.
     *
     * @param string $content The content to process
     *
     * @return string The content with all shortcodes removed.
     */
    protected function processRemoveShortcodes(string $content): string
    {
        $pattern = '/\[([a-zA-Z0-9\-_]+)(.*?)\]/';
        return preg_replace($pattern, '', $content);
    }

    /**
     * Parse the given content for shortcodes and replace them with
     * their corresponding output by calling the associated callback.
     *
     * Shortcodes are expected in the format: [shortcodeName attribute1="value1" attribute2="value2"]
     * If the shortcode exists in the registered shortcodes, its callback is called
     * with the parsed attributes. Otherwise, the original shortcode text is returned.
     *
     * @param string $content The content containing shortcodes to be parsed.
     * @return string The content with shortcodes replaced by their output or the original shortcode text.
     */
    protected function parseShortcodes(string $content): string
    {
        $pattern = '/\[([a-zA-Z0-9\-_]+)(.*?)\]/';

        return preg_replace_callback($pattern, function ($matches) {
            $shortcode = $matches[1];
            $attributes = $this->parseShortcodesAttributes($matches[2] ?? '');
            if (isset(static::$shortcodes[$shortcode])) {
                return call_user_func(static::$shortcodes[$shortcode], $attributes);
            }

            // If the shortcode doesn't exist, return the original content
            return $matches[0];
        }, $content);
    }

    /**
     * Parse a string of attributes in a shortcode, for example:
     *
     * [shortcode attr1="value1" attr2="value2"]
     *
     * and return an associative array with the attribute names as keys and
     * the attribute values as values.
     *
     * @param string $attrString
     * @return array
     */
    protected function parseShortcodesAttributes(string $attrString): array
    {
        $matches = explode("\" ", htmlspecialchars_decode(trim($attrString)));
        $attributes = [];
        foreach ($matches as $match) {
            $attrMatches = explode('=', $match);
            if (count($attrMatches) === 2) {
                $attributes[$attrMatches[0]] = trim($attrMatches[1], '"');
            }
        }
        return $attributes;
    }
}
