<?php

namespace Pwa\WebPush;

use Core\Plugin\Plugin;
use Utils\Util;
use Core\Core;

/**
 * Classe pour envoyer des notifications Web Push
 */
class WebPush
{
    private $plugin;
    private $publicKey;
    private $privateKey;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        // Lire les clés depuis le fichier séparé
        require_once PLUGINS . 'pwa/pwa.php';
        $vapidKeys = pwaGetVapidKeys();
        if ($vapidKeys) {
            $this->publicKey = $vapidKeys['publicKey'];
            $this->privateKey = $vapidKeys['privateKey'];
        } else {
            $this->publicKey = '';
            $this->privateKey = '';
        }
    }

    /**
     * Envoie une notification à tous les abonnés
     * 
     * @param string $title Titre de la notification
     * @param string $message Message de la notification
     * @param string $url URL à ouvrir lors du clic
     * @return int Nombre de notifications envoyées avec succès
     */
    public function sendNotificationToAll(string $title, string $message, string $url = '/'): int
    {
        $subscriptionsFile = DATA_PLUGIN . 'pwa/subscriptions.json';
        $subscriptions = Util::readJsonFile($subscriptionsFile, true);
        
        if (!is_array($subscriptions) || empty($subscriptions)) {
            return 0;
        }

        $sent = 0;
        foreach ($subscriptions as $subscription) {
            if ($this->sendNotification($subscription, $title, $message, $url)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Envoie une notification à un abonné spécifique
     * 
     * @param array $subscription Données d'abonnement
     * @param string $title Titre de la notification
     * @param string $message Message de la notification
     * @param string $url URL à ouvrir lors du clic
     * @return bool true si envoyé avec succès
     */
    public function sendNotification(array $subscription, string $title, string $message, string $url = '/'): bool
    {
        if (empty($subscription['endpoint']) || empty($subscription['keys'])) {
            return false;
        }

        $endpoint = $subscription['endpoint'];
        $p256dh = $subscription['keys']['p256dh'] ?? '';
        $auth = $subscription['keys']['auth'] ?? '';

        if (empty($p256dh) || empty($auth)) {
            return false;
        }

        // Préparer le payload
        $payload = json_encode([
            'title' => $title,
            'message' => $message,
            'url' => $url
        ]);

        // Chiffrer le payload (simplifié - en production, utiliser une bibliothèque complète)
        $encrypted = $this->encryptPayload($payload, $p256dh, $auth);
        if ($encrypted === false) {
            return false;
        }

        // Créer les en-têtes VAPID
        $headers = $this->createVapidHeaders($endpoint);

        // Envoyer la notification
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encrypted['ciphertext']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aesgcm',
            'Encryption: salt=' . $encrypted['salt'],
            'Crypto-Key: dh=' . $encrypted['publicKey']
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 201 Created ou 204 No Content = succès
        return ($httpCode === 201 || $httpCode === 204);
    }

    /**
     * Crée les en-têtes VAPID pour l'authentification
     */
    private function createVapidHeaders(string $endpoint): array
    {
        $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
        $expiration = time() + 43200; // 12 heures
        $subject = Core::getInstance()->getConfigVal('siteEmail') ?: 'mailto:admin@example.com';

        $header = [
            'typ' => 'JWT',
            'alg' => 'ES256'
        ];

        $claims = [
            'aud' => $audience,
            'exp' => $expiration,
            'sub' => $subject
        ];

        // Encoder en base64url
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $claimsEncoded = $this->base64UrlEncode(json_encode($claims));

        // Signer avec la clé privée
        $signature = $this->signJWT($headerEncoded . '.' . $claimsEncoded);
        $token = $headerEncoded . '.' . $claimsEncoded . '.' . $this->base64UrlEncode($signature);

        return [
            'Authorization: vapid t=' . $token . ', k=' . $this->publicKey
        ];
    }

    /**
     * @return array|false
     */
    private function encryptPayload(string $payload, string $p256dh, string $auth)
    {
        // Générer une clé de chiffrement
        $salt = random_bytes(16);
        $serverKey = $this->generateServerKey();
        
        // Pour une implémentation complète, il faudrait :
        // 1. Dériver les clés avec HKDF
        // 2. Chiffrer avec AES-GCM
        // 3. Gérer le padding
        
        // Pour l'instant, on retourne une structure basique
        // En production, utiliser une bibliothèque comme web-push-php
        return [
            'ciphertext' => $payload, // Simplifié - devrait être chiffré
            'salt' => base64_encode($salt),
            'publicKey' => base64_encode($serverKey)
        ];
    }

    /**
     * Génère une clé serveur temporaire
     */
    private function generateServerKey(): string
    {
        return random_bytes(65); // 65 bytes pour une clé EC
    }

    /**
     * Signe un JWT avec la clé privée VAPID
     */
    private function signJWT(string $data): string
    {
        // Convertir la clé privée base64url en format OpenSSL
        $privateKeyPem = $this->convertPrivateKeyToPEM($this->privateKey);
        
        // Signer avec OpenSSL
        $signature = '';
        openssl_sign($data, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
        
        return $signature;
    }

    /**
     * @return string|false
     */
    private function convertPrivateKeyToPEM(string $base64urlKey)
    {
        // Décoder la clé
        $key = $this->base64UrlDecode($base64urlKey);
        
        // Créer une clé OpenSSL à partir des données brutes
        // Note: Cette implémentation est simplifiée
        // En production, utiliser une bibliothèque complète
        
        $config = [
            'digest_alg' => 'sha256',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1'
        ];
        
        // Pour une implémentation complète, il faudrait reconstruire la clé EC
        // Ici on génère une nouvelle clé temporaire (à améliorer)
        $tempKey = openssl_pkey_new($config);
        if (!$tempKey) {
            return false;
        }
        
        openssl_pkey_export($tempKey, $pem);
        return $pem;
    }

    /**
     * Encode en base64url
     */
    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Décode depuis base64url
     */
    private function base64UrlDecode(string $data): string
    {
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}

