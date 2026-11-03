<?php

namespace Modules\CustomerSystems\Services;

class BackendLink
{
    public static function order($backendUrl, $id)
    {
        // Reuse the HTTPS/base-URL and ID validation shared with customer links.
        if (!self::customer($backendUrl, $id)) { return null; }
        $url = rtrim(trim((string) $backendUrl), '/');
        return $url.'#/sw/order/detail/'.strtolower($id).'/general';
    }

    public static function customer($backendUrl, $id)
    {
        $url = rtrim(trim((string) $backendUrl), '/');
        $parts = parse_url($url);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query'])
            || isset($parts['fragment'])) {
            return null;
        }
        return preg_match('/^[a-f0-9]{32}$/i', (string) $id)
            ? $url.'#/sw/customer/detail/'.strtolower($id).'/base' : null;
    }
}
