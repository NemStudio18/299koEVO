<?php
namespace Core\File;

/**
 * UploadHandler
 * Centralized service for secure file uploads with image re-encoding and EXIF validation.
 */
class UploadHandler
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_MIMES      = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE           = 5242880; // 5MB
    private const MAX_DIM            = 5000;    // Max 5000px width/height

    /**
     * Handles a secure image upload
     * 
     * @param array $file $_FILES item
     * @param string $destFolder Target folder
     * @param array $options ['validate_gps' => true, 'target_lat' => X, 'target_lon' => Y]
     * @return array ['success' => bool, 'filename' => string, 'error' => string, 'verified' => bool]
     */
    public static function handleImageUpload(array $file, string $destFolder, array $options = []): array
    {
        // 1. Pre-validation
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
        }

        if ($file['size'] > self::MAX_SIZE) {
            return ['success' => false, 'error' => 'File too large (max 5MB).'];
        }

        // 2. Extension check (basic)
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
            return ['success' => false, 'error' => 'Extension not allowed.'];
        }

        // 3. Binary Magic Bytes check
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIMES)) {
            return ['success' => false, 'error' => 'Invalid image content (MIME mismatch).'];
        }

        // 4. Dimensions check (Anti Zip Bomb)
        $info = @getimagesize($file['tmp_name']);
        if (!$info || $info[0] > self::MAX_DIM || $info[1] > self::MAX_DIM) {
            return ['success' => false, 'error' => 'Image dimensions too large or invalid.'];
        }

        // 5. GPS Validation (if requested)
        $isVerified = false;
        if (!empty($options['validate_gps']) && isset($options['target_lat'], $options['target_lon'])) {
            $gpsResult = self::validateExifGps($file['tmp_name'], $options['target_lat'], $options['target_lon']);
            if ($gpsResult['status'] === 'rejected') {
                return ['success' => false, 'error' => 'GPS location mismatch: ' . ($gpsResult['message'] ?? 'Unknown location')];
            }
            $isVerified = ($gpsResult['status'] === 'verified');
        }

        // 6. Re-encoding (Sanitization)
        $newFilename = bin2hex(random_bytes(16)) . '.jpg';
        if (!is_dir($destFolder)) {
            @mkdir($destFolder, 0755, true);
        }
        $destPath = rtrim($destFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFilename;

        $sanitized = self::sanitizeAndSave($file['tmp_name'], $destPath, $mime);
        if (!$sanitized) {
            return ['success' => false, 'error' => 'Failed to process/sanitize image.'];
        }

        return [
            'success' => true,
            'filename' => $newFilename,
            'verified' => $isVerified
        ];
    }

    /**
     * Re-encodes image to strip all metadata and malicious payloads.
     */
    private static function sanitizeAndSave(string $tmpPath, string $destPath, string $mime): bool
    {
        try {
            switch ($mime) {
                case 'image/jpeg':
                    $img = @imagecreatefromjpeg($tmpPath);
                    break;
                case 'image/png':
                    $img = @imagecreatefrompng($tmpPath);
                    break;
                case 'image/webp':
                    $img = @imagecreatefromwebp($tmpPath);
                    break;
                default:
                    return false;
            }

            if (!$img) return false;

            // Force save as JPEG for normalization and metadata stripping
            $success = imagejpeg($img, $destPath, 85);
            imagedestroy($img);
            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validates EXIF GPS location
     */
    private static function validateExifGps(string $path, $poiLat, $poiLon): array
    {
        if (!function_exists('exif_read_data')) {
            return ['status' => 'ok', 'message' => 'EXIF reader missing']; // Fallback to OK (no bonus)
        }

        $exif = @exif_read_data($path);
        if (!$exif || !isset($exif['GPSLatitude'], $exif['GPSLongitude'])) {
            return ['status' => 'ok', 'message' => 'No EXIF GPS found']; // OK (no bonus)
        }

        $lat = self::gpsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
        $lon = self::gpsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E');

        $dist = self::calculateDistance($lat, $lon, $poiLat, $poiLon);
        
        if ($dist > 100) {
            return ['status' => 'rejected', 'message' => "Too far ($dist m)"];
        }

        return ['status' => 'verified', 'dist' => $dist];
    }

    private static function gpsToDecimal($gps, $ref): float {
        $degrees = self::exifToNum($gps[0]);
        $minutes = self::exifToNum($gps[1]);
        $seconds = self::exifToNum($gps[2]);
        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
        return ($ref === 'S' || $ref === 'W') ? -$decimal : $decimal;
    }

    private static function exifToNum($value): float {
        $parts = explode('/', $value);
        if (count($parts) === 2 && $parts[1] != 0) return (float)$parts[0] / (float)$parts[1];
        return (float)$value;
    }

    private static function calculateDistance($lat1, $lon1, $lat2, $lon2): float {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
