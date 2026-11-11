<?php

namespace QrPoi\Services;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour générer des QR codes
 */
class QrCodeService
{
    /**
     * Génère l'URL du QR code via une API externe avec support des couleurs
     */
    public static function generateQrCodeUrl(string $data, int $size = 300, string $color = '000000', string $bgcolor = 'FFFFFF'): string
    {
        $encodedData = urlencode($data);
        $color = str_replace('#', '', $color);
        $bgcolor = str_replace('#', '', $bgcolor);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedData}&color={$color}&bgcolor={$bgcolor}";
    }

    /**
     * Génère un QR code avancé avec logo et texte via GD
     */
    public static function generateAdvancedQrCode(string $data, array $options = []): ?string
    {
        $size = (int)($options['size'] ?? 300);
        $color = $options['color'] ?? '000000';
        $bgcolor = $options['bgcolor'] ?? 'FFFFFF';
        $logoPath = $options['logo_path'] ?? null;
        $customText = $options['custom_text'] ?? null;
        $textColor = $options['text_color'] ?? '000000';
        $qzone = (int)($options['qzone'] ?? 1);

        // 1. Obtenir le QR code de base
        $apiUrl = self::generateQrCodeUrl($data, $size, $color, $bgcolor) . "&qzone=" . $qzone;
        $qrBinary = self::downloadQrCode($apiUrl);
        if (!$qrBinary) {
            \Core\Core::getInstance()->getLogger()->error("QrCodeService::generateAdvancedQrCode - Aucun binaire reçu de downloadQrCode pour l'URL: " . $apiUrl);
            return null;
        }

        if (!$logoPath && !$customText) {
            return $qrBinary;
        }

        // 2. Traitement GD
        $qrImg = @imagecreatefromstring($qrBinary);
        if (!$qrImg) {
            \Core\Core::getInstance()->getLogger()->error("QrCodeService::generateAdvancedQrCode - Échec de imagecreatefromstring. Format invalide ou non supporté.");
            return $qrBinary;
        }

        $finalWidth = $size;
        $finalHeight = $size;
        
        // Si on a du texte, on augmente la hauteur
        $textHeight = 0;
        if ($customText) {
            $textHeight = (int)($size * 0.15); // 15% de la taille pour le texte
            $finalHeight += $textHeight;
        }

        $baseImg = imagecreatetruecolor($finalWidth, $finalHeight);
        
        // Fond
        list($rb, $gb, $bb) = sscanf($bgcolor, "%02x%02x%02x");
        $bgAlloc = imagecolorallocate($baseImg, $rb, $gb, $bb);
        imagefill($baseImg, 0, 0, $bgAlloc);

        // Copier le QR
        imagecopy($baseImg, $qrImg, 0, 0, 0, 0, $size, $size);
        imagedestroy($qrImg);

        // 3. Ajouter le Logo
        if ($logoPath && file_exists($logoPath)) {
            $logoImg = null;
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            if ($ext === 'png') $logoImg = @imagecreatefrompng($logoPath);
            elseif ($ext === 'jpg' || $ext === 'jpeg') $logoImg = @imagecreatefromjpeg($logoPath);
            elseif ($ext === 'gif') $logoImg = @imagecreatefromgif($logoPath);

            if ($logoImg) {
                $logoWidth = imagesx($logoImg);
                $logoHeight = imagesy($logoImg);
                
                // On veut que le logo fasse environ 20% de la taille du QR
                $targetLogoSize = (int)($size * 0.2);
                $ratio = min($targetLogoSize / $logoWidth, $targetLogoSize / $logoHeight);
                $newW = (int)($logoWidth * $ratio);
                $newH = (int)($logoHeight * $ratio);
                
                $posX = (int)(($size - $newW) / 2);
                $posY = (int)(($size - $newH) / 2);
                
                imagecopyresampled($baseImg, $logoImg, $posX, $posY, 0, 0, $newW, $newH, $logoWidth, $logoHeight);
                imagedestroy($logoImg);
            }
        }

        // 4. Ajouter le texte
        if ($customText) {
            list($rt, $gt, $bt) = sscanf($textColor, "%02x%02x%02x");
            $textAlloc = imagecolorallocate($baseImg, $rt, $gt, $bt);
            
            // On utilise une police builtin pour simplicité si pas de TTF
            $fontSize = 3; // Builtin font 3
            if ($size > 400) $fontSize = 5;
            
            $textW = imagefontwidth($fontSize) * strlen($customText);
            $textH = imagefontheight($fontSize);
            
            $textX = (int)(($size - $textW) / 2);
            $textY = $size + (int)(($textHeight - $textH) / 2);
            
            imagestring($baseImg, $fontSize, $textX, $textY, $customText, $textAlloc);
        }

        // 5. Sortie
        ob_start();
        imagepng($baseImg);
        $finalData = ob_get_clean();
        imagedestroy($baseImg);

        return $finalData;
    }

    /**
     * Génère l'URL complète du QR code pour un POI (basique)
     */
    public static function generatePoiQrCodeUrl(int $poiId, int $size = 300): string
    {
        $redirectUrl = self::generatePoiRedirectUrl($poiId);
        return self::generateQrCodeUrl($redirectUrl, $size);
    }

    /**
     * Génère l'URL de redirection pour un POI
     */
    public static function generatePoiRedirectUrl(int $poiId): string
    {
        $core = \Core\Core::getInstance();
        $siteUrl = rtrim($core->getConfigVal('siteUrl'), '/');
        return $siteUrl . '/qr/poi/' . $poiId;
    }

    /**
     * Télécharge le QR code et le retourne en tant que données binaires
     */
    public static function downloadQrCode(string $url): ?string
    {
        $logger = \Core\Core::getInstance()->getLogger();
        
        // Si l'URL est relative au site, on tente de la rendre absolue ou de la traiter localement
        // Mais ici c'est api.qrserver.com, donc c'est externe.
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200 && $data !== false) {
            return $data;
        }
        
        $logger->error("QrCodeService::downloadQrCode - Échec. HTTP Code: {$httpCode}, Erreur cURL: {$error}, URL: {$url}");
        return null;
    }
}



