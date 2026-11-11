<?php

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

abstract class AntispamAbstractCaptcha {

    protected string $lastReason = '';

    public function getLastReason(): string {
        return $this->lastReason;
    }

    public function isGenericValid():bool {
        if (isset($_POST['ant_name_a']) && !empty($_POST['ant_name_a'])) {
            // If bot has filled the form, antispam is not valid
            $this->lastReason = 'honeypot';
            return false;
        }
        return true;
    }

    public function getGenericHtml():string {
        return '<input type="text" name="ant_name_a" id="first_name_a" value="" class="antispam-hp-field" tabindex="-1" autocomplete="off" />';
    }
}