<?php

namespace Modules\CustomerSystems\Services;

use RuntimeException;

class JsonHttpClient
{
    public function post($url, array $payload, array $headers)
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
            CURLOPT_POSTFIELDS => json_encode($payload),
            // application/json requests the flattened Admin API response, not JSON:API relationships.
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json', 'Content-Type: application/json'], $headers),
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => function ($handle, $chunk) use (&$body) {
                if (strlen($body) + strlen($chunk) > 2 * 1024 * 1024) { return 0; }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if ($ok === false) { throw new RuntimeException('unavailable'); }
        if ($status === 401) { throw new RuntimeException('authentication_failed'); }
        if ($status === 403) { throw new RuntimeException('permission_denied'); }
        if ($status !== 200) { throw new RuntimeException('unavailable'); }
        $data = json_decode($body, true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) { throw new RuntimeException('invalid_response'); }
        return $data;
    }
}
