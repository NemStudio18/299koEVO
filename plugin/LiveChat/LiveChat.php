<?php

namespace LiveChat;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
        @mkdir(DATA_PLUGIN . 'LiveChat', 0755, true);
    }

    /**
     * Injecte le script et les styles du widget de tchat dans le pied de page public
     */
    public static function addFooter(): void
    {
        // Ne pas injecter dans l'administration
        if ((defined('ADMIN_MODE') && ADMIN_MODE) || (defined('IS_ADMIN') && IS_ADMIN)) {
            return;
        }

        $config = self::getConfig();
        if (empty($config['activate'])) {
            return;
        }

        echo '<link rel="stylesheet" href="/plugin/LiveChat/assets/livechat.css?v=' . filemtime(PLUGINS . 'LiveChat/assets/livechat.css') . '">';
        echo '<script src="/plugin/LiveChat/assets/livechat.js?v=' . filemtime(PLUGINS . 'LiveChat/assets/livechat.js') . '" defer></script>';
    }

    public static function getConfig(): array
    {
        $configFile = DATA_PLUGIN . 'LiveChat/config.json';
        if (file_exists($configFile)) {
            $data = json_decode(file_get_contents($configFile), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [
            'activate' => 1,
            'discord_webhook' => '',
            'admin_name' => 'Équipe Support',
            'welcome_message' => 'Bonjour ! Une question sur votre établissement ou sur le site ? Discutons en direct.'
        ];
    }
}
