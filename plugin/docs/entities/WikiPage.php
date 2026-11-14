<?php

namespace Docs\Entities;

use Content\ContentParser;
use Core\Router\Router;
use Core\Storage\JsonActiveRecord;
use Utils\Util;

/**
 * @copyright (C) 2022, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * @author Maxime Blanc <maximeblanc@flexcb.fr>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * Page Wiki
 * 
 * Classe représentant une page du plugin Wiki
 */
class WikiPage extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'docs/pages.json';
    protected static string $tableName = 'wiki_pages';
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'categories' => 'json',
        'draft' => 'string',
        'position' => 'integer',
        'version' => 'integer'
    ];

    private ?ContentParser $parser = null;

    /**
     * Constructeur de la page Wiki
     * 
     * @param array $val Données de la page
     */
    public function __construct($val = [])
    {
        parent::__construct($val);
        // On s'assure que les objets parser sont bien initialisés
        $this->parser = new ContentParser($this->content ?? '');
    }

    /**
     * Obtenir le contenu parsé de la page
     * 
     * @return string Contenu parsé
     */
    public function getParsedContent(): string
    {
        return $this->parser->getParsedContent();
    }

    /**
     * Obtenir le contenu sans shortcodes
     * 
     * @return string Contenu sans shortcodes
     */
    public function getContentWithoutShortcodes(): string
    {
        return $this->parser->getWithoutShortcodesContent();
    }

    /**
     * Obtenir l'URL de la page
     * 
     * @return string URL de la page
     */
    public function getUrl()
    {
        return Router::getInstance()->generate('docs-read', ['name' => $this->slug, 'id' => $this->id]);
    }

    /**
     * Obtenir la date lisible de la page
     * 
     * @return string Date formatée
     */
    public function getReadableDate()
    {
        return Util::getDate($this->date);
    }

    /**
     * Obtenir l'URL de l'image de la page
     * 
     * @return string URL de l'image
     */
    public function getImgUrl()
    {
        return Util::urlBuild($this->img);
    }

    /**
     * Incrémenter la version de la page
     * 
     * @return void
     */
    public function incrementVersion()
    {
        $this->version++;
        $this->lastModified = date('Y-m-d H:i:s');
    }

    // Setters overload to handle logic (like slug generation)
    public function setName($val)
    {
        $this->attributes['name'] = trim($val);
        $this->attributes['slug'] = Util::strToUrl($val);
    }

    public function setContent($val)
    {
        $this->attributes['content'] = trim($val);
        if ($this->parser) {
            $this->parser->setContent($this->attributes['content']);
        }
    }

    // Getters for IDE / convenience
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getContent() { return $this->content; }
    public function getIntro() { return $this->intro; }
    public function getSEODesc() { return $this->seoDesc; }
    public function getDate() { return $this->date; }
    public function getDraft() { return $this->draft; }
    public function getImg() { return $this->img; }
    public function getPosition() { return $this->position; }
    public function getVersion() { return $this->version; }
    public function getLastModified() { return $this->lastModified; }
    public function getModifiedBy() { return $this->modifiedBy; }
    public function getSlug() { return $this->slug; }

    public function setId($val) { $this->id = $val; }
    public function setIntro($val) { $this->intro = trim($val); }
    public function setSEODesc($val) { $this->seoDesc = trim($val); }
    public function setDate($val) { $this->date = $val ?: date('Y-m-d'); }
    public function setDraft($val) { $this->draft = $val; }
    public function setImg($val) { $this->img = trim($val); }
    public function setPosition($val) { $this->position = intval($val); }
    public function setVersion($val) { $this->version = intval($val); }
    public function setLastModified($val) { $this->lastModified = $val; }
    public function setModifiedBy($val) { $this->modifiedBy = $val; }
    public function setSlug($val) { $this->slug = $val; }

    public function toArray(): array
    {
        return $this->attributes;
    }
}