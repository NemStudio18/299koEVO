<?php

namespace Docs\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class WikiActivity extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'docs/activities.json';
    protected static string $tableName = 'wiki_activities';
    protected static string $primaryKey = 'id';
    
    protected static array $casts = [
        'pageId' => 'integer',
        'timestamp' => 'integer'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
        if (empty($this->attributes['timestamp'])) {
            $this->attributes['timestamp'] = time();
        }
        if (empty($this->attributes['date'])) {
            $this->attributes['date'] = date('Y-m-d H:i:s');
        }
    }

    public function getId() { return $this->id; }
    public function getAction() { return $this->action; }
    public function getPageName() { return $this->pageName; }
    public function getCategoryName() { return $this->categoryName; }
    public function getPageId() { return $this->pageId; }
    public function getTimestamp() { return $this->timestamp; }
    public function getDate() { return $this->date; }

    public function setId($val) { $this->id = $val; }
    public function setAction($val) { $this->action = $val; }
    public function setPageName($val) { $this->pageName = $val; }
    public function setCategoryName($val) { $this->categoryName = $val; }
    public function setPageId($val) { $this->pageId = $val; }
    public function setTimestamp($val) { $this->timestamp = $val; }
    public function setDate($val) { $this->date = $val; }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
