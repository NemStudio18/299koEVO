<?php

namespace Dons\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class Don extends JsonActiveRecord
{
    protected static string $tableName = 'dons';
    protected static string $filePath = DATA_PLUGIN . 'dons/dons.json';
    protected static string $primaryKey = 'id';

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if (empty($this->status)) {
            $this->status = 'pending';
        }
        if (empty($this->date)) {
            $this->date = date('Y-m-d H:i:s');
        }
        if (!isset($this->anonymous)) {
            $this->anonymous = '0';
        }
    }

    public function isAnonymous()
    {
        return $this->anonymous == '1';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
