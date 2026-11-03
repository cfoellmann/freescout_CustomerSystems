<?php

namespace Modules\CustomerSystems\Services;

class BueroPlusSettings
{
    const META_KEY = 'customer_systems_bueroplus';

    public static function defaults()
    {
        return ['url' => '', 'client_id' => '', 'client_secret' => ''];
    }

    public function load($mailbox)
    {
        $stored = $mailbox->getMeta(self::META_KEY);
        if ($stored === null) { return self::defaults(); }
        try {
            $settings = decrypt($stored);
            if (!is_array($settings)) { throw new \RuntimeException(); }
            return array_merge(self::defaults(), array_intersect_key($settings, self::defaults()));
        } catch (\Throwable $error) {
            throw new \RuntimeException('credentials_unreadable');
        }
    }

    public function save($mailbox, array $settings)
    {
        $mailbox->setMeta(self::META_KEY, encrypt(array_intersect_key($settings, self::defaults())));
        $mailbox->save();
    }

    public static function publicValues(array $settings)
    {
        unset($settings['client_secret']);
        return $settings;
    }

    public static function prepare(array $input, array $current, $required = false)
    {
        $settings = self::defaults();
        foreach ($settings as $key => $default) {
            $value = $input[$key] ?? '';
            if (!is_string($value) || strlen($value) > 2048) {
                throw new \InvalidArgumentException('Ungültige oder zu lange büro+-Eingabe.');
            }
            $settings[$key] = trim($value);
        }
        $settings['url'] = rtrim($settings['url'], '/');
        if ($settings['url'] !== '') {
            $host = parse_url($settings['url'], PHP_URL_HOST);
            if (!ConnectionSettings::validUrl($settings['url']) || !$host || strtolower($host) === 'localhost'
                || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) {
                throw new \InvalidArgumentException('büro+ benötigt eine HTTPS-Basis-URL mit DNS-Namen, ohne Zugangsdaten, Query oder Fragment.');
            }
            if (preg_match('~/(?:token|graphql/v1)$~i', $settings['url'])) {
                throw new \InvalidArgumentException('Die büro+-Basis-URL ohne /token oder /graphql/v1 eingeben.');
            }
        }
        // microtech generates base64url credentials. Reject separators/header characters.
        foreach (['client_id', 'client_secret'] as $key) {
            if ($settings[$key] !== '' && !preg_match('/^[A-Za-z0-9_-]+$/D', $settings[$key])) {
                throw new \InvalidArgumentException('Client-ID und Client-Secret unverändert aus der microtech-Anwendung übernehmen.');
            }
        }
        if ($settings['client_secret'] === '' && $settings['url'] === ($current['url'] ?? '')
            && $settings['client_id'] === ($current['client_id'] ?? '')) {
            $settings['client_secret'] = $current['client_secret'] ?? '';
        }
        if (($input['clear_secret'] ?? '') === '1') { $settings['client_secret'] = ''; }
        if ($required && in_array('', $settings, true)) {
            throw new \InvalidArgumentException('Für den Test bitte büro+-Basis-URL, Client-ID und Client-Secret eingeben. Bei geändertem Ziel oder Client das Secret erneut eingeben.');
        }
        return $settings;
    }
}
