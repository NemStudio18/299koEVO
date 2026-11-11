<?php

namespace Docs\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @copyright (C) 2022, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Maxime Blanc <maximeblanc@flexcb.fr>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class WikiPageHistory extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'docs/history.json';
    protected static string $tableName = 'wiki_history';
    protected static string $primaryKey = 'id';

    public function __construct($data = []) {
        parent::__construct($data);
        if (empty($this->version)) {
            $this->version = 1;
        }
        if (empty($this->modifiedAt)) {
            $this->modifiedAt = date('Y-m-d H:i:s');
        }
    }

    // Getters / Setters for attributes are handled by __get / __set in JsonActiveRecord
    // but we can keep manual ones if needed for IDE completion or specific logic.
    
    public function getId() { return $this->id; }
    public function getPageId() { return $this->pageId; }
    public function getVersion() { return $this->version; }
    public function getName() { return $this->name; }
    public function getContent() { return $this->content; }
    public function getIntro() { return $this->intro; }
    public function getSEODesc() { return $this->seoDesc; }
    public function getModifiedBy() { return $this->modifiedBy; }
    public function getModifiedAt() { return $this->modifiedAt; }
    public function getChangeDescription() { return $this->changeDescription; }

    public function setId($val) { $this->id = $val; }
    public function setPageId($val) { $this->pageId = $val; }
    public function setVersion($val) { $this->version = $val; }
    public function setName($val) { $this->name = $val; }
    public function setContent($val) { $this->content = $val; }
    public function setIntro($val) { $this->intro = $val; }
    public function setSEODesc($val) { $this->seoDesc = $val; }
    public function setModifiedBy($val) { $this->modifiedBy = $val; }
    public function setModifiedAt($val) { $this->modifiedAt = $val; }
    public function setChangeDescription($val) { $this->changeDescription = $val; }
}