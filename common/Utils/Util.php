<?php

namespace Utils;

use Core\Core;
use Core\Lang;

/**
 * @copyright (C) 2025, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class Util
{
    ## Tri un tableau à 2 dimenssions
    ## $data => tableau
    ## $key => index du tableau sur lequel doit se faire le tri
    ## $mode => type de tri ('desc', 'asc', 'num')

    public static function sort2DimArray($data, $key, $mode)
    {
        if ($mode == 'desc')
            $mode = SORT_DESC;
        elseif ($mode == 'asc')
            $mode = SORT_ASC;
        elseif ($mode == 'num')
            $mode = SORT_NUMERIC;
        $temp = array();
        foreach ($data as $k => $v) {
            $temp[$k] = $v[$key] ?? 0;
        }
        array_multisort($temp, $mode, $data);
        return $data;
    }

    /**
     * Truncate an HTML content and keep only the <p> and <br> tags
     * It save the new lines and dont cut on a word
     * 
     * @param  string $str      Content to truncate
     * @param  int    $length   Number of characters to keep
     * @param  string $add      Text to add after the content if truncated
     * @return string
     */
    public static function cutStr($str, $length, $add = '...')
    {
        // Handle double-encoded tags (&amp;lt;p&amp;gt;) and encoded tags (&lt;p&gt;)
        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $str = str_replace("<br />", "<br>", $str);
        $no_tags_content = strip_tags($str);
        $no_tags_content = str_replace("&nbsp;", " ", $no_tags_content);
        
        if (mb_strlen($no_tags_content) > $length) {
            $cut_pos = mb_strpos($no_tags_content, ' ', $length);
            if ($cut_pos === false) $cut_pos = $length;
            return mb_substr($no_tags_content, 0, $cut_pos) . $add;
        } else {
            return $no_tags_content;
        }
    }


    /**
     * Formate a string to an url
     * @param mixed $str
     * @return string Formatted string
     */
    public static function strToUrl($str)
    {
        $str = str_replace('&', 'et', $str);
        if ($str !== mb_convert_encoding(mb_convert_encoding($str, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32'))
            $str = mb_convert_encoding($str, 'UTF-8');
        $str = htmlentities($str, ENT_NOQUOTES, 'UTF-8');
        $str = preg_replace('`&([a-z]{1,2})(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig);`i', '$1', $str);
        $str = preg_replace(array('`[^a-z0-9]`i', '`[-]+`'), '-', $str);
        return strtolower(trim($str, '-'));
    }

    ## Vérifie si la chaîne est un email valide

    public static function isEmail($email)
    {
        return (filter_var($email, FILTER_VALIDATE_EMAIL) === false) ? false : true;
    }

    /**
     * Masque une adresse email pour l'affichage public (GDPR)
     * ex: j***@gmail.com
     */
    public static function maskEmail(?string $email): string
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1];

        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '***';
        } else {
            $visibleLen = max(1, floor($len / 4)); // Montrer 1/4 du début
            $maskedName = substr($name, 0, $visibleLen) . str_repeat('*', 3);
        }

        return $maskedName . '@' . $domain;
    }

    ## Envoie un email

    public static function sendEmail($from, $reply, $to, $subject, $msg)
    {
        $headers = "From: " . $from . "\r\n";
        $headers .= "Reply-To: " . $reply . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= 'Content-Type: text/plain; charset="utf-8"' . "\r\n";
        $headers .= 'Content-Transfer-Encoding: 8bit';
        if (mail($to, $subject, $msg, $headers)) {
            return true;
        }
        Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, 'Error when sending mail');
        return false;
    }

    ## Retourne l'extension d'un fichier présent sur le serveur

    public static function getFileExtension($file)
    {
        return substr(strtolower(strrchr(basename($file), ".")), 1);
    }

    /**
     * List a directory and return an array with files and folders (separated). This function is not recursive
     * 
     * @param string $folder Path to scan
     * @param array $not Array of files to exclude
     * @return array $data['dir] : Directories / $data['file'] : Files
     */
    public static function scanDir(string $folder, array $not = [])
    {
        $data['dir'] = [];
        $data['file'] = [];
        $folder = rtrim($folder, '/') . '/';
        if (!is_dir($folder)) {
            return $data;
        }
        $files = scandir($folder);
        if ($files === false) {
            return $data;
        }
        foreach ($files as $file) {
            if ($file[0] != '.' && !in_array($file, $not)) {
                if (is_file($folder . $file))
                    $data['file'][] = $file;
                elseif (is_dir($folder . $file))
                    $data['dir'][] = $file;
            }
        }
        return $data;
    }

    /**
     * List a directory and its content recursively and return an array with files and folders (separated)
     * 
     * @param string $dir Path to scan
     * @return array $data['dir] : Directories / $data['file'] : Files
     */
    public static function scanDirRecursive(string $dir): array
    {
        $data['dir'] = [];
        $data['file'] = [];
        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $name => $object) {
            if ($object->isDir()) {
                $data['dir'][] = $name;
            } else {
                $data['file'][] = $name;
            }
        }
        return $data;
    }

    /**
     * Deletes a directory and all its content recursively
     * @param string $dir The directory to delete
     * @return bool True if the directory was deleted successfully, false otherwise
     */
    public static function delTree(string $dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir($dir . DS . $file)) ? static::delTree($dir . DS . $file) : unlink($dir . DS . $file);
        }
        return rmdir($dir);
    }

    /**
     * Write a json file with the given data. If the file does not exist, it will be created.
     * If the file already exists, its content will be overwritten.
     * @param string $file The path to the json file
     * @param array|object $data The data to write in the json file
     * @return bool True if the file was written successfully, false otherwise
     */
    public static function writeJsonFile($file, $data)
    {
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            $testMode->setVolatileJson($file, $data);
            return true;
        }

        if (@file_put_contents($file, json_encode($data), LOCK_EX))
            return true;
        return false;
    }

    /**
     * Reads a json file and returns its content as an associative array or an object.
     * If the file does not exist, return false.
     * @param string $file The path to the json file
     * @param bool $assoc If true, the function will return an associative array, else an object
     * @return array|false The content of the json file or false if the file does not exist
     */
    public static function readJsonFile($file, $assoc = true)
    {
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            $volatile = $testMode->getVolatileJson($file);
            if ($volatile !== null) {
                return $volatile;
            }
        }

        if (!file_exists($file)) {
            return false;
        }
        return json_decode(@file_get_contents($file), $assoc);
    }

    ## Upload

    public static function uploadFile($k, $dir, $name, $validations = array())
    {
        if (isset($_FILES[$k]) && $_FILES[$k]['name'] != '') {
            $extension = mb_strtolower(Util::getFileExtension($_FILES[$k]['name']));

            // 1. Validation de l'extension
            if (isset($validations['extensions']) && !in_array($extension, $validations['extensions'])) {
                return 'extension error';
            }

            // 2. Validation de la taille
            $size = filesize($_FILES[$k]['tmp_name']);
            if (isset($validations['size']) && $size > $validations['size']) {
                return 'size error';
            }

            // 3. Validation du type MIME (Nouveau)
            // Mapping basique des extensions courantes vers leurs types MIME
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'pdf' => 'application/pdf',
                'webp' => 'image/webp',
            ];

            // Si l'extension est connue dans notre mapping, on force la vérification
            if (array_key_exists($extension, $mimeTypes)) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $dectectedMime = $finfo->file($_FILES[$k]['tmp_name']);

                // Si le mime-type détecté ne correspond pas à l'extension
                if ($dectectedMime !== $mimeTypes[$extension]) {
                    // Tolérance pour jpg/jpeg qui peuvent être l'un ou l'autre parfois
                    if (!($dectectedMime === 'image/jpeg' && ($extension === 'jpg' || $extension === 'jpeg'))) {
                        return 'extension error'; // On retourne "extension error" pour rester compatible avec les messages d'erreur existants
                    }
                }
            }

            // 4. Déplacement sécurisé
            // Utiliser l'extension fournie par le système (validée)
            if (move_uploaded_file($_FILES[$k]['tmp_name'], $dir . $name . '.' . $extension)) {
                return 'success';
            } else {
                return 'upload error';
            }
        }
        return 'undefined';
    }

    /**
     * Formate une date avec un format spécifique
     * 
     * @param mixed $date Date (string ou timestamp)
     * @param string $format Format DateTime
     * @return string
     */
    public static function getFormattedDate($date, $format): string
    {
        if (empty($date)) return '';
        try {
            if (is_int($date) || is_numeric($date) && (string)(int)$date === (string)$date) {
                $dateObj = new \DateTime();
                $dateObj->setTimestamp((int)$date);
            } else {
                $dateObj = new \DateTime($date);
            }
            return $dateObj->format($format);
        } catch (\Exception $e) {
            return $date;
        }
    }

    ## Formate une date

    public static function formatDate($date, $langFrom = 'en', $langTo = 'en')
    {
        $date = substr($date, 0, 10);
        $temp = preg_split('#[-_;\. \/]#', $date);
        if ($langFrom == 'en') {
            $year = $temp[0];
            $month = $temp[1];
            $day = $temp[2];
        } elseif ($langFrom == 'fr') {
            $year = $temp[2];
            $month = $temp[1];
            $day = $temp[0];
        }
        if ($langTo == 'en')
            $data = $year . '-' . $month . '-' . $day;
        elseif ($langTo == 'fr')
            $data = $day . '/' . $month . '/' . $year;
        return $data;
    }

    public static function getTimestampFromDate($date): int
    {
        if (is_int($date)) {
            // Date from timestamp
            $dateObj = new \DateTime();
            $dateObj->setTimestamp($date);
        } else {
            // Date from string, old version
            $dateObj = new \DateTime($date);
        }
        return $dateObj->getTimestamp();
    }

    public static function getDateHour($date)
    {
        if (is_int($date)) {
            // Date from timestamp
            $dateObj = new \DateTime();
            $dateObj->setTimestamp($date);
        } else {
            // Date from string, old version
            $dateObj = new \DateTime($date);
        }
        return $dateObj->format(\Core\Lang::get("date-hour-format"));
    }

    public static function getDate($date): string
    {
        if (is_int($date)) {
            // Date from timestamp
            $dateObj = new \DateTime();
            $dateObj->setTimestamp($date);
        } else {
            // Date from string, old version
            $dateObj = new \DateTime($date);
        }
        return $dateObj->format(\Core\Lang::get("date-only"));
    }

    public static function getNaturalDate($date)
    {
        if (empty($date)) {
            return '';
        }
        if (is_int($date)) {
            // Date from timestamp
            $dateObj = new \DateTime();
            $dateObj->setTimestamp($date);
        } else {
            // Date from string, old version
            $dateObj = new \DateTime($date);
        }
        if (class_exists('IntlCalendar')) {
            $cal = \IntlCalendar::fromDateTime($dateObj->format('Y-m-d H:i:s'));
            return \IntlDateFormatter::formatObject($cal, \Core\Lang::get("date-natural-date-hour-format"), \Core\Lang::get('locale'));
        }

        // Fallback if intl extension is missing
        $now = new \DateTime();
        $diff = $now->diff($dateObj);

        // Very basic natural language fallback
        // Ideally this should use translations but for fallback we just want to avoid crash
        if ($diff->y > 0) {
            return $dateObj->format('d/m/Y H:i');
        } elseif ($diff->m > 0) {
            return $dateObj->format('d/m/Y H:i');
        } elseif ($diff->d > 0) {
            if ($diff->d == 1) {
                return 'Hier à ' . $dateObj->format('H:i');
            }
            return $dateObj->format('d/m/Y H:i');
        } elseif ($diff->h > 0) {
            return 'Il y a ' . $diff->h . ' h';
        } elseif ($diff->i > 0) {
            return 'Il y a ' . $diff->i . ' min';
        } else {
            return 'A l\'instant';
        }
    }

    /**
     * Build absolute URL with siteURL saved in config.json
     * 
     * @param  string URI
     * @param  bool   is Admin location
     * @return string URL
     */
    public static function urlBuild($uri, $admin = false)
    {
        $uri = str_replace('\\', '/', $uri);
        $root = str_replace('\\', '/', ROOT);
        if (strpos($uri, $root) === 0) {
            $uri = substr($uri, strlen($root));
        }
        if (isset(parse_url($uri)['host'])) {
            // Absolute URL
            return $uri;
        }
        $base = Core::getInstance()->getConfigVal('siteUrl') . '/';
        if ($admin) {
            $base .= 'admin/';
        }
        $url = $base . ltrim($uri, '/');
        return str_replace('/./', '/', $url);
    }

    /**
     * Return current page URL
     * 
     * @return string
     */
    public static function getCurrentURL()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $url = "https";
        } else {
            $url = "http";
        }
        $url .= "://";
        $url .= $_SERVER['HTTP_HOST'];
        $url .= $_SERVER['REQUEST_URI'];
        return $url;
    }

    /**
     * Format HTML for add missing id to <h1-6> Title, ready for anchors
     * @param string $htmlContent
     * @return string
     */
    public static function generateIdForTitle($htmlContent): string
    {
        $htmlContent = self::normalizeHeadingStructure($htmlContent);
        return preg_replace_callback(
            '#<h([1-6])([^>]*)>(.*?)</h\1>#is',
            function ($matches) {
                $level = $matches[1];
                $attributes = $matches[2];
                $text = $matches[3];

                if (stripos($attributes, 'id=') !== false) {
                    return '<h' . $level . $attributes . '>' . $text . '</h' . $level . '>';
                }

                $id = self::strToUrl(strip_tags($text));
                $attributes = trim($attributes);
                if ($attributes !== '') {
                    $attributes = ' ' . $attributes;
                }
                return '<h' . $level . $attributes . ' id="' . $id . '">' . $text . '</h' . $level . '>';
            },
            $htmlContent
        );
    }

    private static function normalizeHeadingStructure(string $htmlContent): string
    {
        if (trim($htmlContent) === '') {
            return $htmlContent;
        }

        if (!class_exists(\DOMDocument::class)) {
            return $htmlContent;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $wrapperId = '__toc_wrapper__';
        $wrappedHtml = '<?xml encoding="utf-8" ?><div id="' . $wrapperId . '">' . $htmlContent . '</div>';

        $previousLibxml = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxml);

        if (!$loaded) {
            return $htmlContent;
        }

        $paragraphs = [];
        foreach ($document->getElementsByTagName('p') as $paragraph) {
            $paragraphs[] = $paragraph;
        }

        foreach ($paragraphs as $paragraph) {
            $firstChild = self::findFirstMeaningfulNode($paragraph);
            if (!$firstChild) {
                continue;
            }
            $nodeName = strtolower($firstChild->nodeName);
            if (!in_array($nodeName, ['strong', 'b'], true)) {
                continue;
            }
            $headingText = trim($firstChild->textContent);
            if ($headingText === '') {
                continue;
            }

            $heading = $document->createElement('h2');
            $heading->appendChild($document->createTextNode($headingText));
            if ($paragraph->parentNode) {
                $paragraph->parentNode->insertBefore($heading, $paragraph);
            }

            $paragraph->removeChild($firstChild);
            self::trimLeadingInlineNodes($paragraph);

            if (!trim($paragraph->textContent ?? '')) {
                if ($paragraph->parentNode) {
                    $paragraph->parentNode->removeChild($paragraph);
                }
            }
        }

        $wrapper = $document->getElementById($wrapperId);
        if (!$wrapper) {
            return $htmlContent;
        }

        $normalized = '';
        foreach ($wrapper->childNodes as $child) {
            $normalized .= $document->saveHTML($child);
        }

        return $normalized;
    }

    private static function findFirstMeaningfulNode(\DOMNode $node): ?\DOMNode
    {
        $child = $node->firstChild;
        while ($child) {
            if ($child->nodeType === XML_TEXT_NODE && trim($child->textContent) === '') {
                $child = $child->nextSibling;
                continue;
            }
            if ($child->nodeType === XML_COMMENT_NODE) {
                $child = $child->nextSibling;
                continue;
            }
            return $child;
        }

        return null;
    }

    private static function trimLeadingInlineNodes(\DOMNode $node): void
    {
        while ($node->firstChild) {
            $first = $node->firstChild;
            if ($first->nodeType === XML_TEXT_NODE && trim($first->textContent) === '') {
                $node->removeChild($first);
                continue;
            }

            if ($first->nodeType === XML_ELEMENT_NODE && strtolower($first->nodeName) === 'br') {
                $node->removeChild($first);
                continue;
            }

            break;
        }
    }

    /**
     * Generate Table Of Content ready to display in the Content
     * @param string $htmlContent
     * @param string $title
     * @return string
     */
    public static function generateTableOfContents($htmlContent, $title): string
    {
        $inner = self::generateInnerTOC($htmlContent);
        if (!$inner) {
            return false;
        }
        $toc = '<details class="toc-container">';
        $toc .= '<summary><header><h4>' . $title . '</h4></header></summary>';
        $toc .= $inner;
        $toc .= '</details>';
        return $toc;
    }

    /**
     * Generate Table Of Content ready to display in the Sidebar
     * @param mixed $htmlContent
     * @return string
     */
    public static function generateTableOfContentAsModule($htmlContent): string
    {
        $inner = self::generateInnerTOC($htmlContent);
        if (!$inner) {
            return false;
        }
        $toc = '<details class="toc-container">';
        $toc .= '<summary><header><h4>' . Lang::get('wiki-toc-title') . '</h4></header></summary>';
        $toc .= $inner;
        $toc .= '</details>';
        return $toc;
    }

    /**
     * Generate ol, li and a tags for the TOC
     * @param string $htmlContent
     * @return string
     */
    protected static function generateInnerTOC($htmlContent): string
    {
        preg_match_all(
            '#<h([1-6])[^>]*\sid="([^"]+)"[^>]*>(.*?)<\/h\1>#is',
            $htmlContent,
            $headings,
            PREG_SET_ORDER
        );
        $toc = '';
        $current_level = 0;
        $items = 0;
        $baseLevel = null;

        foreach ($headings as $heading) {
            $id = $heading[2];
            $text = $heading[3];
            $link = '<a href="#' . $id . '">' . $text . '</a>';
            $level = (int) $heading[1];

            if ($baseLevel === null) {
                $baseLevel = $level;
            }

            $normalizedLevel = max(1, ($level - $baseLevel) + 1);
            $classLevel = max(1, min(6, $normalizedLevel));

            if ($normalizedLevel > $current_level) {
                // Create new ol and up to higher level
                for ($a = 0; $a < $normalizedLevel - $current_level; $a++) {
                    $toc .= "\n" . str_repeat("\t", $current_level * 2) . '<ol class="toc-level-' . $classLevel . '">' . "\n" . str_repeat("\t", ($current_level * 2) + 1) . '<li>';
                }
                $toc .= $link;
                $items = 1;
            } elseif ($normalizedLevel === $current_level) {
                $toc .= ($items ? '</li>' . "\n" : '') . str_repeat("\t", ($normalizedLevel * 2) - 1) . '<li>' . $link;
                $items++;
            } else {
                // Close ol and down level
                for ($a = 0; $a < $current_level - $normalizedLevel; $a++) {
                    $toc .= "\n" . str_repeat("\t", (($current_level - $a) * 2) - 1) . '</li>' .
                        "\n" . str_repeat("\t", (($current_level - $a) * 2) - 2) . '</ol>';
                }
                $toc .= "\n" . str_repeat("\t", ($normalizedLevel * 2) - 1) . '</li>' . "\n" . str_repeat("\t", ($normalizedLevel * 2) - 1) . '<li>' . $link;
                $items = 0;
            }
            $current_level = $normalizedLevel;
        }

        if ($baseLevel === null) {
            // No heading
            return false;
        }
        // Close all opened ol
        for ($a = $current_level - 1; $a >= 0; $a--) {
            $toc .= "\n" . str_repeat("\t", ($a * 2) + 1) . '</li>' .
                "\n" . str_repeat("\t", $a * 2) . '</ol>';
        }
        return $toc;
    }
}