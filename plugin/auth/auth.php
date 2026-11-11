<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin Auth — Login Facebook/Google OAuth.
 */

function authInstall(): void
{
    $dataDir = DATA_PLUGIN . 'auth' . DS;
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    // Config fonctionnelle (tokens) stockée hors param/config.json pour éviter la maintenance forcée
    $configFile = $dataDir . 'auth_conf.json';
    if (!file_exists($configFile)) {
        $defaultConfig = [
            'activate' => true,
            'priority' => 10,
            'facebook_enabled' => false,
            'google_enabled' => false,
            'facebook_app_id' => '',
            'facebook_app_secret' => '',
            'google_client_id' => '',
            'google_client_secret' => '',
            'content_position' => 'bottom',
            'login_text' => '',
            'register_text' => '',
            'show_spoiler' => false,
            'spoiler_title' => 'Besoin d\'aide ?',
            'spoiler_type' => 'accordion',
            'spoiler_content' => '',
            'spoiler_show_on_login' => true,
            'spoiler_show_on_register' => true,
            'checkboxes' => [],
            'dynamic_fields' => [
                ['id' => 'age', 'label' => 'Âge', 'type' => 'number', 'icon' => 'fa-solid fa-birthday-cake', 'enabled' => true, 'required' => false, 'show_on_register' => true],
                ['id' => 'sex', 'label' => 'Sexe', 'type' => 'select', 'icon' => 'fa-solid fa-venus-mars', 'options' => 'M:Homme,F:Femme,O:Autre', 'enabled' => true, 'required' => false, 'show_on_register' => true],
                ['id' => 'city', 'label' => 'Ville', 'type' => 'select', 'icon' => 'fa-solid fa-city', 'source' => 'common/data/agglo.json', 'enabled' => true, 'required' => false, 'show_on_register' => true],
                ['id' => 'district', 'label' => 'Quartier', 'type' => 'select', 'icon' => 'fa-solid fa-map-marker-alt', 'source' => 'common/data/quartiers-bourges.json', 'enabled' => true, 'required' => false, 'show_on_register' => true]
            ]
        ];
        \Utils\Util::writeJsonFile($configFile, $defaultConfig);
    }
}

function authBeforeRunPlugin(): void
{
    foreach (glob(PLUGINS . 'auth/controllers/' . '*.php') as $file) {
        require_once $file;
    }
}

/**
 * Injecte la feuille de style et le JS public.
 */
function authFrontHead(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $allowedRoutes = ['/users/login', '/users/register', '/users/profile', '/users/profile/edit'];
    $allowed = false;
    foreach ($allowedRoutes as $route) {
        if (strpos($uri, $route) !== false) {
            $allowed = true;
            break;
        }
    }

    if (!$allowed) {
        return '';
    }

    $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
    $cssUrl = $siteUrl . '/plugin/auth/template/public.css';
    $jsUrl = $siteUrl . '/plugin/auth/template/public.js';

    $html = '<link rel="stylesheet" href="' . $cssUrl . '?v=' . VERSION . '">' . PHP_EOL;
    $html .= '<script src="' . $jsUrl . '?v=' . VERSION . '" defer></script>';
    
    return $html;
}

/**
 * Hook de validation pour l'inscription
 */
function authRegisterValidation(): void
{
    $configFile = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';
    if (!file_exists($configFile)) return;

    $config = \Utils\Util::readJsonFile($configFile, true) ?: [];
    if (!($config['activate'] ?? false) || empty($config['checkboxes'])) return;

    $router = \Core\Router\Router::getInstance();
    $hasError = false;

    foreach ($config['checkboxes'] as $index => $cb) {
        if (($cb['required'] ?? false)) {
            // Vérifier si la case est cochée
            if (!isset($_POST['auth_cb'][$index])) {
                \Utils\Show::msg("Veuillez cocher la case obligatorie : " . htmlspecialchars($cb['label']), 'error');
                $hasError = true;
            }
        }
    }

    if ($hasError) {
        // Rediriger pour arrêter le processus d'inscription
        $core = \Core\Core::getInstance();
        $core->redirect($router->generate('register'));
    }
}

/**
 * Hook de filtre pour injecter les éléments personnalisés dans le contenu HTML
 */
function authPublicContent(string $content): string
{
    // Vérifier si on est sur une page login/register
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/users/login') === false && strpos($uri, '/users/register') === false) {
        return $content;
    }
    $isRegisterPage = strpos($uri, '/users/register') !== false;

    $configFile = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';
    if (!file_exists($configFile)) {
        return $content;
    }

    $config = \Utils\Util::readJsonFile($configFile, true) ?: [];
    if (!($config['activate'] ?? false)) {
        return $content;
    }

    // 1. Préparation des Checkboxes (Injection dans le formulaire - UNIQUEMENT SUR INSCRIPTION)
    if ($isRegisterPage && !empty($config['checkboxes'])) {
        $cbHtml = '<div class="auth-custom-checkboxes">';
        foreach ($config['checkboxes'] as $index => $cb) {
            $requiredIdx = (int)$index;
            $requiredAttr = ($cb['required'] ?? false) ? 'required' : '';
            $cbHtml .= '<div class="auth-checkbox-field">';
            $cbHtml .= '<input type="checkbox" name="auth_cb[' . $requiredIdx . ']" id="auth_cb_' . $requiredIdx . '" ' . $requiredAttr . ' />';
            $cbHtml .= '<label for="auth_cb_' . $requiredIdx . '">' . htmlspecialchars($cb['label']) . '</label>';
            $cbHtml .= '</div>';
        }
        $cbHtml .= '</div>';

        // Injecter avant le bouton de soumission
        $content = preg_replace(
            '#(<button[^>]*type="submit"[^>]*>.*?</button>)#is',
            $cbHtml . '$1',
            $content,
            1
        );
    }

    // 1b. Injection des champs dynamiques sur la page d'inscription
    if ($isRegisterPage && !empty($config['dynamic_fields'])) {
        $fieldsHtml = '<div class="auth-dynamic-fields">';
        foreach ($config['dynamic_fields'] as $df) {
            if (!($df['enabled'] ?? false)) {
                continue;
            }

            // Skip fields that shouldn't be shown on registration
            if (!($df['show_on_register'] ?? true)) {
                continue;
            }

            $id = $df['id'];
            $label = $df['label'] ?? ucfirst($id);
            $type = $df['type'] ?? 'text';
            $icon = $df['icon'] ?? 'fa-solid fa-info-circle';
            $required = ($df['required'] ?? false) ? 'required' : '';

            $fieldsHtml .= '<p class="field">';
            $fieldsHtml .= '<label for="register-' . htmlspecialchars($id) . '"><i class="' . htmlspecialchars($icon) . '"></i> ' . htmlspecialchars($label) . '</label>';

            if ($type === 'select') {
                $fieldsHtml .= '<select name="' . htmlspecialchars($id) . '" id="register-' . htmlspecialchars($id) . '" ' . $required . '>';
                $fieldsHtml .= '<option value="">-- Sélectionner --</option>';

                if (!empty($df['options'])) {
                    $options = explode(',', $df['options']);
                    foreach ($options as $opt) {
                        if (strpos($opt, ':') !== false) {
                            [$key, $optLabel] = explode(':', $opt, 2);
                            $fieldsHtml .= '<option value="' . htmlspecialchars(trim($key)) . '">' . htmlspecialchars(trim($optLabel)) . '</option>';
                        }
                    }
                }

                if (!empty($df['source']) && file_exists(ROOT . $df['source'])) {
                    $sourceData = \Utils\Util::readJsonFile(ROOT . $df['source'], true);
                    if (is_array($sourceData)) {
                        foreach ($sourceData as $item) {
                            if (isset($item['value'], $item['label'])) {
                                $fieldsHtml .= '<option value="' . htmlspecialchars($item['value']) . '">' . htmlspecialchars($item['label']) . '</option>';
                            }
                        }
                    }
                }

                $fieldsHtml .= '</select>';
            } elseif ($type === 'number') {
                $fieldsHtml .= '<input type="number" name="' . htmlspecialchars($id) . '" id="register-' . htmlspecialchars($id) . '" ' . $required . '>';
            } else {
                $fieldsHtml .= '<input type="text" name="' . htmlspecialchars($id) . '" id="register-' . htmlspecialchars($id) . '" ' . $required . '>';
            }

            $fieldsHtml .= '</p>';
        }
        $fieldsHtml .= '</div>';

        // Injecter avant le bouton de soumission
        $content = preg_replace(
            '#(<p[^>]*class="actions"[^>]*>)#is',
            $fieldsHtml . '$1',
            $content,
            1
        );
    }

    // 2. Préparation du Texte Personnalisé selon la page
    $textField = $isRegisterPage ? 'register_text' : 'login_text';
    
    $extraHtml = '<div class="auth-plugin-extra">';
    if (!empty($config[$textField])) {
        $extraHtml .= '<div class="auth-custom-text">' . $config[$textField] . '</div>';
    }

    // 3. Préparation des Boutons OAuth
    $buttons = [];
    $oauthActionText = $isRegisterPage ? "S'inscrire avec" : "Se connecter avec";
    if (($config['facebook_enabled'] ?? false) && !empty($config['facebook_app_id'])) {
        $router = \Core\Router\Router::getInstance();
        $fbUrl = $router->generate('auth-facebook');
        $buttons[] = '<a href="' . htmlspecialchars($fbUrl) . '" class="button facebook"><i class="fa-brands fa-facebook"></i> ' . $oauthActionText . ' Facebook</a>';
    }
    if (($config['google_enabled'] ?? false) && !empty($config['google_client_id'])) {
        $router = \Core\Router\Router::getInstance();
        $googleUrl = $router->generate('auth-google');
        $buttons[] = '<a href="' . htmlspecialchars($googleUrl) . '" class="button google"><i class="fa-brands fa-google"></i> ' . $oauthActionText . ' Google</a>';
    }
    if (!empty($buttons)) {
        $extraHtml .= '<div class="oauth-divider"><span>ou</span></div>';
        $extraHtml .= '<div class="oauth-buttons">' . implode('', $buttons) . '</div>';
    }

    // 4. Préparation du Spoiler / Aide
    $showSpoiler = $config['show_spoiler'] ?? false;
    
    // Check if spoiler should be shown on this specific page
    if ($showSpoiler) {
        $showOnLogin = $config['spoiler_show_on_login'] ?? true;
        $showOnRegister = $config['spoiler_show_on_register'] ?? true;
        
        // Skip spoiler if it shouldn't be shown on this page
        if (($isRegisterPage && !$showOnRegister) || (!$isRegisterPage && !$showOnLogin)) {
            $showSpoiler = false;
        }
    }
    
    if ($showSpoiler) {
        $title = htmlspecialchars($config['spoiler_title'] ?? 'Besoin d\'aide ?');
        $type = $config['spoiler_type'] ?? 'accordion';
        $helpId = 'auth-help-' . uniqid();
        
        $extraHtml .= '<div class="auth-spoiler" data-type="' . $type . '" data-modal="' . $helpId . '">';
        $extraHtml .= '<div class="auth-spoiler-toggle"><span class="spoiler-arrow"><i class="fa-solid fa-chevron-down"></i></span> ' . $title . '</div>';
        
        if ($type === 'accordion') {
            $extraHtml .= '<div class="auth-spoiler-content">' . $config['spoiler_content'] . '</div>';
        }
        $extraHtml .= '</div>';

        if ($type === 'modal') {
            $extraHtml .= '<div id="' . $helpId . '" class="auth-modal">';
            $extraHtml .= '<div class="auth-modal-content">';
            $extraHtml .= '<span class="auth-modal-close">&times;</span>';
            $extraHtml .= '<h3>' . $title . '</h3>';
            $extraHtml .= '<div class="auth-modal-body">' . $config['spoiler_content'] . '</div>';
            $extraHtml .= '</div></div>';
        }
    }

    $extraHtml .= '</div>';

    // Injection selon la position
    $position = $config['content_position'] ?? 'bottom';
    
    // Regex robuste pour cibler la section auth-form
    $regexSection = '#(<section[^>]*?class=["\'][^"\']*?\buser-auth-form\b[^"\']*?["\'][^>]*?>)#is';
    $regexForm = '#(<section[^>]*?class=["\'][^"\']*?\buser-auth-form\b[^"\']*?["\'][^>]*?>.*?</form>)#is';

    if ($position === 'top') {
        // Injecter avant le formulaire (dans la section)
        $content = preg_replace($regexSection, '$1' . $extraHtml, $content, 1);
    } else {
        // Injecter après le formulaire (par défaut)
        $content = preg_replace($regexForm, '$1' . $extraHtml, $content, 1);
    }

    return $content;
}

/**
 * Injecte le CSS/JS pour l'admin.
 */
function authAdminHead(): string
{
    $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
    $cssUrl = $siteUrl . '/plugin/auth/template/admin.css';
    return '<link rel="stylesheet" href="' . $cssUrl . '?v=' . VERSION . '">' . PHP_EOL;
}

/**
 * Hook: user:profile-dynamic-fields
 * Injecte les champs dynamiques configurés dans le profil (affichage)
 */
function authProfileDynamicFields(array $fields): array
{
    $configFile = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';
    if (!file_exists($configFile)) {
        return $fields;
    }

    $config = \Utils\Util::readJsonFile($configFile, true) ?: [];
    $dynamicFields = $config['dynamic_fields'] ?? [];

    $user = \Core\Auth\UsersManager::getCurrentUser();
    if (!$user) {
        return $fields;
    }

    foreach ($dynamicFields as $df) {
        if (!($df['enabled'] ?? false)) {
            continue;
        }

        $value = $user->attributes[$df['id']] ?? '';
        
        // Pour les selects, afficher le label au lieu de la valeur
        if ($df['type'] === 'select' && !empty($df['options'])) {
            $options = explode(',', $df['options']);
            foreach ($options as $opt) {
                if (strpos($opt, ':') !== false) {
                    [$key, $label] = explode(':', $opt, 2);
                    if (trim($key) === $value) {
                        $value = trim($label);
                        break;
                    }
                }
            }
        }

        // Charger les données depuis un fichier source si besoin
        if ($df['type'] === 'select' && !empty($df['source']) && file_exists(ROOT . $df['source'])) {
            $sourceData = \Utils\Util::readJsonFile(ROOT . $df['source'], true);
            if (is_array($sourceData)) {
                foreach ($sourceData as $item) {
                    if (isset($item['value'], $item['label']) && $item['value'] === $value) {
                        $value = $item['label'];
                        break;
                    }
                }
            }
        }

        $fields[] = [
            'label' => $df['label'] ?? ucfirst($df['id']),
            'value' => $value,
            'icon' => $df['icon'] ?? 'fa-solid fa-info-circle'
        ];
    }

    return $fields;
}

/**
 * Hook: user:profile-edit-dynamic-fields
 * Injecte les champs dynamiques dans le formulaire d'édition du profil
 */
function authProfileEditDynamicFields($user): string
{
    $configFile = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';
    if (!file_exists($configFile)) {
        return '';
    }

    $config = \Utils\Util::readJsonFile($configFile, true) ?: [];
    $dynamicFields = $config['dynamic_fields'] ?? [];

    if (empty($dynamicFields)) {
        return '';
    }

    $html = '';
    foreach ($dynamicFields as $df) {
        if (!($df['enabled'] ?? false)) {
            continue;
        }

        $id = $df['id'];
        $label = $df['label'] ?? ucfirst($id);
        $type = $df['type'] ?? 'text';
        $icon = $df['icon'] ?? 'fa-solid fa-info-circle';
        $required = ($df['required'] ?? false) ? 'required' : '';
        $value = $user->attributes[$id] ?? '';

        $html .= '<div class="form-group">';
        $html .= '<label for="' . htmlspecialchars($id) . '"><i class="' . htmlspecialchars($icon) . '"></i> ' . htmlspecialchars($label) . '</label>';

        if ($type === 'select') {
            $html .= '<select name="' . htmlspecialchars($id) . '" id="' . htmlspecialchars($id) . '" ' . $required . '>';
            $html .= '<option value="">-- Sélectionner --</option>';

            // Options inline
            if (!empty($df['options'])) {
                $options = explode(',', $df['options']);
                foreach ($options as $opt) {
                    if (strpos($opt, ':') !== false) {
                        [$key, $optLabel] = explode(':', $opt, 2);
                        $key = trim($key);
                        $optLabel = trim($optLabel);
                        $selected = ($value === $key) ? 'selected' : '';
                        $html .= '<option value="' . htmlspecialchars($key) . '" ' . $selected . '>' . htmlspecialchars($optLabel) . '</option>';
                    }
                }
            }

            // Options depuis un fichier source
            if (!empty($df['source']) && file_exists(ROOT . $df['source'])) {
                $sourceData = \Utils\Util::readJsonFile(ROOT . $df['source'], true);
                if (is_array($sourceData)) {
                    foreach ($sourceData as $item) {
                        if (isset($item['value'], $item['label'])) {
                            $selected = ($value === $item['value']) ? 'selected' : '';
                            $html .= '<option value="' . htmlspecialchars($item['value']) . '" ' . $selected . '>' . htmlspecialchars($item['label']) . '</option>';
                        }
                    }
                }
            }

            $html .= '</select>';
        } elseif ($type === 'number') {
            $html .= '<input type="number" name="' . htmlspecialchars($id) . '" id="' . htmlspecialchars($id) . '" value="' . htmlspecialchars($value) . '" ' . $required . '>';
        } else {
            $html .= '<input type="text" name="' . htmlspecialchars($id) . '" id="' . htmlspecialchars($id) . '" value="' . htmlspecialchars($value) . '" ' . $required . '>';
        }

        $html .= '</div>';
    }

    return $html;
}

/**
 * Listeners pour les événements Auth (Decoupled bridge)
 */
function authOnLoginFailed(array $data): void
{
    $email = $data['email'] ?? 'Clé manquante';
    $userId = $data['user_id'] ?? null;
    $reason = $data['reason'] ?? 'Inconnue';

    // On notifie l'utilisateur concerné s'il existe
    if ($userId) {
        \Core\Core::getInstance()->callHook('notifications:send', [
            'user_id' => (int)$userId,
            'type' => 'auth.login_failed',
            'source_plugin' => 'auth',
            'data' => [
                'key' => 'auth.login_failed',
                'params' => [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '???'
                ],
                'url' => '/profile?tab=security'
            ],
            'external_id' => 'auth:fail:' . $userId . ':' . date('Y-m-d-H') // Flood protection par heure
        ]);
    }
}

function authOnRegistered(array $data): void
{
    $user = $data['user'] ?? null;
    if (!$user) return;

    $userId = $user->attributes['id'] ?? $user->id;

    \Core\Core::getInstance()->callHook('notifications:send', [
        'user_id' => $userId,
        'type' => 'auth.welcome',
        'source_plugin' => 'auth',
        'data' => [
            'key' => 'auth.welcome',
            'params' => [
                'name' => $user->attributes['username'] ?? 'nouvel utilisateur'
            ],
            'url' => '/users/profile'
        ]
    ]);

    // Notifier également les administrateurs d'une nouvelle inscription
    try {
        $adminUsers = \Core\Auth\User::query()->where('role', '=', '1')->get();
        $adminIds = !empty($adminUsers) ? array_map(fn($u) => $u->attributes['id'], $adminUsers) : [1];

        \Core\Core::getInstance()->callHook('notifications:send', [
            'user_id' => $adminIds,
            'type' => 'auth.registration',
            'source_plugin' => 'auth',
            'data' => [
                'key' => 'auth.registration',
                'params' => [
                    'username' => $user->attributes['username'] ?? ($user->attributes['email'] ?? 'Nouveau membre'),
                    'email' => $user->attributes['email'] ?? ''
                ],
                'url' => '/admin/users'
            ]
        ]);
    } catch (\Throwable $e) {}
}

/**
 * Hook de filtre pour renvoyer l'URL de sauvegarde de la préférence de thème
 */
function authThemeSaveUrl($params = ''): string
{
    $router = \Core\Router\Router::getInstance();
    return $router->generate('user-save-preference');
}

function authLoginUrl($params = ''): string
{
    return \Core\Router\Router::getInstance()->generate('login');
}

function authRegisterUrl($params = ''): string
{
    return \Core\Router\Router::getInstance()->generate('register');
}

function authLogoutUrl($params = ''): string
{
    return \Core\Router\Router::getInstance()->generate('logout');
}

function authAdminUrl($params = ''): string
{
    return \Core\Router\Router::getInstance()->generate('admin');
}

function authProfileUrl($params = ''): string
{
    return \Core\Router\Router::getInstance()->generate('profile');
}
// Hooks are now registered in hooks.json
