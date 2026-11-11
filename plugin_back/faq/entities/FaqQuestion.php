<?php

namespace Faq\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class FaqQuestion extends JsonActiveRecord
{
    protected static string $tableName = 'faq';
    protected static string $filePath = DATA_PLUGIN . 'faq/faq.json';
    protected static string $primaryKey = 'id';

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function setId($val)
    {
        $this->attributes['id'] = intval($val);
    }

    public function setQuestion($val)
    {
        $this->attributes['question'] = trim(filter_var($val, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    }

    public function setAnswer($val)
    {
        $this->attributes['answer'] = trim($val);
    }

    public function setCategory($val)
    {
        $this->attributes['category'] = trim(filter_var($val, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    }

    public function setOrder($val)
    {
        $this->attributes['order'] = intval($val);
    }

    public function setVotes($val)
    {
        $this->attributes['votes'] = intval($val);
    }

    public function setFingerprints($val)
    {
        $this->attributes['fingerprints'] = is_array($val) ? $val : [];
    }

    public function setDate($val)
    {
        $this->attributes['date'] = $val;
    }

    public function setActive($val)
    {
        $this->attributes['active'] = $val ? '1' : '0';
    }

    public function setEmail($val)
    {
        $this->attributes['email'] = filter_var($val, FILTER_SANITIZE_EMAIL);
    }

    public function getEmail()
    {
        return $this->attributes['email'] ?? '';
    }

    public function getId()
    {
        return isset($this->attributes['id']) ? (int)$this->attributes['id'] : null;
    }

    public function getQuestion()
    {
        return $this->attributes['question'] ?? '';
    }

    public function getAnswer()
    {
        return $this->attributes['answer'] ?? '';
    }

    public function getCategory()
    {
        return $this->attributes['category'] ?? '';
    }

    public function getOrder()
    {
        return isset($this->attributes['order']) ? (int)$this->attributes['order'] : 0;
    }

    public function getVotes()
    {
        return isset($this->attributes['votes']) ? (int)$this->attributes['votes'] : 0;
    }

    public function getFingerprints()
    {
        return $this->attributes['fingerprints'] ?? [];
    }

    public function getDate()
    {
        return $this->attributes['date'] ?? date('Y-m-d H:i:s');
    }

    public function isActive()
    {
        return ($this->attributes['active'] ?? '1') == '1';
    }

    public function hasVoted($fingerprint)
    {
        return in_array($fingerprint, $this->getFingerprints());
    }

    public function addVote($fingerprint)
    {
        if (!$this->hasVoted($fingerprint)) {
            $votes = $this->getVotes();
            $this->setVotes($votes + 1);
            $fps = $this->getFingerprints();
            $fps[] = $fingerprint;
            $this->setFingerprints($fps);
        }
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}

