<?php

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

class AntispamReCaptcha {

    protected $publicKey;
    protected $secretKey;

    public function __construct($publicKey, $secretKey) {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
    }

    public function getText() {
        $input = '<input type="hidden" id="recaptchaResponse" name="recaptcha-response">';
        $script = '<script src="https://www.google.com/recaptcha/api.js?render=' . $this->publicKey . '"></script>';
        $script .= '<script>grecaptcha.ready(function() {';
        $script .= "grecaptcha.execute('" . $this->publicKey . "', {action: 'homepage'}).then(function(token) {";
        $script .= "document.getElementById('recaptchaResponse').value = token;";
        $script .= "});});</script>";
        $infos = '<p>Protection par ReCaptcha. <a href="https://www.google.com/intl/fr/policies/privacy/">Confidentialité</a>'
                . ' - <a href="https://www.google.com/intl/fr/policies/terms/">Conditions</a></p>';
        return $input . $infos . $script;
    }

    public function isValid() {
        if (!isset($_POST['recaptcha-response']) || empty($_POST['recaptcha-response'])) {
            // Captcha not set or empty
            return false;
        }
        $url = "https://www.google.com/recaptcha/api/siteverify?secret="
                . $this->secretKey . "&response=" . $_POST['recaptcha-response'];
        // Verify that CURL is available
        if (function_exists('curl_version')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
        } else {
            // Use file_get_contents
            $response = file_get_contents($url);
        }
        if (empty($response) || is_null($response)) {
            // Bad config or no response by API
            return false;
        }
        $data = json_decode($response);
        if ($data->success) {
            // Captcha is OK
            return true;
        }
        return false;
    }
}