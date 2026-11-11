<?php

namespace Utils;

defined('ROOT') or exit('Access denied!');

/**
 * Service d'optimisation et de conversion automatique des images en WebP.
 * Gère les uploads locaux et le rapatriement sécurisé des images distantes par URL.
 */
class ImageOptimizer
{
    /** @var int Largeur maximale par défaut (adapté Retina / Full HD) */
    public const DEFAULT_MAX_WIDTH = 1600;

    /** @var int Hauteur maximale par défaut */
    public const DEFAULT_MAX_HEIGHT = 1600;

    /** @var int Qualité de compression WebP par défaut (ratio visuel/taille optimal) */
    public const DEFAULT_QUALITY = 82;

    /** @var array<string> Types MIME pris en charge pour l'optimisation */
    public const SUPPORTED_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif'
    ];

    /**
     * Vérifie si le fichier est une image optimisable.
     */
    public static function isOptimizableImage(string $filePath): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);

        return in_array($mime, self::SUPPORTED_MIMES, true);
    }

    /**
     * Optimise une image source et l'enregistre au format WebP.
     * Corrige l'orientation EXIF, redimensionne proportionnellement et compresse.
     *
     * @param string $sourcePath Chemin vers l'image source
     * @param string $destPath Chemin complet du fichier WebP cible
     * @param int $maxWidth Largeur maximale autorisée
     * @param int $maxHeight Hauteur maximale autorisée
     * @param int $quality Qualité WebP (0-100)
     * @return bool True si la conversion et compression ont réussi
     */
    public static function optimizeImage(
        string $sourcePath,
        string $destPath,
        int $maxWidth = self::DEFAULT_MAX_WIDTH,
        int $maxHeight = self::DEFAULT_MAX_HEIGHT,
        int $quality = self::DEFAULT_QUALITY
    ): bool {
        if (!function_exists('imagewebp')) {
            return false;
        }

        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            return false;
        }

        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $mime = $imageInfo['mime'];
        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // 1. Création de la ressource image selon le type
        $srcImage = null;
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $srcImage = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $srcImage = @imagecreatefromwebp($sourcePath);
                break;
            case 'image/gif':
                $srcImage = @imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        if (!$srcImage) {
            return false;
        }

        // 2. Correction automatique de l'orientation EXIF (photos smartphone)
        if (($mime === 'image/jpeg' || $mime === 'image/jpg') && function_exists('exif_read_data')) {
            $exif = @exif_read_data($sourcePath);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $srcImage = imagerotate($srcImage, 180, 0);
                        break;
                    case 6:
                        $srcImage = imagerotate($srcImage, -90, 0);
                        // Inversion des dimensions après rotation 90°
                        $tmp = $width;
                        $width = $height;
                        $height = $tmp;
                        break;
                    case 8:
                        $srcImage = imagerotate($srcImage, 90, 0);
                        $tmp = $width;
                        $width = $height;
                        $height = $tmp;
                        break;
                }
            }
        }

        // 3. Calcul du redimensionnement proportionnel
        $newWidth = $width;
        $newHeight = $height;

        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);
        }

        // 4. Création de l'image de destination avec gestion de transparence
        $destImage = imagecreatetruecolor($newWidth, $newHeight);
        if (!$destImage) {
            imagedestroy($srcImage);
            return false;
        }

        // Préservation de la transparence pour PNG / WebP / GIF
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
        imagefilledrectangle($destImage, 0, 0, $newWidth, $newHeight, $transparent);

        // Rééchantillonnage de haute qualité
        imagecopyresampled(
            $destImage,
            $srcImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        // 5. Enregistrement en WebP
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $success = imagewebp($destImage, $destPath, $quality);

        // Nettoyage mémoire GD (imagedestroy est sans effet depuis PHP 8.0 et déprécié en PHP 8.5)
        if (PHP_VERSION_ID < 80500) {
            @imagedestroy($srcImage);
            @imagedestroy($destImage);
        }

        return $success;
    }

    /**
     * Traite un fichier temporaire uploadé et l'enregistre en WebP dans le dossier cible.
     *
     * @param string $tmpFilePath Chemin du fichier temporaire ($_FILES['...']['tmp_name'])
     * @param string $destDirectory Répertoire cible (avec slash final)
     * @param string $baseName Nom de base souhaité (sans extension)
     * @return string|false Nom du fichier généré (ex: 'photo-17300000.webp') ou false si échec
     */
    public static function optimizeUpload(
        string $tmpFilePath,
        string $destDirectory,
        string $baseName,
        int $maxWidth = self::DEFAULT_MAX_WIDTH,
        int $quality = self::DEFAULT_QUALITY
    ): string|bool {
        if (!self::isOptimizableImage($tmpFilePath)) {
            return false;
        }

        $slugName = Util::strToUrl($baseName);
        if (empty($slugName)) {
            $slugName = 'image';
        }

        $targetName = $slugName . '-' . time() . '.webp';
        $destPath = rtrim($destDirectory, '/') . '/' . $targetName;

        if (self::optimizeImage($tmpFilePath, $destPath, $maxWidth, self::DEFAULT_MAX_HEIGHT, $quality)) {
            return $targetName;
        }

        return false;
    }

    /**
     * Convertit un fichier local en WebP optimisé dans le dossier spécifié.
     *
     * @param string $sourceFilePath Chemin source
     * @param string $destDirectory Répertoire cible
     * @param string $baseName Nom de base
     * @return string|null Chemin relatif généré (ex: 'data/upload/blog/image.webp') ou null
     */
    public static function convertLocalFile(
        string $sourceFilePath,
        string $destDirectory,
        string $baseName,
        int $maxWidth = self::DEFAULT_MAX_WIDTH,
        int $quality = self::DEFAULT_QUALITY
    ): ?string {
        $filename = self::optimizeUpload($sourceFilePath, $destDirectory, $baseName, $maxWidth, $quality);
        if (!$filename) {
            return null;
        }
        $destPath = rtrim($destDirectory, '/') . '/' . $filename;
        if (defined('ROOT') && str_starts_with($destPath, ROOT)) {
            return str_replace('\\', '/', substr($destPath, strlen(ROOT)));
        }
        return str_replace('\\', '/', $destPath);
    }

    /**
     * Rapatrie une image depuis une URL distante, la valide contre les attaques SSRF,
     * la convertit en WebP optimisé et la stocke localement.
     *
     * @param string $url URL distante (http/https)
     * @param string $destDirectory Répertoire cible local (ex: UPLOAD . 'blog/')
     * @param string $prefix Préfixe pour le nom de fichier local
     * @return string|null Chemin relatif du fichier WebP créé (ex: 'data/upload/blog/image.webp') ou null
     */
    public static function downloadAndOptimizeUrl(
        string $url,
        string $destDirectory,
        string $prefix = 'blog',
        int $maxWidth = self::DEFAULT_MAX_WIDTH,
        int $quality = self::DEFAULT_QUALITY
    ): ?string {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // 1. Protection contre les attaques SSRF (interdiction localhost et réseaux privés)
        $parsed = parse_url($url);
        if (empty($parsed['host']) || !in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = $parsed['host'];
        $ip = gethostbyname($host);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            // Rejet immédiat si IP privée (127.0.0.1, 10.x, 192.168.x, etc.)
            return null;
        }

        // 2. Téléchargement sécurisé via cURL avec timeout et limite de taille (max 20 Mo)
        $tmpFile = tempnam(sys_get_temp_dir(), 'img_opt_');
        $fp = fopen($tmpFile, 'wb');
        if (!$fp) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; VivreBourgesBot/1.0)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_BUFFERSIZE => 128 * 1024,
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => function ($resource, $downloadSize, $downloaded) {
                // Interrompre si dépasse 20 Mo pour éviter les saturations
                if ($downloaded > 20 * 1024 * 1024) {
                    return 1;
                }
                return 0;
            }
        ]);

        $execSuccess = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if (!$execSuccess || $httpCode < 200 || $httpCode >= 300) {
            @unlink($tmpFile);
            return null;
        }

        // 3. Vérification du type d'image réel
        if (!self::isOptimizableImage($tmpFile)) {
            @unlink($tmpFile);
            return null;
        }

        // 4. Nom de fichier optimisé
        $originalName = pathinfo($parsed['path'] ?? '', PATHINFO_FILENAME);
        $slugName = Util::strToUrl($originalName);
        if (empty($slugName)) {
            $slugName = $prefix;
        }

        $destDirectory = rtrim($destDirectory, '/') . '/';
        if (!is_dir($destDirectory)) {
            @mkdir($destDirectory, 0755, true);
        }

        $targetName = $slugName . '-' . substr(md5($url), 0, 8) . '-' . time() . '.webp';
        $destPath = $destDirectory . $targetName;

        $optimized = self::optimizeImage($tmpFile, $destPath, $maxWidth, self::DEFAULT_MAX_HEIGHT, $quality);
        @unlink($tmpFile);

        if (!$optimized) {
            return null;
        }

        // Retourner le chemin relatif propre (ex: 'data/upload/blog/image.webp')
        if (defined('ROOT') && str_starts_with($destPath, ROOT)) {
            return str_replace('\\', '/', substr($destPath, strlen(ROOT)));
        }

        return str_replace('\\', '/', $destPath);
    }
}
