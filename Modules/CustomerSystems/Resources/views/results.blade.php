@if ($error)
    <p class="text-muted">{{ \Modules\CustomerSystems\Services\ConnectionError::message($error) }}</p>
@elseif (!count($customers))
    <p class="text-muted">Kein Shopware-Kunde für {{ $email }} gefunden.</p>
@else
    @if (count($customers) > 1)
        <p class="text-warning">Mehrere Kunden mit dieser E-Mail gefunden. Bitte Kundennummer und Shop prüfen.</p>
    @endif
    @foreach ($customers as $record)
        <dl>
            @foreach (['number' => 'Kundennummer', 'id' => 'Shopware-ID', 'shop_id' => 'Shop / Verkaufskanal', 'company' => 'Firma', 'name' => 'Name', 'email' => 'E-Mail', 'phone' => 'Telefon'] as $key => $label)
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
