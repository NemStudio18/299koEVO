<?php

namespace Parrainage\Entities;

defined('ROOT') or exit('Access denied!');

class ParrainageCode
{
    public int $id;
    public ?int $userId;
    public ?string $email;
    public string $code;
    public float $commissionPercent;
    public string $createdAt;

    public function __construct(array $data = [])
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $this->email = $data['email'] ?? null;
        $this->code = (string)($data['code'] ?? '');
        $this->commissionPercent = (float)($data['commission_percent'] ?? 0.0);
        $this->createdAt = (string)($data['created_at'] ?? '');
    }
}
