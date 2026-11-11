<?php

namespace Parrainage\Hooks;

use Core\Core;
use Core\Router\Router;
use Core\Auth\UsersManager;
use Core\Database;

defined('ROOT') or exit('Access denied!');

class ParrainageHooks
{
    /**
     * Injecter le champ "Code Parrain" sur la page d'inscription.
     * Hook: publicContent
     */
    public static function publicContent(string $content): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/users/register') === false) {
            return $content;
        }

        $defaultCode = htmlspecialchars($_GET['ref'] ?? '');

        $fieldHtml = '
        <div class="form-group" style="margin-top:15px; border-top: 1px dashed #ccc; padding-top: 15px;">
            <label for="parrainage_code"><i class="fa-solid fa-gift"></i> Code Parrain (optionnel)</label>
            <input type="text" name="parrainage_code" id="parrainage_code" placeholder="Entrez le code de votre parrain" value="' . $defaultCode . '">
        </div>';

        // Injecter avant le paragraphe des actions ou le bouton submit
        $content = preg_replace(
            '#(<p[^>]*class="actions"[^>]*>)#is',
            $fieldHtml . '$1',
            $content,
            1
        );

        return $content;
    }

    /**
     * Hook déclenché à la fin d'une inscription réussie.
     * Hook: users:after-registration-success
     */
    public static function afterRegistrationSuccess(array $data): void
    {
        $user = $data['user'] ?? null;
        if (!$user || !isset($user->attributes['id'])) return;

        $pdo = Database::getInstance()->getPdo();

        // 1. Lier un code existant par email si présent (pré-création admin)
        $linkStmt = $pdo->prepare("UPDATE parrainage_codes SET user_id = ? WHERE email = ? AND user_id IS NULL");
        $linkStmt->execute([$user->attributes['id'], $user->attributes['email'] ?? '']);

        // 2. Enregistrer le parrainage si un code a été saisi dans le formulaire
        $codeStr = trim($_POST['parrainage_code'] ?? '');
        if (empty($codeStr)) return;
        
        // Vérifier si le code existe
        $stmt = $pdo->prepare("SELECT id FROM parrainage_codes WHERE code = ?");
        $stmt->execute([$codeStr]);
        $codeId = $stmt->fetchColumn();

        if ($codeId) {
            // Enregistrer la relation code <-> filleul
            $insert = $pdo->prepare("INSERT INTO parrainage_referrals (code_id, referred_user_id, created_at) VALUES (?, ?, NOW())");
            try {
                $insert->execute([$codeId, $user->attributes['id']]);
            } catch (\PDOException $e) {
                // Ignorer si déjà inséré (contrainte UNIQUE)
            }
        }
    }

    /**
     * Ajoute un lien vers le tableau de bord parrainage dans le menu utilisateur.
     * Hook: user:avatar-menu
     */
    public static function avatarMenu(array $data): array
    {
        $currentUser = \Core\Auth\CurrentUser::get();
        if (!$currentUser->isLogged()) {
            return $data;
        }

        // Access limited to Admin OR Owners
        $isAdmin = $currentUser->can_access('system:admin-access');
        $isOwner = $currentUser->isOwner();
        
        if (!$isAdmin && !$isOwner) {
            return $data;
        }

        $user = UsersManager::getCurrentUser();
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM parrainage_codes WHERE user_id = ? OR email = ?");
        $stmt->execute([$user->attributes['id'], $user->attributes['email']]);
        $hasCode = ($stmt->fetchColumn() > 0);

        // Si pas de code, on vérifie si l'utilisateur est Premium pour lui proposer d'activer/inviter
        // Si non-premium, il verra la page d'incitation (public_no_access)
        if (!$hasCode && $currentUser->isPremium()) {
            $service = new \Parrainage\Services\ParrainageService();
            if ($service->isAddonActive((int)$user->attributes['id'])) {
                $hasCode = true;
            }
        }

        $router = Router::getInstance();
        try {
            $url = $router->generate('parrainage-dashboard');
        } catch (\Exception $e) {
            $url = '/parrainage';
        }

        if ($hasCode) {
            // Lien standard
            $data['items'][] = [
                'label' => 'Mon Parrainage',
                'url' => $url,
                'icon' => 'fa-solid fa-handshake-angle',
                'order' => 92
            ];
        } else {
            // CTA "Inviter des amis"
            $data['items'][] = [
                'label' => 'Inviter des amis',
                'url' => $url,
                'icon' => 'fa-solid fa-gift',
                'class' => 'avatar-menu-cta-premium',
                'order' => 92
            ];
        }
        
        return $data;
    }
}
