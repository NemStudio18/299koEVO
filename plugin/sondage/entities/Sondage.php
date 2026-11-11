<?php
namespace Sondage\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class Sondage extends JsonActiveRecord
{
    protected static string $tableName = 'sondages';
    protected static string $filePath = DATA_PLUGIN . 'sondage/sondages.json';
    protected static string $primaryKey = 'id';
    
    protected static array $casts = [
        'options' => 'array',
        'votes' => 'array',
        'active' => 'integer',
        'isMultipleChoice' => 'integer',
        'allowOther' => 'integer'
    ];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if (empty($this->attributes['date'])) {
            $this->attributes['date'] = date('Y-m-d H:i:s');
        }
    }

    public function setId($val) { $this->attributes['id'] = $val; }
    public function setTitle($val) { $this->attributes['title'] = trim($val); }
    public function setOptions($val) { $this->attributes['options'] = is_array($val) ? $val : []; }
    public function setActive($val) { $this->attributes['active'] = (int)$val; }
    public function setVotes($val) { $this->attributes['votes'] = is_array($val) ? $val : []; }
    public function setDate($val) { $this->attributes['date'] = $val ?: date('Y-m-d H:i:s'); }
    public function setCloseDate($val) { $this->attributes['closeDate'] = $val ?: null; }
    public function setIsMultipleChoice($val) { $this->attributes['isMultipleChoice'] = (int)$val; }
    public function setAllowOther($val) { $this->attributes['allowOther'] = (int)$val; }

    public function getId() { return $this->attributes['id'] ?? null; }
    public function getTitle() { return $this->attributes['title'] ?? ''; }
    public function getOptions() { return $this->attributes['options'] ?? []; }
    public function getActive() { return (int)($this->attributes['active'] ?? 0) === 1; }
    public function getIsMultipleChoice() { return (int)($this->attributes['isMultipleChoice'] ?? 0) === 1; }
    public function getAllowOther() { return (int)($this->attributes['allowOther'] ?? 0) === 1; }
    public function getVotes() { return $this->attributes['votes'] ?? []; }
    public function getDate() { return $this->attributes['date'] ?? ''; }
    public function getCloseDate() { return $this->attributes['closeDate'] ?? null; }

    public function isClosed()
    {
        $closeDate = $this->getCloseDate();
        if ($closeDate === null || empty($closeDate)) {
            return false;
        }
        return strtotime($closeDate) <= time();
    }

    public function getTotalVotes()
    {
        return count($this->getVotes());
    }

    public function getUrl()
    {
        return \Core\Router\Router::getInstance()->generate('sondage-read', ['id' => $this->getId()]);
    }

    public function getVotesForOption($optionIndex)
    {
        $count = 0;
        foreach ($this->getVotes() as $vote) {
            if (isset($vote['option']) && $vote['option'] == $optionIndex) {
                $count++;
            }
            if (isset($vote['options']) && is_array($vote['options']) && in_array($optionIndex, $vote['options'])) {
                $count++;
            }
        }
        return $count;
    }

    public function getOtherVotes()
    {
        $others = [];
        foreach ($this->getVotes() as $vote) {
            if (!empty($vote['other'])) {
                $others[] = $vote['other'];
            }
        }
        return $others;
    }

    public function hasUserVoted($userId = null, $fingerprint = null)
    {
        if (!$userId) return false;
        foreach ($this->getVotes() as $vote) {
            if (isset($vote['userId']) && $vote['userId'] == $userId) {
                return true;
            }
        }
        return false;
    }

    public function addVote($options, $userId = null, $otherText = null)
    {
        $votes = $this->getVotes();
        $votes[] = [
            'options' => is_array($options) ? $options : [intval($options)],
            'userId' => $userId,
            'other' => $otherText ? trim(filter_var($otherText, FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : null,
            'date' => date('Y-m-d H:i:s')
        ];
        $this->setVotes($votes);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
