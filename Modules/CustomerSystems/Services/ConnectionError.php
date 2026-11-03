<?php

namespace Modules\CustomerSystems\Services;

class ConnectionError
{
    public static function forBueroPlus(\Throwable $error)
    {
        $reason = DiagnosticLog::reason($error);
        if ($reason === 'authentication_failed') {
            $message = 'büro+ hat die Anmeldung abgelehnt. Client-ID, Client-Secret und den verknüpften Benutzer prüfen.';
        } elseif ($reason === 'permission_denied') {
            $message = 'büro+ verweigert den Zugriff. GraphQL-Freigabe des Benutzers und den Scope graphql prüfen.';
        } elseif ($reason === 'graphql_failed') {
            $message = 'büro+ meldet einen GraphQL-Fehler. Schema und API-Berechtigungen prüfen.';
        } elseif ($reason === 'invalid_response') {
            $message = 'büro+ hat eine ungültige OAuth- oder GraphQL-Antwort geliefert.';
        } else {
            $message = str_replace(['Shopware', 'Kunden-API'], ['büro+', 'GraphQL-API'], self::forException($error));
            return $message;
        }
        if ($error instanceof ApiException) {
            $message .= ' (HTTP '.$error->httpStatus.') Schritt: '.($error->phase === 'oauth' ? 'OAuth-Anmeldung.' : 'GraphQL-API.');
        }
        return $message;
    }

    public static function forException(\Throwable $error)
    {
        $reason = DiagnosticLog::reason($error);
        if ($reason === 'unexpected_error') {
            $details = DiagnosticLog::details($error);
            return 'Interner PHP-Fehler im FreeScout-Server: '.$details['exception_type'].
                ' bei '.$details['source_file'].':'.$details['source_line'].' (PHP '.$details['php_version'].').'.
                (($details['php_error'] ?? '') === 'deprecated_api' ? ' Eine veraltete PHP-Funktion wurde als Fehler behandelt.' : '');
        }
        if ($error instanceof ApiException) {
            if ($error->curlErrno !== 0) {
                $network = [
                    6 => 'DNS-Auflösung des Shopware-Servers fehlgeschlagen.',
                    7 => 'Verbindung zum Shopware-Server fehlgeschlagen.',
                    23 => 'Die Shopware-Antwort konnte nicht vollständig verarbeitet werden.',
                    28 => 'Zeitüberschreitung beim Verbindungsaufbau oder Abruf.',
                    35 => 'TLS-Verbindungsaufbau fehlgeschlagen.',
                    60 => 'Das TLS-Zertifikat wird vom FreeScout-Server nicht als vertrauenswürdig erkannt. CA-Zertifikate auf dem Server prüfen.',
                ];
                return ($network[$error->curlErrno] ?? 'Netzwerkfehler beim Shopware-Abruf.').' (cURL '.$error->curlErrno.')';
            }
            $message = self::message($reason).' (HTTP '.$error->httpStatus.')';
            if ($error->httpStatus >= 300 && $error->httpStatus < 400) {
                $message = 'Shopware antwortet mit einer Weiterleitung (HTTP '.$error->httpStatus.'). Bitte die endgültige HTTPS-API-URL eintragen.';
            }
            return $message.' Schritt: '.($error->phase === 'oauth' ? 'OAuth-Anmeldung.'
                : ($error->phase === 'orders' ? 'Bestell-API.' : 'Kunden-API.'));
        }
        return self::message($reason);
    }

    public static function message($code)
    {
        $messages = [
            'disabled' => 'Die Shopware-Verbindung ist für dieses Postfach deaktiviert.',
            'no_email' => 'Das Ticket enthält keine gültige Kunden-E-Mail.',
            'not_configured' => 'Die Shopware-Verbindung ist noch nicht vollständig konfiguriert.',
            'credentials_unreadable' => 'Die gespeicherten Zugangsdaten konnten nicht entschlüsselt werden. Bitte im Postfach neu speichern.',
            'curl_missing' => 'Auf dem FreeScout-Server fehlt die PHP-cURL-Erweiterung.',
            'too_many_matches' => 'Mehr als fünf Kunden gefunden. Bitte die Zuordnung im Shop prüfen.',
            'invalid_response' => 'Shopware hat unerwartete oder abweichende Kundendaten geliefert.',
            'authentication_failed' => 'Shopware hat die Zugangsdaten abgelehnt. Bitte Benutzer / Access-Key-ID und Schlüssel prüfen.',
            'permission_denied' => 'Der Shopware-Zugang hat nicht alle erforderlichen Leserechte auf Kunden, Bestellungen oder deren Status.',
            'unavailable' => 'Shopware ist derzeit nicht erreichbar oder hat den Zugriff abgelehnt.',
        ];
        return $messages[$code] ?? $messages['unavailable'];
    }
}
