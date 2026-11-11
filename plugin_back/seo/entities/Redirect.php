<?php

namespace Seo\Entities;

defined('ROOT') or exit('Access denied!');

/**
 * Entité de redirection SEO
 * Gère les redirections 301/302 et le suivi des hits.
 */
class Redirect extends \Core\Storage\JsonActiveRecord
{
    protected static string $tableName = 'seo_redirects';
    protected static string $filePath = DATA_PLUGIN . 'seo/redirects.json';
    protected static string $primaryKey = 'id';
    protected static array $schema = [
        'old_url' => 'string',
        'new_url' => 'string',
        'code' => 'integer',
        'hits' => 'integer',
        'last_hit' => 'string',
        'active' => 'boolean',
        'created_at' => 'string',
        'updated_at' => 'string'
    ];
    protected static array $indexes = ['old_url', 'active'];

    public function __construct($data = [])
    {
        parent::__construct($data);
        if (empty($this->id)) {
            $this->active = true;
            $this->hits = 0;
            $this->code = 301;
            $this->created_at = date('Y-m-d H:i:s');
        }
    }

    public static function findMatch(string $url): ?self
    {
        // Nettoyer l'URL pour la recherche (sans query string si besoin, ou match exact)
        $url = '/' . ltrim($url, '/');
        
        return self::query()
            ->where('old_url', '=', $url)
            ->where('active', '=', 1)
            ->first();
    }

    public function incrementHits(): void
    {
        $this->hits = (int)$this->hits + 1;
        $this->last_hit = date('Y-m-d H:i:s');
        $this->save();
    }
}
