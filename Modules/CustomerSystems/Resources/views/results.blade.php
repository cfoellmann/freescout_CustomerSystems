@if ($error)
    <div class="panel panel-default"><div class="panel-body">
        <p class="customer-section">{{ \Modules\CustomerSystems\Services\ConnectionError::message($error) }}</p>
        <button type="button" class="customer-systems-retry btn btn-link btn-xs sidebar-block-link">Aktualisieren</button>
    </div></div>
@elseif (!count($customers))
    <div class="panel panel-default"><div class="panel-body">
        <p class="customer-section">Kein Shopware-Kunde für {{ $email }} gefunden.</p>
        <button type="button" class="customer-systems-retry btn btn-link btn-xs sidebar-block-link">Aktualisieren</button>
    </div></div>
@else
    @if (count($customers) > 1)
        <p class="text-warning small">Mehrere Kunden mit dieser E-Mail gefunden. Bitte die Kundennummer prüfen.</p>
    @endif
    @foreach ($customers as $record)
        <div class="panel panel-default">
            <div class="panel-heading"><h4 class="panel-title">
                @if ($record['backend_url'])
                    <a href="{{ $record['backend_url'] }}" target="_blank" rel="noopener noreferrer" title="Kundenprofil im Shopware-Backend öffnen">{{ $record['name'] ?: ($record['company'] ?: 'Kunde') }} <i class="glyphicon glyphicon-new-window" aria-hidden="true"></i></a>
                @else
                    <div><strong>{{ $record['name'] ?: ($record['company'] ?: 'Kunde') }}</strong></div>
                @endif
            </h4></div>
            <div class="panel-body"><div class="customer-section">
                <div class="sidebar-block-header2"><strong>{{ $record['name'] ?: ($record['company'] ?: 'Kunde') }}</strong>
                    @if ($record['backend_url'])<a href="{{ $record['backend_url'] }}" target="_blank" rel="noopener noreferrer" title="Kundenprofil im Shopware-Backend öffnen" aria-label="Kundenprofil im Shopware-Backend öffnen"><i class="glyphicon glyphicon-new-window" aria-hidden="true"></i></a>@endif
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
                <div class="customer-extra"><div class="customer-section">
                    <strong>Letzte Bestellungen</strong>
                    <div class="customer-systems-orders" data-url="{{ $record['orders_url'] }}" aria-live="polite"><p>Bestellungen werden geladen …</p></div>
                </div></div>
                <button type="button" class="customer-systems-retry btn btn-link btn-xs sidebar-block-link"><i class="glyphicon glyphicon-refresh" aria-hidden="true"></i> Aktualisieren</button>
            </div></div>
        </div>
    @endforeach
@endif
