<?php

namespace Core\Http;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * The Curl class provides methods to make HTTP requests using cURL.
 */
class Curl
{

    protected string $url = '';

    protected ?\CurlHandle $ch = null;

    protected array $options = [];

    protected string $method = 'GET';

    protected $datas = [];

    public bool $sendAsJson = false;

    protected array $response = [];

    /**
     * Curl constructor.
     * 
     * @param string $url The URL of the request.
     */
    public function __construct(string $url) {
        $this->url = $url;
    }

    /**
     * Set the URL for the request.
     *
     * @param string $url
     * @return self
     */
    public function url(string $url): self {
        $this->url = $url;
        return $this;
    }

/**
 * Set the HTTP request method to GET.
 *
 * @return self Returns the current Curl instance for method chaining.
 */

    public function get(): self {
        $this->method = 'GET';
        return $this;
    }

    /**
     * Set the HTTP request method to POST.
     *
     * @return self Returns the current Curl instance for method chaining.
     */
    public function post(): self {
        $this->method = 'POST';
        return $this;
    }

    /**
     * Set the HTTP request method to PUT.
     *
     * @return self Returns the current Curl instance for method chaining.
     */
    public function put(): self {
        $this->method = 'PUT';
        return $this;
    }

    /**
     * Set the HTTP request method to DELETE.
     *
     * @return self Returns the current Curl instance for method chaining.
     */

    public function delete(): self {
        $this->method = 'DELETE';
        return $this;
    }

    /**
     * Set the data for the request.
     *
     * @param array $datas The key-value pairs of data to send with the request.
     * @return self Returns the current Curl instance for method chaining.
     */
    public function setDatas($datas): self {
        $this->datas = $datas;
        return $this;
    }

    public function addOption($option, $value): self {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Execute the HTTP request.
     *
     * @return self Returns the current Curl instance for method chaining.
     */
    public function execute(): self {
        if (!$this->ch) {
            $this->ch = curl_init($this->url);
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        }
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->method === 'GET') {
            curl_setopt($this->ch, CURLOPT_HTTPGET, true);
            curl_setopt($this->ch, CURLOPT_URL, $this->url . '?' . http_build_query($this->datas));
        } else {
            if ($this->method !== 'POST') {
                curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->method);
            } else {
                curl_setopt($this->ch, CURLOPT_POST, true);
            }
            if ($this->sendAsJson) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($this->datas));
                curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen(json_encode($this->datas))
                ]);
            } else {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->datas));
            }
        }
        foreach ($this->options as $option => $value) {
            curl_setopt($this->ch, $option, $value);
        }
        $res = curl_exec($this->ch);
        $this->response = [
            'code' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE),
            'body' => $res
        ];
        curl_close($this->ch);
        return $this;
    }

    /**
     * Get the response from the HTTP request.
     *
     * @return array Array containing the HTTP response code and body.
     */
    public function getResponse(): array {
        return $this->response;
    }
}
