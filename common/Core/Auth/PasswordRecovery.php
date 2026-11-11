<?php

namespace Core\Auth;

use Utils\Util;

/**
 * Gestion des tokens de réinitialisation de mot de passe pour le core.
 */
defined('ROOT') or exit('Access denied!');

class PasswordRecovery
{
    protected string $file;
    protected array $data;
    protected string $legacyFile;

    public const EXPIRATION_TIME = 60 * 60 * 3;

    public function __construct(string $file = DATA_CORE_AUTH . 'pwd.json', ?string $legacyFile = null)
    {
        $this->file = $file;
        $this->legacyFile = $legacyFile ?? (DATA_PLUGIN . 'users/pwd.json');

        $this->bootstrapStorage();

        $this->data = Util::readJsonFile($this->file);
        $this->sanitizeExpiredTokens();
    }

    public function insertToken(string $mail, string $token, string $pwd): void
    {
        $this->data[] = [
            'mail' => $mail,
            'token' => $token,
            'pwd' => $pwd,
            'expiration' => time() + self::EXPIRATION_TIME
        ];
        $this->saveTokens();
    }

    public function deleteToken(string $token): void
    {
        foreach ($this->data as $k => &$dToken) {
            if ($dToken['token'] == $token) {
                unset($this->data[$k]);
            }
        }
        $this->saveTokens();
    }

    public function getTokenFromToken(string $token)
    {
        foreach ($this->data as $tk) {
            if ($tk['token'] === $token) {
                return $tk;
            }
        }
        return false;
    }

    public function generatePassword(): string
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, 8);
    }

    protected function sanitizeExpiredTokens(): void
    {
        $dirty = false;
        foreach ($this->data as $k => &$token) {
            if ($token['expiration'] < time()) {
                unset($this->data[$k]);
                $dirty = true;
            }
        }
        if ($dirty) {
            $this->saveTokens();
        }
    }

    protected function saveTokens(): void
    {
        Util::writeJsonFile($this->file, array_values($this->data));
    }

    protected function bootstrapStorage(): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (!file_exists($this->file)) {
            if (file_exists($this->legacyFile)) {
                $payload = Util::readJsonFile($this->legacyFile, true);
                Util::writeJsonFile($this->file, $payload);
            } else {
                Util::writeJsonFile($this->file, []);
            }
        }
    }
}


