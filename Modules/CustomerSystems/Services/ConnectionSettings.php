<?php

namespace Modules\CustomerSystems\Services;

use InvalidArgumentException;
use RuntimeException;

class ConnectionSettings
{
    const META_KEY = 'customer_systems';

    public function load($mailbox)
    {
        $stored = $mailbox->getMeta(self::META_KEY);
        if ($stored !== null) {
            try {
                $settings = decrypt($stored);
                if (!is_array($settings)) {
                    throw new RuntimeException('credentials_unreadable');
                }
                return $settings;
            } catch (\Throwable $error) {
                throw new RuntimeException('credentials_unreadable');
            }
        }
        // Compatibility for installations configured before mailbox settings existed.
        $legacy = config('customersystems.shopware', []);
        return array_merge(self::defaults(), $legacy, [
            'type' => 'shopware5', 'enabled' => !empty($legacy['url']),
        ]);
    }

    public function save($mailbox, array $settings)
    {
        // Encrypt the entire payload: neither credentials nor their keys leak via model serialization.
        $mailbox->setMeta(self::META_KEY, encrypt($settings));
        $mailbox->save();
    }

    public static function defaults()
    {
        return ['enabled' => false, 'type' => 'shopware5', 'url' => '',
            'backend_url' => '', 'username' => '', 'api_key' => '', 'auth' => 'digest'];
    }

    public static function publicValues(array $settings)
    {
        unset($settings['api_key']);
        return $settings;
    }

    public static function validUrl($url)
    {
        $parts = parse_url($url);
        return filter_var($url, FILTER_VALIDATE_URL) && $parts
            && ($parts['scheme'] ?? '') === 'https' && !empty($parts['host'])
            && !isset($parts['user']) && !isset($parts['pass'])
            && !isset($parts['query']) && !isset($parts['fragment']);
    }

    public static function prepare(array $input, array $current)
    {
        $settings = self::defaults();
        foreach ($settings as $key => $default) {
            if ($key === 'enabled') {
                continue;
            }
            $value = $input[$key] ?? $default;
            if (!is_string($value) || strlen($value) > ($key === 'api_key' ? 4096 : 2048)) {
                throw new InvalidArgumentException('Ungültige oder zu lange Eingabe.');
            }
            $settings[$key] = $key === 'api_key' ? $value : trim($value);
        }
        $settings['enabled'] = in_array($input['enabled'] ?? '0', ['1', 1, true], true);
        if (!in_array($settings['type'], ['shopware5', 'shopware6'], true)
            || !in_array($settings['auth'], ['basic', 'digest'], true)) {
            throw new InvalidArgumentException('Bitte ein gültiges Shopware-System und Authentifizierungsverfahren wählen.');
        }
        $settings['url'] = rtrim($settings['url'], '/');
        $settings['backend_url'] = rtrim($settings['backend_url'], '/');
        foreach (['url', 'backend_url'] as $key) {
            if ($settings[$key] !== '' && !self::validUrl($settings[$key])) {
                throw new InvalidArgumentException('Bitte HTTPS-URLs ohne Zugangsdaten, Query oder Fragment eingeben.');
            }
        }
        $sameConnection = true;
        foreach (['type', 'url', 'username', 'auth'] as $key) {
            if ($settings[$key] !== ($current[$key] ?? self::defaults()[$key])) {
                $sameConnection = false;
            }
        }
        if ($settings['api_key'] === '' && $sameConnection) {
            $settings['api_key'] = $current['api_key'] ?? '';
        }
        if (($input['clear_secret'] ?? '') === '1') {
            $settings['api_key'] = '';
        }
        if ($settings['enabled'] && ($settings['url'] === '' || $settings['username'] === '' || $settings['api_key'] === '')) {
            throw new InvalidArgumentException('Für eine aktive Verbindung sind API-URL, API-Benutzer / Access-Key-ID und API-Key / Secret erforderlich. Bei geändertem System, API-Ziel oder Benutzer bitte den Schlüssel neu eingeben.');
        }
        return $settings;
    }
}
