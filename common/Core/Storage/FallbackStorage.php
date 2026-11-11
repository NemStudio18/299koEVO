<?php

namespace Core\Storage;

use Core\Interfaces\StorageInterface;

/**
 * FallbackStorage permet de faire cohabiter deux moteurs de stockage.
 * Utile pour les migrations SQL : si non trouvé en SQL, on cherche en JSON.
 */
class FallbackStorage implements StorageInterface
{
    private StorageInterface $primary;
    private StorageInterface $fallback;

    public function __construct(StorageInterface $primary, StorageInterface $fallback)
    {
        $this->primary = $primary;
        $this->fallback = $fallback;
    }

    public function find(string $id): ?array
    {
        try {
            $record = $this->primary->find($id);
            if ($record !== null) {
                return $record;
            }
        } catch (\Exception $e) {
            EmergencyLogger::log("FALLBACK_ERROR: Primary 'find' failed ($id): " . $e->getMessage());
        }

        EmergencyLogger::log("FALLBACK: Record $id not found in primary or error, checking fallback...");
        return $this->fallback->find($id);
    }

    public function findAll(array $criteria = []): array
    {
        $results = [];
        $primaryFailed = false;
        try {
            $results = $this->primary->findAll($criteria);
        } catch (\Exception $e) {
            EmergencyLogger::log("FALLBACK_ERROR: Primary 'findAll' failed: " . $e->getMessage());
            $primaryFailed = true;
        }

        // On ne lit le JSON que si SQL a vraiment planté (exception).
        // Si SQL répond "vide" c'est une réponse valide — on n'injecte PAS les données JSON.
        if ($primaryFailed) {
            return $this->fallback->findAll($criteria);
        }

        // SQL OK et données présentes : on complète avec JSON uniquement pour les IDs manquants
        // (utile pendant une migration partielle SQL où certains anciens enregistrements sont en JSON).
        if (!empty($results)) {
            $fallbackResults = $this->fallback->findAll($criteria);
            $ids = [];
            foreach ($results as $r) {
                $ids[] = (string) ($r['id'] ?? uniqid());
            }
            foreach ($fallbackResults as $r) {
                $id = (string) ($r['id'] ?? '');
                if ($id !== '' && !in_array($id, $ids)) {
                    $results[] = $r;
                }
            }
        }

        return $results;
    }

    public function save(string $id, array $data): bool
    {
        try {
            // Par défaut, on enregistre dans le primaire (SQL)
            // Cela permet une migration progressive "on-write"
            return $this->primary->save($id, $data);
        } catch (\Exception $e) {
            EmergencyLogger::log("FALLBACK_ERROR: Primary 'save' failed ($id): " . $e->getMessage());
            // Si le primaire échoue (ex: DB offline), on enregistre dans le fallback (JSON)
            return $this->fallback->save($id, $data);
        }
    }

    public function delete(string $id): bool
    {
        // On supprime des deux pour être sûr
        $res1 = false;
        try {
            $res1 = $this->primary->delete($id);
        } catch (\Exception $e) {
             EmergencyLogger::log("FALLBACK_ERROR: Primary 'delete' failed ($id): " . $e->getMessage());
        }
        $res2 = $this->fallback->delete($id);
        return $res1 || $res2;
    }

    public function beginTransaction(): bool
    {
        $this->fallback->beginTransaction();
        return $this->primary->beginTransaction();
    }

    public function commit(): bool
    {
        $this->fallback->commit();
        return $this->primary->commit();
    }

    public function rollBack(): bool
    {
        $this->fallback->rollBack();
        return $this->primary->rollBack();
    }

    public function ensureTable(string $table, string $pk, array $schema, array $indexes = []): bool
    {
        $this->fallback->ensureTable($table, $pk, $schema, $indexes);
        return $this->primary->ensureTable($table, $pk, $schema, $indexes);
    }

    /**
     * Délègue le count au storage primaire (SQL) pour des performances optimales.
     */
    public function count(array $criteria = []): int
    {
        try {
            return $this->primary->count($criteria);
        } catch (\Exception $e) {
            EmergencyLogger::log("FALLBACK_ERROR: Primary 'count' failed: " . $e->getMessage());
            return $this->fallback->count($criteria);
        }
    }
}

