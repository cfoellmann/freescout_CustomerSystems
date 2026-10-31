<?php

namespace Modules\CustomerSystems\Services;

use RuntimeException;

class ShopwareTransport
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function __invoke($path, array $query)
    {
        $url = rtrim((string) ($this->config['url'] ?? ''), '/');
        $parts = parse_url($url);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query'])
            || isset($parts['fragment']) || empty($this->config['username'])
            || empty($this->config['api_key'])
            || !in_array($this->config['auth'] ?? '', ['basic', 'digest'], true)) {
            throw new RuntimeException('not_configured');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('curl_missing');
        }
        $target = $url.'/'.$path;
        if ($query) {
            $target .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        $handle = curl_init($target);
        $body = '';
        curl_setopt_array($handle, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPAUTH => $this->config['auth'] === 'basic' ? CURLAUTH_BASIC : CURLAUTH_DIGEST,
            CURLOPT_USERPWD => $this->config['username'].':'.$this->config['api_key'],
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => function ($handle, $chunk) use (&$body) {
                if (strlen($body) + strlen($chunk) > 2 * 1024 * 1024) {
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if ($ok === false || $status !== 200) {
            throw new RuntimeException('unavailable');
        }
        $data = json_decode($body, true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('invalid_response');
        }
        return $data;
    }
}
