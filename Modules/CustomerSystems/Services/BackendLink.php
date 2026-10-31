<?php

namespace Modules\CustomerSystems\Services;

class BackendLink
{
    public static function customer($backendUrl, $id)
    {
        $url = rtrim(trim((string) $backendUrl), '/');
        $parts = parse_url($url);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query'])
            || isset($parts['fragment']) || !ctype_digit((string) $id) || (int) $id < 1) {
            return null;
        }
        return $url.'/?'.http_build_query(['customerSystemsCustomerId' => (string) $id], '', '&', PHP_QUERY_RFC3986);
    }
}
