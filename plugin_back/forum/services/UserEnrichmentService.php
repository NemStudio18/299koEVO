<?php

namespace Forum\Services;

use PDO;
use Core\Logger;
use Core\Auth\User;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour enrichir les données utilisateur depuis MySQL vivrebourges2
 */
class UserEnrichmentService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Récupère la connexion MySQL
     */
    private function getMysqlConnection(): ?PDO
    {
        try {
            return \Core\Database::getInstance()->getPdo();
        } catch (\PDOException $e) {
            $this->logger->log(\Core\Logger::LEVEL_WARNING, "UserEnrichmentService: Connexion MySQL échouée (Timeout ou autre) : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Valide si une URL d'avatar est valide (pas une valeur par défaut invalide)
     * 
     * @param string|null $avatarUrl
     * @return bool
     */
    private function isValidAvatarUrl(?string $avatarUrl): bool
    {
        if (empty($avatarUrl)) {
            return false;
        }

        // Normaliser l'URL (trim, lowercase pour comparaison)
        $normalized = trim($avatarUrl);
        $normalizedLower = strtolower($normalized);

        // Filtrer les valeurs invalides communes (exactes et variations)
        $invalidValues = ['/avatar', 'avatar', '/', ''];
        $invalidPatterns = ['/^\/avatar/', '/^avatar$/i', '/^\/$/'];

        if (in_array($normalized, $invalidValues, true) || in_array($normalizedLower, $invalidValues, true)) {
            return false;
        }

        // Vérifier les patterns invalides
        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return false;
            }
        }

        // Vérifier si c'est une URL valide (commence par http:// ou https://)
        if (preg_match('/^https?:\/\//', $normalized)) {
            return true;
        }

        // Vérifier si c'est un chemin relatif valide (ne commence pas par / seul)
        if ($normalized[0] === '/' && strlen($normalized) > 1) {
            // Vérifier si le fichier existe
            $filePath = ROOT . ltrim($normalized, '/');
            if (file_exists($filePath) && is_file($filePath)) {
                return true;
            }
        }

        // Vérifier si c'est un chemin relatif sans slash initial
        $filePath = ROOT . $normalized;
        if (file_exists($filePath) && is_file($filePath)) {
            return true;
        }

        return false;
    }

    /**
     * Enrichit un utilisateur avec les données depuis MySQL
     * 
     * @param User|null $user L'utilisateur du CMS
     * @param int|null $userId L'ID utilisateur (si user est null)
     * @param string|null $email L'email utilisateur (si user est null)
     * @return array Données enrichies
     */
    public function enrichUser(?User $user = null, ?int $userId = null, ?string $email = null): array
    {
        $enriched = [
            'id' => null,
            'username' => '',
            'email' => '',
            'avatar' => null,
            'profile_url' => null,
            'is_admin' => false,
            'has_mysql_data' => false,
        ];

        // Récupérer les données de base depuis le User du CMS
        if ($user) {
            $enriched['id'] = (int) ($user->attributes['id'] ?? 0);
            $enriched['username'] = $user->username() ?? '';
            $enriched['email'] = $user->email ?? '';
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            $ctx = new \Core\Navigation\ConditionContext($user);
            $enriched['is_admin'] = $evaluator->isAdmin($ctx);
            $enriched['profile_url'] = '/users/profile';

            // Récupérer la photo de profil depuis les attributs du CMS
            // IMPORTANT: Si l'utilisateur n'a pas de profile_photo dans le CMS, on laisse avatar à null
            // pour utiliser l'avatar CSS généré, même si un avatar existe dans MySQL
            $hasCmsProfilePhoto = false;
            if (isset($user->attributes['profile_photo']) && !empty($user->attributes['profile_photo'])) {
                $photoPath = UPLOAD . 'users' . DS . 'profiles' . DS . $user->attributes['profile_photo'];
                if (file_exists($photoPath)) {
                    $profilePhotoUrl = str_replace(ROOT, '', $photoPath);
                    $profilePhotoUrl = str_replace(DS, '/', $profilePhotoUrl);
                    $profilePhotoUrl = ltrim($profilePhotoUrl, '/');
                    // Valider l'URL avant de l'assigner
                    if ($this->isValidAvatarUrl($profilePhotoUrl)) {
                        $enriched['avatar'] = $profilePhotoUrl;
                        $hasCmsProfilePhoto = true;
                    }
                }
            }
            // Enrichir avec les données du profile CMS
            $enriched['age'] = $user->attributes['age'] ?? null;
            $enriched['sex'] = $user->attributes['sex'] ?? null;
            $enriched['city'] = $user->attributes['city'] ?? null;
            $enriched['district'] = $user->attributes['district'] ?? null;
            
            // Récupérer le nom du groupe
            $group = $user->getGroup();
            $enriched['group_name'] = $group ? $group->getName() : null;

            // Stocker si l'utilisateur a une photo de profil CMS pour ne pas utiliser l'avatar MySQL
            $enriched['_has_cms_profile_photo'] = $hasCmsProfilePhoto;
        } elseif ($userId) {
            $enriched['id'] = $userId;
            // Essayer de récupérer l'utilisateur du CMS pour la photo de profil
            $cmsUser = User::find('id', $userId);
            if ($cmsUser) {
                $hasProfilePhoto = isset($cmsUser->attributes['profile_photo']) && !empty($cmsUser->attributes['profile_photo']);
                if ($hasProfilePhoto) {
                    $photoPath = UPLOAD . 'users' . DS . 'profiles' . DS . $cmsUser->attributes['profile_photo'];
                    if (file_exists($photoPath)) {
                        $profilePhotoUrl = str_replace(ROOT, '', $photoPath);
                        $profilePhotoUrl = str_replace(DS, '/', $profilePhotoUrl);
                        $profilePhotoUrl = ltrim($profilePhotoUrl, '/');
                        // Valider l'URL avant de l'assigner
                        if ($this->isValidAvatarUrl($profilePhotoUrl)) {
                            $enriched['avatar'] = $profilePhotoUrl;
                        }
                    }
                }
            }
        } elseif ($email) {
            $enriched['email'] = $email;
            // Essayer de récupérer l'utilisateur du CMS pour la photo de profil
            $cmsUser = User::find('email', $email);
            if ($cmsUser && isset($cmsUser->attributes['profile_photo']) && !empty($cmsUser->attributes['profile_photo'])) {
                $photoPath = UPLOAD . 'users' . DS . 'profiles' . DS . $cmsUser->attributes['profile_photo'];
                if (file_exists($photoPath)) {
                    $profilePhotoUrl = str_replace(ROOT, '', $photoPath);
                    $profilePhotoUrl = str_replace(DS, '/', $profilePhotoUrl);
                    $profilePhotoUrl = ltrim($profilePhotoUrl, '/');
                    // Valider l'URL avant de l'assigner
                    if ($this->isValidAvatarUrl($profilePhotoUrl)) {
                        $enriched['avatar'] = $profilePhotoUrl;
                    }
                }
            }
        }

        // Enrichir depuis le CMS si possible (déjà fait en partie en haut, mais on s'assure d'avoir les données)
        if ($enriched['id'] || $enriched['email']) {
            $cmsUser = null;
            if ($enriched['id']) {
                $cmsUser = User::find('id', $enriched['id']);
            } elseif ($enriched['email']) {
                $cmsUser = User::find('email', $enriched['email']);
            }

            if ($cmsUser) {
                /** @var User $cmsUser */
                $enriched['has_mysql_data'] = true; // On garde ce flag même si c'est plus forcément MySQL
                if (empty($enriched['username']) && !empty($cmsUser->username())) {
                    $enriched['username'] = $cmsUser->username();
                }
                if (empty($enriched['email']) && !empty($cmsUser->email)) {
                    $enriched['email'] = $cmsUser->email;
                }
                unset($enriched['_has_cms_profile_photo']);
            }
        }

        // Validation finale stricte : s'assurer que l'avatar est valide, sinon le mettre à null
        if (!empty($enriched['avatar'])) {
            $avatarTrimmed = trim($enriched['avatar']);
            // Vérifier explicitement les valeurs invalides communes
            if (
                $avatarTrimmed === '/avatar' ||
                $avatarTrimmed === 'avatar' ||
                $avatarTrimmed === '/' ||
                strtolower($avatarTrimmed) === 'avatar' ||
                preg_match('/^\/avatar/i', $avatarTrimmed) ||
                !$this->isValidAvatarUrl($enriched['avatar'])
            ) {
                $enriched['avatar'] = null;
            }
        }

        return $enriched;
    }

    /**
     * Enrichit plusieurs utilisateurs en une seule requête
     * 
     * @param array $userIds Liste des IDs utilisateurs
     * @return array Tableau associatif [userId => enrichedData]
     */
    public function enrichUsers(array $userIds): array
    {
        $result = [];

        if (empty($userIds)) {
            return $result;
        }


        // Charger tous les utilisateurs CMS demandés en une seule fois
        $cmsUsersById = [];
        if (!empty($userIds)) {
            // Utiliser User::all() et filtrer (ou User::query() si available)
            $allCmsUsers = User::all();
            foreach ($allCmsUsers as $cmsUser) {
                $uid = (int)($cmsUser->attributes['id'] ?? 0);
                if (in_array($uid, $userIds)) {
                    $cmsUsersById[$uid] = $cmsUser;
                }
            }
        }

        // OPTIMISATION : Charger tous les utilisateurs CMS en une seule fois (évite N+1)
        // Ne charger que les utilisateurs nécessaires (filtrer par IDs demandés)
        $cmsUsersById = [];
        if (!empty($userIds)) {
            $allCmsUsers = User::all();
            foreach ($allCmsUsers as $cmsUser) {
                $cmsUserId = (int) ($cmsUser->attributes['id'] ?? 0);
                // Ne garder que les utilisateurs demandés
                if ($cmsUserId > 0 && in_array($cmsUserId, $userIds, true)) {
                    $cmsUsersById[$cmsUserId] = $cmsUser;
                }
            }
        }

        // Pour CHAQUE userId, récupérer le profile_photo du CMS
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            $avatar = null;
            // Récupérer les données depuis le CMS
            $cmsUser = $cmsUsersById[$userId] ?? null;

            if ($cmsUser) {
                $username = !empty($cmsUser->username()) ? $cmsUser->username() : '';
                $email = !empty($cmsUser->email) ? $cmsUser->email : '';
            }

            if ($cmsUser) {

                // Vérifier le profile_photo
                $hasProfilePhoto = isset($cmsUser->attributes['profile_photo']) && !empty($cmsUser->attributes['profile_photo']);

                if ($hasProfilePhoto) {
                    $photoPath = UPLOAD . 'users' . DS . 'profiles' . DS . $cmsUser->attributes['profile_photo'];

                    if (file_exists($photoPath)) {
                        $profilePhotoUrl = str_replace(ROOT, '', $photoPath);
                        $profilePhotoUrl = str_replace(DS, '/', $profilePhotoUrl);
                        $profilePhotoUrl = ltrim($profilePhotoUrl, '/');

                        // Valider l'URL avant de l'assigner
                        if ($this->isValidAvatarUrl($profilePhotoUrl)) {
                            $avatar = $profilePhotoUrl;
                        }
                    }
                }
            }

            // Validation finale : rejeter les valeurs invalides
            if (!empty($avatar)) {
                $avatarTrimmed = trim($avatar);
                if (
                    $avatarTrimmed === '/avatar' ||
                    $avatarTrimmed === 'avatar' ||
                    $avatarTrimmed === '/' ||
                    strtolower($avatarTrimmed) === 'avatar' ||
                    preg_match('/^\/avatar/i', $avatarTrimmed)
                ) {
                    $avatar = null;
                }
            }

            $result[$userId] = [
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'avatar' => $avatar,
                'has_mysql_data' => $cmsUser !== null,
                'created_at' => $cmsUser ? ($cmsUser->attributes['created_at'] ?? null) : null,
                'post_count' => 0, // Sera rempli par le controller si besoin ou par une requête ici
                'age' => $cmsUser ? ($cmsUser->attributes['age'] ?? null) : null,
                'sex' => $cmsUser ? ($cmsUser->attributes['sex'] ?? null) : null,
                'city' => $cmsUser ? ($cmsUser->attributes['city'] ?? null) : null,
                'district' => $cmsUser ? ($cmsUser->attributes['district'] ?? null) : null,
                'group_name' => $cmsUser ? ($cmsUser->getGroup() ? $cmsUser->getGroup()->getName() : null) : null,
            ];
        }

        // OPTIMISATION : Compter les messages pour tous les utilisateurs en une seule fois
        try {
            // Utiliser Post::query() pour compter les messages approuvés
            // GroupBy n'est pas encore nativement dans JsonActiveRecord::query() mais on peut faire une boucle simple
            // ou utiliser une approche par ID. Vu que JsonActiveRecord::query() est optimisé pour les index, 
            // on peut faire des requêtes par utilisateur si le nombre est faible, ou tout charger si nécessaire.
            // Pour être fidèle au Manager, on va simuler le group by.
            
            $posts = \Forum\Entities\Post::query()
                ->whereIn('author_id', $userIds)
                ->where('is_approved', '=', 1)
                ->get();
                
            foreach ($posts as $post) {
                $uid = (int)$post->author_id;
                if (isset($result[$uid])) {
                    $result[$uid]['post_count']++;
                }
            }
        } catch (\Exception $e) {
            // Ignorer l'erreur de comptage
        }


        return $result;
    }
}

