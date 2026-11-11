<?php

namespace Galerie\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Catégorie de Galerie
 */
class GalerieCategory extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'galerie/categories.json';
    protected static string $tableName = 'galerie_categories';
    protected static string $primaryKey = 'id';

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
}
