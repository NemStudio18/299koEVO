<?php
namespace Guestbook\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class GuestbookEntry extends JsonActiveRecord
{
    protected static string $table = 'guestbook';
    protected static string $primaryKey = 'id';
    
    protected static array $attributeMapping = [
        'admin_reply' => 'adminReply',
        'admin_reply_date' => 'adminReplyDate',
        'admin_reply_author' => 'adminReplyAuthor'
    ];

    protected static array $casts = [
        'likes' => 'array',
        'approved' => 'integer'
    ];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if (empty($this->attributes['date'])) {
            $this->attributes['date'] = date('Y-m-d H:i:s');
        }
    }

    public function setId($val) { $this->attributes['id'] = intval($val); }
    public function setName($val) { $this->attributes['name'] = trim(filter_var($val, FILTER_SANITIZE_FULL_SPECIAL_CHARS)); }
    public function setEmail($val) { $this->attributes['email'] = trim(filter_var($val, FILTER_SANITIZE_EMAIL)); }
    public function setMessage($val) { $this->attributes['message'] = trim(filter_var($val, FILTER_SANITIZE_FULL_SPECIAL_CHARS)); }
    public function setApproved($val) { $this->attributes['approved'] = (int)$val; }
    public function setDate($val) { $this->attributes['date'] = $val ?: date('Y-m-d H:i:s'); }
    public function setFingerprint($val) { $this->attributes['fingerprint'] = trim($val); }
    public function setLikes($val) { $this->attributes['likes'] = is_array($val) ? $val : []; }
    public function setAdminReply($val) { $this->attributes['adminReply'] = trim(filter_var($val, FILTER_SANITIZE_FULL_SPECIAL_CHARS)); }
    public function setAdminReplyDate($val) { $this->attributes['adminReplyDate'] = $val ?: date('Y-m-d H:i:s'); }
    public function setAdminReplyAuthor($val) { $this->attributes['adminReplyAuthor'] = trim(filter_var($val, FILTER_SANITIZE_FULL_SPECIAL_CHARS)); }

    public function getId() { return isset($this->attributes['id']) ? (int)$this->attributes['id'] : null; }
    public function getName() { return $this->attributes['name'] ?? ''; }
    public function getEmail() { return $this->attributes['email'] ?? ''; }
    public function getMessage() { return $this->attributes['message'] ?? ''; }
    public function getApproved() { return $this->attributes['approved'] ?? 0; }
    public function getDate() { return $this->attributes['date'] ?? ''; }
    public function getFingerprint() { return $this->attributes['fingerprint'] ?? ''; }
    public function getLikes() { return $this->attributes['likes'] ?? []; }
    public function getAdminReply() { return $this->attributes['adminReply'] ?? ''; }
    public function getAdminReplyDate() { return $this->attributes['adminReplyDate'] ?? null; }
    public function getAdminReplyAuthor() { return $this->attributes['adminReplyAuthor'] ?? ''; }

    public function isApproved() { return (int)($this->attributes['approved'] ?? 0) === 1; }
    public function getLikesCount() { return count($this->getLikes()); }
    public function hasLiked($fingerprint) { return in_array($fingerprint, $this->getLikes()); }
    public function hasAdminReply() { return !empty($this->attributes['adminReply']); }

    public function addLike($fingerprint)
    {
        if (!$this->hasLiked($fingerprint)) {
            $likes = $this->getLikes();
            $likes[] = $fingerprint;
            $this->setLikes($likes);
            return true;
        }
        return false;
    }

    public function removeLike($fingerprint)
    {
        $likes = $this->getLikes();
        $key = array_search($fingerprint, $likes);
        if ($key !== false) {
            unset($likes[$key]);
            $this->setLikes(array_values($likes));
            return true;
        }
        return false;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
