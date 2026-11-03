@if ($error)
    <p class="customer-section small">Bestellungen konnten nicht geladen werden. {{ \Modules\CustomerSystems\Services\ConnectionError::message($error) }}</p>
@elseif (!count($orders))
    <p class="customer-section small">Keine Bestellungen gefunden.</p>
@else
    <ul class="sidebar-block-list">
        @foreach ($orders as $order)
            <li>
                @if ($order['backend_url'])
                    <a href="{{ $order['backend_url'] }}" target="_blank" rel="noopener noreferrer" class="help-link" title="Bestellung im Shopware-Admin öffnen"><i class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></i>{{ $order['number'] }}</a>
                @else
                    <span><i class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></i> {{ $order['number'] }}</span>
                @endif
                <div class="small text-muted">{{ $order['date'] }} · {{ $order['status'] }}</div>
            </li>
        @endforeach
    </ul>
@endif
