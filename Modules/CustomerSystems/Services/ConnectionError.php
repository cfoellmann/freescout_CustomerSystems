<?php

namespace Modules\CustomerSystems\Services;

class ConnectionError
{
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
            'permission_denied' => 'Der Shopware-Zugang hat keine ausreichenden Leserechte auf Kunden.',
            'unavailable' => 'Shopware ist derzeit nicht erreichbar oder hat den Zugriff abgelehnt.',
        ];
        return $messages[$code] ?? $messages['unavailable'];
    }
}
