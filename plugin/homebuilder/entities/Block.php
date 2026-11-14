<?php

namespace HomeBuilder\Entities;

use Core\Lang;
use Core\Plugin\PluginsManager;
use Core\Router\Router;
use Core\Storage\JsonActiveRecord;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class Block extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'homebuilder/blocks.json';
    protected static string $tableName = 'homepage_blocks';
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'active' => 'boolean',
        'order' => 'integer',
        'options' => 'json',
        'children' => 'json',
        'styles' => 'json'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
        if (empty($this->type)) {
            $this->type = 'text';
        }
        if (empty($this->order)) {
            $this->order = 1;
        }
        if (!isset($this->active)) {
            $this->active = true;
        }
        if (empty($this->styles)) {
            $this->styles = $this->getDefaultStyles();
        }
    }

    // IDE Helpers for properties handled by JsonActiveRecord
    public function getId() { return $this->id; }
    public function getType() { return $this->type; }
    public function getTitle() { return $this->title; }
    public function getContent() { return $this->content; }
    public function getOrder() { return $this->order; }
    public function isActive() { return $this->active; }
    public function getOptions() { return $this->options; }
    public function getParentId() { return $this->parentId; }
    public function getChildren() { return $this->children; }
    public function getStyles() { return $this->styles; }

    public function setId($val) { $this->id = $val; }
    public function setType($val) { $this->type = $val; }
    public function setTitle($val) { $this->title = $val; }
    public function setContent($val) { $this->content = $val; }
    public function setOrder($val) { $this->order = intval($val); }
    public function setActive($val) { $this->active = (bool)$val; }
    public function setOptions($val) { $this->options = $val; }
    public function setParentId($val) { $this->parentId = $val; }
    public function setChildren($val) { $this->children = $val; }
    public function setStyles($val) { $this->styles = $val; }

    public function toArray(): array
    {
        return $this->attributes;
    }

    private function getDefaultStyles()
    {
        return [
            'container' => [
                'backgroundColor' => '#ffffff',
                'color' => '#333333',
                'padding' => '20px',
                'margin' => '10px 0',
                'borderRadius' => '8px',
                'border' => '1px solid #e0e0e0',
                'boxShadow' => '0 2px 4px rgba(0,0,0,0.1)',
                'width' => '100%',
                'maxWidth' => 'none',
                'textAlign' => 'left'
            ],
            'title' => [
                'color' => '#333333',
                'fontSize' => '24px',
                'fontWeight' => 'bold',
                'marginBottom' => '15px',
                'textAlign' => 'left'
            ],
            'content' => [
                'color' => '#666666',
                'fontSize' => '16px',
                'lineHeight' => '1.6',
                'marginBottom' => '15px'
            ],
            'links' => [
                'color' => '#007bff',
                'textDecoration' => 'underline'
            ],
            'button' => [
                'backgroundColor' => '#007bff',
                'color' => '#ffffff',
                'padding' => '10px 20px',
                'borderRadius' => '5px',
                'border' => 'none',
                'fontSize' => '16px',
                'cursor' => 'pointer'
            ]
        ];
    }

    public function generateCustomCSS()
    {
        $css = '';
        $blockId = 'block-' . $this->id;
        
        $camelToKebab = function($property) {
            return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));
        };
        
        $processValue = function($value) {
            if ($value === '' || $value === null) return false;
            return $value;
        };
        
        $styles = $this->styles;
        foreach (['container' => '', 'title' => ' h2, #$blockId h3', 'content' => ' .content', 'links' => ' a', 'button' => ' .btn'] as $key => $suffix) {
            if (!empty($styles[$key])) {
                $selector = ($key === 'container') ? "#$blockId" : "#$blockId$suffix";
                $css .= "$selector {";
                foreach ($styles[$key] as $property => $value) {
                    $processedValue = $processValue($value);
                    if ($processedValue !== false) {
                        $css .= " " . $camelToKebab($property) . ": $processedValue;";
                    }
                }
                $css .= " }";
            }
        }
        
        return $css;
    }

    public function render()
    {
        $blockId = 'block-' . $this->id;
        $customCSS = $this->generateCustomCSS();
        $html = '';
        if (!empty($customCSS)) $html .= "<style>$customCSS</style>";
        $html .= '<div id="' . $blockId . '" class="homepage-block homepage-block-' . $this->type . '">';
        
        $method = 'render' . ucfirst($this->type);
        if (method_exists($this, $method)) {
            $html .= $this->$method();
        } else {
            $html .= $this->renderText();
        }
        
        $html .= '</div>';
        return $html;
    }

    private function renderText()
    {
        $html = '';
        if ($this->title) $html .= '<h2>' . htmlspecialchars($this->title) . '</h2>';
        $html .= '<div class="content">' . nl2br(htmlspecialchars($this->content)) . '</div>';
        return $html;
    }

    private function renderButton()
    {
        $url = $this->options['url'] ?? '#';
        $buttonText = $this->options['button_text'] ?? 'Cliquer ici';
        $html = $this->renderText();
        $html .= '<a href="' . htmlspecialchars($url) . '" class="btn">' . htmlspecialchars($buttonText) . '</a>';
        return $html;
    }

    private function renderTable()
    {
        $headers = $this->options['headers'] ?? [];
        $rows = $this->options['rows'] ?? [];
        $html = $this->renderText();
        if (!empty($headers) || !empty($rows)) {
            $html .= '<table class="table">';
            if (!empty($headers)) {
                $html .= '<thead><tr>';
                foreach ($headers as $header) $html .= '<th>' . htmlspecialchars($header) . '</th>';
                $html .= '</tr></thead>';
            }
            if (!empty($rows)) {
                $html .= '<tbody>';
                foreach ($rows as $row) {
                    $html .= '<tr>';
                    foreach ($row as $cell) $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
            }
            $html .= '</table>';
        }
        return $html;
    }

    private function renderForm()
    {
        $fields = $this->options['fields'] ?? [];
        $action = $this->options['action'] ?? '';
        $method = $this->options['method'] ?? 'POST';
        $html = $this->renderText();
        $html .= '<form action="' . htmlspecialchars($action) . '" method="' . $method . '">';
        foreach ($fields as $field) {
            $type = $field['type'] ?? 'text';
            $name = $field['name'] ?? '';
            $label = $field['label'] ?? '';
            $required = ($field['required'] ?? false) ? 'required' : '';
            $html .= '<div class="form-group">';
            if ($label) $html .= '<label for="' . $name . '">' . htmlspecialchars($label) . '</label>';
            if ($type === 'textarea') $html .= '<textarea name="' . $name . '" id="' . $name . '" ' . $required . '></textarea>';
            else $html .= '<input type="' . $type . '" name="' . $name . '" id="' . $name . '" ' . $required . '>';
            $html .= '</div>';
        }
        $html .= '<button type="submit" class="btn">Envoyer</button></form>';
        return $html;
    }

    private function renderImage()
    {
        $src = $this->options['src'] ?? '';
        $alt = $this->options['alt'] ?? '';
        $html = $this->renderText();
        if ($src) $html .= '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '" class="block-image">';
        return $html;
    }

    private function renderHtml()
    {
        $html = '';
        if ($this->title) $html .= '<h2>' . htmlspecialchars($this->title) . '</h2>';
        $html .= '<div class="content">' . $this->content . '</div>';
        return $html;
    }

    private function renderContainer()
    {
        $html = $this->renderText();
        if (!empty($this->children)) {
            $html .= '<div class="block-children">';
            foreach ($this->children as $child) {
                if (is_array($child)) {
                    $childBlock = new Block($child);
                    $html .= $childBlock->render();
                }
            }
            $html .= '</div>';
        }
        return $html;
    }

    public function addChild($childBlock)
    {
        $children = $this->children;
        $children[] = $childBlock;
        $this->children = $children;
    }

    public function removeChild($childId)
    {
        $children = $this->children;
        foreach ($children as $key => $child) {
            if (is_array($child) && $child['id'] === $childId) {
                unset($children[$key]);
                break;
            }
        }
        $this->children = array_values($children);
    }

    public function isContainer() { return $this->type === 'container'; }
    public function hasChildren() { return !empty($this->children); }

    private function renderLatestArticles(): string
    {
        $pluginsManager = PluginsManager::getInstance();
        if (!$pluginsManager->isActivePlugin('blog') || !class_exists('\\newsManager')) {
            return $this->renderInfoMessage(Lang::get('homebuilder.messages.blog-unavailable', 'Activez le plugin Blog pour afficher des articles.'));
        }
        $manager = new \newsManager();
        $count = max(1, (int)($this->options['count'] ?? 3));
        $articles = array_slice(array_filter($manager->getItems(), fn($a) => method_exists($a, 'getDraft') && !$a->getDraft()), 0, $count);
        if (empty($articles)) return $this->renderInfoMessage(Lang::get('homebuilder.messages.latest-articles-empty', 'Aucun article disponible pour le moment.'));
        $router = Router::getInstance();
        $html = '<div class="latest-articles">';
        if ($this->title) $html .= '<h2>' . htmlspecialchars($this->title) . '</h2>';
        $html .= '<ul class="latest-articles-list">';
        foreach ($articles as $article) {
            $url = $router->generate('blog-read', ['id' => $article->getId(), 'name' => Util::strToUrl($article->getName())]);
            $intro = $article->getIntro() ? strip_tags(htmlspecialchars_decode($article->getIntro())) : '';
            $intro = mb_substr($intro, 0, 160) . (mb_strlen($intro) > 160 ? '…' : '');
            $html .= '<li class="latest-article-item"><a href="' . htmlspecialchars($url) . '"><span class="article-title">' . htmlspecialchars($article->getName()) . '</span><span class="article-intro">' . htmlspecialchars($intro) . '</span><span class="article-date">' . htmlspecialchars(Util::FormatDate($article->getDate(), 'en', Lang::getLocale())) . '</span></a></li>';
        }
        $html .= '</ul></div>';
        return $html;
    }

    private function renderLatestSondages(): string
    {
        $pluginsManager = PluginsManager::getInstance();
        if (!$pluginsManager->isActivePlugin('sondage') || !class_exists('\\SondageManager')) return $this->renderInfoMessage('Sondage indisponible');
        $manager = new \SondageManager();
        $polls = array_slice($manager->getActiveItems(), 0, (int)($this->options['count'] ?? 2));
        if (empty($polls)) return $this->renderInfoMessage('Aucun sondage');
        $html = '<div class="latest-sondages">';
        if ($this->title) $html .= '<h2>' . htmlspecialchars($this->title) . '</h2>';
        foreach ($polls as $poll) $html .= '<div class="sondage-item"><a href="' . Router::getInstance()->generate('sondage-read', ['id' => $poll->getId()]) . '">' . htmlspecialchars($poll->getTitle()) . '</a></div>';
        $html .= '</div>';
        return $html;
    }

    private function renderGuestbookCta(): string
    {
        if (!PluginsManager::getInstance()->isActivePlugin('guestbook')) return $this->renderInfoMessage('Guestbook indisponible');
        return '<div class="guestbook-cta">' . ($this->title ? '<h2>' . htmlspecialchars($this->title) . '</h2>' : '') . '<a class="btn" href="' . Router::getInstance()->generate('guestbook-home') . '">Signer</a></div>';
    }

    private function renderInfoMessage(string $message): string { return '<div class="homebuilder-info">' . htmlspecialchars($message) . '</div>'; }
}
