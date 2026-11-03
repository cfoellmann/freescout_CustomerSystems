@if ($error)
    <p class="customer-section">{{ \Modules\CustomerSystems\Services\ConnectionError::message($error) }}</p>
@elseif (!count($customers))
    <p class="customer-section">Kein Shopware-Kunde für {{ $email }} gefunden.</p>
@else
    @if (count($customers) > 1)
        <p class="text-warning small">Mehrere Kunden mit dieser E-Mail gefunden. Bitte die Kundennummer prüfen.</p>
    @endif
    @foreach ($customers as $record)
        <div class="customer-section">
            <div class="clearfix">
                <span class="customer-name">{{ $record['name'] ?: ($record['company'] ?: 'Kunde') }}
                @if ($record['backend_url'])
                    <a href="{{ $record['backend_url'] }}" target="_blank" rel="noopener noreferrer" class="small" title="Kundenprofil im Shopware-Backend öffnen" aria-label="Kundenprofil im Shopware-Backend öffnen"><i class="glyphicon glyphicon-new-window" aria-hidden="true"></i></a>
                @endif
                </span>
            </div>
            @if ($record['company'] !== '' && $record['company'] !== $record['name'])
                <div>{{ $record['company'] }}</div>
            @endif
            @if ($record['number'] !== '')
                <div class="small">Kundennummer {{ $record['number'] }}</div>
            @endif
            <ul class="customer-contacts">
                @if ($record['email'] !== '')
                    <li class="customer-email"><a href="mailto:{{ $record['email'] }}" class="contact-main"><span class="sr-only">E-Mail: </span>{{ $record['email'] }}</a></li>
                @endif
                @if ($record['phone'] !== '')
                    <li class="customer-phone"><a href="tel:{{ $record['phone'] }}"><span class="sr-only">Telefon: </span>{{ $record['phone'] }}</a></li>
                @endif
            </ul>
            @if ($record['street'] !== '' || $record['city'] !== '' || $record['country'] !== '')
                <div class="customer-extra"><div class="customer-section">
                    <span class="sr-only">Rechnungsanschrift: </span>
                    @if ($record['street'] !== '')<div>{{ $record['street'] }}</div>@endif
                    @if ($record['postcode'] !== '' || $record['city'] !== '')<div>{{ trim($record['postcode'].' '.$record['city']) }}</div>@endif
                    @if ($record['country'] !== '')<div>{{ $record['country'] }}</div>@endif
                </div></div>
            @endif
        </div>
    @endforeach
@endif
