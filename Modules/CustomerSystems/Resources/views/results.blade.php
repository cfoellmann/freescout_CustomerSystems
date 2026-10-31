@php
    $messages = [
        'no_email' => 'Das Ticket enthält keine gültige Kunden-E-Mail.',
        'not_configured' => 'Die Shopware-Anbindung ist noch nicht vollständig konfiguriert.',
        'curl_missing' => 'Auf dem FreeScout-Server fehlt die PHP-cURL-Erweiterung.',
        'too_many_matches' => 'Mehr als fünf Kunden gefunden. Bitte die Zuordnung im Shop prüfen.',
        'invalid_response' => 'Shopware hat unerwartete oder abweichende Kundendaten geliefert.',
        'unavailable' => 'Shopware ist derzeit nicht erreichbar oder hat den Zugriff abgelehnt.',
    ];
@endphp
@if ($error)
    <p class="text-warning">{{ $messages[$error] ?? $messages['unavailable'] }}</p>
@elseif (!count($customers))
    <p class="text-muted">Kein Shopware-Kunde für {{ $email }} gefunden.</p>
@else
    @if (count($customers) > 1)
        <p class="text-warning">Mehrere Kunden mit dieser E-Mail gefunden. Bitte Kundennummer und Shop prüfen.</p>
    @endif
    @foreach ($customers as $record)
        <dl>
            @foreach (['number' => 'Kundennummer', 'id' => 'Shopware-ID', 'shop_id' => 'Shop-ID', 'company' => 'Firma', 'name' => 'Name', 'email' => 'E-Mail', 'phone' => 'Telefon'] as $key => $label)
                @if ($record[$key] !== '')
                    <dt>{{ $label }}</dt><dd>{{ $record[$key] }}</dd>
                @endif
            @endforeach
            @if ($record['street'] !== '' || $record['city'] !== '')
                <dt>Rechnungsanschrift</dt>
                <dd>{{ $record['street'] }}<br>{{ trim($record['postcode'].' '.$record['city']) }}<br>{{ $record['country'] }}</dd>
            @endif
        </dl>
        @if ($record['backend_url'])
            <p><a href="{{ $record['backend_url'] }}" target="_blank" rel="noopener noreferrer">Kundenprofil im Shopware-Backend öffnen ↗</a></p>
        @endif
    @endforeach
@endif
