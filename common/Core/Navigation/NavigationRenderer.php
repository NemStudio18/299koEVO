<?php

namespace Core\Navigation;

/**
 * Renderer sécurisé pour la navigation
 * 
 * Responsabilités :
 * - Rendu récursif HTML
 * - Échappement XSS
 * - Support templates personnalisés
 * - Format flexible
 */
defined('ROOT') or exit('Access denied!');

class NavigationRenderer
{
    /**
     * Format par défaut pour un item
     */
    private string $itemFormat;

    /**
     * Format par défaut pour les enfants
     */
    private string $childrenWrapperFormat;

    /**
     * Options de rendu
     */
    private array $options;

    /**
     * Constructeur
     */
    public function __construct(
        string $itemFormat = '<li><a class="[cssClass]" href="[target]" target="[targetAttribut]">[icon][label]</a>[childrens]</li>',
        string $childrenWrapperFormat = '<ul>[childrens]</ul>',
        array $options = []
    ) {
        $this->itemFormat = $itemFormat;
        $this->childrenWrapperFormat = $childrenWrapperFormat;
        $this->options = array_merge([
            'escapeHtml' => true,
            'iconFormat' => '<i class="[icon]"></i> ',
            'emptyIcon' => '',
            'showIcons' => true,
        ], $options);
    }

    /**
     * Rend l'arbre complet
     */
    public function render(NavigationTree $tree): string
    {
        $rootItems = $tree->getRootItems();
        return $this->renderItems($rootItems);
    }

    /**
     * Rend une liste d'items récursivement
     */
    private function renderItems(array $items): string
    {
        $output = '';

        foreach ($items as $item) {
            $output .= $this->renderItem($item);
        }

        return $output;
    }

    /**
     * Rend un item unique avec ses enfants
     */
    private function renderItem(NavigationItem $item): string
    {
        // Rendre les enfants d'abord
        $children = $item->getChildren();
        $childrenHtml = '';
        if (!empty($children)) {
            $childrenContent = $this->renderItems($children);
            $childrenHtml = $this->formatChildrenWrapper($childrenContent);
        }

        // Rendre l'item
        $html = $this->itemFormat;

        // Remplacer les placeholders
        $html = str_replace('[target]', $this->escape($item->getTarget()), $html);
        $html = str_replace('[targetAttribut]', $this->escape($item->getTargetAttr()), $html);
        $html = str_replace('[cssClass]', $this->escape($item->getCssClass()), $html);
        $html = str_replace('[label]', $this->escape($item->getLabel()), $html);
        $html = str_replace('[icon]', $this->renderIcon($item->getIcon()), $html);
        $html = str_replace('[childrens]', $childrenHtml, $html);

        // Remplacer [id] si présent
        $html = str_replace('[id]', $this->escape($item->getId()), $html);

        return $html;
    }

    /**
     * Rend l'icône si présente
     */
    private function renderIcon(?string $icon): string
    {
        if ($icon === null || $icon === '' || !$this->options['showIcons']) {
            return $this->options['emptyIcon'];
        }

        $format = $this->options['iconFormat'];
        return str_replace('[icon]', $this->escape($icon), $format);
    }

    /**
     * Formate le wrapper des enfants
     */
    private function formatChildrenWrapper(string $childrenContent): string
    {
        return str_replace('[childrens]', $childrenContent, $this->childrenWrapperFormat);
    }

    /**
     * Échappe le HTML (protection XSS)
     */
    private function escape(string $value): string
    {
        if (!$this->options['escapeHtml']) {
            return $value;
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Crée un renderer avec format personnalisé
     */
    public static function create(string $itemFormat, string $childrenWrapperFormat = '<ul>[childrens]</ul>', array $options = []): self
    {
        return new self($itemFormat, $childrenWrapperFormat, $options);
    }

    /**
     * Rend avec un format minimal (pour tests)
     */
    public static function renderMinimal(NavigationTree $tree): string
    {
        $renderer = new self(
            '<li><a href="[target]">[label]</a>[childrens]</li>',
            '<ul>[childrens]</ul>'
        );
        return $renderer->render($tree);
    }
}

