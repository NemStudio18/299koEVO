<?php

namespace Core\Auth;

/**
 * Helper pour générer les avatars utilisateur (CSS ou image)
 */
class UserAvatarHelper
{
    /**
     * Génère une couleur de fond pour un avatar basée sur le nom d'utilisateur
     * @param string $username
     * @return string Couleur hexadécimale
     */
    public static function generateAvatarColor(string $username): string
    {
        // Palette de couleurs GitHub-like
        $colors = [
            '#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#e74c3c',
            '#f39c12', '#16a085', '#27ae60', '#2980b9', '#8e44ad',
            '#c0392b', '#d35400', '#34495e', '#7f8c8d', '#95a5a6'
        ];
        
        // Générer un index basé sur le hash du nom d'utilisateur
        $hash = crc32($username);
        $index = abs($hash) % count($colors);
        
        return $colors[$index];
    }
    
    /**
     * Génère les initiales d'un utilisateur (1-2 lettres)
     * @param string $username
     * @return string Initiales en majuscules
     */
    public static function generateAvatarInitials(string $username): string
    {
        $username = trim($username);
        if (empty($username)) {
            return '?';
        }
        
        // Extraire les mots (séparés par espaces, tirets, underscores)
        $words = preg_split('/[\s\-_]+/', $username);
        
        if (count($words) >= 2) {
            // Deux initiales si plusieurs mots
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else {
            // Une ou deux lettres du premier mot
            $firstWord = $words[0];
            if (mb_strlen($firstWord) >= 2) {
                return strtoupper(mb_substr($firstWord, 0, 2));
            } else {
                return strtoupper($firstWord);
            }
        }
    }
    
    /**
     * Prépare les données d'avatar pour un utilisateur
     * @param User $user
     * @return array ['has_photo' => bool, 'photo_url' => string|null, 'color' => string, 'initials' => string]
     */
    public static function prepareAvatarData(User $user): array
    {
        $username = $user->username() ?? '?';
        $profilePhoto = $user->attributes['profile_photo'] ?? null;
        
        $data = [
            'has_photo' => false,
            'photo_url' => null,
            'color' => self::generateAvatarColor($username),
            'initials' => self::generateAvatarInitials($username)
        ];
        
        // Vérifier si l'utilisateur a une photo de profil
        if (!empty($profilePhoto) && $profilePhoto !== '/avatar') {
            $photoPath = UPLOAD . 'users' . DS . 'profiles' . DS . $profilePhoto;
            if (file_exists($photoPath)) {
                $photoUrl = str_replace(ROOT, '', $photoPath);
                $photoUrl = str_replace(DS, '/', $photoUrl);
                $photoUrl = ltrim($photoUrl, '/');
                $data['has_photo'] = true;
                $data['photo_url'] = $photoUrl;
            }
        }
        
        return $data;
    }
}


