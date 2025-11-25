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

use Core\Auth\User;
use Core\Auth\UsersManager;
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

if (count($_POST) > 0) {
	if ($core->install()) {
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
    $adminPwd = UsersManager::encrypt($_POST['adminPwd']);
    $adminEmail = $_POST['adminEmail'];
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
    ];
    $pagesSeed = [
        [
            'id' => 1,
            'name' => 'Accueil',
            'position' => 1,
            'isHomepage' => '1',
            'content' => "<p>Félicitations, l'installation de 299ko s'est déroulée avec succès !<br>Par mesure de sécurité, vous devez maintenant supprimer le fichier install.php du répertoire d'installation.</p>",
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
    if (!file_exists($pagesFile) || count($existingPages) === 0) {
        file_put_contents($pagesFile, json_encode($pagesSeed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    if (!file_put_contents(DATA . 'config.json', json_encode($config)) || !chmod(DATA . 'config.json', 0600)) {
        $core->log('Error while writing config file', 'ERROR');
        Show::msg(Lang::get('install-problem-during-install'), 'error');
        header('location:' . $core->makeSiteUrl() );
        die();
    } else {
        $_SESSION['installOk'] = true;
        $core->log('Plugins installation done', 'SUCCESS');
        $user = new User();
        $user->email = $adminEmail;
        $user->pwd = $adminPwd;
        $user->save();
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
        <link rel="stylesheet" href="admin/styles.css" media="all">
        <link rel="stylesheet" href="<?php echo FONTICON; ?>" />
    </head>

    <body class="login">
        <div id="alert-msg">
            <?php Show::displayMsg(); ?>
        </div>
        <section id="install">
            <header>
                <h1 class="text-center"><?php echo Lang::get('install-installation'); ?></h1>
            </header>
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
                echo Lang::get('install-please-check-errors');
            } else {
                ?>
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
                        <label for="adminEmail"><?php echo Lang::get('email'); ?></label><br>
                        <input type="email" name="adminEmail" required="required">
                    </p>
                    <p>
                        <label for="adminPwd"><?php echo Lang::get('password'); ?></label><br>
                        <input type="password" name="adminPwd" id="adminPwd" required="required">
                    </p>
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
        </script>
    </body>
</html>