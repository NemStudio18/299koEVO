<?php

namespace PoiManager\Services;

defined('ROOT') or exit('Access denied!');

/**
 * PoiPhotoService
 *
 * Gère l'upload, la suppression et la mise à jour des photos de POIs.
 * Les photos sont stockées dans UPLOAD . 'poi/[poiId]/'.
 */
class PoiPhotoService
{
    private \PDO             $pdo;
    private OwnerAccessService $accessService;

    /**
     * Returns the secure proxy URL for an image.
     */
    public function getProxyUrl(int $poiId, string $filename): string
    {
        // Handle legacy URLs if any, but focus on the new proxy
        $filename = basename($filename);
        return '/common/image.php?poi=' . $poiId . '&file=' . $filename;
    }
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_SIZE_BYTES      = 5242880; // 5 Mo

    public function __construct()
    {
        $this->pdo           = \Core\Database::getInstance()->getPdo();
        $this->accessService = new OwnerAccessService();
    }

    // =========================================================
    // UPLOAD
    // =========================================================

    /**
     * Upload une photo pour un POI.
     */
    public function upload(int $poiId, array $file, int $userId, string $caption = '', string $status = 'approved'): array
    {
        $poi = (new PoiManagerService())->getById($poiId);
        $uploadDir = UPLOAD . 'poi' . DS . $poiId . DS;

        // Use new Secure UploadHandler
        $result = \Core\File\UploadHandler::handleImageUpload($file, $uploadDir, [
            'validate_gps' => true,
            'target_lat'   => $poi['lat'] ?? null,
            'target_lon'   => $poi['lon'] ?? null
        ]);

        if (!$result['success']) {
            return ['success' => false, 'message' => $result['error'], 'photo' => null];
        }

        $filename = $result['filename'];
        $is_verified = $result['verified'] ? 1 : 0;
        
        // Auto-approve if verified
        if ($is_verified) {
            $status = 'approved';
        }

        // URL is now proxied (legacy stored url, but we will serve it via /image.php?poi=...&file=...)
        $url = $filename; // We only store the filename or relative base

        // Insérer en base
        $stmt = $this->pdo->prepare(
            'INSERT INTO poi_photos (poi_id, url, caption, uploaded_by, status, is_verified) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$poiId, $url, $caption ?: null, $userId, $status, $is_verified]);
        $photoId = (int)$this->pdo->lastInsertId();

        if ($is_verified) {
            $this->awardPhotoPoints($userId);
        }

        $this->logActivity($poiId, $userId, 'photo_upload');

        return [
            'success' => true,
            'message' => 'Photo uploadée avec succès.',
            'photo'   => ['id' => $photoId, 'url' => $this->getProxyUrl($poiId, $url), 'caption' => $caption],
        ];
    }

    // =========================================================
    // SUPPRESSION
    // =========================================================

    /**
     * Supprime une photo (fichier + ligne BDD).
     */
    public function delete(int $photoId, int $poiId): bool
    {
        $stmt = $this->pdo->prepare('SELECT url FROM poi_photos WHERE id = ? AND poi_id = ?');
        $stmt->execute([$photoId, $poiId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return false;

        // Supprimer le fichier physique
        $filePath = UPLOAD . 'poi' . DS . $poiId . DS . $row['url'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Supprimer la ligne BDD
        $this->pdo->prepare('DELETE FROM poi_photos WHERE id = ?')->execute([$photoId]);
        return true;
    }

    // =========================================================
    // LECTURE
    // =========================================================

    public function getPhotos(int $poiId, bool $onlyApproved = true): array
    {
        $sql = 'SELECT * FROM poi_photos WHERE poi_id = ?';
        if ($onlyApproved) {
            $sql .= " AND status = 'approved'";
        }
        $sql .= ' ORDER BY created_at ASC';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$poiId]);
        $photos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($photos as &$photo) {
            $photo['url'] = $this->getProxyUrl($poiId, $photo['url']);
        }

        return $photos;
    }

    /**
     * Met à jour la légende d'une photo.
     */
    public function updateCaption(int $photoId, int $poiId, string $caption): bool
    {
        $stmt = $this->pdo->prepare('UPDATE poi_photos SET caption = ? WHERE id = ? AND poi_id = ?');
        return $stmt->execute([trim($caption) ?: null, $photoId, $poiId]);
    }

    /**
     * Définit une photo comme photo de couverture pour un POI.
     */
    public function setAsCover(int $photoId, int $poiId): bool
    {
        // 1. Reset toutes les photos du POI
        $stmt = $this->pdo->prepare('UPDATE poi_photos SET is_cover = 0 WHERE poi_id = ?');
        $stmt->execute([$poiId]);

        // 2. Définir la nouvelle photo de couverture
        $stmt = $this->pdo->prepare('UPDATE poi_photos SET is_cover = 1 WHERE id = ? AND poi_id = ?');
        return $stmt->execute([$photoId, $poiId]);
    }

    // =========================================================
    // VALIDATION
    // =========================================================

    private function validateFile(array $file): array
    {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Aucun fichier reçu ou erreur d\'upload.'];
        }

        $size = filesize($file['tmp_name']);
        if ($size > self::MAX_SIZE_BYTES) {
            return ['valid' => false, 'error' => 'Le fichier dépasse la taille maximum de 5 Mo.'];
        }

        $ext = strtolower(\Utils\Util::getFileExtension($file['name']));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return ['valid' => false, 'error' => 'Extension non autorisée. Fichiers acceptés : ' . implode(', ', self::ALLOWED_EXTENSIONS)];
        }

        // Vérification MIME
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            return ['valid' => false, 'error' => 'Type de fichier non autorisé.'];
        }

        return ['valid' => true, 'error' => ''];
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function logActivity(int $poiId, int $userId, string $type): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO poi_owner_activity_log (poi_id, owner_id, activity_type) VALUES (?, ?, ?)'
            );
            $stmt->execute([$poiId, $userId, $type]);
        } catch (\Exception $e) {
            // Non bloquant
        }
    }

    // =========================================================
    // EXIF VALIDATION
    // =========================================================

    private function validateByExif(string $filePath, $poiLat, $poiLon): array
    {
        $result = [
            'is_verified' => false,
            'meta' => []
        ];

        if (!function_exists('exif_read_data')) {
            $result['meta']['error'] = 'exif_read_data not available';
            return $result;
        }

        $exif = @exif_read_data($filePath);
        if (!$exif) {
            $result['meta']['error'] = 'No EXIF data found';
            return $result;
        }

        // 1. Date Check (Recent < 7 days)
        if (isset($exif['DateTimeOriginal'])) {
            $photoDate = new \DateTime($exif['DateTimeOriginal']);
            $now = new \DateTime();
            $diff = $now->getTimestamp() - $photoDate->getTimestamp();
            $result['meta']['date'] = $exif['DateTimeOriginal'];
            
            if ($diff > (7 * 24 * 3600)) {
                $result['meta']['error_date'] = 'Photo too old';
            }
        } else {
            $result['meta']['error_date'] = 'No date in EXIF';
        }

        // 2. GPS Check (< 50m)
        if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude']) && $poiLat && $poiLon) {
            $lat = $this->gpsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
            $lon = $this->gpsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E');
            
            $dist = $this->calculateDistance($lat, $lon, $poiLat, $poiLon);
            $result['meta']['gps'] = ['lat' => $lat, 'lon' => $lon, 'dist' => $dist];

            if ($dist > 50) {
                $result['meta']['error_gps'] = 'Too far from POI (' . round($dist) . 'm)';
            }
        } else {
            $result['meta']['error_gps'] = 'Missing GPS coordinates';
        }

        // Final Verification status
        if (!isset($result['meta']['error_date']) && !isset($result['meta']['error_gps'])) {
            $result['is_verified'] = true;
        }

        return $result;
    }

    private function gpsToDecimal($gps, $ref): float
    {
        $degrees = $this->exifToNumber($gps[0]);
        $minutes = $this->exifToNumber($gps[1]);
        $seconds = $this->exifToNumber($gps[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
        return ($ref === 'S' || $ref === 'W') ? -$decimal : $decimal;
    }

    private function exifToNumber($value): float
    {
        $parts = explode('/', $value);
        if (count($parts) === 2 && $parts[1] != 0) {
            return (float)$parts[0] / (float)$parts[1];
        }
        return (float)$value;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    private function awardPhotoPoints(int $userId): void
    {
        require_once PLUGINS . 'gamification/services/PointsService.php';
        $pointsService = new \Gamification\Services\PointsService();
        
        $action = $pointsService->hasBooster($userId) ? 'photo_verified_subscriber' : 'photo_verified';
        $points = $pointsService->getPointsForAction($action);
        
        if ($points > 0) {
            $pointsService->addPoints($userId, $points, 'photo_verified');
        }
    }

    private function normalizeMediaPath(?string $path): ?string
    {
        if (empty($path) || trim($path) === '') {
            return null;
        }
        $path = trim($path);
        // Si c'est une URL absolue, ne rien faire
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }
        // Si le chemin commence par upload/ mais pas par data/, ajouter data/
        if (strpos($path, 'upload/') === 0) {
            return 'data/' . $path;
        }
        return $path;
    }
}
