<?php
/**
 * @copyright (C) 2024, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
ini_set('display_errors', 1);
const ROOT = '.' . DIRECTORY_SEPARATOR;
const DS = DIRECTORY_SEPARATOR;

include_once(ROOT . 'common/common.php');

use Core\Auth\Permissions;
use Core\Core;
use Core\Lang;
use Core\Plugin\PluginsManager;
use Utils\Show;

if (file_exists(DATA . 'config.json'))
    die('A config file is already exist');
$core = Core::getInstance();
$pluginsManager = PluginsManager::getInstance();
$url = $core->makeSiteUrl() . '/install.php';

// ----------------- Begin tests
// Test PHP Version
$minPHPVersion = 7.4;
$errorPHP = !((float) substr(phpversion(), 0, 3) >= $minPHPVersion);

$errorFopen = true;
if( filter_var( ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN ) ) {
    $errorFopen = false;
}

$errorCurl = false;
if (!function_exists('curl_init')) {
    $errorCurl = true;
}

$errorZip = true;
if(extension_loaded('zip')) {
    $errorZip = false;
}

// Test mod_rewrite
if (function_exists('apache_get_modules')) {
    // PHP is installed as an Apache module
    $errorRewrite = !in_array('mod_rewrite', apache_get_modules()) ? true : false;
} else {
    // PHP is installed as a CGI
    $errorRewrite = 'CGI';
}

// Test writable
if (!is_dir(DATA)) {
    @mkdir(DATA);
}
$errorDataWrite = !is_writable(DATA);

$availablesLocales = Lang::$availablesLocales;
$pdoDrivers = class_exists('PDO') ? PDO::getAvailableDrivers() : [];
$hasSqlitePdo = in_array('sqlite', $pdoDrivers, true);
$hasMysqlPdo = in_array('mysql', $pdoDrivers, true);
$defaultStorageMode = $hasSqlitePdo ? 'sqlite' : 'json';

if (!isset($_SESSION['install_token'])) {
    $_SESSION['install_token'] = bin2hex(random_bytes(32));
}
$installToken = $_SESSION['install_token'];

if (count($_POST) > 0) {
    $postInstallToken = $_POST['_installToken'] ?? '';
    if (!hash_equals($_SESSION['install_token'] ?? '', $postInstallToken)) {
        Show::msg(Lang::get('install-security-token-invalid') ?? 'Invalid security token, please retry.', 'error');
        header('location:' . $url);
        die();
    }
    $_SESSION['install_token'] = bin2hex(random_bytes(32));

    $adminEmail = filter_var($_POST['adminEmail'] ?? '', FILTER_VALIDATE_EMAIL);
    $adminPwdPlain = $_POST['adminPwd'] ?? '';
    $adminUsername = trim($_POST['adminUsername'] ?? '');
    $storageMode = $_POST['storage_mode'] ?? $defaultStorageMode;
    if (!in_array($storageMode, ['sqlite', 'json', 'mysql'], true)) {
        $storageMode = $defaultStorageMode;
    }

    if ($adminEmail === false) {
        Show::msg(Lang::get('users.bad-mail') ?? 'Invalid email address.', 'error');
        header('location:' . $url);
        die();
    }
    if (strlen($adminPwdPlain) < 8) {
        Show::msg(Lang::get('users.registration-password-length') ?? 'Password is too short.', 'error');
        header('location:' . $url);
        die();
    }
    if ($adminUsername === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,60}$/', $adminUsername)) {
        Show::msg(Lang::get('users.registration-username-exists') ?? 'Invalid username.', 'error');
        header('location:' . $url);
        die();
    }

    if ($storageMode === 'sqlite' && !$hasSqlitePdo) {
        Show::msg(Lang::get('install-driver-unavailable') ?: 'SQLite PDO driver unavailable.', 'error');
        header('location:' . $url);
        die();
    }

    $mysqlHost = trim($_POST['mysql_host'] ?? 'localhost');
    $mysqlPort = max(1, (int) ($_POST['mysql_port'] ?? 3306));
    $mysqlDbname = trim($_POST['mysql_dbname'] ?? '');
    $mysqlUser = trim($_POST['mysql_user'] ?? '');
    $mysqlPassword = (string) ($_POST['mysql_password'] ?? '');

    if ($storageMode === 'mysql') {
        if (!$hasMysqlPdo) {
            Show::msg(Lang::get('install-driver-unavailable') ?: 'MySQL PDO driver unavailable.', 'error');
            header('location:' . $url);
            die();
        }
        if ($mysqlHost === '' || $mysqlDbname === '' || $mysqlUser === '') {
            Show::msg(Lang::get('install-mysql-fields-required') ?: 'Please fill all required MySQL fields.', 'error');
            header('location:' . $url);
            die();
        }
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $mysqlHost, $mysqlPort, $mysqlDbname);
            new PDO($dsn, $mysqlUser, $mysqlPassword, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);
        } catch (Throwable $e) {
            $errTpl = Lang::get('install-mysql-connection-error') ?: 'Unable to connect to MySQL: %s';
            Show::msg(sprintf($errTpl, htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')), 'error');
            header('location:' . $url);
            die();
        }
    }

    if ($storageMode === 'mysql') {
        $dbConfig = [
            'database' => [
                'driver' => 'mysql',
                'host' => $mysqlHost,
                'port' => $mysqlPort,
                'dbname' => $mysqlDbname,
                'user' => $mysqlUser,
                'password' => $mysqlPassword,
                'charset' => 'utf8mb4',
            ],
            'storage' => [
                'default' => 'json',
                'User' => 'sql',
                'PageItem' => 'sql',
                'MenuItem' => 'sql',
            ],
        ];
    } elseif ($storageMode === 'sqlite') {
        $dbConfig = [
            'database' => [
                'driver' => 'sqlite',
                'path' => __DIR__ . DS . 'data' . DS . 'database.sqlite',
            ],
            'storage' => [
                'default' => 'json',
                'User' => 'sql',
                'PageItem' => 'sql',
                'MenuItem' => 'sql',
            ],
        ];
    } else {
        $dbConfig = [
            'database' => [
                'driver' => 'sqlite',
                'path' => __DIR__ . DS . 'data' . DS . 'database.sqlite',
            ],
            'storage' => [
                'default' => 'json',
                'User' => 'json',
                'PageItem' => 'json',
                'MenuItem' => 'json',
            ],
        ];
    }

	if ($core->install()) {
        if (file_put_contents(DATA . 'env.json', json_encode($dbConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false) {
            $core->log('Unable to write env.json file', 'ERROR');
        } else {
            \Core\Database::getInstance()->reloadConfig();
        }
        $pagesDirPre = DATA_CORE_PAGE;
        if (!is_dir($pagesDirPre)) {
            @mkdir($pagesDirPre, 0755, true);
        }
        $pagesFilePre = $pagesDirPre . 'pages.json';
        if (!file_exists($pagesFilePre) || count(json_decode((string) file_get_contents($pagesFilePre), true) ?: []) === 0) {
            $homepageSeedPre = [[
                'id' => 1,
                'name' => 'Accueil',
                'position' => 1,
                'isHomepage' => '1',
                'content' => "<p>Félicitations, l'installation de 299koEVO s'est déroulée avec succès !<br>Par mesure de sécurité, vous devez maintenant supprimer le fichier install.php du répertoire d'installation.</p>",
                'isHidden' => 0,
                'file' => null,
                'mainTitle' => null,
                'metaDescriptionTag' => null,
                'metaTitleTag' => null,
                'targetAttr' => null,
                'target' => null,
                'noIndex' => null,
                'parent' => null,
                'cssClass' => null,
                'password' => null,
                'img' => null,
            ]];
            file_put_contents($pagesFilePre, json_encode($homepageSeedPre, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if ($storageMode === 'sqlite' || $storageMode === 'mysql') {
                \Core\Storage\StorageMigrationService::migrate('PageItem', 'sql', 'json');
            }
        }
		$plugins = $pluginsManager->getPlugins();
		if ($plugins != false) {
			foreach ($plugins as $plugin) {
				if ($plugin->getLibFile()) {
					include_once($plugin->getLibFile());
					$plugin->loadLangFile();
					if (!$plugin->isInstalled())
						$pluginsManager->installPlugin($plugin->getName(), true);
					$plugin->setConfigVal('activate', '1');
					$pluginsManager->savePluginConfig($plugin);
				}
			}
		}
	}
	include(DATA . 'key.php');
    $adminPwd = hash_hmac('sha1', $adminPwdPlain, KEY);
    $config = [
        'siteName' => "SiteName",
        'siteDesc' => "Description",
        'siteUrl' => $core->makeSiteUrl(),
        'theme' => 'default',
        'hideTitles' => false,
        'defaultPlugin' => 'page',
        'debug' => false,
        'defaultAdminPlugin' => 'configmanager',
        'siteLang' => $_POST['lang-select'],
        'cache_enabled' => false,
        'cache_duration' => 3600,
        'cache_minify' => false,
        'cache_lazy_loading' => false,
        'marketplaceUrl' => 'https://299ko-api.flexcb.fr/',
        'versionCMS' => VERSION,
        'allowRegistrations' => false,
        'registrationDefaultGroup' => 'member',
        'registrationValidationMode' => 'email',
    ];
    $pagesSeed = [
        [
            'id' => 1,
            'name' => 'Accueil',
            'position' => 1,
            'isHomepage' => '1',
            'content' => "<p>Félicitations, l'installation de 299koEVO s'est déroulée avec succès !<br>Par mesure de sécurité, vous devez maintenant supprimer le fichier install.php du répertoire d'installation.</p>",
            'isHidden' => 0,
            'file' => null,
            'mainTitle' => null,
            'metaDescriptionTag' => null,
            'metaTitleTag' => null,
            'targetAttr' => null,
            'target' => null,
            'noIndex' => null,
            'parent' => null,
            'cssClass' => null,
            'password' => null,
            'img' => null,
        ],
    ];

    $pagesDir = DATA_CORE_PAGE;
    if (!is_dir($pagesDir) && !mkdir($pagesDir, 0755, true)) {
        $core->log('Unable to create default pages directory', 'ERROR');
        Show::msg(Lang::get('install-problem-during-install'), 'error');
        header('location:' . $core->makeSiteUrl());
        die();
    }

    $pluginsDir = PLUGINS;
    if (!is_dir($pluginsDir) && !mkdir($pluginsDir, 0755, true)) {
        $core->log('Unable to create plugins directory', 'ERROR');
        Show::msg(Lang::get('install-problem-during-install'), 'error');
        header('location:' . $core->makeSiteUrl());
        die();
    }

    $pagesFile = $pagesDir . 'pages.json';
    $existingPages = [];
    if (file_exists($pagesFile)) {
        $existingPages = json_decode((string) file_get_contents($pagesFile), true);
        if (!is_array($existingPages)) {
            $existingPages = [];
        }
    }
    $hasHomepage = false;
    foreach ($existingPages as $p) {
        if (!empty($p['isHomepage']) && (string) $p['isHomepage'] !== '0') {
            $hasHomepage = true;
            break;
        }
    }
    if (!$hasHomepage) {
        $existingPages = array_merge($pagesSeed, $existingPages);
        file_put_contents($pagesFile, json_encode($existingPages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    $authDir = DATA_CORE_AUTH;
    if (!is_dir($authDir) && !mkdir($authDir, 0755, true)) {
        $core->log('Unable to create auth directory', 'ERROR');
        Show::msg(Lang::get('install-problem-during-install'), 'error');
        header('location:' . $core->makeSiteUrl());
        die();
    }

    $usersFile = $authDir . 'users.json';
    $groupsFile = $authDir . 'groups.json';
    $registrationsFile = $authDir . 'registrations.json';

    if (!file_exists($groupsFile)) {
        $defaultGroups = [
            [
                'id' => 1,
                'slug' => 'admin',
                'name' => 'Administrators',
                'permissions' => [Permissions::ALL],
                'system' => true,
            ],
            [
                'id' => 2,
                'slug' => 'moderator',
                'name' => 'Moderators',
                'permissions' => ['admin.access', 'pages.manage', 'media.manage'],
                'system' => true,
            ],
            [
                'id' => 3,
                'slug' => 'member',
                'name' => 'Members',
                'permissions' => ['profile.view', 'profile.edit'],
                'system' => true,
            ],
        ];
        if (file_put_contents($groupsFile, json_encode($defaultGroups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            $core->log('Unable to write default groups file', 'ERROR');
            Show::msg(Lang::get('install-problem-during-install'), 'error');
            header('location:' . $core->makeSiteUrl());
            die();
        }
    }

    if (!file_exists($registrationsFile)) {
        if (file_put_contents($registrationsFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            $core->log('Unable to write registrations file', 'ERROR');
            Show::msg(Lang::get('install-problem-during-install'), 'error');
            header('location:' . $core->makeSiteUrl());
            die();
        }
    }

    $adminUser = [
        'id' => 1,
        'email' => $adminEmail,
        'username' => $adminUsername,
        'pwd' => $adminPwd,
        'token' => bin2hex(random_bytes(32)),
        'group_id' => 1,
        'role' => 'admin',
        'status' => 'active',
        'permissions' => [Permissions::ALL],
    ];

    if (file_put_contents($usersFile, json_encode([$adminUser], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        $core->log('Unable to write users file', 'ERROR');
        Show::msg(Lang::get('install-problem-during-install'), 'error');
        header('location:' . $core->makeSiteUrl());
        die();
    }

    if ($storageMode === 'sqlite' || $storageMode === 'mysql') {
        $pageMig = \Core\Storage\StorageMigrationService::migrate('PageItem', 'sql', 'json');
        $userMig = \Core\Storage\StorageMigrationService::migrate('User', 'sql', 'json');
        \Core\Navigation\MenuItem::queryBuilder()->get();
        if (empty($pageMig['success']) || empty($userMig['success'])) {
            $errDetails = array_merge($pageMig['errors'] ?? [], $userMig['errors'] ?? []);
            $core->log('SQL seed error during install: ' . implode('; ', $errDetails), 'ERROR');
            Show::msg(Lang::get('install-problem-during-install'), 'error');
            header('location:' . $url);
            die();
        }
    }

    $configEncoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (!file_put_contents(DATA . 'config.json', $configEncoded) || !chmod(DATA . 'config.json', 0600)) {
        $core->log('Error while writing config file', 'ERROR');
        Show::msg(Lang::get('install-problem-during-install'), 'error');
        header('location:' . $core->makeSiteUrl() );
        die();
    } else {
        // Générer l'installation_id et envoyer les données minimales (même en mode 0)
        try {
            $telemetryService = new \Core\Telemetry\TelemetryService();
            $telemetryService->getInstallationId(); // Génère l'UUID si nécessaire
            // Envoyer les données minimales lors de l'installation (même si mode 0)
            $telemetryService->send(0, true);
        } catch (\Exception $e) {
            // Ne pas bloquer l'installation en cas d'erreur de télémétrie
            $core->log('Telemetry: Failed to send installation data - ' . $e->getMessage(), 'WARNING');
        }

        $_SESSION['installOk'] = true;
        $core->log('Plugins installation done', 'SUCCESS');
        $core->log('Admin user created, end of install', 'SUCCESS');
        Show::msg(Lang::get('install-successfull'), 'success');
        header('location:' . $core->makeSiteUrl() );
        die();
    }
}
?>

<!doctype html>
<html lang="<?php echo Lang::getLocale() ?>">
    <head>
        <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>299ko - <?php echo Lang::get('install-installation'); ?></title>
        <link rel="stylesheet" href="admin/install.css" media="all">
        <link rel="stylesheet" href="<?php echo FONTICON; ?>" />
    </head>

    <body class="install install-body">
        <div id="alert-msg">
            <?php Show::displayMsg(); ?>
        </div>
        <section id="install">
            <header>
                <h1 class="text-center">299koEVO <?php echo Lang::get('install-installation'); ?></h1>
            </header>
                <div class="msg-container">
            <?php
            if ($errorPHP) {
                echo '<div class="msg error">';
                echo Lang::get('install-php-version-error', (float) substr(phpversion(), 0, 3), $minPHPVersion);
                echo '</div>';
            } else {
                echo '<div class="msg success">';
                echo Lang::get('install-php-version-ok', $minPHPVersion);
                echo '</div>';
            }

            if ($errorRewrite === 'CGI') {
                echo '<div class="msg warning">';
                echo Lang::get('install-php-rewrite-cgi');
                echo '</div>';
            } elseif ($errorRewrite) {
                echo '<div class="msg error">';
                echo Lang::get('install-php-rewrite-error');
                echo '</div>';
            } else {
                echo '<div class="msg success">';
                echo Lang::get('install-php-rewrite-ok');
                echo '</div>';
            }

            if ($errorDataWrite) {
                echo '<div class="msg error">';
                echo Lang::get('install-php-data-write-error');
                echo '</div>';
            } else {
                echo '<div class="msg success">';
                echo Lang::get('install-php-data-write-ok');
                echo '</div>';
            }

            if ($errorFopen) {
                echo '<div class="msg warning">';
                echo Lang::get('install-php-fopen-error');
                echo '</div>';
            } else {
                echo '<div class="msg success">';
                echo Lang::get('install-php-fopen-ok');
                echo '</div>';
            }

            if ($errorCurl) {
                echo '<div class="msg warning">';
                echo Lang::get('install-php-curl-error');
                echo '</div>';
            } else {
                echo '<div class="msg success">';
                echo Lang::get('install-php-curl-ok');
                echo '</div>';
            }

            if ($errorZip) {
                echo '<div class="msg warning">';
                echo Lang::get('install-php-zip-error');
                echo '</div>';
            } else {
                echo '<div class="msg success">';
                echo Lang::get('install-php-zip-ok');
                echo '</div>';
            }

            if ($errorDataWrite || $errorPHP || $errorRewrite === true) {
                echo '<div class="msg error">';
                echo Lang::get('install-please-check-errors');
                echo '</div>';
            } else {
                ?>
                </div>
                <form method="post" action="">
                    <?php echo '<h3>'.Lang::get('install-please-fill-fields').'</h3>';
                    ?>
                    <p><label for="lang-select"><?php echo Lang::get('install-lang-choice'); ?></label>
                    <select name="lang-select" id="lang-select" onchange="langChange()">
                        <?php
                        foreach (Lang::$availablesLocales as $k => $v) {
                            if (Lang::getLocale() === $k) {
                                echo '<option value="' . $k . '" selected>' . $v . '</option>';
                            } else {
                                echo '<option value="' . $k . '">' . $v . '</option>';
                            }
                        }
                        ?>
                    </select>
                    </p>
                    <p>
                        <label for="adminUsername"><?php echo Lang::get('users-username'); ?></label><br>
                        <input type="text" name="adminUsername" id="adminUsername" required="required" minlength="3" maxlength="60" pattern="[A-Za-z0-9_.\-]+">
                    </p>
                    <p>
                        <label for="adminEmail"><?php echo Lang::get('email'); ?></label><br>
                        <input type="email" name="adminEmail" required="required">
                    </p>
                    <p>
                        <label for="adminPwd"><?php echo Lang::get('password'); ?></label><br>
                        <input type="password" name="adminPwd" id="adminPwd" required="required">
                    </p>
                    <div style="margin: 18px 0; padding: 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; text-align: left;">
                        <label for="storage_mode" style="font-weight: 700; display: block; margin-bottom: 8px;"><?php echo Lang::get('install-storage-mode'); ?></label>
                        <select name="storage_mode" id="storage_mode" onchange="toggleStorageFields()" style="width: 100%; padding: 8px; margin-bottom: 8px;">
                            <option value="sqlite" <?php echo $hasSqlitePdo ? 'selected' : 'disabled'; ?>>
                                <?php echo Lang::get('install-storage-sqlite') . ($hasSqlitePdo ? '' : ' (' . Lang::get('install-driver-unavailable') . ')'); ?>
                            </option>
                            <option value="json" <?php echo !$hasSqlitePdo ? 'selected' : ''; ?>>
                                <?php echo Lang::get('install-storage-json'); ?>
                            </option>
                            <option value="mysql" <?php echo $hasMysqlPdo ? '' : 'disabled'; ?>>
                                <?php echo Lang::get('install-storage-mysql') . ($hasMysqlPdo ? '' : ' (' . Lang::get('install-driver-unavailable') . ')'); ?>
                            </option>
                        </select>
                        <small id="storageModeDesc" style="color: #475569; display: block; line-height: 1.4;"></small>
                        <div id="mysqlFieldsBox" style="display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 8px 0;">
                                <label for="mysql_host"><?php echo Lang::get('install-mysql-host'); ?></label><br>
                                <input type="text" name="mysql_host" id="mysql_host" value="localhost" placeholder="localhost">
                            </p>
                            <p style="margin: 8px 0;">
                                <label for="mysql_port"><?php echo Lang::get('install-mysql-port'); ?></label><br>
                                <input type="number" name="mysql_port" id="mysql_port" value="3306" min="1" max="65535">
                            </p>
                            <p style="margin: 8px 0;">
                                <label for="mysql_dbname"><?php echo Lang::get('install-mysql-dbname'); ?></label><br>
                                <input type="text" name="mysql_dbname" id="mysql_dbname" value="" placeholder="ex: declic_cms">
                            </p>
                            <p style="margin: 8px 0;">
                                <label for="mysql_user"><?php echo Lang::get('install-mysql-user'); ?></label><br>
                                <input type="text" name="mysql_user" id="mysql_user" value="" placeholder="ex: cms_user">
                            </p>
                            <p style="margin: 8px 0;">
                                <label for="mysql_password"><?php echo Lang::get('install-mysql-password'); ?></label><br>
                                <input type="password" name="mysql_password" id="mysql_password" value="">
                            </p>
                        </div>
                    </div>
                    <input type="hidden" name="_installToken" value="<?php echo htmlspecialchars($installToken, ENT_QUOTES); ?>">
                    <p>
                        <a id="showPassword" href="javascript:showPassword()" class="button success"><?php echo Lang::get('install-show-password'); ?></a>
                        <button type="submit" class="button success"><?php echo Lang::get('submit'); ?></button>
                    </p>
                    </form>
                    <footer><a target="_blank" href="https://github.com/299ko/"><?php echo Lang::get('site-just-using', VERSION); ?></a>
                    </footer>

                <?php
            }
            ?>
        </section>
        <script type="text/javascript">
            function showPassword() {
                document.getElementById("adminPwd").setAttribute("type", "text");
                document.getElementById("showPassword").style.display = 'none';
            }
            function langChange() {
                window.location.href = '<?php echo $url; ?>?lang=' + document.getElementById("lang-select").value;
            }
            function toggleStorageFields() {
                var sel = document.getElementById("storage_mode");
                var box = document.getElementById("mysqlFieldsBox");
                var desc = document.getElementById("storageModeDesc");
                if (!sel || !box) return;
                var descs = {
                    sqlite: <?php echo json_encode(Lang::get('install-storage-sqlite-desc')); ?>,
                    json: <?php echo json_encode(Lang::get('install-storage-json-desc')); ?>,
                    mysql: <?php echo json_encode(Lang::get('install-storage-mysql-desc')); ?>
                };
                if (desc) desc.textContent = descs[sel.value] || '';
                var isMysql = (sel.value === 'mysql');
                box.style.display = isMysql ? 'block' : 'none';
                ['mysql_host', 'mysql_dbname', 'mysql_user'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) {
                        if (isMysql) el.setAttribute('required', 'required');
                        else el.removeAttribute('required');
                    }
                });
            }
            document.addEventListener("DOMContentLoaded", toggleStorageFields);
        </script>
    </body>
</html>