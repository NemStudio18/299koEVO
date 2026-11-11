<?php

namespace Agenda\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class Attendance extends JsonActiveRecord
{
    protected static string $tableName = 'agenda_attendances';
    protected static string $filePath = DATA_PLUGIN . 'agenda/attendances.json';
    protected static string $primaryKey = 'id';

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}
