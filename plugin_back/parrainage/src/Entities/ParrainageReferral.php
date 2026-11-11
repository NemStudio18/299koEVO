<?php

namespace Parrainage\Entities;

defined('ROOT') or exit('Access denied!');

class ParrainageReferral
{
    public int $id;
    public int $codeId;
    public int $referredUserId;
    public string $createdAt;

    public function __construct(array $data = [])
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->codeId = (int)($data['code_id'] ?? 0);
        $this->referredUserId = (int)($data['referred_user_id'] ?? 0);
        $this->createdAt = (string)($data['created_at'] ?? '');
    }
}
