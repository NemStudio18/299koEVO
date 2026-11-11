<?php

namespace Contact\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Message de Contact
 */
class ContactMessage extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'contact/messages.json';
    protected static string $tableName = 'contact_messages';
    protected static string $primaryKey = 'id';

    public function __construct($data = [])
    {
        parent::__construct($data);
        if (empty($this->date)) {
            $this->date = date('Y-m-d H:i:s');
        }
    }
}
