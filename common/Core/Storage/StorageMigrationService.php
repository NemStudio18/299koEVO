<?php

namespace Core\Storage;

use Core\Database;
use Core\Interfaces\StorageInterface;

/**
 * Service pour migrer les données d'un driver à l'autre dynamiquement.
 * Utile pour passer de JSON à SQLite/MySQL et vice-versa.
 */
class StorageMigrationService
{
    /**
     * Migre toutes les données d'un modèle d'un driver vers un autre.
     * 
     * @param string $modelClass Nom de la classe du modèle (ex: "PageItem")
     * @param string $toDriver Driver cible ("json", "sql")
     * @param string|null $fromDriver Driver source (optionnel)
     * @param string|null $toConnection Nom de la connexion cible (ex: "sqlite")
     * @param string|null $fromConnection Nom de la connexion source
     * @return array Résultat de la migration ['success' => bool, 'count' => int, 'errors' => array]
     */
    public static function migrate(
        string $modelClass, 
        string $toDriver, 
        ?string $fromDriver = null, 
        ?string $toConnection = null, 
        ?string $fromConnection = null
    ): array {
        $db = Database::getInstance();
        $fullClass = $modelClass;

        if (!class_exists($fullClass)) {
            // Try Page fallback if not a FQCN
            $fullClass = "Core\\Page\\$modelClass";
            if (!class_exists($fullClass)) {
                // Try Auth fallback
                $fullClass = "Core\\Auth\\$modelClass";
                if (!class_exists($fullClass)) {
                    return ['success' => false, 'errors' => ["Classe $modelClass (ou $fullClass) introuvable."]];
                }
            }
        }

        // 1. Sauvegarder la config actuelle
        $originalDriver = $db->getConfig($modelClass);
        
        // 2. Extraire les données du driver source
        if ($fromDriver) {
            $sourceVal = ($fromDriver === 'sql' && $fromConnection) ? $fromConnection : $fromDriver;
            $db->setStorageConfig($modelClass, $sourceVal);
        }
        
        $items = $fullClass::queryBuilder()->get();
        $count = count($items);
        
        // 3. Basculer sur le driver cible
        $targetVal = ($toDriver === 'sql' && $toConnection) ? $toConnection : $toDriver;
        $db->setStorageConfig($modelClass, $targetVal);

        // 3b. Provisionnement automatique de la table si nécessaire
        if ($toDriver === 'sql') {
            try {
                $connId = $toConnection ?: 'default';
                // On tente de récupérer le table name et le schema depuis storage.json
                $plugins = \Core\Plugin\PluginsManager::getInstance()->getPlugins();
                foreach ($plugins as $plugin) {
                    $storageFile = PLUGINS . $plugin->getName() . DS . 'param' . DS . 'storage.json';
                    if (file_exists($storageFile)) {
                        $manifest = json_decode(file_get_contents($storageFile), true);
                        if (isset($manifest[$modelClass]['table'], $manifest[$modelClass]['schema'])) {
                            $tableName = $manifest[$modelClass]['table'];
                            $schemaSql = $manifest[$modelClass]['schema'];
                            
                            // Vérifier si la table existe via le dialecte SQL approprié
                            $dialect = \Core\Storage\DialectResolver::resolve($db->getDriverName());
                            $exists = $db->query($dialect->tableExists($tableName), [], $connId);
                            if (empty($exists)) {
                                $db->execute($schemaSql, [], $connId);
                            }
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // On log mais on tente quand même la suite au cas où la table existerait déjà sans SHOW TABLES compatible
                \Core\Core::getInstance()->getLogger()->log('WARNING', "Schema provisioning failed for $modelClass: " . $e->getMessage());
            }
        }
        
        $successCount = 0;
        $errors = [];
        
        foreach ($items as $item) {
            try {
                // On s'assure que l'ID est préservé lors de la migration
                if ($item->save()) {
                    $successCount++;
                } else {
                    $errors[] = "Échec de sauvegarde pour l'ID: " . $item->getId();
                }
            } catch (\Exception $e) {
                $errors[] = "Erreur ID " . $item->getId() . " : " . $e->getMessage();
            }
        }

        // 4. Restaurer la config originale
        if ($originalDriver !== null) {
            $db->setStorageConfig($modelClass, $originalDriver);
        }

        // [HOOK] Trigger event after migration
        try {
            \Core\Core::getInstance()->callHook('storage:migration:finished', [
                'model' => $modelClass,
                'to_driver' => $toDriver,
                'success_count' => $successCount,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            // Ignore hook errors
        }

        return [
            'success' => count($errors) === 0,
            'count' => $successCount,
            'errors' => $errors
        ];
    }
}
