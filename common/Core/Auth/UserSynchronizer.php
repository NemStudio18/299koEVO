<?php

namespace Core\Auth;

use Core\Core;
use Core\Database;
use Core\Logger;
use PDO;

/**
 * Service de synchronisation des utilisateurs vers la base MySql.
 * Assure la cohérence des IDs entre le stockage JSON et MySQL.
 */
class UserSynchronizer
{
    /**
     * Synchronise un utilisateur vers MySQL.
     * Utilise REPLACE pour écraser les données existantes avec le même ID.
     */
    public static function sync(User $user): bool
    {
        try {
            $db = Database::getInstance();
            $pdo = $db->getPdo();
            
            $id = $user->getId();
            if ($id === null) {
                return false;
            }

            $attributes = $user->attributes;
            $email = $attributes['email'] ?? '';
            $username = $attributes['username'] ?? '';
            $role = $attributes['role'] ?? ($attributes['group_id'] ?? 'member');
            $passwordHash = $attributes['pwd'] ?? '';
            $token = $attributes['token'] ?? '';
            $tokens = isset($attributes['tokens']) ? (is_array($attributes['tokens']) ? json_encode($attributes['tokens']) : $attributes['tokens']) : '[]';
            $permissions = isset($attributes['permissions']) ? (is_array($attributes['permissions']) ? json_encode($attributes['permissions']) : $attributes['permissions']) : '[]';
            $subscription = $attributes['subscription'] ?? null;
            $subscriptionExpiry = $attributes['subscription_expiry'] ?? null;
            $createdAt = $attributes['created_at'] ?? date('Y-m-d H:i:s');

            // On utilise REPLACE pour forcer l'ID et mettre à jour le reste
            $sql = "REPLACE INTO users (id, email, username, role, password_hash, token, tokens, permissions, subscription, subscription_expiry, created_at) 
                    VALUES (:id, :email, :username, :role, :password_hash, :token, :tokens, :permissions, :subscription, :subscription_expiry, :created_at)";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':email' => $email,
                ':username' => $username,
                ':role' => $role,
                ':password_hash' => $passwordHash,
                ':token' => $token,
                ':tokens' => $tokens,
                ':permissions' => $permissions,
                ':subscription' => $subscription,
                ':subscription_expiry' => $subscriptionExpiry,
                ':created_at' => $createdAt
            ]);
        } catch (\Exception $e) {
            Core::getInstance()->getLogger()->log(Logger::LEVEL_WARNING, "UserSynchronizer: Could not sync user {$user->getId()} to SQL: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un utilisateur de MySQL.
     */
    public static function delete(int|string $id): bool
    {
        try {
            $db = Database::getInstance();
            $pdo = $db->getPdo();
            
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (\Exception $e) {
            Core::getInstance()->getLogger()->log(Logger::LEVEL_WARNING, "UserSynchronizer: Could not delete user $id from SQL: " . $e->getMessage());
            return false;
        }
    }
}
