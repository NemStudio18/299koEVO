<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;
use Core\Core;
use Core\Plugin\PluginsManager;

defined('ROOT') OR exit('No direct script access allowed');

class PwaAdminController extends AdminController
{
    public function home()
    {
        $plugin = $this->runPlugin;

        if (!$plugin) {
            Show::msg('Plugin PWA non trouvé', 'error');
            return new AdminResponse();
        }

        // Charger les langues du plugin AVANT de créer le template
        $locale = \Core\Lang::getLocale();
        $langPath = PLUGINS . 'pwa' . DS . 'langs' . DS;
        $langFile = $langPath . $locale . '.ini';

        \Core\Lang::loadLanguageFile($langPath);
        $keysNotGeneratedText = \Core\Lang::get('pwa.keys-not-generated');
        if ($keysNotGeneratedText === 'pwa.keys-not-generated') {
            $keysNotGeneratedText = 'Les clés VAPID n\'ont pas encore été générées. Cliquez sur le bouton ci-dessous pour les générer.';
        }
        $generateKeysText = \Core\Lang::get('pwa.generate-keys');
        if ($generateKeysText === 'pwa.generate-keys') {
            $generateKeysText = 'Générer les clés VAPID';
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('pwa', 'admin-home');

        // Traitement de la génération/régénération des clés
        if (isset($_POST['generate_keys']) || isset($_POST['regenerate_keys'])) {
            if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null, false)) {
                Show::msg(Lang::get('core-invalid-token'), 'error');
                Core::getInstance()->redirect($this->router->generate('pwa-admin-home'));
                return new AdminResponse();
            }

            require_once PLUGINS . 'pwa/pwa.php';

            if (!function_exists('openssl_pkey_new')) {
                Show::msg('OpenSSL n\'est pas disponible sur ce serveur. Les clés VAPID ne peuvent pas être générées.', 'error');
                Core::getInstance()->redirect($this->router->generate('pwa-admin-home'));
                return new AdminResponse();
            }

            $keys = pwaGenerateVapidKeys();

            if ($keys && !empty($keys['publicKey']) && !empty($keys['privateKey'])) {
                // Sauvegarder les clés dans un fichier séparé au lieu de la config du plugin
                require_once PLUGINS . 'pwa/pwa.php';
                if (pwaSaveVapidKeys($keys['publicKey'], $keys['privateKey'])) {
                    Show::msg(Lang::get('pwa.keys-regenerated'), 'success');
                } else {
                    Show::msg('Erreur lors de la sauvegarde des clés', 'error');
                }
            } else {
                Show::msg(Lang::get('pwa.keys-generation-failed'), 'error');
            }
            Core::getInstance()->redirect($this->router->generate('pwa-admin-home'));
        }

        // Lire les clés depuis le fichier séparé
        require_once PLUGINS . 'pwa/pwa.php';
        $vapidKeys = pwaGetVapidKeys();
        $publicKey = $vapidKeys ? $vapidKeys['publicKey'] : '';
        $hasKeys = !empty($publicKey);

        // Compter les abonnements
        $subsFile = DATA_PLUGIN . 'pwa/subscriptions.json';
        $subscriptions = Util::readJsonFile($subsFile, true);
        $subCount = is_array($subscriptions) ? count($subscriptions) : 0;

        // Passer toutes les traductions directement au template
        $tpl->set('hasKeys', $hasKeys);
        $tpl->set('publicKey', $publicKey);
        $tpl->set('subscriptionCount', $subCount);
        $tpl->set('token', $this->user->token);
        $tpl->set('sendNotificationUrl', $this->router->generate('pwa-admin-send-notification'));
        $tpl->set('keysNotGeneratedText', $keysNotGeneratedText);
        $tpl->set('generateKeysText', $generateKeysText);

        // Passer toutes les traductions PWA pour éviter les problèmes avec les tirets
        // Utiliser des valeurs par défaut en français si les traductions ne sont pas trouvées
        $langPwaName = \Core\Lang::get('pwa.name');
        $tpl->set('lang_pwa_name', ($langPwaName !== 'pwa.name') ? $langPwaName : 'PWA');

        $langPwaPublicKey = \Core\Lang::get('pwa.public-key');
        $tpl->set('lang_pwa_public_key', ($langPwaPublicKey !== 'pwa.public-key') ? $langPwaPublicKey : 'Clé publique VAPID');

        $langPwaRegenerateKeys = \Core\Lang::get('pwa.regenerate-keys');
        $tpl->set('lang_pwa_regenerate_keys', ($langPwaRegenerateKeys !== 'pwa.regenerate-keys') ? $langPwaRegenerateKeys : 'Régénérer les clés');

        $langPwaSubscriptionsCount = \Core\Lang::get('pwa.subscriptions-count');
        $tpl->set('lang_pwa_subscriptions_count', ($langPwaSubscriptionsCount !== 'pwa.subscriptions-count') ? $langPwaSubscriptionsCount : 'Nombre d\'abonnements');

        $langPwaSendNotification = \Core\Lang::get('pwa.send-notification');
        $tpl->set('lang_pwa_send_notification', ($langPwaSendNotification !== 'pwa.send-notification') ? $langPwaSendNotification : 'Envoyer une notification');

        $langPwaNotificationTitle = \Core\Lang::get('pwa.notification-title');
        $tpl->set('lang_pwa_notification_title', ($langPwaNotificationTitle !== 'pwa.notification-title') ? $langPwaNotificationTitle : 'Titre');

        $langPwaNotificationMessage = \Core\Lang::get('pwa.notification-message');
        $tpl->set('lang_pwa_notification_message', ($langPwaNotificationMessage !== 'pwa.notification-message') ? $langPwaNotificationMessage : 'Message');

        $langPwaNotificationUrl = \Core\Lang::get('pwa.notification-url');
        $tpl->set('lang_pwa_notification_url', ($langPwaNotificationUrl !== 'pwa.notification-url') ? $langPwaNotificationUrl : 'URL (optionnel)');

        $langPwaSend = \Core\Lang::get('pwa.send');
        $tpl->set('lang_pwa_send', ($langPwaSend !== 'pwa.send') ? $langPwaSend : 'Envoyer');
        $response->addTemplate($tpl);
        return $response;
    }

    public function sendNotification()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('pwa:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            Core::getInstance()->redirect($this->router->generate('pwa-admin-home'));
            return new AdminResponse();
        }

        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            Core::getInstance()->redirect($this->router->generate('pwa-admin-home'));
            return new AdminResponse();
        }

        $title = filter_var($_POST['title'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $url = filter_var($_POST['url'] ?? '', FILTER_SANITIZE_URL);

        if (empty($title) || empty($message)) {
            Show::msg(Lang::get('pwa.notification-empty'), 'error');
            Core::getInstance()->redirect($this->router->generate('pwa-admin-home'));
            return new AdminResponse();
        }

        require_once PLUGINS . 'pwa/lib/WebPush/WebPush.php';
        $webPush = new \Pwa\WebPush\WebPush($this->runPlugin);

        $sent = $webPush->sendNotificationToAll($title, $message, $url);

        if ($sent > 0) {
            Show::msg(Lang::get('pwa.notification-sent', $sent), 'success');
        } else {
            Show::msg(Lang::get('pwa.notification-error'), 'error');
        }

        Core::getInstance()->redirect($this->router->generate('pwa-admin-home'));
    }
}


