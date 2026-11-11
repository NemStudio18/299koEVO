<?php

namespace Core\Navigation\Providers;

use Core\Navigation\Interfaces\ConditionProviderInterface;
use Core\Navigation\ConditionContext;
use Utils\Util;
use Core\Core;

/**
 * Fournisseur de condition JSON.
 * Type: 'json'
 * Config: [
 *   'file' => 'settings.json', (Path relative to DATA)
 *   'key' => 'registration_enabled',
 *   'value' => true
 * ]
 */
class JsonProvider implements ConditionProviderInterface
{
    public function getId(): string
    {
        return 'json';
    }

    public function evaluate(array $config, ConditionContext $ctx): bool
    {
        $file = $config['file'] ?? null;
        if (!$file || !$this->isFileAllowed($file)) {
            return false;
        }

        $fullPath = DATA . $file;
        if (!file_exists($fullPath)) {
            return false;
        }

        // Utiliser le cache du contexte pour éviter de relire le fichier
        $data = $ctx->getData('json', $file, function() use ($fullPath) {
            return Util::readJsonFile($fullPath, true);
        });

        $key = $config['key'] ?? '';
        $value = $this->getValueByPath($data, $key);
        
        $required = $config['value'] ?? true;
        $operator = $config['operator'] ?? '=';

        switch ($operator) {
            case '!=': return $value != $required;
            case '>': return $value > $required;
            case '<': return $value < $required;
            case 'in': return is_array($required) && in_array($value, $required);
            case '=':
            default: return $value == $required;
        }
    }

    private function isFileAllowed(string $file): bool
    {
        // Interdire de remonter dans l'arborescence
        if (strpos($file, '..') !== false) return false;
        
        $whitelist = Core::getInstance()->getConfigVal('auth_json_whitelist') ?? ['config.json', 'settings.json'];
        return in_array(basename($file), $whitelist);
    }

    private function getValueByPath($data, string $path)
    {
        $keys = explode('.', $path);
        foreach ($keys as $key) {
            if (is_array($data) && isset($data[$key])) {
                $data = $data[$key];
            } else {
                return null;
            }
        }
        return $data;
    }

    public function getMetadata(): array
    {
        return [
            'label' => 'Fichier JSON',
            'description' => 'Vérifie une valeur dans un fichier de configuration JSON',
            'fields' => [
                'file' => ['type' => 'text', 'label' => 'Fichier (relative à DATA)'],
                'key' => ['type' => 'text', 'label' => 'Clé (ex: site.name)'],
                'value' => ['type' => 'text', 'label' => 'Valeur attendue']
            ]
        ];
    }

    public function getCost(): int
    {
        return 5; // Coût moyen (JSON parsing)
    }
}
