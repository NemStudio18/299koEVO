<?php

namespace Core\Auth;

use Core\Storage\JsonActiveRecord;
use Core\Auth\UsersManager;
use Core\Router\Router;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
defined('ROOT') or exit('Access denied!');

class User extends JsonActiveRecord
{
    protected static string $filePath = DATA_CORE_AUTH . 'users.json';

    protected static string $primaryKey = 'id';

    public function save(): bool
    {
        $this->attributes['token'] = $this->attributes['token'] ?? UsersManager::generateToken();
        return parent::save();
    }

    /**
     * Vérifie qu'un token transmis dans la requête correspond à l'utilisateur.
     */
    public function isAuthorized(): bool
    {
        $matches = Router::getInstance()->match();
        if (isset($matches['params']['token'])) {
            return $matches['params']['token'] === $this->attributes['token'];
        }

        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if ($contentType === "application/json") {
            $content = trim(file_get_contents("php://input"));
            $data = json_decode($content, true);
            if (isset($data['token'])) {
                return $data['token'] === $this->attributes['token'];
            }
        }

        if (!isset($_REQUEST['token'])) {
            return false;
        }

        return $_REQUEST['token'] == $this->attributes['token'];
    }
}


