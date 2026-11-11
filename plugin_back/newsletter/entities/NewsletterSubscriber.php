<?php

namespace Newsletter\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class NewsletterSubscriber extends JsonActiveRecord
{
    protected static string $tableName = 'newsletter_subscribers';
    protected static string $filePath = DATA_PLUGIN . 'newsletter/subscribers.json';
    protected static string $primaryKey = 'id';

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if (empty($this->token)) {
            $this->token = $this->generateToken();
        }
    }

    private function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    public function setId($val)
    {
        $this->attributes['id'] = intval($val);
    }

    public function setEmail($val)
    {
        $this->attributes['email'] = trim(filter_var($val, FILTER_SANITIZE_EMAIL));
    }

    public function setDate($val)
    {
        $this->attributes['date'] = $val;
    }

    public function setToken($val)
    {
        $this->attributes['token'] = $val;
    }

    public function setActive($val)
    {
        $this->attributes['active'] = $val ? '1' : '0';
    }

    public function setUserId($val)
    {
        $this->attributes['userId'] = $val ? intval($val) : null;
    }

    public function getId()
    {
        return isset($this->attributes['id']) ? (int)$this->attributes['id'] : null;
    }

    public function getEmail()
    {
        return $this->attributes['email'] ?? '';
    }

    public function getDate()
    {
        return $this->attributes['date'] ?? date('Y-m-d H:i:s');
    }

    public function getToken()
    {
        return $this->attributes['token'] ?? '';
    }

    public function isActive()
    {
        return ($this->attributes['active'] ?? '1') == '1';
    }

    public function getUserId()
    {
        return isset($this->attributes['userId']) ? (int)$this->attributes['userId'] : null;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}


