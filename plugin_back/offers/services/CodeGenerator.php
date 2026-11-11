<?php

namespace Offers\Services;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour générer des codes uniques et QR codes
 */
class CodeGenerator
{
    /**
     * Génère un code unique pour une offre
     * Format: VB-{POI_SLUG}-{RANDOM}
     */
    public static function generateCode(int $offerId, string $poiName): string
    {
        // Créer un slug à partir du nom du POI
        $slug = self::slugify($poiName);
        $slug = strtoupper(substr($slug, 0, 10)); // Limiter à 10 caractères
        
        // Générer un nombre aléatoire
        $random = str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        
        return "VB-{$slug}-{$random}";
    }

    /**
     * Génère un code basé sur l'ID de l'offre (plus simple)
     */
    public static function generateSimpleCode(int $offerId): string
    {
        $random = str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        return "VB-OFF-{$offerId}-{$random}";
    }

    /**
     * Convertit une chaîne en slug
     */
    private static function slugify(string $text): string
    {
        // Remplacer les caractères accentués
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        
        // Convertir en minuscules
        $text = strtolower($text);
        
        // Remplacer les espaces et caractères spéciaux par des tirets
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        
        // Supprimer les tirets en début et fin
        $text = trim($text, '-');
        
        return $text;
    }

    /**
     * Génère l'URL pour le QR code (utilise une API externe)
     */
    public static function generateQrCodeUrl(string $code): string
    {
        // Utiliser une API gratuite pour générer le QR code
        $encodedCode = urlencode($code);
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$encodedCode}";
    }
}



