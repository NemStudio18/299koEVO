<?php

/**
 * @copyright (C) 2022, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

## Fonction d'installation

function antispamInstall() {
    $dir = DATA_PLUGIN . 'antispam' . DS;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    AntispamStats::ensureFile();
}

/**
 * Journal et compteurs locaux du plugin Antispam (stockés dans data/plugin/antispam/stats.json).
 */
class AntispamStats
{
    public const MAX_RECENT = 50;

    public static function filePath(): string
    {
        return DATA_PLUGIN . 'antispam' . DS . 'stats.json';
    }

    public static function ensureFile(): void
    {
        $dir = dirname(self::filePath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_file(self::filePath())) {
            \Utils\Util::writeJsonFile(self::filePath(), self::emptyPayload());
        }
    }

    public static function emptyPayload(): array
    {
        return [
            'blocked_total' => 0,
            'passed_total'  => 0,
            'by_method'     => [],
            'by_reason'     => [],
            'recent'        => [],
            'updated_at'    => '',
        ];
    }

    public static function getStats(): array
    {
        $data = \Utils\Util::readJsonFile(self::filePath(), true);
        if (!is_array($data)) {
            return self::emptyPayload();
        }
        return array_merge(self::emptyPayload(), $data);
    }

    public static function clear(): void
    {
        self::ensureFile();
        \Utils\Util::writeJsonFile(self::filePath(), self::emptyPayload());
    }

    public static function record(string $method, bool $valid, string $reason = ''): void
    {
        $method = $method !== '' ? $method : 'unknown';
        $reason = $reason !== '' ? $reason : ($valid ? 'valid_captcha' : 'wrong_captcha');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $ip = self::maskIp((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

        if (!$valid) {
            try {
                \Core\Core::getInstance()->getLogger()->log(
                    \Core\Logger::LEVEL_WARNING,
                    sprintf('[Antispam] Bloqué (%s / %s) sur %s [%s]', $method, $reason, $uri ?: 'cli', $ip)
                );
            } catch (\Throwable $e) {
            }
        }

        try {
            self::ensureFile();
            $stats = self::getStats();
            if ($valid) {
                $stats['passed_total'] = (int) ($stats['passed_total'] ?? 0) + 1;
            } else {
                $stats['blocked_total'] = (int) ($stats['blocked_total'] ?? 0) + 1;
                $stats['by_reason'][$reason] = (int) ($stats['by_reason'][$reason] ?? 0) + 1;
            }
            if (!isset($stats['by_method'][$method]) || !is_array($stats['by_method'][$method])) {
                $stats['by_method'][$method] = ['blocked' => 0, 'passed' => 0];
            }
            $bucket = $valid ? 'passed' : 'blocked';
            $stats['by_method'][$method][$bucket] = (int) ($stats['by_method'][$method][$bucket] ?? 0) + 1;

            array_unshift($stats['recent'], [
                'date'   => date('Y-m-d H:i:s'),
                'status' => $valid ? 'passed' : 'blocked',
                'method' => $method,
                'reason' => $reason,
                'uri'    => $uri,
                'ip'     => $ip,
            ]);
            $stats['recent'] = array_slice($stats['recent'], 0, self::MAX_RECENT);
            $stats['updated_at'] = date('Y-m-d H:i:s');

            \Utils\Util::writeJsonFile(self::filePath(), $stats);
        } catch (\Throwable $e) {
            // Ne jamais bloquer la soumission en cas de problème d'écriture stats
        }
    }

    public static function maskIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = 'x';
            return implode('.', $parts);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 3)) . '::x';
        }
        return 'local';
    }

    public static function methodLabel(string $method): string
    {
        return [
            'useText'      => 'Captcha texte',
            'useIcon'      => 'Captcha icône',
            'useFlexybot'  => '299nobot',
            'useRecaptcha' => 'reCAPTCHA',
        ][$method] ?? $method;
    }

    public static function reasonLabel(string $reason): string
    {
        return [
            'honeypot'             => 'Pot-de-miel rempli (robot)',
            'missing_session'      => 'Session absente / sans cookies',
            'invalid_nonce'        => 'Jeton session invalide ou rejeu',
            'server_too_fast'      => 'Soumission < 1s (serveur)',
            'expired'              => 'Session expirée (> 1h)',
            'wrong_answer'         => 'Calcul de contrôle incorrect',
            'not_clicked'          => 'Case non cliquée / JS absent',
            'webdriver'            => 'Navigateur automatisé (webdriver)',
            'client_too_fast'      => 'Clic trop rapide (< 0,8s)',
            'no_human_interaction' => 'Aucune interaction humaine détectée',
            'wrong_captcha'        => 'Réponse captcha incorrecte',
            'human_behavior'       => 'Comportement humain validé',
            'fallback_answer'      => 'Calcul complémentaire réussi',
            'valid_captcha'        => 'Captcha validé',
        ][$reason] ?? $reason;
    }
}

## Hooks
## Code relatif au plugin

class antispam {

    protected $captcha;
    protected string $typeCaptcha = 'useText';

    public function __construct() {
        $pluginManager = \Core\Plugin\PluginsManager::getInstance();
        $typeCaptcha = (string) ($pluginManager->getPluginConfVal('antispam', 'type') ?: 'useText');
        $this->typeCaptcha = $typeCaptcha;
        if ($typeCaptcha === 'useText') {
            // UseText lib
            require_once PLUGINS . 'antispam' . DS . 'lib' . DS . 'AntispamAbstractCaptcha.php';
            require_once PLUGINS . 'antispam' . DS . 'lib' . DS . 'AntispamTextCaptcha.php';
            $this->captcha = new AntispamTextCaptcha();
        } elseif ($typeCaptcha === 'useRecaptcha') {
            // ReCaptcha lib
            require_once PLUGINS . 'antispam' . DS . 'lib' . DS . 'AntispamReCaptcha.php';
            $public = $pluginManager->getPluginConfVal('antispam', 'recaptchaPublicKey');
            $secret = $pluginManager->getPluginConfVal('antispam', 'recaptchaSecretKey');
            $this->captcha = new AntispamReCaptcha($public, $secret);
        } elseif ($typeCaptcha === 'useIcon') {
            require_once PLUGINS . 'antispam' . DS . 'lib' . DS . 'AntispamAbstractCaptcha.php';
            require_once PLUGINS . 'antispam' . DS . 'lib' . DS . 'AntispamIconCaptcha.php';
            $this->captcha = new AntispamIconCaptcha();
        } elseif ($typeCaptcha === 'useFlexybot') {
            require_once PLUGINS . 'antispam' . DS . 'lib' . DS . 'AntispamAbstractCaptcha.php';
            require_once PLUGINS . 'antispam' . DS . 'lib' . DS . 'AntispamFlexyBotCaptcha.php';
            $this->captcha = new AntispamFlexyBotCaptcha();
        }
    }

    public function show() {
        return $this->captcha ? $this->captcha->getText() : '';
    }

    public function isValid() {
        if (!$this->captcha) {
            return true;
        }
        $valid = (bool) $this->captcha->isValid();
        $reason = method_exists($this->captcha, 'getLastReason')
            ? (string) $this->captcha->getLastReason()
            : ($valid ? 'valid_captcha' : 'wrong_captcha');
        AntispamStats::record($this->typeCaptcha, $valid, $reason);
        return $valid;
    }

}

function antispamEndHead(): void
{
    $css = PLUGINS . 'antispam' . DS . 'template' . DS . 'public.css';
    if (is_file($css)) {
        $ver = '?v=' . filemtime($css);
        echo '<link rel="stylesheet" href="' . \Utils\Util::urlBuild($css) . $ver . '">';
    }
}


/**
 * Hook: antispam:get-captcha
 */
function antispamGetCaptcha(array $data): array
{
    $antispam = new antispam();
    $data['field_html'] = $antispam->show();
    return $data;
}

/**
 * Hook: antispam:validate
 */
function antispamValidate(array $data): array
{
    $antispam = new antispam();
    $data['is_valid'] = $antispam->isValid();
    return $data;
}

// Hooks are now registered in hooks.json
