@if ($error)
    <p class="customer-section small">Bestellungen konnten nicht geladen werden. {{ \Modules\CustomerSystems\Services\ConnectionError::message($error) }}</p>
@elseif (!count($orders))
    <p class="customer-section small">Keine Bestellungen gefunden.</p>
@else
    <ul class="sidebar-block-list">
        @foreach ($orders as $order)
            @php
                $status = \Modules\CustomerSystems\Services\OrderPresentation::status($order);
                $number = \Modules\CustomerSystems\Services\OrderPresentation::compact($order['number'], 10);
                $date = substr($order['date'], 0, 6).substr($order['date'], -2);
            @endphp
            <li class="small text-nowrap">
                @if ($order['backend_url'])
                    <a href="{{ $order['backend_url'] }}" target="_blank" rel="noopener noreferrer" class="help-link" title="Bestellung {{ $order['number'] }} im Shopware-Admin öffnen"><i class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></i>{{ $number }}</a>
                @else
                    <span title="{{ $order['number'] }}"><i class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></i> {{ $number }}</span>
                @endif
                <span class="text-muted" title="{{ $order['date'] }}">· {{ $date }}</span>
                <span class="label {{ $status['class'] }}" title="{{ $order['status'] }}" aria-label="Bestellstatus: {{ $order['status'] }}">{{ $status['label'] }}</span>
            </li>
        @endforeach
    </ul>
@endif
