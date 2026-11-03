<?php

namespace Modules\CustomerSystems\Services;

use RuntimeException;

class JsonHttpClient
{
    public function post($url, array $payload, array $headers)
    {
        return $this->request($url, json_encode($payload), $headers, 'application/json');
    }

    public function postForm($url, array $payload, array $headers)
    {
        return $this->request($url, http_build_query($payload, '', '&', PHP_QUERY_RFC3986), $headers, 'application/x-www-form-urlencoded');
    }

    private function request($url, $payload, array $headers, $contentType)
    {
        if (!ConnectionSettings::validUrl($url)) {
            throw new RuntimeException('not_configured');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('curl_missing');
        }
        $handle = curl_init($url);
        $body = '';
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            // application/json requests the flattened Admin API response, not JSON:API relationships.
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json', 'Content-Type: '.$contentType], $headers),
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            (defined('CURLOPT_PROTOCOLS_STR') ? CURLOPT_PROTOCOLS_STR : CURLOPT_PROTOCOLS) =>
                (defined('CURLOPT_PROTOCOLS_STR') ? 'https' : CURLPROTO_HTTPS),
            CURLOPT_WRITEFUNCTION => function ($handle, $chunk) use (&$body) {
                if (strlen($body) + strlen($chunk) > 2 * 1024 * 1024) { return 0; }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $errno = curl_errno($handle);
        // Dropping the final reference closes resources (PHP 7) and handles (PHP 8).
        // curl_close() is deprecated in PHP 8.5 and can become an ErrorException in Laravel.
        unset($handle);
        $phase = substr($url, -6) === '/token' ? 'oauth'
            : (substr($url, -13) === '/search/order' ? 'orders' : 'api');
        if ($ok === false) { throw new ApiException('unavailable', $status, $errno, $phase); }
        if ($status === 401) { throw new ApiException('authentication_failed', $status, $errno, $phase); }
        if ($status === 403) { throw new ApiException('permission_denied', $status, $errno, $phase); }
        if ($status !== 200) { throw new ApiException('unavailable', $status, $errno, $phase); }
        $data = json_decode($body, true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) { throw new ApiException('invalid_response', $status, $errno, $phase); }
        return $data;
    }
}
