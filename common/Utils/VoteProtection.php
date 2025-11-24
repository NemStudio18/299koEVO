<?php

namespace Utils;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

/**
 * VoteProtection - Protection contre les votes multiples et gestion RGPD
 */
class VoteProtection
{
    /**
     * Génère un fingerprint unique basé sur l'IP et le User-Agent
     * Compatible RGPD (hash, pas d'IP en clair)
     * 
     * @return string Hash du fingerprint
     */
    public static function getFingerprint(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        
        // Créer un hash unique basé sur plusieurs facteurs
        $data = $ip . $userAgent . $acceptLanguage;
        
        return hash('sha256', $data);
    }

    /**
     * Vérifie si l'utilisateur a accepté les conditions RGPD
     * 
     * @param array $post Données POST du formulaire
     * @return bool true si RGPD accepté, false sinon
     */
    public static function hasAcceptedRGPD(array $post): bool
    {
        return isset($post['rgpd_accept']) && $post['rgpd_accept'] === '1';
    }

    /**
     * Valide un email
     * 
     * @param string $email Email à valider
     * @return bool true si email valide, false sinon
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

